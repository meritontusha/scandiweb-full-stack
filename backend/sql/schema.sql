
CREATE TABLE IF NOT EXISTS categories (
    id VARCHAR(255) PRIMARY KEY,
    name VARCHAR(255) NOT NULL UNIQUE
    );

CREATE TABLE IF NOT EXISTS products (
    id VARCHAR(255) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    in_stock TINYINT(1) NOT NULL,
    description LONGTEXT NOT NULL,
    category_id VARCHAR(255) NOT NULL,
    brand VARCHAR(255) NOT NULL,
    CONSTRAINT fk_products_category FOREIGN KEY (category_id) REFERENCES categories(id)
    );

CREATE TABLE IF NOT EXISTS product_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id VARCHAR(255) NOT NULL,
    image_url TEXT NOT NULL,
    sort_order INT NOT NULL,
    CONSTRAINT fk_product_images_product FOREIGN KEY (product_id) REFERENCES products(id)
    );

CREATE TABLE IF NOT EXISTS attributes (
    id VARCHAR(255) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(50) NOT NULL,
    UNIQUE KEY uq_attribute_name_type(name, type)
    );

CREATE TABLE IF NOT EXISTS attribute_items (
    id VARCHAR(255) PRIMARY KEY,
    attribute_id VARCHAR(255) NOT NULL,
    display_value VARCHAR(255) NOT NULL,
    value VARCHAR(255) NOT NULL,
    CONSTRAINT fk_attribute_items_attribute FOREIGN KEY (attribute_id) REFERENCES attributes(id)
    );

CREATE TABLE IF NOT EXISTS product_attributes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id VARCHAR(255) NOT NULL,
    attribute_id VARCHAR(255) NOT NULL,
    sort_order INT NOT NULL,
    UNIQUE KEY uq_product_attribute (product_id, attribute_id),
    CONSTRAINT fk_pa_product FOREIGN KEY (product_id) REFERENCES products(id),
    CONSTRAINT fk_pa_attribute FOREIGN KEY (attribute_id) REFERENCES attributes(id));

CREATE TABLE IF NOT EXISTS product_attribute_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id VARCHAR(255) NOT NULL,
    attribute_id VARCHAR(255) NOT NULL,
    attribute_item_id VARCHAR(255) NOT NULL,
    sort_order INT NOT NULL,
    CONSTRAINT fk_pai_product_link FOREIGN KEY (product_id) REFERENCES products(id),
    CONSTRAINT fk_pai_attribute_link FOREIGN KEY (attribute_id) REFERENCES attributes(id),
    CONSTRAINT fk_pai_item_link FOREIGN KEY (attribute_item_id) REFERENCES attribute_items(id)
    );

CREATE TABLE IF NOT EXISTS currencies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    label VARCHAR(10) NOT NULL UNIQUE,
    symbol VARCHAR(10) NOT NULL
    );

CREATE TABLE IF NOT EXISTS prices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id VARCHAR(255) NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    currency_id INT NOT NULL,
    CONSTRAINT fk_prices_product FOREIGN KEY (product_id) REFERENCES products(id),
    CONSTRAINT fk_prices_currency FOREIGN KEY (currency_id) REFERENCES currencies(id),
    UNIQUE KEY uq_product_currency (product_id, currency_id)
    );

CREATE TABLE IF NOT EXISTS orders(
    id INT AUTO_INCREMENT PRIMARY KEY,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id VARCHAR(255) NOT NULL,
    quantity INT NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    product_brand VARCHAR(255) NOT NULL,
    product_price DECIMAL(10, 2) NOT NULL,
    currency_label VARCHAR(10) NOT NULL,
    currency_symbol VARCHAR(10) NOT NULL,
    CONSTRAINT fk_order_items_order FOREIGN KEY (order_id) REFERENCES orders(id)
    );


CREATE TABLE IF NOT EXISTS order_item_attributes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_item_id INT NOT NULL,
    attribute_name VARCHAR(255) NOT NULL,
    item_display_value VARCHAR(255) NOT NULL,
    item_value VARCHAR(255) NOT NULL,
    CONSTRAINT fk_order_attr_item FOREIGN KEY (order_item_id) REFERENCES order_items(id)
    );