<template>
  <q-page class="auth-page flex flex-center">
    <q-card class="auth-card q-pa-lg shadow-10">
      <q-card-section class="text-center q-pb-sm">
        <q-icon name="event" color="primary" size="48px" />
        <div class="text-h5 text-weight-bold q-mt-sm">Criar Conta</div>
        <div class="text-caption text-grey-6">Comece a gerenciar seus agendamentos</div>
      </q-card-section>

      <q-card-section>
        <q-form @submit.prevent="submit" class="q-gutter-md">
          <q-input
            v-model="form.name"
            label="Nome completo"
            outlined
            autofocus
            :error="!!errors.name"
            :error-message="errors.name"
            @update:model-value="errors.name = ''"
          >
            <template #prepend>
              <q-icon name="person" />
            </template>
          </q-input>

          <q-input
            v-model="form.business_name"
            label="Nome do negócio"
            outlined
            :error="!!errors.business_name"
            :error-message="errors.business_name"
            @update:model-value="errors.business_name = ''"
          >
            <template #prepend>
              <q-icon name="store" />
            </template>
          </q-input>

          <q-input
            v-model="form.email"
            label="E-mail"
            type="email"
            outlined
            :error="!!errors.email"
            :error-message="errors.email"
            @update:model-value="errors.email = ''"
          >
            <template #prepend>
              <q-icon name="mail" />
            </template>
          </q-input>

          <q-input
            v-model="form.password"
            label="Senha"
            :type="showPassword ? 'text' : 'password'"
            outlined
            hint="Mínimo 8 caracteres"
            :error="!!errors.password"
            :error-message="errors.password"
            @update:model-value="errors.password = ''"
          >
            <template #prepend>
              <q-icon name="lock" />
            </template>
            <template #append>
              <q-icon
                :name="showPassword ? 'visibility_off' : 'visibility'"
                class="cursor-pointer"
                @click="showPassword = !showPassword"
              />
            </template>
          </q-input>

          <q-input
            v-model="form.password_confirmation"
            label="Confirmar senha"
            :type="showPasswordConfirm ? 'text' : 'password'"
            outlined
            :error="!!errors.password_confirmation"
            :error-message="errors.password_confirmation"
            @update:model-value="errors.password_confirmation = ''"
          >
            <template #prepend>
              <q-icon name="lock_outline" />
            </template>
            <template #append>
              <q-icon
                :name="showPasswordConfirm ? 'visibility_off' : 'visibility'"
                class="cursor-pointer"
                @click="showPasswordConfirm = !showPasswordConfirm"
              />
            </template>
          </q-input>

          <q-checkbox
            v-model="form.terms"
            dense
            :error="!!errors.terms"
          >
            <template #default>
              <span class="text-caption">
                Concordo com os
                <a href="#" class="text-primary" @click.prevent>Termos de Uso</a>
                e
                <a href="#" class="text-primary" @click.prevent>Política de Privacidade</a>
              </span>
            </template>
          </q-checkbox>
          <div v-if="errors.terms" class="text-negative text-caption q-mt-none">
            {{ errors.terms }}
          </div>

          <q-btn
            type="submit"
            label="Criar conta"
            color="primary"
            class="full-width q-mt-md"
            unelevated
            size="lg"
            :loading="loading"
          />
        </q-form>
      </q-card-section>

      <q-card-section class="text-center q-pt-none">
        <span class="text-grey-6 text-caption">Já tem uma conta? </span>
        <router-link :to="{ name: 'login' }" class="text-primary text-caption text-weight-bold">
          Entrar
        </router-link>
      </q-card-section>
    </q-card>
  </q-page>
</template>

<script setup>
import { reactive, ref } from "vue"
import { useRouter } from "vue-router"
import { useQuasar } from "quasar"
import { useAuthStore } from "src/stores/auth"

const $q = useQuasar()
const router = useRouter()
const auth = useAuthStore()

const loading = ref(false)
const showPassword = ref(false)
const showPasswordConfirm = ref(false)

const form = reactive({
  name: "",
  business_name: "",
  email: "",
  password: "",
  password_confirmation: "",
  terms: false,
})

const errors = reactive({
  name: "",
  business_name: "",
  email: "",
  password: "",
  password_confirmation: "",
  terms: "",
})

function validate() {
  let valid = true

  if (!form.name.trim()) {
    errors.name = "Nome é obrigatório."
    valid = false
  }
  if (!form.business_name.trim()) {
    errors.business_name = "Nome do negócio é obrigatório."
    valid = false
  }
  if (!form.email.trim()) {
    errors.email = "E-mail é obrigatório."
    valid = false
  }
  if (form.password.length < 8) {
    errors.password = "Senha deve ter no mínimo 8 caracteres."
    valid = false
  }
  if (form.password !== form.password_confirmation) {
    errors.password_confirmation = "As senhas não coincidem."
    valid = false
  }
  if (!form.terms) {
    errors.terms = "Você deve aceitar os termos para continuar."
    valid = false
  }

  return valid
}

async function submit() {
  if (!validate()) return

  loading.value = true
  try {
    await auth.register({
      name: form.name,
      business_name: form.business_name,
      email: form.email,
      password: form.password,
      password_confirmation: form.password_confirmation,
    })
    $q.notify({ type: "positive", message: "Conta criada com sucesso! Bem-vindo!" })
    router.push({ name: "admin-dashboard" })
  } catch (err) {
    const status = err.response?.status
    const data = err.response?.data

    if (status === 422 && data?.errors) {
      Object.assign(errors, data.errors)
    } else if (status === 409) {
      errors.email = "Este e-mail já está cadastrado."
    } else {
      $q.notify({ type: "negative", message: "Erro ao criar conta. Tente novamente." })
    }
  } finally {
    loading.value = false
  }
}
</script>

<style scoped>
.auth-page {
  min-height: 100vh;
  background: linear-gradient(135deg, #1976d2 0%, #0d47a1 100%);
}

.auth-card {
  width: 100%;
  max-width: 460px;
  border-radius: 16px;
}

a {
  text-decoration: none;
}
a:hover {
  text-decoration: underline;
}
</style>
