ALTER TABLE sph_submissions
ADD COLUMN diskon_jenis ENUM('Nominal', 'Persentase') DEFAULT 'Nominal' AFTER harga_penawaran,
ADD COLUMN diskon_nilai DECIMAL(15,2) DEFAULT 0 AFTER diskon_jenis,
ADD COLUMN incoterm VARCHAR(50) AFTER diskon_nilai,
ADD COLUMN payment_term VARCHAR(50) AFTER incoterm,
ADD COLUMN keterangan TEXT AFTER payment_term;
