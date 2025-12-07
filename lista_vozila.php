<?php
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

proveri_login();

// Postavi promenljive za header
$page_title = 'Lista vozila - ' . SITE_NAME;
$base_url = './';

$ime = $_SESSION['ime'];
$prezime = $_SESSION['prezime'];
$tip = $_SESSION['tip_korisnika'];
$lokacija_korisnika = $_SESSION['lokacija'];

// Određivanje koje lokacije korisnik može da vidi
$dostupne_lokacije = [];
if ($tip == 'administrator' || $tip == 'menadzer') {
    // Koristi nove SESSION lokacije
    if (isset($_SESSION['sve_lokacije']) && $_SESSION['sve_lokacije']) {
        $dostupne_lokacije = ['Ostružnica', 'Žarkovo', 'Mirijevo'];
    } elseif (isset($_SESSION['lokacije']) && is_array($_SESSION['lokacije'])) {
        $dostupne_lokacije = $_SESSION['lokacije'];
    } else {
        // Fallback na staru lokaciju
        $dostupne_lokacije = [$lokacija_korisnika];
    }
} else {
    // Zaposleni vide samo svoju lokaciju
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
$sql = "SELECT v.*, k.ime, k.prezime, pl.naziv as pravno_lice_naziv
        FROM vozila v 
        LEFT JOIN korisnici k ON v.kreirao_korisnik_id = k.id 
        LEFT JOIN pravna_lica pl ON v.pravno_lice_id = pl.id
        WHERE 1=1";

$params = [];

// Filter po lokaciji
if (!empty($filter_lokacija)) {
    $sql .= " AND v.lokacija = ?";
    $params[] = $filter_lokacija;
} else {
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

// Include header
include 'includes/header.php';
?>

    <link rel="stylesheet" href="assets/css/responsive-tables.css">

    <div class="container">
        <div class="page-header">
            <h1>📋 Lista vozila</h1>
            <a href="modules/vozila/dodaj.php" class="btn btn-primary">➕ Dodaj vozilo</a>
        </div>


        <!-- Filteri -->
        <div class="filter-section">
            <form method="GET" action="">
                <div class="filter-grid">
                    <?php if ($tip != 'zaposleni'): ?>
                        <div class="form-group">
                            <label for="lokacija">Lokacija</label>
                            <select name="lokacija" id="lokacija" onchange="this.form.submit()">
                                <option value="">Sve dostupne lokacije</option>
                                <?php foreach ($dostupne_lokacije as $lok): ?>
                                    <option value="<?php echo $lok; ?>" <?php echo ($filter_lokacija == $lok) ? 'selected' : ''; ?>>
                                        <?php echo $lok; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <small style="display: block; margin-top: 5px; color: #666;">
                                Vaše dodeljene lokacije: <?php echo implode(', ', $dostupne_lokacije); ?>
                            </small>
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
                                value="<?php echo htmlspecialchars($filter_pretraga); ?>"
                        >
                    </div>

                    <div class="form-group" style="display: flex; align-items: flex-end; gap: 10px;">
                        <button type="submit" class="btn btn-primary">🔍 Pretraži</button>
                        <a href="lista_vozila.php" class="btn btn-secondary">❌ Očisti</a>
                    </div>
                </div>
            </form>
        </div>

        <?php
        // Prikaz primenjenih filtera ispod forme
        $aktivni_filteri = [];
        if (!empty($filter_lokacija)) {
            $aktivni_filteri[] = 'Lokacija: ' . htmlspecialchars($filter_lokacija);
        }
        if (!empty($filter_status)) {
            $aktivni_filteri[] = 'Status: ' . get_status_text($filter_status);
        }
        if (!empty($filter_pretraga)) {
            $aktivni_filteri[] = 'Pretraga: "' . htmlspecialchars($filter_pretraga) . '"';
        }

        if (!empty($aktivni_filteri)) {
            echo '<div class="applied-filters"><strong>Primenjeni filteri:</strong> ' . implode(' | ', $aktivni_filteri) . '</div>';
        }
        ?>

        <?php if (!empty($vozila)): ?>
            <div class="table-info">
                <p>Ukupno: <strong><?php echo count($vozila); ?></strong> vozila</p>
            </div>
        <?php endif; ?>

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




        <!-- Scroll hint -->
        <div class="scroll-hint">
            ← Scroll levo/desno da vidiš sve kolone →
        </div>

        <!-- Tabela -->
        <div class="table-container">
            <div class="table-wrapper">
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
                            <th>Vlasnik/Firma</th>
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
                                        <img src="uploads/vozila/<?php echo htmlspecialchars($vozilo['slika_vozila']); ?>"
                                             alt="Vozilo"
                                             class="vehicle-image">
                                    <?php else: ?>
                                        <div class="no-image">🚗</div>
                                    <?php endif; ?>
                                </td>
                                <td><strong><?php echo htmlspecialchars($vozilo['registracija']); ?></strong></td>
                                <td><?php echo htmlspecialchars($vozilo['marka']); ?></td>

                                <td>
                                    <?php
                                    if ($vozilo['tip_klijenta'] == 'pravno' && $vozilo['pravno_lice_naziv']) {
                                        echo '<strong>' . htmlspecialchars($vozilo['pravno_lice_naziv']) . '</strong>';
                                    } else {
                                        echo htmlspecialchars($vozilo['vlasnik']);
                                    }
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($vozilo['kontakt']); ?></td>
                                <td><?php echo formatuj_datum($vozilo['datum_prijema']); ?></td>
                                <td>📍<?php echo htmlspecialchars($vozilo['lokacija']); ?></td>
                                <td>
                            <span class="status-badge status-<?php echo $vozilo['status']; ?>">
                                <?php echo get_status_text($vozilo['status']); ?>
                            </span>
                                </td>
                                <td><?php echo htmlspecialchars($vozilo['ime'] . ' ' . $vozilo['prezime']); ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="modules/vozila/detalji.php?id=<?php echo $vozilo['id']; ?>"
                                           class="btn-action btn-view">
                                            Vidi detalje
                                        </a>
                                        <?php if ($tip == 'administrator' || $tip == 'menadzer' || $tip == 'zaposleni'): ?>
                                            <button onclick="obrisiVozilo(<?php echo $vozilo['id']; ?>, '<?php echo htmlspecialchars($vozilo['registracija'], ENT_QUOTES); ?>')"
                                                    class="btn-action btn-delete">
                                                🗑️
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div><!-- .table-wrapper -->
        </div><!-- .table-container -->


    </div>

    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
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
        .stat-card.ukupno .number { color: #FF411C; }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }

            .stat-card {
                padding: 15px;
            }

            .stat-card h4 {
                font-size: 12px;
            }

            .stat-card .number {
                font-size: 24px;
            }
        }

        .status-u_radu {
            background-color: #dc3545;
            color: white !important;
        }
    </style>

    <script>
        function obrisiVozilo(id, registracija) {
            if (confirm('Da li ste sigurni da želite da obrišete vozilo ' + registracija + '?\n\nOva akcija se ne može poništiti!')) {
                window.location.href = 'modules/vozila/obrisi.php?id=' + id;
            }
        }
    </script>

<?php include 'includes/footer.php'; ?>