<template>
  <q-dialog v-model="open" persistent>
    <q-card style="min-width: 460px; max-width: 520px">
      <q-card-section class="row items-center q-pb-none">
        <div class="text-h6">Configurações</div>
        <q-space />
        <q-btn flat round dense icon="close" @click="close" />
      </q-card-section>

      <q-tabs
        v-model="tab"
        dense
        align="left"
        class="q-px-md"
        indicator-color="primary"
      >
        <q-tab name="profile" icon="person" label="Meu Perfil" />
        <q-tab v-if="auth.isAdmin" name="company" icon="business" label="Organização" />
      </q-tabs>

      <q-separator />

      <q-tab-panels v-model="tab" animated>
        <!-- ─── Painel: Perfil ─── -->
        <q-tab-panel name="profile" class="q-pa-md q-gutter-md">
          <q-input
            v-model="profile.name"
            label="Nome *"
            outlined
            dense
            :error="!!profileErrors.name"
            :error-message="profileErrors.name"
          />
          <q-input
            v-model="profile.email"
            label="E-mail *"
            type="email"
            outlined
            dense
            :error="!!profileErrors.email"
            :error-message="profileErrors.email"
          />

          <q-separator class="q-my-xs" />
          <div class="text-caption text-grey">Nova senha — deixe em branco para não alterar</div>

          <q-input
            v-model="profile.password"
            label="Nova senha"
            :type="showPassword ? 'text' : 'password'"
            outlined
            dense
            :error="!!profileErrors.password"
            :error-message="profileErrors.password"
          >
            <template #append>
              <q-icon
                :name="showPassword ? 'visibility_off' : 'visibility'"
                class="cursor-pointer"
                @click="showPassword = !showPassword"
              />
            </template>
          </q-input>

          <q-input
            v-model="profile.password_confirmation"
            label="Confirmar nova senha"
            :type="showPassword ? 'text' : 'password'"
            outlined
            dense
          />
        </q-tab-panel>

        <!-- ─── Painel: Organização ─── -->
        <q-tab-panel v-if="auth.isAdmin" name="company" class="q-pa-md q-gutter-md">
          <q-input
            v-model="company.name"
            label="Nome da empresa *"
            outlined
            dense
            :error="!!companyErrors.name"
            :error-message="companyErrors.name"
          />
          <q-input
            v-model="company.slug"
            label="Slug da agenda *"
            outlined
            dense
            hint="Usado na URL pública da agenda"
            :error="!!companyErrors.slug"
            :error-message="companyErrors.slug"
            @update:model-value="company.slug = sanitizeSlug($event)"
          />
          <div v-if="company.slug" class="text-caption text-grey q-mt-none">
            URL pública:
            <span class="text-primary">{{ bookingUrl }}</span>
          </div>
        </q-tab-panel>
      </q-tab-panels>

      <q-separator />

      <q-card-actions align="right" class="q-pa-md">
        <q-btn flat label="Cancelar" @click="close" />
        <q-btn
          color="primary"
          label="Salvar"
          unelevated
          :loading="saving"
          @click="submit"
        />
      </q-card-actions>
    </q-card>
  </q-dialog>
</template>

<script setup>
import { computed, reactive, ref, watch } from 'vue'
import { useQuasar } from 'quasar'
import { useAuthStore } from 'src/stores/auth'

const props = defineProps({
  modelValue: { type: Boolean, default: false },
  initialTab: { type: String, default: 'profile' },
})

const emit = defineEmits(['update:modelValue'])

const $q = useQuasar()
const auth = useAuthStore()

const open = computed({
  get: () => props.modelValue,
  set: (v) => emit('update:modelValue', v),
})

const tab = ref(props.initialTab)
const saving = ref(false)
const showPassword = ref(false)

const profile = reactive({
  name: '',
  email: '',
  password: '',
  password_confirmation: '',
})

const profileErrors = reactive({ name: null, email: null, password: null })

const company = reactive({ name: '', slug: '' })
const companyErrors = reactive({ name: null, slug: null })

const bookingUrl = computed(() => {
  const base = window.location.origin
  return `${base}/book/${company.slug}`
})

watch(
  () => props.modelValue,
  (visible) => {
    if (visible) {
      tab.value = props.initialTab
      resetForms()
    }
  },
)

function resetForms() {
  const u = auth.user
  profile.name = u?.name ?? ''
  profile.email = u?.email ?? ''
  profile.password = ''
  profile.password_confirmation = ''
  showPassword.value = false
  Object.assign(profileErrors, { name: null, email: null, password: null })

  company.name = u?.company?.name ?? ''
  company.slug = u?.company?.slug ?? ''
  Object.assign(companyErrors, { name: null, slug: null })
}

function sanitizeSlug(value) {
  return (value ?? '').toLowerCase().replace(/[^a-z0-9-]/g, '-').replace(/-+/g, '-')
}

function close() {
  open.value = false
}

async function submit() {
  saving.value = true
  try {
    if (tab.value === 'profile') {
      Object.assign(profileErrors, { name: null, email: null, password: null })
      await auth.updateProfile({
        name: profile.name,
        email: profile.email,
        password: profile.password || undefined,
        password_confirmation: profile.password_confirmation || undefined,
      })
      $q.notify({ type: 'positive', message: 'Perfil atualizado com sucesso.' })
      close()
    } else {
      Object.assign(companyErrors, { name: null, slug: null })
      await auth.updateCompany({ name: company.name, slug: company.slug })
      $q.notify({ type: 'positive', message: 'Dados da organização atualizados com sucesso.' })
      close()
    }
  } catch (err) {
    const status = err.response?.status
    if (status === 422) {
      const apiErrors = err.response.data.errors ?? {}
      if (tab.value === 'profile') {
        Object.keys(apiErrors).forEach((k) => { profileErrors[k] = apiErrors[k][0] })
      } else {
        Object.keys(apiErrors).forEach((k) => { companyErrors[k] = apiErrors[k][0] })
      }
    } else {
      $q.notify({ type: 'negative', message: err.response?.data?.message ?? 'Erro ao salvar.' })
    }
  } finally {
    saving.value = false
  }
}
</script>
