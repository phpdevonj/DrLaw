<?php

namespace Database\Factories;

use App\Models\OtpVerification;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

class OtpVerificationFactory extends Factory
{
    protected $model = OtpVerification::class;

    public function definition(): array
    {
        return [
            'email'          => $this->faker->unique()->safeEmail(),
            'contact_number' => null,
            'otp'            => sprintf('%06d', mt_rand(1, 999999)),
            'expired_at'     => Carbon::now()->addMinutes(10),
        ];
    }

    /**
     * Create an expired OTP record.
     */
    public function expired(): static
    {
        return $this->state(fn () => [
            'expired_at' => Carbon::now()->subMinutes(1),
        ]);
    }

    /**
     * Create a phone-based OTP record.
     */
    public function forPhone(string $number): static
    {
        return $this->state(fn () => [
            'email'          => null,
            'contact_number' => $number,
        ]);
    }
}
