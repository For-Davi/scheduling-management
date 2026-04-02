<?php

namespace App\Services;

use App\Models\Company;
use Carbon\Carbon;
use Stripe\StripeClient;

class BillingService
{
    private StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('cashier.secret'));
    }

    // -------------------------------------------------------------------------
    // Checkout e Portal
    // -------------------------------------------------------------------------

    /**
     * Cria uma sessão Stripe Checkout para nova assinatura.
     * Se já houver assinatura ativa, redireciona para o Customer Portal.
     */
    public function checkoutUrl(Company $company, string $plan, string $successUrl, string $cancelUrl): string
    {
        if ($company->subscribed('default')) {
            return $company->billingPortalUrl($cancelUrl);
        }

        $priceId = $this->priceId($plan);

        $checkout = $company->newSubscription('default', $priceId)
            ->checkout([
                'success_url' => $successUrl,
                'cancel_url'  => $cancelUrl,
            ]);

        return $checkout->url;
    }

    /**
     * Retorna a URL do Stripe Customer Portal.
     */
    public function portalUrl(Company $company, string $returnUrl): string
    {
        return $company->billingPortalUrl($returnUrl);
    }

    // -------------------------------------------------------------------------
    // Handlers de webhook
    // -------------------------------------------------------------------------

    /**
     * invoice.payment_succeeded → ativa/renova a assinatura.
     */
    public function handlePaymentSucceeded(object $invoice): void
    {
        if (empty($invoice->subscription)) {
            return;
        }

        $company = Company::where('stripe_id', $invoice->customer)->first();
        if (!$company) {
            return;
        }

        $subscription = $this->stripe->subscriptions->retrieve($invoice->subscription);
        $priceId      = $subscription->items->data[0]->price->id ?? null;
        $plan         = $this->planFromPrice($priceId);
        $endsAt       = Carbon::createFromTimestamp($subscription->current_period_end);

        $company->update([
            'plan'                       => $plan,
            'subscription_ends_at'       => $endsAt,
            'subscription_expiring_soon' => false,
        ]);
    }

    /**
     * customer.subscription.deleted → downgrade forçado para Free (Regra 7).
     */
    public function handleSubscriptionDeleted(object $subscription): void
    {
        $company = Company::where('stripe_id', $subscription->customer)->first();
        if (!$company) {
            return;
        }

        $this->downgradeToFree($company);
    }

    /**
     * invoice.payment_failed → downgrade forçado para Free (Regra 7).
     */
    public function handlePaymentFailed(object $invoice): void
    {
        if (empty($invoice->subscription)) {
            return;
        }

        $company = Company::where('stripe_id', $invoice->customer)->first();
        if (!$company) {
            return;
        }

        $this->downgradeToFree($company);
    }

    // -------------------------------------------------------------------------
    // Helpers privados
    // -------------------------------------------------------------------------

    /**
     * Regra 7: downgrade para Free, bloqueando excedentes mais recentes.
     * Nunca deleta registros — apenas seta is_active = false.
     */
    private function downgradeToFree(Company $company): void
    {
        $limits = Company::PLAN_LIMITS['free'];

        // Bloqueia funcionários excedentes (mantém os mais antigos dentro do limite)
        $company->employees()
            ->where('is_active', true)
            ->orderBy('created_at', 'asc')
            ->get()
            ->slice($limits['employees'])   // excedentes mais recentes
            ->each(fn($e) => $e->update(['is_active' => false]));

        // Bloqueia serviços excedentes
        $company->services()
            ->where('is_active', true)
            ->orderBy('created_at', 'asc')
            ->get()
            ->slice($limits['services'])
            ->each(fn($s) => $s->update(['is_active' => false]));

        $company->update([
            'plan'                       => 'free',
            'subscription_ends_at'       => null,
            'subscription_expiring_soon' => false,
        ]);
    }

    /**
     * Mapeia price_id Stripe → plano interno.
     */
    private function planFromPrice(?string $priceId): string
    {
        if ($priceId && $priceId === env('STRIPE_PRICE_BASIC'))    return 'basic';
        if ($priceId && $priceId === env('STRIPE_PRICE_ADVANCED')) return 'advanced';
        return 'free';
    }

    /**
     * Retorna o Stripe Price ID para o plano solicitado.
     */
    private function priceId(string $plan): string
    {
        return match ($plan) {
            'basic'    => env('STRIPE_PRICE_BASIC'),
            'advanced' => env('STRIPE_PRICE_ADVANCED'),
        };
    }
}
