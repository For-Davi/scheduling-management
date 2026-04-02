<?php

namespace App\Services;

use App\Models\Appointment;
use App\Models\Company;
use Carbon\Carbon;

class DashboardService
{
    /**
     * Retorna todas as métricas do dashboard para a empresa.
     * Regra 5: faturamento e ticket médio só consideram status = 'completed'.
     */
    public function getMetrics(Company $company): array
    {
        $now   = Carbon::now();
        $today = $now->toDateString();
        $month = $now->month;
        $year  = $now->year;

        return [
            'faturamento_hoje'     => $this->faturamentoHoje($company->id, $today),
            'faturamento_mes'      => $this->faturamentoMes($company->id, $month, $year),
            'ticket_medio'         => $this->ticketMedio($company->id, $month, $year),
            'servicos_rentaveis'   => $this->servicosRentaveis($company->id, $month, $year),
            'ranking_funcionarios' => $this->rankingFuncionarios($company->id, $month, $year),
            'taxa_no_show'         => $this->taxaNoShow($company->id, $month, $year),
        ];
    }

    // -------------------------------------------------------------------------

    private function faturamentoHoje(int $companyId, string $today): float
    {
        return (float) Appointment::where('appointments.company_id', $companyId)
            ->where('appointments.status', 'completed')
            ->whereDate('appointments.starts_at', $today)
            ->join('services', 'appointments.service_id', '=', 'services.id')
            ->sum('services.price');
    }

    private function faturamentoMes(int $companyId, int $month, int $year): float
    {
        return (float) Appointment::where('appointments.company_id', $companyId)
            ->where('appointments.status', 'completed')
            ->whereMonth('appointments.starts_at', $month)
            ->whereYear('appointments.starts_at', $year)
            ->join('services', 'appointments.service_id', '=', 'services.id')
            ->sum('services.price');
    }

    private function ticketMedio(int $companyId, int $month, int $year): float
    {
        $count = Appointment::where('company_id', $companyId)
            ->where('status', 'completed')
            ->whereMonth('starts_at', $month)
            ->whereYear('starts_at', $year)
            ->count();

        if ($count === 0) {
            return 0.0;
        }

        $receita = $this->faturamentoMes($companyId, $month, $year);

        return round($receita / $count, 2);
    }

    /** Top 5 serviços por receita no mês (Regra 5: só completed). */
    private function servicosRentaveis(int $companyId, int $month, int $year): array
    {
        return Appointment::where('appointments.company_id', $companyId)
            ->where('appointments.status', 'completed')
            ->whereMonth('appointments.starts_at', $month)
            ->whereYear('appointments.starts_at', $year)
            ->join('services', 'appointments.service_id', '=', 'services.id')
            ->selectRaw('services.id, services.name, SUM(services.price) as receita, COUNT(*) as quantidade')
            ->groupBy('services.id', 'services.name')
            ->orderByDesc('receita')
            ->limit(5)
            ->get()
            ->map(fn($row) => [
                'id'        => $row->id,
                'name'      => $row->name,
                'receita'   => (float) $row->receita,
                'quantidade' => (int) $row->quantidade,
            ])
            ->all();
    }

    /** Ranking de funcionários por agendamentos completed no mês. */
    private function rankingFuncionarios(int $companyId, int $month, int $year): array
    {
        return Appointment::where('appointments.company_id', $companyId)
            ->where('appointments.status', 'completed')
            ->whereMonth('appointments.starts_at', $month)
            ->whereYear('appointments.starts_at', $year)
            ->join('employees', 'appointments.employee_id', '=', 'employees.id')
            ->selectRaw('employees.id, employees.name, COUNT(*) as realizados')
            ->groupBy('employees.id', 'employees.name')
            ->orderByDesc('realizados')
            ->get()
            ->map(fn($row) => [
                'id'        => $row->id,
                'name'      => $row->name,
                'realizados' => (int) $row->realizados,
            ])
            ->all();
    }

    /**
     * Taxa de no-show: % de no_show sobre (completed + no_show) no mês.
     * Exclui cancelled, pois cancelamento não é ausência.
     */
    private function taxaNoShow(int $companyId, int $month, int $year): float
    {
        $totalFinalizado = Appointment::where('company_id', $companyId)
            ->whereIn('status', ['completed', 'no_show'])
            ->whereMonth('starts_at', $month)
            ->whereYear('starts_at', $year)
            ->count();

        if ($totalFinalizado === 0) {
            return 0.0;
        }

        $noShow = Appointment::where('company_id', $companyId)
            ->where('status', 'no_show')
            ->whereMonth('starts_at', $month)
            ->whereYear('starts_at', $year)
            ->count();

        return round(($noShow / $totalFinalizado) * 100, 1);
    }
}
