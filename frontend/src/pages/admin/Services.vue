<template>
  <q-page class="q-pa-md">
    <!-- Cabeçalho -->
    <div class="row items-center justify-between q-mb-md">
      <div>
        <div class="text-h5">Serviços</div>
        <div class="text-caption text-grey">
          {{ activeCount }} de {{ planLimit }} serviços ativos
          <q-badge
            v-if="activeCount >= planLimit"
            color="negative"
            label="Limite atingido"
            class="q-ml-sm"
          />
        </div>
      </div>
      <q-btn
        color="primary"
        icon="add"
        label="Novo Serviço"
        :disable="activeCount >= planLimit"
        @click="openDialog()"
      />
    </div>

    <!-- Tabela -->
    <q-table
      :rows="store.services"
      :columns="columns"
      row-key="id"
      :loading="store.loading"
      flat
      bordered
    >
      <template #body-cell-price="props">
        <q-td :props="props">
          {{ formatCurrency(props.row.price) }}
        </q-td>
      </template>

      <template #body-cell-duration_minutes="props">
        <q-td :props="props"> {{ props.row.duration_minutes }} min </q-td>
      </template>

      <template #body-cell-is_active="props">
        <q-td :props="props">
          <q-badge :color="props.row.is_active ? 'positive' : 'grey'" :label="props.row.is_active ? 'Ativo' : 'Inativo'" />
        </q-td>
      </template>

      <template #body-cell-actions="props">
        <q-td :props="props" class="q-gutter-xs">
          <q-btn flat round dense color="primary" icon="edit" @click="openDialog(props.row)" />
          <q-btn flat round dense color="negative" icon="delete" @click="confirmDelete(props.row)" />
        </q-td>
      </template>

      <template #no-data>
        <div class="full-width text-center q-pa-lg text-grey">
          Nenhum serviço cadastrado. Clique em "Novo Serviço" para começar.
        </div>
      </template>
    </q-table>

    <!-- Dialog criar/editar -->
    <q-dialog v-model="dialog" persistent>
      <q-card style="min-width: 400px">
        <q-card-section>
          <div class="text-h6">{{ editing ? "Editar Serviço" : "Novo Serviço" }}</div>
        </q-card-section>

        <q-card-section class="q-gutter-md">
          <q-input
            v-model="form.name"
            label="Nome *"
            :error="!!errors.name"
            :error-message="errors.name"
            outlined
            dense
          />
          <q-input
            v-model.number="form.price"
            label="Preço (R$) *"
            type="number"
            min="0"
            step="0.01"
            :error="!!errors.price"
            :error-message="errors.price"
            outlined
            dense
          />
          <q-input
            v-model.number="form.duration_minutes"
            label="Duração (minutos) *"
            type="number"
            min="5"
            :error="!!errors.duration_minutes"
            :error-message="errors.duration_minutes"
            outlined
            dense
          />
          <q-input
            v-model="form.description"
            label="Descrição"
            type="textarea"
            rows="3"
            outlined
            dense
          />
        </q-card-section>

        <q-card-actions align="right">
          <q-btn flat label="Cancelar" @click="closeDialog" />
          <q-btn color="primary" label="Salvar" :loading="saving" @click="submit" />
        </q-card-actions>
      </q-card>
    </q-dialog>
  </q-page>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from "vue";
import { useQuasar } from "quasar";
import { useAuthStore } from "src/stores/auth";
import { useServicesStore } from "src/stores/services";

const $q = useQuasar();
const auth = useAuthStore();
const store = useServicesStore();

const PLAN_LIMITS = { free: 3, basic: 10, advanced: 30 };

const planLimit = computed(() => PLAN_LIMITS[auth.user?.company?.plan ?? "free"] ?? 3);
const activeCount = computed(() => store.services.filter((s) => s.is_active).length);

const columns = [
  { name: "name", label: "Nome", field: "name", align: "left", sortable: true },
  { name: "price", label: "Preço", field: "price", align: "right", sortable: true },
  { name: "duration_minutes", label: "Duração", field: "duration_minutes", align: "center" },
  { name: "is_active", label: "Status", field: "is_active", align: "center" },
  { name: "actions", label: "Ações", field: "actions", align: "center" },
];

// --- Dialog state ---
const dialog = ref(false);
const saving = ref(false);
const editing = ref(null);
const errors = reactive({});

const form = reactive({
  name: "",
  price: "",
  duration_minutes: 30,
  description: "",
});

function openDialog(service = null) {
  editing.value = service;
  Object.assign(errors, { name: null, price: null, duration_minutes: null });
  if (service) {
    Object.assign(form, {
      name: service.name,
      price: service.price,
      duration_minutes: service.duration_minutes,
      description: service.description ?? "",
    });
  } else {
    Object.assign(form, { name: "", price: "", duration_minutes: 30, description: "" });
  }
  dialog.value = true;
}

function closeDialog() {
  dialog.value = false;
  editing.value = null;
}

async function submit() {
  saving.value = true;
  Object.assign(errors, { name: null, price: null, duration_minutes: null });

  try {
    if (editing.value) {
      await store.updateService(editing.value.id, form);
      $q.notify({ type: "positive", message: "Serviço atualizado com sucesso." });
    } else {
      await store.createService(form);
      $q.notify({ type: "positive", message: "Serviço criado com sucesso." });
    }
    closeDialog();
  } catch (err) {
    const status = err.response?.status;
    if (status === 422) {
      const apiErrors = err.response.data.errors ?? {};
      Object.keys(apiErrors).forEach((k) => {
        errors[k] = apiErrors[k][0];
      });
    } else if (status === 403) {
      $q.notify({ type: "negative", message: err.response.data.message });
    } else {
      $q.notify({ type: "negative", message: "Erro ao salvar serviço." });
    }
  } finally {
    saving.value = false;
  }
}

function confirmDelete(service) {
  $q.dialog({
    title: "Excluir serviço",
    message: `Deseja excluir o serviço "${service.name}"?`,
    cancel: true,
    persistent: true,
  }).onOk(async () => {
    try {
      await store.deleteService(service.id);
      $q.notify({ type: "positive", message: "Serviço excluído." });
    } catch (err) {
      $q.notify({
        type: "negative",
        message: err.response?.data?.message ?? "Erro ao excluir serviço.",
      });
    }
  });
}

function formatCurrency(value) {
  return Number(value).toLocaleString("pt-BR", { style: "currency", currency: "BRL" });
}

onMounted(() => store.fetchServices());
</script>
