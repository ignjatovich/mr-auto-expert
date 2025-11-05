<?php
return [
    'up' => "
        CREATE TABLE IF NOT EXISTS vozila (
            id INT AUTO_INCREMENT PRIMARY KEY,
            
            -- Identifikacija vozila
            registracija VARCHAR(20) NOT NULL,
            sasija VARCHAR(50),
            marka VARCHAR(100) NOT NULL,
            
            -- Vlasnik
            vlasnik VARCHAR(200) NOT NULL,
            kontakt VARCHAR(50) NOT NULL,
            
            -- Datum i vreme
            datum_prijema DATETIME NOT NULL,
            
            -- Slika vozila
            slika_vozila VARCHAR(255),
            
            -- Parking lokacija
            parking_lokacija ENUM('Silos', 'Balon parking', 'Veliki parking') NOT NULL,
            
            -- Potrebne usluge (čuvamo kao JSON)
            usluge JSON NOT NULL,
            
            -- Cena
            cena DECIMAL(10, 2) DEFAULT 0.00,
            
            -- Status
            status ENUM('u_radu', 'zavrseno', 'placeno') DEFAULT 'u_radu',
            
            -- Ko je kreirao i gde
            kreirao_korisnik_id INT NOT NULL,
            lokacija ENUM('Ostružnica', 'Žarkovo', 'Mirijevo') NOT NULL,
            
            -- Napomene
            napomena TEXT,
            
            -- Timestampovi
            datum_kreiranja TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            datum_izmene TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            
            -- Indeksi za brže pretrage
            INDEX idx_registracija (registracija),
            INDEX idx_status (status),
            INDEX idx_lokacija (lokacija),
            INDEX idx_datum_prijema (datum_prijema),
            INDEX idx_kreirao (kreirao_korisnik_id),
            
            FOREIGN KEY (kreirao_korisnik_id) REFERENCES korisnici(id) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ",

    'down' => "
        DROP TABLE IF EXISTS vozila;
    "
];
?>