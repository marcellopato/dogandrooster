#!/bin/bash

echo "=== Testing Checkout API ===\n"

# 1. First create a quote
echo "1. Creating a quote..."
QUOTE_RESPONSE=$(curl -s -X POST http://localhost/api/quote \
  -H "Content-Type: application/json" \
  -d '{"sku":"GOLD_1OZ","qty":1}')

echo "Quote Response: $QUOTE_RESPONSE"

# Extract quote_id from response (simple parsing)
QUOTE_ID=$(echo $QUOTE_RESPONSE | grep -o '"quote_id":[0-9]*' | grep -o '[0-9]*')
echo "Quote ID: $QUOTE_ID"

if [ -z "$QUOTE_ID" ]; then
    echo "❌ Failed to create quote"
    exit 1
fi

echo ""

# 2. Test checkout
echo "2. Testing checkout..."
CHECKOUT_RESPONSE=$(curl -s -X POST http://localhost/api/checkout \
  -H "Content-Type: application/json" \
  -H "Idempotency-Key: test-$(date +%s)" \
  -d "{\"quote_id\":$QUOTE_ID}")

echo "Checkout Response: $CHECKOUT_RESPONSE"

echo ""

# 3. Test mock fulfillment
echo "3. Testing mock fulfillment..."
FULFILLMENT_RESPONSE=$(curl -s http://localhost/api/mock-fulfillment/availability/GOLD_1OZ)
echo "Fulfillment Response: $FULFILLMENT_RESPONSE"

echo ""
echo "=== Test Complete ==="
