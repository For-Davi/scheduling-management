# 📋 DETAILS.md — Guia de Contexto para o Agente Claude Code

> Este arquivo é o ponto de verdade do projeto. Leia-o inteiro antes de escrever qualquer linha de código.

---

## 🎯 Visão Geral do Produto

Sistema de agendamento de serviços SaaS voltado para pequenos e médios negócios. Empresas contratam o SaaS para gerenciar serviços, funcionários e agendas. Cada empresa recebe um link público exclusivo para que seus clientes agendem online sem precisar criar conta.

**Nome provisório do sistema:** A definir (use `AgendaPro` como placeholder no código).

---

## 🏗️ Stack Tecnológica (NÃO altere sem instrução explícita)

### Backend
- **Linguagem:** PHP 8.2+
- **Framework:** Laravel 11
- **Pagamentos:** Laravel Cashier (Stripe)
- **Banco de Dados:** MySQL 8
- **Comunicação:** RESTful API — todas as respostas em JSON
- **Qualidade de Código:** Laravel Pint (formatação via `npm run format`)


### Frontend
- **Framework:** Vue 3 (Composition API + `<script setup>`)
- **UI Framework:** Quasar Framework (componentes `Q*`)
- **Gerenciamento de Estado:** Pinia
- **Calendário:** QCalendar (componente do Quasar)
- **Roteamento:** Vue Router

### Infraestrutura / Serviços externos
- **Gateway de Pagamento:** Stripe (via Laravel Cashier)
- **Agendamento de Tarefas:** Laravel Task Scheduling (Artisan commands)
- **Rate Limiting:** Nativo do Laravel (Throttle Middleware)

---

## 👥 Perfis de Usuário

| Perfil | Autenticação | Acesso |
|---|---|---|
| **Admin (Dono)** | E-mail + Senha | Painel completo: serviços, funcionários, agenda, dashboard, assinatura |
| **Funcionário** | E-mail + Senha | Apenas sua própria agenda; pode mudar status e bloquear horários |
| **Cliente** | Sem login | Acessa via link público da empresa para agendar |

---

## 🗂️ Módulos do Sistema

### 1. Landing Page (pública)
- Apresentação do produto, benefícios, prova social
- Tabela de preços com os 3 planos
- CTA → Cadastro de empresa (teste grátis)
- CTA → Login

### 2. Autenticação
- Registro de empresa (Admin)
- Login Admin / Funcionário
- Middleware de guarda por role (`admin`, `employee`)

### 3. Gestão de Serviços (Admin)
- CRUD: Nome, Preço (decimal), Duração (minutos), Descrição (opcional)
- Limite por plano: Free=3, Basic=10, Advanced=30

### 4. Gestão de Funcionários (Admin)
- CRUD: Nome, E-mail, Telefone
- Vínculo N:N com Serviços (competências)
- Limite por plano: Free=1, Basic=3, Advanced=10

### 5. Horários e Configuração de Agenda (Admin)
- Configurar dias da semana + horário de abertura/fechamento
- Buffer Time (minutos) entre agendamentos
- Bloquear dias inteiros (feriados/folgas)

### 6. Agenda / Calendário
- Admin: visualiza todos os funcionários
- Funcionário: visualiza apenas sua agenda
- Funcionário/Admin: marca agendamento como `completed`, `cancelled`, `no_show`
- Bloquear horários pessoais (Funcionário)

### 7. Link Público de Agendamento
- URL: `app.dominio.com/{slug-da-empresa}`
- Fluxo: Escolher serviço → Escolher funcionário apto → Escolher data/hora disponível → Preencher nome, e-mail, telefone → Aceitar termos (opt-in LGPD) → Confirmar
- Validações em tempo real de disponibilidade

### 8. Dashboard (Admin)
- Faturamento do dia e do mês
- Ticket médio
- Serviços mais rentáveis (gráfico)
- Ranking de funcionários por serviços realizados
- Taxa de no-show (%)

### 9. Assinatura / Billing (Admin)
- Exibir plano atual, data de expiração, dias restantes
- Botão de Upgrade → Stripe Checkout
- Botão de Gerenciar → Stripe Customer Portal
- Alerta visual quando faltam ≤ 7 dias para expirar

---

## 💳 Planos e Limites

| Plano | Preço | Funcionários | Serviços | Vigência |
|---|---|---|---|---|
| **Free** | Grátis | 1 | 3 | 3 dias (trial) |
| **Basic** | R$ 29,90/mês | 3 | 10 | 30 dias |
| **Advanced** | R$ 49,90/mês | 10 | 30 | 30 dias |

---

## 📐 Banco de Dados — Entidades Principais

> Crie as migrations nesta ordem para respeitar as foreign keys.

```
companies          → id, name, slug, plan, trial_ends_at, subscription_ends_at, stripe_id, ...
users              → id, company_id, name, email, password, role (admin|employee)
services           → id, company_id, name, price, duration_minutes, description
employees          → id, company_id, user_id (nullable), name, email, phone
employee_service   → employee_id, service_id  (pivot N:N)
business_hours     → id, company_id, day_of_week, open_time, close_time, is_open
blocked_days       → id, company_id, employee_id (nullable), date, reason
appointments       → id, company_id, employee_id, service_id, client_name, client_email,
                     client_phone, starts_at, ends_at, status (scheduled|completed|cancelled|no_show),
                     lgpd_consent (bool)
appointment_logs   → id, appointment_id, user_id (nullable), old_status, new_status, changed_at
```

---

## ⚙️ Regras de Negócio Críticas

1. **Antecedência mínima:** Nenhum agendamento pode ser criado com menos de 2 horas de antecedência. Validar no backend e no frontend.

2. **Aptidão obrigatória:** O link público só exibe funcionários que possuem o serviço selecionado na tabela `employee_service`.

3. **Trava de limites do plano:** Antes de criar funcionário ou serviço, verificar se o total atual está abaixo do limite do plano. Retornar erro HTTP 403 com mensagem clara se estiver no limite.

4. **Concorrência de horários:** Ao criar um agendamento, checar no banco se o funcionário já possui outro agendamento com status `scheduled` que se sobreponha ao intervalo `(starts_at, ends_at + buffer_time)`.

5. **Faturamento condicional:** Métricas do dashboard só consideram agendamentos com status `completed`.

6. **Alerta de expiração:** Job diário do Laravel Scheduler verifica se `subscription_ends_at` está entre hoje e hoje+7 dias. Se sim, salva flag na empresa ou dispara evento para exibir banner no frontend.

7. **Downgrade forçado (Webhook Stripe):** Ao receber evento `customer.subscription.deleted` ou `invoice.payment_failed`, rebaixar empresa para Free.
   - Se a empresa tiver mais funcionários/serviços que o plano Free permite, **bloquear** os excedentes mais recentes (por `created_at DESC`) sem deletar. Adicionar campo `is_active` nas tabelas `employees` e `services`.

8. **Trilha de auditoria:** Toda mudança de status em `appointments` insere uma linha em `appointment_logs` com `user_id`, `old_status`, `new_status` e `changed_at = now()`.

9. **Cancelamento pelo cliente:** O usuário que cancelou pelo portal Stripe mantém acesso premium até o fim do ciclo pago (`subscription_ends_at`).

---

## 🔐 Segurança

- Rate limiting nas rotas públicas de agendamento (`throttle:10,1` como ponto de partida)
- Form Requests do Laravel para validação e sanitização em todos os endpoints
- Policies do Laravel para garantir que Admin só acessa dados da própria empresa (multi-tenancy por `company_id`)
- Autenticação via Laravel Sanctum (tokens de API para o frontend Vue)

---

## 🌐 Padrão de Rotas da API

```
POST   /api/auth/register
POST   /api/auth/login
POST   /api/auth/logout

GET    /api/services
POST   /api/services
PUT    /api/services/{id}
DELETE /api/services/{id}

GET    /api/employees
POST   /api/employees
PUT    /api/employees/{id}
DELETE /api/employees/{id}
POST   /api/employees/{id}/services   → sincroniza competências

GET    /api/business-hours
PUT    /api/business-hours

GET    /api/appointments
POST   /api/appointments
PATCH  /api/appointments/{id}/status

GET    /api/dashboard/metrics

# Rotas públicas (sem auth, com throttle)
GET    /public/{slug}                 → dados da empresa para o link público
GET    /public/{slug}/availability    → horários disponíveis
POST   /public/{slug}/appointments    → criar agendamento

# Billing
POST   /api/billing/checkout
GET    /api/billing/portal
POST   /api/webhooks/stripe           → sem auth, validar assinatura Stripe
```

---

## 🧩 Estrutura de Diretórios Sugerida

```
/backend  (Laravel)
  app/
    Http/Controllers/Api/
    Http/Controllers/Public/
    Http/Requests/
    Http/Middleware/
    Models/
    Policies/
    Services/          ← lógica de negócio (AvailabilityService, BillingService)
    Jobs/              ← CheckSubscriptionExpirationJob
  database/migrations/
  routes/api.php
  routes/web.php       ← apenas webhook e portal Stripe

/frontend  (Vue 3 + Quasar)
  src/
    pages/
      Landing.vue
      auth/Login.vue
      auth/Register.vue
      admin/Dashboard.vue
      admin/Services.vue
      admin/Employees.vue
      admin/Calendar.vue
      admin/Billing.vue
      employee/MyCalendar.vue
      public/Booking.vue
    components/
    stores/            ← Pinia stores
    router/
    boot/              ← axios config, auth guard
```

---

## ✅ Convenções de Código

- **PHP:** Seguir PSR-12; rodar `npm run format` antes de commitar (executa o Laravel Pint)
- **Vue:** Composition API com `<script setup>`, sem Options API
- **Commits:** Conventional Commits (`feat:`, `fix:`, `chore:`, etc.)
- **Idioma do código:** variáveis e funções em **inglês**; comentários e mensagens de UI em **português**
- **Respostas de erro API:** sempre `{ "message": "...", "errors": {} }` com HTTP status correto

---

## 🚫 O que NÃO fazer

- Não criar telas de pagamento customizadas — usar Stripe Checkout e Customer Portal
- Não deletar dados ao fazer downgrade — apenas bloquear com `is_active = false`
- Não usar Options API no Vue
- Não misturar lógica de negócio nos Controllers — usar classes de Service
- Não commitar sem rodar `npm run format` antes

---

## 🐳 Ambiente de Desenvolvimento (Docker)

O projeto roda 100% em Docker. Não é necessário instalar PHP, Node ou MySQL na máquina.

### Serviços e portas

| Serviço | Container | Porta local |
|---|---|---|
| Laravel (backend) | `agendapro_backend` | http://localhost:8000 |
| Quasar (frontend) | `agendapro_frontend` | http://localhost:9000 |
| MySQL 8 | `agendapro_mysql` | 3306 |
| phpMyAdmin | `agendapro_phpmyadmin` | http://localhost:8080 |

### Estrutura de arquivos Docker

```
/agendapro
  ├── docker-compose.yml
  ├── .env                  ← copiar de .env.example e preencher
  ├── .env.example
  ├── .gitignore
  ├── DETAILS.md
  ├── docker/
  │   ├── backend/Dockerfile
  │   └── frontend/Dockerfile
  ├── backend/              ← projeto Laravel
  └── frontend/             ← projeto Quasar
```

### Comandos do dia a dia

```bash
# Subir todos os serviços
docker compose up -d

# Ver logs em tempo real
docker compose logs -f backend
docker compose logs -f frontend

# Rodar comandos Laravel (artisan)
docker compose exec backend php artisan migrate
docker compose exec backend php artisan make:model NomeDoModel -m

# Rodar npm run format (Laravel Pint)
docker compose exec backend npm run format

# Instalar pacote Composer
docker compose exec backend composer require nome/pacote

# Instalar pacote npm no frontend
docker compose exec frontend npm install nome-pacote

# Derrubar tudo (mantém os dados do banco)
docker compose down

# Derrubar E apagar o banco (reset total)
docker compose down -v
```

### Primeiro uso (passo a passo)

```bash
# 1. Clone o repositório e entre na pasta
git clone <repo> agendapro && cd agendapro

# 2. Crie o .env com as chaves do Stripe
cp .env.example .env

# 3. Suba os containers (primeira vez faz o build)
docker compose up -d --build

# 4. Aguarde o MySQL ficar saudável, depois rode as migrations
docker compose exec backend php artisan migrate

# 5. Gere a APP_KEY do Laravel
docker compose exec backend php artisan key:generate
```

---

## 🔄 Próximos Passos (Backlog Inicial)

1. [x] Criar estrutura de pastas do monorepo (`/backend`, `/frontend`, `/docker`)
2. [x] Subir o ambiente Docker (`docker compose up -d --build`)
3. [x] Criar projeto Laravel 12 dentro de `/backend`
4. [x] Criar projeto Quasar (Vue 3) dentro de `/frontend`
5. [x] Configurar `npm run format` no `package.json` do backend (executa Laravel Pint)
6. [x] Instalar Laravel Sanctum e Laravel Cashier no backend
7. [x] Criar migrations na ordem descrita acima
8. [x] Autenticação (Sanctum) + Registro de empresa
9. [x] CRUD de Serviços com trava de plano
10. [x] CRUD de Funcionários + vínculo de competências
11. [ ] Configuração de horários e buffer time
12. [ ] Lógica de disponibilidade (`AvailabilityService`)
13. [ ] Link público de agendamento
14. [ ] Integração Stripe (Cashier + Webhooks)
15. [ ] Dashboard com métricas
16. [ ] Job de alerta de expiração
17. [ ] Landing Page