<template>
  <q-layout view="lHh Lpr lFf">
    <q-header elevated>
      <q-toolbar>
        <q-toolbar-title>AgendaPro — Minha Agenda</q-toolbar-title>
        <q-btn flat label="Sair" icon="logout" @click="handleLogout" />
      </q-toolbar>
    </q-header>
    <q-page-container>
      <router-view />
    </q-page-container>
  </q-layout>
</template>

<script setup>
import { useRouter } from "vue-router";
import { useAuthStore } from "src/stores/auth";

const router = useRouter();
const auth = useAuthStore();

async function handleLogout() {
  await auth.logout();
  router.push({ name: "login" });
}
</script>
