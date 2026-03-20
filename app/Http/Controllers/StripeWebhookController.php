<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    /**
     * Handle incoming Stripe webhook events.
     *
     * Requires STRIPE_WEBHOOK_SECRET in .env.
     * Route should be excluded from CSRF middleware.
     */
    public function handle(Request $request): Response
    {
        $payload   = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $secret    = config('services.stripe.webhook_secret');

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $secret);
        } catch (SignatureVerificationException $e) {
            Log::warning('Stripe webhook signature verification failed: ' . $e->getMessage());
            return response('Invalid signature', 400);
        } catch (\UnexpectedValueException $e) {
            Log::warning('Stripe webhook invalid payload: ' . $e->getMessage());
            return response('Invalid payload', 400);
        }

        match ($event->type) {
            'payment_intent.succeeded'              => $this->handlePaymentIntentSucceeded($event->data->object),
            'payment_intent.payment_failed'         => $this->handlePaymentIntentFailed($event->data->object),
            'invoice.payment_succeeded'             => $this->handleInvoicePaymentSucceeded($event->data->object),
            'invoice.payment_failed'                => $this->handleInvoicePaymentFailed($event->data->object),
            'checkout.session.completed'            => $this->handleCheckoutSessionCompleted($event->data->object),
            default => Log::info('Stripe webhook received unhandled event: ' . $event->type),
        };

        return response('OK', 200);
    }

    private function handlePaymentIntentSucceeded(\Stripe\PaymentIntent $paymentIntent): void
    {
        $payment = Payment::where('stripe_payment_intent_id', $paymentIntent->id)->first();

        if (! $payment) {
            return;
        }

        $payment->update([
            'status'  => 'completed',
            'paid_at' => now(),
        ]);

        // Mark invoice as paid if total amount paid covers the total
        $invoice = $payment->invoice;
        if ($invoice) {
            $invoice->recalculate();
            if ($invoice->fresh()->amount_due <= 0) {
                $invoice->update(['status' => 'paid']);
            }
        }

        Log::info("Stripe: PaymentIntent {$paymentIntent->id} succeeded — Payment #{$payment->id}");
    }

    private function handlePaymentIntentFailed(\Stripe\PaymentIntent $paymentIntent): void
    {
        $payment = Payment::where('stripe_payment_intent_id', $paymentIntent->id)->first();

        if (! $payment) {
            return;
        }

        $payment->update(['status' => 'failed']);

        Log::warning("Stripe: PaymentIntent {$paymentIntent->id} failed — Payment #{$payment->id}");
    }

    private function handleInvoicePaymentSucceeded(\Stripe\Invoice $stripeInvoice): void
    {
        if (! $stripeInvoice->metadata->invoice_id ?? null) {
            return;
        }

        $invoice = Invoice::find($stripeInvoice->metadata->invoice_id);
        if ($invoice) {
            $invoice->update(['status' => 'paid']);
            Log::info("Stripe: Invoice #{$invoice->id} marked as paid via Stripe Invoice {$stripeInvoice->id}");
        }
    }

    private function handleInvoicePaymentFailed(\Stripe\Invoice $stripeInvoice): void
    {
        if (! ($stripeInvoice->metadata->invoice_id ?? null)) {
            return;
        }

        $invoice = Invoice::find($stripeInvoice->metadata->invoice_id);
        if ($invoice) {
            $invoice->update(['status' => 'overdue']);
            Log::warning("Stripe: Invoice #{$invoice->id} payment failed — marked overdue");
        }
    }

    private function handleCheckoutSessionCompleted(\Stripe\Checkout\Session $session): void
    {
        $invoiceId = $session->metadata->invoice_id ?? null;
        if (! $invoiceId) {
            return;
        }

        $invoice = Invoice::find($invoiceId);
        if (! $invoice) {
            return;
        }

        Payment::create([
            'invoice_id'                => $invoice->id,
            'amount'                    => ($session->amount_total ?? 0) / 100,
            'currency'                  => strtoupper($session->currency ?? 'gbp'),
            'method'                    => 'stripe',
            'status'                    => 'completed',
            'stripe_payment_intent_id'  => $session->payment_intent,
            'reference'                 => $session->id,
            'paid_at'                   => now(),
        ]);

        $invoice->update(['status' => 'paid']);

        Log::info("Stripe: Checkout session {$session->id} completed — Invoice #{$invoice->id} paid");
    }
}
