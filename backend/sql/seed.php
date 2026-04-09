<?php

declare(strict_types=1);

use App\Database\Connection;
use Dotenv\Dotenv;

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv::createImmutable(dirname(__DIR__));
$dotenv->safeLoad();

$pdo = Connection::get();
$pdo->exec(file_get_contents(__DIR__ . '/schema.sql'));

$data = json_decode(file_get_contents(__DIR__ . '/data.json') ?: '', true, 512, JSON_THROW_ON_ERROR);
$categories = $data['data']['categories'] ?? [];
$products = $data['data']['products'] ?? [];

$pdo->beginTransaction();

$tables = [
    'order_item_attributes', 'order_items', 'orders', 'prices',
    'product_attribute_items', 'product_attributes', 'attribute_items',
    'attributes', 'product_images', 'products', 'currencies', 'categories',
];

foreach ($tables as $table) {
    $pdo->exec("DELETE FROM {$table}");
}


$insertCategory = $pdo->prepare('INSERT INTO categories(id, name) VALUES(:id, :name)');

$insertCurrency = $pdo->prepare('INSERT INTO currencies(label, symbol) VALUES(:label, :symbol) ON DUPLICATE KEY UPDATE symbol = VALUES(symbol)');

$insertProduct = $pdo->prepare(
    'INSERT INTO products(id, name, in_stock, description, category_id, brand) 
     VALUES(:id, :name, :in_stock, :description, :category_id, :brand)'
);

$insertImage = $pdo->prepare('INSERT INTO product_images(product_id, image_url, sort_order) VALUES(:product_id, :image_url, :sort_order)');

$insertPrice = $pdo->prepare('INSERT INTO prices(product_id, currency_id, amount) VALUES(:product_id, :currency_id, :amount)');

$insertAttribute = $pdo->prepare('INSERT INTO attributes(id, name, type) VALUES(:id, :name, :type) ON DUPLICATE KEY UPDATE name = VALUES(name)');

$insertAttributeItem = $pdo->prepare(
    'INSERT INTO attribute_items(id, attribute_id, display_value, value) 
     VALUES(:id, :attribute_id, :display_value, :value)
     ON DUPLICATE KEY UPDATE display_value = VALUES(display_value)'
);

$insertProductAttribute = $pdo->prepare(
    'INSERT INTO product_attributes(product_id, attribute_id, sort_order)
     VALUES(:product_id, :attribute_id, :sort_order)
     ON DUPLICATE KEY UPDATE sort_order = VALUES(sort_order)'
);

$insertProductAttributeItem = $pdo->prepare(
    'INSERT INTO product_attribute_items(product_id, attribute_id, attribute_item_id, sort_order)
     VALUES(:product_id, :attribute_id, :attribute_item_id, :sort_order)'
);


foreach($categories as $category) {
    $insertCategory->execute(['id' => $category['name'], 'name' => $category['name']]);
}

$currencyIds = [];

foreach ($products as $product) {
    $productId = $product['id'];

    $insertProduct->execute([
        'id'          => $productId,
        'name'        => $product['name'],
        'in_stock'    => $product['inStock'] ? 1 : 0,
        'description' => $product['description'],
        'category_id' => $product['category'],
        'brand'       => $product['brand'],
    ]);

    foreach ($product['gallery'] as $index => $imageUrl) {
        $insertImage->execute([
            'product_id' => $productId,
            'image_url'  => $imageUrl,
            'sort_order' => $index,
        ]);
    }

    foreach ($product['prices'] as $price) {
        $label = $price['currency']['label'];
        if (!isset($currencyIds[$label])) {
            $insertCurrency->execute([
                'label'  => $label,
                'symbol' => $price['currency']['symbol'],
            ]);
            $currencyIds[$label] = (int) $pdo->lastInsertId();
        }

        $insertPrice->execute([
            'product_id'  => $productId,
            'currency_id' => $currencyIds[$label],
            'amount'      => $price['amount'],
        ]);
    }

    foreach ($product['attributes'] as $attrIndex => $attribute) {
        $attrId = $attribute['id'];

        $insertAttribute->execute([
            'id'   => $attrId,
            'name' => $attribute['name'],
            'type' => $attribute['type'],
        ]);

        $insertProductAttribute->execute([
            'product_id'   => $productId,
            'attribute_id' => $attrId,
            'sort_order'   => $attrIndex,
        ]);

        foreach ($attribute['items'] as $itemIndex => $item) {
            $itemId = $attrId . '::' . $item['id'];

            $insertAttributeItem->execute([
                'id'            => $itemId,
                'attribute_id'  => $attrId,
                'display_value' => $item['displayValue'],
                'value'         => $item['value'],
            ]);

            $insertProductAttributeItem->execute([
                'product_id'        => $productId,
                'attribute_id'      => $attrId,
                'attribute_item_id' => $itemId,
                'sort_order'        => $itemIndex,
            ]);
        }
    }
}

$pdo->commit();
echo "Database seeded successfully\n";