<?php

declare(strict_types=1);

namespace App\Model\Attribute;

final class SwatchAttributeSet extends AttributeSet
{
    public function getType(): string
    {
        return 'swatch';
    }

    public function getItems(): array
    {
        return array_map(function (array $item): array {
            $item['value'] = strtoupper($item['value']);

            return $item;
        }, $this->items);
    }

    public function isValid(string $value): bool
    {
        $value = strtoupper($value);

        foreach ($this->items as $item) {
            if (strtoupper($item['value']) === $value) {
                return true;
            }
        }

        return false;
    }

    public function findItemByValue(string $value): ?array
    {
        $value = strtoupper($value);

        foreach ($this->getItems() as $item) {
            if (strtoupper((string) ($item['value'] ?? '')) === $value) {
                return $item;
            }
        }

        return null;
    }
}