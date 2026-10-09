<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\Stock;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class SaleService
{
    public function create(array $data): Sale
    {
        app(BranchAccess::class)->authorize($data['branch_id']);
        $canonical = [
            'branch_id' => (int) $data['branch_id'], 'customer_id' => (int) ($data['customer_id'] ?? 0),
            'customer_type' => $data['customer_type'] ?? null, 'customer_ref_id' => (int) ($data['customer_ref_id'] ?? 0),
            'payment_method_id' => (int) $data['payment_method_id'], 'payment_status' => $data['payment_status'],
            'discount' => $this->cents($data['discount'] ?? 0), 'amount_paid' => $this->cents($data['amount_paid'] ?? 0),
            'items' => collect($data['items'])->sortBy('id')->map(fn ($item) => [(int) $item['id'], (int) $item['quantity'], isset($item['expected_price']) ? $this->cents($item['expected_price']) : null])->values()->all(),
        ];
        $hash = hash('sha256', json_encode($canonical, JSON_THROW_ON_ERROR));
        return DB::transaction(function () use ($data, $hash, $canonical) {
            // Serialize branch sale writes, including repeated first submissions.
            Branch::query()->whereKey($data['branch_id'])->where('status', true)->lockForUpdate()->firstOrFail();
            $replay = DB::table('pos_sale_requests')->where('request_token', $data['request_token'])->lockForUpdate()->first();
            if ($replay) {
                abort_unless((int) $replay->user_id === (int) auth()->id() && (int) $replay->branch_id === (int) $data['branch_id'], 403);
                $this->ensure(hash_equals($replay->payload_hash, $hash), 'request_token', 'This submission token was already used for a different sale. Refresh the sales form.');
                return Sale::findOrFail($replay->sale_id);
            }
            $customer = $this->resolveCustomer($data);
            $this->ensure(!$customer || (int) $customer->branch_id === (int) $data['branch_id'], 'customer_id', 'Select a customer in this branch.');
            $this->ensure($customer || $data['payment_status'] === 'paid', 'customer_id', 'Credit and partial sales require a registered customer, member, or group.');
            $method = DB::table('payment_methods')->where('id', $data['payment_method_id'])->first();
            $this->ensure($method && in_array(strtolower((string) $method->status), ['active', '1'], true), 'payment_method_id', 'Select an active payment method.');
            $items = [];
            $subtotal = 0;
            foreach ($canonical['items'] as [$productId, $quantity, $expectedPrice]) {
                $stock = Stock::query()->with('product')->where('product_id', $productId)->where('branch_id', $data['branch_id'])->orderBy('id')->lockForUpdate()->first();
                $this->ensure($stock && $stock->product?->is_active && (int) $stock->quantity >= $quantity, 'items', 'A selected product has insufficient stock or is unavailable.');
                $this->ensure(!$stock->expiry_date || !$stock->expiry_date->lt(today()), 'items', 'A selected product has expired.');
                $price = $this->cents($stock->selling_price ?? 0);
                $this->ensure($price > 0 && $price <= 100000000000, 'items', 'A selected product has an unavailable selling price.');
                $this->ensure($expectedPrice === null || $expectedPrice === $price, 'items', 'The selling price changed. Review the refreshed total before retrying.');
                $lineTotal = $price * $quantity;
                $subtotal += $lineTotal;
                $this->ensure($subtotal <= 99999999999999, 'items', 'The sale exceeds the supported amount.');
                $items[] = ['stock' => $stock, 'quantity' => $quantity, 'price' => $price, 'total' => $lineTotal];
            }
            $discount = $canonical['discount'];
            $this->ensure($discount <= $subtotal, 'discount', 'Discount cannot exceed the sale total.');
            $total = $subtotal - $discount;
            $paid = match ($data['payment_status']) { 'paid' => $total, 'oncredit' => 0, default => $canonical['amount_paid'] };
            $this->ensure($paid <= $total, 'amount_paid', 'Payment cannot exceed the sale total.');
            $this->ensure($data['payment_status'] !== 'partially paid' || ($paid > 0 && $paid < $total), 'amount_paid', 'Partial payment must be greater than zero and less than the sale total.');
            $invoice = 'INV-' . now()->format('Ymd') . '-' . Str::uuid();
            $saleValues = [
                'customer_id' => $customer?->id, 'user_id' => auth()->id(), 'branch_id' => $data['branch_id'],
                'invoice_no' => $invoice, 'total_amount' => $this->money($total), 'balance_amount' => $this->money($total - $paid),
                'discount' => $this->money($discount), 'payment_method' => (string) $method->id,
                'payment_status' => $data['payment_status'], 'partial_amount' => $this->money($paid), 'paid_amount' => $this->money($paid),
                'customer_type' => $customer ? 'customer' : 'walkin', 'customer_ref_id' => $customer?->id,
                'customer_name' => $customer?->name ?? 'Walk-in Customer', 'customer_phone' => $customer?->phone ?? '',
                'sale_date' => today()->toDateString(), 'created_at' => now(),
            ];
            $sale = Sale::create($this->supported('sales', $saleValues, ['customer_id', 'user_id', 'branch_id', 'invoice_no', 'total_amount', 'balance_amount', 'sale_date']));
            foreach ($items as $item) {
                $stock = $item['stock'];
                DB::table('sale_items')->insert($this->supported('sale_items', [
                    'sale_id' => $sale->id, 'product_id' => $stock->product_id, 'quantity' => $item['quantity'],
                    'price' => $this->money($item['price']), 'total' => $this->money($item['total']),
                    'cost_price' => $stock->cost_price, 'product_name' => $stock->product->name,
                ], ['sale_id', 'product_id', 'quantity', 'price', 'total']));
                $stock->decrement('quantity', $item['quantity']);
            }
            if ($paid > 0) {
                $accountId = (int) ($method->chart_account_id ?? 0);
                $chartTable = Schema::hasTable('chart_accounts') ? 'chart_accounts' : 'chart_of_accounts';
                $this->ensure($accountId > 0 && Schema::hasTable($chartTable) && DB::table($chartTable)->where('id', $accountId)->exists(), 'payment_method_id', 'The payment method needs a valid linked chart account.');
                $type = DB::table('payment_transaction_types')->where('type_name', 'Sales Invoice Payment')->first();
                $typeId = $type?->id ?? DB::table('payment_transaction_types')->insertGetId($this->supported('payment_transaction_types', [
                    'type_name' => 'Sales Invoice Payment', 'is_active' => 1, 'created_at' => now(), 'updated_at' => now(),
                ], ['type_name']));
                DB::table('payment_transactions')->insert($this->supported('payment_transactions', [
                    'transaction_number' => 'PAY-' . Str::uuid(), 'receipt_number' => 'REC-' . Str::uuid(),
                    'account_or_mobile' => $method->method_name, 'payment_type' => 'Sales Invoice Payment',
                    'payer_type' => $customer ? 'customer' : 'walkin', 'customer_id' => $customer?->id,
                    'chart_account_id' => $accountId, 'branch_id' => $data['branch_id'], 'transaction_type_id' => $typeId,
                    'transaction_date' => now(), 'amount' => $this->money($paid), 'net_amount' => $this->money($paid),
                    'payment_method_id' => $method->id, 'total_amount' => $this->money($total), 'total_amount_paid' => $this->money($paid),
                    'total_amount_outstanding' => $this->money($total - $paid), 'reference' => $invoice,
                    'notes' => 'Sales payment for invoice ' . $invoice, 'status' => 'APPROVED', 'reconciliation_status' => 'UNRECONCILED',
                    'created_by' => auth()->id(), 'approved_by' => auth()->id(), 'approval_date' => now(), 'created_at' => now(), 'updated_at' => now(),
                ], ['branch_id', 'reference', 'receipt_number', 'payment_method_id', 'amount', 'total_amount_paid', 'status', 'transaction_date']));
            }
            DB::table('pos_sale_requests')->insert([
                'request_token' => $data['request_token'], 'user_id' => auth()->id(), 'branch_id' => $data['branch_id'],
                'sale_id' => $sale->id, 'payload_hash' => $hash, 'created_at' => now(),
            ]);
            return $sale;
        }, 3);
    }

    private function supported(string $table, array $values, array $required): array
    {
        $columns = Schema::getColumnListing($table);
        $this->ensure(!array_diff($required, $columns), 'items', 'The sales database schema is incomplete. Run the production readiness check.');
        return array_intersect_key($values, array_flip($columns));
    }

    private function resolveCustomer(array $data): ?Customer
    {
        if (!empty($data['customer_id'])) return Customer::findOrFail($data['customer_id']);
        if (empty($data['customer_type']) || empty($data['customer_ref_id'])) return null;
        $type = $data['customer_type'];
        $table = $type === 'member' ? 'members' : 'groups';
        $this->ensure(Schema::hasTable($table) && Schema::hasColumn($table, 'branch_id'), 'customer_id', 'This payer type requires its branch-aware legacy schema.');
        $payer = DB::table($table)->where('id', $data['customer_ref_id'])->where('branch_id', $data['branch_id'])->first();
        $this->ensure((bool) $payer, 'customer_id', 'The selected payer is unavailable in this branch.');
        $name = $type === 'member' ? trim(implode(' ', array_filter([$payer->first_name ?? '', $payer->other_name ?? '', $payer->last_name ?? '']))) : $payer->group_name;
        $phone = $payer->telephone1 ?? $payer->phone ?? $payer->telephone ?? null;
        $query = Customer::query()->where('branch_id', $data['branch_id'])->where('customer_type', $type);
        if ($type === 'member') $query->where('member_id', $payer->id);
        else $query->where('name', $name)->where('phone', $phone);
        return $query->first() ?? Customer::create([
            'branch_id' => $data['branch_id'], 'customer_type' => $type, 'member_id' => $type === 'member' ? $payer->id : null,
            'name' => $name ?: ucfirst($type) . ' ' . $payer->id, 'phone' => $phone,
            'email' => $payer->email ?? null, 'address' => $payer->address ?? $payer->village ?? null,
        ]);
    }

    private function ensure(bool $condition, string $field, string $message): void
    {
        if (!$condition) throw ValidationException::withMessages([$field => $message]);
    }
    private function cents(mixed $value): int { return (int) round((float) $value * 100); }
    private function money(int $cents): string { return sprintf('%d.%02d', intdiv($cents, 100), $cents % 100); }
}
