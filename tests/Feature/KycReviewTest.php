<?php

namespace Tests\Feature;

use App\Domain\Investments\InvestmentService;
use App\Domain\Kyc\KycService;
use App\Domain\Wallets\WalletService;
use App\Models\InvestmentPlan;
use App\Models\KycVerification;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KycReviewTest extends TestCase
{
    use RefreshDatabase;

    protected function adminUser(): User
    {
        $role = \Spatie\Permission\Models\Role::findOrCreate('admin', 'web');
        foreach (\Database\Seeders\RoleSeeder::ADMIN_PERMISSIONS as $permission) {
            \Spatie\Permission\Models\Permission::findOrCreate($permission, 'web');
        }
        $role->syncPermissions(\Database\Seeders\RoleSeeder::ADMIN_PERMISSIONS);

        return tap(User::factory()->create(), fn (User $user) => $user->assignRole('admin'));
    }

    protected function pendingSubmission(User $user): KycVerification
    {
        $user->update(['kyc_status' => User::KYC_PENDING]);

        return KycVerification::create([
            'user_id' => $user->id,
            'document_type' => 'passport',
            'document_path' => 'kyc/1/document_x.png',
            'document_back_path' => 'kyc/1/document_back_x.png',
            'selfie_path' => 'kyc/1/selfie_x.png',
            'status' => KycVerification::STATUS_PENDING,
        ]);
    }

    public function test_kyc_resource_renders_for_admin(): void
    {
        $admin = $this->adminUser();
        $user = User::factory()->create();
        $submission = $this->pendingSubmission($user);

        $this->actingAs($admin)
            ->get('/admin/kyc-verifications')
            ->assertOk()
            ->assertSee($user->name);

        $this->actingAs($admin)
            ->get("/admin/kyc-verifications/{$submission->id}")
            ->assertOk()
            ->assertSee('Submitted Documents');
    }

    public function test_non_admin_cannot_access_kyc_resource(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/admin/kyc-verifications')
            ->assertForbidden();
    }

    public function test_kyc_approve_verifies_user_and_notifies(): void
    {
        $admin = $this->adminUser();
        $user = User::factory()->create();
        $submission = $this->pendingSubmission($user);

        app(KycService::class)->approve($submission, $admin);

        $this->assertSame(User::KYC_VERIFIED, $user->fresh()->kyc_status);
        $this->assertSame(KycVerification::STATUS_VERIFIED, $submission->fresh()->status);
        $this->assertSame($admin->id, $submission->fresh()->reviewed_by);
        $this->assertNotNull($submission->fresh()->reviewed_at);
        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_kyc_reject_marks_user_rejected_with_reason(): void
    {
        $admin = $this->adminUser();
        $user = User::factory()->create();
        $submission = $this->pendingSubmission($user);

        app(KycService::class)->reject($submission, $admin, 'Document is unreadable.');

        $this->assertSame(User::KYC_REJECTED, $user->fresh()->kyc_status);
        $this->assertSame(KycVerification::STATUS_REJECTED, $submission->fresh()->status);
        $this->assertSame('Document is unreadable.', $submission->fresh()->rejection_reason);
        $this->assertSame($admin->id, $submission->fresh()->reviewed_by);
        $this->assertDatabaseCount('notifications', 1);
    }

    public function test_kyc_cannot_approve_twice(): void
    {
        $admin = $this->adminUser();
        $user = User::factory()->create();
        $submission = $this->pendingSubmission($user);

        app(KycService::class)->approve($submission, $admin);

        $this->expectException(\LogicException::class);
        app(KycService::class)->approve($submission->fresh(), $admin);
    }

    public function test_operations_page_renders_for_admin(): void
    {
        $admin = $this->adminUser();

        $this->actingAs($admin)
            ->get('/admin/operations')
            ->assertOk()
            ->assertSee('Credit interest now');
    }

    public function test_operations_credit_interest_credits_earnings_wallet(): void
    {
        $admin = $this->adminUser();
        $plan = InvestmentPlan::factory()->create();
        $user = User::factory()->create();

        $walletService = app(WalletService::class);
        $walletService->credit(
            $walletService->getOrCreate($user, Wallet::TYPE_PRINCIPAL),
            1000,
            'Deposit',
        );

        $investment = app(InvestmentService::class)->create($user, $plan, 100);
        $investment->forceFill(['last_interest_credited_at' => now()->subDays(2)])->save();

        Livewire::actingAs($admin)
            ->test(\App\Filament\Pages\Operations::class)
            ->call('creditInterest');

        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $walletService->getOrCreate($user, Wallet::TYPE_EARNINGS)->id,
            'description' => "Daily interest — {$plan->name}",
        ]);
    }
}
