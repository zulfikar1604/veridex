-- ============================================================
-- VERIDEX: Procure-to-Pay (P2P) Workflow Migration
-- Run this SQL in phpMyAdmin or MySQL CLI
-- ============================================================

-- 1. Tambah kolom tracking workflow ke tabel purchase_orders
ALTER TABLE purchase_orders 
  ADD COLUMN IF NOT EXISTS confirmed_by_vendor_at DATETIME NULL AFTER status,
  ADD COLUMN IF NOT EXISTS shipped_at DATETIME NULL AFTER confirmed_by_vendor_at,
  ADD COLUMN IF NOT EXISTS no_resi VARCHAR(100) NULL AFTER shipped_at,
  ADD COLUMN IF NOT EXISTS foto_surat_jalan VARCHAR(255) NULL AFTER no_resi,
  ADD COLUMN IF NOT EXISTS received_at DATETIME NULL AFTER foto_surat_jalan,
  ADD COLUMN IF NOT EXISTS received_by INT NULL AFTER received_at,
  ADD COLUMN IF NOT EXISTS jumlah_diterima INT DEFAULT 0 AFTER received_by,
  ADD COLUMN IF NOT EXISTS invoice_number VARCHAR(100) NULL AFTER jumlah_diterima,
  ADD COLUMN IF NOT EXISTS invoice_file VARCHAR(255) NULL AFTER invoice_number,
  ADD COLUMN IF NOT EXISTS invoice_submitted_at DATETIME NULL AFTER invoice_file,
  ADD COLUMN IF NOT EXISTS payment_matched_at DATETIME NULL AFTER invoice_submitted_at,
  ADD COLUMN IF NOT EXISTS payment_completed_at DATETIME NULL AFTER payment_matched_at,
  ADD COLUMN IF NOT EXISTS bukti_bayar_file VARCHAR(255) NULL AFTER payment_completed_at,
  ADD COLUMN IF NOT EXISTS payment_notes TEXT NULL AFTER bukti_bayar_file;

-- 2. Fix status mismatch: Standardisasi ke 'Pending Approval'
UPDATE purchase_orders SET status = 'Pending Approval' WHERE status = 'Waiting Approval';

-- 3. Buat folder uploads (jalankan manual jika perlu)
-- mkdir uploads/delivery
-- mkdir uploads/invoices  
-- mkdir uploads/payments
