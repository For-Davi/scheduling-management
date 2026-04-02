<template>
  <q-page class="error-page flex flex-center">
    <div class="text-center q-pa-xl">
      <div class="error-code text-primary text-weight-bold">404</div>

      <q-icon name="search_off" size="80px" color="grey-4" class="q-mb-md" />

      <div class="text-h5 text-weight-bold q-mb-sm">Página não encontrada</div>
      <div class="text-body1 text-grey-6 q-mb-xl">
        O endereço que você acessou não existe ou foi removido.
      </div>

      <div class="row q-gutter-md justify-center">
        <q-btn
          label="Voltar"
          flat
          color="grey-7"
          icon="arrow_back"
          @click="router.back()"
        />
        <q-btn
          label="Ir para o início"
          color="primary"
          unelevated
          icon="home"
          :to="{ name: 'landing' }"
        />
      </div>
    </div>
  </q-page>
</template>

<script setup>
import { useRouter } from "vue-router"

const router = useRouter()
</script>

<style scoped>
.error-page {
  min-height: 100vh;
  background: #f8f9fa;
}

.error-code {
  font-size: 120px;
  line-height: 1;
  opacity: 0.15;
  margin-bottom: -24px;
}
</style>
