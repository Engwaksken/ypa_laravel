<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuthFlowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // The production `users` table already exists in MySQL; the test
        // database is sqlite :memory:, so reproduce the schema here.
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name', 150)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('password', 255)->nullable();
            $table->string('role', 50)->default('customer');
            $table->string('status', 20)->default('active');
            $table->timestamp('created_at')->useCurrent();
            $table->unsignedInteger('branch_id')->nullable()->default(1);
            $table->string('profile_pic', 255)->nullable();
            $table->string('verification_code', 6)->nullable();
            $table->dateTime('code_expires')->nullable();
            $table->string('remember_token', 100)->nullable();
        });
    }

    public function test_login_page_renders(): void
    {
        $this->get(route('login'))->assertOk();
    }

    public function test_verify_page_requires_login_flow(): void
    {
        $this->get(route('verify'))->assertRedirect(route('login'));
    }

    public function test_dashboard_requires_authentication(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_login_with_invalid_credentials_shows_error(): void
    {
        $this->post(route('login.attempt'), [
            'email' => 'nobody@example.com',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('email');
    }

    public function test_full_otp_login_flow(): void
    {
        Mail::fake();

        $user = User::create([
            'name' => 'Test Admin',
            'email' => 'admin@test.local',
            'password' => Hash::make('secret123'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->post(route('login.attempt'), [
            'email' => 'admin@test.local',
            'password' => 'secret123',
        ])->assertRedirect(route('verify'));

        $fresh = $user->fresh();
        $this->assertNotNull($fresh->verification_code);
        $this->assertNotNull($fresh->code_expires);

        $this->post(route('verify.attempt'), ['code' => $fresh->verification_code])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($fresh->fresh());
    }

    public function test_verify_rejects_wrong_code(): void
    {
        Mail::fake();

        $user = User::create([
            'name' => 'Test Admin',
            'email' => 'admin2@test.local',
            'password' => Hash::make('secret123'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->post(route('login.attempt'), [
            'email' => 'admin2@test.local',
            'password' => 'secret123',
        ])->assertRedirect(route('verify'));

        $this->post(route('verify.attempt'), ['code' => '000000'])
            ->assertSessionHasErrors('code');

        $this->assertGuest();
    }

    public function test_inactive_user_is_logged_out(): void
    {
        $user = User::create([
            'name' => 'Inactive User',
            'email' => 'inactive@test.local',
            'password' => Hash::make('secret123'),
            'role' => 'admin',
            'status' => 'inactive',
        ]);

        $this->actingAs($user);

        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_authenticated_user_hitting_login_is_redirected_to_home(): void
    {
        $user = User::create([
            'name' => 'Test Admin',
            'email' => 'admin3@test.local',
            'password' => Hash::make('secret123'),
            'role' => 'admin',
            'status' => 'active',
        ]);

        $this->actingAs($user);

        // The `guest` middleware sends authenticated users to the `home`
        // route. Without it the user bounced / -> login -> / forever.
        $this->get(route('login'))
            ->assertRedirect(route('home'));

        // Following the chain lands on the dashboard.
        $this->followingRedirects()
            ->get(route('login'))
            ->assertOk();
    }
}