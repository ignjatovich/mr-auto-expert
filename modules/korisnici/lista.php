<?php
require_once '../../config.php';
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Samo administrator i menadžer mogu pristupiti
proveri_tip(['administrator', 'menadzer']);

// Postavi promenljive za header
$page_title = 'Korisnici - ' . SITE_NAME;
$base_url = '../../';

$uspeh = $_SESSION['uspeh'] ?? '';
$greska = $_SESSION['greska'] ?? '';
unset($_SESSION['uspeh'], $_SESSION['greska']);

// Filtri
$filter_tip = $_GET['tip'] ?? '';
$filter_lokacija = $_GET['lokacija'] ?? '';
$filter_status = $_GET['status'] ?? '';

// Query
$sql = "SELECT * FROM korisnici WHERE 1=1";
$params = [];

// Filter po tipu
if (!empty($filter_tip)) {
    $sql .= " AND tip_korisnika = ?";
    $params[] = $filter_tip;
}

// Filter po lokaciji
if (!empty($filter_lokacija)) {
    $sql .= " AND lokacija = ?";
    $params[] = $filter_lokacija;
}

// Filter po statusu
if ($filter_status === 'aktivan') {
    $sql .= " AND aktivan = 1";
} elseif ($filter_status === 'neaktivan') {
    $sql .= " AND aktivan = 0";
}

$sql .= " ORDER BY datum_kreiranja DESC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$korisnici = $stmt->fetchAll();

// Include header POSLE svih provera
require_once '../../includes/header.php';
?>
    <link rel="stylesheet" href="<?php echo $base_url; ?>assets/css/responsive-tables.css">

    <div class="container">
        <div class="page-header">
            <h1>👥 Korisnici sistema</h1>
            <a href="dodaj.php" class="btn btn-primary">➕ Dodaj korisnika</a>
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

        <!-- FILTERI -->
        <div class="filter-section">
            <form method="GET" class="filter-form">
                <div class="filter-group">
                    <label for="tip">Tip korisnika</label>
                    <select id="tip" name="tip" onchange="this.form.submit()">
                        <option value="">Svi tipovi</option>
                        <option value="administrator" <?php echo $filter_tip == 'administrator' ? 'selected' : ''; ?>>Administrator</option>
                        <option value="menadzer" <?php echo $filter_tip == 'menadzer' ? 'selected' : ''; ?>>Menadžer</option>
                        <option value="zaposleni" <?php echo $filter_tip == 'zaposleni' ? 'selected' : ''; ?>>Zaposleni</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="lokacija">Lokacija</label>
                    <select id="lokacija" name="lokacija" onchange="this.form.submit()">
                        <option value="">Sve lokacije</option>
                        <option value="Ostružnica" <?php echo $filter_lokacija == 'Ostružnica' ? 'selected' : ''; ?>>Ostružnica</option>
                        <option value="Žarkovo" <?php echo $filter_lokacija == 'Žarkovo' ? 'selected' : ''; ?>>Žarkovo</option>
                        <option value="Mirijevo" <?php echo $filter_lokacija == 'Mirijevo' ? 'selected' : ''; ?>>Mirijevo</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="status">Status</label>
                    <select id="status" name="status" onchange="this.form.submit()">
                        <option value="">Svi</option>
                        <option value="aktivan" <?php echo $filter_status == 'aktivan' ? 'selected' : ''; ?>>Aktivni</option>
                        <option value="neaktivan" <?php echo $filter_status == 'neaktivan' ? 'selected' : ''; ?>>Neaktivni</option>
                    </select>
                </div>

                <div class="filter-actions">
                    <a href="lista.php" class="btn btn-secondary">Resetuj</a>
                </div>
            </form>
        </div>

        <?php if (!empty($korisnici)): ?>
            <div class="table-info">
                <p>Ukupno: <strong><?php echo count($korisnici); ?></strong> korisnika</p>
            </div>
        <?php endif; ?>

        <div class="scroll-hint">← Scroll levo/desno da vidiš sve →</div>

        <!-- TABELA -->
        <div class="table-container">
            <div class="table-wrapper">
                <?php if (empty($korisnici)): ?>
                    <div class="no-data">
                        <div class="no-data-message">
                            <span class="no-data-icon">👥</span>
                            <p>Nema korisnika za prikaz</p>
                            <a href="dodaj.php" class="btn btn-primary" style="margin-top: 20px;">➕ Dodaj prvog korisnika</a>
                        </div>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                        <tr>
                            <th>ID</th>
                            <th>Korisničko ime</th>
                            <th>Ime</th>
                            <th>Email</th>
                            <th>Telefon</th>
                            <th>Tip korisnika</th>
                            <th>Lokacija</th>
                            <th>Status</th>
                            <th>Datum kreiranja</th>
                            <th>Akcije</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($korisnici as $k): ?>
                            <tr>
                                <td><?php echo $k['id']; ?></td>
                                <td><strong><?php echo e($k['korisnicko_ime']); ?></strong></td>
                                <td><?php echo e($k['ime']); ?></td>
                                <td><?php echo e($k['email']); ?></td>
                                <td><?php echo e($k['telefon']); ?></td>
                                <td>
                                <span class="badge badge-<?php echo $k['tip_korisnika']; ?>">
                                    <?php echo ucfirst($k['tip_korisnika']); ?>
                                </span>
                                </td>
                                <td>
                                    <?php
                                    // Prikaži lokacije
                                    if ($k['sve_lokacije']) {
                                        echo '<span class="badge-lokacija" style="background: #9c27b0; color: white;">Sve lokacije</span>';
                                    } elseif (!empty($k['lokacije'])) {
                                        $lokacije = json_decode($k['lokacije'], true);
                                        foreach ($lokacije as $lok) {
                                            echo '<span class="badge-lokacija" style="margin-right: 5px;">' . e($lok) . '</span>';
                                        }
                                    } else {
                                        echo '<span class="badge-lokacija">' . e($k['lokacija']) . '</span>';
                                    }
                                    ?>
                                </td>
                                <td style="text-align: center; font-size: 20px;">
                                    <?php if ($k['aktivan']): ?>
                                        <span style="color: #28a745;" title="Aktivan">✓</span>
                                    <?php else: ?>
                                        <span style="color: #dc3545;" title="Neaktivan">✗</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small><?php echo formatuj_datum($k['datum_kreiranja']); ?></small>
                                </td>
                                <td>
                                    <div class="action-buttons-table">
                                        <a href="izmeni.php?id=<?php echo $k['id']; ?>" class="btn-table btn-edit" title="Izmeni">
                                            ✏️
                                        </a>
                                        <?php
                                        // Prikaži dugme za brisanje ako:
                                        // 1. Nije trenutni korisnik
                                        // 2. Administrator može brisati sve
                                        // 3. Menadžer može brisati samo zaposlene
                                        $moze_brisati = false;
                                        if ($k['id'] != $_SESSION['korisnik_id']) {
                                            if ($_SESSION['tip_korisnika'] == 'administrator') {
                                                $moze_brisati = true;
                                            } elseif ($_SESSION['tip_korisnika'] == 'menadzer' && $k['tip_korisnika'] == 'zaposleni') {
                                                $moze_brisati = true;
                                            }
                                        }
                                        ?>
                                        <?php if ($moze_brisati): ?>
                                            <button onclick="obrisiKorisnika(<?php echo $k['id']; ?>, '<?php echo e($k['korisnicko_ime']); ?>')" class="btn-table btn-delete" title="Obriši">
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
            </div>

        </div>


    </div>

    <script>
        function obrisiKorisnika(id, korisnicko_ime) {
            if (confirm('Da li ste sigurni da želite da obrišete korisnika "' + korisnicko_ime + '"?\n\nOva akcija se ne može poništiti!\n\nNapomena: Korisnici koji su kreirali ili menjali vozila ne mogu biti obrisani.')) {
                window.location.href = 'obrisi.php?id=' + id;
            }
        }
    </script>

<?php require_once '../../includes/footer.php'; ?>