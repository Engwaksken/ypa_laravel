<?php

namespace Tests\Feature;

use App\Models\Meeting;
use App\Models\MeetingAttendance;
use App\Models\MeetingInvite;
use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class MeetingModuleTest extends TestCase
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

        // Reproduce the members table (subset of the production shape used by
        // the invite/attendance pickers and MeetingService::saveInvites).
        if (!Schema::hasTable('members')) {
            Schema::create('members', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('membership_id')->unique();
                $table->string('first_name')->nullable();
                $table->string('last_name')->nullable();
                $table->string('other_name')->nullable();
                $table->string('email')->nullable();
                $table->string('telephone1')->nullable();
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->timestamps();
            });
        }

        // Reproduce the Meetings module tables against the real schema
        // (kemmytec_ypa.sql): meetings has no meeting_code/title/description/
        // start_time/end_time/updated_by columns; status enum is
        // ('Scheduled','Ongoing','Completed','Cancelled').
        if (!Schema::hasTable('meetings')) {
            Schema::create('meetings', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('meeting_type', 100);
                $table->string('meeting_title', 255);
                $table->date('meeting_date');
                $table->time('meeting_time')->nullable();
                $table->string('location', 255)->nullable();
                $table->text('agenda')->nullable();
                $table->longText('minutes')->nullable();
                $table->integer('attendance_count')->default(0);
                $table->string('status', 20)->default('Scheduled');
                $table->string('chaired_by', 150)->nullable();
                $table->text('notes')->nullable();
                $table->unsignedBigInteger('created_by');
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (!Schema::hasTable('meeting_attendance')) {
            Schema::create('meeting_attendance', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('meeting_id')->index();
                $table->unsignedBigInteger('member_id')->index();
                $table->string('status', 20)->default('Present');
                $table->timestamp('check_in_time')->useCurrent();
                $table->text('notes')->nullable();
                $table->timestamp('attended_at')->nullable();
                $table->timestamp('created_at')->useCurrent();
            });
        }

        if (!Schema::hasTable('meeting_invites')) {
            Schema::create('meeting_invites', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('meeting_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('invite_status', 20)->default('Pending');
                $table->timestamp('invited_at')->nullable();
                $table->timestamp('reminder_sent_at')->nullable();
                $table->timestamps();
            });
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

    protected function createMeeting(array $overrides = []): Meeting
    {
        return Meeting::create(array_merge([
            'meeting_type' => 'Monthly',
            'meeting_title' => 'Test Meeting ' . uniqid(),
            'meeting_date' => '2026-01-15',
            'meeting_time' => '10:00',
            'location' => 'Kampala',
            'status' => 'Scheduled',
            'created_by' => 1,
        ], $overrides));
    }

    protected function createMember(): Member
    {
        $user = User::create([
            'name' => 'Meet Ing',
            'email' => 'meet_' . uniqid() . '@test.local',
            'password' => Hash::make('password'),
            'role' => 'member',
            'status' => 'active',
        ]);

        return Member::create([
            'membership_id' => 'KAM-2026-' . str_pad((string) Member::count() + 1, 6, '0', STR_PAD_LEFT),
            'first_name' => 'Meet',
            'last_name' => 'Ing',
            'telephone1' => '+256780000001',
            'branch_id' => 1,
            'user_id' => $user->id,
        ]);
    }

    /* ---------------------------------------------------------------
       Auth guard tests
    --------------------------------------------------------------- */

    public function test_guest_is_redirected_to_login_for_meetings_index(): void
    {
        $this->get(route('meetings.index'))->assertRedirect(route('login'));
    }

    public function test_admin_can_view_meetings_index(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)->get(route('meetings.index'))
            ->assertOk();
    }

    public function test_member_role_gets_403_on_meetings_index(): void
    {
        $member = $this->createMemberUser();

        $this->actingAs($member)->get(route('meetings.index'))
            ->assertStatus(403);
    }

    /* ---------------------------------------------------------------
       Meetings CRUD
    --------------------------------------------------------------- */

    public function test_admin_can_view_create_meeting_form(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)->get(route('meetings.create'))
            ->assertOk();
    }

    public function test_admin_can_store_meeting(): void
    {
        $admin = $this->createAdmin();

        $data = [
            'meeting_type' => 'Quarterly',
            'meeting_title' => 'Quarterly Review',
            'meeting_date' => '2026-02-10',
            'meeting_time' => '09:00',
            'location' => 'Head Office',
            'agenda' => 'Review of Q1 performance',
            'chaired_by' => 'Chairperson One',
            'status' => 'Scheduled',
        ];

        $this->actingAs($admin)->post(route('meetings.store'), $data)
            ->assertRedirect();

        $meeting = Meeting::where('meeting_title', 'Quarterly Review')->first();
        $this->assertNotNull($meeting);
        $this->assertEquals('Quarterly', $meeting->meeting_type);
        $this->assertEquals('2026-02-10', $meeting->meeting_date->format('Y-m-d'));
        $this->assertEquals('09:00', $meeting->meeting_time);
        $this->assertEquals('Head Office', $meeting->location);
        $this->assertEquals('Review of Q1 performance', $meeting->agenda);
        $this->assertEquals('Chairperson One', $meeting->chaired_by);
        $this->assertEquals('Scheduled', $meeting->status);
        $this->assertEquals($admin->id, $meeting->created_by);
    }

    public function test_store_meeting_validates_required_fields(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)->post(route('meetings.store'), [])
            ->assertSessionHasErrors(['meeting_type', 'meeting_title', 'meeting_date', 'status']);
    }

    public function test_admin_can_view_meeting(): void
    {
        $admin = $this->createAdmin();
        $meeting = $this->createMeeting();

        $this->actingAs($admin)->get(route('meetings.show', $meeting))
            ->assertOk();
    }

    public function test_admin_can_update_meeting(): void
    {
        $admin = $this->createAdmin();
        $meeting = $this->createMeeting();

        $data = [
            'meeting_type' => 'Annual',
            'meeting_title' => 'Updated Meeting',
            'meeting_date' => '2026-03-05',
            'meeting_time' => '14:00',
            'location' => 'Mbarara',
            'status' => 'Ongoing',
        ];

        $this->actingAs($admin)->put(route('meetings.update', $meeting), $data)
            ->assertRedirect();

        $meeting->refresh();
        $this->assertEquals('Annual', $meeting->meeting_type);
        $this->assertEquals('Updated Meeting', $meeting->meeting_title);
        $this->assertEquals('Mbarara', $meeting->location);
        $this->assertEquals('Ongoing', $meeting->status);
        // Update must not overwrite the original creator (no updated_by column).
        $this->assertEquals(1, $meeting->created_by);
    }

    public function test_admin_can_delete_meeting(): void
    {
        $admin = $this->createAdmin();
        $meeting = $this->createMeeting();

        $this->actingAs($admin)->delete(route('meetings.destroy', $meeting))
            ->assertRedirect();

        $this->assertDatabaseMissing('meetings', ['id' => $meeting->id]);
    }

    /* ---------------------------------------------------------------
       AJAX endpoints (admin/manager only)
    --------------------------------------------------------------- */

    public function test_guest_is_redirected_to_login_for_get_invites_ajax(): void
    {
        $this->get(route('meetings.ajax.get_invites', ['id' => 1]))
            ->assertRedirect(route('login'));
    }

    public function test_member_role_gets_403_on_get_invites_ajax(): void
    {
        $member = $this->createMemberUser();

        $this->actingAs($member)->get(route('meetings.ajax.get_invites', ['id' => 1]))
            ->assertStatus(403);
    }

    public function test_admin_can_get_invites_ajax(): void
    {
        $admin = $this->createAdmin();
        $meeting = $this->createMeeting();
        $member = $this->createMember();

        MeetingInvite::create([
            'meeting_id' => $meeting->id,
            'user_id' => $member->user_id,
            'invite_status' => 'Pending',
        ]);

        $this->actingAs($admin)->get(route('meetings.ajax.get_invites', ['id' => $meeting->id]))
            ->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonPath('meeting_id', $meeting->id);
    }

    public function test_admin_can_save_invites_ajax(): void
    {
        $admin = $this->createAdmin();
        $meeting = $this->createMeeting();
        $member = $this->createMember();

        $this->actingAs($admin)->post(route('meetings.ajax.save_invites'), [
            'meeting_id' => $meeting->id,
            'member_ids' => [$member->id],
        ])->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('meeting_invites', [
            'meeting_id' => $meeting->id,
            'user_id' => $member->user_id,
            'invite_status' => 'Pending',
        ]);
    }

    public function test_admin_can_get_attendance_ajax(): void
    {
        $admin = $this->createAdmin();
        $meeting = $this->createMeeting();
        $member = $this->createMember();

        MeetingAttendance::create([
            'meeting_id' => $meeting->id,
            'member_id' => $member->id,
            'status' => 'Present',
        ]);

        $this->actingAs($admin)->get(route('meetings.ajax.get_attendance', ['id' => $meeting->id]))
            ->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonPath('meeting_id', $meeting->id);
    }

    public function test_admin_can_save_attendance_ajax(): void
    {
        $admin = $this->createAdmin();
        $meeting = $this->createMeeting();
        $member = $this->createMember();

        $this->actingAs($admin)->post(route('meetings.ajax.save_attendance'), [
            'meeting_id' => $meeting->id,
            'attendees' => [$member->id],
        ])->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('meeting_attendance', [
            'meeting_id' => $meeting->id,
            'member_id' => $member->id,
            'status' => 'Present',
        ]);
    }

    public function test_admin_can_mark_meeting_complete_ajax(): void
    {
        $admin = $this->createAdmin();
        $meeting = $this->createMeeting();

        $this->actingAs($admin)->post(route('meetings.ajax.mark_complete'), [
            'meeting_id' => $meeting->id,
        ])->assertOk()
            ->assertJson(['success' => true]);

        $meeting->refresh();
        $this->assertEquals('Completed', $meeting->status);
    }

    public function test_member_role_gets_403_on_search_members_ajax(): void
    {
        $member = $this->createMemberUser();

        $this->actingAs($member)->get(route('meetings.ajax.search_members', ['term' => 'test']))
            ->assertStatus(403);
    }

    public function test_meeting_member_search_ajax_route_has_throttle_middleware(): void
    {
        $route = Route::getRoutes()->getByName('meetings.ajax.search_members');

        $this->assertNotNull($route);
        $middleware = implode(',', $route->gatherMiddleware());

        $this->assertMatchesRegularExpression('/permission.*meetings_manage/i', $middleware);
        $this->assertMatchesRegularExpression('/throttle|ThrottleRequests/i', $middleware);
    }

    /* ---------------------------------------------------------------
       Real-schema creation behaviour
    --------------------------------------------------------------- */

    public function test_meetings_are_created_with_real_schema_columns(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)->post(route('meetings.store'), [
            'meeting_type' => 'Monthly',
            'meeting_title' => 'Seq Meeting One',
            'meeting_date' => '2026-04-01',
            'status' => 'Scheduled',
        ])->assertRedirect();

        $this->actingAs($admin)->post(route('meetings.store'), [
            'meeting_type' => 'Quarterly',
            'meeting_title' => 'Seq Meeting Two',
            'meeting_date' => '2026-04-02',
            'status' => 'Scheduled',
        ])->assertRedirect();

        $meetings = Meeting::query()->orderBy('id')->get();
        $this->assertCount(2, $meetings);
        $this->assertSame('Seq Meeting One', $meetings[0]->meeting_title);
        $this->assertSame('Monthly', $meetings[0]->meeting_type);
        $this->assertSame('Scheduled', $meetings[0]->status);
        $this->assertEquals($admin->id, $meetings[0]->created_by);
        $this->assertSame('Seq Meeting Two', $meetings[1]->meeting_title);
        $this->assertSame('Quarterly', $meetings[1]->meeting_type);
        $this->assertSame('Scheduled', $meetings[1]->status);
    }
}