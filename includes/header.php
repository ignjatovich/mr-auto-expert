<?php
// Header template - za korišćenje na svim stranicama
// Mora biti pozvan POSLE proveri_login() funkcije

if (!isset($_SESSION['korisnik_id'])) {
    die('Sesija nije aktivna. Pozovi proveri_login() pre include header.php');
}

$ime = $_SESSION['ime'] ?? '';
$prezime = $_SESSION['prezime'] ?? '';
$tip = $_SESSION['tip_korisnika'] ?? '';
$lokacija = $_SESSION['lokacija'] ?? '';

// Da li prikazati lokaciju? (samo za zaposlene)
$prikazi_lokaciju = ($tip == 'zaposleni');
?>
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Mr Auto Expert DOO'; ?></title>
    <link rel="stylesheet" href="<?php echo $base_url ?? ''; ?>assets/css/style.css">
    <link rel="stylesheet" href="<?php echo $base_url ?? ''; ?>assets/css/camera.css">
    <link rel="stylesheet" href="<?php echo $base_url ?? ''; ?>assets/css/header.css">
</head>
<body>

<!-- NAVBAR SA HAMBURGER MENIJEM -->
<nav class="navbar">
    <div class="nav-container">
        <!-- Logo / Brand -->
        <div class="nav-brand">
            <a href="<?php echo $base_url ?? ''; ?>dashboard.php">
                MR AUTO EXPERT DOO
            </a>
        </div>

        <!-- Desktop Menu -->
        <div class="nav-links desktop-only">
            <a href="<?php echo $base_url ?? ''; ?>dashboard.php" class="nav-link">
                📊 Dashboard
            </a>
            <a href="<?php echo $base_url ?? ''; ?>lista_vozila.php" class="nav-link">
                📋 Lista vozila
            </a>
            <a href="<?php echo $base_url ?? ''; ?>modules/pravna_lica/lista.php" class="nav-link">
                🏢 Pravna lica
            </a>
            <a href="<?php echo $base_url ?? ''; ?>modules/usluge/lista.php" class="nav-link">
                🔧 Usluge
            </a>
            <?php if ($tip != 'zaposleni'): ?>
                <a href="<?php echo $base_url ?? ''; ?>modules/korisnici/lista.php" class="nav-link">
                    👥 Korisnici
                </a>
            <?php endif; ?>
        </div>

        <!-- User Info & Hamburger -->
        <div class="nav-right">
            <div class="nav-user">
                <span class="user-name"><?php echo htmlspecialchars($ime . ' ' . $prezime); ?></span>
                <span class="badge badge-<?php echo $tip; ?>">
                    <?php echo ucfirst($tip); ?>
                </span>

            </div>

            <button class="hamburger" id="hamburger-btn" aria-label="Menu">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </div>

    <!-- Mobile Menu -->
    <div class="mobile-menu" id="mobile-menu">
        <div class="mobile-menu-header">
            <div class="mobile-user-info">
                <div class="mobile-user-name"><?php echo htmlspecialchars($ime . ' ' . $prezime); ?></div>
                <div class="mobile-user-meta">
                    <span class="badge badge-<?php echo $tip; ?>">
                        <?php echo ucfirst($tip); ?>
                    </span>
                    <?php if ($prikazi_lokaciju): ?>
                        <span class="mobile-location">📍 <?php echo htmlspecialchars($lokacija); ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="mobile-menu-links">
            <a href="<?php echo $base_url ?? ''; ?>dashboard.php" class="mobile-link">
                <span class="mobile-link-icon">📊</span>
                <span class="mobile-link-text">Dashboard</span>
            </a>
            <a href="<?php echo $base_url ?? ''; ?>lista_vozila.php" class="mobile-link">
                <span class="mobile-link-icon">📋</span>
                <span class="mobile-link-text">Lista vozila</span>
            </a>
            <a href="<?php echo $base_url ?? ''; ?>modules/pravna_lica/lista.php" class="mobile-link">
                <span class="mobile-link-icon">🏢</span>
                <span class="mobile-link-text">Pravna lica</span>
            </a>
            <a href="<?php echo $base_url ?? ''; ?>modules/usluge/lista.php" class="mobile-link">
                <span class="mobile-link-icon">🔧</span>
                <span class="mobile-link-text">Usluge</span>
            </a>
            <?php if ($tip != 'zaposleni'): ?>
                <a href="<?php echo $base_url ?? ''; ?>modules/korisnici/lista.php" class="mobile-link">
                    <span class="mobile-link-icon">👥</span>
                    <span class="mobile-link-text">Korisnici</span>
                </a>
            <?php endif; ?>
            <a href="<?php echo $base_url ?? ''; ?>modules/vozila/dodaj.php" class="mobile-link mobile-link-primary">
                <span class="mobile-link-icon">➕</span>
                <span class="mobile-link-text">Dodaj vozilo</span>
            </a>
        </div>

        <div class="mobile-menu-footer">
            <a href="<?php echo $base_url ?? ''; ?>modules/profil/moj_profil.php" class="mobile-link">
                <span class="mobile-link-icon">👤</span>
                <span class="mobile-link-text">Moj profil</span>
            </a>
            <a href="<?php echo $base_url ?? ''; ?>logout.php" class="mobile-link mobile-link-logout">
                <span class="mobile-link-icon">🚪</span>
                <span class="mobile-link-text">Odjavi se</span>
            </a>
        </div>
    </div>
</nav>

<!-- Overlay za zatvaranje menija -->
<div class="menu-overlay" id="menu-overlay"></div>

<!-- Page Content Start -->
<div class="page-wrapper">