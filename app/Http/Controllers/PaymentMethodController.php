<?php

namespace App\Http\Controllers;

use App\Models\PaymentMethod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentMethodController extends Controller
{   
    public function index()
    {
        $methods = PaymentMethod::where('user_id', Auth::id())->get();

        return response()->json([
            'success' => true,
            'message' => __('Payment methods retrieved successfully'),
            'data' => $methods
        ], 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'bank_name'     => 'required|string|max:64',
            'account_name'  => 'required|string|max:64',
            'account_number'=> 'required|numeric|digits_between:1,20',
        ]);

        $paymentMethod = PaymentMethod::create([
            'user_id'=> Auth::id(),
            'bank_name'=> $validated['bank_name'],
            'account_name'=> $validated['account_name'],
            'account_number'=> $validated['account_number']
        ]);

        return response()->json([
            'success' => true,
            'message' => __('Payment method created successfully'),
            'data' => $paymentMethod
        ],201);
    }

    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'bank_name'      => 'required|string|max:64',
            'account_name'   => 'required|string|max:64',
            'account_number' => 'required|numeric|digits_between:1,20',
        ]);

        $paymentMethod = PaymentMethod::where('payment_method_id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $paymentMethod->update($validated);

        return response()->json([
            'success' => true,
            'message' => __('Payment method updated successfully'),
            'data'    => $paymentMethod
        ], 200);
    }

    public function destroy($id)
    {
        $paymentMethod = PaymentMethod::where('payment_method_id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();
        
        $paymentMethod->delete();

        return response()->json([
            'success' => true,
            'message' => __('Payment method deleted successfully')
        ], 200);
    }
}
