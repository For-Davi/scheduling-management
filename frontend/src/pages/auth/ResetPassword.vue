<template>
  <q-page class="auth-page flex flex-center">
    <q-card class="auth-card q-pa-lg shadow-10">
      <!-- Token inválido ou ausente -->
      <template v-if="!token">
        <q-card-section class="text-center q-py-xl">
          <q-icon name="error_outline" color="negative" size="64px" />
          <div class="text-h6 text-weight-bold q-mt-md">Link inválido</div>
          <div class="text-body2 text-grey-7 q-mt-sm">
            Este link de recuperação é inválido ou expirou.
          </div>
          <q-btn
            label="Solicitar novo link"
            color="primary"
            unelevated
            class="q-mt-lg"
            :to="{ name: 'forgot-password' }"
          />
        </q-card-section>
      </template>

      <!-- Senha redefinida com sucesso -->
      <template v-else-if="success">
        <q-card-section class="text-center q-py-xl">
          <q-icon name="check_circle" color="positive" size="64px" />
          <div class="text-h6 text-weight-bold q-mt-md">Senha redefinida!</div>
          <div class="text-body2 text-grey-7 q-mt-sm">
            Sua senha foi atualizada com sucesso.
          </div>
          <q-btn
            label="Fazer login"
            color="primary"
            unelevated
            class="q-mt-lg"
            :to="{ name: 'login' }"
          />
        </q-card-section>
      </template>

      <!-- Formulário de nova senha -->
      <template v-else>
        <q-card-section class="text-center q-pb-sm">
          <q-icon name="lock_reset" color="primary" size="48px" />
          <div class="text-h5 text-weight-bold q-mt-sm">Nova Senha</div>
          <div class="text-caption text-grey-6">Defina uma nova senha para sua conta</div>
        </q-card-section>

        <q-card-section>
          <q-form @submit.prevent="submit" class="q-gutter-md">
            <q-input
              v-model="form.password"
              label="Nova senha"
              :type="showPassword ? 'text' : 'password'"
              outlined
              autofocus
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
              label="Confirmar nova senha"
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

            <q-btn
              type="submit"
              label="Redefinir senha"
              color="primary"
              class="full-width q-mt-md"
              unelevated
              size="lg"
              :loading="loading"
            />
          </q-form>
        </q-card-section>

        <q-card-section class="text-center q-pt-none">
          <router-link :to="{ name: 'login' }" class="text-primary text-caption text-weight-bold">
            <q-icon name="arrow_back" size="xs" /> Voltar para o login
          </router-link>
        </q-card-section>
      </template>
    </q-card>
  </q-page>
</template>

<script setup>
import { reactive, ref } from "vue"
import { useRoute } from "vue-router"
import { useQuasar } from "quasar"
import { useAuthStore } from "src/stores/auth"

const $q = useQuasar()
const route = useRoute()
const auth = useAuthStore()

const token = route.query.token || ""
const loading = ref(false)
const success = ref(false)
const showPassword = ref(false)
const showPasswordConfirm = ref(false)

const form = reactive({
  password: "",
  password_confirmation: "",
})

const errors = reactive({
  password: "",
  password_confirmation: "",
})

function validate() {
  let valid = true

  if (form.password.length < 8) {
    errors.password = "Senha deve ter no mínimo 8 caracteres."
    valid = false
  }
  if (form.password !== form.password_confirmation) {
    errors.password_confirmation = "As senhas não coincidem."
    valid = false
  }

  return valid
}

async function submit() {
  if (!validate()) return

  loading.value = true
  try {
    await auth.resetPassword({
      token,
      password: form.password,
      password_confirmation: form.password_confirmation,
    })
    success.value = true
  } catch (err) {
    const status = err.response?.status
    const data = err.response?.data

    if (status === 422 && data?.errors) {
      Object.assign(errors, data.errors)
    } else if (status === 400) {
      $q.notify({ type: "negative", message: "Token inválido ou expirado. Solicite um novo link." })
    } else {
      $q.notify({ type: "negative", message: "Erro ao redefinir senha. Tente novamente." })
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
