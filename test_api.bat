@echo off
echo === Testing Checkout API ===

REM 1. First create a quote
echo 1. Creating a quote...
curl -s -X POST http://localhost/api/quote -H "Content-Type: application/json" -d "{\"sku\":\"GOLD_1OZ\",\"qty\":1}" > quote_response.json

echo Quote created. Content:
type quote_response.json
echo.

REM 2. Test mock fulfillment
echo 2. Testing mock fulfillment...
curl -s http://localhost/api/mock-fulfillment/availability/GOLD_1OZ
echo.

REM 3. Test checkout (with hardcoded quote_id for now)
echo 3. Testing checkout...
curl -s -X POST http://localhost/api/checkout -H "Content-Type: application/json" -H "Idempotency-Key: test-12345" -d "{\"quote_id\":1}"
echo.

echo === Test Complete ===
