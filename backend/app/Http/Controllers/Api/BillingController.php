<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\BillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function __construct(private BillingService $billing) {}

    /**
     * GET /api/billing
     * Retorna informações da assinatura atual da empresa.
     */
    public function info(Request $request): JsonResponse
    {
        $company = $request->user()->company;

        return response()->json([
            'plan'             => $company->plan,
            'next_billing_date' => $company->subscription_ends_at?->toIso8601String(),
            'expiring_soon'    => $company->subscription_expiring_soon,
            'active_employees' => $company->activeEmployees()->count(),
            'plan_limits'      => \App\Models\Company::PLAN_LIMITS[$company->plan] ?? null,
        ]);
    }

    /**
     * POST /api/billing/plan
     * Muda o plano da empresa (free → paid: checkout; paid → paid: swap; paid → free: cancela).
     */
    public function changePlan(Request $request): JsonResponse
    {
        $request->validate(['plan' => 'required|in:free,basic,advanced']);

        $company = $request->user()->company;
        $newPlan = $request->plan;

        if ($newPlan === 'free') {
            if ($company->subscribed('default')) {
                $company->subscription('default')->cancel();
            }
            $company->update([
                'plan'                 => 'free',
                'subscription_ends_at' => null,
            ]);
        } elseif ($company->subscribed('default')) {
            $priceId = $newPlan === 'basic'
                ? env('STRIPE_PRICE_BASIC')
                : env('STRIPE_PRICE_ADVANCED');

            $company->subscription('default')->swap($priceId);
            $company->update(['plan' => $newPlan]);
        } else {
            $frontendUrl = rtrim(env('FRONTEND_URL', 'http://localhost:9000'), '/');
            $url = $this->billing->checkoutUrl(
                $company,
                $newPlan,
                "{$frontendUrl}/admin/billing?success=1",
                "{$frontendUrl}/admin/billing"
            );

            return response()->json(['checkout_url' => $url]);
        }

        return response()->json([
            'plan'              => $company->plan,
            'next_billing_date' => $company->subscription_ends_at?->toIso8601String(),
        ]);
    }

    /**
     * POST /api/billing/checkout
     * Cria sessão Stripe Checkout para nova assinatura.
     * Retorna a URL para redirecionar o usuário.
     */
    public function checkout(Request $request): JsonResponse
    {
        $request->validate([
            'plan'        => 'required|in:basic,advanced',
            'success_url' => 'nullable|url',
            'cancel_url'  => 'nullable|url',
        ]);

        $frontendUrl = rtrim(env('FRONTEND_URL', 'http://localhost:9000'), '/');
        $successUrl  = $request->success_url ?? "{$frontendUrl}/billing/success";
        $cancelUrl   = $request->cancel_url  ?? "{$frontendUrl}/billing";

        $company = $request->user()->company;
        $url     = $this->billing->checkoutUrl($company, $request->plan, $successUrl, $cancelUrl);

        return response()->json(['url' => $url]);
    }

    /**
     * GET /api/billing/portal
     * Retorna a URL do Stripe Customer Portal.
     */
    public function portal(Request $request): JsonResponse
    {
        $request->validate(['return_url' => 'nullable|url']);

        $frontendUrl = rtrim(env('FRONTEND_URL', 'http://localhost:9000'), '/');
        $returnUrl   = $request->return_url ?? "{$frontendUrl}/billing";

        $company = $request->user()->company;
        $url     = $this->billing->portalUrl($company, $returnUrl);

        return response()->json(['url' => $url]);
    }
}
