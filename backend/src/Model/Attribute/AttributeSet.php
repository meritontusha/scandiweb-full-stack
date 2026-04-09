<?php

declare(strict_types=1);

namespace App\Model\Attribute;

abstract class AttributeSet
{
    public function __construct(
        protected string $id,
        protected string $name,
        protected array $items
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function findItemByValue(string $value): ?array
    {
        foreach ($this->getItems() as $item) {
            if (($item['value'] ?? null) === $value) {
                return $item;
            }
        }

        return null;
    }

    abstract public function getType(): string;

    abstract public function getItems(): array;

    abstract public function isValid(string $value): bool;
}