<?php

namespace Tests\Unit;

use App\Http\Controllers\ContractPdfController;
use App\Http\Controllers\ContractController;
use App\Models\Contract;
use App\Models\ContractTemplate;
use App\Models\Member;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Services\ContractService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ContractTemplateResolutionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('contract_templates', function (Blueprint $table): void {
            $table->id();
            $table->string('template_name');
            $table->string('template_key')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->unsignedBigInteger('project_type_id')->nullable();
            $table->unsignedBigInteger('project_category_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('version')->default(1);
            $table->text('template_body')->nullable();
            $table->text('cover_page')->nullable();
            $table->text('contract_footer')->nullable();
            $table->text('contract_signature')->nullable();
            $table->timestamps();
        });
    }

    private function contract(): Contract
    {
        $project = new Project(['project_type_id' => 2, 'project_category_id' => 3, 'project_name' => 'Bee Project']);
        $project->setRelation('category', new ProjectCategory(['category_name' => 'Honey']));
        $member = new Member(['first_name' => 'Jane', 'last_name' => 'Farmer', 'membership_id' => 'MEM-001']);
        $member->setRelation('nextOfKin', collect());
        $member->setRelation('bankDetail', null);
        $contract = new Contract(['project_id' => 10, 'contract_number' => 'CT-001', 'contract_for' => 'member', 'total_payable' => 1000, 'total_paid' => 100]);
        foreach (['project' => $project, 'projectData' => null, 'member' => $member, 'group' => null,
            'branch' => null, 'paymentMethod' => null, 'items' => collect()] as $relation => $value) {
            $contract->setRelation($relation, $value);
        }

        return $contract;
    }

    private function template(array $attributes = []): ContractTemplate
    {
        return ContractTemplate::create(array_merge([
            'template_name' => 'Bee agreement', 'template_body' => '<p>{{contract_number}} · {{farmer_name}}</p>',
            'is_active' => true, 'version' => 1,
        ], $attributes));
    }

    public function test_exact_category_and_type_take_precedence_over_defaults_and_wrong_projects(): void
    {
        $this->template(['version' => 10]);
        $this->template(['project_type_id' => 2, 'version' => 10]);
        $this->template(['project_category_id' => 3, 'version' => 10]);
        $exact = $this->template(['project_type_id' => 2, 'project_category_id' => 3]);
        $this->template(['project_type_id' => 4, 'project_category_id' => 3, 'version' => 20]);
        $this->assertSame($exact->id, (new ContractService)->resolveTemplate($this->contract())->id);
    }

    public function test_category_default_is_used_before_type_default_and_latest_active_version_wins(): void
    {
        $this->template(['project_type_id' => 2]);
        $this->template(['project_category_id' => 3]);
        $latest = $this->template(['project_category_id' => 3, 'version' => 2]);
        $this->template(['project_category_id' => 3, 'version' => 3, 'is_active' => false]);
        $this->assertSame($latest->id, (new ContractService)->resolveTemplate($this->contract())->id);
    }

    public function test_specific_project_takes_precedence_and_other_project_templates_are_excluded(): void
    {
        $this->template(['project_category_id' => 3, 'project_type_id' => 2, 'version' => 10]);
        $specific = $this->template(['project_id' => 10, 'project_category_id' => 3]);
        $this->template(['project_id' => 11, 'project_category_id' => 3, 'version' => 20]);
        $this->assertSame($specific->id, (new ContractService)->resolveTemplate($this->contract())->id);
    }

    public function test_saved_matching_template_is_preserved_but_unrelated_saved_template_is_not_used(): void
    {
        $saved = $this->template(['template_key' => 'saved', 'project_category_id' => 3, 'is_active' => false]);
        $active = $this->template(['project_category_id' => 3, 'version' => 2]);
        $contract = $this->contract();
        $contract->template_path = 'saved';
        $service = new ContractService;
        $this->assertSame($saved->id, $service->resolveTemplate($contract)->id);
        $saved->update(['project_category_id' => 4]);
        $this->assertSame($active->id, $service->resolveTemplate($contract)->id);
    }

    public function test_no_matching_template_is_reported_instead_of_using_an_unrelated_one(): void
    {
        $this->template(['project_category_id' => 4]);
        $this->assertNull((new ContractService)->resolveTemplate($this->contract()));
    }

    public function test_contract_category_and_type_override_project_defaults(): void
    {
        $this->template(['project_category_id' => 3, 'project_type_id' => 2]);
        $expected = $this->template(['project_category_id' => 5, 'project_type_id' => 6]);
        $contract = $this->contract();
        $contract->project_category_id = 5;
        $contract->project_type_id = 6;
        $this->assertSame($expected->id, (new ContractService)->resolveTemplate($contract)->id);
    }

    public function test_requested_template_must_be_active_and_match_the_contract(): void
    {
        $unrelated = $this->template(['project_category_id' => 4]);
        $this->expectException(ModelNotFoundException::class);
        (new ContractService)->resolveTemplate($this->contract(), $unrelated->id);
    }

    public function test_retired_template_cannot_be_requested_as_an_override(): void
    {
        $retired = $this->template(['is_active' => false]);
        $this->expectException(ModelNotFoundException::class);
        (new ContractService)->resolveTemplate($this->contract(), $retired->id);
    }

    public function test_rendering_includes_cover_footer_signature_and_escaped_legacy_tokens(): void
    {
        $template = $this->template([
            'cover_page' => '<h1>{{project_category}}</h1>',
            'template_body' => '<p>{{farmer_name}} {{membership_number}} {{outstanding}}</p>{{contract_footer}}{{contract_signature}}',
            'contract_footer' => '<footer>Footer {{contract_number}}</footer>',
            'contract_signature' => '<p>Signature {{farmer_name}}</p>',
        ]);
        $contract = $this->contract();
        $contract->member->first_name = '<b>Jane</b>';
        $html = (new ContractService)->renderTemplate($contract, $template);
        $this->assertStringContainsString('<h1>Honey</h1>', $html);
        $this->assertStringContainsString('&lt;b&gt;Jane&lt;/b&gt; Farmer', $html);
        $this->assertStringContainsString('MEM-001 900.00', $html);
        $this->assertSame(1, substr_count($html, 'Footer CT-001'));
        $this->assertSame(1, substr_count($html, 'Signature'));
        $this->assertStringNotContainsString('{{', $html);
    }

    public function test_separately_stored_footer_and_signature_are_appended(): void
    {
        $template = $this->template(['contract_footer' => '<p>Footer</p>', 'contract_signature' => '<p>Sign here</p>']);
        $html = (new ContractService)->renderTemplate($this->contract(), $template);
        $this->assertStringContainsString('<p>Footer</p><p>Sign here</p>', $html);
    }

    public function test_pdf_uses_the_same_automatically_resolved_document_as_the_contract_view(): void
    {
        $template = $this->template(['project_category_id' => 3]);
        $fixture = $this->contract();
        $contract = \Mockery::mock(Contract::class)->makePartial();
        $contract->setRawAttributes($fixture->getAttributes());
        $contract->setRelations($fixture->getRelations());
        $contract->shouldReceive('load')->once()->andReturnSelf();
        $screen = (new ContractController)->show($contract)->getData();
        $response = (new ContractPdfController)->show(Request::create('/contracts/1/pdf'), $contract);
        $view = $response->getOriginalContent();
        $this->assertSame($template->id, $view->getData()['template']->id);
        $this->assertSame($screen['template']->id, $view->getData()['template']->id);
        $this->assertSame($screen['renderedHtml'], $view->getData()['renderedHtml']);
        $this->assertStringContainsString('CT-001 · Jane Farmer', $response->getContent());
        $this->assertStringNotContainsString('<h4>Items</h4>', $response->getContent());
    }

    public function test_project_binding_migration_is_repeatable_and_preserves_existing_templates(): void
    {
        $template = $this->template();
        Schema::table('contract_templates', fn (Blueprint $table) => $table->dropColumn('project_id'));
        $migration = require __DIR__.'/../../database/migrations/2026_10_14_000001_add_project_to_contract_templates.php';
        $migration->up();
        $migration->up();
        $this->assertTrue(Schema::hasColumn('contract_templates', 'project_id'));
        $this->assertSame($template->template_name, ContractTemplate::find($template->id)->template_name);
        $migration->down();
        $this->assertFalse(Schema::hasColumn('contract_templates', 'project_id'));
    }
}
