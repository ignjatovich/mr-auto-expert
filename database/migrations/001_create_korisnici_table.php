<?php
return [
    'up' => "
        CREATE TABLE IF NOT EXISTS korisnici (
            id INT AUTO_INCREMENT PRIMARY KEY,
            korisnicko_ime VARCHAR(50) UNIQUE NOT NULL,
            sifra VARCHAR(255) NOT NULL,
            ime VARCHAR(100) NOT NULL,
            prezime VARCHAR(100) NOT NULL,
            email VARCHAR(100),
            tip_korisnika ENUM('administrator', 'menadzer', 'zaposleni') NOT NULL,
            lokacija ENUM('Ostružnica', 'Žarkovo', 'Mirijevo') NOT NULL,
            aktivan TINYINT(1) DEFAULT 1,
            datum_kreiranja TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_korisnicko_ime (korisnicko_ime),
            INDEX idx_tip (tip_korisnika)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        
        -- Dodaj default admin korisnika ako ne postoji
        INSERT IGNORE INTO korisnici (korisnicko_ime, sifra, ime, prezime, email, tip_korisnika, lokacija) 
        VALUES (
            'admin', 
            '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 
            'Administrator', 
            'System', 
            'admin@tehpregled.rs', 
            'administrator', 
            'Ostružnica'
        );
    ",

    'down' => "
        DROP TABLE IF EXISTS korisnici;
    "
];
?>