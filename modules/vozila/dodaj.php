<?php
require_once '../../config.php';
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Svi tipovi korisnika mogu da dodaju vozila
proveri_login();

$greska = '';
$uspeh = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validacija
    $registracija = trim($_POST['registracija'] ?? '');
    $sasija = trim($_POST['sasija'] ?? '');
    $marka = trim($_POST['marka'] ?? '');
    $vlasnik = trim($_POST['vlasnik'] ?? '');
    $kontakt = trim($_POST['kontakt'] ?? '');
    $parking_lokacija = $_POST['parking_lokacija'] ?? '';
    $usluge = $_POST['usluge'] ?? [];
    $cena = floatval($_POST['cena'] ?? 0);
    $napomena = trim($_POST['napomena'] ?? '');

    // Datum prijema - automatski trenutno vreme
    $datum_prijema = date('Y-m-d H:i:s');

    if (empty($registracija) || empty($marka) || empty($vlasnik) || empty($kontakt) || empty($parking_lokacija)) {
        $greska = 'Molimo popunite sva obavezna polja.';
    } elseif (empty($usluge)) {
        $greska = 'Molimo izaberite bar jednu uslugu.';
    } else {
        // Upload slike
        $slika_vozila = null;
        if (isset($_FILES['slika_vozila']) && $_FILES['slika_vozila']['error'] == 0) {
            $upload_result = upload_slika($_FILES['slika_vozila']);
            if ($upload_result['success']) {
                $slika_vozila = $upload_result['filename'];
            } else {
                $greska = $upload_result['error'];
            }
        }

        if (empty($greska)) {
            // Konvertuj usluge u JSON
            $usluge_json = json_encode($usluge);

            // Insert u bazu
            $stmt = $conn->prepare("
                INSERT INTO vozila (
                    registracija, sasija, marka, vlasnik, kontakt, 
                    datum_prijema, slika_vozila, parking_lokacija, 
                    usluge, cena, napomena, 
                    kreirao_korisnik_id, lokacija, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'u_radu')
            ");

            $stmt->execute([
                $registracija,
                $sasija,
                $marka,
                $vlasnik,
                $kontakt,
                $datum_prijema,
                $slika_vozila,
                $parking_lokacija,
                $usluge_json,
                $cena,
                $napomena,
                $_SESSION['korisnik_id'],
                $_SESSION['lokacija']
            ]);

            $uspeh = 'Vozilo uspešno dodato!';

            // Resetuj formu
            $_POST = [];
        }
    }
}

// Preuzmi usluge za prikaz
$usluge_lista = get_usluge();
?>
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dodaj vozilo - <?php echo SITE_NAME; ?></title>
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
        <h1>➕ Dodaj novo vozilo</h1>
        <a href="../../lista_vozila.php" class="btn btn-secondary">📋 Vidi sve poslove</a>
    </div>

    <?php if ($greska): ?>
        <div class="alert alert-error">
            <?php echo e($greska); ?>
        </div>
    <?php endif; ?>

    <?php if ($uspeh): ?>
        <div class="alert alert-success">
            <?php echo e($uspeh); ?>
            <a href="../../lista_vozila.php">Vidi listu vozila</a>
        </div>
    <?php endif; ?>

    <div class="form-card">
        <form method="POST" enctype="multipart/form-data" id="forma-vozilo">

            <!-- IDENTIFIKACIJA VOZILA -->
            <div class="form-section">
                <h2>🚗 Identifikacija vozila</h2>

                <div class="form-row">
                    <div class="form-group">
                        <label for="registracija">Registarska oznaka *</label>
                        <input
                                type="text"
                                id="registracija"
                                name="registracija"
                                required
                                placeholder="npr. BG-123-AB"
                                value="<?php echo e($_POST['registracija'] ?? ''); ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="sasija">Broj šasije (VIN)</label>
                        <input
                                type="text"
                                id="sasija"
                                name="sasija"
                                placeholder="npr. WBA12345678901234"
                                value="<?php echo e($_POST['sasija'] ?? ''); ?>"
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label for="marka">Marka vozila *</label>
                    <input
                            type="text"
                            id="marka"
                            name="marka"
                            required
                            placeholder="npr. BMW X5"
                            value="<?php echo e($_POST['marka'] ?? ''); ?>"
                    >
                </div>
            </div>

            <!-- VLASNIK -->
            <div class="form-section">
                <h2>👤 Podaci o vlasniku</h2>

                <div class="form-group">
                    <label for="vlasnik">Ime i prezime vlasnika *</label>
                    <input
                            type="text"
                            id="vlasnik"
                            name="vlasnik"
                            required
                            placeholder="npr. Marko Marković"
                            value="<?php echo e($_POST['vlasnik'] ?? ''); ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="kontakt">Kontakt telefon *</label>
                    <input
                            type="tel"
                            id="kontakt"
                            name="kontakt"
                            required
                            placeholder="npr. 061 123 4567"
                            value="<?php echo e($_POST['kontakt'] ?? ''); ?>"
                    >
                </div>
            </div>

            <!-- DATUM I VREME -->
            <div class="form-section">
                <h2>📅 Datum i vreme prijema</h2>
                <div class="info-box">
                    <strong>Automatski:</strong> <?php echo date('d.m.Y H:i'); ?>
                    <br><small>Datum i vreme se automatski beleže prilikom dodavanja</small>
                </div>
            </div>

            <!-- SLIKA VOZILA -->
            <div class="form-section">
                <h2>📷 Slika vozila</h2>

                <div class="form-group">
                    <label for="slika_vozila">Upload slika (opciono)</label>
                    <input
                            type="file"
                            id="slika_vozila"
                            name="slika_vozila"
                            accept="image/*"
                            class="file-input"
                    >
                    <small>Max 5MB, formati: JPG, PNG, WEBP</small>
                </div>

                <div id="slika-preview"></div>
            </div>

            <!-- PARKING LOKACIJA -->
            <div class="form-section">
                <h2>🅿️ Parking lokacija</h2>

                <div class="form-group">
                    <label for="parking_lokacija">Gde je vozilo parkirano? *</label>
                    <select id="parking_lokacija" name="parking_lokacija" required>
                        <option value="">-- Izaberite --</option>
                        <option value="Silos" <?php echo (($_POST['parking_lokacija'] ?? '') == 'Silos') ? 'selected' : ''; ?>>Silos</option>
                        <option value="Balon parking" <?php echo (($_POST['parking_lokacija'] ?? '') == 'Balon parking') ? 'selected' : ''; ?>>Balon parking</option>
                        <option value="Veliki parking" <?php echo (($_POST['parking_lokacija'] ?? '') == 'Veliki parking') ? 'selected' : ''; ?>>Veliki parking</option>
                    </select>
                </div>
            </div>

            <!-- POTREBNE USLUGE -->
            <div class="form-section">
                <h2>🔧 Potrebne usluge</h2>

                <?php if (empty($usluge_lista)): ?>
                    <div class="alert alert-error">
                        Nema dostupnih usluga. Molimo administratora da doda usluge.
                        <?php if ($_SESSION['tip_korisnika'] != 'zaposleni'): ?>
                            <br><a href="../usluge/dodaj.php">Dodaj prvu uslugu</a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="checkbox-group">
                        <?php
                        $izabrane_usluge = $_POST['usluge'] ?? [];
                        foreach ($usluge_lista as $id => $usluga):
                            ?>
                            <label class="checkbox-label">
                                <input
                                        type="checkbox"
                                        name="usluge[]"
                                        value="<?php echo $id; ?>"
                                        data-cena="<?php echo $usluga['cena']; ?>"
                                        class="usluga-checkbox"
                                    <?php echo in_array($id, $izabrane_usluge) ? 'checked' : ''; ?>
                                >
                                <span>
                                    <?php echo e($usluga['naziv']); ?>
                                    <strong style="color: #667eea;">(<?php echo number_format($usluga['cena'], 2, ',', '.'); ?> RSD)</strong>
                                </span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- CENA -->
            <div class="form-section">
                <h2>💰 Cena</h2>

                <div class="form-group">
                    <label for="cena">Ukupna cena (RSD)</label>
                    <input
                            type="number"
                            id="cena"
                            name="cena"
                            step="0.01"
                            min="0"
                            placeholder="0.00"
                            value="<?php echo e($_POST['cena'] ?? '0'); ?>"
                            readonly
                            style="background: #f8f9fa; font-size: 20px; font-weight: bold; color: #28a745;"
                    >
                    <small>Cena se automatski izračunava na osnovu izabranih usluga</small>
                </div>
            </div>

            <!-- NAPOMENA -->
            <div class="form-section">
                <h2>📝 Napomena</h2>

                <div class="form-group">
                    <label for="napomena">Dodatne napomene (opciono)</label>
                    <textarea
                            id="napomena"
                            name="napomena"
                            rows="4"
                            placeholder="Unesite bilo kakve dodatne informacije..."
                    ><?php echo e($_POST['napomena'] ?? ''); ?></textarea>
                </div>
            </div>

            <!-- DUGMAD -->
            <div class="form-actions">
                <button type="submit" class="btn btn-primary btn-lg">
                    ✅ Dodaj vozilo
                </button>
                <a href="../../dashboard.php" class="btn btn-secondary btn-lg">
                    ❌ Otkaži
                </a>
            </div>

        </form>
    </div>
</div>

<script src="../../assets/js/main.js"></script>
<script>
    // Automatski račun cene
    document.addEventListener('DOMContentLoaded', function() {
        const checkboxes = document.querySelectorAll('.usluga-checkbox');
        const cenaInput = document.getElementById('cena');

        function updateCena() {
            let ukupno = 0;
            checkboxes.forEach(cb => {
                if (cb.checked) {
                    ukupno += parseFloat(cb.dataset.cena);
                }
            });
            cenaInput.value = ukupno.toFixed(2);
        }

        checkboxes.forEach(cb => {
            cb.addEventListener('change', updateCena);
        });

        // Inicijalno izračunaj cenu
        updateCena();
    });
</script>
</body>
</html>