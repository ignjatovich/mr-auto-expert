<?php
require_once '../../config.php';
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Provera login-a
proveri_login();

$ime = $_SESSION['ime'];
$prezime = $_SESSION['prezime'];
$tip = $_SESSION['tip_korisnika'];
$korisnik_lokacija = $_SESSION['lokacija'];

// Filteri
$filter_lokacija = $_GET['lokacija'] ?? '';
$filter_status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

// SQL upit sa filterima
$sql = "SELECT 
    v.*,
    CONCAT(k.ime, ' ', k.prezime) as kreirao_ime
FROM vozila v
LEFT JOIN korisnici k ON v.kreirao_korisnik_id = k.id
WHERE 1=1";

$params = [];

// Filter po lokaciji
if ($filter_lokacija) {
    $sql .= " AND v.lokacija = ?";
    $params[] = $filter_lokacija;
} else {
    // Ako korisnik nije admin, prikazuj samo vozila sa njegove lokacije
    if ($tip != 'administrator') {
        $sql .= " AND v.lokacija = ?";
        $params[] = $korisnik_lokacija;
    }
}

// Filter po statusu
if ($filter_status) {
    $sql .= " AND v.status = ?";
    $params[] = $filter_status;
}

// Pretraga
if ($search) {
    $sql .= " AND (v.registracija LIKE ? OR v.marka LIKE ? OR v.vlasnik LIKE ? OR v.kontakt LIKE ?)";
    $search_param = '%' . $search . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$sql .= " ORDER BY v.datum_prijema DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$vozila = $stmt->fetchAll();

// Brojanje vozila po statusima
$stmt_stats = $conn->prepare("
    SELECT 
        status,
        COUNT(*) as broj
    FROM vozila
    WHERE lokacija = ?
    GROUP BY status
");
$stmt_stats->execute([$tip == 'administrator' && !$filter_lokacija ? $korisnik_lokacija : ($filter_lokacija ?: $korisnik_lokacija)]);
$stats = $stmt_stats->fetchAll(PDO::FETCH_KEY_PAIR);

$u_radu = $stats['u_radu'] ?? 0;
$zavrseno = $stats['zavrseno'] ?? 0;
$placeno = $stats['placeno'] ?? 0;
?>
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista vozila - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../../assets/css/style.css">
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
                <?php echo e($ime . ' ' . $prezime); ?>
                <span class="badge badge-<?php echo $tip; ?>">
                    <?php echo ucfirst($tip); ?>
                </span>
            </span>
            <a href="../../logout.php" class="btn btn-secondary btn-sm">Odjavi se</a>
        </div>
    </div>
</nav>

<div class="container">
    <div class="page-header">
        <h1>📋 Lista vozila</h1>
        <a href="dodaj.php" class="btn btn-primary">➕ Dodaj vozilo</a>
    </div>

    <!-- STATISTIKA -->
    <div class="stats-cards">
        <div class="stat-card stat-danger">
            <div class="stat-icon">🔴</div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $u_radu; ?></div>
                <div class="stat-label">U radu</div>
            </div>
        </div>
        <div class="stat-card stat-warning">
            <div class="stat-icon">🟡</div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $zavrseno; ?></div>
                <div class="stat-label">Završeno</div>
            </div>
        </div>
        <div class="stat-card stat-success">
            <div class="stat-icon">🟢</div>
            <div class="stat-content">
                <div class="stat-number"><?php echo $placeno; ?></div>
                <div class="stat-label">Plaćeno</div>
            </div>
        </div>
    </div>

    <!-- FILTERI I PRETRAGA -->
    <div class="filter-section">
        <form method="GET" class="filter-form">
            <div class="filter-group">
                <label for="search">🔍 Pretraga</label>
                <input
                        type="text"
                        id="search"
                        name="search"
                        placeholder="Registracija, marka, vlasnik..."
                        value="<?php echo e($search); ?>"
                >
            </div>

            <?php if ($tip == 'administrator'): ?>
                <div class="filter-group">
                    <label for="lokacija">📍 Lokacija</label>
                    <select id="lokacija" name="lokacija">
                        <option value="">Sve lokacije</option>
                        <option value="Ostružnica" <?php echo $filter_lokacija == 'Ostružnica' ? 'selected' : ''; ?>>Ostružnica</option>
                        <option value="Žarkovo" <?php echo $filter_lokacija == 'Žarkovo' ? 'selected' : ''; ?>>Žarkovo</option>
                        <option value="Mirijevo" <?php echo $filter_lokacija == 'Mirijevo' ? 'selected' : ''; ?>>Mirijevo</option>
                    </select>
                </div>
            <?php endif; ?>

            <div class="filter-group">
                <label for="status">📊 Status</label>
                <select id="status" name="status">
                    <option value="">Svi statusi</option>
                    <option value="u_radu" <?php echo $filter_status == 'u_radu' ? 'selected' : ''; ?>>U radu</option>
                    <option value="zavrseno" <?php echo $filter_status == 'zavrseno' ? 'selected' : ''; ?>>Završeno</option>
                    <option value="placeno" <?php echo $filter_status == 'placeno' ? 'selected' : ''; ?>>Plaćeno</option>
                </select>
            </div>

            <div class="filter-actions">
                <button type="submit" class="btn btn-primary">Filtriraj</button>
                <a href="lista.php" class="btn btn-secondary">Resetuj</a>
            </div>
        </form>
    </div>

    <!-- TABELA SA HORIZONTAL SCROLL -->
    <div class="table-container">
        <div class="table-scroll">
            <table class="vozila-table">
                <thead>
                <tr>
                    <th>Status</th>
                    <th>Registracija</th>
                    <th>Marka</th>
                    <th>Vlasnik</th>
                    <th>Kontakt</th>
                    <th>Datum prijema</th>
                    <th>Parking</th>
                    <th>Lokacija</th>
                    <th>Cena</th>
                    <th>Akcije</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($vozila)): ?>
                    <tr>
                        <td colspan="10" class="no-data">
                            <div class="no-data-message">
                                <span class="no-data-icon">📭</span>
                                <p>Nema vozila za prikaz</p>
                                <a href="dodaj.php" class="btn btn-primary btn-sm">Dodaj prvo vozilo</a>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($vozila as $vozilo): ?>
                        <tr>
                            <td>
                                <?php echo get_status_badge($vozilo['status']); ?>
                            </td>
                            <td>
                                <strong><?php echo e($vozilo['registracija']); ?></strong>
                            </td>
                            <td><?php echo e($vozilo['marka']); ?></td>
                            <td><?php echo e($vozilo['vlasnik']); ?></td>
                            <td><?php echo e($vozilo['kontakt']); ?></td>
                            <td>
                                <small><?php echo formatuj_datum($vozilo['datum_prijema']); ?></small>
                            </td>
                            <td><?php echo e($vozilo['parking_lokacija']); ?></td>
                            <td>
                                <span class="badge-lokacija"><?php echo e($vozilo['lokacija']); ?></span>
                            </td>
                            <td>
                                <strong><?php echo number_format($vozilo['cena'], 2); ?> RSD</strong>
                            </td>
                            <td>
                                <div class="action-buttons-table">
                                    <a href="detalji.php?id=<?php echo $vozilo['id']; ?>" class="btn-table btn-info" title="Detalji">
                                        👁️
                                    </a>
                                    <a href="izmeni.php?id=<?php echo $vozilo['id']; ?>" class="btn-table btn-edit" title="Izmeni">
                                        ✏️
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if (!empty($vozila)): ?>
        <div class="table-info">
            <p>Ukupno: <strong><?php echo count($vozila); ?></strong> vozila</p>
        </div>
    <?php endif; ?>
</div>

</body>
</html>