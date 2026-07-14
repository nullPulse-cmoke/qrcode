-- Giza Kids - Database Setup (PostgreSQL for Supabase)
-- Run this in Supabase SQL Editor

CREATE TABLE IF NOT EXISTS products (
    id SERIAL PRIMARY KEY,
    product_code VARCHAR(20) UNIQUE NOT NULL,
    name VARCHAR(255) NOT NULL,
    purchase_price DECIMAL(10,2) NOT NULL,
    sale_price DECIMAL(10,2) NOT NULL,
    min_sale_price DECIMAL(10,2) NULL,
    max_sale_price DECIMAL(10,2) NULL,
    image_path VARCHAR(500) NOT NULL DEFAULT '',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX IF NOT EXISTS idx_product_code ON products(product_code);
CREATE INDEX IF NOT EXISTS idx_name ON products(name);

-- Auto-update updated_at
CREATE OR REPLACE FUNCTION update_updated_at()
RETURNS TRIGGER AS $$ BEGIN NEW.updated_at = CURRENT_TIMESTAMP; RETURN NEW; END; $$ language 'plpgsql';

DROP TRIGGER IF EXISTS trg_products_updated_at ON products;
CREATE TRIGGER trg_products_updated_at BEFORE UPDATE ON products FOR EACH ROW EXECUTE FUNCTION update_updated_at();

-- Sample data
INSERT INTO products (product_code, name, purchase_price, sale_price, min_sale_price, max_sale_price) VALUES
('P-00001', 'Blue Denim Jacket', 1200.00, 2500.00, 1800.00, 3000.00),
('P-00002', 'Pink T-Shirt with Unicorn', 350.00, 890.00, 650.00, 1200.00),
('P-00003', 'Kids Sneakers - Size 28', 1500.00, 3200.00, 2500.00, 4000.00)
ON CONFLICT (product_code) DO NOTHING;
