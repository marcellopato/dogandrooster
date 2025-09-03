<template>
  <div class="max-w-md mx-auto bg-white rounded-xl shadow-md overflow-hidden md:max-w-2xl">
    <div class="md:flex">
      <div class="p-8">
        <div class="uppercase tracking-wide text-sm text-indigo-500 font-semibold">
          Precious Metals Checkout
        </div>
        <h2 class="block mt-1 text-lg leading-tight font-medium text-black">
          Get Quote & Checkout Demo
        </h2>
        
        <!-- Quote Form -->
        <div class="mt-4" v-if="!currentQuote">
          <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2">
              SKU:
            </label>
            <select v-model="selectedSku" class="shadow border rounded w-full py-2 px-3 text-gray-700">
              <option value="GOLD_1OZ">Gold 1oz</option>
              <option value="SILVER_1OZ">Silver 1oz</option>
            </select>
          </div>
          
          <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2">
              Quantity:
            </label>
            <input 
              type="number" 
              v-model="quantity" 
              min="1" 
              class="shadow border rounded w-full py-2 px-3 text-gray-700"
            >
          </div>
          
          <button 
            @click="getQuote"
            :disabled="loading"
            class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50"
          >
            {{ loading ? 'Getting Quote...' : 'Get Quote' }}
          </button>
        </div>

        <!-- Quote Display -->
        <div v-if="currentQuote" class="mt-4">
          <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            <strong>Quote ID:</strong> {{ currentQuote.quote_id }}<br>
            <strong>Unit Price:</strong> ${{ (currentQuote.unit_price_cents / 100).toFixed(2) }}<br>
            <strong>Expires:</strong> <span id="countdown">{{ countdown }}</span>
          </div>
          
          <button 
            @click="checkout"
            :disabled="loading || expired"
            class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded disabled:opacity-50 mr-2"
          >
            {{ loading ? 'Processing...' : 'Checkout' }}
          </button>
          
          <button 
            @click="resetQuote"
            class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded"
          >
            Get New Quote
          </button>
        </div>

        <!-- Error Display -->
        <div v-if="error" role="alert" tabindex="0" class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mt-4">
          {{ friendlyError }}
          <button 
            v-if="error === 'REQUOTE_REQUIRED' || error === 'OUT_OF_STOCK'"
            @click="resetQuote"
            class="ml-2 bg-red-500 hover:bg-red-700 text-white font-bold py-1 px-2 rounded text-sm"
          >
            Get New Quote
          </button>
        </div>

        <!-- Success Display -->
        <div v-if="success" class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mt-4">
          {{ success }}
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, onUnmounted } from 'vue'

export default {
  name: 'QuoteDemo',
  setup() {
    const selectedSku = ref('GOLD_1OZ')
    const quantity = ref(1)
    const loading = ref(false)
    const currentQuote = ref(null)
    const error = ref(null)
    const success = ref(null)
    const countdown = ref('')
    const expired = ref(false)
    
    let countdownInterval = null

    const friendlyError = computed(() => {
      const errorMessages = {
        'REQUOTE_REQUIRED': 'Prices moved while you were checking out. Get a fresh quote to continue.',
        'OUT_OF_STOCK': 'This item just sold out at our fulfillment partner. Try a smaller quantity or another product.',
        'invalid_signature': 'We couldn\'t confirm payment with the provider. Please retry.',
        'unknown_intent': 'We couldn\'t confirm payment with the provider. Please retry.'
      }
      return errorMessages[error.value] || error.value || 'An error occurred'
    })

    const getQuote = async () => {
      loading.value = true
      error.value = null
      
      try {
        const response = await fetch('/api/quote', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
          },
          body: JSON.stringify({
            sku: selectedSku.value,
            qty: quantity.value
          })
        })
        
        const data = await response.json()
        
        if (response.ok) {
          currentQuote.value = data
          startCountdown()
        } else {
          error.value = data.error || 'Failed to get quote'
        }
      } catch (e) {
        error.value = 'Network error'
      } finally {
        loading.value = false
      }
    }

    const checkout = async () => {
      loading.value = true
      error.value = null
      
      try {
        const response = await fetch('/api/checkout', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
            'Idempotency-Key': 'demo-' + Date.now()
          },
          body: JSON.stringify({
            quote_id: currentQuote.value.quote_id
          })
        })
        
        const data = await response.json()
        
        if (response.ok) {
          success.value = `Order created successfully! Order ID: ${data.order_id}`
          resetQuote()
        } else {
          error.value = data.error || 'Checkout failed'
        }
      } catch (e) {
        error.value = 'Network error'
      } finally {
        loading.value = false
      }
    }

    const resetQuote = () => {
      currentQuote.value = null
      error.value = null
      success.value = null
      expired.value = false
      if (countdownInterval) {
        clearInterval(countdownInterval)
        countdownInterval = null
      }
    }

    const startCountdown = () => {
      if (!currentQuote.value?.quote_expires_at) return
      
      const updateCountdown = () => {
        const now = new Date().getTime()
        const expiry = new Date(currentQuote.value.quote_expires_at).getTime()
        const distance = expiry - now
        
        if (distance > 0) {
          const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60))
          const seconds = Math.floor((distance % (1000 * 60)) / 1000)
          countdown.value = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`
        } else {
          countdown.value = '00:00'
          expired.value = true
          if (countdownInterval) {
            clearInterval(countdownInterval)
            countdownInterval = null
          }
        }
      }
      
      updateCountdown()
      countdownInterval = setInterval(updateCountdown, 1000)
    }

    onUnmounted(() => {
      if (countdownInterval) {
        clearInterval(countdownInterval)
      }
    })

    return {
      selectedSku,
      quantity,
      loading,
      currentQuote,
      error,
      success,
      countdown,
      expired,
      friendlyError,
      getQuote,
      checkout,
      resetQuote
    }
  }
}
</script>
