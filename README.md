# TC Billing Service (`tc-billing-service`)

Microsserviço responsável pela gestão de orçamentos, faturamento e integração com gateway de pagamento (**Mercado Pago**), compondo a arquitetura de microsserviços distribuídos da **Fase 4 do Tech Challenge**.

---

## 🏛️ Arquitetura e Padrões de Projeto (DDD)

O serviço foi projetado adotando **Domain-Driven Design (DDD)** e princípios de **Arquitetura Limpa / Hexagonal**, garantindo independência entre o núcleo de faturamento (regras de domínio) e os detalhes de infraestrutura (MongoDB, RabbitMQ e SDKs de pagamento):

```
app/
├── Domain/                                 # Regras de Negócio e Invariantes
│   ├── Entities/
│   │   ├── Invoice.php                     # Agregado Raiz (Root Entity)
│   │   └── PaymentAttempt.php              # Entidade interna de histórico transacional
│   ├── ValueObjects/
│   │   ├── InvoiceId.php                   # Identificador tipado (ObjectId / UUID)
│   │   ├── WorkOrderId.php                 # Referência à Ordem de Serviço
│   │   ├── Money.php                       # Valor monetário imutável e validações
│   │   └── PaymentStatus.php               # Enum (PENDING, APPROVED, REJECTED, REFUNDED)
│   ├── Repositories/
│   │   └── InvoiceRepositoryInterface.php  # Contrato de persistência agnóstico
│   ├── Events/
│   │   ├── PaymentApprovedDomainEvent.php  # Evento disparado na aprovação
│   │   └── PaymentFailedDomainEvent.php    # Evento disparado em recusa/falha
│   └── Exceptions/
│       ├── InvoiceNotFoundException.php
│       └── InvalidPaymentOperationException.php
│
├── Application/                            # Casos de Uso e Orquestração
│   ├── UseCases/
│   │   ├── CreateInvoiceUseCase.php        # Criação de fatura e link Mercado Pago
│   │   ├── ProcessPaymentWebhookUseCase.php# Processamento do retorno de pagamento
│   │   └── RefundPaymentUseCase.php        # Compensação da Saga (Estorno via Gateway)
│   ├── DTOs/
│   │   ├── CreateInvoiceInputDto.php
│   │   ├── WebhookPaymentInputDto.php
│   │   └── InvoiceOutputDto.php
│   └── Services/
│       ├── PaymentGatewayInterface.php     # Abstração do gateway (Mercado Pago)
│       └── MessagePublisherInterface.php   # Abstração de despacho no RabbitMQ
│
├── Infrastructure/                         # Implementações Técnicas e Conectores
│   ├── Persistence/
│   │   ├── Models/
│   │   │   └── InvoiceMongoModel.php       # Model MongoDB (mongodb/laravel-mongodb)
│   │   └── Mappers/
│   │       └── InvoiceMapper.php           # Conversão Modelo Mongo <-> Agregado Domínio
│   ├── Repositories/
│   │   └── InvoiceMongoRepository.php      # Implementação real em MongoDB
│   ├── Gateways/
│   │   └── MercadoPagoGateway.php          # Comunicação com a API do Mercado Pago
│   └── Messaging/
│       ├── RabbitMQService.php             # Gerenciamento de conexões AMQP
│       ├── Publishers/
│       │   └── BillingRabbitMQPublisher.php# Publicador de eventos de faturamento
│       └── Consumers/
│           ├── WorkOrderCreatedConsumer.php# Consome criação de OS (Início faturamento)
│           └── ProductionAbortedConsumer.php# Consome cancelamento de reparo (Compensação)
│
└── Presentation/                           # Pontos de Entrada da Aplicação
    ├── Http/
    │   ├── Controllers/
    │   │   ├── PaymentWebhookController.php# Recepção assíncrona do Mercado Pago
    │   │   └── InvoiceController.php       # Endpoints REST de consulta
    │   └── Requests/
    │       └── MercadoPagoWebhookRequest.php# Sanitização e validação de Webhooks
    └── Console/Commands/
        └── StartBillingConsumers.php       # Worker CLI para consumo contínuo de filas
```

---

## 🔄 Saga Pattern & Fluxo de Mensageria

O `tc-billing-service` atua como o ponto decisório central na **Saga Coreografada**:

```
                       queue.workorder.created
  ┌──────────────────┐ ──────────────────────> ┌───────────────────────────┐
  │  tc-os-service   │                         │    tc-billing-service     │
  │     (MySQL)      │                         │         (MongoDB)         │
  └──────────────────┘                         └─────────────┬─────────────┘
          ▲                                                  │
          │                                                  │ Integração Webhook
          │                                                  ▼
          │                                    ┌───────────────────────────┐
          │                                    │        Mercado Pago       │
          │                                    │   (Gateway de Pagamento)  │
          │                                    └─────────────┬─────────────┘
          │                                                  │
          │ queue.payment.failed                             │ Retorno Webhook
          ├──────────────────────── [Pagamento Recusado] ────┤
          │                                                  │
          │                                                  │ [Pagamento Aprovado]
          │                                                  ▼
          │                                         queue.payment.approved
          │                                                  │
          │                                                  ▼
          │                                    ┌───────────────────────────┐
          │                                    │   tc-production-service   │
          │                                    │      (Linha de Reparo)    │
          │                                    └─────────────┬─────────────┘
          │                                                  │
          │            queue.production.aborted              │
          └──────────────── [Falha Crítica / Peça Falta] ────┴──────┐
                                                                    │
                                                                    ▼
                                                       (Compensação: Estorno)
                                                       queue.payment.refunded
```

### Filas RabbitMQ Envolvidas

| Fila | Papel | Descrição |
| :--- | :--- | :--- |
| `queue.workorder.created` | **Consumidor** | Recebe a notificação de abertura de OS e cria a fatura gerando preferência no Mercado Pago. |
| `queue.payment.approved` | **Publicador** | Notifica que o pagamento foi liquidado para que a produção técnica seja iniciada. |
| `queue.payment.failed` | **Publicador** | Dispara o rollback da Saga no `tc-os-service` cancelando a ordem de serviço. |
| `queue.production.aborted` | **Consumidor** | Aciona o caso de uso de compensação (`RefundPaymentUseCase`) emitindo estorno no Mercado Pago. |
| `queue.payment.refunded` | **Publicador** | Confirma a efetivação do estorno para auditoria e encerramento. |

---

## 🛠️ Stack Tecnológica

* **Linguagem**: PHP 8.4
* **Framework**: Laravel 10 / 11
* **Banco de Dados NoSQL**: MongoDB 6.0+ (armazenamento de documentos flexíveis com histórico de tentativas)
* **Driver MongoDB**: `mongodb/laravel-mongodb`
* **Mensageria**: RabbitMQ 3.x (Protocolo AMQP 0-9-1 via `php-amqplib/php-amqplib`)
* **Gateway de Pagamento**: Mercado Pago REST API v1/v2
* **Testes & Qualidade**: PHPUnit (> 80% de cobertura de código), SonarQube

---

## 🚀 Como Executar Localmente

### 1. Pré-requisitos
* Docker e Docker Compose instalados.
* Credenciais de sandbox / testes do Mercado Pago (`MERCADO_PAGO_ACCESS_TOKEN`).

### 2. Configurar Variáveis de Ambiente

Crie o arquivo `.env` a partir do template:

```bash
cp .env.example .env
```

Parâmetros essenciais no `.env`:

```ini
APP_NAME=tc-billing-service
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8001

# MongoDB NoSQL
DB_CONNECTION=mongodb
MONGODB_URI=mongodb://root:secret@mongo:27017
MONGODB_DATABASE=tc_billing

# RabbitMQ
RABBITMQ_HOST=rabbitmq
RABBITMQ_PORT=5672
RABBITMQ_USER=guest
RABBITMQ_PASSWORD=guest
RABBITMQ_VHOST=/

# Gateway Mercado Pago
MERCADO_PAGO_ACCESS_TOKEN=TEST-0000000000000000-000000-00000000000000000000000000000000-000000000
```

### 3. Subir os Containers Docker

```bash
docker compose up -d --build
```

### 4. Instalar Dependências e Gerar Chave da Aplicação

```bash
docker compose exec app composer install
docker compose exec app php artisan key:generate
```

### 5. Executar os Workers do RabbitMQ

Para escutar as mensagens da Saga em terminais dedicados (ou via supervisor):

```bash
# Executa todos os consumers configurados
docker compose exec app php artisan billing:consume --queue=all

# Ou executar de forma isolada por processo:
docker compose exec app php artisan billing:consume --queue=workorder-created
docker compose exec app php artisan billing:consume --queue=production-aborted
```

---

## 🧪 Testes Automatizados e Cobertura

O projeto adota uma suíte rigorosa de testes unitários para a camada de domínio e casos de uso:

```bash
# Executar todos os testes
docker compose exec app php artisan test

# Executar com validação de cobertura de código mínima de 80%
docker compose exec app php artisan test --coverage-text --min=80
```

---

## 📚 Documentação da API REST

### 1. Webhook do Mercado Pago

Recebe as notificações de atualização de status do checkout e atualiza a fatura de forma idempotente.

* **Método**: `POST`
* **Rota**: `/api/v1/payments/webhook`
* **Parâmetros aceitos (Query ou Body)**:

```json
{
  "action": "payment.updated",
  "type": "payment",
  "data": {
    "id": "1234567890"
  }
}
```

* **Resposta de Sucesso (`200 OK`)**:

```json
{
  "status": "acknowledged"
}
```

---

### 2. Consultar Fatura por ID do Documento

* **Método**: `GET`
* **Rota**: `/api/v1/invoices/{id}`
* **Headers**: `Accept: application/json`

**Resposta de Sucesso (`200 OK`)**:

```json
{
  "data": {
    "id": "64f1a2b3c4d5e6f7a8b9c0d1",
    "work_order_id": 10,
    "customer_id": 1,
    "amount": 271.00,
    "currency": "BRL",
    "status": "APPROVED",
    "preference_id": "pref_99887766",
    "payment_url": "https://www.mercadopago.com.br/checkout/v1/redirect?pref_id=pref_99887766",
    "attempts": [
      {
        "transaction_id": "1234567890",
        "status": "APPROVED",
        "gateway_message": "Payment successful",
        "created_at": "2026-11-10T14:32:00+00:00"
      }
    ],
    "created_at": "2026-11-10T14:30:00+00:00"
  }
}
```

---

### 3. Consultar Fatura por Ordem de Serviço

* **Método**: `GET`
* **Rota**: `/api/v1/invoices/work-order/{workOrderId}`
* **Headers**: `Accept: application/json`

**Resposta de Sucesso (`200 OK`)**:

```json
{
  "data": {
    "id": "64f1a2b3c4d5e6f7a8b9c0d1",
    "work_order_id": 10,
    "customer_id": 1,
    "amount": 271.00,
    "currency": "BRL",
    "status": "APPROVED",
    "preference_id": "pref_99887766",
    "payment_url": "https://www.mercadopago.com.br/checkout/v1/redirect?pref_id=pref_99887766",
    "attempts": [
      {
        "transaction_id": "1234567890",
        "status": "APPROVED",
        "gateway_message": "Payment successful",
        "created_at": "2026-11-10T14:32:00+00:00"
      }
    ],
    "created_at": "2026-11-10T14:30:00+00:00"
  }
}
```

**Resposta de Não Encontrado (`404 Not Found`)**:

```json
{
  "message": "Invoice for Work Order #999 not found."
}
```