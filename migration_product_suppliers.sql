-- Jalankan SEKALI pada database inventory_app yang sudah ada.
-- Tidak menghapus atau menimpa tabel maupun data lama.
USE inventory_app;

CREATE TABLE IF NOT EXISTS product_suppliers (
    product_id INT UNSIGNED NOT NULL,
    supplier_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (product_id, supplier_id),
    CONSTRAINT fk_product_suppliers_product
      FOREIGN KEY (product_id) REFERENCES products(id)
      ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_product_suppliers_supplier
      FOREIGN KEY (supplier_id) REFERENCES suppliers(id)
      ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_product_suppliers_supplier (supplier_id)
) ENGINE=InnoDB;

-- Memindahkan relasi pemasok yang sudah ada ke tabel penghubung.
INSERT IGNORE INTO product_suppliers(product_id, supplier_id)
SELECT id, supplier_id
FROM products
WHERE supplier_id IS NOT NULL;
