<?php

declare(strict_types=1);

namespace App\Model\Category;

final class ClothesCategory extends Category
{
    public function getFilterKey(): string
    {
        return 'clothes';
    }
}
