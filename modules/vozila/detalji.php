<?php
$base_url = '../../';
require_once '../../config.php';
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../includes/header.php';

proveri_login();

$id = $_GET['id'] ?? 0;
$greska = '';
$uspeh = '';

if (empty($id)) {
    header('Location: ../../lista_vozila.php');
    exit();
}

// Preuzmi vozilo sa informacijama o korisniku koji je kreirao i menjao
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

// Provera pristupa - zaposleni mogu videti samo vozila sa svoje lokacije
if ($_SESSION['tip_korisnika'] == 'zaposleni' && $vozilo['lokacija'] != $_SESSION['lokacija']) {
    $_SESSION['greska'] = 'Nemate pristup ovom vozilu!';
    header('Location: ../../lista_vozila.php');
    exit();
}

// Promena statusa
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['novi_status'])) {
    $novi_status = $_POST['novi_status'] ?? '';

    if (in_array($novi_status, ['u_radu', 'zavrseno', 'placeno'])) {
        $stmt = $conn->prepare("UPDATE vozila SET status = ?, izmenjeno_korisnik_id = ? WHERE id = ?");
        $stmt->execute([$novi_status, $_SESSION['korisnik_id'], $id]);

        $uspeh = 'Status uspešno promenjen!';

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
    }
}

// Dekoduj usluge i preuzmi njihove nazive i cene
$usluge_ids = json_decode($vozilo['usluge'], true);
$usluge_detalji = [];

if (!empty($usluge_ids) && is_array($usluge_ids)) {
    $placeholders = str_repeat('?,', count($usluge_ids) - 1) . '?';
    $stmt = $conn->prepare("SELECT id, naziv, cena FROM usluge WHERE id IN ($placeholders)");
    $stmt->execute($usluge_ids);
    $usluge_detalji = $stmt->fetchAll();
}

// Dekoduj custom usluge
$custom_usluge = null;
if (!empty($vozilo['custom_usluge'])) {
    $custom_usluge = json_decode($vozilo['custom_usluge'], true);
}
?>
    <link rel="stylesheet" href="<?php echo $base_url; ?>assets/css/detalji.css">

    <div class="container">
        <div class="page-header">
            <h1>🚗 Detalji vozila: <?php echo e($vozilo['registracija']); ?></h1>
            <div>
                <a href="../../lista_vozila.php" class="btn btn-secondary">← Nazad na listu</a>
                <?php if ($_SESSION['tip_korisnika'] != 'zaposleni'): ?>
                    <a href="izmeni.php?id=<?php echo $id; ?>" class="btn btn-primary">✏️ Izmeni</a>
                <?php endif; ?>
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

        <!-- Status -->
        <div class="detail-section">
            <h2>📊 Status vozila</h2>
            <div class="status-section">
                <div>
                    <strong>Trenutni status:</strong><br>
                    <span class="status-current <?php echo $vozilo['status']; ?>">
                    <?php echo get_status_text($vozilo['status']); ?>
                </span>
                </div>

                <form method="POST" action="" style="margin-top: 20px;">
                    <p style="margin-bottom: 15px;"><strong>Promeni status:</strong></p>
                    <div class="status-buttons">
                        <button type="submit" name="novi_status" value="u_radu" class="btn-status btn-status-u-radu">
                            🔴 U radu
                        </button>
                        <button type="submit" name="novi_status" value="zavrseno" class="btn-status btn-status-zavrseno">
                            🟡 Završeno
                        </button>
                        <button type="submit" name="novi_status" value="placeno" class="btn-status btn-status-placeno">
                            🟢 Plaćeno
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Identifikacija vozila -->
        <div class="detail-section">
            <h2>🚗 Identifikacija vozila</h2>
            <div class="detail-grid">
                <div class="detail-item">
                    <div class="detail-label">Registarska oznaka</div>
                    <div class="detail-value"><strong><?php echo e($vozilo['registracija']); ?></strong></div>
                </div>
                <?php if ($vozilo['sasija']): ?>
                    <div class="detail-item">
                        <div class="detail-label">Broj šasije (VIN)</div>
                        <div class="detail-value"><?php echo e($vozilo['sasija']); ?></div>
                    </div>
                <?php endif; ?>
                <div class="detail-item">
                    <div class="detail-label">Marka vozila</div>
                    <div class="detail-value"><?php echo e($vozilo['marka']); ?></div>
                </div>
            </div>
        </div>

        <!-- Slika vozila -->
        <?php if ($vozilo['slika_vozila']): ?>
            <div class="detail-section">
                <h2>📷 Slika vozila</h2>
                <img src="../../uploads/vozila/<?php echo e($vozilo['slika_vozila']); ?>"
                     alt="Vozilo"
                     class="vehicle-image-full">
            </div>
        <?php endif; ?>

        <!-- Podaci o vlasniku/firmi -->
        <div class="detail-section">
            <h2><?php echo ($vozilo['tip_klijenta'] == 'pravno') ? '🏢 Podaci o firmi' : '👤 Podaci o vlasniku'; ?></h2>
            <div class="detail-grid">
                <div class="detail-item">
                    <div class="detail-label"><?php echo ($vozilo['tip_klijenta'] == 'pravno') ? 'Naziv firme' : 'Ime i prezime vlasnika'; ?></div>
                    <div class="detail-value"><?php echo e($vozilo['vlasnik']); ?></div>
                </div>
                <?php if ($vozilo['tip_klijenta'] == 'pravno' && $vozilo['kontakt_osoba']): ?>
                    <div class="detail-item">
                        <div class="detail-label">Kontakt osoba iz firme</div>
                        <div class="detail-value"><?php echo e($vozilo['kontakt_osoba']); ?></div>
                    </div>
                <?php endif; ?>
                <div class="detail-item">
                    <div class="detail-label">Kontakt telefon</div>
                    <div class="detail-value"><a href="tel:<?php echo e($vozilo['kontakt']); ?>"><?php echo e($vozilo['kontakt']); ?></a></div>
                </div>
            </div>
        </div>

        <!-- Datum i lokacija -->
        <div class="detail-section">
            <h2>📅 Datum i lokacija</h2>
            <div class="detail-grid">
                <div class="detail-item">
                    <div class="detail-label">Datum prijema</div>
                    <div class="detail-value"><?php echo formatuj_datum($vozilo['datum_prijema']); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Parking lokacija</div>
                    <div class="detail-value"><?php echo e($vozilo['parking_lokacija']); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Lokacija servisa</div>
                    <div class="detail-value"><?php echo e($vozilo['lokacija']); ?></div>
                </div>
            </div>
        </div>

        <!-- Usluge -->
        <div class="detail-section">
            <h2>🔧 Potrebne usluge</h2>
            <?php if (empty($usluge_detalji) && !$custom_usluge): ?>
                <p style="color: #999;">Nema izabranih usluga.</p>
            <?php else: ?>
                <ul class="usluge-lista">
                    <?php foreach ($usluge_detalji as $usluga): ?>
                        <li>
                            <span class="usluga-naziv"><?php echo e($usluga['naziv']); ?></span>
                            <span class="usluga-cena"><?php echo number_format($usluga['cena'], 2, ',', '.'); ?> RSD</span>
                        </li>
                    <?php endforeach; ?>

                    <?php if ($custom_usluge): ?>
                        <li style="border-left-color: #FF411C;">
                        <span class="usluga-naziv">
                            <?php echo e($custom_usluge['naziv']); ?>
                            <span style="font-size: 11px; background: #FF411C; color: white; padding: 2px 6px; border-radius: 4px; margin-left: 8px;">CUSTOM</span>
                        </span>
                            <span class="usluga-cena"><?php echo number_format($custom_usluge['cena'], 2, ',', '.'); ?> RSD</span>
                        </li>
                    <?php endif; ?>
                </ul>
            <?php endif; ?>
        </div>

        <!-- Cena -->
        <div class="detail-section">
            <h2>💰 Finansije</h2>
            <div class="detail-item">
                <div class="detail-label">Ukupna cena</div>
                <div class="detail-value" style="font-size: 24px; color: #28a745; font-weight: bold;">
                    <?php echo number_format($vozilo['cena'], 2, ',', '.'); ?> RSD
                </div>
            </div>
        </div>

        <!-- Napomena -->
        <?php if ($vozilo['napomena']): ?>
            <div class="detail-section">
                <h2>📝 Napomena</h2>
                <div class="detail-value">
                    <?php echo nl2br(e($vozilo['napomena'])); ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Informacije o kreaciji i izmenama -->
        <div class="detail-section">
            <h2>ℹ️ Informacije o kreaciji i izmenama</h2>
            <div class="detail-grid">
                <div class="detail-item">
                    <div class="detail-label">Kreirao korisnik</div>
                    <div class="detail-value"><?php echo e($vozilo['kreirao_ime'] . ' ' . $vozilo['kreirao_prezime']); ?> (<?php echo e($vozilo['kreirao_username']); ?>)</div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Datum kreiranja</div>
                    <div class="detail-value"><?php echo formatuj_datum($vozilo['datum_kreiranja']); ?></div>
                </div>
                <div class="detail-item">
                    <div class="detail-label">Poslednja izmena</div>
                    <div class="detail-value"><?php echo formatuj_datum($vozilo['datum_izmene']); ?></div>
                </div>
                <?php if ($vozilo['izmenjeno_korisnik_id']): ?>
                    <div class="detail-item">
                        <div class="detail-label">Izmenio korisnik</div>
                        <div class="detail-value" style="color: #FF411C; font-weight: 600;">
                            <?php echo e($vozilo['izmenjeno_ime'] . ' ' . $vozilo['izmenjeno_prezime']); ?> (<?php echo e($vozilo['izmenjeno_username']); ?>)
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>

<?php require_once '../../includes/footer.php'; ?>