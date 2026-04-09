<?php

declare(strict_types=1);

namespace App\Model\Product;

final class ClothesProduct extends Product
{
    public function getType(): string
    {
        return 'clothes';
    }

    public function validateSelection(array $selection): bool
    {
        $attributesById = [];

        foreach ($this->attributes as $attribute) {
            $attributesById[$attribute->getId()] = $attribute;
        }

        foreach ($selection as $attrId => $value) {
            if (!isset($attributesById[$attrId])) {
                return false;
            }
        }

        foreach ($attributesById as $attrId => $attribute) {
            if (!isset($selection[$attrId]) || !$attribute->isValid($selection[$attrId])) {
                return false;
            }
        }

        return true;
    }
}