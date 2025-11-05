<?php
// Provera da li je korisnik ulogovan
function proveri_login() {
    if (!isset($_SESSION['korisnik_id'])) {
        header('Location: /mr-auto-expert/login.php');
        exit();
    }
}

// Provera tipa korisnika
function proveri_tip($dozvoljeni_tipovi = []) {
    proveri_login();

    if (!in_array($_SESSION['tip_korisnika'], $dozvoljeni_tipovi)) {
        header('Location: /mr-auto-expert/dashboard.php');
        exit();
    }
}

// Provera da li korisnik može da izmeni drugog korisnika
function moze_izmeniti_korisnika($ciljni_tip) {
    $trenutni_tip = $_SESSION['tip_korisnika'];

    // Administrator može sve
    if ($trenutni_tip == 'administrator') {
        return true;
    }

    // Menadžer može samo zaposlene
    if ($trenutni_tip == 'menadzer' && $ciljni_tip == 'zaposleni') {
        return true;
    }

    return false;
}
?>