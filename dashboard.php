<?php
$base_url = '';
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/header.php';

proveri_login();

$ime = $_SESSION['ime'];
$prezime = $_SESSION['prezime'];
$tip = $_SESSION['tip_korisnika'];
$lokacija = $_SESSION['lokacija'];

// Statistika vozila za trenutnu lokaciju
$lokacija_korisnika = $_SESSION['lokacija'];

// Ako je administrator, uzmi sve lokacije
if ($tip == 'administrator' || $tip == 'menadzer') {
    $stmt = $conn->query("
        SELECT 
            status,
            COUNT(*) as broj
        FROM vozila
        GROUP BY status
    ");
} else {
    // Zaposleni vidi samo svoju lokaciju
    $stmt = $conn->prepare("
        SELECT 
            status,
            COUNT(*) as broj
        FROM vozila
        WHERE lokacija = ?
        GROUP BY status
    ");
    $stmt->execute([$lokacija_korisnika]);
}

$stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$u_radu = $stats['u_radu'] ?? 0;
$zavrseno = $stats['zavrseno'] ?? 0;
$placeno = $stats['placeno'] ?? 0;

// Ukupno vozila danas
if ($tip == 'administrator' || $tip == 'menadzer') {
    $stmt = $conn->query("SELECT COUNT(*) as broj FROM vozila WHERE DATE(datum_prijema) = CURDATE()");
} else {
    $stmt = $conn->prepare("SELECT COUNT(*) as broj FROM vozila WHERE lokacija = ? AND DATE(datum_prijema) = CURDATE()");
    $stmt->execute([$lokacija_korisnika]);
}
$vozila_danas = $stmt->fetch()['broj'];

// Broj korisnika (samo za admin i menadžera)
if ($tip != 'zaposleni') {
    $stmt = $conn->query("SELECT COUNT(*) as broj FROM korisnici WHERE aktivan = 1");
    $broj_korisnika = $stmt->fetch()['broj'];
}
?>

    <div class="container">
        <div class="welcome-section">
            <h1>Dobrodošli, <?php echo htmlspecialchars($ime); ?>! 👋</h1>
            <p>Lokacija: 📍<strong><?php echo htmlspecialchars($lokacija); ?></strong></p>
        </div>

        <div class="dashboard-grid">
            <div class="card">
                <h3>📋 Aktivni poslovi</h3>
                <p class="card-number"><?php echo $u_radu; ?></p>
                <p class="card-description">U toku</p>
            </div>

            <div class="card">
                <h3>✅ Završeni poslovi</h3>
                <p class="card-number"><?php echo $zavrseno; ?></p>
                <p class="card-description">Završeno</p>
            </div>

            <div class="card">
                <h3>💰 Plaćeni poslovi</h3>
                <p class="card-number"><?php echo $placeno; ?></p>
                <p class="card-description">Plaćeno</p>
            </div>

            <div class="card">
                <h3>🚗 Vozila danas</h3>
                <p class="card-number"><?php echo $vozila_danas; ?></p>
                <p class="card-description">Primljeno danas</p>
            </div>

            <?php if ($tip != 'zaposleni'): ?>
                <div class="card">
                    <h3>👥 Korisnici</h3>
                    <p class="card-number"><?php echo $broj_korisnika; ?></p>
                    <p class="card-description">Aktivnih</p>
                </div>
            <?php endif; ?>
        </div>

        <div class="quick-actions">
            <h2>Brze akcije</h2>
            <div class="action-buttons">
                <a href="modules/vozila/dodaj.php" class="btn btn-primary">➕ Dodaj vozilo</a>
                <a href="lista_vozila.php" class="btn btn-secondary">📊 Pregledaj sve poslove</a>
                <a href="modules/profil/moj_profil.php" class="btn btn-secondary">👤 Moj profil</a>
                <a href="modules/usluge/lista.php" class="btn btn-secondary">🔧 Usluge</a>
                <?php if ($tip != 'zaposleni'): ?>
                    <a href="modules/korisnici/lista.php" class="btn btn-secondary">👥 Upravljaj korisnicima</a>
                <?php endif; ?>
                <a href="modules/pravna_lica/lista.php" class="btn btn-secondary">🏢 Pravna lica</a>
            </div>
        </div>
    </div>

<?php require_once 'includes/footer.php'; ?>