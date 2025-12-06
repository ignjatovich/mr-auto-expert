<?php
return [
    'up' => "
        ALTER TABLE vozila
        ADD COLUMN tip_klijenta ENUM('fizicko', 'pravno') DEFAULT 'fizicko' AFTER vlasnik,
        ADD COLUMN pravno_lice_id INT NULL AFTER tip_klijenta,
        ADD INDEX idx_tip_klijenta (tip_klijenta),
        ADD INDEX idx_pravno_lice (pravno_lice_id),
        ADD CONSTRAINT fk_vozila_pravno_lice 
            FOREIGN KEY (pravno_lice_id) 
            REFERENCES pravna_lica(id) 
            ON DELETE RESTRICT;
    ",

    'down' => "
        ALTER TABLE vozila
        DROP FOREIGN KEY fk_vozila_pravno_lice,
        DROP INDEX idx_pravno_lice,
        DROP INDEX idx_tip_klijenta,
        DROP COLUMN pravno_lice_id,
        DROP COLUMN tip_klijenta;
    "
];
?>