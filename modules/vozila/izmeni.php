<?php
require_once '../../config.php';
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Samo administrator i menadžer mogu da izmene vozila
proveri_tip(['administrator', 'menadzer']);

// Postavi promenljive za header
$page_title = 'Izmeni vozilo - ' . SITE_NAME;
$base_url = '../../';
$include_camera_js = true;

$id = $_GET['id'] ?? 0;
$greska = '';
$uspeh = '';

if (empty($id)) {
    header('Location: ../../lista_vozila.php');
    exit();
}

// Preuzmi vozilo
$stmt = $conn->prepare("SELECT * FROM vozila WHERE id = ?");
$stmt->execute([$id]);
$vozilo = $stmt->fetch();

if (!$vozilo) {
    $_SESSION['greska'] = 'Vozilo ne postoji!';
    header('Location: ../../lista_vozila.php');
    exit();
}

// Dekoduj usluge - pazi na stare i nove formate
$trenutne_usluge = json_decode($vozilo['usluge'], true);

// Ako su usluge u starom formatu (asocijativni niz), pretvori u prazan niz
if (!empty($trenutne_usluge) && !isset($trenutne_usluge[0])) {
    $trenutne_usluge = [];
}

// Određivanje dostupnih lokacija za korisnika
$dostupne_lokacije = [];
if ($_SESSION['tip_korisnika'] == 'administrator' || $_SESSION['tip_korisnika'] == 'menadzer') {
    $dostupne_lokacije = ['Ostružnica', 'Žarkovo', 'Mirijevo'];
} else {
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
    $status = $_POST['status'] ?? 'u_radu';

    if (empty($registracija) || empty($marka) || empty($vlasnik) || empty($kontakt) || empty($parking_lokacija)) {
        $greska = 'Molimo popunite sva obavezna polja.';
    } elseif (empty($lokacija_vozila)) {
        $greska = 'Molimo izaberite lokaciju vozila.';
    } elseif (empty($usluge)) {
        $greska = 'Molimo izaberite bar jednu uslugu.';
    } elseif (!in_array($lokacija_vozila, $dostupne_lokacije)) {
        $greska = 'Nemate dozvolu da dodate vozilo na izabranu lokaciju.';
    } else {
        // Upload nove slike ako je priložena
        $slika_vozila = $vozilo['slika_vozila']; // Zadrži staru sliku

        if (isset($_FILES['slika_vozila']) && $_FILES['slika_vozila']['error'] == 0) {
            // Obriši staru sliku
            if ($vozilo['slika_vozila']) {
                $stara_slika = __DIR__ . '/../../uploads/vozila/' . $vozilo['slika_vozila'];
                if (file_exists($stara_slika)) {
                    unlink($stara_slika);
                }
            }

            // Upload nove slike
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

            // Update u bazi
            $stmt = $conn->prepare("
                UPDATE vozila SET
                    registracija = ?,
                    sasija = ?,
                    marka = ?,
                    vlasnik = ?,
                    kontakt = ?,
                    slika_vozila = ?,
                    parking_lokacija = ?,
                    lokacija = ?,
                    usluge = ?,
                    cena = ?,
                    napomena = ?,
                    status = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $registracija,
                $sasija,
                $marka,
                $vlasnik,
                $kontakt,
                $slika_vozila,
                $parking_lokacija,
                $lokacija_vozila,
                $usluge_json,
                $cena,
                $napomena,
                $status,
                $id
            ]);

            $uspeh = 'Vozilo uspešno izmenjeno!';

            // Osvezi podatke
            $stmt = $conn->prepare("SELECT * FROM vozila WHERE id = ?");
            $stmt->execute([$id]);
            $vozilo = $stmt->fetch();
            $trenutne_usluge = json_decode($vozilo['usluge'], true);
        }
    }
}

// Preuzmi dostupne usluge
$usluge_lista = get_usluge();

// Include header
include '../../includes/header.php';
?>

    <div class="container">
        <div class="page-header">
            <h1>✏️ Izmeni vozilo: <?php echo htmlspecialchars($vozilo['registracija']); ?></h1>
            <div>
                <a href="detalji.php?id=<?php echo $id; ?>" class="btn btn-secondary">← Nazad</a>
                <a href="../../lista_vozila.php" class="btn btn-secondary">📋 Lista vozila</a>
            </div>
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
                                    value="<?php echo htmlspecialchars($vozilo['registracija']); ?>"
                            >
                        </div>

                        <div class="form-group">
                            <label for="sasija">Broj šasije (VIN)</label>
                            <input
                                    type="text"
                                    id="sasija"
                                    name="sasija"
                                    placeholder="npr. WBA12345678901234"
                                    value="<?php echo htmlspecialchars($vozilo['sasija']); ?>"
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
                                value="<?php echo htmlspecialchars($vozilo['marka']); ?>"
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
                                value="<?php echo htmlspecialchars($vozilo['vlasnik']); ?>"
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
                                value="<?php echo htmlspecialchars($vozilo['kontakt']); ?>"
                        >
                    </div>
                </div>

                <!-- DATUM I VREME -->
                <div class="form-section">
                    <h2>📅 Datum prijema</h2>
                    <div class="info-box">
                        <strong>Datum prijema:</strong> <?php echo formatuj_datum($vozilo['datum_prijema']); ?>
                        <br><small>Datum prijema se ne može menjati</small>
                    </div>
                </div>

                <!-- SLIKA VOZILA -->
                <div class="form-section">
                    <h2>📷 Slika vozila</h2>

                    <?php if ($vozilo['slika_vozila']): ?>
                        <div style="margin-bottom: 15px;">
                            <strong>Trenutna slika:</strong><br>
                            <img src="../../uploads/vozila/<?php echo htmlspecialchars($vozilo['slika_vozila']); ?>"
                                 alt="Trenutna slika"
                                 style="max-width: 300px; max-height: 300px; border-radius: 8px; margin-top: 10px;">
                        </div>
                    <?php endif; ?>

                    <input type="file" id="slika_vozila" name="slika_vozila" accept="image/*" style="display: none;">

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

                    <small>Max 5MB, formati: JPG, PNG, WEBP. Ako ne izaberete novu sliku, stara će ostati.</small>

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
                                <option value="<?php echo $lok; ?>" <?php echo ($vozilo['lokacija'] == $lok) ? 'selected' : ''; ?>>
                                    <?php echo $lok; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- PARKING LOKACIJA -->
                <div class="form-section">
                    <h2>🅿️ Parking lokacija</h2>

                    <div class="form-group">
                        <label for="parking_lokacija">Gde je vozilo parkirano? *</label>
                        <select id="parking_lokacija" name="parking_lokacija" required>
                            <option value="">-- Izaberite --</option>
                            <option value="Silos" <?php echo ($vozilo['parking_lokacija'] == 'Silos') ? 'selected' : ''; ?>>Silos</option>
                            <option value="Balon parking" <?php echo ($vozilo['parking_lokacija'] == 'Balon parking') ? 'selected' : ''; ?>>Balon parking</option>
                            <option value="Veliki parking" <?php echo ($vozilo['parking_lokacija'] == 'Veliki parking') ? 'selected' : ''; ?>>Veliki parking</option>
                        </select>
                    </div>
                </div>

                <!-- POTREBNE USLUGE -->
                <div class="form-section">
                    <h2>🔧 Potrebne usluge</h2>

                    <?php if (empty($usluge_lista)): ?>
                        <div class="alert alert-error">
                            Nema dostupnih usluga. Molimo administratora da doda usluge.
                        </div>
                    <?php else: ?>
                        <div class="checkbox-group">
                            <?php
                            foreach ($usluge_lista as $id_usluge => $usluga):
                                $checked = in_array($id_usluge, $trenutne_usluge) ? 'checked' : '';
                                ?>
                                <label class="checkbox-label">
                                    <input
                                            type="checkbox"
                                            name="usluge[]"
                                            value="<?php echo $id_usluge; ?>"
                                            data-cena="<?php echo $usluga['cena']; ?>"
                                            class="usluga-checkbox"
                                        <?php echo $checked; ?>
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

                <!-- STATUS -->
                <div class="form-section">
                    <h2>📊 Status</h2>

                    <div class="form-group">
                        <label for="status">Status vozila</label>
                        <select id="status" name="status" required>
                            <option value="u_radu" <?php echo ($vozilo['status'] == 'u_radu') ? 'selected' : ''; ?>>🔴 U radu</option>
                            <option value="zavrseno" <?php echo ($vozilo['status'] == 'zavrseno') ? 'selected' : ''; ?>>🟡 Završeno</option>
                            <option value="placeno" <?php echo ($vozilo['status'] == 'placeno') ? 'selected' : ''; ?>>🟢 Plaćeno</option>
                        </select>
                    </div>
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
                                value="<?php echo htmlspecialchars($vozilo['cena']); ?>"
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
                        ><?php echo htmlspecialchars($vozilo['napomena']); ?></textarea>
                    </div>
                </div>

                <!-- DUGMAD -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-lg">
                        ✅ Sačuvaj izmene
                    </button>
                    <a href="detalji.php?id=<?php echo $id; ?>" class="btn btn-secondary btn-lg">
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