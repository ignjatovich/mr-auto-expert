<?php
require_once '../../config.php';
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Svi tipovi korisnika mogu da dodaju vozila
proveri_login();

// Postavi promenljive za header
$page_title = 'Dodaj vozilo - ' . SITE_NAME;
$base_url = '../../';
$include_camera_js = true;

$greska = '';
$uspeh = '';

// Određivanje dostupnih lokacija za korisnika
$dostupne_lokacije = [];
if ($_SESSION['tip_korisnika'] == 'administrator' || $_SESSION['tip_korisnika'] == 'menadzer') {
    // Admin i menadžer mogu da biraju sve lokacije
    $dostupne_lokacije = ['Ostružnica', 'Žarkovo', 'Mirijevo'];
} else {
    // Zaposleni mogu da biraju samo svoju lokaciju
    $dostupne_lokacije = [$_SESSION['lokacija']];
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validacija
    $registracija = trim($_POST['registracija'] ?? '');
    $sasija = trim($_POST['sasija'] ?? '');
    $marka = trim($_POST['marka'] ?? '');
    $vlasnik = trim($_POST['vlasnik'] ?? '');
    $kontakt = trim($_POST['kontakt'] ?? '');
    $parking_lokacija = $_POST['parking_lokacija'] ?? '';
    $lokacija_vozila = $_POST['lokacija_vozila'] ?? '';
    $usluge = $_POST['usluge'] ?? [];
    $cena = floatval($_POST['cena'] ?? 0);
    $napomena = trim($_POST['napomena'] ?? '');

    // Datum prijema - automatski trenutno vreme
    $datum_prijema = date('Y-m-d H:i:s');

    if (empty($registracija) || empty($marka) || empty($vlasnik) || empty($kontakt) || empty($parking_lokacija)) {
        $greska = 'Molimo popunite sva obavezna polja.';
    } elseif (empty($lokacija_vozila)) {
        $greska = 'Molimo izaberite lokaciju vozila.';
    } elseif (empty($usluge)) {
        $greska = 'Molimo izaberite bar jednu uslugu.';
    } elseif (!in_array($lokacija_vozila, $dostupne_lokacije)) {
        // Provera da li je korisnik pokušao da izabere lokaciju koja mu nije dostupna
        $greska = 'Nemate dozvolu da dodate vozilo na izabranu lokaciju.';
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
                $lokacija_vozila,
            ]);

            $uspeh = 'Vozilo uspešno dodato!';

            // Resetuj formu
            $_POST = [];
        }
    }
}

// Preuzmi usluge za prikaz
$usluge_lista = get_usluge();

// Include header
include '../../includes/header.php';
?>

    <div class="container">
        <div class="page-header">
            <h1>➕ Dodaj novo vozilo</h1>
            <a href="../../lista_vozila.php" class="btn btn-secondary">📋 Vidi sve poslove</a>
        </div>

        <?php if ($greska): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($greska); ?>
            </div>
        <?php endif; ?>

        <?php if ($uspeh): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($uspeh); ?>
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
                                    value="<?php echo htmlspecialchars($_POST['registracija'] ?? ''); ?>"
                            >
                        </div>

                        <div class="form-group">
                            <label for="sasija">Broj šasije (VIN)</label>
                            <input
                                    type="text"
                                    id="sasija"
                                    name="sasija"
                                    placeholder="npr. WBA12345678901234"
                                    value="<?php echo htmlspecialchars($_POST['sasija'] ?? ''); ?>"
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
                                value="<?php echo htmlspecialchars($_POST['marka'] ?? ''); ?>"
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
                                value="<?php echo htmlspecialchars($_POST['vlasnik'] ?? ''); ?>"
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
                                value="<?php echo htmlspecialchars($_POST['kontakt'] ?? ''); ?>"
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

                <!-- SLIKA VOZILA - SA KAMEROM -->
                <div class="form-section">
                    <h2>📷 Slika vozila</h2>

                    <input type="file" id="slika_vozila" name="slika_vozila" accept="image/*">

                    <div class="upload-options">
                        <button type="button" class="upload-btn" id="camera-btn">
                            <span class="icon">📸</span>
                            <span class="text">Uslikaj sada</span>
                            <span class="subtext">Otvori kameru</span>
                        </button>

                        <button type="button" class="upload-btn" id="upload-btn">
                            <span class="icon">📁</span>
                            <span class="text">Uploaduj sliku</span>
                            <span class="subtext">Izaberi sa uređaja</span>
                        </button>
                    </div>

                    <div id="slika-preview"></div>
                </div>

                <!-- LOKACIJA VOZILA -->
                <div class="form-section">
                    <h2>📍 Lokacija vozila</h2>

                    <div class="form-group">
                        <label for="lokacija_vozila">Na kojoj lokaciji se vrši tehnički pregled? *</label>
                        <select id="lokacija_vozila" name="lokacija_vozila" required>
                            <option value="">-- Izaberite lokaciju --</option>
                            <?php foreach ($dostupne_lokacije as $lok): ?>
                                <option value="<?php echo $lok; ?>" <?php echo (($_POST['lokacija_vozila'] ?? '') == $lok) ? 'selected' : ''; ?>>
                                    <?php echo $lok; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($_SESSION['tip_korisnika'] == 'zaposleni'): ?>
                            <small>Vi možete dodavati vozila samo na Vašoj lokaciji: <strong><?php echo $_SESSION['lokacija']; ?></strong></small>
                        <?php else: ?>
                            <small>Izaberite lokaciju na kojoj se nalazi vozilo</small>
                        <?php endif; ?>
                    </div>
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
                                    <?php echo htmlspecialchars($usluga['naziv']); ?>
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
                                value="<?php echo htmlspecialchars($_POST['cena'] ?? '0'); ?>"
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
                        ><?php echo htmlspecialchars($_POST['napomena'] ?? ''); ?></textarea>
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

    <!-- KAMERA MODAL -->
    <div id="camera-modal" class="camera-modal">
        <div class="camera-container">
            <button id="close-camera">×</button>
            <div class="camera-info">📸 Pozicionirajte vozilo i kliknite na dugme</div>
            <video id="camera-video" class="camera-video" autoplay playsinline></video>
            <canvas id="camera-canvas" class="camera-canvas"></canvas>
            <div class="camera-controls">
                <button type="button" id="switch-camera-btn" class="camera-btn">
                    🔄
                </button>
                <button type="button" id="capture-btn" class="camera-btn">
                    📸
                </button>
            </div>
        </div>
    </div>

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

<?php include '../../includes/footer.php'; ?>