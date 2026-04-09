<?php

declare(strict_types=1);

namespace App\Model\Order;

use App\Model\Attribute\AttributeSet;
use App\Model\Product\Product;
use RuntimeException;

final class OrderItem
{
    /**
     * @param array<string, string> $selectedAttributes
     */
    public function __construct(
        private readonly Product $product,
        private readonly int $quantity,
        private readonly array $selectedAttributes
    ) {
    }

    public function getProduct(): Product
    {
        return $this->product;
    }

    public function getQuantity(): int
    {
        return $this->quantity;
    }

    /**
     * @return array<string, string>
     */
    public function getSelectedAttributes(): array
    {
        return $this->selectedAttributes;
    }

    public function validate(): void
    {
        if ($this->quantity < 1) {
            throw new RuntimeException('Order item quantity must be at least 1.');
        }

        if (!$this->product->isInStock()) {
            throw new RuntimeException(sprintf('Product "%s" is out of stock.', $this->product->getId()));
        }

        if (!$this->product->validateSelection($this->selectedAttributes)) {
            throw new RuntimeException(sprintf('Selected attributes are invalid for product "%s".', $this->product->getId()));
        }
    }

    /**
     * @return array{amount: float, currency: array{label: string, symbol: string}}
     */
    public function getPrimaryPrice(): array
    {
        $prices = $this->product->getPrices();
        if ($prices === []) {
            throw new RuntimeException(sprintf('Product "%s" has no price to snapshot.', $this->product->getId()));
        }

        return $prices[0];
    }

    /**
     * @return array<int, array{attribute_name: string, item_display_value: string, item_value: string}>
     */
    // Build the persisted attribute rows from the product model so stored order data is human-readable.
    public function getPersistableAttributes(): array
    {
        $rows = [];

        foreach ($this->selectedAttributes as $attributeId => $value) {
            $attribute = $this->product->getAttributeById($attributeId);
            if (!$attribute instanceof AttributeSet) {
                throw new RuntimeException(sprintf(
                    'Attribute "%s" does not exist on product "%s".',
                    $attributeId,
                    $this->product->getId()
                ));
            }

            $item = $attribute->findItemByValue($value);
            if ($item === null) {
                throw new RuntimeException(sprintf(
                    'Value "%s" is not available for attribute "%s" on product "%s".',
                    $value,
                    $attributeId,
                    $this->product->getId()
                ));
            }

            $rows[] = [
                'attribute_name' => $attribute->getName(),
                'item_display_value' => (string) ($item['displayValue'] ?? $value),
                'item_value' => (string) ($item['value'] ?? $value),
            ];
        }

        return $rows;
    }
}