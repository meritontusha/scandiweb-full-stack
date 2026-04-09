<?php

declare(strict_types=1);

namespace App\Model\Category;

final class TechCategory extends Category
{
    public function getFilterKey(): string
    {
        return 'tech';
    }
}
