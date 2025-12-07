<?php
require_once '../../config.php';
require_once '../../includes/db.php';
require_once '../../includes/auth.php';

// SVI korisnici mogu da brišu usluge
proveri_login();

$id = $_GET['id'] ?? 0;

if (empty($id)) {
    header('Location: lista.php');
    exit();
}

// Proveri da li usluga postoji
$stmt = $conn->prepare("SELECT * FROM usluge WHERE id = ?");
$stmt->execute([$id]);
$usluga = $stmt->fetch();

if (!$usluga) {
    $_SESSION['greska'] = 'Usluga ne postoji!';
    header('Location: lista.php');
    exit();
}

// Obriši uslugu iz baze
$stmt = $conn->prepare("DELETE FROM usluge WHERE id = ?");
$stmt->execute([$id]);

$_SESSION['uspeh'] = 'Usluga "' . htmlspecialchars($usluga['naziv']) . '" je uspešno obrisana!';
header('Location: lista.php');
exit();
?>