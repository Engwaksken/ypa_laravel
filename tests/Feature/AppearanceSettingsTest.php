<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use App\Services\AppearanceService;
use App\Services\PermissionService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AppearanceSettingsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('settings', function (Blueprint $table) {
            $table->increments('id'); $table->string('setting_key')->unique(); $table->text('setting_value')->nullable();
        });
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id'); $table->string('name'); $table->string('email'); $table->string('password');
            $table->string('role'); $table->string('status'); $table->timestamp('created_at')->nullable();
        });
        $permissions = \Mockery::mock(PermissionService::class)->makePartial();
        $permissions->shouldReceive('can')->andReturn(true);
        $this->app->instance(PermissionService::class, $permissions);
        $this->loginAs('super_admin');
    }

    private function loginAs(string $role): void
    {
        $user = User::create(['name' => 'Settings Operator', 'email' => $role . '@example.test', 'password' => bcrypt('password'), 'role' => $role, 'status' => 'active']);
        $this->actingAs($user);
    }

    public function test_super_admin_can_save_and_render_theme_wording_and_print_sizes(): void
    {
        $this->get(route('settings.index'))->assertOk()->assertSee('theme_primary')->assertSee('receipt_paper');
        $this->put(route('settings.update'), [
            'site_name' => 'New Brand', 'theme_primary' => '#123456', 'theme_font' => 'serif', 'theme_base_size' => 18,
            'invoice_title' => 'Tax Invoice', 'invoice_footer' => 'Thank you <script>alert(1)</script>',
            'receipt_paper' => '80mm', 'invoice_paper' => 'a5', 'receipt_font_size' => 10, 'contract_paper' => 'letter',
        ])->assertRedirect(route('settings.index'))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('settings', ['setting_key' => 'theme_primary', 'setting_value' => '#123456']);
        $this->get(route('settings.index'))->assertOk()->assertSee('--ds-brand: #123456;', false)->assertSee('font-size: 18px', false)->assertSee('Tax Invoice');
        $html = view('documents.wording', ['kind' => 'invoice', 'position' => 'footer'])->render();
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $print = view('documents.print-settings', ['kind' => 'receipt'])->render();
        $this->assertStringContainsString('size: 80mm 297mm', $print);
        $this->assertStringContainsString('font-size: 10pt', $print);
        $this->assertSame('letter', app(AppearanceService::class)->get('contract_paper'));
    }

    public function test_non_super_admin_with_settings_permission_cannot_submit_customizations(): void
    {
        $this->loginAs('admin');
        $this->get(route('settings.index'))->assertOk()->assertDontSee('name="theme_primary"', false);
        foreach (['theme_primary' => '#ffffff', 'invoice_title' => 'Forged', 'receipt_paper' => '58mm'] as $key => $value) {
            $this->put(route('settings.update'), ['site_name' => 'Brand', $key => $value])->assertForbidden();
        }
        $this->assertDatabaseCount('settings', 0);
        $this->put(route('settings.update'), ['site_name' => 'Existing permission'])->assertRedirect();
    }

    public function test_payment_receipt_renders_custom_wording_and_paper_size(): void
    {
        foreach (['receipt_title' => 'Official Cash Receipt', 'receipt_header' => 'Finance office',
            'receipt_footer' => 'Retain for your records', 'receipt_terms' => 'Payments are subject to clearance.',
            'receipt_paper' => 'a5'] as $key => $value) {
            Setting::create(['setting_key' => $key, 'setting_value' => $value]);
        }
        $payment = new \App\Models\PaymentTransaction;
        $payment->forceFill(['id' => 1, 'receipt_number' => 'REC-TEST', 'amount' => 10, 'status' => 'APPROVED']);
        foreach (['contract', 'creator', 'transactionType', 'paymentMethod'] as $relation) $payment->setRelation($relation, null);
        $html = view('payments.receipt', ['payment' => $payment, 'errors' => new \Illuminate\Support\ViewErrorBag])->render();
        foreach (['Official Cash Receipt', 'Finance office', 'Retain for your records', 'Payments are subject to clearance.', 'size: a5'] as $text) {
            $this->assertStringContainsString($text, $html);
        }
    }

    public function test_invalid_css_fonts_sizes_and_paper_are_rejected_without_partial_saves(): void
    {
        $this->putJson(route('settings.update'), [
            'site_name' => 'Invalid', 'theme_primary' => 'red;}</style><script>alert(1)</script>',
            'theme_font' => 'url(https://evil.test)', 'theme_base_size' => 1000,
            'receipt_paper' => 'unknown', 'contract_margin' => -1, 'invoice_title' => str_repeat('x', 2001),
        ])->assertUnprocessable()->assertJsonValidationErrors(['theme_primary', 'theme_font', 'theme_base_size', 'receipt_paper', 'contract_margin', 'invoice_title']);
        $this->assertDatabaseCount('settings', 0);
    }

    public function test_setting_cache_is_invalidated_and_unsafe_stored_values_use_safe_defaults(): void
    {
        $this->assertSame('Default', Setting::value('site_name', 'Default'));
        Setting::create(['setting_key' => 'site_name', 'setting_value' => 'Changed']);
        $this->assertSame('Changed', Setting::value('site_name'));
        Setting::create(['setting_key' => 'theme_primary', 'setting_value' => '</style><script>bad</script>']);
        $this->assertSame('#005fa3', app(AppearanceService::class)->get('theme_primary'));
        $html = view('layouts.partials.theme')->render();
        $this->assertStringNotContainsString('<script>bad', $html);
    }
}
