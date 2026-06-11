<?php

namespace Tests\Feature;

use App\Models\OtpVerification;
use App\Mail\OtpVerificationMail;
use App\Services\TwilioService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OtpApiTest extends TestCase
{
    use RefreshDatabase;

    // ─────────────────────────────────────────────────────────
    //  SEND OTP – Email (default channel)
    // ─────────────────────────────────────────────────────────

    /** @test */
    public function send_otp_via_email_returns_success()
    {
        Mail::fake();

        $response = $this->postJson('/api/send-otp', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'status'  => true,
                 ])
                 ->assertJsonFragment(['sent_via' => ['email']]);

        // OTP record should exist in DB
        $this->assertDatabaseHas('otp_verifications', [
            'email' => 'test@example.com',
        ]);

        // Mail should have been sent
        Mail::assertSent(OtpVerificationMail::class, function ($mail) {
            return $mail->hasTo('test@example.com');
        });
    }

    /** @test */
    public function send_otp_via_email_requires_valid_email()
    {
        $response = $this->postJson('/api/send-otp', [
            'email' => 'not-an-email',
        ]);

        $response->assertStatus(400);
    }

    /** @test */
    public function send_otp_via_email_requires_email_field()
    {
        $response = $this->postJson('/api/send-otp', []);

        $response->assertStatus(400);
    }

    /** @test */
    public function send_otp_default_type_is_email()
    {
        Mail::fake();

        // No otp_type provided — should default to email
        $response = $this->postJson('/api/send-otp', [
            'email' => 'default@example.com',
        ]);

        $response->assertStatus(200)
                 ->assertJsonFragment(['sent_via' => ['email']]);

        Mail::assertSent(OtpVerificationMail::class);
    }

    /** @test */
    public function send_otp_overwrites_previous_otp_for_same_email()
    {
        Mail::fake();

        // First OTP
        OtpVerification::create([
            'email'      => 'duplicate@example.com',
            'otp'        => '111111',
            'expired_at' => Carbon::now()->addMinutes(10),
        ]);

        // Send new OTP
        $response = $this->postJson('/api/send-otp', [
            'email' => 'duplicate@example.com',
        ]);

        $response->assertStatus(200);

        // Should be only one record (updateOrCreate)
        $this->assertEquals(1, OtpVerification::where('email', 'duplicate@example.com')->count());

        // OTP should be updated (not the old one)
        $record = OtpVerification::where('email', 'duplicate@example.com')->first();
        $this->assertNotEquals('111111', $record->otp);
    }

    // ─────────────────────────────────────────────────────────
    //  SEND OTP – Phone (SMS channel)
    // ─────────────────────────────────────────────────────────

    /** @test */
    public function send_otp_via_phone_returns_success()
    {
        Mail::fake();

        // Mock TwilioService to avoid real SMS
        $this->mock(TwilioService::class, function ($mock) {
            $mock->shouldReceive('sendSMS')->once()->andReturn(true);
        });

        $response = $this->postJson('/api/send-otp', [
            'otp_type'       => 'phone',
            'contact_number' => '+911234567890',
        ]);

        $response->assertStatus(200)
                 ->assertJsonFragment(['status' => true]);

        $this->assertDatabaseHas('otp_verifications', [
            'contact_number' => '+911234567890',
        ]);
    }

    /** @test */
    public function send_otp_via_phone_requires_contact_number()
    {
        $response = $this->postJson('/api/send-otp', [
            'otp_type' => 'phone',
        ]);

        $response->assertStatus(400);
    }

    /** @test */
    public function send_otp_via_phone_also_sends_email_if_provided()
    {
        Mail::fake();

        $this->mock(TwilioService::class, function ($mock) {
            $mock->shouldReceive('sendSMS')->once()->andReturn(true);
        });

        $response = $this->postJson('/api/send-otp', [
            'otp_type'       => 'phone',
            'contact_number' => '+911234567890',
            'email'          => 'also@example.com',
        ]);

        $response->assertStatus(200)
                 ->assertJsonFragment(['sent_via' => ['phone', 'email']]);

        // Both channels should be reported
        Mail::assertSent(OtpVerificationMail::class, function ($mail) {
            return $mail->hasTo('also@example.com');
        });
    }

    /** @test */
    public function send_otp_via_phone_without_email_does_not_send_email()
    {
        Mail::fake();

        $this->mock(TwilioService::class, function ($mock) {
            $mock->shouldReceive('sendSMS')->once()->andReturn(true);
        });

        $response = $this->postJson('/api/send-otp', [
            'otp_type'       => 'phone',
            'contact_number' => '+911234567890',
        ]);

        $response->assertStatus(200)
                 ->assertJsonFragment(['sent_via' => ['phone']]);

        Mail::assertNothingSent();
    }

    // ─────────────────────────────────────────────────────────
    //  VERIFY OTP – Email
    // ─────────────────────────────────────────────────────────

    /** @test */
    public function verify_otp_with_valid_email_otp_returns_success()
    {
        OtpVerification::create([
            'email'      => 'verify@example.com',
            'otp'        => '123456',
            'expired_at' => Carbon::now()->addMinutes(10),
        ]);

        $response = $this->postJson('/api/verify-otp', [
            'email' => 'verify@example.com',
            'otp'   => '123456',
        ]);

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'status'  => true,
                     'message' => 'OTP verified successfully.',
                 ]);

        // OTP record should be deleted after successful verification
        $this->assertDatabaseMissing('otp_verifications', [
            'email' => 'verify@example.com',
        ]);
    }

    /** @test */
    public function verify_otp_with_invalid_otp_returns_error()
    {
        OtpVerification::create([
            'email'      => 'verify@example.com',
            'otp'        => '123456',
            'expired_at' => Carbon::now()->addMinutes(10),
        ]);

        $response = $this->postJson('/api/verify-otp', [
            'email' => 'verify@example.com',
            'otp'   => '999999',
        ]);

        $response->assertStatus(400);
    }

    /** @test */
    public function verify_otp_with_expired_otp_returns_error()
    {
        OtpVerification::create([
            'email'      => 'expired@example.com',
            'otp'        => '123456',
            'expired_at' => Carbon::now()->subMinutes(1), // already expired
        ]);

        $response = $this->postJson('/api/verify-otp', [
            'email' => 'expired@example.com',
            'otp'   => '123456',
        ]);

        $response->assertStatus(400)
                 ->assertJsonFragment(['message' => 'OTP has expired.Please retry again']);
    }

    /** @test */
    public function verify_otp_with_nonexistent_email_returns_invalid()
    {
        $response = $this->postJson('/api/verify-otp', [
            'email' => 'nobody@example.com',
            'otp'   => '123456',
        ]);

        $response->assertStatus(400)
                 ->assertJsonFragment(['message' => 'Invalid OTP.']);
    }

    /** @test */
    public function verify_otp_requires_otp_field()
    {
        $response = $this->postJson('/api/verify-otp', [
            'email' => 'test@example.com',
        ]);

        $response->assertStatus(400);
    }

    /** @test */
    public function verify_otp_requires_6_digit_otp()
    {
        $response = $this->postJson('/api/verify-otp', [
            'email' => 'test@example.com',
            'otp'   => '123', // too short
        ]);

        $response->assertStatus(400);
    }

    // ─────────────────────────────────────────────────────────
    //  VERIFY OTP – Phone
    // ─────────────────────────────────────────────────────────

    /** @test */
    public function verify_otp_with_valid_phone_otp_returns_success()
    {
        OtpVerification::create([
            'contact_number' => '+911234567890',
            'otp'            => '654321',
            'expired_at'     => Carbon::now()->addMinutes(10),
        ]);

        $response = $this->postJson('/api/verify-otp', [
            'otp_type'       => 'phone',
            'contact_number' => '+911234567890',
            'otp'            => '654321',
        ]);

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'status'  => true,
                     'message' => 'OTP verified successfully.',
                 ]);

        // Record should be deleted
        $this->assertDatabaseMissing('otp_verifications', [
            'contact_number' => '+911234567890',
        ]);
    }

    /** @test */
    public function verify_otp_with_wrong_phone_otp_returns_error()
    {
        OtpVerification::create([
            'contact_number' => '+911234567890',
            'otp'            => '654321',
            'expired_at'     => Carbon::now()->addMinutes(10),
        ]);

        $response = $this->postJson('/api/verify-otp', [
            'otp_type'       => 'phone',
            'contact_number' => '+911234567890',
            'otp'            => '000000',
        ]);

        $response->assertStatus(400)
                 ->assertJsonFragment(['message' => 'Invalid OTP.']);
    }

    /** @test */
    public function verify_otp_with_expired_phone_otp_returns_error()
    {
        OtpVerification::create([
            'contact_number' => '+911234567890',
            'otp'            => '654321',
            'expired_at'     => Carbon::now()->subMinutes(5),
        ]);

        $response = $this->postJson('/api/verify-otp', [
            'otp_type'       => 'phone',
            'contact_number' => '+911234567890',
            'otp'            => '654321',
        ]);

        $response->assertStatus(400);
    }

    // ─────────────────────────────────────────────────────────
    //  FULL FLOW: Send → Verify
    // ─────────────────────────────────────────────────────────

    /** @test */
    public function full_email_otp_flow_send_then_verify()
    {
        Mail::fake();

        // Step 1: Send OTP
        $sendResponse = $this->postJson('/api/send-otp', [
            'email' => 'flow@example.com',
        ]);
        $sendResponse->assertStatus(200);

        // Step 2: Get the OTP from DB
        $otpRecord = OtpVerification::where('email', 'flow@example.com')->first();
        $this->assertNotNull($otpRecord);

        // Step 3: Verify OTP
        $verifyResponse = $this->postJson('/api/verify-otp', [
            'email' => 'flow@example.com',
            'otp'   => $otpRecord->otp,
        ]);

        $verifyResponse->assertStatus(200)
                       ->assertJsonFragment(['message' => 'OTP verified successfully.']);

        // Step 4: OTP should be deleted
        $this->assertDatabaseMissing('otp_verifications', [
            'email' => 'flow@example.com',
        ]);
    }

    /** @test */
    public function full_phone_otp_flow_send_then_verify()
    {
        Mail::fake();

        $this->mock(TwilioService::class, function ($mock) {
            $mock->shouldReceive('sendSMS')->once()->andReturn(true);
        });

        // Step 1: Send OTP via phone
        $sendResponse = $this->postJson('/api/send-otp', [
            'otp_type'       => 'phone',
            'contact_number' => '+919876543210',
        ]);
        $sendResponse->assertStatus(200);

        // Step 2: Get the OTP from DB
        $otpRecord = OtpVerification::where('contact_number', '+919876543210')->first();
        $this->assertNotNull($otpRecord);

        // Step 3: Verify OTP
        $verifyResponse = $this->postJson('/api/verify-otp', [
            'otp_type'       => 'phone',
            'contact_number' => '+919876543210',
            'otp'            => $otpRecord->otp,
        ]);

        $verifyResponse->assertStatus(200)
                       ->assertJsonFragment(['message' => 'OTP verified successfully.']);

        // Step 4: OTP should be deleted
        $this->assertDatabaseMissing('otp_verifications', [
            'contact_number' => '+919876543210',
        ]);
    }
}
