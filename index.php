<?php
require_once 'config.php';

// Ako je korisnik ulogovan, preusmeri na dashboard
if (isset($_SESSION['korisnik_id'])) {
    header('Location: dashboard.php');
    exit();
}

// Ako nije ulogovan, preusmeri na login
header('Location: login.php');
exit();
?>