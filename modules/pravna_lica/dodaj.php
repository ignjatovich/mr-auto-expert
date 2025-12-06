<?php
require_once '../../config.php';
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Svi tipovi korisnika mogu pristupiti
proveri_login();

// Postavi promenljive za header
$page_title = 'Dodaj pravno lice - ' . SITE_NAME;
$base_url = '../../';

$greska = '';
$uspeh = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $naziv = trim($_POST['naziv'] ?? '');
    $pib = trim($_POST['pib'] ?? '');
    $kontakt_telefon = trim($_POST['kontakt_telefon'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $adresa = trim($_POST['adresa'] ?? '');
    $napomena = trim($_POST['napomena'] ?? '');
    $aktivan = isset($_POST['aktivan']) ? 1 : 0;

    if (empty($naziv)) {
        $greska = 'Molimo unesite naziv pravnog lica.';
    } else {
        // Proveri da li pravno lice sa istim nazivom već postoji
        $stmt = $conn->prepare("SELECT id FROM pravna_lica WHERE naziv = ?");
        $stmt->execute([$naziv]);

        if ($stmt->fetch()) {
            $greska = 'Pravno lice sa ovim nazivom već postoji!';
        } else {
            // Dodaj novo pravno lice
            $stmt = $conn->prepare("
                INSERT INTO pravna_lica (naziv, pib, kontakt_telefon, email, adresa, napomena, aktivan)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$naziv, $pib, $kontakt_telefon, $email, $adresa, $napomena, $aktivan]);

            $_SESSION['uspeh'] = 'Pravno lice "' . htmlspecialchars($naziv) . '" je uspešno dodato!';
            header('Location: lista.php');
            exit();
        }
    }
}

// Include header
include '../../includes/header.php';
?>

    <div class="container">
        <div class="page-header">
            <h1>➕ Dodaj pravno lice</h1>
            <a href="lista.php" class="btn btn-secondary">← Nazad na listu</a>
        </div>

        <?php if ($greska): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($greska); ?>
            </div>
        <?php endif; ?>

        <div class="form-card">
            <form method="POST" action="">

                <div class="form-section">
                    <h2>🏢 Osnovni podaci</h2>

                    <div class="form-group">
                        <label for="naziv">Naziv pravnog lica *</label>
                        <input
                            type="text"
                            id="naziv"
                            name="naziv"
                            required
                            placeholder="npr. Auto Servis DOO"
                            value="<?php echo htmlspecialchars($_POST['naziv'] ?? ''); ?>"
                            autofocus
                        >
                    </div>

                    <div class="form-group">
                        <label for="pib">PIB</label>
                        <input
                            type="text"
                            id="pib"
                            name="pib"
                            placeholder="npr. 123456789"
                            value="<?php echo htmlspecialchars($_POST['pib'] ?? ''); ?>"
                        >
                    </div>
                </div>

                <div class="form-section">
                    <h2>📞 Kontakt podaci</h2>

                    <div class="form-group">
                        <label for="kontakt_telefon">Kontakt telefon</label>
                        <input
                            type="tel"
                            id="kontakt_telefon"
                            name="kontakt_telefon"
                            placeholder="npr. 011 123 4567"
                            value="<?php echo htmlspecialchars($_POST['kontakt_telefon'] ?? ''); ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="email">Email</label>
                        <input
                            type="email"
                            id="email"
                            name="email"
                            placeholder="npr. info@firma.rs"
                            value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="adresa">Adresa</label>
                        <input
                            type="text"
                            id="adresa"
                            name="adresa"
                            placeholder="npr. Bulevar Kralja Aleksandra 1, Beograd"
                            value="<?php echo htmlspecialchars($_POST['adresa'] ?? ''); ?>"
                        >
                    </div>
                </div>

                <div class="form-section">
                    <h2>📝 Dodatno</h2>

                    <div class="form-group">
                        <label for="napomena">Napomena</label>
                        <textarea
                            id="napomena"
                            name="napomena"
                            rows="3"
                            placeholder="Dodatne napomene o pravnom licu..."
                        ><?php echo htmlspecialchars($_POST['napomena'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label class="checkbox-label" style="border: none; padding: 0; background: transparent;">
                            <input
                                type="checkbox"
                                name="aktivan"
                                value="1"
                                <?php echo (!isset($_POST['naziv']) || isset($_POST['aktivan'])) ? 'checked' : ''; ?>
                            >
                            <span>Pravno lice je aktivno (prikazuje se pri dodavanju vozila)</span>
                        </label>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-lg">
                        ✅ Dodaj pravno lice
                    </button>
                    <a href="lista.php" class="btn btn-secondary btn-lg">
                        ❌ Otkaži
                    </a>
                </div>

            </form>
        </div>
    </div>

<?php include '../../includes/footer.php'; ?>