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

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validacija
    $registracija = trim($_POST['registracija'] ?? '');
    $sasija = trim($_POST['sasija'] ?? '');
    $marka = trim($_POST['marka'] ?? '');

    // TIP KLIJENTA - NOVO
    $tip_klijenta = $_POST['tip_klijenta'] ?? 'fizicko';
    $pravno_lice_id = null;
    $vlasnik = '';
    $kontakt_osoba = ''; // Inicijalizacija

    if ($tip_klijenta == 'pravno') {
        $pravno_lice_id = intval($_POST['pravno_lice_id'] ?? 0);
        $kontakt_osoba = trim($_POST['kontakt_osoba'] ?? '');

        if (empty($pravno_lice_id)) {
            $greska = 'Molimo izaberite pravno lice.';
        } else {
            // Proveri da li pravno lice postoji
            $stmt = $conn->prepare("SELECT naziv FROM pravna_lica WHERE id = ?");
            $stmt->execute([$pravno_lice_id]);
            $firma = $stmt->fetch();

            if (!$firma) {
                $greska = 'Izabrano pravno lice ne postoji!';
            } else {
                // Vlasnik je naziv firme, kontakt osoba ide u napomenu
                $vlasnik = $firma['naziv'];
            }
        }
    } else {
        // Fizičko lice
        $vlasnik = trim($_POST['vlasnik'] ?? '');
        if (empty($vlasnik)) {
            $greska = 'Molimo unesite ime i prezime vlasnika.';
        }
    }

    $kontakt = trim($_POST['kontakt'] ?? '');
    $parking_lokacija = $_POST['parking_lokacija'] ?? '';
    $usluge = $_POST['usluge'] ?? [];
    $cena = floatval($_POST['cena'] ?? 0);
    $napomena = trim($_POST['napomena'] ?? '');

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

    // Datum prijema - automatski trenutno vreme
    $datum_prijema = date('Y-m-d H:i:s');

    if (empty($greska)) {
        if (empty($registracija) || empty($marka) || empty($parking_lokacija)) {
            $greska = 'Molimo popunite sva obavezna polja.';
        } elseif (empty($usluge) && empty($custom_usluga_naziv)) {
            $greska = 'Molimo izaberite bar jednu uslugu ili unesite custom uslugu.';
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

                // Custom usluge u JSON
                $custom_usluge_json = !empty($custom_usluge) ? json_encode($custom_usluge) : null;

                // Insert u bazu - AŽURIRANO SA NOVIM POLJIMA
                $stmt = $conn->prepare("
                    INSERT INTO vozila (
                        registracija, sasija, marka, vlasnik, tip_klijenta, pravno_lice_id, kontakt_osoba, kontakt, 
                        datum_prijema, slika_vozila, parking_lokacija, 
                        usluge, custom_usluge, cena, napomena, 
                        kreirao_korisnik_id, lokacija, status
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'u_radu')
                ");

                $stmt->execute([
                    $registracija,
                    $sasija,
                    $marka,
                    $vlasnik,
                    $tip_klijenta,
                    $pravno_lice_id,
                    $kontakt_osoba,
                    $kontakt,
                    $datum_prijema,
                    $slika_vozila,
                    $parking_lokacija,
                    $usluge_json,
                    $custom_usluge_json,
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
}

// Preuzmi usluge za prikaz
$usluge_lista = get_usluge();

// Include header
include '../../includes/header.php';
?>
    <link rel="stylesheet" href="<?php echo $base_url; ?>assets/css/pravna_lica_styles.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>assets/css/camera.css">

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

                <!-- TIP KLIJENTA - NOVO -->
                <div class="form-section">
                    <h2>👤 Tip klijenta</h2>

                    <div class="form-group">
                        <label>Klijent je:</label>
                        <div class="radio-group">
                            <label class="radio-label">
                                <input
                                        type="radio"
                                        name="tip_klijenta"
                                        value="fizicko"
                                        checked
                                        onchange="toggleKlijentType()"
                                >
                                <span>👤 Fizičko lice</span>
                            </label>
                            <label class="radio-label">
                                <input
                                        type="radio"
                                        name="tip_klijenta"
                                        value="pravno"
                                        onchange="toggleKlijentType()"
                                >
                                <span>🏢 Pravno lice</span>
                            </label>
                        </div>
                    </div>

                    <!-- FIZIČKO LICE -->
                    <div id="fizicko-lice-section">
                        <div class="form-group">
                            <label for="vlasnik">Ime i prezime vlasnika *</label>
                            <input
                                    type="text"
                                    id="vlasnik"
                                    name="vlasnik"
                                    placeholder="npr. Marko Marković"
                                    value="<?php echo htmlspecialchars($_POST['vlasnik'] ?? ''); ?>"
                            >
                        </div>
                    </div>

                    <!-- PRAVNO LICE -->
                    <div id="pravno-lice-section" style="display: none;">
                        <div class="form-group">
                            <label for="pravno-lice-search">Pretraži pravno lice *</label>
                            <div class="autocomplete-wrapper">
                                <input
                                        type="text"
                                        id="pravno-lice-search"
                                        placeholder="Počnite kucati naziv firme..."
                                        autocomplete="off"
                                >
                                <div id="pravno-lice-results" class="autocomplete-results"></div>
                            </div>
                            <input type="hidden" id="pravno_lice_id" name="pravno_lice_id">
                            <div id="selected-pravno-lice" class="selected-item"></div>
                        </div>

                        <div class="form-group">
                            <label for="kontakt_osoba">Kontakt osoba (opciono)</label>
                            <input
                                    type="text"
                                    id="kontakt_osoba"
                                    name="kontakt_osoba"
                                    placeholder="npr. Petar Petrović"
                                    value="<?php echo htmlspecialchars($_POST['kontakt_osoba'] ?? ''); ?>"
                            >
                            <small>Osoba iz firme koja je dovela vozilo</small>
                        </div>
                    </div>
                </div>

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

                <!-- SLIKA VOZILA -->
                <div class="form-section">
                    <h2>📷 Slika vozila</h2>

                    <div class="camera-options">
                        <button type="button" class="btn btn-camera" onclick="openCamera()">
                            📷 Uslikaj kamerom
                        </button>
                        <span class="camera-or">ili</span>
                        <label for="slika_vozila" class="btn btn-upload">
                            📁 Upload sa uređaja
                        </label>
                    </div>

                    <input
                            type="file"
                            id="slika_vozila"
                            name="slika_vozila"
                            accept="image/*"
                            class="file-input"
                            style="display: none;"
                    >

                    <div id="slika-preview"></div>

                    <!-- Camera Modal -->
                    <div id="camera-modal" class="camera-modal">
                        <div class="camera-container">
                            <div class="camera-header">
                                <h3>📷 Fotografiši vozilo</h3>
                                <button type="button" class="camera-close" onclick="closeCamera()">✕</button>
                            </div>
                            <video id="camera-video" autoplay playsinline></video>
                            <canvas id="camera-canvas" style="display: none;"></canvas>
                            <div class="camera-controls">
                                <button type="button" class="btn-capture" onclick="capturePhoto()">
                                    <span class="capture-icon"></span>
                                    Uslikaj
                                </button>
                            </div>
                        </div>
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
                                    <strong style="color: #FF411C;">(<?php echo number_format($usluga['cena'], 2, ',', '.'); ?> RSD)</strong>
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
                        <!-- Prva custom usluga (uvek prikazana) -->
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
                    </div>

                    <button type="button" onclick="addCustomUsluga()" class="btn-add-custom">
                        ➕ Dodaj još jednu specifičnu uslugu
                    </button>
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

    <style>
        /* Radio buttons */
        .radio-group {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }

        .radio-label {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            background: #f8f9fa;
            border: 2px solid #e1e8ed;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            flex: 1;
            min-width: 200px;
        }

        .radio-label:hover {
            background: #e9ecef;
            border-color: #FF411C;
        }

        .radio-label input[type="radio"] {
            margin-right: 10px;
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .radio-label input[type="radio"]:checked + span {
            font-weight: 600;
            color: #FF411C;
        }

        /* Autocomplete */
        .autocomplete-wrapper {
            position: relative;
        }

        .autocomplete-results {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 2px solid #e1e8ed;
            border-top: none;
            border-radius: 0 0 8px 8px;
            max-height: 300px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .autocomplete-results.active {
            display: block;
        }

        .autocomplete-item {
            padding: 12px 15px;
            cursor: pointer;
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.2s;
        }

        .autocomplete-item:hover {
            background: #f8f9fa;
        }

        .autocomplete-item:last-child {
            border-bottom: none;
        }

        .autocomplete-item-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 4px;
        }

        .autocomplete-item-details {
            font-size: 13px;
            color: #666;
        }

        .selected-item {
            margin-top: 15px;
            padding: 15px;
            background: #e7f3ff;
            border-left: 4px solid #FF411C;
            border-radius: 8px;
            display: none;
        }

        .selected-item.active {
            display: block;
        }

        .selected-item-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 10px;
        }

        .selected-item-name {
            font-weight: 600;
            color: #FF411C;
            font-size: 16px;
        }

        .selected-item-remove {
            background: #dc3545;
            color: white;
            border: none;
            padding: 4px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 12px;
        }

        .selected-item-remove:hover {
            background: #c82333;
        }

        .selected-item-details {
            font-size: 14px;
            color: #666;
        }

        @media (max-width: 768px) {
            .radio-group {
                flex-direction: column;
            }

            .radio-label {
                min-width: 100%;
            }
        }
    </style>

    <script>
        // Toggle između fizičkog i pravnog lica
        function toggleKlijentType() {
            const tipKlijenta = document.querySelector('input[name="tip_klijenta"]:checked').value;
            const fizickoSection = document.getElementById('fizicko-lice-section');
            const pravnoSection = document.getElementById('pravno-lice-section');
            const vlasnikInput = document.getElementById('vlasnik');
            const pravnoLiceInput = document.getElementById('pravno_lice_id');

            if (tipKlijenta === 'pravno') {
                fizickoSection.style.display = 'none';
                pravnoSection.style.display = 'block';
                vlasnikInput.removeAttribute('required');
                vlasnikInput.value = '';
            } else {
                fizickoSection.style.display = 'block';
                pravnoSection.style.display = 'none';
                vlasnikInput.setAttribute('required', 'required');
                pravnoLiceInput.value = '';
                document.getElementById('selected-pravno-lice').classList.remove('active');
            }
        }

        // Autocomplete za pravna lica
        let searchTimeout;
        const searchInput = document.getElementById('pravno-lice-search');
        const resultsDiv = document.getElementById('pravno-lice-results');
        const selectedDiv = document.getElementById('selected-pravno-lice');
        const hiddenInput = document.getElementById('pravno_lice_id');

        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();

            if (query.length < 2) {
                resultsDiv.classList.remove('active');
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

        function displayResults(results) {
            if (results.length === 0) {
                resultsDiv.innerHTML = '<div class="autocomplete-item">Nema rezultata</div>';
                resultsDiv.classList.add('active');
                return;
            }

            let html = '';
            results.forEach(item => {
                html += `
            <div class="autocomplete-item" onclick="selectPravnoLice(${item.id}, '${escapeHtml(item.naziv)}', '${escapeHtml(item.pib || '')}', '${escapeHtml(item.kontakt_telefon || '')}')">
                <div class="autocomplete-item-name">${escapeHtml(item.naziv)}</div>
                <div class="autocomplete-item-details">
                    ${item.pib ? 'PIB: ' + escapeHtml(item.pib) : ''}
                    ${item.kontakt_telefon ? '• Tel: ' + escapeHtml(item.kontakt_telefon) : ''}
                </div>
            </div>
        `;
            });

            resultsDiv.innerHTML = html;
            resultsDiv.classList.add('active');
        }

        function selectPravnoLice(id, naziv, pib, telefon) {
            hiddenInput.value = id;
            searchInput.value = '';
            resultsDiv.classList.remove('active');

            let details = [];
            if (pib) details.push('PIB: ' + pib);
            if (telefon) details.push('Tel: ' + telefon);

            selectedDiv.innerHTML = `
        <div class="selected-item-header">
            <div class="selected-item-name">🏢 ${escapeHtml(naziv)}</div>
            <button type="button" class="selected-item-remove" onclick="removePravnoLice()">✕ Ukloni</button>
        </div>
        <div class="selected-item-details">${details.join(' • ')}</div>
    `;
            selectedDiv.classList.add('active');
        }

        function removePravnoLice() {
            hiddenInput.value = '';
            selectedDiv.classList.remove('active');
            selectedDiv.innerHTML = '';
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Zatvori rezultate kad se klikne van
        document.addEventListener('click', function(e) {
            if (!searchInput.contains(e.target) && !resultsDiv.contains(e.target)) {
                resultsDiv.classList.remove('active');
            }
        });

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
    </script>

<?php include '../../includes/footer.php'; ?>