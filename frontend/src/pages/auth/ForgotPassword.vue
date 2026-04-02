<template>
  <q-page class="auth-page flex flex-center">
    <q-card class="auth-card q-pa-lg shadow-10">
      <!-- Estado: formulário -->
      <template v-if="!sent">
        <q-card-section class="text-center q-pb-sm">
          <q-icon name="lock_reset" color="primary" size="48px" />
          <div class="text-h5 text-weight-bold q-mt-sm">Recuperar Senha</div>
          <div class="text-caption text-grey-6">
            Informe seu e-mail e enviaremos as instruções de recuperação
          </div>
        </q-card-section>

        <q-card-section>
          <q-form @submit.prevent="submit" class="q-gutter-md">
            <q-input
              v-model="email"
              label="E-mail"
              type="email"
              outlined
              autofocus
              :error="!!emailError"
              :error-message="emailError"
              @update:model-value="emailError = ''"
            >
              <template #prepend>
                <q-icon name="mail" />
              </template>
            </q-input>

            <q-btn
              type="submit"
              label="Enviar instruções"
              color="primary"
              class="full-width q-mt-md"
              unelevated
              size="lg"
              :loading="loading"
            />
          </q-form>
        </q-card-section>
      </template>

      <!-- Estado: e-mail enviado -->
      <template v-else>
        <q-card-section class="text-center q-py-xl">
          <q-icon name="mark_email_read" color="positive" size="64px" />
          <div class="text-h6 text-weight-bold q-mt-md">E-mail enviado!</div>
          <div class="text-body2 text-grey-7 q-mt-sm q-px-md">
            Verifique sua caixa de entrada em <strong>{{ email }}</strong> e siga as instruções para redefinir sua senha.
          </div>
          <q-btn
            label="Reenviar e-mail"
            flat
            color="primary"
            class="q-mt-lg"
            :loading="loading"
            @click="submit"
          />
        </q-card-section>
      </template>

      <q-card-section class="text-center q-pt-none">
        <router-link :to="{ name: 'login' }" class="text-primary text-caption text-weight-bold">
          <q-icon name="arrow_back" size="xs" /> Voltar para o login
        </router-link>
      </q-card-section>
    </q-card>
  </q-page>
</template>

<script setup>
import { ref } from "vue"
import { useQuasar } from "quasar"
import { useAuthStore } from "src/stores/auth"

const $q = useQuasar()
const auth = useAuthStore()

const email = ref("")
const emailError = ref("")
const loading = ref(false)
const sent = ref(false)

async function submit() {
  if (!email.value.trim()) {
    emailError.value = "E-mail é obrigatório."
    return
  }

  loading.value = true
  try {
    await auth.forgotPassword(email.value)
    sent.value = true
  } catch (err) {
    const status = err.response?.status
    const data = err.response?.data

    if (status === 422 && data?.errors?.email) {
      emailError.value = data.errors.email
    } else if (status === 404) {
      emailError.value = "Nenhuma conta encontrada com este e-mail."
    } else {
      $q.notify({ type: "negative", message: "Erro ao enviar e-mail. Tente novamente." })
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
