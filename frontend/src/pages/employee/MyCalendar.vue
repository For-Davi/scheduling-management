<template>
  <q-page class="q-pa-md" style="max-width: 1000px; margin: 0 auto">

    <!-- Cabeçalho -->
    <div class="row items-center justify-between q-mb-md">
      <div>
        <div class="text-h5 text-weight-bold">Minha Agenda</div>
        <div class="text-caption text-grey">Seus agendamentos do dia</div>
      </div>

      <div class="row items-center q-gutter-sm">
        <q-btn
          flat
          round
          icon="chevron_left"
          color="grey-7"
          dense
          @click="changeDay(-1)"
        />
        <q-btn
          flat
          dense
          color="primary"
          :label="dateLabel"
          icon="calendar_today"
          @click="showDatePicker = true"
        />
        <q-btn
          flat
          round
          icon="chevron_right"
          color="grey-7"
          dense
          @click="changeDay(1)"
        />
        <q-btn flat round icon="refresh" color="primary" :loading="store.myLoading" @click="load" />
      </div>
    </div>

    <!-- Filtro de status -->
    <div class="row q-gutter-sm q-mb-md">
      <q-chip
        v-for="opt in statusOptions"
        :key="opt.value"
        :selected="filters.status === opt.value"
        :color="filters.status === opt.value ? opt.color : 'grey-3'"
        :text-color="filters.status === opt.value ? 'white' : 'grey-8'"
        clickable
        dense
        @click="toggleStatus(opt.value)"
      >
        {{ opt.label }}
      </q-chip>
    </div>

    <!-- Picker de data (popup) -->
    <q-dialog v-model="showDatePicker">
      <q-card>
        <q-date
          v-model="filters.date"
          mask="YYYY-MM-DD"
          @update:model-value="showDatePicker = false; load()"
        />
      </q-card>
    </q-dialog>

    <!-- Carregando -->
    <div v-if="store.myLoading" class="flex flex-center q-pa-xl">
      <q-spinner size="48px" color="primary" />
    </div>

    <!-- Sem agendamentos -->
    <div
      v-else-if="store.myAppointments.length === 0"
      class="flex flex-center column q-pa-xl text-grey"
    >
      <q-icon name="event_available" size="64px" class="q-mb-md text-grey-4" />
      <div class="text-subtitle1">Nenhum agendamento encontrado</div>
      <div class="text-caption">Tente outra data ou remova os filtros.</div>
    </div>

    <!-- Lista de agendamentos -->
    <div v-else class="q-gutter-md">
      <q-card
        v-for="appointment in store.myAppointments"
        :key="appointment.id"
        flat
        bordered
        :class="`appointment-card border-${statusColor(appointment.status)}`"
      >
        <q-card-section class="q-py-sm">
          <div class="row items-center justify-between">
            <!-- Horário + serviço -->
            <div class="row items-center q-gutter-md">
              <div class="text-center" style="min-width: 64px">
                <div class="text-h6 text-weight-bold text-primary">{{ formatTime(appointment.starts_at) }}</div>
                <div class="text-caption text-grey">{{ formatTime(appointment.ends_at) }}</div>
              </div>

              <q-separator vertical />

              <div>
                <div class="text-subtitle2 text-weight-bold">{{ appointment.client_name }}</div>
                <div class="text-caption text-grey">{{ appointment.client_email }}</div>
                <div v-if="appointment.client_phone" class="text-caption text-grey">
                  {{ appointment.client_phone }}
                </div>
              </div>

              <div class="gt-xs">
                <q-chip dense color="blue-grey-1" text-color="blue-grey-8" icon="design_services">
                  {{ appointment.service?.name ?? '—' }}
                </q-chip>
                <div class="text-caption text-grey q-mt-xs">
                  {{ appointment.service?.duration_minutes ?? 0 }} min
                </div>
              </div>
            </div>

            <!-- Status + ações -->
            <div class="row items-center q-gutter-sm">
              <q-badge
                :color="statusColor(appointment.status)"
                :label="statusLabel(appointment.status)"
                rounded
              />

              <q-btn-dropdown
                v-if="hasActions(appointment.status)"
                flat
                dense
                round
                icon="more_vert"
                color="grey-7"
                no-icon-animation
              >
                <q-list dense>
                  <q-item
                    v-if="appointment.status === 'pending'"
                    clickable
                    v-close-popup
                    @click="changeStatus(appointment, 'confirmed')"
                  >
                    <q-item-section avatar><q-icon name="check_circle" color="primary" /></q-item-section>
                    <q-item-section>Confirmar</q-item-section>
                  </q-item>

                  <q-item
                    v-if="['pending', 'confirmed'].includes(appointment.status)"
                    clickable
                    v-close-popup
                    @click="changeStatus(appointment, 'completed')"
                  >
                    <q-item-section avatar><q-icon name="task_alt" color="positive" /></q-item-section>
                    <q-item-section>Concluído</q-item-section>
                  </q-item>

                  <q-item
                    v-if="['pending', 'confirmed'].includes(appointment.status)"
                    clickable
                    v-close-popup
                    @click="changeStatus(appointment, 'no_show')"
                  >
                    <q-item-section avatar><q-icon name="person_off" color="orange" /></q-item-section>
                    <q-item-section>Não compareceu</q-item-section>
                  </q-item>

                  <q-item
                    v-if="['pending', 'confirmed'].includes(appointment.status)"
                    clickable
                    v-close-popup
                    @click="changeStatus(appointment, 'cancelled')"
                  >
                    <q-item-section avatar><q-icon name="cancel" color="negative" /></q-item-section>
                    <q-item-section>Cancelar</q-item-section>
                  </q-item>
                </q-list>
              </q-btn-dropdown>
            </div>
          </div>
        </q-card-section>
      </q-card>
    </div>

  </q-page>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from "vue"
import { useQuasar } from "quasar"
import { useAppointmentsStore } from "src/stores/appointments"

const $q = useQuasar()
const store = useAppointmentsStore()

const showDatePicker = ref(false)

const filters = reactive({
  date: new Date().toISOString().slice(0, 10),
  status: null,
})

const statusOptions = [
  { label: "Pendente", value: "pending", color: "amber-8" },
  { label: "Confirmado", value: "confirmed", color: "primary" },
  { label: "Concluído", value: "completed", color: "positive" },
  { label: "Cancelado", value: "cancelled", color: "grey-6" },
  { label: "Não compareceu", value: "no_show", color: "deep-orange" },
]

const STATUS_LABELS = {
  pending: "Pendente",
  confirmed: "Confirmado",
  completed: "Concluído",
  cancelled: "Cancelado",
  no_show: "Não compareceu",
}

const STATUS_COLORS = {
  pending: "amber-8",
  confirmed: "primary",
  completed: "positive",
  cancelled: "grey-6",
  no_show: "deep-orange",
}

function statusLabel(status) {
  return STATUS_LABELS[status] ?? status
}

function statusColor(status) {
  return STATUS_COLORS[status] ?? "grey"
}

function hasActions(status) {
  return ["pending", "confirmed"].includes(status)
}

function formatTime(iso) {
  if (!iso) return "—"
  return new Date(iso).toLocaleTimeString("pt-BR", { hour: "2-digit", minute: "2-digit" })
}

const dateLabel = computed(() => {
  const d = new Date(filters.date + "T00:00:00")
  const today = new Date()
  today.setHours(0, 0, 0, 0)
  const diff = Math.round((d - today) / 86400000)
  if (diff === 0) return "Hoje"
  if (diff === 1) return "Amanhã"
  if (diff === -1) return "Ontem"
  return d.toLocaleDateString("pt-BR", { weekday: "short", day: "2-digit", month: "short" })
})

function changeDay(delta) {
  const d = new Date(filters.date + "T00:00:00")
  d.setDate(d.getDate() + delta)
  filters.date = d.toISOString().slice(0, 10)
  load()
}

function toggleStatus(value) {
  filters.status = filters.status === value ? null : value
  load()
}

async function load() {
  const params = { date: filters.date }
  if (filters.status) params.status = filters.status
  await store.fetchMyAppointments(params)
}

async function changeStatus(appointment, status) {
  const ACTION_LABELS = {
    confirmed: "confirmar",
    completed: "marcar como concluído",
    no_show: "marcar como não compareceu",
    cancelled: "cancelar",
  }

  $q.dialog({
    title: "Alterar status",
    message: `Deseja ${ACTION_LABELS[status]} o agendamento de <strong>${appointment.client_name}</strong>?`,
    html: true,
    cancel: true,
    persistent: true,
  }).onOk(async () => {
    try {
      await store.updateStatus(appointment.id, status)
      $q.notify({ type: "positive", message: "Status atualizado com sucesso." })
    } catch {
      $q.notify({ type: "negative", message: "Erro ao atualizar status." })
    }
  })
}

onMounted(load)
</script>

<style scoped>
.appointment-card {
  border-left: 4px solid transparent;
  transition: box-shadow 0.2s;
}
.appointment-card:hover {
  box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
}
.border-amber-8    { border-left-color: var(--q-amber-8, #f59e0b); }
.border-primary    { border-left-color: var(--q-primary); }
.border-positive   { border-left-color: var(--q-positive); }
.border-grey-6     { border-left-color: #9e9e9e; }
.border-deep-orange{ border-left-color: #ff5722; }
</style>
