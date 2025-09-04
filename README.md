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

## 🧪 Testes Implementados

### Testes Unitários

#### **IntegerMoneyTest** (`tests/Unit/Pricing/`)
Garante que todos os cálculos de preço usam apenas matemática de inteiros (centavos):
- ✅ Verifica que `unit_price_cents` é sempre inteiro
- ✅ Testa cálculos com pesos fracionários (0.5 oz)
- ✅ Valida múltiplas quantidades
- ✅ Previne problemas de ponto flutuante

### Testes de Feature

#### **QuoteExpiryTest** (`tests/Feature/Checkout/`)
Testa expiração de cotações com erro `REQUOTE_REQUIRED`:
- ✅ Rejeita cotações expiradas (409)
- ✅ Aceita cotações válidas
- ✅ Trata tempo exato de expiração como expirado
- ✅ Manipula tempo UTC corretamente

#### **ToleranceBreachTest** (`tests/Feature/Checkout/`)
Valida tolerância de movimento do preço spot:
- ✅ Rejeita quando spot move além da tolerância (409)
- ✅ Aceita movimento dentro da tolerância
- ✅ Testa aumentos e diminuições de preço
- ✅ Calcula basis points corretamente

#### **IdempotencyTest** (`tests/Feature/Checkout/`)
Garante idempotência com `Idempotency-Key`:
- ✅ Retorna mesmo `order_id` para chave duplicada
- ✅ Cria ordens diferentes para chaves diferentes
- ✅ Manipula requisições concorrentes
- ✅ Funciona através de diferentes `quote_id`

#### **InventoryCheckTest** (`tests/Feature/Checkout/`)
Valida verificação de estoque com erro `OUT_OF_STOCK`:
- ✅ Rejeita quando estoque insuficiente (409)
- ✅ Rejeita quando quantidade > estoque
- ✅ Aceita quando quantidade ≤ estoque
- ✅ Trata erros da API como falta de estoque
- ✅ Não cria ordens quando falha verificação

#### **SignatureTest** (`tests/Feature/Webhooks/`)
Testa webhooks com HMAC válido:
- ✅ Processa `payment_authorized` → status `authorized`
- ✅ Processa `payment_captured` apenas de `authorized` → `captured`
- ✅ Rejeita transições ilegais de status
- ✅ Aceita eventos não suportados sem erro
- ✅ Rejeita assinatura inválida ou intent desconhecido

#### **InvalidSignatureTest** (`tests/Feature/Webhooks/`)
Garante retorno de 400 e nenhuma mudança de estado:
- ✅ Assinatura completamente inválida
- ✅ Payload adulterado após assinatura
- ✅ Assinatura malformada (não hex)
- ✅ Intent desconhecido com assinatura válida
- ✅ Múltiplas tentativas inválidas preservam estado

#### **TotalsIntegrityTest** (`tests/Feature/Checkout/`)
Verifica integridade de totais e cálculos:
- ✅ `orders.total_cents == sum(order_lines.subtotal_cents)`
- ✅ `order_lines.subtotal_cents == unit_price_cents * quantity`
- ✅ Funciona com itens únicos e múltiplos
- ✅ Testa diferentes tipos de produto
- ✅ Valida grandes quantidades sem erro de arredondamento

### Execução dos Testes

```bash
# Todos os testes
docker exec dogandrooster-laravel.test-1 php artisan test

# Apenas testes unitários
docker exec dogandrooster-laravel.test-1 php artisan test --testsuite=Unit

# Apenas testes de feature
docker exec dogandrooster-laravel.test-1 php artisan test --testsuite=Feature

# Teste específico
docker exec dogandrooster-laravel.test-1 php artisan test tests/Unit/Pricing/IntegerMoneyTest.php

# Com cobertura (se configurado)
docker exec dogandrooster-laravel.test-1 php artisan test --coverage
```

### Verificação de Qualidade

```bash
# Laravel Pint (Style)
docker exec dogandrooster-laravel.test-1 php ./vendor/bin/pint

# Larastan (Static Analysis)
docker exec dogandrooster-laravel.test-1 php ./vendor/bin/phpstan analyse --level=6

# Executar todos juntos
docker exec dogandrooster-laravel.test-1 bash -c "php ./vendor/bin/pint && php ./vendor/bin/phpstan analyse --level=6 && php artisan test"
```

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

## 🧪 Resultado dos Testes Unitários

### PHP Code Style (Laravel Pint)

```bash
# ./vendor/bin/pint

  ..✓✓✓✓...........✓.✓✓✓✓✓.........................✓✓✓✓.....✓✓✓✓✓.✓✓.✓.............✓...

  ──────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────────── Laravel  
    FIXED   .................................................................................................................................................................................. 85 files, 23 style issues fixed  
  ✓ app/Http/Controllers/Api/CheckoutController.php no_superfluous_phpdoc_tags, concat_space, method_chaining_indentation, no_trailing_whitespace, phpdoc_separation, phpdoc_trim, not_operator_with_successor_space, blank_l…  
  ✓ app/Http/Controllers/Api/MockFulfillmentController.php                                                                                                            no_superfluous_phpdoc_tags, phpdoc_trim, ordered_imports  
  ✓ app/Http/Controllers/Api/QuoteController.php                                                                                                         phpdoc_separation, not_operator_with_successor_space, ordered_imports  
  ✓ app/Http/Controllers/Api/WebhookController.php    no_superfluous_phpdoc_tags, phpdoc_trim, no_unused_imports, not_operator_with_successor_space, blank_line_before_statement, ordered_imports, no_whitespace_in_blank_line  
  ✓ app/Http/Requests/Api/CheckoutRequest.php                                                                                                                                          no_superfluous_phpdoc_tags, phpdoc_trim  
  ✓ app/Models/Order.php                                                                                concat_space, line_ending, not_operator_with_successor_space, blank_line_before_statement, no_whitespace_in_blank_line  
  ✓ app/Models/OrderLine.php                                                                                          line_ending, not_operator_with_successor_space, blank_line_before_statement, no_whitespace_in_blank_line  
  ✓ app/Models/PriceQuote.php                                                                                                                  not_operator_with_successor_space, ordered_imports, no_whitespace_in_blank_line  
  ✓ app/Models/Product.php                                                                                                                                                                         no_whitespace_in_blank_line  
  ✓ app/Models/SpotPrice.php                                                                                                                         method_chaining_indentation, ordered_imports, no_whitespace_in_blank_line  
  ✓ database/factories/OrderLineFactory.php                                                                                                                                                        no_whitespace_in_blank_line  
  ✓ database/factories/PriceQuoteFactory.php                                                                                                                                                       no_whitespace_in_blank_line  
  ✓ database/factories/ProductFactory.php                                                                                                                                                          no_whitespace_in_blank_line  
  ✓ database/factories/SpotPriceFactory.php                                                                                                                                 method_argument_space, no_whitespace_in_blank_line  
  ✓ database/migrations/2025_09_03_151500_create_products_table.php                                                                                class_definition, line_ending, braces_position, no_whitespace_in_blank_line  
  ✓ database/migrations/2025_09_03_151600_create_spot_prices_table.php                                                                             class_definition, line_ending, braces_position, no_whitespace_in_blank_line  
  ✓ database/migrations/2025_09_03_151700_create_price_quotes_table.php                                                                            class_definition, line_ending, braces_position, no_whitespace_in_blank_line  
  ✓ database/migrations/2025_09_03_151800_create_orders_table.php                                                     class_definition, line_ending, method_chaining_indentation, braces_position, no_whitespace_in_blank_line  
  ✓ database/migrations/2025_09_03_151900_create_order_lines_table.php                                                                             class_definition, line_ending, braces_position, no_whitespace_in_blank_line  
  ✓ database/seeders/ProductSeeder.php                                                                                                                                                            line_ending, ordered_imports  
  ✓ database/seeders/SpotPriceSeeder.php                                                                                                line_ending, method_chaining_indentation, ordered_imports, no_whitespace_in_blank_line  
  ✓ routes/api.php                                                                                                                                                                     no_trailing_whitespace, ordered_imports  
  ✓ tests/Feature/Webhooks/SignatureTest.php                                                                                                                                                       no_whitespace_in_blank_line  
```

### Resumo dos Testes

- **85 arquivos** verificados pelo Pint
- **23 problemas de estilo** corrigidos automaticamente
- **Cobertura completa** de controllers, models, factories, migrations e seeders
- **Padrão Laravel** aplicado consistentemente em todo o projeto

### Principais Correções Aplicadas

- **PHPDoc**: Limpeza e padronização de comentários
- **Espaçamento**: Correção de indentação e espaços em branco
- **Imports**: Organização e remoção de imports não utilizados
- **Formatação**: Padronização de quebras de linha e chaves
- **Operadores**: Espaçamento consistente de operadores

## 🧪 Resultados dos Testes PHPUnit

### Execução Completa dos Testes

```bash
# php artisan test

   PASS  Tests\Unit\ExampleTest
  ✓ that true is true                                                                                                                                                                                                    0.16s  

   PASS  Tests\Unit\Pricing\IntegerMoneyTest
  ✓ it calculates unit price with integer cents only                                                                                                                                                                     9.49s  
  ✓ it uses only integer math in pricing calculation                                                                                                                                                                     0.14s  
  ✓ it maintains integer precision with multiple quantities                                                                                                                                                              0.13s  
  ✓ it avoids floating point numbers in pricing                                                                                                                                                                          0.16s  

   PASS  Tests\Feature\Api\CheckoutTest
  ✓ it requires idempotency key                                                                                                                                                                                          0.36s  
  ✓ it creates order successfully                                                                                                                                                                                        0.51s  
  ✓ it enforces idempotency                                                                                                                                                                                              0.21s  
  ✓ it rejects expired quotes                                                                                                                                                                                            0.20s  
  ✓ it rejects when price tolerance exceeded                                                                                                                                                                             0.19s  
  ✓ it validates quote exists                                                                                                                                                                                            0.27s  

   PASS  Tests\Feature\Api\QuoteTest
  ✓ it can generate a price quote                                                                                                                                                                                        0.23s  
  ✓ it validates required fields                                                                                                                                                                                         0.19s  
  ✓ it returns 404 for invalid sku                                                                                                                                                                                       0.18s  
  ✓ it validates quantity bounds                                                                                                                                                                                         0.20s  

   PASS  Tests\Feature\Checkout\IdempotencyTest
  ✓ it returns same order for duplicate idempotency key                                                                                                                                                                  0.31s  
  ✓ it creates different orders for different idempotency keys                                                                                                                                                           0.25s  
  ✓ it handles concurrent requests with same idempotency key                                                                                                                                                             0.29s  
  ✓ it enforces idempotency across different quote ids                                                                                                                                                                   0.28s  

   PASS  Tests\Feature\Checkout\InventoryCheckTest
  ✓ it rejects checkout when insufficient inventory                                                                                                                                                                      0.27s  
  ✓ it rejects checkout when requested quantity exceeds inventory                                                                                                                                                        0.23s  
  ✓ it accepts checkout when sufficient inventory available                                                                                                                                                              0.21s  
  ✓ it accepts checkout when requested quantity equals inventory                                                                                                                                                         0.24s  
  ✓ it handles fulfillment api errors as out of stock                                                                                                                                                                    0.28s  
  ✓ it validates inventory before creating order                                                                                                                                                                         0.29s  

   PASS  Tests\Feature\Checkout\QuoteExpiryTest
  ✓ it rejects expired quotes with requote required error                                                                                                                                                                0.26s  
  ✓ it accepts quotes that are still valid                                                                                                                                                                               0.24s  
  ✓ it treats exact expiry time as expired                                                                                                                                                                               0.29s  
  ✓ it handles utc time correctly for expiry                                                                                                                                                                             0.26s  

   PASS  Tests\Feature\Checkout\ToleranceBreachTest
  ✓ it rejects quotes when spot moves beyond tolerance                                                                                                                                                                   0.24s  
  ✓ it accepts quotes when spot moves within tolerance                                                                                                                                                                   0.22s  
  ✓ it handles spot price decreases beyond tolerance                                                                                                                                                                     0.26s  
  ✓ it calculates tolerance correctly for different basis points                                                                                                                                                         0.27s  

   PASS  Tests\Feature\Checkout\TotalsIntegrityTest
  ✓ it ensures order total equals sum of order lines subtotals                                                                                                                                                           0.30s  
  ✓ it ensures order line subtotal equals unit price times quantity                                                                                                                                                      0.27s  
  ✓ it maintains integrity with single item orders                                                                                                                                                                       0.22s  
  ✓ it maintains integrity with different product types                                                                                                                                                                  0.29s  
  ✓ it maintains integrity with large quantities                                                                                                                                                                         0.25s  
  ✓ it ensures no rounding errors in calculations                                                                                                                                                                        0.32s  

   PASS  Tests\Feature\ExampleTest
  ✓ the application returns a successful response                                                                                                                                                                        0.83s  

   PASS  Tests\Feature\Webhooks\InvalidSignatureTest
  ✓ it returns 400 for invalid signature and no state change                                                                                                                                                             0.27s  
  ✓ it returns 400 for tampered payload and no state change                                                                                                                                                              0.26s  
  ✓ it returns 400 for malformed signature and no state change                                                                                                                                                           0.27s  
  ✓ it returns 400 for empty signature and no state change                                                                                                                                                               0.28s  
  ✓ it returns 400 for unknown intent with valid signature and no state change                                                                                                                                           0.23s  
  ✓ it preserves order state across multiple invalid attempts                                                                                                                                                            0.31s  

   PASS  Tests\Feature\Webhooks\SignatureTest
  ✓ it processes payment authorized webhook with valid signature                                                                                                                                                         0.29s  
  ✓ it processes payment captured webhook with valid signature                                                                                                                                                           0.42s  
  ✓ it only allows payment captured from authorized status                                                                                                                                                               0.22s  
  ✓ it rejects webhook with invalid signature                                                                                                                                                                            0.21s  
  ✓ it rejects webhook with unknown payment intent                                                                                                                                                                       0.18s  
  ✓ it rejects webhook without signature header                                                                                                                                                                          0.24s  
  ✓ it handles unsupported webhook events                                                                                                                                                                                0.28s  

  Tests:    53 passed (158 assertions)
  Duration: 25.27s
```

### 📊 Análise dos Resultados

- **✅ 53 testes passaram** com 100% de sucesso
- **📈 158 assertions** executadas e validadas
- **⏱️ 25.27s** de duração total
- **🎯 0 falhas** - Todos os requisitos implementados

### 🏆 Cobertura de Testes por Categoria

| Categoria | Testes | Status | Funcionalidade Testada |
|-----------|---------|--------|------------------------|
| **Unit/ExampleTest** | 1 | ✅ PASS | Testes básicos de sanidade |
| **Unit/Pricing/IntegerMoneyTest** | 4 | ✅ PASS | Matemática de preços com integers |
| **Feature/Api/CheckoutTest** | 6 | ✅ PASS | Fluxo principal de checkout da API |
| **Feature/Api/QuoteTest** | 4 | ✅ PASS | Geração e validação de cotações |
| **Feature/Checkout/IdempotencyTest** | 4 | ✅ PASS | Chaves de idempotência e concorrência |
| **Feature/Checkout/InventoryCheckTest** | 6 | ✅ PASS | Validação de estoque via API mock |
| **Feature/Checkout/QuoteExpiryTest** | 4 | ✅ PASS | Expiração de cotações e UTC |
| **Feature/Checkout/ToleranceBreachTest** | 4 | ✅ PASS | Validação de tolerância de preços |
| **Feature/Checkout/TotalsIntegrityTest** | 6 | ✅ PASS | Integridade dos cálculos de totais |
| **Feature/ExampleTest** | 1 | ✅ PASS | Testes de integração básicos |
| **Feature/Webhooks/InvalidSignatureTest** | 6 | ✅ PASS | Segurança de webhooks (casos negativos) |
| **Feature/Webhooks/SignatureTest** | 7 | ✅ PASS | Segurança de webhooks (casos positivos) |

### 🔍 Requisitos Funcionais Validados

✅ **Quote Generation** - Cotações com cálculo correto de preços usando matemática de inteiros  
✅ **Quote Expiry** - Validação de expiração em 5 minutos com UTC  
✅ **Price Tolerance** - Verificação de basis points para volatilidade do mercado  
✅ **Inventory Check** - Integração com API mock de fulfillment  
✅ **Idempotent Checkout** - Transações seguras com chaves de idempotência  
✅ **Payment Webhooks** - Verificação HMAC e transições de status válidas  
✅ **Error Handling** - Códigos HTTP corretos e mensagens de erro apropriadas  
✅ **Data Integrity** - Consistência de totais e cálculos matemáticos
