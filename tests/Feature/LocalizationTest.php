<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Support\FormatHelper;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles if needed
        Role::firstOrCreate(
            ['role_code' => 'super_admin'],
            ['role_name' => 'Super Admin', 'is_active' => true]
        );
        Role::firstOrCreate(
            ['role_code' => 'user'],
            ['role_name' => 'Standard User', 'is_active' => true]
        );
    }

    public function test_default_locale_is_english_for_guests(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $this->assertEquals('en', app()->getLocale());
        $response->assertSee('Welcome to MyFinance');
        $response->assertSee('Sign In');
    }

    public function test_new_user_defaults_to_english(): void
    {
        $user = User::factory()->create();

        $this->assertEquals('en', $user->locale ?? 'en');
        $this->assertEquals('en', $user->getLocale());
    }

    public function test_guest_can_switch_locale_via_post(): void
    {
        $response = $this->post('/locale', ['locale' => 'id']);

        $response->assertRedirect();
        $response->assertCookie('locale', 'id');
        $this->assertEquals('id', session('locale'));
    }

    public function test_guest_can_switch_locale_via_get(): void
    {
        $response = $this->get('/locale/id');

        $response->assertRedirect();
        $response->assertCookie('locale', 'id');
        $this->assertEquals('id', session('locale'));
    }

    public function test_authenticated_user_persists_locale_in_database(): void
    {
        $user = User::factory()->create(['locale' => 'en']);

        $this->actingAs($user)
            ->post('/locale', ['locale' => 'id'])
            ->assertRedirect();

        $user->refresh();
        $this->assertEquals('id', $user->locale);
    }

    public function test_user_locale_persists_via_settings_update_route(): void
    {
        $user = User::factory()->create(['locale' => 'en']);
        $role = Role::where('role_code', 'super_admin')->first();
        $user->roles()->attach($role->id);

        $this->actingAs($user)
            ->put('/settings/locale', ['locale' => 'id'])
            ->assertRedirect();

        $user->refresh();
        $this->assertEquals('id', $user->locale);
    }

    public function test_unsupported_locale_falls_back_to_english(): void
    {
        $response = $this->post('/locale', ['locale' => 'fr']);

        $response->assertRedirect();
        $this->assertEquals('en', session('locale'));
    }

    public function test_user_locale_is_restored_upon_subsequent_requests(): void
    {
        $user = User::factory()->create(['locale' => 'id']);
        $role = Role::where('role_code', 'super_admin')->first();
        $user->roles()->attach($role->id);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $this->assertEquals('id', app()->getLocale());
        $response->assertSee('Dasbor');
    }

    public function test_financial_terminology_translations(): void
    {
        App::setLocale('en');
        $this->assertEquals('Principal Amount', __('debts.principal_amount'));
        $this->assertEquals('Interest Rate', __('debts.interest_rate'));
        $this->assertEquals('Interest Period', __('debts.interest_period'));
        $this->assertEquals('Administration Fee', __('debts.administration_fee'));
        $this->assertEquals('Loan Tenor', __('debts.loan_tenor'));
        $this->assertEquals('Installment', __('debts.installment'));
        $this->assertEquals('Outstanding Balance', __('debts.outstanding_balance'));
        $this->assertEquals('Total Repayment', __('debts.total_repayment'));
        $this->assertEquals('Due Date', __('debts.due_date'));
        $this->assertEquals('Receivable', __('debts.receivable'));
        $this->assertEquals('Payable', __('debts.payable'));
        $this->assertEquals('Cash Flow', __('dashboard.cash_flow'));

        App::setLocale('id');
        $this->assertEquals('Pokok Pinjaman', __('debts.principal_amount'));
        $this->assertEquals('Suku Bunga', __('debts.interest_rate'));
        $this->assertEquals('Periode Bunga', __('debts.interest_period'));
        $this->assertEquals('Biaya Administrasi', __('debts.administration_fee'));
        $this->assertEquals('Jangka Waktu Pinjaman', __('debts.loan_tenor'));
        $this->assertEquals('Cicilan', __('debts.installment'));
        $this->assertEquals('Sisa Kewajiban', __('debts.outstanding_balance'));
        $this->assertEquals('Total Pembayaran', __('debts.total_repayment'));
        $this->assertEquals('Tanggal Jatuh Tempo', __('debts.due_date'));
        $this->assertEquals('Piutang', __('debts.receivable'));
        $this->assertEquals('Utang', __('debts.payable'));
        $this->assertEquals('Arus Kas', __('dashboard.cash_flow'));
    }

    public function test_number_and_currency_formatting_by_locale(): void
    {
        App::setLocale('en');
        $this->assertEquals('1,234.56', FormatHelper::number(1234.56, 2));
        $this->assertEquals('1,235', FormatHelper::number(1234.56, 0));
        $this->assertEquals('Rp 1,234.56', FormatHelper::currency(1234.56, 'Rp', 2));

        App::setLocale('id');
        $this->assertEquals('1.234,56', FormatHelper::number(1234.56, 2));
        $this->assertEquals('1.235', FormatHelper::number(1234.56, 0));
        $this->assertEquals('Rp 1.234,56', FormatHelper::currency(1234.56, 'Rp', 2));
    }

    public function test_date_formatting_by_locale(): void
    {
        $date = Carbon::parse('2026-11-14');

        App::setLocale('en');
        Carbon::setLocale('en_US');
        $this->assertEquals('Nov 14, 2026', FormatHelper::date($date));

        App::setLocale('id');
        Carbon::setLocale('id_ID');
        $this->assertEquals('14 Nov 2026', FormatHelper::date($date));
    }

    public function test_validation_attributes_are_localized(): void
    {
        App::setLocale('en');
        $this->assertEquals('principal amount', __('validation.attributes.principal_amount'));
        $this->assertEquals('suku bunga', (function () {
            App::setLocale('id');
            return __('validation.attributes.interest_rate');
        })());
    }
}
