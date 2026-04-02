<?php

namespace App\Jobs;

use App\Models\Company;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Carbon;

class CheckSubscriptionExpirationJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        $today   = Carbon::today();
        $in7Days = Carbon::today()->addDays(7)->endOfDay();

        // Marca empresas com assinatura expirando nos próximos 7 dias
        Company::query()
            ->whereIn('plan', ['basic', 'advanced'])
            ->whereBetween('subscription_ends_at', [$today, $in7Days])
            ->update(['subscription_expiring_soon' => true]);

        // Remove a flag de empresas que já não estão mais na janela de alerta
        Company::query()
            ->where('subscription_expiring_soon', true)
            ->where(function ($query) use ($today, $in7Days): void {
                $query
                    ->where('plan', 'free')
                    ->orWhereNull('subscription_ends_at')
                    ->orWhere('subscription_ends_at', '<', $today)
                    ->orWhere('subscription_ends_at', '>', $in7Days);
            })
            ->update(['subscription_expiring_soon' => false]);
    }
}
