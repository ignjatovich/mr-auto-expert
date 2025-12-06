<?php
require_once '../../config.php';
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Samo administrator i menadžer mogu pristupiti
proveri_tip(['administrator', 'menadzer']);

// Postavi promenljive za header
$page_title = 'Dodaj uslugu - ' . SITE_NAME;
$base_url = '../../';

$greska = '';
$uspeh = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $naziv = trim($_POST['naziv'] ?? '');
    $cena = floatval($_POST['cena'] ?? 0);
    $aktivan = isset($_POST['aktivan']) ? 1 : 0;

    if (empty($naziv)) {
        $greska = 'Molimo unesite naziv usluge.';
    } elseif ($cena < 0) {
        $greska = 'Cena ne može biti negativna.';
    } else {
        // Proveri da li usluga već postoji
        $stmt = $conn->prepare("SELECT id FROM usluge WHERE naziv = ?");
        $stmt->execute([$naziv]);

        if ($stmt->fetch()) {
            $greska = 'Usluga sa ovim nazivom već postoji!';
        } else {
            // Dodaj novu uslugu
            $stmt = $conn->prepare("INSERT INTO usluge (naziv, cena, aktivan) VALUES (?, ?, ?)");
            $stmt->execute([$naziv, $cena, $aktivan]);

            $_SESSION['uspeh'] = 'Usluga "' . htmlspecialchars($naziv) . '" je uspešno dodata!';
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
            <h1>➕ Dodaj novu uslugu</h1>
            <a href="lista.php" class="btn btn-secondary">← Nazad na listu</a>
        </div>

        <?php if ($greska): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($greska); ?>
            </div>
        <?php endif; ?>

        <?php if ($uspeh): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($uspeh); ?>
            </div>
        <?php endif; ?>

        <div class="form-card">
            <form method="POST" action="">

                <div class="form-section">
                    <h2>🔧 Podaci o usluzi</h2>

                    <div class="form-group">
                        <label for="naziv">Naziv usluge *</label>
                        <input
                                type="text"
                                id="naziv"
                                name="naziv"
                                required
                                placeholder="npr. Tehnički pregled"
                                value="<?php echo htmlspecialchars($_POST['naziv'] ?? ''); ?>"
                                autofocus
                        >
                    </div>

                    <div class="form-group">
                        <label for="cena">Cena (RSD) *</label>
                        <input
                                type="number"
                                id="cena"
                                name="cena"
                                step="0.01"
                                min="0"
                                required
                                placeholder="0.00"
                                value="<?php echo htmlspecialchars($_POST['cena'] ?? '0'); ?>"
                        >
                        <small>Unesite cenu usluge u dinarima</small>
                    </div>

                    <div class="form-group">
                        <label class="checkbox-label" style="border: none; padding: 0; background: transparent;">
                            <input
                                    type="checkbox"
                                    name="aktivan"
                                    value="1"
                                <?php echo (isset($_POST['aktivan']) || !isset($_POST['naziv'])) ? 'checked' : ''; ?>
                            >
                            <span>Usluga je aktivna (prikazuje se pri dodavanju vozila)</span>
                        </label>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-lg">
                        ✅ Dodaj uslugu
                    </button>
                    <a href="lista.php" class="btn btn-secondary btn-lg">
                        ❌ Otkaži
                    </a>
                </div>

            </form>
        </div>
    </div>

<?php include '../../includes/footer.php'; ?>