<?php

declare(strict_types=1);

namespace App\Model\Category;

final class CategoryFactory
{
    public static function create(string $name): Category
    {
        return match ($name) {
            'clothes' => new ClothesCategory($name),
            'tech' => new TechCategory($name),
            'all' => new AllCategory($name),
            default => new AllCategory($name),
        };
    }
}
