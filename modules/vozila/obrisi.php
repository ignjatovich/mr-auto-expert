<?php
require_once '../../config.php';
require_once '../../includes/db.php';
require_once '../../includes/auth.php';

// Provera login-a
proveri_login();

$id = $_GET['id'] ?? 0;

if (empty($id)) {
    header('Location: ../../lista_vozila.php');
    exit();
}

// Proveri da li vozilo postoji
$stmt = $conn->prepare("SELECT * FROM vozila WHERE id = ?");
$stmt->execute([$id]);
$vozilo = $stmt->fetch();

if (!$vozilo) {
    $_SESSION['greska'] = 'Vozilo ne postoji!';
    header('Location: ../../lista_vozila.php');
    exit();
}

// Provera pristupa
// - Administrator i menadžer mogu brisati SVA vozila
// - Zaposleni mogu brisati SAMO SVOJA vozila
$tip = $_SESSION['tip_korisnika'];
$korisnik_id = $_SESSION['korisnik_id'];

if ($tip == 'zaposleni' && $vozilo['kreirao_korisnik_id'] != $korisnik_id) {
    $_SESSION['greska'] = 'Nemate pristup ovom vozilu! Možete brisati samo vozila koja ste Vi dodali.';
    header('Location: ../../lista_vozila.php');
    exit();
}

// Obriši sliku ako postoji
if ($vozilo['slika_vozila']) {
    $slika_path = __DIR__ . '/../../uploads/vozila/' . $vozilo['slika_vozila'];
    if (file_exists($slika_path)) {
        unlink($slika_path);
    }
}

// Obriši vozilo iz baze
$stmt = $conn->prepare("DELETE FROM vozila WHERE id = ?");
$stmt->execute([$id]);

$_SESSION['uspeh'] = 'Vozilo ' . htmlspecialchars($vozilo['registracija']) . ' je uspešno obrisano!';
header('Location: ../../lista_vozila.php');
exit();
?>