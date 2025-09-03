<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;
use Carbon\Carbon;

class PriceQuote extends Model
{
    use HasFactory;

    protected $fillable = [
        'quote_id',
        'sku',
        'quantity',
        'unit_price_cents',
        'total_price_cents',
        'basis_spot_cents',
        'basis_version',
        'tolerance_bps',
        'quote_expires_at',
    ];

    protected $casts = [
        'quote_id' => 'string',
        'quantity' => 'integer',
        'unit_price_cents' => 'integer',
        'total_price_cents' => 'integer',
        'basis_spot_cents' => 'integer',
        'basis_version' => 'integer',
        'tolerance_bps' => 'integer',
        'quote_expires_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($quote) {
            if (!$quote->quote_id) {
                $quote->quote_id = Str::uuid()->toString();
            }
        });
    }

    /**
     * Get the product this quote is for
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the spot price this quote is based on
     */
    public function basisSpotPrice(): BelongsTo
    {
        return $this->belongsTo(SpotPrice::class, 'basis_version', 'id');
    }

    /**
     * Get the order created from this quote
     */
    public function order(): HasOne
    {
        return $this->hasOne(Order::class, 'quote_id', 'quote_id');
    }

    /**
     * Check if the quote has expired
     */
    public function isExpired(): bool
    {
        return Carbon::now()->greaterThanOrEqualTo($this->quote_expires_at);
    }

    /**
     * Check if current spot price is within tolerance
     */
    public function isWithinTolerance(): bool
    {
        $product = Product::where('sku', $this->sku)->first();
        
        if (!$product) {
            return false;
        }

        $currentSpotPrice = SpotPrice::getCurrent($product->metal_type);
        
        if (!$currentSpotPrice) {
            return false;
        }

        $basisPointsDiff = abs($this->basisSpotPrice->calculateBasisPointsDiff(
            $currentSpotPrice->price_per_oz_cents
        ));

        return $basisPointsDiff <= $this->tolerance_bps;
    }

    /**
     * Create a new quote for a product
     */
    public static function createForProduct(string $sku, int $quantity): self
    {
        $product = Product::where('sku', $sku)->where('active', true)->first();
        
        if (!$product) {
            throw new \Exception("Product not found or inactive: {$sku}");
        }

        $spotPrice = SpotPrice::getCurrent($product->metal_type);
        
        if (!$spotPrice) {
            throw new \Exception("No current spot price for {$product->metal_type}");
        }

        $unitPriceCents = $product->calculateUnitPrice();
        $totalPriceCents = $unitPriceCents * $quantity;

        return self::create([
            'sku' => $sku,
            'quantity' => $quantity,
            'unit_price_cents' => $unitPriceCents,
            'total_price_cents' => $totalPriceCents,
            'basis_spot_cents' => $spotPrice->price_per_oz_cents,
            'basis_version' => $spotPrice->id,
            'tolerance_bps' => config('pricing.tolerance_bps', 50),
            'quote_expires_at' => Carbon::now()->addMinutes(
                config('pricing.quote_expiry_minutes', 5)
            ),
        ]);
    }
}
