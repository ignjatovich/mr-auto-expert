<?php
return [
    'up' => "
        CREATE TABLE IF NOT EXISTS usluge (
            id INT AUTO_INCREMENT PRIMARY KEY,
            naziv VARCHAR(200) NOT NULL,
            cena DECIMAL(10, 2) NOT NULL DEFAULT 0.00,
            aktivan TINYINT(1) DEFAULT 1,
            datum_kreiranja TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            datum_izmene TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_aktivan (aktivan)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        
        -- Dodaj default usluge
        INSERT INTO usluge (naziv, cena) VALUES
        ('Tehnički pregled', 5000.00),
        ('Registracija vozila', 8000.00),
        ('Carina', 15000.00),
        ('Ugradnja tahografa', 25000.00),
        ('Ispitivanje vozila', 3000.00),
        ('Reatest TNG/KPG', 4000.00),
        ('Utiskivanje identifikacionih oznaka', 2000.00),
        ('Izdavanje probnih tablica', 1500.00);
    ",

    'down' => "
        DROP TABLE IF EXISTS usluge;
    "
];
?>