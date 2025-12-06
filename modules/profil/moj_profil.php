<?php
$base_url = '../';
require_once '../config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

proveri_login();

$korisnik_id = $_SESSION['korisnik_id'];
$greska = '';
$uspeh = '';

// Preuzmi trenutne podatke korisnika
$stmt = $conn->prepare("SELECT * FROM korisnici WHERE id = ?");
$stmt->execute([$korisnik_id]);
$korisnik = $stmt->fetch();

if (!$korisnik) {
    $_SESSION['greska'] = 'Korisnik ne postoji!';
    header('Location: ../dashboard.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $ime = trim($_POST['ime'] ?? '');
    $prezime = trim($_POST['prezime'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefon = trim($_POST['telefon'] ?? '');
    $stara_sifra = $_POST['stara_sifra'] ?? '';
    $nova_sifra = $_POST['nova_sifra'] ?? '';
    $potvrdi_sifru = $_POST['potvrdi_sifru'] ?? '';

    if (empty($ime)) {
        $greska = 'Ime je obavezno polje.';
    } else {
        // Proveri da li menja šifru
        $menja_sifru = false;
        if (!empty($stara_sifra) || !empty($nova_sifra) || !empty($potvrdi_sifru)) {
            $menja_sifru = true;

            if (empty($stara_sifra)) {
                $greska = 'Unesite trenutnu šifru.';
            } elseif (!password_verify($stara_sifra, $korisnik['sifra'])) {
                $greska = 'Trenutna šifra nije tačna.';
            } elseif (empty($nova_sifra)) {
                $greska = 'Unesite novu šifru.';
            } elseif (strlen($nova_sifra) < 6) {
                $greska = 'Nova šifra mora imati najmanje 6 karaktera.';
            } elseif ($nova_sifra !== $potvrdi_sifru) {
                $greska = 'Nove šifre se ne poklapaju.';
            }
        }

        if (empty($greska)) {
            if ($menja_sifru) {
                // Ažuriraj podatke i šifru
                $nova_sifra_hash = password_hash($nova_sifra, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("
                    UPDATE korisnici 
                    SET ime = ?, prezime = ?, email = ?, telefon = ?, sifra = ?
                    WHERE id = ?
                ");
                $stmt->execute([$ime, $prezime, $email, $telefon, $nova_sifra_hash, $korisnik_id]);
                $uspeh = 'Profil i šifra uspešno ažurirani!';
            } else {
                // Ažuriraj samo podatke
                $stmt = $conn->prepare("
                    UPDATE korisnici 
                    SET ime = ?, prezime = ?, email = ?, telefon = ?
                    WHERE id = ?
                ");
                $stmt->execute([$ime, $prezime, $email, $telefon, $korisnik_id]);
                $uspeh = 'Profil uspešno ažuriran!';
            }

            // Ažuriraj session podatke
            $_SESSION['ime'] = $ime;
            $_SESSION['prezime'] = $prezime;

            // Osvezi podatke
            $stmt = $conn->prepare("SELECT * FROM korisnici WHERE id = ?");
            $stmt->execute([$korisnik_id]);
            $korisnik = $stmt->fetch();
        }
    }
}
?>

    <div class="container">
        <div class="page-header">
            <h1>👤 Moj profil</h1>
            <a href="../dashboard.php" class="btn btn-secondary">← Nazad</a>
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

                <!-- OSNOVNI PODACI -->
                <div class="form-section">
                    <h2>📝 Osnovni podaci</h2>

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

                <!-- INFORMACIJE O NALOGU -->
                <div class="form-section">
                    <h2>ℹ️ Informacije o nalogu</h2>
                    <div class="info-box">
                        <strong>Korisničko ime:</strong> <?php echo e($korisnik['korisnicko_ime']); ?><br>
                        <strong>Tip korisnika:</strong>
                        <span class="badge badge-<?php echo $korisnik['tip_korisnika']; ?>">
                        <?php echo ucfirst($korisnik['tip_korisnika']); ?>
                    </span><br>
                        <strong>Lokacija:</strong> <?php echo e($korisnik['lokacija']); ?><br>
                        <strong>Datum kreiranja:</strong> <?php echo formatuj_datum($korisnik['datum_kreiranja']); ?>
                    </div>
                </div>

                <!-- PROMENA ŠIFRE -->
                <div class="form-section">
                    <h2>🔒 Promena šifre</h2>
                    <p style="color: #666; margin-bottom: 15px;">Popuni samo ako želiš da promeniš šifru</p>

                    <div class="form-group">
                        <label for="stara_sifra">Trenutna šifra</label>
                        <input
                            type="password"
                            id="stara_sifra"
                            name="stara_sifra"
                            placeholder="Unesite trenutnu šifru"
                        >
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="nova_sifra">Nova šifra</label>
                            <input
                                type="password"
                                id="nova_sifra"
                                name="nova_sifra"
                                placeholder="Najmanje 6 karaktera"
                            >
                        </div>

                        <div class="form-group">
                            <label for="potvrdi_sifru">Potvrdi novu šifru</label>
                            <input
                                type="password"
                                id="potvrdi_sifru"
                                name="potvrdi_sifru"
                                placeholder="Ponovi novu šifru"
                            >
                        </div>
                    </div>
                </div>

                <!-- DUGMAD -->
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary btn-lg">
                        ✅ Sačuvaj izmene
                    </button>
                    <a href="../dashboard.php" class="btn btn-secondary btn-lg">
                        ❌ Otkaži
                    </a>
                </div>

            </form>
        </div>
    </div>

<?php require_once '../includes/footer.php'; ?>