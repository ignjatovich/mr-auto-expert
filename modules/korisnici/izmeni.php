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
    $_SESSION['greska'] = 'ID korisnika nije naveden!';
    header('Location: lista.php');
    exit();
}

// Preuzmi korisnika
$stmt = $conn->prepare("SELECT * FROM korisnici WHERE id = ?");
$stmt->execute([$id]);
$korisnik = $stmt->fetch();

if (!$korisnik) {
    $_SESSION['greska'] = 'Korisnik ne postoji!';
    header('Location: lista.php');
    exit();
}

// Provera da li menadžer pokušava da menja administratora ili menadžera
if ($_SESSION['tip_korisnika'] == 'menadzer' && in_array($korisnik['tip_korisnika'], ['administrator', 'menadzer'])) {
    $_SESSION['greska'] = 'Nemate dozvolu da menjate ovog korisnika!';
    header('Location: lista.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ime = trim($_POST['ime'] ?? '');
    $prezime = trim($_POST['prezime'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefon = trim($_POST['telefon'] ?? '');
    $tip_korisnika = $_POST['tip_korisnika'] ?? $korisnik['tip_korisnika'];
    $aktivan = isset($_POST['aktivan']) ? 1 : 0;
    $nova_sifra = $_POST['nova_sifra'] ?? '';
    $potvrdi_sifru = $_POST['potvrdi_sifru'] ?? '';

    if (empty($ime)) {
        $greska = 'Ime je obavezno.';
    } else {
        // Provera da li menadžer pokušava da dodeli tip administratora ili menadžera
        if ($_SESSION['tip_korisnika'] == 'menadzer' && in_array($tip_korisnika, ['administrator', 'menadzer'])) {
            $greska = 'Menadžer ne može dodeliti tip administratora ili menadžera.';
        }

        // Provera nove šifre
        $menja_sifru = false;
        if (!empty($nova_sifra) || !empty($potvrdi_sifru)) {
            $menja_sifru = true;

            if (strlen($nova_sifra) < 6) {
                $greska = 'Nova šifra mora imati najmanje 6 karaktera.';
            } elseif ($nova_sifra !== $potvrdi_sifru) {
                $greska = 'Nove šifre se ne poklapaju.';
            }
        }

        // LOKACIJE - nova logika
        if (empty($greska)) {
            $lokacija = null;
            $lokacije_json = null;
            $sve_lokacije = 0;

            if ($tip_korisnika == 'zaposleni') {
                // Zaposleni - jedna lokacija
                $lokacija = $_POST['lokacija'] ?? '';
                if (empty($lokacija)) {
                    $greska = 'Lokacija je obavezna.';
                }
            } else {
                // Administrator ili menadžer - više lokacija
                $sve_lokacije = isset($_POST['sve_lokacije']) ? 1 : 0;

                if ($sve_lokacije) {
                    // Sve lokacije
                    $lokacija = 'Ostružnica'; // Default prva
                    $lokacije_json = json_encode(['Ostružnica', 'Žarkovo', 'Mirijevo']);
                } else {
                    // Izabrane lokacije
                    $izabrane_lokacije = $_POST['lokacije'] ?? [];

                    if (empty($izabrane_lokacije)) {
                        $greska = 'Morate izabrati bar jednu lokaciju ili označiti "Sve lokacije".';
                    } else {
                        $lokacija = $izabrane_lokacije[0]; // Prva kao default
                        $lokacije_json = json_encode($izabrane_lokacije);
                    }
                }
            }
        }

        if (empty($greska)) {
            if ($menja_sifru) {
                // Ažuriraj podatke i šifru
                $nova_sifra_hash = password_hash($nova_sifra, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("
                    UPDATE korisnici 
                    SET ime = ?, prezime = ?, email = ?, telefon = ?, tip_korisnika = ?, lokacija = ?, lokacije = ?, sve_lokacije = ?, aktivan = ?, sifra = ?
                    WHERE id = ?
                ");
                $stmt->execute([$ime, $prezime, $email, $telefon, $tip_korisnika, $lokacija, $lokacije_json, $sve_lokacije, $aktivan, $nova_sifra_hash, $id]);
                $uspeh = 'Korisnik i šifra uspešno ažurirani!';
            } else {
                // Ažuriraj samo podatke
                $stmt = $conn->prepare("
                    UPDATE korisnici 
                    SET ime = ?, prezime = ?, email = ?, telefon = ?, tip_korisnika = ?, lokacija = ?, lokacije = ?, sve_lokacije = ?, aktivan = ?
                    WHERE id = ?
                ");
                $stmt->execute([$ime, $prezime, $email, $telefon, $tip_korisnika, $lokacija, $lokacije_json, $sve_lokacije, $aktivan, $id]);
                $uspeh = 'Korisnik uspešno ažuriran!';
            }

            // Osvezi podatke
            $stmt = $conn->prepare("SELECT * FROM korisnici WHERE id = ?");
            $stmt->execute([$id]);
            $korisnik = $stmt->fetch();
        }
    }
}

// Pripremi trenutne lokacije za prikaz
$trenutne_lokacije = [];
if ($korisnik['sve_lokacije']) {
    $trenutne_lokacije = ['Ostružnica', 'Žarkovo', 'Mirijevo'];
} elseif (!empty($korisnik['lokacije'])) {
    $trenutne_lokacije = json_decode($korisnik['lokacije'], true);
} else {
    $trenutne_lokacije = [$korisnik['lokacija']];
}

// Postavi promenljive za header
$page_title = 'Izmeni korisnika - ' . SITE_NAME;
$base_url = '../../';

// Include header POSLE svih provera i redirecta
require_once '../../includes/header.php';
?>

    <div class="container">
        <div class="page-header">
            <h1>✏️ Izmeni korisnika: <?php echo e($korisnik['korisnicko_ime']); ?></h1>
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
            <form method="POST" action="" id="forma-korisnik">

                <!-- OSNOVNI PODACI -->
                <div class="form-section">
                    <h2>👤 Lični podaci</h2>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="ime">Ime *</label>
                            <input
                                    type="text"
                                    id="ime"
                                    name="ime"
                                    required
                                    value="<?php echo e($korisnik['ime']); ?>"
                            >
                        </div>

                        <div class="form-group">
                            <label for="prezime">Prezime</label>
                            <input
                                    type="text"
                                    id="prezime"
                                    name="prezime"
                                    value="<?php echo e($korisnik['prezime']); ?>"
                            >
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input
                                    type="email"
                                    id="email"
                                    name="email"
                                    value="<?php echo e($korisnik['email']); ?>"
                            >
                        </div>

                        <div class="form-group">
                            <label for="telefon">Broj telefona</label>
                            <input
                                    type="tel"
                                    id="telefon"
                                    name="telefon"
                                    placeholder="npr. 061 123 4567"
                                    value="<?php echo e($korisnik['telefon']); ?>"
                            >
                        </div>
                    </div>
                </div>

                <!-- TIP KORISNIKA -->
                <div class="form-section">
                    <h2>⚙️ Tip korisnika</h2>

                    <div class="form-group">
                        <label for="tip_korisnika">Tip korisnika *</label>
                        <select id="tip_korisnika" name="tip_korisnika" required onchange="promeniTipKorisnika()">
                            <?php if ($_SESSION['tip_korisnika'] == 'administrator'): ?>
                                <option value="administrator" <?php echo ($korisnik['tip_korisnika'] == 'administrator') ? 'selected' : ''; ?>>Administrator</option>
                                <option value="menadzer" <?php echo ($korisnik['tip_korisnika'] == 'menadzer') ? 'selected' : ''; ?>>Menadžer</option>
                            <?php endif; ?>
                            <option value="zaposleni" <?php echo ($korisnik['tip_korisnika'] == 'zaposleni') ? 'selected' : ''; ?>>Zaposleni</option>
                        </select>
                    </div>
                </div>

                <!-- LOKACIJA - DINAMIČKI -->
                <div class="form-section">
                    <h2>📍 Lokacija</h2>

                    <!-- Za zaposlene - single select -->
                    <div id="zaposleni-lokacija" style="display: <?php echo ($korisnik['tip_korisnika'] == 'zaposleni') ? 'block' : 'none'; ?>;">
                        <div class="form-group">
                            <label for="lokacija">Lokacija *</label>
                            <select id="lokacija" name="lokacija">
                                <option value="">-- Izaberi lokaciju --</option>
                                <option value="Ostružnica" <?php echo ($korisnik['lokacija'] == 'Ostružnica') ? 'selected' : ''; ?>>Ostružnica</option>
                                <option value="Žarkovo" <?php echo ($korisnik['lokacija'] == 'Žarkovo') ? 'selected' : ''; ?>>Žarkovo</option>
                                <option value="Mirijevo" <?php echo ($korisnik['lokacija'] == 'Mirijevo') ? 'selected' : ''; ?>>Mirijevo</option>
                            </select>
                            <small>Radnik može pregledati i dodavati vozila samo za svoju lokaciju</small>
                        </div>
                    </div>

                    <!-- Za administratore i menadžere - checkboxes -->
                    <div id="admin-lokacije" style="display: <?php echo ($korisnik['tip_korisnika'] != 'zaposleni') ? 'block' : 'none'; ?>;">
                        <div class="form-group">
                            <label>Dostupne lokacije *</label>
                            <small style="display: block; margin-bottom: 10px;">Izaberite jednu ili više lokacija</small>

                            <div class="checkbox-group">
                                <label class="checkbox-label">
                                    <input type="checkbox" name="lokacije[]" value="Ostružnica" class="lokacija-checkbox"
                                        <?php echo in_array('Ostružnica', $trenutne_lokacije) ? 'checked' : ''; ?>
                                        <?php echo $korisnik['sve_lokacije'] ? 'disabled' : ''; ?>>
                                    <span>📍 Ostružnica</span>
                                </label>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="lokacije[]" value="Žarkovo" class="lokacija-checkbox"
                                        <?php echo in_array('Žarkovo', $trenutne_lokacije) ? 'checked' : ''; ?>
                                        <?php echo $korisnik['sve_lokacije'] ? 'disabled' : ''; ?>>
                                    <span>📍 Žarkovo</span>
                                </label>
                                <label class="checkbox-label">
                                    <input type="checkbox" name="lokacije[]" value="Mirijevo" class="lokacija-checkbox"
                                        <?php echo in_array('Mirijevo', $trenutne_lokacije) ? 'checked' : ''; ?>
                                        <?php echo $korisnik['sve_lokacije'] ? 'disabled' : ''; ?>>
                                    <span>📍 Mirijevo</span>
                                </label>
                            </div>

                            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #e0e0e0;">
                                <label class="checkbox-label" style="background: #e7f3ff; border-color: #2196f3;">
                                    <input type="checkbox" name="sve_lokacije" id="sve_lokacije" onchange="toggleSveLokacije()"
                                        <?php echo $korisnik['sve_lokacije'] ? 'checked' : ''; ?>>
                                    <span style="font-weight: 600; color: #0066cc;">✓ Pristup svim lokacijama</span>
                                </label>
                                <small style="display: block; margin-top: 5px; color: #666;">Preporučeno za administratore</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STATUS -->
                <div class="form-section">
                    <h2>📊 Status naloga</h2>

                    <div class="form-group">
                        <label class="checkbox-label" style="border: none; padding: 0; background: transparent;">
                            <input
                                    type="checkbox"
                                    name="aktivan"
                                    value="1"
                                <?php echo $korisnik['aktivan'] ? 'checked' : ''; ?>
                            >
                            <span>Nalog je aktivan</span>
                        </label>
                    </div>
                </div>

                <!-- INFORMACIJE -->
                <div class="form-section">
                    <h2>ℹ️ Informacije</h2>
                    <div class="info-box">
                        <strong>Korisničko ime:</strong> <?php echo e($korisnik['korisnicko_ime']); ?><br>
                        <strong>Datum kreiranja:</strong> <?php echo formatuj_datum($korisnik['datum_kreiranja']); ?>
                    </div>
                </div>

                <!-- PROMENA ŠIFRE -->
                <div class="form-section">
                    <h2>🔒 Promena šifre</h2>
                    <p style="color: #666; margin-bottom: 15px;">Popuni samo ako želiš da promeniš šifru korisniku</p>

                    <div class="form-row">
                        <div class="form-group password-group">
                            <label for="nova_sifra">Nova šifra</label>
                            <div class="password-wrapper">
                                <input
                                        type="password"
                                        id="nova_sifra"
                                        name="nova_sifra"
                                        placeholder="Najmanje 6 karaktera"
                                        data-toggle="password"
                                >
                                <span class="toggle-password" onclick="togglePassword(this)">Prikaži šifru</span>
                            </div>
                        </div>

                        <div class="form-group password-group">
                            <label for="potvrdi_sifru">Potvrdi novu šifru</label>
                            <div class="password-wrapper">
                                <input
                                        type="password"
                                        id="potvrdi_sifru"
                                        name="potvrdi_sifru"
                                        placeholder="Ponovi novu šifru"
                                        data-toggle="password"
                                >
                                <span class="toggle-password" onclick="togglePassword(this)">Prikaži šifru</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- DUGMAD -->
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

    <style>
        .checkbox-group {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            padding: 12px 15px;
            background: #f8f9fa;
            border: 2px solid #e1e8ed;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .checkbox-label:hover {
            background: #e9ecef;
            border-color: #FF411C;
        }

        .checkbox-label input[type="checkbox"] {
            margin-right: 10px;
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .checkbox-label input[type="checkbox"]:checked + span {
            font-weight: 600;
            color: #FF411C;
        }

        .checkbox-label input[type="checkbox"]:disabled {
            opacity: 0.5;
        }
    </style>

    <script src="<?php echo $base_url; ?>assets/js/prikaz-sifre.js"></script>

    <script>
        // Prikaži/sakrij polja za lokaciju na osnovu tipa korisnika
        function promeniTipKorisnika() {
            const tipKorisnika = document.getElementById('tip_korisnika').value;
            const zaposleniLokacija = document.getElementById('zaposleni-lokacija');
            const adminLokacije = document.getElementById('admin-lokacije');
            const lokacijaSelect = document.getElementById('lokacija');

            if (tipKorisnika === 'zaposleni') {
                zaposleniLokacija.style.display = 'block';
                adminLokacije.style.display = 'none';
                lokacijaSelect.setAttribute('required', 'required');
            } else {
                zaposleniLokacija.style.display = 'none';
                adminLokacije.style.display = 'block';
                lokacijaSelect.removeAttribute('required');
            }
        }

        // Toggle "Sve lokacije" checkbox
        function toggleSveLokacije() {
            const sveLokacije = document.getElementById('sve_lokacije');
            const checkboxes = document.querySelectorAll('.lokacija-checkbox');

            if (sveLokacije.checked) {
                checkboxes.forEach(cb => {
                    cb.checked = false;
                    cb.disabled = true;
                });
            } else {
                checkboxes.forEach(cb => {
                    cb.disabled = false;
                });
            }
        }
    </script>

<?php require_once '../../includes/footer.php'; ?>