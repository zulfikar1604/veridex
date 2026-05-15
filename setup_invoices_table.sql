-- ============================================================
-- VERIDEX: Invoices Table Setup
-- ============================================================

CREATE TABLE IF NOT EXISTS invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    po_id INT NOT NULL,
    invoice_number VARCHAR(100) NOT NULL,
    vendor_id INT NOT NULL,
    inventory_id INT NOT NULL,
    jumlah_barang INT NOT NULL DEFAULT 0,
    total_tagihan DECIMAL(15,2) NOT NULL DEFAULT 0,
    tanggal_terima_gudang DATETIME NOT NULL,
    tanggal_jatuh_tempo DATE NOT NULL,
    status ENUM('Unpaid', 'Paid') DEFAULT 'Unpaid',
    paid_at DATETIME NULL,
    paid_by INT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (po_id) REFERENCES purchase_orders(id),
    FOREIGN KEY (vendor_id) REFERENCES vendors(id),
    FOREIGN KEY (inventory_id) REFERENCES inventory(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
