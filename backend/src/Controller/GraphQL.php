<?php

declare(strict_types=1);

namespace App\Controller;

use App\Database\Connection;
use App\Model\Attribute\AttributeSet;
use App\Model\Category\Category;
use App\Model\Category\CategoryFactory;
use App\Model\Order\Order;
use App\Model\Order\OrderItem;
use App\Model\Product\Product;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use GraphQL\GraphQL as GraphQLBase;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;
use GraphQL\Type\SchemaConfig;
use RuntimeException;
use Throwable;

final class GraphQL
{
    public static function endpoint(): string
    {
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET' && !isset($_GET['query'])) {
            return self::renderPlayground();
        }

        return self::handle();
    }

    
    public static function renderPlayground(): string
    {
        header('Content-Type: text/html; charset=UTF-8');

        return <<<'HTML'
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>GraphQL Playground</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/graphiql/graphiql.min.css">
    <style>
        body { margin: 0; height: 100vh; overflow: hidden; }
        #graphiql { height: 100vh; }
    </style>
</head>
<body>
    <div id="graphiql">Loading...</div>

    <script crossorigin src="https://cdn.jsdelivr.net/npm/react@18/umd/react.production.min.js"></script>
    <script crossorigin src="https://cdn.jsdelivr.net/npm/react-dom@18/umd/react-dom.production.min.js"></script>
    <script crossorigin src="https://cdn.jsdelivr.net/npm/graphiql/graphiql.min.js"></script>

    <script>
        const fetcher = async (graphQLParams) => {
            const response = await fetch('./graphql', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(graphQLParams),
            });
            return response.json();
        };

        if (typeof GraphiQL !== 'undefined') {
            const root = ReactDOM.createRoot(document.getElementById('graphiql'));
            root.render(
                React.createElement(GraphiQL, {
                    fetcher,
                    defaultQuery: 'query { products { id name category inStock } }'
                })
            );
        } else {
            document.getElementById('graphiql').innerHTML = 'Error: GraphiQL library failed to load from CDN.';
        }
    </script>
</body>
</html>
HTML;
    }

    
    public static function handle(): string
    {
        try {
            $pdo = Connection::get();
            $repository = new ProductRepository($pdo);
            $orderRepository = new OrderRepository($pdo);

            $currencyType = new ObjectType([
                'name' => 'Currency',
                'fields' => [
                    'label' => Type::nonNull(Type::string()),
                    'symbol' => Type::nonNull(Type::string()),
                ],
            ]);

            $priceType = new ObjectType([
                'name' => 'Price',
                'fields' => [
                    'amount' => Type::nonNull(Type::float()),
                    'currency' => Type::nonNull($currencyType),
                ],
            ]);

            $attributeItemType = new ObjectType([
                'name' => 'AttributeItem',
                'fields' => [
                    'id' => Type::nonNull(Type::string()),
                    'displayValue' => Type::nonNull(Type::string()),
                    'value' => Type::nonNull(Type::string()),
                ],
            ]);

            $attributeType = new ObjectType([
                'name' => 'AttributeSet',
                'fields' => [
                    'id' => [
                        'type' => Type::nonNull(Type::string()),
                        'resolve' => static fn (AttributeSet $attribute): string => $attribute->getId(),
                    ],
                    'name' => [
                        'type' => Type::nonNull(Type::string()),
                        'resolve' => static fn (AttributeSet $attribute): string => $attribute->getName(),
                    ],
                    'type' => [
                        'type' => Type::nonNull(Type::string()),
                        'resolve' => static fn (AttributeSet $attribute): string => $attribute->getType(),
                    ],
                    'items' => [
                        'type' => Type::nonNull(Type::listOf(Type::nonNull($attributeItemType))),
                        'resolve' => static fn (AttributeSet $attribute): array => $attribute->getItems(),
                    ],
                ],
            ]);

            $categoryType = new ObjectType([
                'name' => 'Category',
                'fields' => [
                    'name' => [
                        'type' => Type::nonNull(Type::string()),
                        'resolve' => static fn (Category $category): string => $category->getName(),
                    ],
                ],
            ]);

            $productType = new ObjectType([
                'name' => 'Product',
                'fields' => [
                    'id' => [
                        'type' => Type::nonNull(Type::string()),
                        'resolve' => static fn (Product $product): string => $product->getId(),
                    ],
                    'name' => [
                        'type' => Type::nonNull(Type::string()),
                        'resolve' => static fn (Product $product): string => $product->getName(),
                    ],
                    'inStock' => [
                        'type' => Type::nonNull(Type::boolean()),
                        'resolve' => static fn (Product $product): bool => $product->isInStock(),
                    ],
                    'gallery' => [
                        'type' => Type::nonNull(Type::listOf(Type::nonNull(Type::string()))),
                        'resolve' => static fn (Product $product): array => $product->getGallery(),
                    ],
                    'description' => [
                        'type' => Type::nonNull(Type::string()),
                        'resolve' => static fn (Product $product): string => $product->getDescription(),
                    ],
                    'category' => [
                        'type' => Type::nonNull(Type::string()),
                        'resolve' => static fn (Product $product): string => $product->getCategory(),
                    ],
                    'attributes' => [
                        'type' => Type::nonNull(Type::listOf(Type::nonNull($attributeType))),
                        'resolve' => static fn (Product $product): array => $product->getAttributes(),
                    ],
                    'prices' => [
                        'type' => Type::nonNull(Type::listOf(Type::nonNull($priceType))),
                        'resolve' => static fn (Product $product): array => $product->getPrices(),
                    ],
                    'brand' => [
                        'type' => Type::nonNull(Type::string()),
                        'resolve' => static fn (Product $product): string => $product->getBrand(),
                    ],
                ],
            ]);

            $selectedAttributeInputType = new InputObjectType([
                'name' => 'SelectedAttributeInput',
                'fields' => [
                    'attributeId' => Type::nonNull(Type::string()),
                    'value' => Type::nonNull(Type::string()),
                ],
            ]);

            $orderItemInputType = new InputObjectType([
                'name' => 'OrderItemInput',
                'fields' => [
                    'productId' => Type::nonNull(Type::string()),
                    'quantity' => Type::nonNull(Type::int()),
                    'selectedAttributes' => Type::nonNull(Type::listOf(Type::nonNull($selectedAttributeInputType))),
                ],
            ]);

            $orderResponseType = new ObjectType([
                'name' => 'OrderResponse',
                'fields' => [
                    'orderId' => Type::nonNull(Type::int()),
                    'success' => Type::nonNull(Type::boolean()),
                    'message' => Type::nonNull(Type::string()),
                ],
            ]);

            $queryType = new ObjectType([
                'name' => 'Query',
                'fields' => [
                    'categories' => [
                        'type' => Type::nonNull(Type::listOf(Type::nonNull($categoryType))),
                        'resolve' => static fn (): array => array_map(
                            static fn (array $row): Category => CategoryFactory::create($row['name']),
                            $repository->getCategories()
                        ),
                    ],
                    'products' => [
                        'type' => Type::nonNull(Type::listOf(Type::nonNull($productType))),
                        'args' => [
                            'category' => ['type' => Type::string()],
                        ],
                        'resolve' => static fn ($rootValue, array $args): array => $repository->getProducts($args['category'] ?? null),
                    ],
                    'product' => [
                        'type' => $productType,
                        'args' => [
                            'id' => Type::nonNull(Type::string()),
                        ],
                        'resolve' => static fn ($rootValue, array $args): ?Product => $repository->getProduct($args['id']),
                    ],
                ],
            ]);

            $mutationType = new ObjectType([
                'name' => 'Mutation',
                'fields' => [
                    'placeOrder' => [
                        'type' => Type::nonNull($orderResponseType),
                        'args' => [
                            'items' => Type::nonNull(Type::listOf(Type::nonNull($orderItemInputType))),
                        ],
                        'resolve' => function ($rootValue, array $args) use ($repository, $orderRepository): array {
                            $itemsInput = $args['items'] ?? [];
                            if (!is_array($itemsInput) || $itemsInput === []) {
                                throw new RuntimeException('The order must include at least one item.');
                            }

                            $orderItems = [];

                            foreach ($itemsInput as $itemInput) {
                                $productId = (string) ($itemInput['productId'] ?? '');
                                $quantity = (int) ($itemInput['quantity'] ?? 0);
                                $selectedAttributes = self::mapSelectedAttributes($itemInput['selectedAttributes'] ?? []);

                                $product = $repository->getProduct($productId);
                                if (!$product instanceof Product) {
                                    throw new RuntimeException(sprintf('Product "%s" does not exist.', $productId));
                                }

                                $orderItems[] = new OrderItem($product, $quantity, $selectedAttributes);
                            }

                            $order = new Order($orderItems);
                            $orderId = $orderRepository->create($order);

                            return [
                                'orderId' => $orderId,
                                'success' => true,
                                'message' => 'Order created successfully.',
                            ];
                        },
                    ],
                ],
            ]);

            $schema = new Schema(
                (new SchemaConfig())
                    ->setQuery($queryType)
                    ->setMutation($mutationType)
            );

            [$query, $variableValues, $operationName] = self::parseRequest();

            $result = GraphQLBase::executeQuery($schema, $query, null, null, $variableValues, $operationName);
            $output = $result->toArray();
        } catch (Throwable $e) {
            $output = [
                'errors' => [
                    [
                        'message' => $e->getMessage(),
                    ],
                ],
            ];
        }

        header('Content-Type: application/json; charset=UTF-8');
        return json_encode($output, JSON_THROW_ON_ERROR);
    }

    /**
     * @return array{0: string, 1: ?array, 2: ?string}
     */
    private static function parseRequest(): array
    {
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'GET') {
            $query = $_GET['query'] ?? null;
            if (!is_string($query) || trim($query) === '') {
                throw new RuntimeException('The GraphQL query must be a non-empty string.');
            }

            $operationName = $_GET['operationName'] ?? null;
            if (!is_string($operationName) || trim($operationName) === '') {
                $operationName = null;
            }

            return [
                $query,
                self::parseVariables($_GET['variables'] ?? null),
                $operationName,
            ];
        }

        $rawInput = file_get_contents('php://input');
        if ($rawInput === false) {
            throw new RuntimeException('Failed to get php://input');
        }

        $input = json_decode($rawInput, true);
        if (!is_array($input)) {
            throw new RuntimeException('The GraphQL request body must be valid JSON.');
        }

        $query = $input['query'] ?? null;
        if (!is_string($query) || trim($query) === '') {
            throw new RuntimeException('The GraphQL query must be a non-empty string.');
        }

        $operationName = $input['operationName'] ?? null;
        if (!is_string($operationName) || trim($operationName) === '') {
            $operationName = null;
        }

        return [
            $query,
            self::parseVariables($input['variables'] ?? null),
            $operationName,
        ];
    }

    /**
     * @param mixed $variables
     * @return ?array<string, mixed>
     */
    private static function parseVariables(mixed $variables): ?array
    {
        if ($variables === null || $variables === '') {
            return null;
        }

        if (is_array($variables)) {
            return $variables;
        }

        if (!is_string($variables)) {
            throw new RuntimeException('The GraphQL variables must be a JSON object.');
        }

        $decoded = json_decode($variables, true);
        if ($decoded === null && trim($variables) !== 'null') {
            throw new RuntimeException('The GraphQL variables must be valid JSON.');
        }

        if ($decoded === null) {
            return null;
        }

        if (!is_array($decoded)) {
            throw new RuntimeException('The GraphQL variables must decode to an object.');
        }

        return $decoded;
    }

    /**
     * @param array<int, array{attributeId?: mixed, value?: mixed}> $selectedAttributes
     * @return array<string, string>
     */
    private static function mapSelectedAttributes(array $selectedAttributes): array
    {
        $mapped = [];

        foreach ($selectedAttributes as $selectedAttribute) {
            $attributeId = (string) ($selectedAttribute['attributeId'] ?? '');
            $value = (string) ($selectedAttribute['value'] ?? '');

            if ($attributeId === '' || $value === '') {
                throw new RuntimeException('Each selected attribute must include attributeId and value.');
            }

            if (isset($mapped[$attributeId])) {
                throw new RuntimeException(sprintf(
                    'Attribute "%s" was selected more than once.',
                    $attributeId
                ));
            }

            $mapped[$attributeId] = $value;
        }

        return $mapped;
    }
}