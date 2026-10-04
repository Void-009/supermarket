<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PaymentController extends Controller
{

    public function initPaystack(Order $order)
    {
        abort_unless($order->user_id === Auth::id(), 403);

        $reference = 'PSK_' . strtoupper(Str::random(16));

        $payment = Payment::create([
            'order_id' => $order->id,
            'gateway' => 'paystack',
            'reference' => $reference,
            'amount' => $order->total_amount,
            'status' => 'pending',
        ]);

        $callbackUrl = route('payment.paystack.callback', [
            'reference' => $reference,
            'order_id' => $order->id,
        ]);

        return redirect($callbackUrl);
    }

    public function paystackCallback(Request $request)
    {
        $reference = $request->query('reference');

        $payment = Payment::where('reference', $reference)->firstOrFail();
        $order = $payment->order;

        // Verify Paystack HMAC signature
        $signature = $request->header('x-paystack-signature');
        if ($signature) {
            $secretKey = config('services.paystack.secret_key');
            $payload = $request->getContent();
            $expectedSignature = hash_hmac('sha512', $payload, $secretKey);
            if (!hash_equals($expectedSignature, $signature)) {
                Log::warning('Paystack HMAC signature mismatch', ['reference' => $reference]);
                abort(403, 'Invalid signature.');
            }
        }

        try {
            $secretKey = config('services.paystack.secret_key');

            $response = Http::withToken($secretKey)
                ->get("https://api.paystack.co/transaction/verify/{$reference}");

            if ($response->successful() && $response->json('data.status') === 'success') {
                $payment->update([
                    'status' => 'success',
                    'gateway_response' => $response->json('data'),
                ]);

                $order->update(['payment_status' => 'verified', 'status' => 'paid']);

                return redirect()->route('order.confirm', $order->id)
                    ->with('success', 'Payment successful!');
            }

            Log::warning('Paystack verification failed', [
                'reference' => $reference,
                'response' => $response->json(),
            ]);

            $payment->update([
                'status' => 'failed',
                'gateway_response' => $response->json('data'),
            ]);

            return redirect()->route('order.confirm', $order->id)
                ->with('error', 'Payment could not be verified. Please contact support if payment was made.');

        } catch (\Exception $e) {
            Log::error('Paystack verification exception', [
                'reference' => $reference,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('order.confirm', $order->id)
                ->with('error', 'Payment verification failed. Please contact support.');
        }
    }

    public function initFlutterwave(Order $order)
    {
        abort_unless($order->user_id === Auth::id(), 403);

        $transactionId = 'FLW_' . strtoupper(Str::random(16));

        $payment = Payment::create([
            'order_id' => $order->id,
            'gateway' => 'flutterwave',
            'reference' => $transactionId,
            'amount' => $order->total_amount,
            'status' => 'pending',
        ]);

        $callbackUrl = route('payment.flutterwave.callback', [
            'transaction_id' => $transactionId,
            'order_id' => $order->id,
        ]);

        return redirect($callbackUrl);
    }

    public function flutterwaveCallback(Request $request)
    {
        $transactionId = $request->query('transaction_id');

        $payment = Payment::where('reference', $transactionId)->firstOrFail();
        $order = $payment->order;

        try {
            $secretKey = config('services.flutterwave.secret_key');

            $response = Http::withToken($secretKey)
                ->get("https://api.flutterwave.com/v3/transactions/verify_by_reference?tx_ref={$transactionId}");

            if ($response->successful() && $response->json('data.status') === 'successful') {
                $payment->update([
                    'status' => 'success',
                    'gateway_response' => $response->json('data'),
                ]);

                $order->update(['status' => 'paid']);

                return redirect()->route('order.confirm', $order->id)
                    ->with('success', 'Payment successful!');
            }

            Log::warning('Flutterwave verification failed', [
                'reference' => $transactionId,
                'response' => $response->json(),
            ]);

            $payment->update([
                'status' => 'failed',
                'gateway_response' => $response->json('data'),
            ]);

            return redirect()->route('order.confirm', $order->id)
                ->with('error', 'Payment could not be verified. Please contact support if payment was made.');

        } catch (\Exception $e) {
            Log::error('Flutterwave verification exception', [
                'reference' => $transactionId,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('order.confirm', $order->id)
                ->with('error', 'Payment verification failed. Please contact support.');
        }
    }
}
