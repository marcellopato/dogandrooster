# Ecom Volatile Pricing - Precious Metals Checkout

An e-commerce checkout system for precious metals with volatile pricing and time-limited quotes.

## 🎯 Project Overview

This project implements a checkout slice for precious metals e-commerce that handles:

- **Locked quotes** valid for 5 minutes
- **Volatile prices** indexed to spot market
- **Real-time inventory check** via mock API
- **Idempotent and transactional** checkout
- **Payment webhooks** with HMAC verification
- **Responsive interface** with countdown and error handling

## 🛠️ Technology Stack

### Backend
- **Laravel 10** - PHP Framework
- **MySQL 8.0** - Primary database
- **Redis** - Cache and sessions
- **Laravel Sanctum** - API authentication
- **L5-Swagger** - API documentation

### Frontend  
- **Vue.js 3** - JavaScript framework
- **TailwindCSS** - CSS framework
- **Vite** - Build tool

### DevOps
- **Docker + Laravel Sail** - Containerization
- **Mailpit** - Email testing

## 📋 Installation and Setup

### Prerequisites
- Docker Desktop
- Node.js 18+
- Git

### 1. Clone the repository
```bash
git clone <[repo-url](https://github.com/marcellopato/dogandrooster)>
cd dogandrooster
```

### 2. Environment configuration
```bash
# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 3. Configure Docker/Sail
```bash
# Install Composer dependencies
composer install

# Start containers
docker-compose up -d

# Run migrations
docker-compose exec laravel.test php artisan migrate

# Run seeders (when available)
docker-compose exec laravel.test php artisan db:seed
```

### 4. Configure Frontend
```bash
# Install dependencies
npm install

# Run in development mode
npm run dev
```

## 🚀 Running the Project

### Development
```bash
# Backend (Docker containers)
docker-compose up -d

# Frontend (Vite dev server)
npm run dev
```

### Important URLs
- **Application**: http://localhost
- **Vue Demo**: http://localhost/demo
- **API Docs (Swagger)**: http://localhost/api/documentation
- **Mailpit**: http://localhost:8025
- **MySQL**: localhost:3306
- **Redis**: localhost:6379

## 📡 API Endpoints

### 1. Quote
```http
POST /api/quote
Content-Type: application/json

{
  "sku": "GOLD_1OZ",
  "qty": 1
}
```

**Response:**
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

### 3. Payment Webhooks
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

## 🧪 Running Tests

```bash
# All tests
docker-compose exec laravel.test php artisan test

# Specific tests
docker-compose exec laravel.test php artisan test --filter=QuoteTest

# With coverage
docker-compose exec laravel.test php artisan test --coverage
```

### Implemented Tests
- ✅ `Pricing/IntegerMoneyTest` - Integer math for prices
- ✅ `Checkout/QuoteExpiryTest` - Quote expiration
- ✅ `Checkout/ToleranceBreachTest` - Tolerance breach
- ✅ `Checkout/IdempotencyTest` - Checkout idempotency
- ✅ `Checkout/InventoryCheckTest` - Inventory verification
- ✅ `Webhooks/SignatureTest` - Signature validation
- 🆕 `Webhooks/InvalidSignatureTest` - Invalid signatures
- 🆕 `Checkout/TotalsIntegrityTest` - Totals integrity

## ⚙️ Important Configurations

### Environment Variables (.env)
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

## 🏗️ Architecture and Technical Decisions

### Concurrency and Idempotency
- **Row Locking**: Use of `SELECT ... FOR UPDATE` during checkout
- **DB Transactions**: All critical operations are wrapped in transactions
- **Idempotency Keys**: Unique headers prevent order duplication
- **Optimistic Locking**: Spot price version verification

### Financial Precision
- **Integers only**: All calculations in cents (no decimals)
- **Safe math**: Controlled multiplications and divisions
- **Basis Points**: Price tolerance in basis points (1 bp = 0.01%)

### Error Handling
- **4xx for business logic**: Expected errors return appropriate codes
- **Specific error codes**: `REQUOTE_REQUIRED`, `OUT_OF_STOCK`, etc.
- **Friendly messages**: Interface converts codes to user-friendly messages
- **Fail-fast**: Quick validations before expensive operations

### Observability
- **Structured Logging**: Structured logs for fulfillment and webhooks
- **Health Checks**: Containers with configured health checks
- **Error Tracking**: Detailed error logs for debugging

## 🎨 User Interface

### Implemented Features
- **Countdown Timer**: Shows remaining quote time (mm:ss)
- **Loading States**: Disabled buttons during requests
- **Friendly Messages**: Translation of error codes to natural language
- **Accessibility**: `role="alert"`, focusable, screen reader friendly
- **Responsive**: Adaptive design for mobile and desktop

### User Flow
1. **Selection**: Choose SKU and quantity
2. **Quote**: Get price with 5-minute deadline
3. **Countdown**: View remaining time
4. **Checkout**: Process order or renew quote
5. **Feedback**: Receive confirmation or error instruction

## 🔒 Security

### HMAC Verification
```php
$signature = 'sha256=' . hash_hmac('sha256', $payload, config('app.webhook_secret'));
if (!hash_equals($signature, $providedSignature)) {
    abort(400, 'Invalid signature');
}
```

### CSRF Protection
- **API**: CSRF token in headers
- **Forms**: `@csrf` directive in Blade forms

### Rate Limiting
- **API endpoints**: Throttling configured per IP/user
- **Webhook endpoints**: Specific rate limiting

## 📊 Monitoring and Logs

### Important Logs
- **Fulfillment Calls**: `LOG::info('Checking inventory', ['sku' => $sku])`
- **Webhook Results**: `LOG::info('Webhook processed', ['event' => $event])`
- **Quote Creation**: Quote creation and expiration
- **Checkout Process**: Checkout success/failure

### Suggested Metrics
- Quote to checkout conversion rate
- Average API response time
- Requote frequency due to tolerance
- Fulfillment service availability

## 🧪 Implemented Tests

### Unit Tests

#### **IntegerMoneyTest** (`tests/Unit/Pricing/`)
Ensures all price calculations use only integer math (cents):
- ✅ Verifies that `unit_price_cents` is always an integer  
- ✅ Tests calculations with fractional weights (0.5 oz)  
- ✅ Validates multiple quantities  
- ✅ Prevents floating-point issues  

### Feature Tests

#### **QuoteExpiryTest** (`tests/Feature/Checkout/`)
Tests quote expiration with `REQUOTE_REQUIRED` error:
- ✅ Rejects expired quotes (409)  
- ✅ Accepts valid quotes  
- ✅ Treats exact expiration time as expired  
- ✅ Handles UTC time correctly  

#### **ToleranceBreachTest** (`tests/Feature/Checkout/`)
Validates spot price movement tolerance:
- ✅ Rejects when spot moves beyond tolerance (409)  
- ✅ Accepts movement within tolerance  
- ✅ Tests both price increases and decreases  
- ✅ Calculates basis points correctly  

#### **IdempotencyTest** (`tests/Feature/Checkout/`)
Ensures idempotency with `Idempotency-Key`:
- ✅ Returns same `order_id` for duplicate key  
- ✅ Creates different orders for different keys  
- ✅ Handles concurrent requests  
- ✅ Works across different `quote_id`s  

#### **InventoryCheckTest** (`tests/Feature/Checkout/`)
Validates stock check with `OUT_OF_STOCK` error:
- ✅ Rejects when stock is insufficient (409)  
- ✅ Rejects when quantity > stock  
- ✅ Accepts when quantity ≤ stock  
- ✅ Treats API errors as out of stock  
- ✅ Does not create orders when stock check fails  

#### **SignatureTest** (`tests/Feature/Webhooks/`)
Tests webhooks with valid HMAC:
- ✅ Processes `payment_authorized` → status `authorized`  
- ✅ Processes `payment_captured` only from `authorized` → `captured`  
- ✅ Rejects illegal status transitions  
- ✅ Accepts unsupported events without error  
- ✅ Rejects invalid signature or unknown intent  

#### **InvalidSignatureTest** (`tests/Feature/Webhooks/`)
Ensures 400 response and no state changes:
- ✅ Completely invalid signature  
- ✅ Payload tampered after signing  
- ✅ Malformed signature (non-hex)  
- ✅ Unknown intent with valid signature  
- ✅ Multiple invalid attempts preserve state  

#### **TotalsIntegrityTest** (`tests/Feature/Checkout/`)
Checks totals and calculation integrity:
- ✅ `orders.total_cents == sum(order_lines.subtotal_cents)`  
- ✅ `order_lines.subtotal_cents == unit_price_cents * quantity`  
- ✅ Works with single and multiple items  
- ✅ Tests different product types  
- ✅ Validates large quantities without rounding errors  

### Test Execution

```bash
# All tests
docker exec dogandrooster-laravel.test-1 php artisan test

# Unit tests only
docker exec dogandrooster-laravel.test-1 php artisan test --testsuite=Unit

# Feature tests only
docker exec dogandrooster-laravel.test-1 php artisan test --testsuite=Feature

# Specific test
docker exec dogandrooster-laravel.test-1 php artisan test tests/Unit/Pricing/IntegerMoneyTest.php

# With coverage (if configured)
docker exec dogandrooster-laravel.test-1 php artisan test --coverage

### Quality Check

# Laravel Pint (Style)
docker exec dogandrooster-laravel.test-1 php ./vendor/bin/pint

# Larastan (Static Analysis)
docker exec dogandrooster-laravel.test-1 php ./vendor/bin/phpstan analyse --level=6

# Run all together
docker exec dogandrooster-laravel.test-1 bash -c "php ./vendor/bin/pint && php ./vendor/bin/phpstan analyse --level=6 && php artisan test"


### With More Time
1. **Smart Cache**: Redis for spot prices with TTL
2. **Queue System**: Background jobs for webhooks
3. **Event Sourcing**: Complete price change history
4. **Circuit Breaker**: Protection against fulfillment failures
5. **Multi-currency**: Support for multiple currencies
6. **Advanced UI**: Price charts, quote history
7. **Mobile App**: PWA or native app
8. **Analytics**: Real-time metrics dashboard

### Scalability
- **Horizontal Scaling**: Load balancer + multiple instances
- **Database Sharding**: Partitioning by region/product
- **CDN**: Static assets caching
- **Microservices**: Separation of pricing, inventory, payments

## 🤝 Contributing

1. Create new branch: `git checkout -b feature/new-feature`
2. Make commit: `git commit -m 'feat: new feature'`
3. Push: `git push origin feature/new-feature`
4. Open Pull Request

### Code Style
```bash
# Laravel Pint (PHP)
docker-compose exec laravel.test ./vendor/bin/pint

# Larastan (Static Analysis)
docker-compose exec laravel.test ./vendor/bin/phpstan analyse
```

## 📄 License

This project is under the MIT license. See [LICENSE](LICENSE) for more details.

## 🛟 Support

For questions or issues:
1. Check [existing Issues](https://github.com/owner/repo/issues)
2. Create new issue with appropriate template
3. Consult API documentation at `/api/documentation`

---

**Developed with ❤️ using Laravel + Vue.js + TailwindCSS**


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

### Test Summary

- **85 files** checked by Pint  
- **23 style issues** automatically fixed  
- **Full coverage** of controllers, models, factories, migrations, and seeders  
- **Laravel standard** consistently applied across the entire project  

### Main Fixes Applied

- **PHPDoc**: Cleanup and standardization of comments  
- **Spacing**: Fixed indentation and whitespace  
- **Imports**: Organized and removed unused imports  
- **Formatting**: Standardized line breaks and braces  
- **Operators**: Consistent operator spacing  

## 🧪 PHPUnit Test Results

### Full Test Execution


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

### 📊 Results Analysis

- **✅ 53 tests passed** with 100% success  
- **📈 158 assertions** executed and validated  
- **⏱️ 25.27s** total duration  
- **🎯 0 failures** - All requirements implemented  

### 🏆 Test Coverage by Category

| Category | Tests | Status | Functionality Tested |
|----------|-------|--------|-----------------------|
| **Unit/ExampleTest** | 1 | ✅ PASS | Basic sanity tests |
| **Unit/Pricing/IntegerMoneyTest** | 4 | ✅ PASS | Integer-based price math |
| **Feature/Api/CheckoutTest** | 6 | ✅ PASS | Main API checkout flow |
| **Feature/Api/QuoteTest** | 4 | ✅ PASS | Quote generation and validation |
| **Feature/Checkout/IdempotencyTest** | 4 | ✅ PASS | Idempotency keys and concurrency |
| **Feature/Checkout/InventoryCheckTest** | 6 | ✅ PASS | Stock validation via mock API |
| **Feature/Checkout/QuoteExpiryTest** | 4 | ✅ PASS | Quote expiration and UTC |
| **Feature/Checkout/ToleranceBreachTest** | 4 | ✅ PASS | Price tolerance validation |
| **Feature/Checkout/TotalsIntegrityTest** | 6 | ✅ PASS | Totals calculation integrity |
| **Feature/ExampleTest** | 1 | ✅ PASS | Basic integration tests |
| **Feature/Webhooks/InvalidSignatureTest** | 6 | ✅ PASS | Webhook security (negative cases) |
| **Feature/Webhooks/SignatureTest** | 7 | ✅ PASS | Webhook security (positive cases) |

### 🔍 Validated Functional Requirements

✅ **Quote Generation** - Quotes with correct price calculation using integer math  
✅ **Quote Expiry** - Expiration validation within 5 minutes using UTC  
✅ **Price Tolerance** - Basis points check for market volatility  
✅ **Inventory Check** - Integration with mock fulfillment API  
✅ **Idempotent Checkout** - Safe transactions with idempotency keys  
✅ **Payment Webhooks** - HMAC verification and valid status transitions  
✅ **Error Handling** - Correct HTTP codes and appropriate error messages  
✅ **Data Integrity** - Consistency of totals and mathematical calculations  


