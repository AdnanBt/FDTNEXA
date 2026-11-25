<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PaymentController extends Controller
{
    // 1. Buy Tool 1 for $1
    public function buyFieldTool(Request $request)
    {
        return $request->user()->checkoutCharge(100, 'Field Checklist Tool - Lifetime Access', 1, [
            'success_url' => route('field-tool.index') . '?payment=success',
            'cancel_url' => route('pricing') . '?payment=cancelled',
        ]);
    }

    // 2. Subscribe to Tool 2 (Monthly)
    public function subscribeMonthly(Request $request)
    {
        // PASTE YOUR STRIPE $5/MONTH PRICE ID HERE
        $priceId = 'price_1XXXXXXXXXXXXXX'; 
        
        return $request->user()->newSubscription('pro', $priceId)->checkout([
            'success_url' => route('bulk.index') . '?payment=success',
            'cancel_url' => route('pricing') . '?payment=cancelled',
        ]);
    }

    // 3. Subscribe to Tool 2 (Yearly)
    public function subscribeYearly(Request $request)
    {
        // PASTE YOUR STRIPE $50/YEAR PRICE ID HERE
        $priceId = 'price_1YYYYYYYYYYYYYY'; 
        
        return $request->user()->newSubscription('pro', $priceId)->checkout([
            'success_url' => route('bulk.index') . '?payment=success',
            'cancel_url' => route('pricing') . '?payment=cancelled',
        ]);
    }
}