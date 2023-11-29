<?php

namespace App\Services;

use App\Exceptions\AffiliateCreateException;
use App\Mail\AffiliateCreated;
use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class AffiliateService
{
    public function __construct(
        protected ApiService $apiService
    ) {}

    /**
     * Create a new affiliate for the merchant with the given commission rate.
     *
     * @param  Merchant $merchant
     * @param  string $email
     * @param  string $name
     * @param  float $commissionRate
     * @return Affiliate
     */
    public function register(Merchant $merchant, string $email, string $name, float $commissionRate): Affiliate
    {
        // TODO: Complete this method

        // Check if the email is already return exception
        
        if ($merchant->user->email === $email || Affiliate::whereHas('user', function ($query) use ($email) {
            $query->where('email', $email);
        })->exists()) {
            throw new AffiliateCreateException('Email is already in use as a merchant or affiliate.');
        }

        $discountCode = $this->apiService->createDiscountCode($merchant);

        // Create a new user
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'type' => User::TYPE_MERCHANT,
            'email_verified_at' => now(),
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'type' => User::TYPE_MERCHANT,
        ]);

        // Create a new affiliate
        $affiliate = Affiliate::create([
            'merchant_id' => $merchant->id,
            'user_id' => $user->id,
            'commission_rate' => $commissionRate,
            'discount_code' => $discountCode['code'],
        ]);

        // Send the AffiliateCreated email
        Mail::to($email)->send(new AffiliateCreated($affiliate));

        return $affiliate;

    }
}
