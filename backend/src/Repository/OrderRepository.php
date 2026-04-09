<?php

declare(strict_types=1);

namespace App\Repository;

use App\Model\Order\Order;
use App\Model\Order\OrderItem;
use PDO;
use Throwable;

final class OrderRepository
{
    public function __construct(
        private readonly PDO $pdo
    ) {
    }

    public function create(Order $order): int
    {
        $order->validate();

        $insertOrder = $this->pdo->prepare('INSERT INTO orders () VALUES ()');
        $insertOrderItem = $this->pdo->prepare(
            'INSERT INTO order_items (
                order_id,
                product_id,
                quantity,
                product_name,
                product_brand,
                product_price,
                currency_label,
                currency_symbol
            ) VALUES (
                :order_id,
                :product_id,
                :quantity,
                :product_name,
                :product_brand,
                :product_price,
                :currency_label,
                :currency_symbol
            )'
        );
        $insertOrderAttribute = $this->pdo->prepare(
            'INSERT INTO order_item_attributes (
                order_item_id,
                attribute_name,
                item_display_value,
                item_value
            ) VALUES (
                :order_item_id,
                :attribute_name,
                :item_display_value,
                :item_value
            )'
        );

        $this->pdo->beginTransaction();

        try {
            $insertOrder->execute();
            $orderId = (int) $this->pdo->lastInsertId();

            foreach ($order->getItems() as $item) {
                $orderItemId = $this->insertOrderItem($insertOrderItem, $orderId, $item);
                $this->insertOrderAttributes($insertOrderAttribute, $orderItemId, $item);
            }

            $this->pdo->commit();

            return $orderId;
        } catch (Throwable $throwable) {
            $this->pdo->rollBack();

            throw $throwable;
        }
    }

    private function insertOrderItem(\PDOStatement $statement, int $orderId, OrderItem $item): int
    {
        $product = $item->getProduct();
        $price = $item->getPrimaryPrice();

        $statement->execute([
            'order_id' => $orderId,
            'product_id' => $product->getId(),
            'quantity' => $item->getQuantity(),
            'product_name' => $product->getName(),
            'product_brand' => $product->getBrand(),
            'product_price' => $price['amount'],
            'currency_label' => $price['currency']['label'],
            'currency_symbol' => $price['currency']['symbol'],
        ]);

        return (int) $this->pdo->lastInsertId();
    }

    private function insertOrderAttributes(\PDOStatement $statement, int $orderItemId, OrderItem $item): void
    {
        foreach ($item->getPersistableAttributes() as $attributeRow) {
            $statement->execute([
                'order_item_id' => $orderItemId,
                'attribute_name' => $attributeRow['attribute_name'],
                'item_display_value' => $attributeRow['item_display_value'],
                'item_value' => $attributeRow['item_value'],
            ]);
        }
    }
}