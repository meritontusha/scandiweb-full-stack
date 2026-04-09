<?php

declare(strict_types=1);

namespace App\Model\Product;

use App\Model\Attribute\AttributeSet;
use App\Model\Attribute\AttributeSetFactory;

final class ProductFactory
{
    public static function create(array $p): Product
    {
        $attributeObjects = array_map(
            static function (array $attr): AttributeSet {
                return AttributeSetFactory::create([
                    'id' => $attr['id'],
                    'name' => $attr['name'],
                    'type' => $attr['type'],
                    'items' => $attr['items'] ?? [],
                ]);
            },
            $p['attributes'] ?? []
        );

        return match (self::resolveProductType($p)) {
            'clothes' => new ClothesProduct(
                $p['id'],
                $p['name'],
                $p['inStock'],
                $p['gallery'],
                $p['description'],
                $p['category'],
                $attributeObjects,
                $p['prices'],
                $p['brand']
            ),
            'tech' => new TechProduct(
                $p['id'],
                $p['name'],
                $p['inStock'],
                $p['gallery'],
                $p['description'],
                $p['category'],
                $attributeObjects,
                $p['prices'],
                $p['brand']
            ),
            default => throw new \RuntimeException(sprintf('Unknown product type "%s".', self::resolveProductType($p))),
        };
    }

    private static function resolveProductType(array $payload): string
    {
        $type = (string) ($payload['type'] ?? '');

        if ($type !== '') {
            return $type;
        }

        return match ((string) ($payload['category'] ?? '')) {
            'clothes' => 'clothes',
            'tech', 'all' => 'tech',
            default => throw new \RuntimeException(sprintf(
                'Cannot infer product type from category "%s".',
                (string) ($payload['category'] ?? '')
            )),
        };
    }
}