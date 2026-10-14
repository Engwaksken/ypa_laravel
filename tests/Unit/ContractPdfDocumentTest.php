<?php

namespace Tests\Unit;

use App\Http\Controllers\ContractPdfController;
use App\Models\Contract;
use App\Models\ContractItem;
use App\Models\ContractTemplate;
use App\Models\Member;
use App\Models\MemberBankDetail;
use App\Models\MemberNextOfKin;
use App\Models\PaymentMethod;
use App\Models\PaymentTransaction;
use App\Models\Project;
use App\Models\ProjectCategory;
use App\Services\ContractPdfDocumentService;
use App\Services\ContractService;
use Illuminate\Http\Request;
use Tests\TestCase;

class ContractPdfDocumentTest extends TestCase
{
    private function contract(): Contract
    {
        $project = new Project(['project_name' => 'THE MIGHTY YPA MAIZE']);
        $project->setRelation('category', new ProjectCategory(['category_name' => 'Maize']));
        $member = new Member(['first_name' => 'Jane', 'last_name' => 'Farmer', 'membership_id' => 'MEM-DEMO', 'nin' => 'TEST-NIN', 'member_photo' => '/images/logo.png']);
        $member->setRelation('nextOfKin', collect([new MemberNextOfKin(['photo' => '/images/logo.png'])]));
        $member->setRelation('bankDetail', new MemberBankDetail(['bank_name' => 'Demo Bank', 'bank_branch' => 'Demo Branch', 'bank_account' => 'TEST-ACCOUNT']));
        $item = new ContractItem;
        $item->forceFill(['item_name' => 'Maize Acreage', 'item_type' => 'maize', 'quantity' => 3, 'unit_name' => 'acre',
            'total_price' => 12000000, 'expected_quantity' => 108, 'expected_units' => 'bags', 'harvest_per_bag' => 199950, 'projected_harvest_balance' => 21600000]);
        $item->setRelation('project', $project);
        $payment = $this->payment();
        $contract = new Contract(['contract_number' => 'CT-DEMO', 'contract_for' => 'member', 'signing_date' => '2026-08-23',
            'contract_amount' => 12000000, 'contract_amount_paid' => 12000000, 'total_payable' => 12100000,
            'total_paid' => 12000000, 'expected_quantity' => 108, 'expected_units' => 'bags', 'projected_harvest_amount' => 21600000, 'projected_harvest_balance' => 21600000]);
        foreach (['project' => $project, 'member' => $member, 'group' => null, 'branch' => null, 'paymentMethod' => null,
            'items' => collect([$item]), 'projectData' => null, 'payments' => collect([$payment])] as $key => $relation) $contract->setRelation($key, $relation);
        return $contract;
    }

    private function payment(string $status = 'APPROVED'): PaymentTransaction
    {
        $payment = new PaymentTransaction;
        $payment->forceFill(['status' => $status, 'transaction_date' => '2026-08-23', 'amount' => 12100000,
            'total_amount' => 12100000, 'total_amount_paid' => 12100000, 'registration_fee_paid' => 100000,
            'contract_amount_paid' => 12000000, 'reference' => 'DEMO-REF']);
        $payment->setRelation('paymentMethod', new PaymentMethod(['method_name' => 'Bank Deposit']));
        return $payment;
    }

    public function test_structural_blocks_are_separated_without_losing_nested_legal_content(): void
    {
        $html = '<section class="contract-cover-custom"><h1>Custom Cover</h1></section>' .
            '<article class="agreement"><h2>Agreement</h2><p>Legal wording remains unchanged.</p><p><strong>IN WITNESS WHEREOF the parties sign;</strong></p><table><tr><td>Custom signing line</td></tr></table></article>';
        $parts = (new ContractPdfDocumentService)->sections($html);
        $this->assertStringContainsString('Custom Cover', $parts['coverHtml']);
        $this->assertStringContainsString('Legal wording remains unchanged.', $parts['bodyHtml']);
        $this->assertStringNotContainsString('IN WITNESS', $parts['bodyHtml']);
        $this->assertStringContainsString('Custom signing line', $parts['signatureHtml']);
        $this->assertStringNotContainsString('Legal wording', $parts['signatureHtml']);
    }

    public function test_duplicate_bank_headings_and_stray_list_punctuation_are_removed_only_in_pdf(): void
    {
        $parts = (new ContractPdfDocumentService)->sections('<h3>Farmer’s acknowledgment of bank details</h3><p>Bank consent unchanged.</p>' .
            '<p><strong>Farmer’s acknowledgment of bank details</strong></p><table><tr><td>Demo Bank</td></tr></table><ol type="a"><li>) Clause unchanged.</li></ol><h3>9. TERMINATION</h3><p>Terms unchanged.</p>');
        $this->assertSame(1, substr_count($parts['bodyHtml'], 'acknowledgment of bank details'));
        $this->assertStringContainsString('Bank consent unchanged.', $parts['bodyHtml']);
        $this->assertStringContainsString('<li>Clause unchanged.</li>', $parts['bodyHtml']);
        $this->assertStringContainsString('class="keep-heading"', $parts['bodyHtml']);
    }

    public function test_receipt_includes_all_paid_components_but_never_unapproved_or_deleted_records(): void
    {
        $contract = $this->contract();
        $deleted = $this->payment(); $deleted->deleted_at = now();
        $contract->setRelation('payments', collect([$this->payment(), $this->payment('PENDING'), $this->payment('REJECTED'), $deleted]));
        $receipt = (new ContractPdfDocumentService)->receipt($contract);
        $this->assertCount(1, $receipt['rows']);
        $this->assertSame(12100000.0, $receipt['paid']);
        $this->assertSame(12100000.0, $receipt['scheduled']);
        $this->assertSame(0.0, $receipt['outstanding']);
        $this->assertSame(100000.0, $receipt['rows'][0]['registration']);
        $this->assertSame(12000000.0, $receipt['rows'][0]['contract']);
    }

    public function test_missing_details_are_flagged_without_inventing_identity_or_bank_values(): void
    {
        $contract = $this->contract(); $contract->member->nin = null; $contract->member->setRelation('bankDetail', null);
        $this->assertSame(['Member NIN', 'Bank name', 'Bank branch'], (new ContractPdfDocumentService)->missingDetails($contract));
    }

    public function test_public_images_are_used_but_external_portraits_are_not_fetched(): void
    {
        $contract = $this->contract();
        $document = (new ContractPdfDocumentService)->prepare($contract, null, '');
        $this->assertStringStartsWith('file://', $document['logo']);
        $this->assertStringStartsWith('file://', $document['partyPhoto']);
        $contract->farmer_photo = 'https://external.test/private-photo.png';
        $document = (new ContractPdfDocumentService)->prepare($contract, null, '');
        $this->assertNull($document['partyPhoto']);
    }

    public function test_separately_stored_custom_signature_is_kept_on_its_own_page(): void
    {
        $contract = $this->contract();
        $template = new ContractTemplate(['template_body' => '<p>Agreement wording.</p>', 'contract_signature' => '<p>Custom witness: {{farmer_name}}</p>']);
        $html = (new ContractService)->renderTemplate($contract, $template);
        $document = (new ContractPdfDocumentService)->prepare($contract, $template, $html);
        $this->assertStringContainsString('Agreement wording.', $document['bodyHtml']);
        $this->assertStringNotContainsString('Custom witness', $document['bodyHtml']);
        $this->assertStringContainsString('Custom witness: Jane Farmer', $document['signatureHtml']);
    }

    public function test_document_sections_match_reference_order_and_preserve_recorded_discrepant_amounts(): void
    {
        $contract = $this->contract(); $template = new ContractTemplate(['template_body' => '<p>Agreement wording.</p>']);
        $html = view('contracts.pdf', ['contract' => $contract, 'template' => $template, 'renderedHtml' => '<p>Agreement wording.</p>',
            'totals' => (new ContractService)->contractTotals($contract)])->render();
        $sections = ['THE REPUBLIC OF UGANDA', 'NEXT OF KIN', 'Agreement wording.', 'IN WITNESS WHEREOF',
            'A TABLE SHOWING PURCHASES AND HARVESTS', 'CONTRACT ITEMS', 'OFFICIAL PAYMENT RECEIPT'];
        $previous = -1;
        foreach ($sections as $section) {
            $position = strpos($html, $section);
            $this->assertNotFalse($position); $this->assertGreaterThan($previous, $position); $previous = $position;
        }
        $this->assertStringContainsString('199,950.00', $html);
        $this->assertStringContainsString('21,600,000.00', $html);
        $this->assertStringContainsString('12,100,000.00', $html);
        $this->assertStringContainsString('100,000.00', $html);
        $this->assertStringNotContainsString('21,594,600', $html);
    }

    public function test_dompdf_renders_separate_cover_agreement_signatures_purchase_items_and_receipt_pages(): void
    {
        $contract = $this->contract(); $template = new ContractTemplate(['template_body' => '<h3>AGREEMENT</h3><p>Terms remain unchanged.</p>']);
        $service = \Mockery::mock(ContractService::class)->makePartial();
        $service->shouldReceive('resolveTemplate')->andReturn($template);
        $this->app->instance(ContractService::class, $service);
        $response = (new ContractPdfController)->show(Request::create('/contracts/6/pdf'), $contract);
        $this->assertStringStartsWith('%PDF-', $response->getContent());
        preg_match_all('/\/Type\s*\/Page\b/', $response->getContent(), $pages);
        $this->assertCount(6, $pages[0]);
    }

    public function test_no_approved_payments_does_not_issue_a_fake_official_receipt(): void
    {
        $contract = $this->contract(); $contract->setRelation('payments', collect([$this->payment('PENDING')]));
        $document = (new ContractPdfDocumentService)->prepare($contract, null, '');
        $html = view('contracts.documents.receipt', compact('contract', 'document'))->render();
        $this->assertStringContainsString('PAYMENT SUMMARY', $html);
        $this->assertStringContainsString('not proof of payment', $html);
        $this->assertStringNotContainsString('OFFICIAL PAYMENT RECEIPT', $html);
    }
}
