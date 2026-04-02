<template>
  <q-page class="q-pa-md" style="max-width: 1200px; margin: 0 auto">

    <!-- Cabeçalho -->
    <div class="row items-center justify-between q-mb-lg">
      <div>
        <div class="text-h5 text-weight-bold">Assinatura</div>
        <div class="text-caption text-grey">Gerencie seu plano e uso da plataforma</div>
      </div>
      <q-btn flat round icon="refresh" color="primary" :loading="store.loading" @click="store.fetchInfo()" />
    </div>

    <!-- Carregando -->
    <div v-if="store.loading && !store.info" class="flex flex-center q-pa-xl">
      <q-spinner size="48px" color="primary" />
    </div>

    <!-- Erro -->
    <q-banner v-else-if="store.error" rounded dense class="bg-negative text-white q-mb-md">
      {{ store.error }}
    </q-banner>

    <template v-else>
      <!-- Resumo do plano atual -->
      <q-card flat bordered class="q-mb-lg">
        <q-card-section>
          <div class="row items-center justify-between q-mb-md">
            <div>
              <div class="text-subtitle1 text-weight-bold">Plano atual</div>
              <div class="text-caption text-grey">Seu plano ativo e uso atual</div>
            </div>
            <q-badge
              :color="planColor(currentPlan)"
              :label="planLabel(currentPlan)"
              class="text-subtitle2 q-pa-sm"
              rounded
            />
          </div>

          <div class="row q-col-gutter-md">
            <div class="col-12 col-sm-4">
              <div class="text-caption text-grey q-mb-xs">Funcionários</div>
              <div class="row items-center q-gutter-sm">
                <div class="text-h6 text-weight-bold">{{ activeEmployees }} / {{ planLimit }}</div>
                <q-badge
                  v-if="activeEmployees >= planLimit"
                  color="negative"
                  label="Limite atingido"
                  dense
                />
              </div>
              <q-linear-progress
                :value="planLimit > 0 ? activeEmployees / planLimit : 0"
                :color="activeEmployees >= planLimit ? 'negative' : 'primary'"
                rounded
                class="q-mt-sm"
                style="height: 8px"
              />
            </div>

            <div class="col-12 col-sm-4">
              <div class="text-caption text-grey q-mb-xs">Próximo vencimento</div>
              <div class="text-h6 text-weight-bold">
                {{ store.info?.next_billing_date ? formatDate(store.info.next_billing_date) : '—' }}
              </div>
            </div>

            <div class="col-12 col-sm-4">
              <div class="text-caption text-grey q-mb-xs">Valor mensal</div>
              <div class="text-h6 text-weight-bold text-primary">
                {{ formatCurrency(PLANS[currentPlan]?.price ?? 0) }}
              </div>
            </div>
          </div>
        </q-card-section>
      </q-card>

      <!-- Cards de planos -->
      <div class="text-subtitle1 text-weight-bold q-mb-md">Planos disponíveis</div>

      <div class="row q-col-gutter-md">
        <div
          v-for="plan in PLANS"
          :key="plan.key"
          class="col-12 col-sm-4"
        >
          <q-card
            flat
            :bordered="currentPlan !== plan.key"
            :class="currentPlan === plan.key ? 'plan-card-active' : 'plan-card'"
          >
            <q-card-section class="text-center q-pb-sm">
              <q-icon :name="plan.icon" :color="plan.color" size="36px" />
              <div class="text-subtitle1 text-weight-bold q-mt-sm">{{ plan.label }}</div>
              <div class="text-h5 text-weight-bold q-mt-xs" :class="`text-${plan.color}`">
                {{ formatCurrency(plan.price) }}
                <span class="text-caption text-grey text-weight-regular">/mês</span>
              </div>
            </q-card-section>

            <q-separator />

            <q-card-section class="q-py-sm">
              <q-list dense>
                <q-item v-for="feature in plan.features" :key="feature" dense>
                  <q-item-section avatar>
                    <q-icon name="check" color="positive" size="16px" />
                  </q-item-section>
                  <q-item-section class="text-caption">{{ feature }}</q-item-section>
                </q-item>
              </q-list>
            </q-card-section>

            <q-card-actions class="q-pa-md q-pt-none">
              <q-btn
                v-if="currentPlan !== plan.key"
                :label="isUpgrade(plan.key) ? 'Fazer upgrade' : 'Fazer downgrade'"
                :color="isUpgrade(plan.key) ? plan.color : 'grey-7'"
                :outline="!isUpgrade(plan.key)"
                unelevated
                class="full-width"
                :loading="store.upgrading"
                @click="confirmChangePlan(plan)"
              />
              <q-btn
                v-else
                label="Plano atual"
                flat
                disable
                class="full-width"
                color="grey-6"
              />
            </q-card-actions>
          </q-card>
        </div>
      </div>
    </template>

  </q-page>
</template>

<script setup>
import { computed, onMounted } from "vue"
import { useQuasar } from "quasar"
import { useBillingStore } from "src/stores/billing"
import { useEmployeesStore } from "src/stores/employees"
import { useAuthStore } from "src/stores/auth"

const $q = useQuasar()
const store = useBillingStore()
const employeesStore = useEmployeesStore()
const auth = useAuthStore()

const PLANS = [
  {
    key: "free",
    label: "Gratuito",
    price: 0,
    limit: 1,
    color: "grey-7",
    icon: "star_border",
    features: [
      "1 funcionário",
      "Agendamentos ilimitados",
      "Página de reserva pública",
      "Suporte por e-mail",
    ],
  },
  {
    key: "basic",
    label: "Básico",
    price: 49,
    limit: 3,
    color: "primary",
    icon: "star_half",
    features: [
      "Até 3 funcionários",
      "Agendamentos ilimitados",
      "Página de reserva pública",
      "Relatórios básicos",
      "Suporte prioritário",
    ],
  },
  {
    key: "advanced",
    label: "Avançado",
    price: 99,
    limit: 10,
    color: "amber-8",
    icon: "star",
    features: [
      "Até 10 funcionários",
      "Agendamentos ilimitados",
      "Página de reserva pública",
      "Relatórios avançados",
      "Dashboard completo",
      "Suporte prioritário 24h",
    ],
  },
]

const PLAN_ORDER = { free: 0, basic: 1, advanced: 2 }

const currentPlan = computed(() => store.info?.plan ?? auth.user?.company?.plan ?? "free")

const planLimit = computed(() => PLANS.find((p) => p.key === currentPlan.value)?.limit ?? 1)

const activeEmployees = computed(() => employeesStore.employees.filter((e) => e.is_active).length)

function isUpgrade(planKey) {
  return (PLAN_ORDER[planKey] ?? 0) > (PLAN_ORDER[currentPlan.value] ?? 0)
}

function planLabel(key) {
  return PLANS.find((p) => p.key === key)?.label ?? key
}

function planColor(key) {
  return PLANS.find((p) => p.key === key)?.color ?? "grey"
}

function formatCurrency(value) {
  if (value === 0) return "Grátis"
  return Number(value).toLocaleString("pt-BR", { style: "currency", currency: "BRL" })
}

function formatDate(iso) {
  return new Date(iso).toLocaleDateString("pt-BR", { day: "2-digit", month: "long", year: "numeric" })
}

function confirmChangePlan(plan) {
  const action = isUpgrade(plan.key) ? "upgrade" : "downgrade"
  $q.dialog({
    title: action === "upgrade" ? "Fazer upgrade" : "Fazer downgrade",
    message: `Deseja mudar para o plano <strong>${plan.label}</strong> (${formatCurrency(plan.price)}/mês)?`,
    html: true,
    cancel: true,
    persistent: true,
    ok: { label: "Confirmar", color: plan.color, unelevated: true },
  }).onOk(async () => {
    try {
      await store.changePlan(plan.key)
      $q.notify({ type: "positive", message: `Plano alterado para ${plan.label} com sucesso!` })
    } catch {
      $q.notify({ type: "negative", message: "Erro ao alterar plano. Tente novamente." })
    }
  })
}

onMounted(async () => {
  await Promise.all([store.fetchInfo(), employeesStore.fetchEmployees()])
})
</script>

<style scoped>
.plan-card {
  transition: box-shadow 0.2s;
  height: 100%;
}
.plan-card:hover {
  box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
}
.plan-card-active {
  border: 2px solid var(--q-primary);
  height: 100%;
}
</style>
