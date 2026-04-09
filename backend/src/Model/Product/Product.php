<?php

declare(strict_types=1);

namespace App\Model\Product;

use App\Model\Attribute\AttributeSet;

abstract class Product
{
    /**
     * @param AttributeSet[] $attributes
     */
    public function __construct(
        protected string $id,
        protected string $name,
        protected bool $inStock,
        protected array $gallery,
        protected string $description,
        protected string $category,
        protected array $attributes,
        protected array $prices,
        protected string $brand
    ) {
    }

    abstract public function getType(): string;

    abstract public function validateSelection(array $selection): bool;

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isInStock(): bool
    {
        return $this->inStock;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getPrices(): array
    {
        return $this->prices;
    }

    public function getGallery(): array
    {
        return $this->gallery;
    }

    public function getBrand(): string
    {
        return $this->brand;
    }

    public function getAttributeById(string $attributeId): ?AttributeSet
    {
        foreach ($this->attributes as $attribute) {
            if ($attribute->getId() === $attributeId) {
                return $attribute;
            }
        }

        return null;
    }
}