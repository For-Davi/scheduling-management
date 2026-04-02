<template>
  <q-layout>
    <q-page-container>
      <q-page class="q-pa-md" style="max-width: 720px; margin: 0 auto">

        <!-- Carregando empresa -->
        <div v-if="booking.loading" class="flex flex-center q-pa-xl">
          <q-spinner size="48px" color="primary" />
        </div>

        <!-- Empresa não encontrada / erro -->
        <div v-else-if="booking.loadError" class="text-center q-pa-xl">
          <q-icon name="error_outline" size="64px" color="negative" />
          <div class="text-h6 q-mt-md text-negative">{{ booking.loadError }}</div>
        </div>

        <!-- Agendamento confirmado -->
        <div v-else-if="booking.confirmedAppointment" class="text-center q-pa-lg">
          <q-icon name="check_circle" size="72px" color="positive" />
          <div class="text-h5 text-weight-bold q-mt-md">Agendamento Confirmado!</div>
          <div class="text-body2 text-grey q-mt-xs q-mb-lg">
            Um e-mail de confirmação será enviado para
            <strong>{{ booking.confirmedAppointment.client_email }}</strong>.
          </div>

          <q-card flat bordered class="text-left" style="max-width: 420px; margin: 0 auto">
            <q-card-section>
              <div class="text-subtitle1 text-weight-medium q-mb-sm">Detalhes</div>
              <q-list dense>
                <q-item>
                  <q-item-section avatar>
                    <q-icon name="design_services" color="primary" />
                  </q-item-section>
                  <q-item-section>{{ booking.confirmedAppointment.service?.name }}</q-item-section>
                </q-item>
                <q-item>
                  <q-item-section avatar>
                    <q-icon name="person" color="primary" />
                  </q-item-section>
                  <q-item-section>{{ booking.confirmedAppointment.employee?.name }}</q-item-section>
                </q-item>
                <q-item>
                  <q-item-section avatar>
                    <q-icon name="schedule" color="primary" />
                  </q-item-section>
                  <q-item-section>{{ formatDateTime(booking.confirmedAppointment.starts_at) }}</q-item-section>
                </q-item>
              </q-list>
            </q-card-section>
          </q-card>
        </div>

        <!-- Fluxo de agendamento -->
        <template v-else-if="booking.company">
          <div class="q-mb-md">
            <div class="text-h5 text-weight-bold">{{ booking.company.name }}</div>
            <div class="text-caption text-grey">Agende seu horário online, sem precisar criar conta</div>
          </div>

          <q-stepper
            v-model="booking.step"
            animated
            flat
            color="primary"
            class="shadow-1 rounded-borders"
          >
            <!-- ── Etapa 1: Serviço ── -->
            <q-step :name="1" title="Serviço" icon="design_services" :done="booking.step > 1">
              <div v-if="booking.company.services.length === 0" class="text-grey text-center q-pa-md">
                Nenhum serviço disponível no momento.
              </div>

              <div v-else class="q-gutter-sm">
                <div
                  v-for="service in booking.company.services"
                  :key="service.id"
                  class="cursor-pointer"
                  @click="booking.selectService(service)"
                >
                  <q-card
                    flat
                    bordered
                    :class="[
                      'rounded-borders',
                      booking.selectedService?.id === service.id ? 'bg-primary text-white' : '',
                    ]"
                  >
                    <q-card-section class="row items-center justify-between q-py-sm">
                      <div>
                        <div class="text-subtitle1 text-weight-medium">{{ service.name }}</div>
                        <div
                          class="text-caption"
                          :class="booking.selectedService?.id === service.id ? 'text-white' : 'text-grey'"
                        >
                          {{ service.duration_minutes }} min
                          <span v-if="service.description"> · {{ service.description }}</span>
                        </div>
                      </div>
                      <div class="text-subtitle1 text-weight-bold">
                        {{ formatCurrency(service.price) }}
                      </div>
                    </q-card-section>
                  </q-card>
                </div>
              </div>

              <q-stepper-navigation class="q-pt-md">
                <q-btn
                  color="primary"
                  label="Próximo"
                  :disable="!booking.selectedService"
                  @click="booking.step = 2"
                />
              </q-stepper-navigation>
            </q-step>

            <!-- ── Etapa 2: Profissional ── -->
            <q-step :name="2" title="Profissional" icon="person" :done="booking.step > 2">
              <div
                v-if="booking.filteredEmployees.length === 0"
                class="text-grey text-center q-pa-md"
              >
                Nenhum profissional disponível para este serviço.
              </div>

              <div v-else class="q-gutter-sm">
                <div
                  v-for="emp in booking.filteredEmployees"
                  :key="emp.id"
                  class="cursor-pointer"
                  @click="booking.selectEmployee(emp)"
                >
                  <q-card
                    flat
                    bordered
                    :class="[
                      'rounded-borders',
                      booking.selectedEmployee?.id === emp.id ? 'bg-primary text-white' : '',
                    ]"
                  >
                    <q-card-section class="row items-center q-py-sm q-gutter-sm">
                      <q-avatar
                        :color="booking.selectedEmployee?.id === emp.id ? 'white' : 'primary'"
                        :text-color="booking.selectedEmployee?.id === emp.id ? 'primary' : 'white'"
                        size="36px"
                      >
                        {{ emp.name.charAt(0).toUpperCase() }}
                      </q-avatar>
                      <div class="text-subtitle1">{{ emp.name }}</div>
                    </q-card-section>
                  </q-card>
                </div>
              </div>

              <q-stepper-navigation class="q-pt-md">
                <q-btn
                  color="primary"
                  label="Próximo"
                  :disable="!booking.selectedEmployee"
                  @click="booking.step = 3"
                />
                <q-btn flat label="Voltar" class="q-ml-sm" @click="booking.step = 1" />
              </q-stepper-navigation>
            </q-step>

            <!-- ── Etapa 3: Data e Hora ── -->
            <q-step :name="3" title="Data e Hora" icon="calendar_today" :done="booking.step > 3">
              <div class="row q-col-gutter-md">
                <div class="col-12 col-sm-auto">
                  <q-date
                    v-model="booking.selectedDate"
                    minimal
                    :options="booking.isDateAllowed"
                    @update:model-value="booking.fetchSlots"
                  />
                </div>

                <div class="col">
                  <div v-if="!booking.selectedDate" class="text-grey q-pt-sm">
                    Selecione uma data para ver os horários disponíveis.
                  </div>

                  <div v-else-if="booking.loadingSlots" class="flex flex-center q-pa-lg">
                    <q-spinner color="primary" />
                  </div>

                  <div
                    v-else-if="booking.availableSlots.length === 0"
                    class="text-grey q-pt-sm"
                  >
                    Sem horários disponíveis para este dia.
                  </div>

                  <template v-else>
                    <div class="text-subtitle2 q-mb-sm">Horários disponíveis</div>
                    <div class="row q-gutter-sm">
                      <q-btn
                        v-for="slot in booking.availableSlots"
                        :key="slot.starts_at"
                        :label="slot.starts_at"
                        :color="
                          booking.selectedSlot?.starts_at === slot.starts_at
                            ? 'primary'
                            : 'grey-3'
                        "
                        :text-color="
                          booking.selectedSlot?.starts_at === slot.starts_at ? 'white' : 'dark'
                        "
                        unelevated
                        dense
                        padding="xs sm"
                        @click="booking.selectedSlot = slot"
                      />
                    </div>
                  </template>
                </div>
              </div>

              <q-stepper-navigation class="q-pt-md">
                <q-btn
                  color="primary"
                  label="Próximo"
                  :disable="!booking.selectedSlot"
                  @click="booking.step = 4"
                />
                <q-btn flat label="Voltar" class="q-ml-sm" @click="booking.step = 2" />
              </q-stepper-navigation>
            </q-step>

            <!-- ── Etapa 4: Dados do Cliente ── -->
            <q-step :name="4" title="Seus Dados" icon="edit" :done="booking.step > 4">
              <div class="q-gutter-md" style="max-width: 420px">
                <q-input
                  v-model="booking.clientData.name"
                  label="Nome completo *"
                  :error="!!booking.fieldErrors.client_name"
                  :error-message="booking.fieldErrors.client_name"
                  outlined
                  dense
                />
                <q-input
                  v-model="booking.clientData.email"
                  label="E-mail *"
                  type="email"
                  :error="!!booking.fieldErrors.client_email"
                  :error-message="booking.fieldErrors.client_email"
                  outlined
                  dense
                />
                <q-input
                  v-model="booking.clientData.phone"
                  label="Telefone (opcional)"
                  mask="(##) #####-####"
                  outlined
                  dense
                />
              </div>

              <q-stepper-navigation class="q-pt-md">
                <q-btn
                  color="primary"
                  label="Próximo"
                  :disable="!booking.clientData.name || !booking.clientData.email"
                  @click="booking.step = 5"
                />
                <q-btn flat label="Voltar" class="q-ml-sm" @click="booking.step = 3" />
              </q-stepper-navigation>
            </q-step>

            <!-- ── Etapa 5: Confirmar + LGPD ── -->
            <q-step :name="5" title="Confirmar" icon="check">
              <q-card flat bordered class="q-mb-md">
                <q-card-section>
                  <div class="text-subtitle1 text-weight-medium q-mb-sm">Resumo do Agendamento</div>
                  <q-list dense>
                    <q-item>
                      <q-item-section avatar>
                        <q-icon name="design_services" color="primary" />
                      </q-item-section>
                      <q-item-section>
                        <q-item-label>{{ booking.selectedService?.name }}</q-item-label>
                        <q-item-label caption>
                          {{ formatCurrency(booking.selectedService?.price) }}
                          · {{ booking.selectedService?.duration_minutes }} min
                        </q-item-label>
                      </q-item-section>
                    </q-item>

                    <q-item>
                      <q-item-section avatar>
                        <q-icon name="person" color="primary" />
                      </q-item-section>
                      <q-item-section>{{ booking.selectedEmployee?.name }}</q-item-section>
                    </q-item>

                    <q-item>
                      <q-item-section avatar>
                        <q-icon name="calendar_today" color="primary" />
                      </q-item-section>
                      <q-item-section>
                        {{ formatDateBR(booking.selectedDate) }} às
                        {{ booking.selectedSlot?.starts_at }}
                      </q-item-section>
                    </q-item>

                    <q-item>
                      <q-item-section avatar>
                        <q-icon name="person_outline" color="primary" />
                      </q-item-section>
                      <q-item-section>
                        <q-item-label>{{ booking.clientData.name }}</q-item-label>
                        <q-item-label caption>
                          {{ booking.clientData.email
                          }}{{ booking.clientData.phone ? ' · ' + booking.clientData.phone : '' }}
                        </q-item-label>
                      </q-item-section>
                    </q-item>
                  </q-list>
                </q-card-section>
              </q-card>

              <!-- LGPD opt-in -->
              <q-checkbox v-model="booking.lgpdConsent" class="q-mb-md items-start">
                <span class="text-caption">
                  Concordo com o uso dos meus dados pessoais para fins de agendamento, conforme
                  a <strong>Lei Geral de Proteção de Dados (LGPD — Lei nº 13.709/2018)</strong>.
                  Os dados não serão compartilhados com terceiros.
                </span>
              </q-checkbox>

              <!-- Erro de submit -->
              <q-banner
                v-if="booking.submitError"
                rounded
                dense
                class="bg-negative text-white q-mb-md"
              >
                {{ booking.submitError }}
              </q-banner>

              <q-stepper-navigation>
                <q-btn
                  color="primary"
                  label="Confirmar Agendamento"
                  icon-right="check"
                  :loading="booking.submitting"
                  :disable="!booking.lgpdConsent"
                  @click="booking.submit(route.params.slug)"
                />
                <q-btn flat label="Voltar" class="q-ml-sm" @click="booking.step = 4" />
              </q-stepper-navigation>
            </q-step>
          </q-stepper>
        </template>

      </q-page>
    </q-page-container>
  </q-layout>
</template>

<script setup>
import { onMounted } from 'vue'
import { useRoute } from 'vue-router'
import { useBookingStore } from 'src/stores/booking'

const route = useRoute()
const booking = useBookingStore()

onMounted(() => {
  booking.loadCompany(route.params.slug)
})

// selectedDate vem no formato YYYY/MM/DD do q-date
function formatDateBR(dateStr) {
  if (!dateStr) return ''
  const [y, m, d] = dateStr.split('/')
  return new Date(`${y}-${m}-${d}T12:00:00`).toLocaleDateString('pt-BR', {
    weekday: 'long',
    day: '2-digit',
    month: 'long',
    year: 'numeric',
  })
}

// starts_at vem em ISO do backend após confirmação
function formatDateTime(isoStr) {
  if (!isoStr) return ''
  return new Date(isoStr).toLocaleString('pt-BR', {
    weekday: 'long',
    day: '2-digit',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

function formatCurrency(value) {
  return Number(value).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' })
}
</script>
