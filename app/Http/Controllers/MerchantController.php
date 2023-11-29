<?php

namespace App\Http\Controllers;

use App\Models\Merchant;
use App\Services\MerchantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class MerchantController extends Controller
{
    public function __construct(
        MerchantService $merchantService
    ) {}

    /**
     * Useful order statistics for the merchant API.
     * 
     * @param Request $request Will include a from and to date
     * @return JsonResponse Should be in the form {count: total number of orders in range, commission_owed: amount of unpaid commissions for orders with an affiliate, revenue: sum order subtotals}
     */
    public function orderStats(Request $request): JsonResponse
    {
        // TODO: Complete this method
        $response = [];
        
        $from = Carbon::parse($request->input('from'));
        $to = Carbon::parse($request->input('to'));

        $orders = Merchant::find(auth()->user()->id)
            ->orders()
            ->whereBetween('created_at', [$from, $to]);

        $commission = \DB::table('orders')
            ->selectRaw('SUM(CASE WHEN affiliate_id IS NOT NULL THEN commission_owed ELSE 0 END) as commissions_owed')
            ->whereBetween('created_at', [$from, $to])
            ->value('commissions_owed');

        if(!empty($orders)){
            $response = [
                'count' => $orders->count(),
                'revenue' => $orders->sum('subtotal'),
                'commissions_owed' => $commission,
            ];    
        }
        
        return response()->json($response);
    
    }
}
