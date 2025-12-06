<?php
return [
    'up' => "
        CREATE TABLE IF NOT EXISTS pravna_lica (
            id INT AUTO_INCREMENT PRIMARY KEY,
            
            -- Osnovni podaci
            naziv VARCHAR(200) NOT NULL,
            pib VARCHAR(20),
            
            -- Kontakt podaci
            kontakt_telefon VARCHAR(50),
            email VARCHAR(100),
            adresa VARCHAR(255),
            
            -- Status
            aktivan TINYINT(1) DEFAULT 1,
            
            -- Napomene
            napomena TEXT,
            
            -- Timestampovi
            datum_kreiranja TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            datum_izmene TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            -- Indeksi
            INDEX idx_naziv (naziv),
            INDEX idx_pib (pib),
            INDEX idx_aktivan (aktivan)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",

    'down' => "
        DROP TABLE IF EXISTS pravna_lica;
    "
];
?>