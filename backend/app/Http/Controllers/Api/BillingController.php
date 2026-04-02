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
