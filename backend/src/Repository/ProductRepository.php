<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\Product\Product;
use App\Model\Product\ProductFactory;
use PDO;

final class ProductRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function getCategories(): array
    {
        $stmt = $this->pdo->query('SELECT name FROM categories ORDER BY id');

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getProducts(?string $category): array
    {
        $sql = 'SELECT p.id, p.name, p.in_stock, p.description, p.brand, c.name AS category
                FROM products p 
                INNER JOIN categories c ON c.id = p.category_id';

        $params = [];

        if ($category !== null && $category !== 'all') {
            $sql .= ' WHERE c.name = :category';
            $params['category'] = $category;
        }

        $sql .= ' ORDER BY p.name';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($rows === []) {
            return [];
        }

        $maps = $this->buildRelationshipMaps(array_column($rows, 'id'));

        return array_map(fn (array $row): Product => $this->hydrateProduct($row, $maps), $rows);
    }

    public function getProduct(string $id): ?Product
    {
        $stmt = $this->pdo->prepare('SELECT p.id, p.name, p.in_stock, p.description, p.brand, c.name AS category
        FROM products p
        INNER JOIN categories c ON c.id = p.category_id
        WHERE p.id = :id
        ');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        if (!$row) {
            return null;
        }

        $maps = $this->buildRelationshipMaps([$row['id']]);

        return $this->hydrateProduct($row, $maps);
    }

    private function hydrateProduct(array $base, array $maps): Product
    {
        $productId = $base['id'];
        $payload = [
            'id' => $productId,
            'name' => $base['name'],
            'inStock' => (bool) $base['in_stock'],
            'gallery' => $maps['gallery'][$productId] ?? [],
            'description' => $base['description'],
            'type' => $base['type'] ?? null,
            'category' => $base['category'],
            'attributes' => $maps['attributes'][$productId] ?? [],
            'prices' => $maps['prices'][$productId] ?? [],
            'brand' => $base['brand'],
        ];

        return ProductFactory::create($payload);
    }

    private function buildRelationshipMaps(array $productIds): array
    {
        return [
            'gallery' => $this->getGalleryMap($productIds),
            'prices' => $this->getPriceMap($productIds),
            'attributes' => $this->getAttributeMap($productIds),
        ];
    }

    private function getGalleryMap(array $productIds): array
    {
        if ($productIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT product_id, image_url
             FROM product_images
             WHERE product_id IN ($placeholders)
             ORDER BY product_id, sort_order"
        );
        $stmt->execute($productIds);
        $map = [];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $map[$row['product_id']][] = $row['image_url'];
        }

        return $map;
    }

    private function getPriceMap(array $productIds): array
    {
        if ($productIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $stmt = $this->pdo->prepare("SELECT pr.product_id, pr.amount, c.label, c.symbol
            FROM prices pr
            INNER JOIN currencies c ON c.id = pr.currency_id
            WHERE pr.product_id IN ($placeholders)
            ORDER BY pr.product_id
        ");
        $stmt->execute($productIds);
        $map = [];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $map[$row['product_id']][] = [
                'amount' => (float) $row['amount'],
                'currency' => [
                    'label' => $row['label'],
                    'symbol' => $row['symbol'],
                ],
            ];
        }

        return $map;
    }

    private function getAttributeMap(array $productIds): array
    {
        if ($productIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($productIds), '?'));

        $stmt = $this->pdo->prepare(
            "SELECT 
        pa.product_id, 
        pa.sort_order AS attribute_sort, 
        a.id AS attribute_id, 
        a.name, 
        a.type, 
        pai.sort_order AS item_sort,
        ai.id AS item_id, 
        ai.display_value, 
        ai.value
        FROM product_attributes pa
        INNER JOIN attributes a ON a.id = pa.attribute_id
        LEFT JOIN product_attribute_items pai 
        ON pai.product_id = pa.product_id 
        AND pai.attribute_id = pa.attribute_id
        LEFT JOIN attribute_items ai 
        ON ai.id = pai.attribute_item_id
        WHERE pa.product_id IN ($placeholders)
        ORDER BY pa.product_id, pa.sort_order, a.id, pai.sort_order"
        );
        $stmt->execute($productIds);

        $attributeBuckets = [];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $productId = $row['product_id'];
            $attributeKey = (string) $row['attribute_id'];

            if (!isset($attributeBuckets[$productId][$attributeKey])) {
                $attributeBuckets[$productId][$attributeKey] = [
                    'id' => $row['attribute_id'],
                    'name' => $row['name'],
                    'type' => $row['type'],
                    'items' => [],
                ];
            }

            if ($row['item_id'] !== null) {
                $attributeBuckets[$productId][$attributeKey]['items'][] = [
                    'id' => $row['item_id'],
                    'displayValue' => $row['display_value'],
                    'value' => $row['value'],
                ];
            }
        }

        $map = [];

        foreach ($attributeBuckets as $productId => $bucket) {
            $map[$productId] = [];

            foreach (array_values($bucket) as $attribute) {
                $map[$productId][] = [
                    'id' => $attribute['id'],
                    'name' => $attribute['name'],
                    'type' => $attribute['type'],
                    'items' => $attribute['items'],
                ];
            }
        }

        return $map;
    }
}