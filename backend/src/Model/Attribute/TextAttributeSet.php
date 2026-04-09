<?php

declare(strict_types=1);

namespace App\Model\Attribute;

final class TextAttributeSet extends AttributeSet
{
    public function getType(): string
    {
        return 'text';
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function isValid(string $value): bool
    {
        foreach ($this->items as $item) {
            if ($item['value'] === $value) {
                return true;
            }
        }

        return false;
    }
}
