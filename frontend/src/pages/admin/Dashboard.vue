<template>
  <q-page class="q-pa-md" style="max-width: 1200px; margin: 0 auto">

    <!-- Cabeçalho -->
    <div class="row items-center justify-between q-mb-lg">
      <div>
        <div class="text-h5 text-weight-bold">Dashboard</div>
        <div class="text-caption text-grey">
          Mês de {{ mesAtual }} · atualizado agora há pouco
        </div>
      </div>
      <q-btn
        flat
        round
        icon="refresh"
        color="primary"
        :loading="dashboard.loading"
        @click="dashboard.fetchMetrics()"
      />
    </div>

    <!-- Carregando -->
    <div v-if="dashboard.loading && !dashboard.metrics" class="flex flex-center q-pa-xl">
      <q-spinner size="48px" color="primary" />
    </div>

    <!-- Erro -->
    <q-banner
      v-else-if="dashboard.error"
      rounded
      dense
      class="bg-negative text-white q-mb-md"
    >
      {{ dashboard.error }}
    </q-banner>

    <template v-else-if="dashboard.metrics">
      <!-- ── Linha 1: Cards de métricas ── -->
      <div class="row q-col-gutter-md q-mb-lg">

        <div class="col-12 col-sm-6 col-md-3">
          <q-card flat bordered class="metric-card">
            <q-card-section>
              <div class="row items-center q-gutter-sm q-mb-xs">
                <q-icon name="today" color="primary" size="20px" />
                <div class="text-caption text-grey">Faturamento Hoje</div>
              </div>
              <div class="text-h5 text-weight-bold text-primary">
                {{ formatCurrency(dashboard.metrics.faturamento_hoje) }}
              </div>
            </q-card-section>
          </q-card>
        </div>

        <div class="col-12 col-sm-6 col-md-3">
          <q-card flat bordered class="metric-card">
            <q-card-section>
              <div class="row items-center q-gutter-sm q-mb-xs">
                <q-icon name="calendar_month" color="green" size="20px" />
                <div class="text-caption text-grey">Faturamento do Mês</div>
              </div>
              <div class="text-h5 text-weight-bold text-green">
                {{ formatCurrency(dashboard.metrics.faturamento_mes) }}
              </div>
            </q-card-section>
          </q-card>
        </div>

        <div class="col-12 col-sm-6 col-md-3">
          <q-card flat bordered class="metric-card">
            <q-card-section>
              <div class="row items-center q-gutter-sm q-mb-xs">
                <q-icon name="receipt_long" color="orange" size="20px" />
                <div class="text-caption text-grey">Ticket Médio</div>
              </div>
              <div class="text-h5 text-weight-bold text-orange">
                {{ formatCurrency(dashboard.metrics.ticket_medio) }}
              </div>
            </q-card-section>
          </q-card>
        </div>

        <div class="col-12 col-sm-6 col-md-3">
          <q-card flat bordered class="metric-card">
            <q-card-section>
              <div class="row items-center q-gutter-sm q-mb-xs">
                <q-icon name="person_off" color="negative" size="20px" />
                <div class="text-caption text-grey">Taxa de No-show</div>
              </div>
              <div class="text-h5 text-weight-bold text-negative">
                {{ dashboard.metrics.taxa_no_show }}%
              </div>
            </q-card-section>
          </q-card>
        </div>

      </div>

      <!-- ── Linha 2: Gráfico de serviços + Ranking funcionários ── -->
      <div class="row q-col-gutter-md">

        <!-- Top 5 Serviços mais rentáveis -->
        <div class="col-12 col-md-7">
          <q-card flat bordered>
            <q-card-section>
              <div class="text-subtitle1 text-weight-medium q-mb-md">
                Top 5 Serviços — Receita no Mês
              </div>

              <div v-if="dashboard.metrics.servicos_rentaveis.length === 0" class="text-grey text-center q-pa-md">
                Nenhum serviço concluído este mês.
              </div>

              <div v-else class="q-gutter-y-md">
                <div
                  v-for="(service, i) in dashboard.metrics.servicos_rentaveis"
                  :key="service.id"
                >
                  <div class="row items-center q-gutter-x-sm q-mb-xs">
                    <q-badge
                      :color="rankColor(i)"
                      rounded
                      class="text-weight-bold"
                      style="min-width: 22px; text-align: center"
                    >
                      {{ i + 1 }}
                    </q-badge>
                    <div class="col text-subtitle2 text-truncate">{{ service.name }}</div>
                    <div class="text-caption text-grey">{{ service.quantidade }}x</div>
                    <div class="text-caption text-weight-bold" style="min-width: 80px; text-align: right">
                      {{ formatCurrency(service.receita) }}
                    </div>
                  </div>
                  <q-linear-progress
                    :value="service.receita / maxReceita"
                    :color="rankColor(i)"
                    rounded
                    style="height: 10px"
                  />
                </div>
              </div>
            </q-card-section>
          </q-card>
        </div>

        <!-- Ranking de funcionários -->
        <div class="col-12 col-md-5">
          <q-card flat bordered style="height: 100%">
            <q-card-section>
              <div class="text-subtitle1 text-weight-medium q-mb-md">
                Ranking de Funcionários
              </div>

              <div v-if="dashboard.metrics.ranking_funcionarios.length === 0" class="text-grey text-center q-pa-md">
                Nenhum atendimento concluído este mês.
              </div>

              <q-list v-else dense separator>
                <q-item
                  v-for="(emp, i) in dashboard.metrics.ranking_funcionarios"
                  :key="emp.id"
                >
                  <q-item-section avatar>
                    <q-avatar
                      :color="rankColor(i)"
                      text-color="white"
                      size="32px"
                      class="text-weight-bold"
                    >
                      {{ i + 1 }}
                    </q-avatar>
                  </q-item-section>
                  <q-item-section>
                    <q-item-label>{{ emp.name }}</q-item-label>
                    <q-item-label caption>
                      {{ emp.realizados }} atendimento{{ emp.realizados !== 1 ? 's' : '' }} concluído{{ emp.realizados !== 1 ? 's' : '' }}
                    </q-item-label>
                  </q-item-section>
                  <q-item-section side>
                    <q-chip
                      dense
                      :color="rankColor(i)"
                      text-color="white"
                      class="text-weight-bold"
                    >
                      {{ emp.realizados }}
                    </q-chip>
                  </q-item-section>
                </q-item>
              </q-list>
            </q-card-section>
          </q-card>
        </div>

      </div>
    </template>

  </q-page>
</template>

<script setup>
import { computed, onMounted } from 'vue'
import { useDashboardStore } from 'src/stores/dashboard'

const dashboard = useDashboardStore()

onMounted(() => {
  dashboard.fetchMetrics()
})

const mesAtual = computed(() => {
  return new Date().toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' })
})

const maxReceita = computed(() => {
  const list = dashboard.metrics?.servicos_rentaveis ?? []
  if (list.length === 0) return 1
  return Math.max(...list.map((s) => s.receita)) || 1
})

const RANK_COLORS = ['primary', 'amber-8', 'orange', 'deep-purple-4', 'teal']

function rankColor(index) {
  return RANK_COLORS[index] ?? 'grey-6'
}

function formatCurrency(value) {
  return Number(value ?? 0).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}
</script>

<style scoped>
.metric-card {
  transition: box-shadow 0.2s;
}
.metric-card:hover {
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
}
</style>
