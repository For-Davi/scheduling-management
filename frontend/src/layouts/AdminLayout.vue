<template>
  <q-layout view="lHh Lpr lFf">
    <q-header elevated>
      <q-toolbar>
        <q-btn flat dense round icon="menu" @click="leftDrawerOpen = !leftDrawerOpen" />
        <q-toolbar-title>AgendaPro</q-toolbar-title>
        <q-btn flat label="Sair" icon="logout" @click="handleLogout" />
      </q-toolbar>
    </q-header>

    <q-drawer v-model="leftDrawerOpen" show-if-above bordered>
      <q-list>
        <q-item clickable v-ripple :to="{ name: 'admin-dashboard' }">
          <q-item-section avatar><q-icon name="dashboard" /></q-item-section>
          <q-item-section>Dashboard</q-item-section>
        </q-item>
        <q-item clickable v-ripple :to="{ name: 'admin-services' }">
          <q-item-section avatar><q-icon name="design_services" /></q-item-section>
          <q-item-section>Serviços</q-item-section>
        </q-item>
        <q-item clickable v-ripple :to="{ name: 'admin-employees' }">
          <q-item-section avatar><q-icon name="people" /></q-item-section>
          <q-item-section>Funcionários</q-item-section>
        </q-item>
        <q-item clickable v-ripple :to="{ name: 'admin-calendar' }">
          <q-item-section avatar><q-icon name="calendar_month" /></q-item-section>
          <q-item-section>Agenda</q-item-section>
        </q-item>
        <q-item clickable v-ripple :to="{ name: 'admin-billing' }">
          <q-item-section avatar><q-icon name="credit_card" /></q-item-section>
          <q-item-section>Assinatura</q-item-section>
        </q-item>
      </q-list>
    </q-drawer>

    <q-page-container>
      <router-view />
    </q-page-container>
  </q-layout>
</template>

<script setup>
import { ref } from "vue";
import { useRouter } from "vue-router";
import { useAuthStore } from "src/stores/auth";

const router = useRouter();
const auth = useAuthStore();
const leftDrawerOpen = ref(false);

async function handleLogout() {
  await auth.logout();
  router.push({ name: "login" });
}
</script>
