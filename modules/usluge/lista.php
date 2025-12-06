<?php
require_once '../../config.php';
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';

// Samo administrator i menadžer mogu pristupiti
proveri_tip(['administrator', 'menadzer']);

// Postavi promenljive za header
$page_title = 'Usluge - ' . SITE_NAME;
$base_url = '../../';

$uspeh = $_SESSION['uspeh'] ?? '';
$greska = $_SESSION['greska'] ?? '';
unset($_SESSION['uspeh'], $_SESSION['greska']);

// Preuzmi sve usluge
$stmt = $conn->query("SELECT * FROM usluge ORDER BY naziv");
$usluge = $stmt->fetchAll();

// Include header
include '../../includes/header.php';
?>

    <style>
        .status-active {
            color: #28a745;
            font-weight: bold;
        }

        .status-inactive {
            color: #dc3545;
            font-weight: bold;
        }

        .price {
            font-size: 18px;
            font-weight: bold;
            color: #667eea;
        }
    </style>

    <div class="container">
        <div class="page-header">
            <h1>🔧 Usluge</h1>
            <a href="dodaj.php" class="btn btn-primary">➕ Dodaj uslugu</a>
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

        <div class="table-container">
            <?php if (empty($usluge)): ?>
                <div class="empty-state">
                    <h3>🔧 Nema usluga</h3>
                    <p>Trenutno nema definisanih usluga.</p>
                    <a href="dodaj.php" class="btn btn-primary" style="margin-top: 20px;">➕ Dodaj prvu uslugu</a>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Naziv usluge</th>
                        <th>Cena (RSD)</th>
                        <th>Status</th>
                        <th>Datum kreiranja</th>
                        <th>Akcije</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($usluge as $usluga): ?>
                        <tr>
                            <td><?php echo $usluga['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($usluga['naziv']); ?></strong></td>
                            <td class="price"><?php echo number_format($usluga['cena'], 2, ',', '.'); ?> RSD</td>
                            <td>
                                <?php if ($usluga['aktivan']): ?>
                                    <span class="status-active">✓ Aktivna</span>
                                <?php else: ?>
                                    <span class="status-inactive">✗ Neaktivna</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo formatuj_datum($usluga['datum_kreiranja']); ?></td>
                            <td>
                                <div class="action-buttons">
                                    <a href="izmeni.php?id=<?php echo $usluga['id']; ?>"
                                       class="btn-action btn-view">
                                        ✏️ Izmeni
                                    </a>
                                    <button onclick="obrisiUslugu(<?php echo $usluga['id']; ?>, '<?php echo htmlspecialchars($usluga['naziv'], ENT_QUOTES); ?>')"
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
    </div>

    <script>
        function obrisiUslugu(id, naziv) {
            if (confirm('Da li ste sigurni da želite da obrišete uslugu "' + naziv + '"?\n\nOva akcija se ne može poništiti!')) {
                window.location.href = 'obrisi.php?id=' + id;
            }
        }
    </script>

<?php include '../../includes/footer.php'; ?>