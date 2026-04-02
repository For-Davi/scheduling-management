<template>
  <q-layout view="lHh Lpr lFf">
    <q-header elevated>
      <q-toolbar>
        <q-btn flat dense round icon="menu" @click="leftDrawerOpen = !leftDrawerOpen" />
        <q-toolbar-title>AgendaPro</q-toolbar-title>

        <!-- Perfil do usuário -->
        <q-btn flat round dense>
          <q-avatar color="primary" text-color="white" size="34px" class="text-weight-bold">
            {{ userInitial }}
          </q-avatar>

          <q-menu anchor="bottom right" self="top right" class="q-mt-sm">
            <!-- Informações do usuário -->
            <div class="q-pa-md" style="min-width: 220px">
              <div class="text-subtitle2 text-weight-bold ellipsis">{{ auth.user?.name }}</div>
              <div class="text-caption text-grey ellipsis">{{ auth.user?.email }}</div>
              <q-badge color="primary" :label="auth.user?.company?.name" class="q-mt-xs" />
            </div>

            <q-separator />

            <q-list dense>
              <q-item clickable v-close-popup @click="openDialog('profile')">
                <q-item-section avatar>
                  <q-icon name="person" color="primary" size="20px" />
                </q-item-section>
                <q-item-section>Editar Perfil</q-item-section>
              </q-item>

              <q-item clickable v-close-popup @click="openDialog('company')">
                <q-item-section avatar>
                  <q-icon name="business" color="primary" size="20px" />
                </q-item-section>
                <q-item-section>Dados da Organização</q-item-section>
              </q-item>
            </q-list>

            <q-separator />

            <q-list dense>
              <q-item clickable v-close-popup class="text-negative" @click="handleLogout">
                <q-item-section avatar>
                  <q-icon name="logout" color="negative" size="20px" />
                </q-item-section>
                <q-item-section>Sair</q-item-section>
              </q-item>
            </q-list>
          </q-menu>
        </q-btn>
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

    <ProfileDialog v-model="dialogOpen" :initial-tab="dialogTab" />
  </q-layout>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from 'src/stores/auth'
import ProfileDialog from 'src/components/ProfileDialog.vue'

const router = useRouter()
const auth = useAuthStore()
const leftDrawerOpen = ref(false)

const dialogOpen = ref(false)
const dialogTab = ref('profile')

const userInitial = computed(() => {
  return (auth.user?.name ?? 'U').charAt(0).toUpperCase()
})

function openDialog(tab) {
  dialogTab.value = tab
  dialogOpen.value = true
}

async function handleLogout() {
  await auth.logout()
  router.push({ name: 'login' })
}
</script>
