<template>
  <q-page class="q-pa-md" style="max-width: 1200px; margin: 0 auto">

    <!-- Cabeçalho -->
    <div class="row items-center justify-between q-mb-md">
      <div>
        <div class="text-h5 text-weight-bold">Agenda</div>
        <div class="text-caption text-grey">Todos os agendamentos da empresa</div>
      </div>
      <q-btn flat round icon="refresh" color="primary" :loading="store.loading" @click="load" />
    </div>

    <!-- Filtros -->
    <q-card flat bordered class="q-mb-md">
      <q-card-section>
        <div class="row q-col-gutter-md items-end">
          <div class="col-12 col-sm-4 col-md-3">
            <q-input
              v-model="filters.date"
              label="Data"
              type="date"
              outlined
              dense
              clearable
              @update:model-value="load"
            />
          </div>

          <div class="col-12 col-sm-4 col-md-3">
            <q-select
              v-model="filters.employee_id"
              :options="employeeOptions"
              label="Funcionário"
              outlined
              dense
              clearable
              emit-value
              map-options
              @update:model-value="load"
            />
          </div>

          <div class="col-12 col-sm-4 col-md-3">
            <q-select
              v-model="filters.status"
              :options="statusOptions"
              label="Status"
              outlined
              dense
              clearable
              emit-value
              map-options
              @update:model-value="load"
            />
          </div>

          <div class="col-12 col-md-3">
            <q-btn
              flat
              label="Limpar filtros"
              icon="filter_alt_off"
              color="grey-7"
              dense
              @click="clearFilters"
            />
          </div>
        </div>
      </q-card-section>
    </q-card>

    <!-- Tabela -->
    <q-table
      :rows="store.appointments"
      :columns="columns"
      row-key="id"
      :loading="store.loading"
      flat
      bordered
      :rows-per-page-options="[15, 25, 50]"
    >
      <template #body-cell-starts_at="props">
        <q-td :props="props">
          <div class="text-weight-medium">{{ formatDate(props.row.starts_at) }}</div>
          <div class="text-caption text-grey">{{ formatTime(props.row.starts_at) }} — {{ formatTime(props.row.ends_at) }}</div>
        </q-td>
      </template>

      <template #body-cell-client="props">
        <q-td :props="props">
          <div class="text-weight-medium">{{ props.row.client_name }}</div>
          <div class="text-caption text-grey">{{ props.row.client_email }}</div>
          <div v-if="props.row.client_phone" class="text-caption text-grey">{{ props.row.client_phone }}</div>
        </q-td>
      </template>

      <template #body-cell-service="props">
        <q-td :props="props">
          <span>{{ props.row.service?.name ?? '—' }}</span>
          <div class="text-caption text-grey">{{ props.row.service?.duration_minutes ?? 0 }} min</div>
        </q-td>
      </template>

      <template #body-cell-employee="props">
        <q-td :props="props">
          {{ props.row.employee?.name ?? '—' }}
        </q-td>
      </template>

      <template #body-cell-status="props">
        <q-td :props="props">
          <q-badge
            :color="statusColor(props.row.status)"
            :label="statusLabel(props.row.status)"
            rounded
          />
        </q-td>
      </template>

      <template #body-cell-actions="props">
        <q-td :props="props">
          <q-btn-dropdown
            v-if="hasActions(props.row.status)"
            flat
            dense
            round
            icon="more_vert"
            color="grey-7"
            no-icon-animation
          >
            <q-list dense>
              <q-item
                v-if="props.row.status === 'pending'"
                clickable
                v-close-popup
                @click="changeStatus(props.row, 'confirmed')"
              >
                <q-item-section avatar><q-icon name="check_circle" color="primary" /></q-item-section>
                <q-item-section>Confirmar</q-item-section>
              </q-item>

              <q-item
                v-if="['pending', 'confirmed'].includes(props.row.status)"
                clickable
                v-close-popup
                @click="changeStatus(props.row, 'completed')"
              >
                <q-item-section avatar><q-icon name="task_alt" color="positive" /></q-item-section>
                <q-item-section>Marcar como concluído</q-item-section>
              </q-item>

              <q-item
                v-if="['pending', 'confirmed'].includes(props.row.status)"
                clickable
                v-close-popup
                @click="changeStatus(props.row, 'no_show')"
              >
                <q-item-section avatar><q-icon name="person_off" color="orange" /></q-item-section>
                <q-item-section>Não compareceu</q-item-section>
              </q-item>

              <q-item
                v-if="['pending', 'confirmed'].includes(props.row.status)"
                clickable
                v-close-popup
                @click="changeStatus(props.row, 'cancelled')"
              >
                <q-item-section avatar><q-icon name="cancel" color="negative" /></q-item-section>
                <q-item-section>Cancelar</q-item-section>
              </q-item>
            </q-list>
          </q-btn-dropdown>

          <span v-else class="text-caption text-grey">—</span>
        </q-td>
      </template>

      <template #no-data>
        <div class="full-width text-center q-pa-xl text-grey">
          <q-icon name="event_busy" size="48px" class="q-mb-sm" />
          <div>Nenhum agendamento encontrado para os filtros selecionados.</div>
        </div>
      </template>
    </q-table>

  </q-page>
</template>

<script setup>
import { computed, onMounted, reactive } from "vue"
import { useQuasar } from "quasar"
import { useAppointmentsStore } from "src/stores/appointments"
import { useEmployeesStore } from "src/stores/employees"

const $q = useQuasar()
const store = useAppointmentsStore()
const employeesStore = useEmployeesStore()

const filters = reactive({
  date: new Date().toISOString().slice(0, 10),
  employee_id: null,
  status: null,
})

const columns = [
  { name: "starts_at", label: "Data / Horário", field: "starts_at", align: "left", sortable: true },
  { name: "client", label: "Cliente", field: "client_name", align: "left" },
  { name: "service", label: "Serviço", field: "service", align: "left" },
  { name: "employee", label: "Funcionário", field: "employee", align: "left" },
  { name: "status", label: "Status", field: "status", align: "center" },
  { name: "actions", label: "Ações", field: "actions", align: "center" },
]

const statusOptions = [
  { label: "Pendente", value: "pending" },
  { label: "Confirmado", value: "confirmed" },
  { label: "Concluído", value: "completed" },
  { label: "Cancelado", value: "cancelled" },
  { label: "Não compareceu", value: "no_show" },
]

const employeeOptions = computed(() =>
  employeesStore.employees.map((e) => ({ label: e.name, value: e.id }))
)

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

function formatDate(iso) {
  if (!iso) return "—"
  return new Date(iso).toLocaleDateString("pt-BR", { weekday: "short", day: "2-digit", month: "2-digit", year: "numeric" })
}

function formatTime(iso) {
  if (!iso) return ""
  return new Date(iso).toLocaleTimeString("pt-BR", { hour: "2-digit", minute: "2-digit" })
}

function activeFilters() {
  const params = {}
  if (filters.date) params.date = filters.date
  if (filters.employee_id) params.employee_id = filters.employee_id
  if (filters.status) params.status = filters.status
  return params
}

async function load() {
  await store.fetchAppointments(activeFilters())
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

function clearFilters() {
  filters.date = new Date().toISOString().slice(0, 10)
  filters.employee_id = null
  filters.status = null
  load()
}

onMounted(async () => {
  await Promise.all([employeesStore.fetchEmployees(), load()])
})
</script>
