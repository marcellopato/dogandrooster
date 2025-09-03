# Ecom Volatile Pricing - Precious Metals Checkout

Um sistema de checkout de e-commerce para metais preciosos com precificação volátil e cotações com prazo de validade limitado.

## 🎯 Visão Geral do Projeto

Este projeto implementa uma fatia de checkout para e-commerce de metais preciosos que lida com:

- **Cotações travadas** válidas por 5 minutos
- **Preços voláteis** indexados ao mercado spot
- **Verificação de estoque** em tempo real via API mock
- **Checkout idempotente** e transacional
- **Webhooks de pagamento** com verificação HMAC
- **Interface responsiva** com countdown e tratamento de erros

## 🛠️ Stack Tecnológica

### Backend
- **Laravel 10** - Framework PHP
- **MySQL 8.0** - Banco de dados principal
- **Redis** - Cache e sessions
- **Laravel Sanctum** - Autenticação API
- **L5-Swagger** - Documentação da API

### Frontend  
- **Vue.js 3** - Framework JavaScript
- **TailwindCSS** - Framework CSS
- **Vite** - Build tool

### DevOps
- **Docker + Laravel Sail** - Containerização
- **Mailpit** - Testing de emails

## 📋 Instalação e Setup

### Pré-requisitos
- Docker Desktop
- Node.js 18+
- Git

### 1. Clone o repositório
```bash
git clone <repo-url>
cd dogandrooster
```

### 2. Configuração do ambiente
```bash
# Copiar arquivo de ambiente
cp .env.example .env

# Gerar chave da aplicação
php artisan key:generate
```

### 3. Configurar Docker/Sail
```bash
# Instalar dependências do Composer
composer install

# Levantar containers
docker-compose up -d

# Executar migrações
docker-compose exec laravel.test php artisan migrate

# Executar seeders (quando disponíveis)
docker-compose exec laravel.test php artisan db:seed
```

### 4. Configurar Frontend
```bash
# Instalar dependências
npm install

# Executar em modo desenvolvimento
npm run dev
```

## 🚀 Executando o Projeto

### Desenvolvimento
```bash
# Backend (containers Docker)
docker-compose up -d

# Frontend (Vite dev server)
npm run dev
```

### URLs Importantes
- **Aplicação**: http://localhost
- **Demo Vue**: http://localhost/demo
- **API Docs (Swagger)**: http://localhost/api/documentation
- **Mailpit**: http://localhost:8025
- **MySQL**: localhost:3306
- **Redis**: localhost:6379

## 📡 Endpoints da API

### 1. Cotação
```http
POST /api/quote
Content-Type: application/json

{
  "sku": "GOLD_1OZ",
  "qty": 1
}
```

**Resposta:**
```json
{
  "quote_id": "uuid",
  "unit_price_cents": 200000,
  "quote_expires_at": "2025-09-03T15:25:00Z"
}
```

### 2. Checkout
```http
POST /api/checkout
Content-Type: application/json
Idempotency-Key: unique-key

{
  "quote_id": "uuid"
}
```

### 3. Webhooks de Pagamento
```http
POST /api/webhooks/payments
X-Signature: hmac-sha256-signature

{
  "event": "payment_authorized",
  "payment_intent_id": "uuid"
}
```

### 4. Mock Fulfillment
```http
GET /api/mock-fulfillment/availability/{sku}
POST /api/mock-fulfillment/availability
```

## 🧪 Executando Testes

```bash
# Todos os testes
docker-compose exec laravel.test php artisan test

# Testes específicos
docker-compose exec laravel.test php artisan test --filter=QuoteTest

# Com coverage
docker-compose exec laravel.test php artisan test --coverage
```

### Testes Implementados
- ✅ `Pricing/IntegerMoneyTest` - Matemática inteira para preços
- ✅ `Checkout/QuoteExpiryTest` - Expiração de cotações
- ✅ `Checkout/ToleranceBreachTest` - Violação de tolerância
- ✅ `Checkout/IdempotencyTest` - Idempotência do checkout
- ✅ `Checkout/InventoryCheckTest` - Verificação de estoque
- ✅ `Webhooks/SignatureTest` - Validação de assinaturas
- 🆕 `Webhooks/InvalidSignatureTest` - Assinaturas inválidas
- 🆕 `Checkout/TotalsIntegrityTest` - Integridade dos totais

## ⚙️ Configurações Importantes

### Variáveis de Ambiente (.env)
```env
# Database
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel

# Payment Webhook
PAYMENT_WEBHOOK_SECRET=your-secret-key

# Spot Pricing (mock)
SPOT_PRICE_TOLERANCE_BPS=50
QUOTE_EXPIRY_MINUTES=5
```

## 🏗️ Arquitetura e Decisões Técnicas

### Concorrência e Idempotência
- **Row Locking**: Uso de `SELECT ... FOR UPDATE` durante checkout
- **Transações DB**: Todas as operações críticas são envolvidas em transações
- **Idempotency Keys**: Headers únicos previnem duplicação de pedidos
- **Optimistic Locking**: Verificação de versão de preços spot

### Precisão Financeira
- **Apenas inteiros**: Todos os cálculos em centavos (sem decimais)
- **Matemática segura**: Multiplicações e divisões controladas
- **Basis Points**: Tolerância de preço em pontos base (1 bp = 0.01%)

### Tratamento de Erros
- **4xx para business logic**: Erros esperados retornam códigos apropriados
- **Error codes específicos**: `REQUOTE_REQUIRED`, `OUT_OF_STOCK`, etc.
- **Friendly messages**: Interface converte códigos em mensagens amigáveis
- **Fail-fast**: Validações rápidas antes de operações caras

### Observabilidade
- **Structured Logging**: Logs estruturados para fulfillment e webhooks
- **Health Checks**: Containers com health checks configurados
- **Error Tracking**: Logs de erro detalhados para debugging

## 🎨 Interface do Usuário

### Recursos Implementados
- **Countdown Timer**: Mostra tempo restante da cotação (mm:ss)
- **Estados de Loading**: Botões desabilitados durante requisições
- **Mensagens Amigáveis**: Tradução de códigos de erro para linguagem natural
- **Acessibilidade**: `role="alert"`, focusable, screen reader friendly
- **Responsivo**: Design adaptável para mobile e desktop

### Fluxo do Usuário
1. **Seleção**: Escolher SKU e quantidade
2. **Cotação**: Obter preço com prazo de 5 minutos
3. **Countdown**: Visualizar tempo restante
4. **Checkout**: Processar pedido ou renovar cotação
5. **Feedback**: Receber confirmação ou instrução de erro

## 🔒 Segurança

### HMAC Verification
```php
$signature = 'sha256=' . hash_hmac('sha256', $payload, config('app.webhook_secret'));
if (!hash_equals($signature, $providedSignature)) {
    abort(400, 'Invalid signature');
}
```

### CSRF Protection
- **API**: Token CSRF em headers
- **Forms**: `@csrf` directive em formulários Blade

### Rate Limiting
- **API endpoints**: Throttling configurado por IP/usuário
- **Webhook endpoints**: Rate limiting específico

## 📊 Monitoramento e Logs

### Logs Importantes
- **Fulfillment Calls**: `LOG::info('Checking inventory', ['sku' => $sku])`
- **Webhook Results**: `LOG::info('Webhook processed', ['event' => $event])`
- **Quote Creation**: Criação e expiração de cotações
- **Checkout Process**: Sucesso/falha de checkout

### Métricas Sugeridas
- Taxa de conversão de cotação → checkout
- Tempo médio de resposta da API
- Frequência de requotes por tolerância
- Disponibilidade do serviço de fulfillment

## 🚧 Melhorias Futuras

### Com Mais Tempo
1. **Cache Inteligente**: Redis para preços spot com TTL
2. **Queue System**: Background jobs para webhooks
3. **Event Sourcing**: Histórico completo de mudanças de preço
4. **Circuit Breaker**: Proteção contra falhas do fulfillment
5. **Multi-currency**: Suporte a múltiplas moedas
6. **Advanced UI**: Gráficos de preço, histórico de cotações
7. **Mobile App**: PWA ou app nativo
8. **Analytics**: Dashboard de métricas em tempo real

### Escalabilidade
- **Horizontal Scaling**: Load balancer + múltiplas instâncias
- **Database Sharding**: Particionamento por região/produto
- **CDN**: Cache de assets estáticos
- **Microservices**: Separação de pricing, inventory, payments

## 🤝 Contribuindo

1. Criar nova branch: `git checkout -b feature/nova-funcionalidade`
2. Fazer commit: `git commit -m 'feat: nova funcionalidade'`
3. Push: `git push origin feature/nova-funcionalidade`
4. Abrir Pull Request

### Code Style
```bash
# Laravel Pint (PHP)
docker-compose exec laravel.test ./vendor/bin/pint

# Larastan (Static Analysis)
docker-compose exec laravel.test ./vendor/bin/phpstan analyse
```

## 📄 Licença

Este projeto está sob a licença MIT. Veja [LICENSE](LICENSE) para mais detalhes.

## 🛟 Suporte

Para dúvidas ou problemas:
1. Verificar [Issues existentes](https://github.com/owner/repo/issues)
2. Criar nova issue com template apropriado
3. Consultar documentação da API em `/api/documentation`

---

**Desenvolvido com ❤️ usando Laravel + Vue.js + TailwindCSS**