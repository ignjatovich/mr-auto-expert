<?php
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

proveri_login();

$ime = $_SESSION['ime'];
$prezime = $_SESSION['prezime'];
$tip = $_SESSION['tip_korisnika'];
$lokacija_korisnika = $_SESSION['lokacija'];

// Određivanje koje lokacije korisnik može da vidi
$dostupne_lokacije = [];
if ($tip == 'administrator' || $tip == 'menadzer') {
    // Admin i menadžer mogu videti sve lokacije
    $dostupne_lokacije = ['Ostružnica', 'Žarkovo', 'Mirijevo'];
} else {
    // Zaposleni vidi samo svoju lokaciju
    $dostupne_lokacije = [$lokacija_korisnika];
}

// Filtri
$filter_lokacija = $_GET['lokacija'] ?? '';
$filter_status = $_GET['status'] ?? '';
$filter_pretraga = $_GET['pretraga'] ?? '';

// Provera da li korisnik ima pristup izabranoj lokaciji
if (!empty($filter_lokacija) && !in_array($filter_lokacija, $dostupne_lokacije)) {
    $filter_lokacija = '';
}

// Ako zaposleni, automatski postavi njegov filter
if ($tip == 'zaposleni' && empty($filter_lokacija)) {
    $filter_lokacija = $lokacija_korisnika;
}

// Query za preuzimanje vozila
$sql = "SELECT v.*, k.ime, k.prezime 
        FROM vozila v 
        LEFT JOIN korisnici k ON v.kreirao_korisnik_id = k.id 
        WHERE 1=1";

$params = [];

// Filter po lokaciji
if (!empty($filter_lokacija)) {
    $sql .= " AND v.lokacija = ?";
    $params[] = $filter_lokacija;
} else {
    // Ako nije izabrana lokacija, pokaži samo dostupne lokacije
    $placeholders = str_repeat('?,', count($dostupne_lokacije) - 1) . '?';
    $sql .= " AND v.lokacija IN ($placeholders)";
    $params = array_merge($params, $dostupne_lokacije);
}

// Filter po statusu
if (!empty($filter_status)) {
    $sql .= " AND v.status = ?";
    $params[] = $filter_status;
}

// Pretraga
if (!empty($filter_pretraga)) {
    $sql .= " AND (v.registracija LIKE ? OR v.marka LIKE ? OR v.vlasnik LIKE ? OR v.kontakt LIKE ?)";
    $search = "%$filter_pretraga%";
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
}

$sql .= " ORDER BY v.datum_prijema DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$vozila = $stmt->fetchAll();

// Statistika
$stats = [
    'u_radu' => 0,
    'zavrseno' => 0,
    'placeno' => 0,
    'ukupno' => count($vozila)
];

foreach ($vozila as $vozilo) {
    $stats[$vozilo['status']]++;
}
?>
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista vozila - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .filter-section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 25px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .stat-card h4 {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }

        .stat-card .number {
            font-size: 32px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .stat-card.u-radu .number { color: #dc3545; }
        .stat-card.zavrseno .number { color: #ffc107; }
        .stat-card.placeno .number { color: #28a745; }
        .stat-card.ukupno .number { color: #667eea; }

        .table-container {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead {
            background: #f8f9fa;
        }

        th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #e1e8ed;
        }

        td {
            padding: 15px;
            border-bottom: 1px solid #f1f3f5;
        }

        tbody tr:hover {
            background: #f8f9fa;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .status-u-radu {
            background: #dc3545;
            color: white;
        }

        .status-zavrseno {
            background: #ffc107;
            color: #333;
        }

        .status-placeno {
            background: #28a745;
            color: white;
        }

        .action-buttons {
            display: flex;
            gap: 8px;
        }

        .btn-action {
            padding: 6px 12px;
            font-size: 13px;
            text-decoration: none;
            border-radius: 6px;
            transition: all 0.3s;
        }

        .btn-view {
            background: #667eea;
            color: white;
        }

        .btn-view:hover {
            background: #5568d3;
        }

        .btn-delete {
            background: #dc3545;
            color: white;
            border: none;
            cursor: pointer;
        }

        .btn-delete:hover {
            background: #c82333;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }

        .empty-state h3 {
            font-size: 24px;
            margin-bottom: 10px;
        }

        .vehicle-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }

        .no-image {
            width: 60px;
            height: 60px;
            background: #e9ecef;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
    </style>
</head>
<body>
<nav class="navbar">
    <div class="nav-container">
        <div class="nav-brand">
            <a href="dashboard.php" style="color: inherit; text-decoration: none;">
                🚗 Mr Auto Expert DOO
            </a>
        </div>
        <div class="nav-menu">
            <span class="nav-user">
                <?php echo e($ime . ' ' . $prezime); ?>
                <span class="badge badge-<?php echo $tip; ?>">
                    <?php echo ucfirst($tip); ?>
                </span>
            </span>
            <a href="logout.php" class="btn btn-secondary btn-sm">Odjavi se</a>
        </div>
    </div>
</nav>

<div class="container">
    <div class="page-header">
        <h1>📋 Lista vozila</h1>
        <a href="modules/vozila/dodaj.php" class="btn btn-primary">➕ Dodaj vozilo</a>
    </div>

    <!-- Statistika -->
    <div class="stats-grid">
        <div class="stat-card u-radu">
            <h4>U radu</h4>
            <div class="number"><?php echo $stats['u_radu']; ?></div>
        </div>
        <div class="stat-card zavrseno">
            <h4>Završeno</h4>
            <div class="number"><?php echo $stats['zavrseno']; ?></div>
        </div>
        <div class="stat-card placeno">
            <h4>Plaćeno</h4>
            <div class="number"><?php echo $stats['placeno']; ?></div>
        </div>
        <div class="stat-card ukupno">
            <h4>Ukupno</h4>
            <div class="number"><?php echo $stats['ukupno']; ?></div>
        </div>
    </div>

    <!-- Filteri -->
    <div class="filter-section">
        <form method="GET" action="">
            <div class="filter-grid">
                <?php if ($tip != 'zaposleni'): ?>
                    <div class="form-group">
                        <label for="lokacija">Lokacija</label>
                        <select name="lokacija" id="lokacija" onchange="this.form.submit()">
                            <option value="">Sve lokacije</option>
                            <?php foreach ($dostupne_lokacije as $lok): ?>
                                <option value="<?php echo $lok; ?>" <?php echo ($filter_lokacija == $lok) ? 'selected' : ''; ?>>
                                    <?php echo $lok; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="status">Status</label>
                    <select name="status" id="status" onchange="this.form.submit()">
                        <option value="">Svi statusi</option>
                        <option value="u_radu" <?php echo ($filter_status == 'u_radu') ? 'selected' : ''; ?>>U radu</option>
                        <option value="zavrseno" <?php echo ($filter_status == 'zavrseno') ? 'selected' : ''; ?>>Završeno</option>
                        <option value="placeno" <?php echo ($filter_status == 'placeno') ? 'selected' : ''; ?>>Plaćeno</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="pretraga">Pretraga</label>
                    <input
                        type="text"
                        name="pretraga"
                        id="pretraga"
                        placeholder="Registracija, marka, vlasnik..."
                        value="<?php echo e($filter_pretraga); ?>"
                    >
                </div>

                <div class="form-group" style="display: flex; align-items: flex-end;">
                    <button type="submit" class="btn btn-primary" style="margin-right: 10px;">🔍 Pretraži</button>
                    <a href="lista_vozila.php" class="btn btn-secondary">❌ Očisti</a>
                </div>
            </div>
        </form>
    </div>

    <!-- Tabela -->
    <div class="table-container">
        <?php if (empty($vozila)): ?>
            <div class="empty-state">
                <h3>🚗 Nema vozila</h3>
                <p>Trenutno nema vozila koja odgovaraju vašim filterima.</p>
                <a href="modules/vozila/dodaj.php" class="btn btn-primary" style="margin-top: 20px;">➕ Dodaj prvo vozilo</a>
            </div>
        <?php else: ?>
            <table>
                <thead>
                <tr>
                    <th>Slika</th>
                    <th>Registracija</th>
                    <th>Marka</th>
                    <th>Vlasnik</th>
                    <th>Kontakt</th>
                    <th>Datum prijema</th>
                    <th>Lokacija</th>
                    <th>Status</th>
                    <th>Kreirao</th>
                    <th>Akcije</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($vozila as $vozilo): ?>
                    <tr>
                        <td>
                            <?php if ($vozilo['slika_vozila']): ?>
                                <img src="uploads/vozila/<?php echo e($vozilo['slika_vozila']); ?>"
                                     alt="Vozilo"
                                     class="vehicle-image">
                            <?php else: ?>
                                <div class="no-image">🚗</div>
                            <?php endif; ?>
                        </td>
                        <td><strong><?php echo e($vozilo['registracija']); ?></strong></td>
                        <td><?php echo e($vozilo['marka']); ?></td>
                        <td><?php echo e($vozilo['vlasnik']); ?></td>
                        <td><?php echo e($vozilo['kontakt']); ?></td>
                        <td><?php echo formatuj_datum($vozilo['datum_prijema']); ?></td>
                        <td><?php echo e($vozilo['lokacija']); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $vozilo['status']; ?>">
                                <?php echo get_status_text($vozilo['status']); ?>
                            </span>
                        </td>
                        <td><?php echo e($vozilo['ime'] . ' ' . $vozilo['prezime']); ?></td>
                        <td>
                            <div class="action-buttons">
                                <a href="modules/vozila/detalji.php?id=<?php echo $vozilo['id']; ?>"
                                   class="btn-action btn-view">
                                    👁️ Vidi
                                </a>
                                <?php if ($tip == 'administrator' || $tip == 'menadzer'): ?>
                                    <button onclick="obrisiVozilo(<?php echo $vozilo['id']; ?>, '<?php echo e($vozilo['registracija']); ?>')"
                                            class="btn-action btn-delete">
                                        🗑️ Obriši
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<script>
    function obrisiVozilo(id, registracija) {
        if (confirm('Da li ste sigurni da želite da obrišete vozilo ' + registracija + '?\n\nOva akcija se ne može poništiti!')) {
            window.location.href = 'modules/vozila/obrisi.php?id=' + id;
        }
    }
</script>
</body>
</html>