<template>
  <q-page class="auth-page flex flex-center">
    <q-card class="auth-card q-pa-lg shadow-10">
      <q-card-section class="text-center q-pb-sm">
        <q-icon name="event" color="primary" size="48px" />
        <div class="text-h5 text-weight-bold q-mt-sm">Entrar</div>
        <div class="text-caption text-grey-6">Acesse sua conta de agendamentos</div>
      </q-card-section>

      <q-card-section>
        <q-form @submit.prevent="submit" class="q-gutter-md">
          <q-input
            v-model="form.email"
            label="E-mail"
            type="email"
            outlined
            autofocus
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

          <div class="row items-center justify-between q-mt-none">
            <q-checkbox v-model="form.remember" label="Lembrar de mim" dense />
            <router-link :to="{ name: 'forgot-password' }" class="text-primary text-caption">
              Esqueceu a senha?
            </router-link>
          </div>

          <q-btn
            type="submit"
            label="Entrar"
            color="primary"
            class="full-width q-mt-md"
            unelevated
            size="lg"
            :loading="loading"
          />
        </q-form>
      </q-card-section>

      <q-card-section class="text-center q-pt-none">
        <span class="text-grey-6 text-caption">Não tem uma conta? </span>
        <router-link :to="{ name: 'register' }" class="text-primary text-caption text-weight-bold">
          Criar conta
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

const form = reactive({
  email: "",
  password: "",
  remember: false,
})

const errors = reactive({
  email: "",
  password: "",
})

async function submit() {
  loading.value = true
  try {
    await auth.login({ email: form.email, password: form.password })
    if (auth.isAdmin) {
      router.push({ name: "admin-dashboard" })
    } else if (auth.isEmployee) {
      router.push({ name: "employee-calendar" })
    } else {
      router.push({ name: "landing" })
    }
  } catch (err) {
    const status = err.response?.status
    const data = err.response?.data

    if (status === 422 && data?.errors) {
      Object.assign(errors, data.errors)
    } else if (status === 401) {
      $q.notify({ type: "negative", message: "E-mail ou senha incorretos." })
    } else {
      $q.notify({ type: "negative", message: "Erro ao fazer login. Tente novamente." })
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
  max-width: 420px;
  border-radius: 16px;
}

a {
  text-decoration: none;
}
a:hover {
  text-decoration: underline;
}
</style>
