<?php
$include_camera_js = true;
$base_url = '../../';
require_once '../../config.php';
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../includes/header.php';
?>
    <link rel="stylesheet" href="<?php echo $base_url; ?>assets/css/pravna_lica_styles.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>assets/css/camera.css">
<?php

// SVI korisnici mogu da izmene vozila, ali zaposleni samo svoja
proveri_login();

$id = $_GET['id'] ?? 0;
$greska = '';
$uspeh = '';

if (empty($id)) {
    header('Location: ../../lista_vozila.php');
    exit();
}

// Preuzmi vozilo sa informacijama o korisniku koji je menjao
$stmt = $conn->prepare("
    SELECT v.*, 
           k.ime as kreirao_ime, k.prezime as kreirao_prezime, k.korisnicko_ime as kreirao_username,
           km.ime as izmenjeno_ime, km.prezime as izmenjeno_prezime, km.korisnicko_ime as izmenjeno_username
    FROM vozila v
    LEFT JOIN korisnici k ON v.kreirao_korisnik_id = k.id
    LEFT JOIN korisnici km ON v.izmenjeno_korisnik_id = km.id
    WHERE v.id = ?
");
$stmt->execute([$id]);
$vozilo = $stmt->fetch();

if (!$vozilo) {
    $_SESSION['greska'] = 'Vozilo ne postoji!';
    header('Location: ../../lista_vozila.php');
    exit();
}

// Provera pristupa - zaposleni mogu menjati SAMO SVOJA vozila
//if ($_SESSION['tip_korisnika'] == 'zaposleni' && $vozilo['kreirao_korisnik_id'] != $_SESSION['korisnik_id']) {
//    $_SESSION['greska'] = 'Nemate pristup ovom vozilu! Možete menjati samo vozila koja ste Vi dodali.';
//    header('Location: ../../lista_vozila.php');
//    exit();
//}
//header('Location: ../../lista_vozila.php');
//exit();
//}

// Provera pristupa - zaposleni mogu samo svoja vozila
//if ($_SESSION['tip_korisnika'] == 'zaposleni' && $vozilo['kreirao_korisnik_id'] != $_SESSION['korisnik_id']) {
//    $_SESSION['greska'] = 'Nemate pravo da izmenite ovo vozilo!';
//    header('Location: ../../lista_vozila.php');
//    exit();
//}

// Dekoduj usluge - pazi na stare i nove formate
$trenutne_usluge = json_decode($vozilo['usluge'], true);

// Ako su usluge u starom formatu, pretvori u prazan niz
if (!empty($trenutne_usluge) && !isset($trenutne_usluge[0])) {
    $trenutne_usluge = [];
}

// Dekoduj custom usluge ako postoje
$custom_usluge = [];
if (!empty($vozilo['custom_usluge'])) {
    $decoded = json_decode($vozilo['custom_usluge'], true);
    // Stari format: jedan objekat sa 'naziv' i 'cena'
    // Novi format: array objekata
    if (isset($decoded['naziv'])) {
        // Stari format - pretvori u array
        $custom_usluge = [$decoded];
    } else {
        // Novi format - već je array
        $custom_usluge = $decoded;
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validacija
    $registracija = trim($_POST['registracija'] ?? '');
    $sasija = trim($_POST['sasija'] ?? '');
    $marka = trim($_POST['marka'] ?? '');
    $tip_klijenta = $_POST['tip_klijenta'] ?? 'fizicko';
    $vlasnik = trim($_POST['vlasnik'] ?? '');
    $pravno_lice_id = $_POST['pravno_lice_id'] ?? null;
    $kontakt_osoba = trim($_POST['kontakt_osoba'] ?? '');
    $kontakt = trim($_POST['kontakt'] ?? '');
    $parking_lokacija = $_POST['parking_lokacija'] ?? '';
    $usluge = $_POST['usluge'] ?? [];
    $cena = floatval($_POST['cena'] ?? 0);
    $napomena = trim($_POST['napomena'] ?? '');
    $status = $_POST['status'] ?? 'u_radu';

    // Custom usluge - više custom usluga
    $custom_usluge = [];
    if (isset($_POST['custom_naziv']) && is_array($_POST['custom_naziv'])) {
        foreach ($_POST['custom_naziv'] as $index => $naziv) {
            $naziv = trim($naziv);
            $custom_cena = floatval($_POST['custom_cena'][$index] ?? 0);

            if (!empty($naziv) && $custom_cena > 0) {
                $custom_usluge[] = [
                    'naziv' => $naziv,
                    'cena' => $custom_cena
                ];
                $cena += $custom_cena;
            }
        }
    }

    if (empty($registracija) || empty($marka) || empty($parking_lokacija)) {
        $greska = 'Molimo popunite sva obavezna polja.';
    } elseif ($tip_klijenta == 'fizicko' && empty($vlasnik)) {
        $greska = 'Molimo unesite ime vlasnika.';
    } elseif ($tip_klijenta == 'pravno' && empty($pravno_lice_id)) {
        $greska = 'Molimo izaberite pravno lice.';
    } elseif (empty($usluge) && empty($custom_usluge)) {
        $greska = 'Molimo izaberite bar jednu uslugu ili unesite custom uslugu.';
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

            // Custom usluge u JSON
            $custom_usluge_json = !empty($custom_usluge) ? json_encode($custom_usluge) : null;

            // Postavi vlasnika u zavisnosti od tipa klijenta
            if ($tip_klijenta == 'pravno') {
                $stmt_pl = $conn->prepare("SELECT naziv FROM pravna_lica WHERE id = ?");
                $stmt_pl->execute([$pravno_lice_id]);
                $pl = $stmt_pl->fetch();
                $vlasnik = $pl['naziv'];
            } else {
                $pravno_lice_id = null;
            }

            // Update u bazi
            $stmt = $conn->prepare("
                UPDATE vozila SET
                    registracija = ?,
                    sasija = ?,
                    marka = ?,
                    tip_klijenta = ?,
                    vlasnik = ?,
                    pravno_lice_id = ?,
                    kontakt_osoba = ?,
                    kontakt = ?,
                    slika_vozila = ?,
                    parking_lokacija = ?,
                    usluge = ?,
                    custom_usluge = ?,
                    cena = ?,
                    napomena = ?,
                    status = ?,
                    izmenjeno_korisnik_id = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $registracija,
                $sasija,
                $marka,
                $tip_klijenta,
                $vlasnik,
                $pravno_lice_id,
                $kontakt_osoba,
                $kontakt,
                $slika_vozila,
                $parking_lokacija,
                $usluge_json,
                $custom_usluge_json,
                $cena,
                $napomena,
                $status,
                $_SESSION['korisnik_id'],
                $id
            ]);

            $uspeh = 'Vozilo uspešno izmenjeno!';

            // Osvezi podatke
            $stmt = $conn->prepare("
                SELECT v.*, 
                       k.ime as kreirao_ime, k.prezime as kreirao_prezime, k.korisnicko_ime as kreirao_username,
                       km.ime as izmenjeno_ime, km.prezime as izmenjeno_prezime, km.korisnicko_ime as izmenjeno_username
                FROM vozila v
                LEFT JOIN korisnici k ON v.kreirao_korisnik_id = k.id
                LEFT JOIN korisnici km ON v.izmenjeno_korisnik_id = km.id
                WHERE v.id = ?
            ");
            $stmt->execute([$id]);
            $vozilo = $stmt->fetch();
            $trenutne_usluge = json_decode($vozilo['usluge'], true);

            // Dekoduj custom usluge
            if (!empty($vozilo['custom_usluge'])) {
                $custom_usluge = json_decode($vozilo['custom_usluge'], true);
            }
        }
    }
}

// Preuzmi dostupne usluge
$usluge_lista = get_usluge();
?>

    <div class="container">
        <div class="page-header">
            <h1>✏️ Izmeni vozilo: <?php echo e($vozilo['registracija']); ?></h1>
            <div>
                <a href="detalji.php?id=<?php echo $id; ?>" class="btn btn-secondary">← Nazad</a>
                <a href="../../lista_vozila.php" class="btn btn-secondary">📋 Lista vozila</a>
            </div>
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
                                    value="<?php echo e($vozilo['registracija']); ?>"
                            >
                        </div>

                        <div class="form-group">
                            <label for="sasija">Broj šasije (VIN)</label>
                            <input
                                    type="text"
                                    id="sasija"
                                    name="sasija"
                                    placeholder="npr. WBA12345678901234"
                                    value="<?php echo e($vozilo['sasija']); ?>"
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
                                value="<?php echo e($vozilo['marka']); ?>"
                        >
                    </div>
                </div>

                <!-- TIP KLIJENTA -->
                <div class="form-section">
                    <h2>👤 Klijent je:</h2>

                    <div class="radio-group">
                        <label class="radio-label">
                            <input type="radio" name="tip_klijenta" value="fizicko"
                                <?php echo ($vozilo['tip_klijenta'] == 'fizicko') ? 'checked' : ''; ?>
                                   onchange="toggleKlijentType()">
                            <span>👤 Fizičko lice</span>
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="tip_klijenta" value="pravno"
                                <?php echo ($vozilo['tip_klijenta'] == 'pravno') ? 'checked' : ''; ?>
                                   onchange="toggleKlijentType()">
                            <span>🏢 Pravno lice</span>
                        </label>
                    </div>
                </div>

                <!-- FIZIČKO LICE -->
                <div class="form-section" id="fizicko-section" style="display: <?php echo ($vozilo['tip_klijenta'] == 'fizicko') ? 'block' : 'none'; ?>;">
                    <h2>👤 Podaci o vlasniku (fizičko lice)</h2>

                    <div class="form-group">
                        <label for="vlasnik">Ime i prezime vlasnika *</label>
                        <input
                                type="text"
                                id="vlasnik"
                                name="vlasnik"
                                placeholder="npr. Marko Marković"
                                value="<?php echo e($vozilo['tip_klijenta'] == 'fizicko' ? $vozilo['vlasnik'] : ''); ?>"
                        >
                    </div>
                </div>

                <!-- PRAVNO LICE -->
                <div class="form-section" id="pravno-section" style="display: <?php echo ($vozilo['tip_klijenta'] == 'pravno') ? 'block' : 'none'; ?>;">
                    <h2>🏢 Podaci o firmi (pravno lice)</h2>

                    <div class="form-group">
                        <label for="pravno-search">Pretraži pravno lice</label>
                        <input
                                type="text"
                                id="pravno-search"
                                placeholder="Kucaj naziv, PIB ili telefon..."
                                autocomplete="off"
                        >
                        <div id="pravno-results" class="search-results"></div>
                    </div>

                    <input type="hidden" id="pravno_lice_id" name="pravno_lice_id" value="<?php echo e($vozilo['pravno_lice_id']); ?>">

                    <div id="pravno-selected" class="selected-pravno" style="display: <?php echo !empty($vozilo['pravno_lice_id']) ? 'block' : 'none'; ?>;">
                        <div class="selected-content">
                            <strong id="selected-naziv"><?php echo e($vozilo['tip_klijenta'] == 'pravno' ? $vozilo['vlasnik'] : ''); ?></strong>
                            <button type="button" onclick="removePravnoLice()" class="btn-remove">✕</button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="kontakt_osoba">Kontakt osoba iz firme (opciono)</label>
                        <input
                                type="text"
                                id="kontakt_osoba"
                                name="kontakt_osoba"
                                placeholder="Ime osobe koja predaje vozilo"
                                value="<?php echo e($vozilo['kontakt_osoba']); ?>"
                        >
                    </div>
                </div>

                <!-- KONTAKT -->
                <div class="form-section">
                    <h2>📞 Kontakt</h2>

                    <div class="form-group">
                        <label for="kontakt">Kontakt telefon</label>
                        <input
                                type="tel"
                                id="kontakt"
                                name="kontakt"
                                placeholder="npr. 061 123 4567"
                                value="<?php echo e($vozilo['kontakt']); ?>"
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
                            <img src="../../uploads/vozila/<?php echo e($vozilo['slika_vozila']); ?>"
                                 alt="Trenutna slika"
                                 style="max-width: 300px; max-height: 300px; border-radius: 8px; margin-top: 10px;">
                        </div>
                    <?php endif; ?>

                    <div class="camera-options">
                        <button type="button" onclick="openCamera()" class="btn-camera">
                            📷 Uslikaj kamerom
                        </button>
                        <span class="camera-or">ili</span>
                        <label for="slika_vozila" class="btn-upload">
                            📁 Upload sa uređaja
                        </label>
                        <input
                                type="file"
                                id="slika_vozila"
                                name="slika_vozila"
                                accept="image/*"
                                style="display: none;"
                        >
                    </div>
                    <small>Max 5MB, formati: JPG, PNG, WEBP</small>

                    <div id="slika-preview"></div>
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
                                    <?php echo e($usluga['naziv']); ?>
                                    <strong style="color: #667eea;">(<?php echo number_format($usluga['cena'], 2, ',', '.'); ?> RSD)</strong>
                                </span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- CUSTOM USLUGE -->
                <div class="form-section">
                    <h2>➕ Dodatne usluge (custom)</h2>
                    <p style="color: #666; margin-bottom: 15px;">Dodaj specifične usluge za ovo vozilo</p>

                    <div id="custom-usluge-container" class="custom-usluge-container">
                        <?php if (!empty($custom_usluge)): ?>
                            <?php foreach ($custom_usluge as $index => $cu): ?>
                                <div class="custom-usluga-item">
                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label>Naziv usluge</label>
                                        <input
                                                type="text"
                                                name="custom_naziv[]"
                                                placeholder="npr. Popravka haube"
                                                class="custom-usluga-input"
                                                value="<?php echo e($cu['naziv']); ?>"
                                        >
                                    </div>

                                    <div class="form-group" style="margin-bottom: 0;">
                                        <label>Cena (RSD)</label>
                                        <input
                                                type="number"
                                                name="custom_cena[]"
                                                step="0.01"
                                                min="0"
                                                placeholder="0.00"
                                                class="custom-cena-input"
                                                value="<?php echo e($cu['cena']); ?>"
                                        >
                                    </div>

                                    <button type="button" onclick="removeCustomUsluga(this)" class="btn-remove-usluga">✕</button>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <!-- Prva custom usluga (uvek prikazana ako nema postojećih) -->
                            <div class="custom-usluga-item">
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label>Naziv usluge</label>
                                    <input
                                            type="text"
                                            name="custom_naziv[]"
                                            placeholder="npr. Popravka haube"
                                            class="custom-usluga-input"
                                    >
                                </div>

                                <div class="form-group" style="margin-bottom: 0;">
                                    <label>Cena (RSD)</label>
                                    <input
                                            type="number"
                                            name="custom_cena[]"
                                            step="0.01"
                                            min="0"
                                            placeholder="0.00"
                                            class="custom-cena-input"
                                    >
                                </div>

                                <button type="button" onclick="removeCustomUsluga(this)" class="btn-remove-usluga" style="visibility: hidden;">✕</button>
                            </div>
                        <?php endif; ?>
                    </div>

                    <button type="button" onclick="addCustomUsluga()" class="btn-add-custom">
                        ➕ Dodaj još jednu custom uslugu
                    </button>
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
                                value="<?php echo e($vozilo['cena']); ?>"
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
                        ><?php echo e($vozilo['napomena']); ?></textarea>
                    </div>
                </div>

                <!-- INFORMACIJE -->
                <div class="form-section">
                    <h2>ℹ️ Informacije o kreaciji i izmenama</h2>
                    <div class="info-box">
                        <strong>Kreirao:</strong> <?php echo e($vozilo['kreirao_ime'] . ' ' . $vozilo['kreirao_prezime']); ?> (<?php echo e($vozilo['kreirao_username']); ?>)<br>
                        <strong>Datum kreiranja:</strong> <?php echo formatuj_datum($vozilo['datum_kreiranja']); ?><br>
                        <strong>Poslednja izmena:</strong> <?php echo formatuj_datum($vozilo['datum_izmene']); ?><br>
                        <?php if ($vozilo['izmenjeno_korisnik_id']): ?>
                            <strong>Izmenio:</strong> <?php echo e($vozilo['izmenjeno_ime'] . ' ' . $vozilo['izmenjeno_prezime']); ?> (<?php echo e($vozilo['izmenjeno_username']); ?>)
                        <?php endif; ?>
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

    <!-- Camera Modal -->
    <div id="camera-modal" class="camera-modal">
        <div class="camera-container">
            <div class="camera-header">
                <h3>📷 Uslikaj vozilo</h3>
                <button type="button" onclick="closeCamera()" class="camera-close">✕</button>
            </div>
            <video id="camera-video" autoplay playsinline></video>
            <canvas id="camera-canvas" style="display: none;"></canvas>
            <div class="camera-controls">
                <button type="button" onclick="capturePhoto()" class="btn-capture">
                    <span class="capture-icon"></span>
                    Uslikaj
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

                // Standardne usluge
                checkboxes.forEach(cb => {
                    if (cb.checked) {
                        ukupno += parseFloat(cb.dataset.cena);
                    }
                });

                // Custom usluge
                const customCenaInputs = document.querySelectorAll('.custom-cena-input');
                customCenaInputs.forEach(input => {
                    if (input.value) {
                        ukupno += parseFloat(input.value) || 0;
                    }
                });

                cenaInput.value = ukupno.toFixed(2);
            }

            checkboxes.forEach(cb => {
                cb.addEventListener('change', updateCena);
            });

            // Event delegation za custom cene
            document.addEventListener('input', function(e) {
                if (e.target.classList.contains('custom-cena-input')) {
                    updateCena();
                }
            });

            // Inicijalno izračunaj cenu
            updateCena();
        });

        // Dodaj novu custom uslugu
        function addCustomUsluga() {
            const container = document.getElementById('custom-usluge-container');

            const newItem = document.createElement('div');
            newItem.className = 'custom-usluga-item';
            newItem.innerHTML = `
        <div class="form-group" style="margin-bottom: 0;">
            <label>Naziv usluge</label>
            <input
                type="text"
                name="custom_naziv[]"
                placeholder="npr. Popravka haube"
                class="custom-usluga-input"
            >
        </div>

        <div class="form-group" style="margin-bottom: 0;">
            <label>Cena (RSD)</label>
            <input
                type="number"
                name="custom_cena[]"
                step="0.01"
                min="0"
                placeholder="0.00"
                class="custom-cena-input"
            >
        </div>

        <button type="button" onclick="removeCustomUsluga(this)" class="btn-remove-usluga">✕</button>
    `;

            container.appendChild(newItem);
        }

        // Ukloni custom uslugu
        function removeCustomUsluga(btn) {
            const item = btn.closest('.custom-usluga-item');
            item.remove();

            // Ažuriraj cenu
            const event = new Event('input', { bubbles: true });
            document.dispatchEvent(event);
        }

        // Toggle klijent type
        function toggleKlijentType() {
            const tipKlijenta = document.querySelector('input[name="tip_klijenta"]:checked').value;
            const fizickoSection = document.getElementById('fizicko-section');
            const pravnoSection = document.getElementById('pravno-section');

            if (tipKlijenta === 'fizicko') {
                fizickoSection.style.display = 'block';
                pravnoSection.style.display = 'none';
            } else {
                fizickoSection.style.display = 'none';
                pravnoSection.style.display = 'block';
            }
        }

        // Search pravnih lica
        let searchTimeout;
        const searchInput = document.getElementById('pravno-search');
        const resultsDiv = document.getElementById('pravno-results');

        if (searchInput) {
            searchInput.addEventListener('input', function(e) {
                const query = e.target.value.trim();

                clearTimeout(searchTimeout);

                if (query.length < 2) {
                    resultsDiv.innerHTML = '';
                    resultsDiv.style.display = 'none';
                    return;
                }

                searchTimeout = setTimeout(() => {
                    fetch(`../../modules/pravna_lica/search_api.php?q=${encodeURIComponent(query)}`)
                        .then(response => response.json())
                        .then(data => {
                            displayResults(data);
                        })
                        .catch(error => {
                            console.error('Greška pri pretrazi:', error);
                        });
                }, 300);
            });
        }

        function displayResults(data) {
            if (data.length === 0) {
                resultsDiv.innerHTML = '<div class="search-no-results">Nema rezultata</div>';
                resultsDiv.style.display = 'block';
                return;
            }

            let html = '';
            data.forEach(item => {
                html += `
            <div class="search-result-item" onclick="selectPravnoLice(${item.id}, '${item.naziv.replace(/'/g, "\\'")}', '${item.pib}', '${item.kontakt_telefon}')">
                <strong>${item.naziv}</strong><br>
                <small>PIB: ${item.pib} | Tel: ${item.kontakt_telefon}</small>
            </div>
        `;
            });

            resultsDiv.innerHTML = html;
            resultsDiv.style.display = 'block';
        }

        function selectPravnoLice(id, naziv, pib, telefon) {
            document.getElementById('pravno_lice_id').value = id;
            document.getElementById('selected-naziv').textContent = naziv;
            document.getElementById('pravno-selected').style.display = 'block';
            document.getElementById('pravno-search').value = '';
            resultsDiv.innerHTML = '';
            resultsDiv.style.display = 'none';
        }

        function removePravnoLice() {
            document.getElementById('pravno_lice_id').value = '';
            document.getElementById('pravno-selected').style.display = 'none';
        }

        // Close search results when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('#pravno-search') && !e.target.closest('#pravno-results')) {
                resultsDiv.style.display = 'none';
            }
        });
    </script>

<?php require_once '../../includes/footer.php'; ?>