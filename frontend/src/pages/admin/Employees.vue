<template>
  <q-page class="q-pa-md">
    <!-- Cabeçalho -->
    <div class="row items-center justify-between q-mb-md">
      <div>
        <div class="text-h5">Funcionários</div>
        <div class="text-caption text-grey">
          {{ activeCount }} de {{ planLimit }} funcionários ativos
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
        icon="person_add"
        label="Novo Funcionário"
        :disable="activeCount >= planLimit"
        @click="openDialog()"
      />
    </div>

    <!-- Tabela -->
    <q-table
      :rows="store.employees"
      :columns="columns"
      row-key="id"
      :loading="store.loading"
      flat
      bordered
    >
      <template #body-cell-services="props">
        <q-td :props="props">
          <template v-if="props.row.services?.length">
            <q-chip
              v-for="svc in props.row.services"
              :key="svc.id"
              dense
              color="primary"
              text-color="white"
              size="sm"
            >
              {{ svc.name }}
            </q-chip>
          </template>
          <span v-else class="text-grey text-caption">Nenhuma</span>
        </q-td>
      </template>

      <template #body-cell-is_active="props">
        <q-td :props="props">
          <q-badge
            :color="props.row.is_active ? 'positive' : 'grey'"
            :label="props.row.is_active ? 'Ativo' : 'Inativo'"
          />
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
          Nenhum funcionário cadastrado. Clique em "Novo Funcionário" para começar.
        </div>
      </template>
    </q-table>

    <!-- Dialog criar/editar -->
    <q-dialog v-model="dialog" persistent>
      <q-card style="min-width: 480px">
        <q-card-section>
          <div class="text-h6">{{ editing ? "Editar Funcionário" : "Novo Funcionário" }}</div>
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
            v-model="form.email"
            label="E-mail *"
            type="email"
            :error="!!errors.email"
            :error-message="errors.email"
            outlined
            dense
          />
          <q-input
            v-model="form.phone"
            label="Telefone"
            mask="(##) #####-####"
            outlined
            dense
          />

          <div>
            <div class="text-subtitle2 q-mb-sm">Competências</div>
            <div v-if="servicesStore.services.length === 0" class="text-grey text-caption">
              Nenhum serviço cadastrado ainda.
            </div>
            <div v-else class="row q-gutter-sm">
              <q-checkbox
                v-for="svc in servicesStore.services.filter((s) => s.is_active)"
                :key="svc.id"
                v-model="form.service_ids"
                :val="svc.id"
                :label="svc.name"
                dense
              />
            </div>
          </div>
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
import { useEmployeesStore } from "src/stores/employees";
import { useServicesStore } from "src/stores/services";

const $q = useQuasar();
const auth = useAuthStore();
const store = useEmployeesStore();
const servicesStore = useServicesStore();

const PLAN_LIMITS = { free: 1, basic: 3, advanced: 10 };

const planLimit = computed(() => PLAN_LIMITS[auth.user?.company?.plan ?? "free"] ?? 1);
const activeCount = computed(() => store.employees.filter((e) => e.is_active).length);

const columns = [
  { name: "name", label: "Nome", field: "name", align: "left", sortable: true },
  { name: "email", label: "E-mail", field: "email", align: "left" },
  { name: "phone", label: "Telefone", field: "phone", align: "left" },
  { name: "services", label: "Competências", field: "services", align: "left" },
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
  email: "",
  phone: "",
  service_ids: [],
});

function openDialog(employee = null) {
  editing.value = employee;
  Object.assign(errors, { name: null, email: null });
  if (employee) {
    Object.assign(form, {
      name: employee.name,
      email: employee.email,
      phone: employee.phone ?? "",
      service_ids: employee.services?.map((s) => s.id) ?? [],
    });
  } else {
    Object.assign(form, { name: "", email: "", phone: "", service_ids: [] });
  }
  dialog.value = true;
}

function closeDialog() {
  dialog.value = false;
  editing.value = null;
}

async function submit() {
  saving.value = true;
  Object.assign(errors, { name: null, email: null });

  try {
    const { service_ids, ...employeeData } = form;

    if (editing.value) {
      await store.updateEmployee(editing.value.id, employeeData);
      await store.syncServices(editing.value.id, service_ids);
      $q.notify({ type: "positive", message: "Funcionário atualizado com sucesso." });
    } else {
      const created = await store.createEmployee(employeeData);
      if (service_ids.length > 0) {
        await store.syncServices(created.id, service_ids);
      }
      $q.notify({ type: "positive", message: "Funcionário criado com sucesso." });
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
      $q.notify({ type: "negative", message: "Erro ao salvar funcionário." });
    }
  } finally {
    saving.value = false;
  }
}

function confirmDelete(employee) {
  $q.dialog({
    title: "Excluir funcionário",
    message: `Deseja excluir o funcionário "${employee.name}"?`,
    cancel: true,
    persistent: true,
  }).onOk(async () => {
    try {
      await store.deleteEmployee(employee.id);
      $q.notify({ type: "positive", message: "Funcionário excluído." });
    } catch (err) {
      $q.notify({
        type: "negative",
        message: err.response?.data?.message ?? "Erro ao excluir funcionário.",
      });
    }
  });
}

onMounted(async () => {
  await Promise.all([store.fetchEmployees(), servicesStore.fetchServices()]);
});
</script>
