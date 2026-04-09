<?php

declare(strict_types=1);

namespace App\Model\Product;

final class TechProduct extends Product
{
    public function getType(): string
    {
        return 'tech';
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

            if (!$attributesById[$attrId]->isValid($value)) {
                return false;
            }
        }

        foreach ($attributesById as $attrId => $attribute) {
            if (!isset($selection[$attrId])) {
                return false;
            }
        }

        return true;
    }
}
