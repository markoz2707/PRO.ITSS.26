-- --------------------------------------------------------
-- ITSS Project Management System - Rozszerzenie KSeF
-- --------------------------------------------------------

-- Dodanie kolumny ksef_reference_number do tabeli invoices
-- Zapewnia unikalność faktur importowanych z KSeF

ALTER TABLE invoices ADD COLUMN IF NOT EXISTS ksef_reference_number VARCHAR(100) NULL AFTER invoice_number;

-- Dodanie indeksu, ale pozwalającego na wiele NULL-i
CREATE UNIQUE INDEX idx_invoices_ksef_ref ON invoices(ksef_reference_number)
WHERE ksef_reference_number IS NOT NULL;
