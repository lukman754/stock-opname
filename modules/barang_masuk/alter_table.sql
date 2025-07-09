-- Add image columns to barang_masuk table
ALTER TABLE barang_masuk
    ADD COLUMN gambar_barang VARCHAR(255) NULL AFTER keterangan,
    ADD COLUMN struk VARCHAR(255) NULL AFTER gambar_barang;

-- Remove unused columns
ALTER TABLE barang_masuk
    DROP COLUMN IF EXISTS supplier,
    DROP COLUMN IF EXISTS total_nilai;

-- Remove unused columns from barang_masuk_detail
ALTER TABLE barang_masuk_detail
    DROP COLUMN IF EXISTS harga_satuan,
    DROP COLUMN IF EXISTS subtotal; 