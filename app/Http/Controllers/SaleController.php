<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSaleRequest;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\Stock;
use App\Services\BranchAccess;
use App\Services\SaleService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SaleController extends Controller
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth'), new Middleware('user.status'),
            new Middleware('permission:manage_sales,pos,point_of_sale,sales_view,view_sales'),
            (new Middleware('permission:sales_create,create_sales,pos,point_of_sale'))->only(['store']),
            (new Middleware('throttle:30,1'))->only(['store']),
        ];
    }

    public function index(Request $request)
    {
        $branches = app(BranchAccess::class)->branches()->where('status', true)->get();
        $branchId = (int) $request->query('branch_id', auth()->user()->branch_id ?: $branches->first()?->id);
        app(BranchAccess::class)->authorize($branchId);
        abort_unless($branches->contains('id', $branchId), 422, 'Select an active branch.');
        $catalog = $this->catalog($branchId);
        $customers = $catalog['customers'];
        $products = $catalog['products'];
        $paymentMethods = $catalog['paymentMethods'];
        $sales = app(BranchAccess::class)->scope(Sale::query())->where('branch_id', $branchId)->latest('id')->paginate(20)->withQueryString();
        $requestToken = old('request_token', (string) Str::uuid());
        return view('sales.index', compact('branches', 'branchId', 'customers', 'products', 'paymentMethods', 'sales', 'requestToken'));
    }

    public function products(Request $request)
    {
        $data = $request->validate(['branch_id' => ['required', 'integer', 'exists:branches,id']]);
        app(BranchAccess::class)->authorize($data['branch_id']);
        return response()->json($this->catalog((int) $data['branch_id']));
    }

    public function store(StoreSaleRequest $request, SaleService $service)
    {
        $sale = $service->create($request->validated());
        return $request->expectsJson()
            ? response()->json(['success' => true, 'sale_id' => $sale->id, 'invoice_no' => $sale->invoice_no,
                'total' => $sale->total_amount, 'amount_paid' => $sale->partial_amount,
                'balance_amount' => $sale->balance_amount, 'receipt_url' => route('sales.show', $sale)])
            : redirect()->route('sales.show', $sale)->with('success', 'Sale recorded successfully.');
    }

    public function show(Sale $sale)
    {
        app(BranchAccess::class)->authorize($sale->branch_id);
        $sale->load(['items.product', 'customer', 'branch']);
        $branch = $sale->branch;
        $receipt = DB::table('payment_transactions')->where('reference', $sale->invoice_no)->where('branch_id', $sale->branch_id)->where('status', 'APPROVED')->first();
        return view('sales.show', compact('sale', 'branch', 'receipt'));
    }

    private function catalog(int $branchId): array
    {
        $products = Stock::query()->with('product')->where('branch_id', $branchId)->where('quantity', '>', 0)
            ->where('selling_price', '>', 0)->where(fn ($q) => $q->whereNull('expiry_date')->orWhereDate('expiry_date', '>=', today()))
            ->whereHas('product', fn ($q) => $q->where('is_active', true))->orderBy('id')->get()->unique('product_id')
            ->map(fn ($stock) => ['id' => $stock->product_id, 'name' => $stock->product->name, 'sku' => $stock->product->sku,
                'quantity' => (int) $stock->quantity, 'selling_price' => $stock->selling_price])->values();
        $customers = Customer::query()->where('branch_id', $branchId)->orderBy('name')->get(['id', 'name'])->map(fn ($customer) => (object) ['id' => (string) $customer->id, 'name' => $customer->name]);
        foreach (['members' => 'member', 'groups' => 'group'] as $table => $type) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'branch_id')) {
                foreach (DB::table($table)->where('branch_id', $branchId)->get() as $payer) {
                    $name = $type === 'member' ? trim(implode(' ', array_filter([$payer->first_name ?? '', $payer->other_name ?? '', $payer->last_name ?? '']))) : $payer->group_name;
                    $customers->push((object) ['id' => $type . '_' . $payer->id, 'name' => $name . ' (' . ucfirst($type) . ')']);
                }
            }
        }
        return [
            'products' => $products,
            'customers' => $customers,
            'paymentMethods' => DB::table('payment_methods')->whereIn('status', ['active', '1'])->orderBy('method_name')->get(['id', 'method_name']),
        ];
    }
}
