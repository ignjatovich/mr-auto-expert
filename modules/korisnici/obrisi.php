<?php
require_once '../../config.php';
require_once '../../includes/db.php';
require_once '../../includes/auth.php';

// Samo administrator i menadžer mogu pristupiti
proveri_tip(['administrator', 'menadzer']);

$id = $_GET['id'] ?? 0;

if (empty($id)) {
    header('Location: lista.php');
    exit();
}

// Preuzmi korisnika
$stmt = $conn->prepare("SELECT * FROM korisnici WHERE id = ?");
$stmt->execute([$id]);
$korisnik = $stmt->fetch();

if (!$korisnik) {
    $_SESSION['greska'] = 'Korisnik ne postoji!';
    header('Location: lista.php');
    exit();
}

// Provera da li pokušava da obriše samog sebe
if ($id == $_SESSION['korisnik_id']) {
    $_SESSION['greska'] = 'Ne možete obrisati sopstveni nalog!';
    header('Location: lista.php');
    exit();
}

// Provera da li menadžer pokušava da obriše administratora ili menadžera
if ($_SESSION['tip_korisnika'] == 'menadzer' && in_array($korisnik['tip_korisnika'], ['administrator', 'menadzer'])) {
    $_SESSION['greska'] = 'Menadžer može brisati samo zaposlene!';
    header('Location: lista.php');
    exit();
}

// Proveri da li korisnik ima kreirana vozila
$stmt = $conn->prepare("SELECT COUNT(*) as broj FROM vozila WHERE kreirao_korisnik_id = ? OR izmenjeno_korisnik_id = ?");
$stmt->execute([$id, $id]);
$broj_vozila = $stmt->fetch()['broj'];

if ($broj_vozila > 0) {
    $_SESSION['greska'] = 'Ne možete obrisati korisnika koji je kreirao ili menjao vozila! Prvo deaktivirajte nalog.';
    header('Location: lista.php');
    exit();
}

// Obriši korisnika
$stmt = $conn->prepare("DELETE FROM korisnici WHERE id = ?");
$stmt->execute([$id]);

$_SESSION['uspeh'] = 'Korisnik "' . htmlspecialchars($korisnik['korisnicko_ime']) . '" je uspešno obrisan!';
header('Location: lista.php');
exit();
?>