<template>
  <q-layout>
    <q-page-container>
      <q-page class="q-pa-xl flex flex-center column">
        <div class="text-h3 text-weight-bold q-mb-md">AgendaPro</div>
        <div class="text-subtitle1 q-mb-xl">
          Sistema de agendamento para pequenos e médios negócios
        </div>
        <div class="q-gutter-md">
          <q-btn color="primary" label="Começar grátis" :to="{ name: 'register' }" size="lg" />
          <q-btn outline color="primary" label="Entrar" :to="{ name: 'login' }" size="lg" />
        </div>
      </q-page>
    </q-page-container>
  </q-layout>
</template>

<script setup>
// TODO: adicionar seções completas (preços, benefícios, prova social)
</script>
