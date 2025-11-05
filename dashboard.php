<?php
require_once 'config.php';
require_once 'includes/db.php';

// Provera da li je korisnik ulogovan
if (!isset($_SESSION['korisnik_id'])) {
    header('Location: login.php');
    exit();
}

$ime = $_SESSION['ime'];
$prezime = $_SESSION['prezime'];
$tip = $_SESSION['tip_korisnika'];
$lokacija = $_SESSION['lokacija'];
?>
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<nav class="navbar">
    <div class="nav-container">
        <div class="nav-brand">
            🚗 Mr Auto Expert DOO
        </div>
        <div class="nav-menu">
                <span class="nav-user">
                    <?php echo htmlspecialchars($ime . ' ' . $prezime); ?>
                    <span class="badge badge-<?php echo $tip; ?>">
                        <?php echo ucfirst($tip); ?>
                    </span>
                </span>
            <a href="logout.php" class="btn btn-secondary btn-sm">Odjavi se</a>
        </div>
    </div>
</nav>

<div class="container">
    <div class="welcome-section">
        <h1>Dobrodošli, <?php echo htmlspecialchars($ime); ?>! 👋</h1>
        <p>Lokacija: <strong><?php echo htmlspecialchars($lokacija); ?></strong></p>
    </div>

    <div class="dashboard-grid">
        <div class="card">
            <h3>📋 Aktivni poslovi</h3>
            <p class="card-number">0</p>
            <p class="card-description">U toku</p>
        </div>

        <div class="card">
            <h3>✅ Završeni poslovi</h3>
            <p class="card-number">0</p>
            <p class="card-description">Danas</p>
        </div>

        <div class="card">
            <h3>💰 Plaćeni poslovi</h3>
            <p class="card-number">0</p>
            <p class="card-description">Danas</p>
        </div>

        <?php if ($tip == 'administrator'): ?>
            <div class="card">
                <h3>👥 Korisnici</h3>
                <p class="card-number">1</p>
                <p class="card-description">Ukupno</p>
            </div>
        <?php endif; ?>
    </div>

    <div class="quick-actions">
        <h2>Brze akcije</h2>
        <div class="action-buttons">
            <a href="modules/vozila/dodaj.php" class="btn btn-primary">➕ Dodaj vozilo</a>
            <a href="#" class="btn btn-secondary">📊 Pregledaj sve poslove</a>
            <?php if ($tip != 'zaposleni'): ?>
                <a href="#" class="btn btn-secondary">👥 Upravljaj korisnicima</a>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>