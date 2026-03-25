<?php

namespace App\Http\Controllers;

use App\Mail\PaymentReceivedMail;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Setting;
use App\Services\PayuService;
use App\Services\SmsService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PayuWebhookController extends Controller
{
    /**
     * Handle incoming IPN (Instant Payment Notification) from PayU.
     *
     * Route is CSRF-exempt (see bootstrap/app.php).
     * PayU docs: https://developers.payu.com/en/restapi.html#notifications
     */
    public function notify(Request $request): Response
    {
        $body            = $request->getContent();
        $signatureHeader = $request->header('OpenPayU-Signature', '');

        $payu = new PayuService();

        if ($signatureHeader && ! $payu->verifySignature($signatureHeader, $body)) {
            return response('Invalid signature', 400);
        }

        $payload = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return response('Invalid JSON payload', 400);
        }

        $order  = $payload['order'] ?? null;
        $status = $order['status'] ?? null;

        if (! $order || ! $status) {
            return response('OK', 200);
        }

        // extOrderId format: inv-{invoice_id}-{timestamp}
        $extOrderId = $order['extOrderId'] ?? '';
        $invoiceId  = null;
        if (preg_match('/^inv-(\d+)-/', $extOrderId, $m)) {
            $invoiceId = (int) $m[1];
        }

        if (! $invoiceId) {
            return response('OK', 200);
        }

        $invoice = Invoice::find($invoiceId);
        if (! $invoice) {
            Log::warning("PayU IPN: Invoice #{$invoiceId} not found");
            return response('OK', 200);
        }

        if ($status === 'COMPLETED') {
            $amount   = isset($order['totalAmount']) ? $order['totalAmount'] / 100 : 0;
            $currency = strtoupper($order['currencyCode'] ?? 'GBP');

            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'amount'     => $amount,
                'currency'   => $currency,
                'method'     => 'payu',
                'status'     => 'completed',
                'reference'  => $order['orderId'] ?? $extOrderId,
                'paid_at'    => now(),
            ]);

            $invoice->recalculate();

            if ($invoice->fresh()->amount_due <= 0) {
                $invoice->update(['status' => 'paid', 'paid_at' => now()]);
            }

            // Send confirmation email + SMS
            try {
                $clientEmail = $invoice->client?->primary_contact_email;
                if ($clientEmail) {
                    Mail::to($clientEmail)->send(new PaymentReceivedMail($payment));
                }

                $phone = $invoice->client?->primary_contact_phone;
                if ($phone && Setting::get('twilio_enabled')) {
                    $sms = new SmsService();
                    $amount   = strtoupper($payment->currency) . ' ' . number_format($payment->amount, 2);
                    $sms->send($phone, "WebsiteExpert: Payment of {$amount} received for invoice {$invoice->number}. Thank you!");
                }
            } catch (\Throwable $e) {
                Log::error('PayU: Failed to send payment notification: ' . $e->getMessage());
            }

            Log::info("PayU IPN: Order {$extOrderId} COMPLETED — Invoice #{$invoice->id} paid");
        } elseif ($status === 'CANCELED') {
            Log::info("PayU IPN: Order {$extOrderId} CANCELED — Invoice #{$invoice->id}");
        } elseif ($status === 'PENDING' || $status === 'WAITING_FOR_CONFIRMATION') {
            Log::info("PayU IPN: Order {$extOrderId} status={$status} — Invoice #{$invoice->id}");
        }

        return response('OK', 200);
    }
}
