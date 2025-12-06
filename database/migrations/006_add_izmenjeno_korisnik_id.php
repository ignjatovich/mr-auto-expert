<?php
return [
    'up' => "
        ALTER TABLE vozila 
        ADD COLUMN izmenjeno_korisnik_id INT DEFAULT NULL AFTER kreirao_korisnik_id,
        ADD FOREIGN KEY (izmenjeno_korisnik_id) REFERENCES korisnici(id) ON DELETE SET NULL;
    ",

    'down' => "
        ALTER TABLE vozila 
        DROP FOREIGN KEY vozila_ibfk_2,
        DROP COLUMN izmenjeno_korisnik_id;
    "
];
?>