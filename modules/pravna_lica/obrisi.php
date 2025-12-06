<?php
require_once '../../config.php';
require_once '../../includes/db.php';
require_once '../../includes/auth.php';

// Svi tipovi korisnika mogu pristupiti
proveri_login();

$id = $_GET['id'] ?? 0;

if (empty($id)) {
    header('Location: lista.php');
    exit();
}

// Proveri da li pravno lice postoji
$stmt = $conn->prepare("SELECT * FROM pravna_lica WHERE id = ?");
$stmt->execute([$id]);
$pravno_lice = $stmt->fetch();

if (!$pravno_lice) {
    $_SESSION['greska'] = 'Pravno lice ne postoji!';
    header('Location: lista.php');
    exit();
}

// Proveri da li postoje vozila koja koriste ovo pravno lice
$stmt = $conn->prepare("SELECT COUNT(*) as broj FROM vozila WHERE pravno_lice_id = ?");
$stmt->execute([$id]);
$result = $stmt->fetch();

if ($result['broj'] > 0) {
    $_SESSION['greska'] = 'Ne možete obrisati pravno lice jer postoje vozila koja su povezana sa njim! Prvo obrišite sva vozila ili ih promenite na fizička lica.';
    header('Location: lista.php');
    exit();
}

// Obriši pravno lice
$stmt = $conn->prepare("DELETE FROM pravna_lica WHERE id = ?");
$stmt->execute([$id]);

$_SESSION['uspeh'] = 'Pravno lice "' . htmlspecialchars($pravno_lice['naziv']) . '" je uspešno obrisano!';
header('Location: lista.php');
exit();
?>