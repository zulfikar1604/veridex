-- ============================================================
-- VERIDEX: RFQ & SPH Submission Tables Migration
-- Run this SQL in phpMyAdmin after setup_p2p_tables.sql
-- ============================================================

-- 1. Tabel RFQ (Request for Quotation)
CREATE TABLE IF NOT EXISTS rfq_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rfq_number VARCHAR(50) NOT NULL,
    inventory_id INT NOT NULL,
    jumlah_kebutuhan INT NOT NULL DEFAULT 0,
    catatan TEXT NULL,
    deadline DATETIME NULL,
    status ENUM('Open', 'Closed', 'Cancelled') DEFAULT 'Open',
    created_by INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    closed_at DATETIME NULL,
    FOREIGN KEY (inventory_id) REFERENCES inventory(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Tabel RFQ Vendors (Vendor mana saja yang diundang ke RFQ)
CREATE TABLE IF NOT EXISTS rfq_vendors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rfq_id INT NOT NULL,
    vendor_id INT NOT NULL,
    invited_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    has_submitted TINYINT(1) DEFAULT 0,
    FOREIGN KEY (rfq_id) REFERENCES rfq_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (vendor_id) REFERENCES vendors(id),
    UNIQUE KEY unique_rfq_vendor (rfq_id, vendor_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Tambah kolom rfq_id ke sph_submissions agar terhubung ke RFQ
ALTER TABLE sph_submissions 
    ADD COLUMN IF NOT EXISTS rfq_id INT NULL AFTER id,
    ADD COLUMN IF NOT EXISTS dokumen_sph VARCHAR(255) NULL AFTER harga_penawaran;
