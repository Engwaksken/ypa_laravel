<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ActivityParticipant;
use App\Models\ActivityType;
use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ActivityModuleTest extends TestCase
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

        if (!Schema::hasTable('members')) {
            Schema::create('members', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('membership_id')->unique();
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('other_name')->nullable();
                $table->string('telephone1')->nullable();
                $table->string('email')->nullable();
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->timestamps();
            });
        }

        // Reproduce the Activities module tables.
        if (!Schema::hasTable('activity_types')) {
            Schema::create('activity_types', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('type_code')->unique();
                $table->string('type_name');
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('activities')) {
            Schema::create('activities', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('activity_code')->nullable()->index();
                $table->string('activity_name');
                $table->unsignedBigInteger('activity_type_id')->nullable()->index();
                $table->text('description')->nullable();
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->string('location')->nullable();
                $table->decimal('budget', 15, 2)->nullable();
                $table->string('is_promotion')->nullable()->default('No');
                $table->string('status')->nullable()->default('Planned');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('activity_participants')) {
            Schema::create('activity_participants', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('activity_id')->index();
                $table->unsignedBigInteger('member_id')->nullable()->index();
                $table->string('participant_type')->nullable()->default('Member');
                $table->boolean('attended')->default(false);
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('gender')->nullable();
                $table->string('phone')->nullable();
                $table->string('email')->nullable();
                $table->string('address')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('activity_participant_items')) {
            Schema::create('activity_participant_items', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('participant_id')->nullable()->index();
                $table->unsignedBigInteger('activity_id')->nullable()->index();
                $table->string('item_name')->nullable();
                $table->integer('quantity')->nullable();
                $table->timestamps();
            });
        }

        // Seed an activity type.
        if (ActivityType::query()->count() === 0) {
            ActivityType::create([
                'type_code' => 'OUTREACH',
                'type_name' => 'Outreach',
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

    protected function createActivity(array $overrides = []): Activity
    {
        $type = ActivityType::query()->first();

        return Activity::create(array_merge([
            'activity_code' => 'ACT-2026-' . str_pad((string) Activity::count() + 1, 6, '0', STR_PAD_LEFT),
            'activity_name' => 'Test Activity ' . uniqid(),
            'activity_type_id' => $type->id,
            'start_date' => '2026-01-10',
            'end_date' => '2026-01-12',
            'location' => 'Kampala',
            'is_promotion' => 'No',
            'status' => 'Planned',
        ], $overrides));
    }

    /* ---------------------------------------------------------------
       Auth guard tests
    --------------------------------------------------------------- */

    public function test_guest_is_redirected_to_login_for_activities_index(): void
    {
        $this->get(route('activities.index'))->assertRedirect(route('login'));
    }

    public function test_admin_can_view_activities_index(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)->get(route('activities.index'))
            ->assertOk();
    }

    public function test_member_role_gets_403_on_activities_index(): void
    {
        $member = $this->createMemberUser();

        $this->actingAs($member)->get(route('activities.index'))
            ->assertStatus(403);
    }

    /* ---------------------------------------------------------------
       Activities CRUD
    --------------------------------------------------------------- */

    public function test_admin_can_view_create_activity_form(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)->get(route('activities.create'))
            ->assertOk();
    }

    public function test_admin_can_store_activity(): void
    {
        $admin = $this->createAdmin();
        $type = ActivityType::query()->first();

        $data = [
            'activity_name' => 'Masaka Outreach',
            'activity_type_id' => $type->id,
            'description' => 'Community outreach in Masaka',
            'start_date' => '2026-02-01',
            'end_date' => '2026-02-03',
            'location' => 'Masaka',
            'budget' => '1500000',
            'is_promotion' => 'Yes',
            'status' => 'Planned',
        ];

        $this->actingAs($admin)->post(route('activities.store'), $data)
            ->assertRedirect();

        $activity = Activity::where('activity_name', 'Masaka Outreach')->first();
        $this->assertNotNull($activity);
        $this->assertMatchesRegularExpression('/^ACT-2026-\d{6}$/', $activity->activity_code);
        $this->assertEquals($type->id, $activity->activity_type_id);
        $this->assertEquals('Yes', $activity->is_promotion);
        $this->assertEquals('Planned', $activity->status);
        $this->assertEquals(1500000, (float) $activity->budget);
    }

    public function test_store_activity_validates_required_fields(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)->post(route('activities.store'), [])
            ->assertSessionHasErrors(['activity_name', 'activity_type_id', 'start_date']);
    }

    public function test_store_activity_rejects_end_date_before_start_date(): void
    {
        $admin = $this->createAdmin();
        $type = ActivityType::query()->first();

        $data = [
            'activity_name' => 'Bad Dates',
            'activity_type_id' => $type->id,
            'start_date' => '2026-05-10',
            'end_date' => '2026-05-01',
            'status' => 'Planned',
        ];

        $this->actingAs($admin)->post(route('activities.store'), $data)
            ->assertSessionHasErrors('end_date');
    }

    public function test_admin_can_view_activity(): void
    {
        $admin = $this->createAdmin();
        $activity = $this->createActivity();

        $this->actingAs($admin)->get(route('activities.show', $activity))
            ->assertOk();
    }

    public function test_admin_can_update_activity(): void
    {
        $admin = $this->createAdmin();
        $activity = $this->createActivity();
        $type = ActivityType::query()->first();

        $data = [
            'activity_name' => 'Updated Activity',
            'activity_type_id' => $type->id,
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-05',
            'location' => 'Mbarara',
            'is_promotion' => 'No',
            'status' => 'Ongoing',
        ];

        $this->actingAs($admin)->put(route('activities.update', $activity), $data)
            ->assertRedirect();

        $activity->refresh();
        $this->assertEquals('Updated Activity', $activity->activity_name);
        $this->assertEquals('Mbarara', $activity->location);
        $this->assertEquals('Ongoing', $activity->status);
        $this->assertEquals($admin->id, $activity->updated_by);
    }

    public function test_admin_can_delete_activity(): void
    {
        $admin = $this->createAdmin();
        $activity = $this->createActivity();

        $this->actingAs($admin)->delete(route('activities.destroy', $activity))
            ->assertRedirect();

        $this->assertDatabaseMissing('activities', ['id' => $activity->id]);
    }

    /* ---------------------------------------------------------------
       Export
    --------------------------------------------------------------- */

    public function test_admin_can_export_activities_csv(): void
    {
        $admin = $this->createAdmin();
        $this->createActivity();

        $response = $this->actingAs($admin)->get(route('activities.export'));
        $response->assertOk();

        $content = $response->streamedContent();
        $this->assertStringContainsString('Activity Code', $content);
        $this->assertStringContainsString('ACT-2026-', $content);
    }

    /* ---------------------------------------------------------------
       Participants
    --------------------------------------------------------------- */

    public function test_admin_can_view_participants(): void
    {
        $admin = $this->createAdmin();
        $activity = $this->createActivity();

        $this->actingAs($admin)->get(route('activities.participants', $activity))
            ->assertOk();
    }

    public function test_admin_can_register_participant(): void
    {
        $admin = $this->createAdmin();
        $activity = $this->createActivity();

        // Minimal member record for the participant FK.
        $member = Member::create([
            'membership_id' => 'KAM-2026-900001',
            'first_name' => 'Part',
            'last_name' => 'Icipant',
            'telephone1' => '+256780000001',
            'branch_id' => 1,
        ]);

        $this->actingAs($admin)->post(route('activities.participants.store', $activity), [
            'member_id' => $member->id,
            'participant_type' => 'Member',
        ])->assertRedirect();

        $this->assertDatabaseHas('activity_participants', [
            'activity_id' => $activity->id,
            'member_id' => $member->id,
            'participant_type' => 'Member',
        ]);
    }

    public function test_admin_can_update_participant_attendance(): void
    {
        $admin = $this->createAdmin();
        $activity = $this->createActivity();

        $participant = ActivityParticipant::create([
            'activity_id' => $activity->id,
            'participant_type' => 'Member',
            'attended' => false,
        ]);

        $this->actingAs($admin)->post(route('activities.participants.attendance', [$activity, $participant]), [
            'attended' => 1,
        ])->assertRedirect();

        $participant->refresh();
        $this->assertTrue((bool) $participant->attended);
    }

    public function test_admin_can_remove_participant(): void
    {
        $admin = $this->createAdmin();
        $activity = $this->createActivity();

        $participant = ActivityParticipant::create([
            'activity_id' => $activity->id,
            'participant_type' => 'Member',
            'attended' => false,
        ]);

        $this->actingAs($admin)->delete(route('activities.participants.destroy', [$activity, $participant]))
            ->assertRedirect();

        $this->assertDatabaseMissing('activity_participants', ['id' => $participant->id]);
    }

    /* ---------------------------------------------------------------
       Public registration
    --------------------------------------------------------------- */

    public function test_public_can_view_activity_registration_page(): void
    {
        $activity = $this->createActivity();

        $this->get(route('activity-registration', ['activity_id' => $activity->id]))
            ->assertOk();
    }

    public function test_public_can_register_for_activity(): void
    {
        $activity = $this->createActivity();

        $this->post(route('activity-registration.register'), [
            'activity_id' => $activity->id,
            'first_name' => 'External',
            'last_name' => 'Person',
            'gender' => 'female',
            'phone' => '+256780000002',
            'email' => 'external_' . uniqid() . '@test.com',
        ])->assertRedirect();

        $this->assertDatabaseHas('activity_participants', [
            'activity_id' => $activity->id,
            'participant_type' => 'External',
            'first_name' => 'External',
            'last_name' => 'Person',
            'gender' => 'Female',
        ]);
    }

    public function test_public_registration_validates_required_fields(): void
    {
        $activity = $this->createActivity();

        $this->post(route('activity-registration.register'), [
            'activity_id' => $activity->id,
        ])->assertSessionHas('external_error');
    }

    public function test_public_registration_rejects_cancelled_activity(): void
    {
        $activity = $this->createActivity(['status' => 'Cancelled']);

        $this->get(route('activity-registration', ['activity_id' => $activity->id]))
            ->assertNotFound();

        $this->post(route('activity-registration.register'), [
            'activity_id' => $activity->id,
            'first_name' => 'External',
            'last_name' => 'Person',
            'gender' => 'Female',
            'phone' => '+256780000002',
        ])->assertRedirect();

        $this->assertDatabaseMissing('activity_participants', [
            'activity_id' => $activity->id,
            'participant_type' => 'External',
        ]);
    }

    /* ---------------------------------------------------------------
       AJAX endpoints
    --------------------------------------------------------------- */

    public function test_guest_is_redirected_to_login_for_activity_details_ajax(): void
    {
        $this->get(route('activities.ajax.details', ['id' => 1]))
            ->assertRedirect(route('login'));
    }

    public function test_admin_can_get_activity_details_ajax(): void
    {
        $admin = $this->createAdmin();
        $activity = $this->createActivity();

        $this->actingAs($admin)->get(route('activities.ajax.details', ['id' => $activity->id]))
            ->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonPath('activity.activity_code', $activity->activity_code)
            ->assertJsonPath('activity.activity_name', $activity->activity_name);
    }

    public function test_member_role_gets_403_on_activity_details_ajax(): void
    {
        $member = $this->createMemberUser();
        $activity = $this->createActivity();

        $this->actingAs($member)->get(route('activities.ajax.details', ['id' => $activity->id]))
            ->assertStatus(403);
    }

    public function test_activity_details_ajax_route_has_permission_middleware(): void
    {
        $route = Route::getRoutes()->getByName('activities.ajax.details');

        $this->assertNotNull($route);
        $this->assertMatchesRegularExpression('/permission.*activities/i', implode(',', $route->gatherMiddleware()));
    }

    public function test_guest_is_redirected_to_login_for_activity_member_search_ajax(): void
    {
        $this->get(route('activities.ajax.search_members', ['term' => 'test']))
            ->assertRedirect(route('login'));
    }

    public function test_member_role_gets_403_on_activity_member_search_ajax(): void
    {
        $member = $this->createMemberUser();

        $this->actingAs($member)->get(route('activities.ajax.search_members', ['term' => 'test']))
            ->assertStatus(403);
    }

    public function test_admin_can_search_members_for_activity_ajax(): void
    {
        $admin = $this->createAdmin();
        $member = Member::create([
            'membership_id' => 'KAM-2026-SEARCH01',
            'first_name' => 'Search',
            'last_name' => 'Target',
            'telephone1' => '+256780000099',
            'branch_id' => 1,
        ]);

        $this->actingAs($admin)->get(route('activities.ajax.search_members', ['term' => 'SEARCH01']))
            ->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonPath('members.0.id', $member->id);
    }

    public function test_activity_member_search_ajax_route_has_permission_middleware(): void
    {
        $route = Route::getRoutes()->getByName('activities.ajax.search_members');

        $this->assertNotNull($route);
        $middleware = implode(',', $route->gatherMiddleware());

        $this->assertMatchesRegularExpression('/permission.*activities_edit/i', $middleware);
        $this->assertMatchesRegularExpression('/throttle|ThrottleRequests/i', $middleware);
    }

    public function test_public_registration_route_is_rate_limited(): void
    {
        $route = Route::getRoutes()->getByName('activity-registration.register');

        $this->assertNotNull($route);
        $this->assertMatchesRegularExpression('/throttle|ThrottleRequests/i', implode(',', $route->gatherMiddleware()));
    }

    public function test_public_registration_member_search_route_is_removed(): void
    {
        $this->assertFalse(Route::has('activity-registration.ajax.search_members'));
    }

    public function test_public_registration_validates_email_phone_and_gender(): void
    {
        $activity = $this->createActivity();

        $this->post(route('activity-registration.register'), [
            'activity_id' => $activity->id,
            'first_name' => 'External',
            'last_name' => 'Person',
            'gender' => 'unknown',
            'phone' => '12',
            'email' => 'not-an-email',
        ])->assertSessionHasErrors(['gender', 'phone', 'email']);
    }

    public function test_mismatched_participant_activity_pair_returns_404_on_attendance_update(): void
    {
        $admin = $this->createAdmin();
        $activityOne = $this->createActivity(['activity_name' => 'Activity One']);
        $activityTwo = $this->createActivity(['activity_name' => 'Activity Two']);

        $participant = ActivityParticipant::create([
            'activity_id' => $activityOne->id,
            'participant_type' => 'Member',
            'attended' => false,
        ]);

        $this->actingAs($admin)->post(route('activities.participants.attendance', [$activityTwo, $participant]), [
            'attended' => 1,
        ])->assertStatus(404);
    }

    public function test_mismatched_participant_activity_pair_returns_404_on_destroy(): void
    {
        $admin = $this->createAdmin();
        $activityOne = $this->createActivity(['activity_name' => 'Activity One']);
        $activityTwo = $this->createActivity(['activity_name' => 'Activity Two']);

        $participant = ActivityParticipant::create([
            'activity_id' => $activityOne->id,
            'participant_type' => 'Member',
            'attended' => false,
        ]);

        $this->actingAs($admin)->delete(route('activities.participants.destroy', [$activityTwo, $participant]))
            ->assertStatus(404);
    }

    /* ---------------------------------------------------------------
       Activity code sequencing
    --------------------------------------------------------------- */

    public function test_activity_codes_are_sequential(): void
    {
        $admin = $this->createAdmin();
        $type = ActivityType::query()->first();

        $base = [
            'activity_type_id' => $type->id,
            'start_date' => '2026-04-01',
            'status' => 'Planned',
        ];

        $this->actingAs($admin)->post(route('activities.store'), array_merge($base, ['activity_name' => 'Seq Activity One']))->assertRedirect();
        $this->actingAs($admin)->post(route('activities.store'), array_merge($base, ['activity_name' => 'Seq Activity Two']))->assertRedirect();

        $activities = Activity::query()->orderBy('id')->get();
        $this->assertCount(2, $activities);
        $this->assertSame('ACT-2026-000001', $activities[0]->activity_code);
        $this->assertSame('ACT-2026-000002', $activities[1]->activity_code);
    }
}
