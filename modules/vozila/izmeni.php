<?php
require_once '../../config.php';
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Samo administrator i menadžer mogu da izmene vozila
proveri_tip(['administrator', 'menadzer']);

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

// Dekoduj usluge
$trenutne_usluge = json_decode($vozilo['usluge'], true);

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
    $status = $_POST['status'] ?? 'u_radu';

    if (empty($registracija) || empty($marka) || empty($vlasnik) || empty($kontakt) || empty($parking_lokacija)) {
        $greska = 'Molimo popunite sva obavezna polja.';
    } elseif (empty($usluge)) {
        $greska = 'Molimo izaberite bar jednu uslugu.';
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
?>
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Izmeni vozilo - <?php echo SITE_NAME; ?></title>
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
                        value="<?php echo e($vozilo['vlasnik']); ?>"
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

                <div class="form-group">
                    <label for="slika_vozila">Promeni sliku (opciono)</label>
                    <input
                        type="file"
                        id="slika_vozila"
                        name="slika_vozila"
                        accept="image/*"
                        class="file-input"
                    >
                    <small>Max 5MB, formati: JPG, PNG, WEBP. Ako ne izaberete novu sliku, stara će ostati.</small>
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
                        <option value="Silos" <?php echo ($vozilo['parking_lokacija'] == 'Silos') ? 'selected' : ''; ?>>Silos</option>
                        <option value="Balon parking" <?php echo ($vozilo['parking_lokacija'] == 'Balon parking') ? 'selected' : ''; ?>>Balon parking</option>
                        <option value="Veliki parking" <?php echo ($vozilo['parking_lokacija'] == 'Veliki parking') ? 'selected' : ''; ?>>Veliki parking</option>
                    </select>
                </div>
            </div>

            <!-- POTREBNE USLUGE -->
            <div class="form-section">
                <h2>🔧 Potrebne usluge</h2>

                <div class="checkbox-group">
                    <?php
                    $usluge_lista = get_usluge();
                    foreach ($usluge_lista as $key => $naziv):
                        ?>
                        <label class="checkbox-label">
                            <input
                                type="checkbox"
                                name="usluge[]"
                                value="<?php echo $key; ?>"
                                <?php echo in_array($key, $trenutne_usluge) ? 'checked' : ''; ?>
                            >
                            <span><?php echo e($naziv); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
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
                    <label for="cena">Cena (RSD)</label>
                    <input
                        type="number"
                        id="cena"
                        name="cena"
                        step="0.01"
                        min="0"
                        placeholder="0.00"
                        value="<?php echo e($vozilo['cena']); ?>"
                    >
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

<script src="../../assets/js/main.js"></script>
</body>
</html>