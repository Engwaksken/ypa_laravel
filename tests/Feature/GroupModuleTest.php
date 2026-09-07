<?php

namespace Tests\Feature;

use App\Models\Group;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class GroupModuleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Reproduce the production users table (from AuthFlowTest).
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 150)->nullable();
                $table->string('email', 150)->nullable();
                $table->string('password', 255)->nullable();
                $table->string('role', 50)->default('member');
                $table->string('status', 20)->default('active');
                $table->timestamp('created_at')->useCurrent();
                $table->unsignedInteger('branch_id')->nullable()->default(1);
                $table->string('profile_pic', 255)->nullable();
                $table->string('verification_code', 6)->nullable();
                $table->dateTime('code_expires')->nullable();
                $table->string('remember_token', 100)->nullable();
            });
        }

        // Reproduce the branches table.
        if (!Schema::hasTable('branches')) {
            Schema::create('branches', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('name');
                $table->string('location')->nullable();
                $table->string('contact')->nullable();
                $table->string('branch_email')->nullable();
                $table->boolean('status')->default(true);
                $table->timestamps();
            });
        }

        // Reproduce role_permissions (needed by PermissionService for non-super roles).
        if (!Schema::hasTable('role_permissions')) {
            Schema::create('role_permissions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('role');
                $table->string('permission');
                $table->timestamps();
            });
        }

        // Reproduce mobilizers (needed for the groups FK).
        if (!Schema::hasTable('mobilizers')) {
            Schema::create('mobilizers', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('department')->nullable();
                $table->string('position')->nullable();
                $table->string('contact_number')->nullable();
                $table->string('email')->nullable();
                $table->string('branch_region')->nullable()->index();
                $table->string('supervisor')->nullable();
                $table->string('status')->nullable()->default('Active');
                $table->text('remarks')->nullable();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->timestamps();
            });
        }

        // Reproduce the Groups module tables.
        if (!Schema::hasTable('groups')) {
            Schema::create('groups', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('group_code')->nullable()->index();
                $table->string('group_name');
                $table->string('group_category')->nullable();
                $table->string('category_other')->nullable();
                $table->string('country')->nullable();
                $table->string('uganda_subregion')->nullable();
                $table->string('uganda_district')->nullable();
                $table->date('formation_date')->nullable();
                $table->unsignedBigInteger('mobilizer_id')->nullable()->index();
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->unsignedBigInteger('coordinator_id')->nullable()->index();
                $table->string('bank_name')->nullable();
                $table->string('bank_account_name')->nullable();
                $table->string('bank_account_number')->nullable();
                $table->json('non_member_coordinators')->nullable();
                $table->string('status')->nullable()->default('Active');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('group_members')) {
            Schema::create('group_members', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('group_id')->index();
                $table->unsignedBigInteger('member_id')->index();
                $table->string('role')->nullable();
                $table->date('joined_at')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('group_coordinators')) {
            Schema::create('group_coordinators', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('group_id')->index();
                $table->unsignedBigInteger('coordinator_id')->index();
                $table->string('role')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('group_non_member_coordinators')) {
            Schema::create('group_non_member_coordinators', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('group_id')->index();
                $table->string('name')->nullable();
                $table->string('phone')->nullable();
                $table->string('email')->nullable();
                $table->string('role')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('group_documents')) {
            Schema::create('group_documents', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('group_id')->index();
                $table->string('document_name')->nullable();
                $table->string('file_path')->nullable();
                $table->unsignedBigInteger('uploaded_by')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('group_contributions')) {
            Schema::create('group_contributions', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('group_id')->index();
                $table->unsignedBigInteger('member_id')->nullable()->index();
                $table->decimal('amount', 15, 2)->nullable();
                $table->date('contribution_date')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('group_next_of_kin')) {
            Schema::create('group_next_of_kin', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('group_id')->index();
                $table->unsignedBigInteger('member_id')->nullable()->index();
                $table->string('name')->nullable();
                $table->string('phone')->nullable();
                $table->string('relationship')->nullable();
                $table->timestamps();
            });
        }

        // Seed a branch for FK references.
        if (\DB::table('branches')->count() === 0) {
            \DB::table('branches')->insert([
                'name' => 'Kampala',
                'location' => 'Kampala',
                'status' => 1,
                'created_at' => now(),
            ]);
        }
    }

    /* ---------------------------------------------------------------
       Helpers
    --------------------------------------------------------------- */

    protected function createAdmin(): User
    {
        return User::create([
            'name' => 'Test Admin',
            'email' => 'admin_' . uniqid() . '@test.local',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'status' => 'active',
        ]);
    }

    protected function createMemberUser(): User
    {
        return User::create([
            'name' => 'Test Member',
            'email' => 'member_' . uniqid() . '@test.local',
            'password' => Hash::make('password'),
            'role' => 'member',
            'status' => 'active',
        ]);
    }

    protected function createMobilizer(): \App\Models\Mobilizer
    {
        return \App\Models\Mobilizer::create([
            'first_name' => 'Mobi',
            'last_name' => 'Lizer',
            'department' => 'Field Operations',
            'position' => 'Mobilizer',
            'contact_number' => '+256780000001',
            'email' => 'mobi_' . uniqid() . '@test.local',
            'branch_region' => 'Kampala',
            'status' => 'Active',
        ]);
    }

    protected function createGroup(array $overrides = []): Group
    {
        $branch = \DB::table('branches')->first();
        $mobilizer = $this->createMobilizer();

        return Group::create(array_merge([
            'group_code' => 'GRP-2026-' . str_pad((string) Group::count() + 1, 6, '0', STR_PAD_LEFT),
            'group_name' => 'Test Group ' . uniqid(),
            'group_category' => 'Business Group',
            'country' => 'Uganda',
            'uganda_subregion' => 'Central',
            'uganda_district' => 'Kampala',
            'formation_date' => '2024-01-15',
            'mobilizer_id' => $mobilizer->id,
            'branch_id' => $branch->id,
            'status' => 'Active',
        ], $overrides));
    }

    /* ---------------------------------------------------------------
       Auth guard tests
    --------------------------------------------------------------- */

    public function test_guest_is_redirected_to_login_for_groups_index(): void
    {
        $this->get(route('groups.index'))->assertRedirect(route('login'));
    }

    public function test_admin_can_view_groups_index(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)->get(route('groups.index'))
            ->assertOk();
    }

    public function test_member_role_gets_403_on_groups_index(): void
    {
        $member = $this->createMemberUser();

        $this->actingAs($member)->get(route('groups.index'))
            ->assertStatus(403);
    }

    /* ---------------------------------------------------------------
       Groups CRUD
    --------------------------------------------------------------- */

    public function test_admin_can_view_create_group_form(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)->get(route('groups.create'))
            ->assertOk();
    }

    public function test_admin_can_store_group(): void
    {
        $admin = $this->createAdmin();
        $branch = \DB::table('branches')->first();
        $mobilizer = $this->createMobilizer();

        $data = [
            'group_name' => 'Kampala Farmers Cooperative',
            'group_category' => 'Farmer Producer Group / Farmer Cooperative',
            'country' => 'Uganda',
            'uganda_subregion' => 'Central',
            'uganda_district' => 'Kampala',
            'formation_date' => '2024-03-10',
            'mobilizer_id' => $mobilizer->id,
            'branch_id' => $branch->id,
            'status' => 'Active',
        ];

        $this->actingAs($admin)->post(route('groups.store'), $data)
            ->assertRedirect();

        $group = Group::where('group_name', 'Kampala Farmers Cooperative')->first();
        $this->assertNotNull($group);
        $this->assertMatchesRegularExpression('/^GRP-2026-\d{6}$/', $group->group_code);
        $this->assertEquals('Farmer Producer Group / Farmer Cooperative', $group->group_category);
        $this->assertEquals('Kampala', $group->uganda_district);
        $this->assertEquals($branch->id, $group->branch_id);
        $this->assertEquals($mobilizer->id, $group->mobilizer_id);
        $this->assertEquals('Active', $group->status);
    }

    public function test_store_group_validates_required_fields(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)->post(route('groups.store'), [])
            ->assertSessionHasErrors(['group_name', 'group_category', 'formation_date', 'mobilizer_id', 'branch_id']);
    }

    public function test_store_group_requires_uganda_fields_when_country_is_uganda(): void
    {
        $admin = $this->createAdmin();
        $branch = \DB::table('branches')->first();
        $mobilizer = $this->createMobilizer();

        $data = [
            'group_name' => 'Missing Uganda Fields',
            'group_category' => 'Business Group',
            'country' => 'Uganda',
            'formation_date' => '2024-03-10',
            'mobilizer_id' => $mobilizer->id,
            'branch_id' => $branch->id,
        ];

        $this->actingAs($admin)->post(route('groups.store'), $data)
            ->assertSessionHasErrors(['uganda_subregion', 'uganda_district']);
    }

    public function test_store_group_requires_category_other_when_category_is_other(): void
    {
        $admin = $this->createAdmin();
        $branch = \DB::table('branches')->first();
        $mobilizer = $this->createMobilizer();

        $data = [
            'group_name' => 'Other Category Group',
            'group_category' => 'Other',
            'country' => 'Kenya',
            'formation_date' => '2024-03-10',
            'mobilizer_id' => $mobilizer->id,
            'branch_id' => $branch->id,
        ];

        $this->actingAs($admin)->post(route('groups.store'), $data)
            ->assertSessionHasErrors('category_other');
    }

    public function test_store_group_rejects_duplicate_name_in_same_country(): void
    {
        $admin = $this->createAdmin();
        $branch = \DB::table('branches')->first();
        $mobilizer = $this->createMobilizer();
        $name = 'Duplicate Group ' . uniqid();

        $this->createGroup(['group_name' => $name, 'country' => 'Uganda']);

        $data = [
            'group_name' => $name,
            'group_category' => 'Business Group',
            'country' => 'Uganda',
            'uganda_subregion' => 'Central',
            'uganda_district' => 'Kampala',
            'formation_date' => '2024-03-10',
            'mobilizer_id' => $mobilizer->id,
            'branch_id' => $branch->id,
        ];

        $this->actingAs($admin)->post(route('groups.store'), $data)
            ->assertSessionHasErrors('group_name');
    }

    public function test_admin_can_view_group(): void
    {
        $admin = $this->createAdmin();
        $group = $this->createGroup();

        $this->actingAs($admin)->get(route('groups.show', $group))
            ->assertOk();
    }

    public function test_admin_can_update_group(): void
    {
        $admin = $this->createAdmin();
        $group = $this->createGroup();
        $branch = \DB::table('branches')->first();
        $mobilizer = $this->createMobilizer();

        $data = [
            'group_name' => 'Updated Group Name',
            'group_category' => 'Village Savings and Loan Association (VSLA)',
            'country' => 'Uganda',
            'uganda_subregion' => 'Western',
            'uganda_district' => 'Mbarara',
            'formation_date' => '2023-06-01',
            'mobilizer_id' => $mobilizer->id,
            'branch_id' => $branch->id,
            'status' => 'Inactive',
        ];

        $this->actingAs($admin)->put(route('groups.update', $group), $data)
            ->assertRedirect();

        $group->refresh();
        $this->assertEquals('Updated Group Name', $group->group_name);
        $this->assertEquals('Village Savings and Loan Association (VSLA)', $group->group_category);
        $this->assertEquals('Mbarara', $group->uganda_district);
        $this->assertEquals('Inactive', $group->status);
        $this->assertEquals($admin->id, $group->updated_by);
    }

    public function test_admin_can_delete_group(): void
    {
        $admin = $this->createAdmin();
        $group = $this->createGroup();

        $this->actingAs($admin)->delete(route('groups.destroy', $group))
            ->assertRedirect();

        $this->assertDatabaseMissing('groups', ['id' => $group->id]);
    }

    /* ---------------------------------------------------------------
       Export
    --------------------------------------------------------------- */

    public function test_admin_can_export_groups_csv(): void
    {
        $admin = $this->createAdmin();
        $this->createGroup();

        $response = $this->actingAs($admin)->get(route('groups.export'));
        $response->assertOk();

        $content = $response->streamedContent();
        $this->assertStringContainsString('Group Code', $content);
        $this->assertStringContainsString('GRP-2026-', $content);
    }

    /* ---------------------------------------------------------------
       AJAX endpoints
    --------------------------------------------------------------- */

    public function test_guest_is_redirected_to_login_for_group_details_ajax(): void
    {
        $this->get(route('groups.ajax.details', ['id' => 1]))
            ->assertRedirect(route('login'));
    }

    public function test_group_details_ajax_route_has_permission_middleware(): void
    {
        $route = Route::getRoutes()->getByName('groups.ajax.details');

        $this->assertNotNull($route);
        $this->assertMatchesRegularExpression('/permission.*groups/i', implode(',', $route->gatherMiddleware()));
    }

    public function test_admin_can_get_group_details_ajax(): void
    {
        $admin = $this->createAdmin();
        $group = $this->createGroup();

        $this->actingAs($admin)->get(route('groups.ajax.details', ['id' => $group->id]))
            ->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonPath('group.group_code', $group->group_code)
            ->assertJsonPath('group.group_name', $group->group_name);
    }

    public function test_member_role_gets_403_on_group_details_ajax(): void
    {
        $member = $this->createMemberUser();
        $group = $this->createGroup();

        $this->actingAs($member)->get(route('groups.ajax.details', ['id' => $group->id]))
            ->assertStatus(403);
    }

    public function test_store_group_rejects_invalid_non_member_coordinators_json(): void
    {
        $admin = $this->createAdmin();
        $branch = \DB::table('branches')->first();
        $mobilizer = $this->createMobilizer();

        $data = [
            'group_name' => 'Invalid JSON Group',
            'group_category' => 'Business Group',
            'country' => 'Kenya',
            'formation_date' => '2024-03-10',
            'mobilizer_id' => $mobilizer->id,
            'branch_id' => $branch->id,
            'non_member_coordinators' => '{invalid-json',
        ];

        $this->actingAs($admin)->post(route('groups.store'), $data)
            ->assertSessionHasErrors('non_member_coordinators');
    }

    /* ---------------------------------------------------------------
       Group code sequencing
    --------------------------------------------------------------- */

    public function test_group_codes_are_sequential(): void
    {
        $admin = $this->createAdmin();
        $branch = \DB::table('branches')->first();
        $mobilizer = $this->createMobilizer();

        $base = [
            'group_category' => 'Business Group',
            'country' => 'Uganda',
            'uganda_subregion' => 'Central',
            'uganda_district' => 'Kampala',
            'formation_date' => '2024-03-10',
            'mobilizer_id' => $mobilizer->id,
            'branch_id' => $branch->id,
        ];

        $this->actingAs($admin)->post(route('groups.store'), array_merge($base, ['group_name' => 'Seq Group One']))->assertRedirect();
        $this->actingAs($admin)->post(route('groups.store'), array_merge($base, ['group_name' => 'Seq Group Two']))->assertRedirect();

        $groups = Group::query()->orderBy('id')->get();
        $this->assertCount(2, $groups);
        $this->assertSame('GRP-2026-000001', $groups[0]->group_code);
        $this->assertSame('GRP-2026-000002', $groups[1]->group_code);
    }
}
