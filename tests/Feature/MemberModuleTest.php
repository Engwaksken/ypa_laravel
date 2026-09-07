<?php

namespace Tests\Feature;

use App\Models\Member;
use App\Models\MemberBankDetail;
use App\Models\MemberNextOfKin;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MemberModuleTest extends TestCase
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

        // Reproduce the Members module tables.
        if (!Schema::hasTable('members')) {
            Schema::create('members', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('membership_id')->unique();
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('other_name')->nullable();
                $table->date('date_of_birth')->nullable();
                $table->enum('sex', ['Male', 'Female', 'Other'])->nullable();
                $table->string('gender')->nullable();
                $table->string('nin')->nullable()->unique();
                $table->string('tin_number')->nullable();
                $table->string('tin')->nullable();
                $table->string('tax_identification_number')->nullable();
                $table->string('nationality')->nullable();
                $table->text('address')->nullable();
                $table->enum('region', ['Central', 'Eastern', 'Western', 'Northern'])->nullable();
                $table->string('district_residence')->nullable();
                $table->string('district')->nullable();
                $table->enum('employment_status', [
                    'Employed (Full-time)',
                    'Employed (Part-time)',
                    'Self-employed',
                    'Casual / Temporary worker',
                    'Contract employee',
                    'Unemployed',
                    'Student',
                    'Retired',
                    'Farmer / Agribusiness operator',
                    'Business owner / Entrepreneur',
                    'Informal sector worker',
                    'Other',
                ])->nullable();
                $table->string('employment_other')->nullable();
                $table->enum('marital_status', ['Single', 'Married', 'Divorced', 'Separated'])->nullable();
                $table->integer('children_count')->nullable()->default(0);
                $table->enum('source', ['Radio', 'TV', 'Social Media', 'YPA Website', 'Outreaches', 'Exhibitions', 'Personal', 'Referral', 'Other'])->nullable();
                $table->string('source_type')->nullable();
                $table->string('source_station')->nullable();
                $table->string('radio_station')->nullable();
                $table->string('tv_station')->nullable();
                $table->string('source_other')->nullable();
                $table->string('email')->nullable();
                $table->string('telephone1')->nullable();
                $table->string('telephone2')->nullable();
                $table->string('mother_name')->nullable();
                $table->string('mother_phone')->nullable();
                $table->string('father_name')->nullable();
                $table->string('father_phone')->nullable();
                $table->enum('account_type', ['Personal', 'Joint', 'Group', 'Infant'])->nullable();
                $table->string('bank_account')->nullable();
                $table->string('bank_account_name')->nullable();
                $table->string('bank_name')->nullable();
                $table->string('bank_branch')->nullable();
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->unsignedBigInteger('mobilizer_id')->nullable()->index();
                $table->enum('membership_status', ['Active', 'Pending', 'Suspended', 'Expired', 'Inactive'])->nullable()->default('Pending');
                $table->decimal('membership_fee_amount', 15, 2)->nullable();
                $table->decimal('membership_fee_paid', 15, 2)->nullable();
                $table->decimal('membership_outstanding', 15, 2)->nullable();
                $table->string('membership_payment_method')->nullable();
                $table->string('membership_payment_reference')->nullable();
                $table->string('membership_receipt_number')->nullable();
                $table->string('membership_payment_status')->nullable();
                $table->dateTime('membership_paid_at')->nullable();
                $table->unsignedBigInteger('membership_payment_transaction_id')->nullable();
                $table->string('member_photo')->nullable();
                $table->string('photo')->nullable();
                $table->string('image_url')->nullable();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('member_next_of_kin')) {
            Schema::create('member_next_of_kin', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('member_id')->index();
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('nin')->nullable();
                $table->date('date_of_birth')->nullable();
                $table->date('dob')->nullable();
                $table->string('relationship')->nullable();
                $table->text('address')->nullable();
                $table->string('phone')->nullable();
                $table->string('telephone1')->nullable();
                $table->string('telephone2')->nullable();
                $table->string('email')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('member_bank_details')) {
            Schema::create('member_bank_details', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('member_id')->unique();
                $table->string('bank_account')->nullable();
                $table->string('account_name')->nullable();
                $table->string('bank_name')->nullable();
                $table->string('bank_branch')->nullable();
                $table->timestamps();
            });
        }

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

        if (!Schema::hasTable('member_mobilization')) {
            Schema::create('member_mobilization', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('member_id')->index();
                $table->unsignedBigInteger('mobilizer_id')->index();
                $table->date('mobilized_date')->nullable();
                $table->string('channel')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->unique(['member_id', 'mobilizer_id', 'mobilized_date']);
            });
        }

        // Seed a branch for FK references.
        if (Member::query()->count() === 0 && \DB::table('branches')->count() === 0) {
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

    protected function createMember(array $overrides = []): Member
    {
        $branch = \DB::table('branches')->first();
        $mobilizer = $this->createMobilizer();

        return Member::create(array_merge([
            'membership_id' => 'KAM-2026-' . str_pad((string) Member::count() + 1, 6, '0', STR_PAD_LEFT),
            'first_name' => 'Test',
            'last_name' => 'Member',
            'sex' => 'Male',
            'nationality' => 'Uganda',
            'address' => 'Plot 1 Kampala',
            'region' => 'Central',
            'district_residence' => 'Kampala',
            'district' => 'Kampala',
            'employment_status' => 'Employed (Full-time)',
            'marital_status' => 'Single',
            'source' => 'Radio',
            'source_station' => 'Impact FM',
            'telephone1' => '+256780000010',
            'email' => 'member_' . uniqid() . '@test.local',
            'branch_id' => $branch->id,
            'mobilizer_id' => $mobilizer->id,
            'membership_status' => 'Active',
        ], $overrides));
    }

    /* ---------------------------------------------------------------
       Auth guard tests
    --------------------------------------------------------------- */

    public function test_guest_is_redirected_to_login_for_members_index(): void
    {
        $this->get(route('members.index'))->assertRedirect(route('login'));
    }

    public function test_admin_can_view_members_index(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)->get(route('members.index'))
            ->assertOk();
    }

    public function test_member_role_gets_403_on_members_index(): void
    {
        $member = $this->createMemberUser();

        $this->actingAs($member)->get(route('members.index'))
            ->assertStatus(403);
    }

    /* ---------------------------------------------------------------
       Members CRUD
    --------------------------------------------------------------- */

    public function test_admin_can_view_create_member_form(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)->get(route('members.create'))
            ->assertOk();
    }

    public function test_admin_can_store_member(): void
    {
        $admin = $this->createAdmin();
        $branch = \DB::table('branches')->first();
        $mobilizer = $this->createMobilizer();

        $data = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'date_of_birth' => '1990-05-15',
            'sex' => 'Male',
            'nationality' => 'Uganda',
            'address' => '123 Main St',
            'region' => 'Central',
            'district_residence' => 'Kampala',
            'district' => 'Kampala',
            'employment_status' => 'Self-employed',
            'marital_status' => 'Single',
            'source' => 'Radio',
            'source_station' => 'Prime FM',
            'telephone1' => '+256780000100',
            'email' => 'john.doe_' . uniqid() . '@test.com',
            'branch_id' => $branch->id,
            'mobilizer_id' => $mobilizer->id,
        ];

        $this->actingAs($admin)->post(route('members.store'), $data)
            ->assertRedirect();

        $member = Member::where('email', $data['email'])->first();
        $this->assertNotNull($member);
        $this->assertEquals('John', $member->first_name);
        $this->assertEquals('Doe', $member->last_name);
        $this->assertEquals('Pending', $member->membership_status);
        $this->assertMatchesRegularExpression('/^KAM-2026-\d{6}$/', $member->membership_id);
        $this->assertEquals($branch->id, $member->branch_id);
        $this->assertEquals($mobilizer->id, $member->mobilizer_id);
    }

    public function test_store_member_validates_required_fields(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)->post(route('members.store'), [])
            ->assertSessionHasErrors(['first_name', 'last_name', 'sex', 'address', 'employment_status', 'marital_status', 'source', 'telephone1', 'branch_id', 'mobilizer_id']);
    }

    public function test_store_member_rejects_invalid_phone(): void
    {
        $admin = $this->createAdmin();
        $branch = \DB::table('branches')->first();
        $mobilizer = $this->createMobilizer();

        $data = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'date_of_birth' => '1990-05-15',
            'sex' => 'Male',
            'address' => '123 Main St',
            'employment_status' => 'Self-employed',
            'marital_status' => 'Single',
            'source' => 'Radio',
            'source_station' => 'Prime FM',
            'telephone1' => '0781234567', // missing +256 prefix
            'branch_id' => $branch->id,
            'mobilizer_id' => $mobilizer->id,
        ];

        $this->actingAs($admin)->post(route('members.store'), $data)
            ->assertSessionHasErrors('telephone1');
    }

    public function test_store_member_rejects_duplicate_email(): void
    {
        $admin = $this->createAdmin();
        $branch = \DB::table('branches')->first();
        $mobilizer = $this->createMobilizer();
        $email = 'dupe_' . uniqid() . '@test.com';

        // Create first member.
        $this->createMember(['email' => $email, 'branch_id' => $branch->id, 'mobilizer_id' => $mobilizer->id]);

        $data = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'date_of_birth' => '1990-05-15',
            'sex' => 'Male',
            'nationality' => 'Uganda',
            'address' => '123 Main St',
            'region' => 'Central',
            'district_residence' => 'Kampala',
            'district' => 'Kampala',
            'employment_status' => 'Self-employed',
            'marital_status' => 'Single',
            'source' => 'Radio',
            'source_station' => 'Prime FM',
            'telephone1' => '+256780000200',
            'email' => $email,
            'branch_id' => $branch->id,
            'mobilizer_id' => $mobilizer->id,
        ];

        $this->actingAs($admin)->post(route('members.store'), $data)
            ->assertSessionHasErrors('email');
    }

    public function test_admin_can_view_member(): void
    {
        $admin = $this->createAdmin();
        $member = $this->createMember();

        $this->actingAs($admin)->get(route('members.show', $member))
            ->assertOk();
    }

    public function test_admin_can_update_member(): void
    {
        $admin = $this->createAdmin();
        $member = $this->createMember();
        $branch = \DB::table('branches')->first();
        $mobilizer = $this->createMobilizer();

        $data = [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'date_of_birth' => '1992-08-20',
            'sex' => 'Female',
            'nationality' => 'Uganda',
            'address' => '456 New St',
            'region' => 'Eastern',
            'district_residence' => 'Jinja',
            'district' => 'Jinja',
            'employment_status' => 'Student',
            'marital_status' => 'Single',
            'source' => 'TV',
            'source_station' => 'BBS',
            'telephone1' => '+256780000300',
            'branch_id' => $branch->id,
            'mobilizer_id' => $mobilizer->id,
        ];

        $this->actingAs($admin)->put(route('members.update', $member), $data)
            ->assertRedirect();

        $member->refresh();
        $this->assertEquals('Jane', $member->first_name);
        $this->assertEquals('Smith', $member->last_name);
        $this->assertEquals($admin->id, $member->updated_by);
    }

    public function test_admin_can_delete_member(): void
    {
        $admin = $this->createAdmin();
        $member = $this->createMember();

        $this->actingAs($admin)->delete(route('members.destroy', $member))
            ->assertRedirect();

        $this->assertDatabaseMissing('members', ['id' => $member->id]);
    }

    /* ---------------------------------------------------------------
       Export
    --------------------------------------------------------------- */

    public function test_admin_can_export_members_csv(): void
    {
        $admin = $this->createAdmin();
        $this->createMember();

        $response = $this->actingAs($admin)->get(route('members.export'));
        $response->assertOk();

        $content = $response->streamedContent();
        $this->assertStringContainsString('Membership ID', $content);
        $this->assertStringContainsString('KAM-2026-', $content);
    }

    /* ---------------------------------------------------------------
       Mobilizers CRUD
    --------------------------------------------------------------- */

    public function test_admin_can_view_mobilizers_index(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)->get(route('mobilizers.index'))
            ->assertOk();
    }

    public function test_admin_can_store_mobilizer(): void
    {
        $admin = $this->createAdmin();
        $branch = \DB::table('branches')->first();

        $data = [
            'first_name' => 'Jane',
            'last_name' => 'Mobilizer',
            'department' => 'Field Operations',
            'position' => 'Senior Mobilizer',
            'contact_number' => '+256780000400',
            'email' => 'jane_' . uniqid() . '@test.com',
            'branch_region' => $branch->name,
            'status' => 'Active',
        ];

        $this->actingAs($admin)->post(route('mobilizers.store'), $data)
            ->assertRedirect(route('mobilizers.index'));

        $this->assertDatabaseHas('mobilizers', [
            'first_name' => 'Jane',
            'last_name' => 'Mobilizer',
            'contact_number' => '+256780000400',
        ]);
    }

    public function test_store_mobilizer_validates_required_fields(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)->post(route('mobilizers.store'), [])
            ->assertSessionHasErrors(['first_name', 'last_name', 'department', 'position', 'contact_number']);
    }

    public function test_store_mobilizer_rejects_invalid_contact(): void
    {
        $admin = $this->createAdmin();
        $branch = \DB::table('branches')->first();

        $data = [
            'first_name' => 'Jane',
            'last_name' => 'Mobilizer',
            'department' => 'Field Operations',
            'position' => 'Mobilizer',
            'contact_number' => '0781234', // invalid
            'branch_region' => $branch->name,
            'status' => 'Active',
        ];

        $this->actingAs($admin)->post(route('mobilizers.store'), $data)
            ->assertSessionHasErrors('contact_number');
    }

    public function test_admin_can_update_mobilizer(): void
    {
        $admin = $this->createAdmin();
        $mobilizer = $this->createMobilizer();

        $data = [
            'first_name' => 'Updated',
            'last_name' => 'Mobilizer',
            'department' => 'Sales',
            'position' => 'Team Leader',
            'contact_number' => '+256780000500',
            'branch_region' => 'Kampala',
            'status' => 'Inactive',
        ];

        $this->actingAs($admin)->put(route('mobilizers.update', $mobilizer), $data)
            ->assertRedirect(route('mobilizers.index'));

        $mobilizer->refresh();
        $this->assertEquals('Updated', $mobilizer->first_name);
        $this->assertEquals('Inactive', $mobilizer->status);
    }

    public function test_admin_can_delete_mobilizer(): void
    {
        $admin = $this->createAdmin();
        $mobilizer = $this->createMobilizer();

        $this->actingAs($admin)->delete(route('mobilizers.destroy', $mobilizer))
            ->assertRedirect(route('mobilizers.index'));

        $this->assertDatabaseMissing('mobilizers', ['id' => $mobilizer->id]);
    }

    /* ---------------------------------------------------------------
       Member self-service
    --------------------------------------------------------------- */

    public function test_member_dashboard_requires_member_record(): void
    {
        $memberUser = $this->createMemberUser();

        $this->actingAs($memberUser)->get(route('member.dashboard'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_member_can_view_dashboard_with_profile(): void
    {
        $memberUser = $this->createMemberUser();
        $m = $this->createMember(['user_id' => $memberUser->id]);

        $this->actingAs($memberUser)->get(route('member.dashboard'))
            ->assertOk();
    }

    public function test_member_can_update_profile(): void
    {
        $memberUser = $this->createMemberUser();
        $m = $this->createMember(['user_id' => $memberUser->id]);
        $newEmail = 'updated_' . uniqid() . '@test.com';

        $data = [
            'email' => $newEmail,
            'telephone1' => '+256780000600',
            'nationality' => 'Uganda',
            'marital_status' => 'Married',
            'employment_status' => 'Self-employed',
            'source' => 'Referral',
            'address' => 'Updated Address',
        ];

        $this->actingAs($memberUser)->post(route('member.profile.update'), $data)
            ->assertRedirect(route('member.profile'));

        $m->refresh();
        $this->assertEquals($newEmail, $m->email);
        $this->assertEquals('Married', $m->marital_status);
        $this->assertEquals('Updated Address', $m->address);
    }

    public function test_member_can_save_next_of_kin(): void
    {
        $memberUser = $this->createMemberUser();
        $m = $this->createMember(['user_id' => $memberUser->id]);

        $data = [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'relationship' => 'Spouse',
            'phone' => '+256780000700',
        ];

        $this->actingAs($memberUser)->post(route('member.next_of_kin.save'), $data)
            ->assertRedirect(route('member.next_of_kin'));

        $this->assertDatabaseHas('member_next_of_kin', [
            'member_id' => $m->id,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'relationship' => 'Spouse',
        ]);
    }

    public function test_member_can_save_bank_details(): void
    {
        $memberUser = $this->createMemberUser();
        $m = $this->createMember(['user_id' => $memberUser->id]);

        $data = [
            'bank_account' => '1234567890',
            'account_name' => 'John Doe',
            'bank_name' => 'Centenary Bank',
            'bank_branch' => 'Main Branch',
        ];

        $this->actingAs($memberUser)->post(route('member.bank_details.save'), $data)
            ->assertRedirect(route('member.bank_details'));

        $this->assertDatabaseHas('member_bank_details', [
            'member_id' => $m->id,
            'bank_account' => '1234567890',
            'account_name' => 'John Doe',
        ]);
    }

    public function test_member_can_view_membership_page(): void
    {
        $memberUser = $this->createMemberUser();
        $m = $this->createMember(['user_id' => $memberUser->id]);

        $this->actingAs($memberUser)->get(route('membership'))
            ->assertOk();
    }

    /* ---------------------------------------------------------------
       Phone normalization
    --------------------------------------------------------------- */

    public function test_phone_is_normalized_on_store(): void
    {
        $admin = $this->createAdmin();
        $branch = \DB::table('branches')->first();
        $mobilizer = $this->createMobilizer();

        $data = [
            'first_name' => 'Phone',
            'last_name' => 'Test',
            'date_of_birth' => '1995-01-01',
            'sex' => 'Female',
            'nationality' => 'Kenya',
            'address' => 'Nairobi',
            'employment_status' => 'Student',
            'marital_status' => 'Single',
            'source' => 'Social Media',
            'telephone1' => '256780000800', // missing + prefix, should be normalized to +256780000800
            'branch_id' => $branch->id,
            'mobilizer_id' => $mobilizer->id,
        ];

        $this->actingAs($admin)->post(route('members.store'), $data)
            ->assertRedirect();

        $member = Member::where('telephone1', '+256780000800')->first();
        $this->assertNotNull($member);
    }

    /* ---------------------------------------------------------------
       Membership ID generation
    --------------------------------------------------------------- */

    public function test_membership_id_is_auto_generated(): void
    {
        $admin = $this->createAdmin();
        $branch = \DB::table('branches')->first();
        $mobilizer = $this->createMobilizer();

        $data = [
            'first_name' => 'Auto',
            'last_name' => 'Gen',
            'date_of_birth' => '1995-01-01',
            'sex' => 'Male',
            'nationality' => 'Uganda',
            'address' => 'Kampala',
            'region' => 'Central',
            'district_residence' => 'Kampala',
            'district' => 'Kampala',
            'employment_status' => 'Student',
            'marital_status' => 'Single',
            'source' => 'Personal',
            'telephone1' => '+256780000900',
            'branch_id' => $branch->id,
            'mobilizer_id' => $mobilizer->id,
        ];

        $this->actingAs($admin)->post(route('members.store'), $data)
            ->assertRedirect();

        $member = Member::where('telephone1', '+256780000900')->first();
        $this->assertNotNull($member);
        $this->assertMatchesRegularExpression('/^KAM-2026-\d{6}$/', $member->membership_id);
    }

    /* ---------------------------------------------------------------
       AJAX endpoints
    --------------------------------------------------------------- */

    public function test_guest_is_redirected_to_login_for_member_details_ajax(): void
    {
        $this->get(route('members.ajax.details', ['id' => 1]))
            ->assertRedirect(route('login'));
    }

    public function test_admin_can_get_member_details_ajax(): void
    {
        $admin = $this->createAdmin();
        $member = $this->createMember();

        $this->actingAs($admin)->get(route('members.ajax.details', ['id' => $member->id]))
            ->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonPath('member.membership_id', $member->membership_id)
            ->assertJsonPath('member.first_name', $member->first_name);
    }

    public function test_guest_is_redirected_to_login_for_mobilizer_get_ajax(): void
    {
        $this->get(route('mobilizers.ajax.get', ['mobilizer_id' => 1]))
            ->assertRedirect(route('login'));
    }

    public function test_admin_can_get_mobilizer_ajax(): void
    {
        $admin = $this->createAdmin();
        $mobilizer = $this->createMobilizer();

        $this->actingAs($admin)->get(route('mobilizers.ajax.get', ['mobilizer_id' => $mobilizer->id]))
            ->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonPath('mobilizer.id', $mobilizer->id)
            ->assertJsonPath('mobilizer.first_name', $mobilizer->first_name);
    }

    /* ---------------------------------------------------------------
       Membership ID sequencing
    --------------------------------------------------------------- */

    public function test_membership_ids_are_sequential(): void
    {
        $admin = $this->createAdmin();
        $branchId = \DB::table('branches')->insertGetId([
            'name' => 'Zulu',
            'location' => 'Zulu',
            'status' => 1,
            'created_at' => now(),
        ]);
        $mobilizer = $this->createMobilizer();

        $base = [
            'first_name' => 'Seq',
            'last_name' => 'Member',
            'date_of_birth' => '1990-01-01',
            'sex' => 'Male',
            'nationality' => 'Uganda',
            'address' => 'Kampala',
            'region' => 'Central',
            'district_residence' => 'Kampala',
            'district' => 'Kampala',
            'employment_status' => 'Self-employed',
            'marital_status' => 'Single',
            'source' => 'Radio',
            'source_station' => 'Prime FM',
            'branch_id' => $branchId,
            'mobilizer_id' => $mobilizer->id,
        ];

        $this->actingAs($admin)->post(route('members.store'), array_merge($base, [
            'email' => 'seq1_' . uniqid() . '@test.com',
            'telephone1' => '+256780001001',
        ]))->assertRedirect();

        $this->actingAs($admin)->post(route('members.store'), array_merge($base, [
            'email' => 'seq2_' . uniqid() . '@test.com',
            'telephone1' => '+256780001002',
        ]))->assertRedirect();

        $members = Member::query()->where('branch_id', $branchId)->orderBy('id')->get();
        $this->assertCount(2, $members);
        $this->assertSame('ZUL-2026-000001', $members[0]->membership_id);
        $this->assertSame('ZUL-2026-000002', $members[1]->membership_id);
    }

    /* ---------------------------------------------------------------
       Member self-service validation
    --------------------------------------------------------------- */

    public function test_update_profile_rejects_invalid_enum(): void
    {
        $memberUser = $this->createMemberUser();
        $this->createMember(['user_id' => $memberUser->id]);

        $data = [
            'email' => 'invalid_enum_' . uniqid() . '@test.com',
            'telephone1' => '+256780000999',
            'nationality' => 'Uganda',
            'address' => 'Kampala',
            'region' => 'Central',
            'district_residence' => 'Kampala',
            'district' => 'Kampala',
            'employment_status' => 'Self-employed',
            'marital_status' => 'Married',
            'source' => 'Referral',
            'account_type' => 'Invalid',
        ];

        $this->actingAs($memberUser)->post(route('member.profile.update'), $data)
            ->assertSessionHasErrors('account_type');
    }

    public function test_save_next_of_kin_rejects_invalid_phone(): void
    {
        $memberUser = $this->createMemberUser();
        $this->createMember(['user_id' => $memberUser->id]);

        $data = [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'relationship' => 'Spouse',
            'phone' => '0781234567', // missing +256 prefix
        ];

        $this->actingAs($memberUser)->post(route('member.next_of_kin.save'), $data)
            ->assertSessionHasErrors('phone');
    }

    public function test_save_bank_details_rejects_empty_all_fields(): void
    {
        $memberUser = $this->createMemberUser();
        $this->createMember(['user_id' => $memberUser->id]);

        $this->actingAs($memberUser)->post(route('member.bank_details.save'), [
            'bank_account' => '',
            'account_name' => '',
            'bank_name' => '',
            'bank_branch' => '',
        ])->assertSessionHasErrors('bank_account');
    }

    /* ---------------------------------------------------------------
       Payment status filter
    --------------------------------------------------------------- */

    public function test_index_filters_by_payment_status(): void
    {
        $admin = $this->createAdmin();
        $this->createMember();

        // No payment_transactions table in the test DB, so every member
        // resolves to UNPAID via ledgerPayStatus().
        $this->actingAs($admin)->get(route('members.index', ['payment_status' => 'UNPAID']))
            ->assertOk();
    }
}
