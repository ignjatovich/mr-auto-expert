<?php
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Provera da li je korisnik ulogovan
proveri_login();

// Postavi promenljive za header
$page_title = 'Dashboard - ' . SITE_NAME;
$base_url = './';

// Include header template
include 'includes/header.php';
?>

    <div class="container">
        <div class="welcome-section">
            <h1>Dobrodošli, <?php echo htmlspecialchars($_SESSION['ime']); ?>! 👋</h1>
            <p>Lokacija: <strong><?php echo htmlspecialchars($_SESSION['lokacija']); ?></strong></p>
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

            <?php if ($_SESSION['tip_korisnika'] == 'administrator'): ?>
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
                <a href="lista_vozila.php" class="btn btn-secondary">📊 Pregledaj sve poslove</a>
                <?php if ($_SESSION['tip_korisnika'] != 'zaposleni'): ?>
                    <a href="modules/usluge/lista.php" class="btn btn-secondary">🔧 Upravljaj uslugama</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

<?php include 'includes/footer.php'; ?>