<?php
require_once '../../config.php';
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Svi tipovi korisnika mogu pristupiti
proveri_login();

// Postavi promenljive za header
$page_title = 'Pravna lica - ' . SITE_NAME;
$base_url = '../../';

$uspeh = $_SESSION['uspeh'] ?? '';
$greska = $_SESSION['greska'] ?? '';
unset($_SESSION['uspeh'], $_SESSION['greska']);

// Pretraga
$pretraga = $_GET['pretraga'] ?? '';

// Query za preuzimanje pravnih lica
$sql = "SELECT * FROM pravna_lica WHERE 1=1";
$params = [];

if (!empty($pretraga)) {
    $sql .= " AND (naziv LIKE ? OR pib LIKE ? OR kontakt_telefon LIKE ? OR email LIKE ?)";
    $search = "%$pretraga%";
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
    $params[] = $search;
}

$sql .= " ORDER BY naziv ASC";

$stmt = $conn->prepare($sql);
$stmt->execute($params);
$pravna_lica = $stmt->fetchAll();

// Include header
include '../../includes/header.php';
?>

    <link rel="stylesheet" href="../../assets/css/responsive-tables.css">

    <style>
        .status-active {
            color: #28a745;
            font-weight: bold;
        }

        .status-inactive {
            color: #dc3545;
            font-weight: bold;
        }
    </style>

    <div class="container">
        <div class="page-header">
            <h1>🏢 Pravna lica</h1>
            <a href="dodaj.php" class="btn btn-primary">➕ Dodaj pravno lice</a>
        </div>

        <?php if ($greska): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($greska); ?>
            </div>
        <?php endif; ?>

        <?php if ($uspeh): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($uspeh); ?>
            </div>
        <?php endif; ?>

        <!-- Pretraga -->
        <div class="filter-section">
            <form method="GET" action="">
                <div class="filter-grid">
                    <div class="form-group">
                        <label for="pretraga">🔍 Pretraga</label>
                        <input
                            type="text"
                            name="pretraga"
                            id="pretraga"
                            placeholder="Naziv, PIB, telefon, email..."
                            value="<?php echo htmlspecialchars($pretraga); ?>"
                        >
                    </div>

                    <div class="form-group" style="display: flex; align-items: flex-end; gap: 10px;">
                        <button type="submit" class="btn btn-primary">🔍 Pretraži</button>
                        <a href="lista.php" class="btn btn-secondary">❌ Očisti</a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Tabela -->
        <div class="scroll-hint">
            ← Pomerajte tabelu levo/desno da vidite sve kolone →
        </div>

        <div class="table-wrapper">
            <?php if (empty($pravna_lica)): ?>
                <div class="empty-state">
                    <h3>🏢 Nema pravnih lica</h3>
                    <p>Trenutno nema pravnih lica koja odgovaraju vašoj pretrazi.</p>
                    <a href="dodaj.php" class="btn btn-primary" style="margin-top: 20px;">➕ Dodaj prvo pravno lice</a>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Naziv</th>
                        <th>PIB</th>
                        <th>Kontakt telefon</th>
                        <th>Email</th>
                        <th>Adresa</th>
                        <th>Status</th>
                        <th>Akcije</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($pravna_lica as $firma): ?>
                        <tr>
                            <td><?php echo $firma['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($firma['naziv']); ?></strong></td>
                            <td><?php echo htmlspecialchars($firma['pib'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($firma['kontakt_telefon'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($firma['email'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($firma['adresa'] ?? '-'); ?></td>
                            <td>
                                <?php if ($firma['aktivan']): ?>
                                    <span class="status-active">✓ Aktivno</span>
                                <?php else: ?>
                                    <span class="status-inactive">✗ Neaktivno</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <a href="izmeni.php?id=<?php echo $firma['id']; ?>"
                                       class="btn-action btn-view">
                                        ✏️ Izmeni
                                    </a>
                                    <button onclick="obrisiPravnoLice(<?php echo $firma['id']; ?>, '<?php echo htmlspecialchars($firma['naziv'], ENT_QUOTES); ?>')"
                                            class="btn-action btn-delete">
                                        🗑️ Obriši
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>

        <?php if (!empty($pravna_lica)): ?>
            <div class="table-info">
                <p>Ukupno: <strong><?php echo count($pravna_lica); ?></strong> pravnih lica</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function obrisiPravnoLice(id, naziv) {
            if (confirm('Da li ste sigurni da želite da obrišete pravno lice "' + naziv + '"?\n\nOva akcija se ne može poništiti!')) {
                window.location.href = 'obrisi.php?id=' + id;
            }
        }
    </script>

<?php include '../../includes/footer.php'; ?>