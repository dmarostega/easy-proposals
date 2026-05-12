<?php

namespace App\Services;

class ProposalCalculator
{
    /** @param array<int, array{quantity:numeric, unit_price:numeric}> $items */
    public function totals(array $items, float $discount = 0): array
    {
        $subtotal = collect($items)->sum(fn (array $item): float => round((float) $item['quantity'] * (float) $item['unit_price'], 2));
        $discount = max(0, round($discount, 2));

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => max(0, round($subtotal - $discount, 2)),
        ];
    }
}
