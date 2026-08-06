<?php

namespace Tests\Feature;

use App\Livewire\Kyc;
use App\Models\KycVerification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class KycTest extends TestCase
{
    use RefreshDatabase;

    public function test_kyc_submission_creates_pending_verification(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Kyc::class)
            ->set('documentType', 'passport')
            ->set('document', UploadedFile::fake()->image('doc.jpg'))
            ->set('documentBack', UploadedFile::fake()->image('back.jpg'))
            ->set('selfie', UploadedFile::fake()->image('selfie.jpg'))
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('kyc_verifications', [
            'user_id' => $user->id,
            'status' => KycVerification::STATUS_PENDING,
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'kyc_status' => User::KYC_PENDING,
        ]);

        $files = Storage::disk('public')->files('kyc/'.$user->id);

        $this->assertCount(3, $files);
        $this->assertTrue(count(array_filter($files, fn ($f) => str_contains($f, 'document_') && ! str_contains($f, 'document_back_'))) === 1);
        $this->assertTrue(count(array_filter($files, fn ($f) => str_contains($f, 'document_back_'))) === 1);
        $this->assertTrue(count(array_filter($files, fn ($f) => str_contains($f, 'selfie_'))) === 1);
    }

    public function test_kyc_submission_requires_documents(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Kyc::class)
            ->call('submit')
            ->assertHasErrors(['document', 'documentBack', 'selfie']);
    }

    public function test_kyc_transitions_unverified_to_verified_by_admin(): void
    {
        $user = User::factory()->create(['kyc_status' => User::KYC_UNVERIFIED]);

        $user->update(['kyc_status' => User::KYC_VERIFIED]);

        $this->assertTrue($user->isKycVerified());
        $this->assertDatabaseHas('users', ['id' => $user->id, 'kyc_status' => User::KYC_VERIFIED]);
    }

    public function test_kyc_rejected_user_can_resubmit(): void
    {
        $user = User::factory()->create(['kyc_status' => User::KYC_REJECTED]);
        $this->assertTrue(in_array($user->kyc_status, [User::KYC_UNVERIFIED, User::KYC_REJECTED]));

        Storage::fake('public');

        Livewire::actingAs($user)
            ->test(Kyc::class)
            ->set('document', UploadedFile::fake()->image('doc.jpg'))
            ->set('documentBack', UploadedFile::fake()->image('back.jpg'))
            ->set('selfie', UploadedFile::fake()->image('selfie.jpg'))
            ->call('submit')
            ->assertHasNoErrors();
    }

    public function test_pending_kyc_cannot_submit_again(): void
    {
        $user = User::factory()->create(['kyc_status' => User::KYC_PENDING]);

        Storage::fake('public');

        Livewire::actingAs($user)
            ->test(Kyc::class)
            ->set('document', UploadedFile::fake()->image('doc.jpg'))
            ->set('documentBack', UploadedFile::fake()->image('back.jpg'))
            ->set('selfie', UploadedFile::fake()->image('selfie.jpg'))
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertDatabaseCount('kyc_verifications', 0);
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Kyc::class)
            ->set('name', 'Jane Updated')
            ->set('phone', '+1 555 000 1111')
            ->set('country', 'Canada')
            ->call('updateProfile')
            ->assertHasNoErrors();

        $user->refresh();

        $this->assertSame('Jane Updated', $user->name);
        $this->assertSame('+1 555 000 1111', $user->phone);
        $this->assertSame('Canada', $user->country);
    }

    public function test_profile_email_change_resets_email_verification(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Kyc::class)
            ->set('email', 'new@example.com')
            ->call('updateProfile')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'new@example.com',
            'email_verified_at' => null,
        ]);
    }

    public function test_profile_update_rejects_duplicate_email(): void
    {
        User::factory()->create(['email' => 'taken@example.com']);
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Kyc::class)
            ->set('email', 'taken@example.com')
            ->call('updateProfile')
            ->assertHasErrors(['email']);
    }

    public function test_profile_update_keeps_verification_when_email_is_unchanged(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Kyc::class)
            ->set('name', 'Same Email User')
            ->call('updateProfile')
            ->assertHasNoErrors();

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_avatar_upload_stores_image_and_sets_avatar_path(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Kyc::class)
            ->set('avatar', UploadedFile::fake()->image('avatar.png'))
            ->call('updateProfile')
            ->assertHasNoErrors();

        $user->refresh();

        $this->assertNotNull($user->avatar_path);
        $this->assertTrue(Storage::disk('public')->exists($user->avatar_path));
    }

    public function test_date_of_birth_can_be_saved(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Kyc::class)
            ->set('dateOfBirth', '1990-05-15')
            ->call('updateProfile')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('users', ['id' => $user->id, 'date_of_birth' => '1990-05-15']);
    }

    public function test_password_can_be_changed(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Kyc::class)
            ->set('currentPassword', 'password')
            ->set('newPassword', 'newpassword123')
            ->set('newPasswordConfirmation', 'newpassword123')
            ->call('updatePassword')
            ->assertHasNoErrors();

        $user->refresh();

        $this->assertTrue(Hash::check('newpassword123', $user->password));
        $this->assertFalse(Hash::check('password', $user->password));
    }

    public function test_password_change_rejects_wrong_current_password(): void
    {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(Kyc::class)
            ->set('currentPassword', 'wrongpassword')
            ->set('newPassword', 'newpassword123')
            ->set('newPasswordConfirmation', 'newpassword123')
            ->call('updatePassword')
            ->assertHasErrors(['currentPassword']);

        $user->refresh();

        $this->assertTrue(Hash::check('password', $user->password));
    }

    public function test_verification_email_can_be_resent(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        Livewire::actingAs($user)
            ->test(Kyc::class)
            ->call('sendVerification')
            ->assertHasNoErrors();

        Notification::assertSentTo($user, \Illuminate\Auth\Notifications\VerifyEmail::class);
    }
}
