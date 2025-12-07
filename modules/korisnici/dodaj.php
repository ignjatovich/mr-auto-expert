<?php
$base_url = '../../';
require_once '../../config.php';
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../includes/header.php';

// Samo administrator i menadžer mogu da dodaju korisnike
proveri_tip(['administrator', 'menadzer']);

$greska = '';
$uspeh = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $korisnicko_ime = trim($_POST['korisnicko_ime'] ?? '');
    $sifra = $_POST['sifra'] ?? '';
    $potvrdi_sifru = $_POST['potvrdi_sifru'] ?? '';
    $ime = trim($_POST['ime'] ?? '');
    $prezime = trim($_POST['prezime'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefon = trim($_POST['telefon'] ?? '');
    $tip_korisnika = $_POST['tip_korisnika'] ?? 'zaposleni';
    $lokacija = $_POST['lokacija'] ?? '';
    $aktivan = isset($_POST['aktivan']) ? 1 : 0;

    // Validacija
    if (empty($korisnicko_ime)) {
        $greska = 'Korisničko ime je obavezno.';
    } elseif (empty($ime)) {
        $greska = 'Ime je obavezno.';
    } elseif (empty($lokacija)) {
        $greska = 'Lokacija je obavezna.';
    } elseif (empty($sifra)) {
        $greska = 'Šifra je obavezna.';
    } elseif (strlen($sifra) < 6) {
        $greska = 'Šifra mora imati najmanje 6 karaktera.';
    } elseif ($sifra !== $potvrdi_sifru) {
        $greska = 'Šifre se ne poklapaju.';
    } else {
        // Proveri da li korisničko ime već postoji
        $stmt = $conn->prepare("SELECT id FROM korisnici WHERE korisnicko_ime = ?");
        $stmt->execute([$korisnicko_ime]);

        if ($stmt->fetch()) {
            $greska = 'Korisničko ime već postoji!';
        } else {
            // Provera da li menadžer pokušava da kreira administratora ili menadžera
            if ($_SESSION['tip_korisnika'] == 'menadzer' && in_array($tip_korisnika, ['administrator', 'menadzer'])) {
                $greska = 'Menadžer može kreirati samo zaposlene.';
            } else {
                // Hash šifre
                $sifra_hash = password_hash($sifra, PASSWORD_DEFAULT);

                // Kreiraj korisnika
                // Priprema lokacija
                $sve_lokacije = 0;
                $lokacije_json = null;
                $prva_lokacija = null;

                if (isset($_POST['sve_lokacije']) && $_POST['sve_lokacije'] == 1) {
                    // Sve lokacije
                    $sve_lokacije = 1;
                    $prva_lokacija = 'Ostružnica'; // Default
                } elseif (isset($_POST['lokacije']) && is_array($_POST['lokacije']) && count($_POST['lokacije']) > 0) {
                    // Više lokacija
                    $lokacije_json = json_encode($_POST['lokacije']);
                    $prva_lokacija = $_POST['lokacije'][0];
                } else {
                    // Jedna lokacija (zaposleni)
                    $prva_lokacija = $_POST['lokacija'] ?? '';
                }

                $stmt = $conn->prepare("

    
    INSERT INTO korisnici (korisnicko_ime, sifra, ime, prezime, email, telefon, tip_korisnika, lokacija, lokacije, sve_lokacije, aktivan)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

                $stmt->execute([
                    $korisnicko_ime,
                    $sifra_hash,
                    $ime,
                    $prezime,
                    $email,
                    $telefon,
                    $tip_korisnika,
                    $prva_lokacija,
                    $lokacije_json,
                    $sve_lokacije,
                    $aktivan
                ]);

                if (in_array($tip_korisnika, ['administrator', 'menadzer'])) {
                    if (!isset($_POST['sve_lokacije']) && empty($_POST['lokacije'])) {
                        $greska = 'Morate izabrati bar jednu lokaciju.';
                    }
                } else {
                    if (empty($lokacija)) {
                        $greska = 'Lokacija je obavezna.';
                    }
                }

                $uspeh = "Korisnik '$korisnicko_ime' je uspešno kreiran!";

                // Resetuj formu
                $_POST = [];
            }
        }
    }
}
?>

    <div class="container">
        <div class="page-header">
            <h1>➕ Dodaj novog korisnika</h1>
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
                <a href="lista.php">Vidi sve korisnike</a>
            </div>
        <?php endif; ?>

        <div class="form-card">
            <form method="POST" action="">

                <!-- PRISTUPNI PODACI -->
                <div class="form-section">
                    <h2>🔐 Pristupni podaci</h2>

                    <div class="form-group">
                        <label for="korisnicko_ime">Korisničko ime *</label>
                        <input
                                type="text"
                                id="korisnicko_ime"
                                name="korisnicko_ime"
                                required
                                placeholder="npr. marko.markovic"
                                value="<?php echo e($_POST['korisnicko_ime'] ?? ''); ?>"
                                oninput="validateUsername(this)"
                        >
                        <small>Mora biti jedinstveno, samo mala slova, dozvoljeno: . i _</small>
                    </div>

                    <div class="form-row">
                        <div class="form-group password-group">
                            <label for="sifra">Šifra *</label>
                            <div class="password-wrapper">
                                <input
                                        type="password"
                                        id="sifra"
                                        name="sifra"
                                        required
                                        placeholder="Najmanje 6 karaktera"
                                        data-toggle="password"
                                >
                                <span class="toggle-password" onclick="togglePassword(this)">Prikaži šifru</span>
                            </div>
                        </div>

                        <div class="form-group password-group">
                            <label for="potvrdi_sifru">Potvrdi šifru *</label>
                            <div class="password-wrapper">
                                <input
                                        type="password"
                                        id="potvrdi_sifru"
                                        name="potvrdi_sifru"
                                        required
                                        placeholder="Ponovi šifru"
                                        data-toggle="password"
                                >
                                <span class="toggle-password" onclick="togglePassword(this)">Prikaži šifru</span>
                            </div>
                        </div>
                    </div>
                </div>


                <!-- LIČNI PODACI -->
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
                                value="<?php echo e($_POST['ime'] ?? ''); ?>"
                            >
                        </div>

                        <div class="form-group">
                            <label for="prezime">Prezime</label>
                            <input
                                type="text"
                                id="prezime"
                                name="prezime"
                                value="<?php echo e($_POST['prezime'] ?? ''); ?>"
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
                                placeholder="npr. marko@example.com"
                                value="<?php echo e($_POST['email'] ?? ''); ?>"
                            >
                        </div>

                        <div class="form-group">
                            <label for="telefon">Broj telefona</label>
                            <input
                                type="tel"
                                id="telefon"
                                name="telefon"
                                placeholder="npr. 061 123 4567"
                                value="<?php echo e($_POST['telefon'] ?? ''); ?>"
                            >
                        </div>
                    </div>
                </div>

                <!-- TIP KORISNIKA I LOKACIJA -->
                <div class="form-section">
                    <h2>⚙️ Podešavanja naloga</h2>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="tip_korisnika">Tip korisnika *</label>
                            <select id="tip_korisnika" name="tip_korisnika" required>
                                <?php if ($_SESSION['tip_korisnika'] == 'administrator'): ?>
                                    <option value="administrator" <?php echo (($_POST['tip_korisnika'] ?? '') == 'administrator') ? 'selected' : ''; ?>>Administrator</option>
                                    <option value="menadzer" <?php echo (($_POST['tip_korisnika'] ?? '') == 'menadzer') ? 'selected' : ''; ?>>Menadžer</option>
                                <?php endif; ?>
                                <option value="zaposleni" <?php echo (($_POST['tip_korisnika'] ?? 'zaposleni') == 'zaposleni') ? 'selected' : ''; ?>>Zaposleni</option>
                            </select>
                            <small>
                                <?php if ($_SESSION['tip_korisnika'] == 'menadzer'): ?>
                                    Menadžer može kreirati samo zaposlene
                                <?php endif; ?>
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="lokacija">Lokacije *</label>

                            <?php if ($_SESSION['tip_korisnika'] == 'administrator' && in_array($tip_korisnika ?? 'zaposleni', ['administrator', 'menadzer'])): ?>
                                <!-- Checkbox grupa za administratore i menadžere -->
                                <div class="checkbox-group">
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="lokacije[]" value="Ostružnica"
                                            <?php echo (in_array('Ostružnica', $_POST['lokacije'] ?? [])) ? 'checked' : ''; ?>>
                                        <span>📍 Ostružnica</span>
                                    </label>
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="lokacije[]" value="Žarkovo"
                                            <?php echo (in_array('Žarkovo', $_POST['lokacije'] ?? [])) ? 'checked' : ''; ?>>
                                        <span>📍 Žarkovo</span>
                                    </label>
                                    <label class="checkbox-label">
                                        <input type="checkbox" name="lokacije[]" value="Mirijevo"
                                            <?php echo (in_array('Mirijevo', $_POST['lokacije'] ?? [])) ? 'checked' : ''; ?>>
                                        <span>📍 Mirijevo</span>
                                    </label>
                                </div>
                                <small>Izaberite jednu ili više lokacija</small>

                                <div class="form-group" style="margin-top: 15px;">
                                    <label class="checkbox-label" style="border: none; padding: 0; background: transparent;">
                                        <input type="checkbox" name="sve_lokacije" value="1"
                                            <?php echo (isset($_POST['sve_lokacije'])) ? 'checked' : ''; ?>>
                                        <span>Pristup svim lokacijama (preporučeno za administratore)</span>
                                    </label>
                                </div>
                            <?php else: ?>
                                <!-- Običan select za zaposlene -->
                                <select id="lokacija" name="lokacija" required>
                                    <option value="">-- Izaberi lokaciju --</option>
                                    <option value="Ostružnica" <?php echo (($_POST['lokacija'] ?? '') == 'Ostružnica') ? 'selected' : ''; ?>>Ostružnica</option>
                                    <option value="Žarkovo" <?php echo (($_POST['lokacija'] ?? '') == 'Žarkovo') ? 'selected' : ''; ?>>Žarkovo</option>
                                    <option value="Mirijevo" <?php echo (($_POST['lokacija'] ?? '') == 'Mirijevo') ? 'selected' : ''; ?>>Mirijevo</option>
                                </select>
                                <small>Zaposleni može pristupiti samo jednoj lokaciji</small>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="checkbox-label" style="border: none; padding: 0; background: transparent;">
                            <input
                                type="checkbox"
                                name="aktivan"
                                value="1"
                                <?php echo (isset($_POST['aktivan']) || !isset($_POST['korisnicko_ime'])) ? 'checked' : ''; ?>
                            >
                            <span>Nalog je aktivan</span>
                        </label>
                        <small>Samo aktivni korisnici mogu da se prijave</small>
                    </div>
                </div>

                <!-- DUGMAD -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-lg">
                        ✅ Kreiraj korisnika
                    </button>
                    <a href="lista.php" class="btn btn-secondary btn-lg">
                        ❌ Otkaži
                    </a>
                </div>

            </form>
        </div>
    </div>
    <script src="<?php echo $base_url; ?>assets/js/prikaz-sifre.js"></script>

<?php require_once '../../includes/footer.php'; ?>