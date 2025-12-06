<?php
return [
    'up' => "
        ALTER TABLE vozila 
        ADD COLUMN custom_usluge JSON DEFAULT NULL AFTER usluge,
        ADD COLUMN kontakt_osoba VARCHAR(200) DEFAULT NULL AFTER pravno_lice_id;
    ",

    'down' => "
        ALTER TABLE vozila 
        DROP COLUMN custom_usluge,
        DROP COLUMN kontakt_osoba;
    "
];
?>