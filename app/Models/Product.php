<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku',
        'name',
        'metal_type',
        'weight_oz',
        'premium_cents',
        'active',
    ];

    protected $casts = [
        'weight_oz' => 'decimal:4',
        'premium_cents' => 'integer',
        'active' => 'boolean',
    ];

    /**
     * Get price quotes for this product
     */
    public function priceQuotes(): HasMany
    {
        return $this->hasMany(PriceQuote::class, 'sku', 'sku');
    }

    /**
     * Calculate unit price in cents based on spot price
     */
    public function calculateUnitPrice(int $spotPriceCents): int
    {
        // unit_price_cents = spot_per_oz_cents * weight_oz + premium_cents
        // Using integer math only
        $weightMilliOz = intval(bcmul($this->weight_oz, '10000', 0)); // Convert to milli-ounces
        
        // Calculate: (spot_cents * weight_milli_oz) / 10000 + premium_cents
        $basePrice = intval(bcdiv(bcmul($spotPriceCents, $weightMilliOz, 0), '10000', 0));
        
        return $basePrice + $this->premium_cents;
    }

    /**
     * Scope for active products
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
