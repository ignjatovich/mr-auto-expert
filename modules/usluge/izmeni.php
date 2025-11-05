<?php
require_once '../../config.php';
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Samo administrator i menadžer mogu pristupiti
proveri_tip(['administrator', 'menadzer']);

$id = $_GET['id'] ?? 0;
$greska = '';
$uspeh = '';

if (empty($id)) {
    header('Location: lista.php');
    exit();
}

// Preuzmi uslugu
$stmt = $conn->prepare("SELECT * FROM usluge WHERE id = ?");
$stmt->execute([$id]);
$usluga = $stmt->fetch();

if (!$usluga) {
    $_SESSION['greska'] = 'Usluga ne postoji!';
    header('Location: lista.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $naziv = trim($_POST['naziv'] ?? '');
    $cena = floatval($_POST['cena'] ?? 0);
    $aktivan = isset($_POST['aktivan']) ? 1 : 0;

    if (empty($naziv)) {
        $greska = 'Molimo unesite naziv usluge.';
    } elseif ($cena < 0) {
        $greska = 'Cena ne može biti negativna.';
    } else {
        // Proveri da li usluga sa istim nazivom već postoji (osim trenutne)
        $stmt = $conn->prepare("SELECT id FROM usluge WHERE naziv = ? AND id != ?");
        $stmt->execute([$naziv, $id]);

        if ($stmt->fetch()) {
            $greska = 'Usluga sa ovim nazivom već postoji!';
        } else {
            // Ažuriraj uslugu
            $stmt = $conn->prepare("UPDATE usluge SET naziv = ?, cena = ?, aktivan = ? WHERE id = ?");
            $stmt->execute([$naziv, $cena, $aktivan, $id]);

            $uspeh = 'Usluga je uspešno izmenjena!';

            // Osvezi podatke
            $stmt = $conn->prepare("SELECT * FROM usluge WHERE id = ?");
            $stmt->execute([$id]);
            $usluga = $stmt->fetch();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Izmeni uslugu - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
<nav class="navbar">
    <div class="nav-container">
        <div class="nav-brand">
            <a href="../../dashboard.php" style="color: inherit; text-decoration: none;">
                🚗 Tehnički pregled
            </a>
        </div>
        <div class="nav-menu">
            <span class="nav-user">
                <?php echo e($_SESSION['ime'] . ' ' . $_SESSION['prezime']); ?>
                <span class="badge badge-<?php echo $_SESSION['tip_korisnika']; ?>">
                    <?php echo ucfirst($_SESSION['tip_korisnika']); ?>
                </span>
            </span>
            <a href="../../logout.php" class="btn btn-secondary btn-sm">Odjavi se</a>
        </div>
    </div>
</nav>

<div class="container">
    <div class="page-header">
        <h1>✏️ Izmeni uslugu: <?php echo e($usluga['naziv']); ?></h1>
        <a href="lista.php" class="btn btn-secondary">← Nazad na listu</a>
    </div>

    <?php if ($greska): ?>
        <div class="alert alert-error">
            <?php echo e($greska); ?>
        </div>
    <?php endif; ?>

    <?php if ($uspeh): ?>
        <div class="alert alert-success">
            <?php echo e($uspeh); ?>
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
                        value="<?php echo e($usluga['naziv']); ?>"
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
                        value="<?php echo e($usluga['cena']); ?>"
                    >
                    <small>Unesite cenu usluge u dinarima</small>
                </div>

                <div class="form-group">
                    <label class="checkbox-label" style="border: none; padding: 0; background: transparent;">
                        <input
                            type="checkbox"
                            name="aktivan"
                            value="1"
                            <?php echo $usluga['aktivan'] ? 'checked' : ''; ?>
                        >
                        <span>Usluga je aktivna (prikazuje se pri dodavanju vozila)</span>
                    </label>
                </div>
            </div>

            <div class="form-section">
                <h2>ℹ️ Informacije</h2>
                <div class="info-box">
                    <strong>Datum kreiranja:</strong> <?php echo formatuj_datum($usluga['datum_kreiranja']); ?><br>
                    <strong>Poslednja izmena:</strong> <?php echo formatuj_datum($usluga['datum_izmene']); ?>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-lg">
                    ✅ Sačuvaj izmene
                </button>
                <a href="lista.php" class="btn btn-secondary btn-lg">
                    ❌ Otkaži
                </a>
            </div>

        </form>
    </div>
</div>
</body>
</html>