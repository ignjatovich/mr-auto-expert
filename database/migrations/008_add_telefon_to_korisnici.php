<?php
return [
    'up' => "
        ALTER TABLE korisnici 
        ADD COLUMN telefon VARCHAR(50) DEFAULT NULL AFTER email;
    ",

    'down' => "
        ALTER TABLE korisnici 
        DROP COLUMN telefon;
    "
];
?>