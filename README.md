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
git clone <repo-url>
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

## 🚧 Future Improvements

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