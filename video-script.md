# Precious Metals E-commerce Demo - Video Script

## 🎬 Video Overview
**Duration**: 8-12 minutes  
**Target Audience**: Technical stakeholders, developers, product managers  
**Goal**: Demonstrate a complete e-commerce checkout solution with volatile pricing

---

## 📝 Script Outline

### 1. INTRODUCTION (30 seconds)
> **[Screen: Project Title/Logo]**

**"Hello! Today I'm going to show you a complete e-commerce checkout system built for precious metals trading. This project handles one of the most challenging aspects of metals e-commerce: volatile pricing with time-limited quotes."**

**"What makes this special? Real-time spot price indexing, transaction safety, and a user experience that handles price volatility gracefully."**

---

### 2. PROJECT OVERVIEW (1 minute)
> **[Screen: README.md - Project Overview Section]**

**"Let me start with what we've built. This is a Laravel + Vue.js application that implements:"**

- **"Locked price quotes valid for exactly 5 minutes"**
- **"Real-time inventory checking with our fulfillment partner"** 
- **"Idempotent transactions to prevent duplicate orders"**
- **"Payment webhooks with HMAC signature verification"**
- **"And a responsive interface that guides users through time-sensitive purchases"**

**"The challenge here is that precious metals prices can change by the second, so we need to balance accurate pricing with user experience."**

---

### 3. TECHNOLOGY STACK (30 seconds)
> **[Screen: README.md - Technology Stack]**

**"The stack is modern and robust:"**
- **"Laravel 10 backend with MySQL for data persistence"**
- **"Vue.js 3 frontend with TailwindCSS for styling"**
- **"Docker containerization for consistent development"**
- **"Redis for caching and session management"**

---

### 4. LIVE FRONTEND DEMO (3 minutes)
> **[Screen: http://localhost/demo]**

#### 4.1 Initial State
**"Let's see it in action. Here's our demo interface - clean, focused, and purpose-built for metals trading."**

#### 4.2 Getting a Quote
**"First, I'll select a product - let's go with Gold 1oz - and request 1 unit."**

> **[Action: Select GOLD_1OZ, quantity 1, click "Get Quote"]**

**"Notice what happens immediately:"**
- **"We get a unique quote ID - that's a UUID for tracking"**
- **"A precise unit price in dollars"**  
- **"And critically - a countdown timer showing exactly when this quote expires"**

#### 4.3 Countdown Timer
**"This countdown is crucial. Precious metals prices are volatile, so we only guarantee this price for 5 minutes. Watch the timer - it's counting down in real-time."**

#### 4.4 Successful Checkout
**"Now let's complete the purchase while the quote is still valid."**

> **[Action: Click "Checkout"]**

**"Perfect! The checkout completed successfully. Notice the detailed response:"**
- **"Order ID for tracking"**
- **"Payment Intent ID for payment processing"**  
- **"Total amount clearly displayed"**
- **"And a clean path to start a new quote"**

#### 4.5 Error Handling Demo
**"Let me show you the error handling. I'll get a new quote and wait for it to expire."**

> **[Action: Get new quote, wait for timer to reach 00:00, then try checkout]**

**"See that? The system immediately detects the expired quote and gives us a clear, actionable error message. No confusing technical jargon - just 'Get a fresh quote to continue.'"**

---

### 5. API DOCUMENTATION (2 minutes)
> **[Screen: http://localhost/api/documentation]**

**"Now let's look under the hood. We have comprehensive Swagger documentation for all our endpoints."**

#### 5.1 Quote Endpoint
> **[Screen: POST /api/quote in Swagger]**

**"The quote endpoint is beautifully simple - just SKU and quantity in, quote details out. But notice the response structure - we're returning integer cents, not floating-point dollars. This prevents rounding errors that could cost real money."**

#### 5.2 Checkout Endpoint  
> **[Screen: POST /api/checkout in Swagger]**

**"The checkout endpoint requires an Idempotency-Key header. This is critical for financial transactions - if a user accidentally clicks twice, they won't create duplicate orders."**

**"Look at the possible responses:"**
- **"201 for successful order creation"**
- **"409 for business logic conflicts like expired quotes or insufficient stock"**
- **"422 for validation errors"**

#### 5.3 Testing the API
> **[Action: Execute a quote request in Swagger UI]**

**"Let me test this live. I'll request a quote through the API..."**

**"Perfect! See how we get back that UUID quote_id, the price in cents, and the exact expiration timestamp in ISO format."**

---

### 6. CODE WALKTHROUGH (3 minutes)

#### 6.1 Quote Controller - Integer Math
> **[Screen: QuoteController.php - store method]**

**"Let me show you some key implementation details. First, our pricing calculation:"**

**"Everything is done in integer cents. No floating-point arithmetic anywhere near money. This line here calculates the unit price by taking the spot price per ounce and adding the product premium - all in cents."**

#### 6.2 Checkout Controller - Transaction Safety
> **[Screen: CheckoutController.php - store method]**

**"The checkout process is wrapped in a database transaction. Notice the sequence:"**

1. **"Check idempotency first - if this key was used before, return the existing order"**
2. **"Validate the quote hasn't expired"**  
3. **"Check price tolerance - if the market moved too much, require a new quote"**
4. **"Verify inventory with our fulfillment partner"**
5. **"Only then create the order and order lines atomically"**

**"If any step fails, the entire transaction rolls back. No partial orders, no data inconsistency."**

#### 6.3 Inventory Management
> **[Screen: CheckoutController.php - checkInventory method]**

**"Here's something interesting - our inventory checking adapts to the environment. In local development, we use cache for fast testing. In production, this would make HTTP calls to the real fulfillment API."**

#### 6.4 Frontend State Management
> **[Screen: QuoteDemo.vue - resetQuote function]**

**"On the frontend, state management is crucial. When a user completes an order or wants to start fresh, we reset everything - form fields, timers, messages - back to the initial state. It's like reloading the page without actually reloading."**

---

### 7. TESTING & QUALITY (1 minute)
> **[Screen: README.md - Test Results Section]**

**"Quality is critical in financial applications. We have comprehensive test coverage:"**

- **"53 tests covering every critical path"**
- **"Unit tests ensuring integer-only math"**  
- **"Feature tests for quote expiry, tolerance breaches, and idempotency"**
- **"Webhook security tests with invalid signatures"**
- **"All tests passing with 100% success rate"**

---

### 8. TECHNICAL HIGHLIGHTS (1 minute)

**"Let me highlight what makes this solution robust:"**

#### 8.1 Financial Precision
**"All money calculations use integers only. No floating-point errors that could compound over thousands of transactions."**

#### 8.2 Concurrency Safety  
**"Database transactions with proper locking ensure multiple users can't create race conditions."**

#### 8.3 User Experience
**"Real-time countdown, friendly error messages, and clear success feedback keep users informed throughout the volatile pricing process."**

#### 8.4 Production Ready
**"HMAC webhook verification, proper HTTP status codes, comprehensive logging, and Docker containerization make this deployment-ready."**

---

### 9. CONCLUSION (30 seconds)

**"This project demonstrates how to handle complex e-commerce requirements - volatile pricing, financial precision, and transaction safety - while maintaining an excellent user experience."**

**"The architecture is scalable, the code is tested, and the documentation is comprehensive. It's a complete solution ready for real-world precious metals trading."**

**"Thank you for watching! Feel free to explore the code on GitHub."**

---

## 🎥 Recording Tips

### Screen Setup
1. **Use 1920x1080 resolution** for crisp recording
2. **Close unnecessary browser tabs** to avoid distractions  
3. **Use browser zoom at 100%** for optimal text visibility
4. **Have multiple browser windows ready:**
   - Demo: `http://localhost/demo`
   - Swagger: `http://localhost/api/documentation`
   - Code editor with key files open

### Recording Flow
1. **Record in segments** - easier to edit and redo if needed
2. **Practice the demo flow** beforehand to avoid mistakes
3. **Use clear, confident speech** - you're the expert here
4. **Point out specific code lines** when relevant
5. **Show both success and error cases** for completeness

### Key Files to Have Ready
- `app/Http/Controllers/Api/QuoteController.php` (integer math)
- `app/Http/Controllers/Api/CheckoutController.php` (transaction safety)  
- `resources/js/components/QuoteDemo.vue` (frontend state)
- `README.md` (overview and test results)

### Demo Data to Prepare
- Have fresh inventory seeded: `php artisan db:seed --class=InventorySeeder`
- Clear any existing cache
- Ensure Docker containers are running smoothly

---

## 🎯 Key Messages to Emphasize

1. **"Financial precision with integer-only math"**
2. **"Transaction safety with proper locking and rollbacks"**  
3. **"User experience that handles volatility gracefully"**
4. **"Production-ready with comprehensive testing"**
5. **"Modern stack with best practices throughout"**

This script should give you a compelling 8-12 minute demo that showcases both the technical depth and practical value of your application!
