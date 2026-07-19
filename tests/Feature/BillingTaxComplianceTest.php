<?php

namespace Tests\Feature;

use App\Models\ErpAccount;
use App\Models\ErpCommercialDocument;
use App\Models\ErpParty;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BillingTaxComplianceTest extends TestCase
{
    use RefreshDatabase;

    public function test_tax_rate_and_billing_profile_can_be_created(): void
    {
        $this->seed();
        Sanctum::actingAs(User::first());

        $rateResponse = $this->postJson('/api/v1/billing-tax/rates', [
            'tax_type' => 'vat',
            'code' => 'VAT13',
            'name' => 'Standard VAT 13%',
            'rate' => 13,
            'effective_from' => now()->toDateString(),
        ]);

        $rateResponse->assertStatus(201)->assertJsonPath('rate', 13);
        $this->assertDatabaseHas('erp_tax_rates', ['tax_type' => 'vat', 'code' => 'VAT13']);

        $profileResponse = $this->postJson('/api/v1/billing-tax/profiles', [
            'profile_type' => 'tax_invoice',
            'display_name' => 'Tax Invoice',
            'series_prefix' => 'TI',
            'print_layout' => 'a4',
            'requires_vat' => true,
        ]);

        $profileResponse->assertStatus(201)->assertJsonPath('series_prefix', 'TI');
        $this->assertDatabaseHas('erp_billing_profiles', ['profile_type' => 'tax_invoice', 'series_prefix' => 'TI']);
    }

    public function test_tax_invoice_issue_assigns_ird_series_and_audit_entry(): void
    {
        $this->seed();
        Sanctum::actingAs(User::first());
        [$party, $account] = $this->billingFixture();
        $document = $this->document('sales_invoice', $party->id, $account->id, 1000, 130, 0, 'draft');

        $response = $this->postJson("/api/v1/billing-tax/documents/{$document->id}/issue");

        $response->assertOk()
            ->assertJsonPath('document_number', 'TI-000001')
            ->assertJsonPath('reference_number', 'TAX_INVOICE');
        $this->assertDatabaseHas('erp_tax_invoice_audits', [
            'commercial_document_id' => $document->id,
            'audit_type' => 'tax_invoice_issued',
        ]);
    }

    public function test_vat_and_tds_reports_are_generated_from_posted_documents(): void
    {
        $this->seed();
        Sanctum::actingAs(User::first());
        [$party, $account] = $this->billingFixture();
        $from = now()->subDay()->toDateString();
        $to = now()->addDay()->toDateString();

        $this->document('sales_invoice', $party->id, $account->id, 1000, 130, 15, 'posted');
        $this->document('purchase_bill', $party->id, $account->id, 500, 65, 5, 'posted');

        $vatResponse = $this->postJson('/api/v1/billing-tax/reports/vat', [
            'period_from' => $from,
            'period_to' => $to,
        ]);

        $vatResponse->assertStatus(201)
            ->assertJsonPath('sales_vat', 130)
            ->assertJsonPath('purchase_vat', 65)
            ->assertJsonPath('net_payable', 65);

        $tdsResponse = $this->postJson('/api/v1/billing-tax/reports/tds', [
            'period_from' => $from,
            'period_to' => $to,
        ]);

        $tdsResponse->assertStatus(201)->assertJsonPath('tds_deducted', 20);
    }

    public function test_posted_invoice_cannot_be_reissued(): void
    {
        $this->seed();
        Sanctum::actingAs(User::first());
        [$party, $account] = $this->billingFixture();
        $document = $this->document('sales_invoice', $party->id, $account->id, 1000, 130, 0, 'posted');

        $this->postJson("/api/v1/billing-tax/documents/{$document->id}/issue")
            ->assertStatus(422)
            ->assertJsonValidationErrors('document');
    }

    private function billingFixture(): array
    {
        $company = \App\Models\ErpCompany::first();
        $company->update(['pan' => '123456789', 'vat_number' => '123456789']);
        $account = ErpAccount::query()->first();
        $party = ErpParty::create([
            'company_id' => $company->id,
            'account_id' => $account->id,
            'party_type' => 'customer',
            'code' => 'TAX-CUS',
            'name' => 'Tax Customer',
            'pan' => '987654321',
        ]);

        return [$party, $account];
    }

    private function document(string $type, int $partyId, int $accountId, float $taxable, float $vat, float $tds, string $status): ErpCommercialDocument
    {
        $document = ErpCommercialDocument::create([
            'company_id' => 1,
            'branch_id' => 1,
            'fiscal_year_id' => 1,
            'party_id' => $partyId,
            'document_type' => $type,
            'document_number' => strtoupper(substr($type, 0, 3)) . '-' . random_int(1000, 9999),
            'document_date_ad' => now()->toDateString(),
            'status' => $status,
            'subtotal' => $taxable,
            'discount_total' => 0,
            'vat_total' => $vat,
            'tds_total' => $tds,
            'grand_total' => $taxable + $vat - $tds,
        ]);

        $document->lines()->create([
            'account_id' => $accountId,
            'description' => 'Tax test line',
            'quantity' => 1,
            'rate' => $taxable,
            'vat_rate' => $taxable > 0 ? ($vat / $taxable) * 100 : 0,
            'vat_amount' => $vat,
            'tds_rate' => $taxable > 0 ? ($tds / $taxable) * 100 : 0,
            'tds_amount' => $tds,
            'line_total' => $taxable + $vat - $tds,
            'line_order' => 1,
        ]);

        return $document;
    }
}
