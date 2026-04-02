<template>
  <q-layout view="lHh Lpr lFf">
    <q-header elevated>
      <q-toolbar>
        <q-toolbar-title>AgendaPro — Minha Agenda</q-toolbar-title>

        <!-- Perfil do usuário -->
        <q-btn flat round dense>
          <q-avatar color="primary" text-color="white" size="34px" class="text-weight-bold">
            {{ userInitial }}
          </q-avatar>

          <q-menu anchor="bottom right" self="top right" class="q-mt-sm">
            <div class="q-pa-md" style="min-width: 200px">
              <div class="text-subtitle2 text-weight-bold ellipsis">{{ auth.user?.name }}</div>
              <div class="text-caption text-grey ellipsis">{{ auth.user?.email }}</div>
            </div>

            <q-separator />

            <q-list dense>
              <q-item clickable v-close-popup @click="dialogOpen = true">
                <q-item-section avatar>
                  <q-icon name="person" color="primary" size="20px" />
                </q-item-section>
                <q-item-section>Editar Perfil</q-item-section>
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

    <q-page-container>
      <router-view />
    </q-page-container>

    <ProfileDialog v-model="dialogOpen" initial-tab="profile" />
  </q-layout>
</template>

<script setup>
import { computed, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from 'src/stores/auth'
import ProfileDialog from 'src/components/ProfileDialog.vue'

const router = useRouter()
const auth = useAuthStore()

const dialogOpen = ref(false)

const userInitial = computed(() => {
  return (auth.user?.name ?? 'U').charAt(0).toUpperCase()
})

async function handleLogout() {
  await auth.logout()
  router.push({ name: 'login' })
}
</script>
