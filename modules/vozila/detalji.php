<?php
require_once '../../config.php';
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

proveri_login();

$id = $_GET['id'] ?? 0;
$greska = '';
$uspeh = '';

if (empty($id)) {
    header('Location: ../../lista_vozila.php');
    exit();
}

// Preuzmi vozilo
$stmt = $conn->prepare("
    SELECT v.*, k.ime, k.prezime, k.korisnicko_ime
    FROM vozila v
    LEFT JOIN korisnici k ON v.kreirao_korisnik_id = k.id
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

// Promena statusa - ISPRAVLJENO
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['novi_status'])) {
    $novi_status = $_POST['novi_status'] ?? '';

    if (in_array($novi_status, ['u_radu', 'zavrseno', 'placeno'])) {
        $stmt = $conn->prepare("UPDATE vozila SET status = ? WHERE id = ?");
        $stmt->execute([$novi_status, $id]);

        $uspeh = 'Status uspešno promenjen!';

        // Osvezi podatke
        $stmt = $conn->prepare("
            SELECT v.*, k.ime, k.prezime, k.korisnicko_ime
            FROM vozila v
            LEFT JOIN korisnici k ON v.kreirao_korisnik_id = k.id
            WHERE v.id = ?
        ");
        $stmt->execute([$id]);
        $vozilo = $stmt->fetch();
    }
}

// Dekoduj usluge
$usluge = json_decode($vozilo['usluge'], true);
$usluge_lista = get_usluge();
?>
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalji vozila - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
    <style>
        .detail-section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .detail-section h2 {
            color: #667eea;
            font-size: 20px;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e1e8ed;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .detail-item {
            margin-bottom: 15px;
        }

        .detail-label {
            font-weight: 600;
            color: #666;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .detail-value {
            font-size: 16px;
            color: #333;
        }

        .vehicle-image-full {
            max-width: 100%;
            height: auto;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }

        .status-section {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 12px;
            border: 2px solid #e1e8ed;
        }

        .status-current {
            display: inline-block;
            padding: 12px 24px;
            border-radius: 8px;
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .status-current.u_radu {
            background: #dc3545;
            color: white;
        }

        .status-current.zavrseno {
            background: #ffc107;
            color: #333;
        }

        .status-current.placeno {
            background: #28a745;
            color: white;
        }

        .status-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }

        .btn-status {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-status-u-radu {
            background: #dc3545;
            color: white;
        }

        .btn-status-u-radu:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        .btn-status-zavrseno {
            background: #ffc107;
            color: #333;
        }

        .btn-status-zavrseno:hover {
            background: #e0a800;
            transform: translateY(-2px);
        }

        .btn-status-placeno {
            background: #28a745;
            color: white;
        }

        .btn-status-placeno:hover {
            background: #218838;
            transform: translateY(-2px);
        }

        .usluge-lista {
            list-style: none;
            padding: 0;
        }

        .usluge-lista li {
            padding: 10px 15px;
            background: #f8f9fa;
            margin-bottom: 8px;
            border-radius: 6px;
            border-left: 3px solid #667eea;
        }

        .usluge-lista li:before {
            content: "✓ ";
            color: #28a745;
            font-weight: bold;
            margin-right: 8px;
        }
    </style>
</head>
<body>
<nav class="navbar">
    <div class="nav-container">
        <div class="nav-brand">
            <a href="../../dashboard.php" style="color: inherit; text-decoration: none;">
                🚗 Mr Auto Expert DOO
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

    <!-- Podaci o vlasniku -->
    <div class="detail-section">
        <h2>👤 Podaci o vlasniku</h2>
        <div class="detail-grid">
            <div class="detail-item">
                <div class="detail-label">Ime i prezime vlasnika</div>
                <div class="detail-value"><?php echo e($vozilo['vlasnik']); ?></div>
            </div>
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
        <ul class="usluge-lista">
            <?php foreach ($usluge as $usluga_key): ?>
                <li><?php echo e($usluge_lista[$usluga_key] ?? $usluga_key); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>

    <!-- Cena -->
    <div class="detail-section">
        <h2>💰 Finansije</h2>
        <div class="detail-item">
            <div class="detail-label">Cena</div>
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

    <!-- Informacije o kreaciji -->
    <div class="detail-section">
        <h2>ℹ️ Informacije o kreaciji</h2>
        <div class="detail-grid">
            <div class="detail-item">
                <div class="detail-label">Kreirao korisnik</div>
                <div class="detail-value"><?php echo e($vozilo['ime'] . ' ' . $vozilo['prezime']); ?> (<?php echo e($vozilo['korisnicko_ime']); ?>)</div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Datum kreiranja</div>
                <div class="detail-value"><?php echo formatuj_datum($vozilo['datum_kreiranja']); ?></div>
            </div>
            <div class="detail-item">
                <div class="detail-label">Poslednja izmena</div>
                <div class="detail-value"><?php echo formatuj_datum($vozilo['datum_izmene']); ?></div>
            </div>
        </div>
    </div>

</div>
</body>
</html>