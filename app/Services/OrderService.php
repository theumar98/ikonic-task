<?php

namespace App\Services;

use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;

class OrderService
{
    public function __construct(
        protected AffiliateService $affiliateService
    ) {}

    /**
     * Process an order and log any commissions.
     * This should create a new affiliate if the customer_email is not already associated with one.
     * This method should also ignore duplicates based on order_id.
     *
     * @param  array{order_id: string, subtotal_price: float, merchant_domain: string, discount_code: string, customer_email: string, customer_name: string} $data
     * @return void
     */
    public function processOrder(array $data)
    {
        // TODO: Complete this method

        // return if it already exists
        if (Order::where('external_order_id', $data['order_id'])->exists()) {
            return;
        }

        $affiliate = Affiliate::where('discount_code', $data['discount_code'])->first();

        $commissionRate = null;

        if (!empty($affiliate)) {

            try {
                $merchant = Merchant::where('domain',$data['merchant_domain'])->first();

                $commissionRate = $merchant->default_commission_rate;
                
                // create new affiliate
                $this->affiliateService->register($merchant, $data['customer_email'], $data['customer_name'], $commissionRate);

                // Log the order and commission
                Order::create([
                    'subtotal' => $data['subtotal_price'],
                    'affiliate_id' => $affiliate->id,
                    'merchant_id' => $merchant->id,
                    'commission_owed' => $data['subtotal_price'] * $affiliate->commission_rate,
                    'external_order_id' => $data['order_id'],
                ]);

            } catch (\Exception $e) {
                // Handle exception if needed
                \Log::error('Error creating affiliate: ' . $e->getMessage());
            }
        }
    }
}
