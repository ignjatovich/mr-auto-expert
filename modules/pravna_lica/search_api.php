<?php
require_once '../../config.php';
require_once '../../includes/db.php';
require_once '../../includes/auth.php';

// Provera login-a
proveri_login();

// Postavi JSON header
header('Content-Type: application/json; charset=utf-8');

// Dobavi search termin
$search = $_GET['q'] ?? '';

if (empty($search)) {
    echo json_encode([]);
    exit();
}

// Pretraži pravna lica
$stmt = $conn->prepare("
    SELECT id, naziv, pib, kontakt_telefon
    FROM pravna_lica
    WHERE aktivan = 1
    AND (naziv LIKE ? OR pib LIKE ?)
    ORDER BY naziv ASC
    LIMIT 10
");

$searchParam = "%$search%";
$stmt->execute([$searchParam, $searchParam]);
$rezultati = $stmt->fetchAll();

echo json_encode($rezultati, JSON_UNESCAPED_UNICODE);
?>