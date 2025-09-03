<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class SpotPrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'metal_type',
        'price_per_oz_cents',
        'effective_at',
        'is_current',
    ];

    protected $casts = [
        'price_per_oz_cents' => 'integer',
        'effective_at' => 'datetime',
        'is_current' => 'boolean',
    ];

    /**
     * Get the current spot price for a metal type
     */
    public static function getCurrent(string $metalType): ?self
    {
        return self::where('metal_type', $metalType)
                   ->where('is_current', true)
                   ->first();
    }

    /**
     * Get the latest spot price (most recent)
     */
    public static function getLatest(): ?self
    {
        return self::orderBy('effective_at', 'desc')->first();
    }

    /**
     * Set this as the current price for the metal type
     */
    public function setCurrent(): void
    {
        // First, mark all other prices for this metal as not current
        self::where('metal_type', $this->metal_type)
            ->where('id', '!=', $this->id)
            ->update(['is_current' => false]);
        
        // Then mark this one as current
        $this->update(['is_current' => true]);
    }

    /**
     * Calculate basis points difference from another price
     */
    public function calculateBasisPointsDiff(int $otherPriceCents): int
    {
        if ($this->price_per_oz_cents === 0) {
            return 0;
        }

        // Basis points = (new_price - old_price) / old_price * 10000
        $difference = $otherPriceCents - $this->price_per_oz_cents;
        
        // Using integer math: (diff * 10000) / original_price
        return intval(bcdiv(bcmul($difference, '10000', 0), $this->price_per_oz_cents, 0));
    }

    /**
     * Scope for current prices
     */
    public function scopeCurrent(Builder $query): Builder
    {
        return $query->where('is_current', true);
    }

    /**
     * Scope for specific metal type
     */
    public function scopeForMetal(Builder $query, string $metalType): Builder
    {
        return $query->where('metal_type', $metalType);
    }
}
