<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectCategory;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProjectModuleTest extends TestCase
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

        // Reproduce the branches table (needed by the projects branch relation).
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

        // Project categories table (matches the projects migration).
        if (!Schema::hasTable('project_categories')) {
            Schema::create('project_categories', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->string('category_name');
                $table->text('description')->nullable();
                $table->tinyInteger('status')->default(1)->index();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->nullable();
            });
        }

        // Projects table (matches the projects migration).
        if (!Schema::hasTable('projects')) {
            Schema::create('projects', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('project_category_id')->nullable()->index();
                $table->unsignedBigInteger('project_type_id')->nullable()->index();
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->string('project_name');
                $table->string('project_code', 100)->unique();
                $table->text('description')->nullable();
                $table->date('start_date')->nullable();
                $table->date('end_date')->nullable();
                $table->decimal('registration_fee', 15, 2)->default(0)->index();
                $table->decimal('administrative_fee', 15, 2)->default(0);
                $table->string('status', 30)->default('Planning')->index();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->timestamp('created_at')->useCurrent();
                $table->timestamp('updated_at')->nullable();
            });
        }

        // Minimal contracts table (needed by the member_count relation and the
        // delete guard on ProjectController::destroy()).
        if (!Schema::hasTable('contracts')) {
            Schema::create('contracts', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('project_id')->nullable()->index();
                $table->string('status', 30)->nullable()->index();
                $table->timestamps();
            });
        }

        // Seed a branch for project references.
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

    protected function createCategory(array $overrides = []): ProjectCategory
    {
        return ProjectCategory::create(array_merge([
            'category_name' => 'Microfinance ' . uniqid(),
            'description' => 'Lending programme',
            'status' => 1,
        ], $overrides));
    }

    protected function projectData(ProjectCategory $category, array $overrides = []): array
    {
        $branch = \DB::table('branches')->first();

        return array_merge([
            'project_category_id' => $category->id,
            'project_name' => 'Youth Savings ' . uniqid(),
            'project_code' => 'PRJ-' . strtoupper(uniqid()),
            'description' => 'Savings promotion project',
            'start_date' => '2026-09-01',
            'end_date' => '2027-09-01',
            'registration_fee' => 5000,
            'administrative_fee' => 2000,
            'status' => 'Planning',
            'branch_id' => $branch->id,
        ], $overrides);
    }

    /* ---------------------------------------------------------------
       Auth guards
    --------------------------------------------------------------- */

    public function test_guest_is_redirected_to_login_for_projects_index(): void
    {
        $this->get(route('projects.index'))->assertRedirect(route('login'));
    }

    public function test_admin_can_view_projects_index(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)->get(route('projects.index'))
            ->assertOk();
    }

    public function test_member_role_gets_403_on_projects_index(): void
    {
        $member = $this->createMemberUser();

        $this->actingAs($member)->get(route('projects.index'))
            ->assertStatus(403);
    }

    public function test_guest_is_redirected_to_login_for_project_categories_index(): void
    {
        $this->get(route('project-categories.index'))->assertRedirect(route('login'));
    }

    public function test_admin_can_view_project_categories_index(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)->get(route('project-categories.index'))
            ->assertOk();
    }

    /* ---------------------------------------------------------------
       Projects CRUD
    --------------------------------------------------------------- */

    public function test_admin_can_store_project(): void
    {
        $admin = $this->createAdmin();
        $category = $this->createCategory();
        $data = $this->projectData($category);

        $this->actingAs($admin)->post(route('projects.store'), $data)
            ->assertRedirect(route('projects.index'));

        $this->assertDatabaseHas('projects', [
            'project_name' => $data['project_name'],
            'project_code' => $data['project_code'],
            'status' => 'Planning',
        ]);

        $project = Project::where('project_code', $data['project_code'])->first();
        $this->assertEquals($category->id, $project->project_category_id);
        $this->assertEquals($admin->id, $project->created_by);
        $this->assertEquals('2000.00', (string) $project->administrative_fee);
    }

    public function test_store_project_validates_required_fields(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)->post(route('projects.store'), [])
            ->assertSessionHasErrors(['project_category_id', 'project_name', 'project_code', 'description', 'start_date', 'registration_fee', 'status']);
    }

    public function test_store_project_rejects_duplicate_code(): void
    {
        $admin = $this->createAdmin();
        $category = $this->createCategory();
        $data = $this->projectData($category);

        $this->actingAs($admin)->post(route('projects.store'), $data)->assertRedirect();

        $this->actingAs($admin)->post(route('projects.store'), $data)
            ->assertSessionHasErrors('project_code');
    }

    public function test_store_project_rejects_invalid_status(): void
    {
        $admin = $this->createAdmin();
        $category = $this->createCategory();

        $this->actingAs($admin)->post(route('projects.store'), $this->projectData($category, [
            'status' => 'Bogus',
            'project_code' => 'PRJ-BOGUS-' . uniqid(),
        ]))->assertSessionHasErrors('status');
    }

    public function test_admin_can_update_project(): void
    {
        $admin = $this->createAdmin();
        $category = $this->createCategory();
        $project = Project::create($this->projectData($category));

        $data = $this->projectData($category, [
            'project_name' => 'Renamed Project',
            'status' => 'Active',
            'project_code' => $project->project_code,
        ]);

        $this->actingAs($admin)->put(route('projects.update', $project), $data)
            ->assertRedirect(route('projects.index'));

        $project->refresh();
        $this->assertEquals('Renamed Project', $project->project_name);
        $this->assertEquals('Active', $project->status);
    }

    public function test_admin_can_delete_project_without_contracts(): void
    {
        $admin = $this->createAdmin();
        $category = $this->createCategory();
        $project = Project::create($this->projectData($category));

        $this->actingAs($admin)->delete(route('projects.destroy', $project))
            ->assertRedirect(route('projects.index'));

        $this->assertDatabaseMissing('projects', ['id' => $project->id]);
    }

    public function test_cannot_delete_project_with_contracts(): void
    {
        $admin = $this->createAdmin();
        $category = $this->createCategory();
        $project = Project::create($this->projectData($category));

        \DB::table('contracts')->insert([
            'project_id' => $project->id,
            'status' => 'Active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)->delete(route('projects.destroy', $project))
            ->assertRedirect();

        $this->assertDatabaseHas('projects', ['id' => $project->id]);
    }

    /* ---------------------------------------------------------------
       Project categories CRUD
    --------------------------------------------------------------- */

    public function test_admin_can_store_project_category(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)->post(route('project-categories.store'), [
            'category_name' => 'Women Empowerment',
            'description' => 'Programme for women',
            'status' => 1,
        ])->assertRedirect(route('project-categories.index'));

        $this->assertDatabaseHas('project_categories', ['category_name' => 'Women Empowerment']);
    }

    public function test_store_project_category_validates_name(): void
    {
        $admin = $this->createAdmin();

        $this->actingAs($admin)->post(route('project-categories.store'), [])
            ->assertSessionHasErrors('category_name');
    }

    public function test_admin_can_update_project_category(): void
    {
        $admin = $this->createAdmin();
        $category = $this->createCategory();

        $this->actingAs($admin)->put(route('project-categories.update', $category), [
            'category_name' => 'Renamed Category',
            'description' => 'Updated',
            'status' => 0,
        ])->assertRedirect(route('project-categories.index'));

        $category->refresh();
        $this->assertEquals('Renamed Category', $category->category_name);
        $this->assertEquals(0, $category->status);
    }

    public function test_admin_can_delete_unused_project_category(): void
    {
        $admin = $this->createAdmin();
        $category = $this->createCategory();

        $this->actingAs($admin)->delete(route('project-categories.destroy', $category))
            ->assertRedirect(route('project-categories.index'));

        $this->assertDatabaseMissing('project_categories', ['id' => $category->id]);
    }

    public function test_cannot_delete_project_category_with_projects(): void
    {
        $admin = $this->createAdmin();
        $category = $this->createCategory();
        Project::create($this->projectData($category));

        $this->actingAs($admin)->delete(route('project-categories.destroy', $category))
            ->assertRedirect();

        $this->assertDatabaseHas('project_categories', ['id' => $category->id]);
    }

    /* ---------------------------------------------------------------
       Exports
    --------------------------------------------------------------- */

    public function test_admin_can_export_projects_csv(): void
    {
        $admin = $this->createAdmin();
        $category = $this->createCategory();
        Project::create($this->projectData($category));

        $response = $this->actingAs($admin)->get(route('projects.export'));
        $response->assertOk();
        $this->assertStringContainsString('Project Code', $response->streamedContent());
    }

    public function test_admin_can_export_project_categories_csv(): void
    {
        $admin = $this->createAdmin();
        $this->createCategory();

        $response = $this->actingAs($admin)->get(route('project-categories.export'));
        $response->assertOk();
        $this->assertStringContainsString('Category Name', $response->streamedContent());
    }

    /* ---------------------------------------------------------------
       AJAX endpoints
    --------------------------------------------------------------- */

    public function test_admin_can_get_project_details_ajax(): void
    {
        $admin = $this->createAdmin();
        $category = $this->createCategory();
        $project = Project::create($this->projectData($category));

        $this->actingAs($admin)->get(route('projects.ajax.details', ['id' => $project->id]))
            ->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonPath('project.project_name', $project->project_name)
            ->assertJsonPath('project.category_name', $category->category_name);
    }

    public function test_admin_can_get_project_category_details_ajax(): void
    {
        $admin = $this->createAdmin();
        $category = $this->createCategory();

        $this->actingAs($admin)->get(route('project-categories.edit-data', $category))
            ->assertOk()
            ->assertJson(['success' => true])
            ->assertJsonPath('category.category_name', $category->category_name);
    }

    /* ---------------------------------------------------------------
       Search and filters
    --------------------------------------------------------------- */

    public function test_index_searches_projects(): void
    {
        $admin = $this->createAdmin();
        $category = $this->createCategory();
        Project::create($this->projectData($category, [
            'project_name' => 'Solar Power Project',
            'project_code' => 'PRJ-SOLAR-' . uniqid(),
        ]));

        $this->actingAs($admin)->get(route('projects.index', ['search' => 'Solar']))
            ->assertOk()
            ->assertSee('Solar Power Project');
    }

    public function test_index_filters_projects_by_status(): void
    {
        $admin = $this->createAdmin();
        $category = $this->createCategory();
        Project::create($this->projectData($category, [
            'project_name' => 'Completed Project',
            'status' => 'Completed',
        ]));

        $this->actingAs($admin)->get(route('projects.index', ['status' => 'Completed']))
            ->assertOk()
            ->assertSee('Completed Project');
    }

    public function test_index_filters_categories_by_status(): void
    {
        $admin = $this->createAdmin();
        $this->createCategory(['category_name' => 'Active Category', 'status' => 1]);
        $this->createCategory(['category_name' => 'Hidden Category', 'status' => 0]);

        $this->actingAs($admin)->get(route('project-categories.index', ['status' => 'active']))
            ->assertOk()
            ->assertSee('Active Category')
            ->assertDontSee('Hidden Category');
    }
}