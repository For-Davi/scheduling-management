<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Webhook;

class StripeWebhookController extends Controller
{
    public function __construct(private BillingService $billing) {}

    /**
     * POST /api/webhooks/stripe
     * Recebe eventos do Stripe, valida a assinatura e despacha para o serviço.
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $sig     = $request->header('Stripe-Signature');
        $secret  = config('cashier.webhook.secret');

        try {
            $event = Webhook::constructEvent($payload, $sig, $secret);
        } catch (\UnexpectedValueException) {
            return response()->json(['error' => 'Payload inválido.'], 400);
        } catch (SignatureVerificationException) {
            return response()->json(['error' => 'Assinatura inválida.'], 400);
        }

        $data = $event->data->object;

        match ($event->type) {
            'invoice.payment_succeeded'     => $this->billing->handlePaymentSucceeded($data),
            'customer.subscription.deleted' => $this->billing->handleSubscriptionDeleted($data),
            'invoice.payment_failed'        => $this->billing->handlePaymentFailed($data),
            default                         => null,
        };

        return response()->json(['received' => true]);
    }
}
