<?php

declare(strict_types=1);

namespace App\Model\Attribute;

final class AttributeSetFactory
{
    public static function create(array $data): AttributeSet
    {
        return match ($data['type']) {
            'swatch' => new SwatchAttributeSet($data['id'], $data['name'], $data['items']),
            'text' => new TextAttributeSet($data['id'], $data['name'], $data['items']),
            default => new TextAttributeSet($data['id'], $data['name'], $data['items']),
        };
    }
}
