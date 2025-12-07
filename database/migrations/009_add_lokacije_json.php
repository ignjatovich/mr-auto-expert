<?php
return [
    'up' => "
        ALTER TABLE korisnici 
        ADD COLUMN lokacije JSON DEFAULT NULL AFTER lokacija,
        ADD COLUMN sve_lokacije TINYINT(1) DEFAULT 0 AFTER lokacije;
        
        -- Migracija postojećih podataka
        UPDATE korisnici 
        SET lokacije = JSON_ARRAY(lokacija)
        WHERE lokacija IS NOT NULL;
        
        -- Administrator automatski ima sve lokacije
        UPDATE korisnici 
        SET sve_lokacije = 1
        WHERE tip_korisnika = 'administrator';
    ",

    'down' => "
        ALTER TABLE korisnici 
        DROP COLUMN lokacije,
        DROP COLUMN sve_lokacije;
    "
];
?>