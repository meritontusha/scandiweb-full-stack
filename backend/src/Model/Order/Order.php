<?php

declare(strict_types=1);

namespace App\Model\Order;

use RuntimeException;

final class Order
{
    /**
     * @param OrderItem[] $items
     */
    public function __construct(
        private readonly array $items
    ) {
    }

    /**
     * @return OrderItem[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function validate(): void
    {
        if ($this->items === []) {
            throw new RuntimeException('An order must contain at least one item.');
        }

        foreach ($this->items as $item) {
            $item->validate();
        }
    }
}