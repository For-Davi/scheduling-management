<template>
  <q-layout>
    <!-- Navbar -->
    <q-header flat class="bg-white text-dark">
      <q-toolbar class="container row justify-between items-center q-py-sm">
        <div class="text-h6 text-weight-bold text-primary">AgendaPro</div>
        <div class="q-gutter-sm">
          <q-btn flat color="primary" label="Entrar" :to="{ name: 'login' }" />
          <q-btn unelevated color="primary" label="Começar grátis" :to="{ name: 'register' }" />
        </div>
      </q-toolbar>
    </q-header>

    <q-page-container>
      <q-page>

        <!-- Hero -->
        <section class="hero-section bg-primary text-white">
          <div class="container q-py-xl text-center">
            <div class="text-h3 text-weight-bold q-mb-md">
              Agendamento online para o seu negócio
            </div>
            <div class="text-subtitle1 q-mb-xl hero-subtitle">
              Reduza faltas, organize sua equipe e deixe seus clientes agendarem
              a qualquer hora — sem precisar criar conta.
            </div>
            <div class="q-gutter-md">
              <q-btn
                unelevated
                color="white"
                text-color="primary"
                label="Começar grátis"
                size="lg"
                :to="{ name: 'register' }"
              />
              <q-btn
                outline
                color="white"
                label="Já tenho conta"
                size="lg"
                :to="{ name: 'login' }"
              />
            </div>
            <div class="q-mt-md text-caption" style="opacity: 0.8">
              3 dias grátis · Sem cartão de crédito
            </div>
          </div>
        </section>

        <!-- Benefícios -->
        <section class="q-py-xl bg-grey-1">
          <div class="container">
            <div class="text-h5 text-weight-bold text-center q-mb-xl">
              Por que usar o AgendaPro?
            </div>
            <div class="row q-col-gutter-lg justify-center">
              <div
                v-for="benefit in benefits"
                :key="benefit.title"
                class="col-12 col-sm-6 col-md-4"
              >
                <q-card flat bordered class="full-height">
                  <q-card-section class="text-center q-pa-lg">
                    <q-icon :name="benefit.icon" size="48px" color="primary" class="q-mb-md" />
                    <div class="text-subtitle1 text-weight-bold q-mb-sm">
                      {{ benefit.title }}
                    </div>
                    <div class="text-body2 text-grey-7">{{ benefit.description }}</div>
                  </q-card-section>
                </q-card>
              </div>
            </div>
          </div>
        </section>

        <!-- Como funciona -->
        <section class="q-py-xl bg-white">
          <div class="container">
            <div class="text-h5 text-weight-bold text-center q-mb-xl">
              Como funciona
            </div>
            <div class="row q-col-gutter-lg items-start justify-center">
              <div
                v-for="(step, index) in steps"
                :key="step.title"
                class="col-12 col-sm-4 text-center"
              >
                <div class="step-number q-mx-auto q-mb-md">{{ index + 1 }}</div>
                <div class="text-subtitle1 text-weight-bold q-mb-sm">{{ step.title }}</div>
                <div class="text-body2 text-grey-7">{{ step.description }}</div>
              </div>
            </div>
          </div>
        </section>

        <!-- Preços -->
        <section class="q-py-xl bg-grey-1">
          <div class="container">
            <div class="text-h5 text-weight-bold text-center q-mb-sm">Planos e preços</div>
            <div class="text-body1 text-grey-7 text-center q-mb-xl">
              Comece grátis e escale conforme seu negócio crescer.
            </div>
            <div class="row q-col-gutter-lg justify-center">
              <div
                v-for="plan in plans"
                :key="plan.name"
                class="col-12 col-sm-6 col-md-4"
              >
                <q-card
                  flat
                  bordered
                  class="full-height pricing-card"
                  :class="{ 'pricing-card--featured': plan.featured }"
                >
                  <q-card-section class="q-pa-lg">
                    <div v-if="plan.featured" class="q-mb-sm">
                      <q-badge color="primary" label="Mais popular" />
                    </div>
                    <div class="text-h6 text-weight-bold q-mb-xs">{{ plan.name }}</div>
                    <div class="q-mb-md">
                      <span class="text-h4 text-weight-bold text-primary">{{ plan.price }}</span>
                      <span v-if="plan.period" class="text-grey-6">{{ plan.period }}</span>
                    </div>
                    <q-separator class="q-mb-md" />
                    <q-list dense class="q-mb-lg">
                      <q-item v-for="feature in plan.features" :key="feature" class="q-px-none">
                        <q-item-section avatar>
                          <q-icon name="check_circle" color="positive" size="18px" />
                        </q-item-section>
                        <q-item-section class="text-body2">{{ feature }}</q-item-section>
                      </q-item>
                    </q-list>
                    <q-btn
                      unelevated
                      :color="plan.featured ? 'primary' : 'grey-3'"
                      :text-color="plan.featured ? 'white' : 'dark'"
                      :label="plan.cta"
                      class="full-width"
                      :to="{ name: 'register' }"
                    />
                  </q-card-section>
                </q-card>
              </div>
            </div>
          </div>
        </section>

        <!-- CTA final -->
        <section class="q-py-xl bg-primary text-white text-center">
          <div class="container">
            <div class="text-h5 text-weight-bold q-mb-md">
              Pronto para organizar sua agenda?
            </div>
            <div class="text-body1 q-mb-lg" style="opacity: 0.85">
              Crie sua conta em menos de 2 minutos e teste grátis por 3 dias.
            </div>
            <q-btn
              unelevated
              color="white"
              text-color="primary"
              label="Criar conta grátis"
              size="lg"
              :to="{ name: 'register' }"
            />
          </div>
        </section>

        <!-- Rodapé -->
        <footer class="bg-dark text-white q-py-lg">
          <div class="container row justify-between items-center">
            <div class="text-body2" style="opacity: 0.7">
              © {{ currentYear }} AgendaPro. Todos os direitos reservados.
            </div>
            <div class="q-gutter-md text-body2">
              <a href="#" class="footer-link">Termos de Uso</a>
              <a href="#" class="footer-link">Política de Privacidade (LGPD)</a>
            </div>
          </div>
        </footer>

      </q-page>
    </q-page-container>
  </q-layout>
</template>

<script setup>
const currentYear = new Date().getFullYear()

const benefits = [
  {
    icon: 'event_available',
    title: 'Reduza faltas e no-shows',
    description:
      'Seus clientes agendam online com confirmação imediata, diminuindo esquecimentos e cancelamentos de última hora.',
  },
  {
    icon: 'groups',
    title: 'Organize sua equipe',
    description:
      'Cada funcionário tem sua própria agenda. O admin vê tudo; o funcionário vê apenas o que é dele.',
  },
  {
    icon: 'link',
    title: 'Link público exclusivo',
    description:
      'Compartilhe um link personalizado da sua empresa. O cliente agenda sem precisar criar conta.',
  },
]

const steps = [
  {
    title: 'Crie sua conta',
    description:
      'Cadastre sua empresa em minutos. Configure serviços, funcionários e horários de atendimento.',
  },
  {
    title: 'Compartilhe o link',
    description:
      'Envie o link público da sua empresa para seus clientes via WhatsApp, Instagram ou onde preferir.',
  },
  {
    title: 'Receba agendamentos',
    description:
      'Os clientes escolhem serviço, funcionário e horário. Você acompanha tudo pelo painel.',
  },
]

const plans = [
  {
    name: 'Free',
    price: 'Grátis',
    period: '',
    featured: false,
    cta: 'Começar grátis',
    features: [
      '1 funcionário',
      'Até 3 serviços',
      'Link público de agendamento',
      '3 dias de trial',
    ],
  },
  {
    name: 'Basic',
    price: 'R$ 29,90',
    period: '/mês',
    featured: true,
    cta: 'Assinar Basic',
    features: [
      'Até 3 funcionários',
      'Até 10 serviços',
      'Link público de agendamento',
      'Dashboard com métricas',
      'Suporte por e-mail',
    ],
  },
  {
    name: 'Advanced',
    price: 'R$ 49,90',
    period: '/mês',
    featured: false,
    cta: 'Assinar Advanced',
    features: [
      'Até 10 funcionários',
      'Até 30 serviços',
      'Link público de agendamento',
      'Dashboard com métricas',
      'Prioridade no suporte',
    ],
  },
]
</script>

<style scoped>
.container {
  max-width: 1100px;
  margin: 0 auto;
  padding: 0 24px;
}

.hero-section {
  padding: 96px 0;
}

.hero-subtitle {
  max-width: 600px;
  margin-left: auto;
  margin-right: auto;
  opacity: 0.9;
}

.step-number {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: var(--q-primary);
  color: white;
  font-size: 22px;
  font-weight: 700;
  display: flex;
  align-items: center;
  justify-content: center;
}

.pricing-card {
  transition: box-shadow 0.2s;
}

.pricing-card:hover {
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
}

.pricing-card--featured {
  border-color: var(--q-primary);
  border-width: 2px;
}

.footer-link {
  color: rgba(255, 255, 255, 0.65);
  text-decoration: none;
  transition: color 0.15s;
}

.footer-link:hover {
  color: white;
}
</style>
