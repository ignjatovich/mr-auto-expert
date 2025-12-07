<?php
$base_url = '../../';
require_once '../../config.php';
require_once '../../includes/db.php';
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../includes/header.php';

// SVI korisnici mogu pristupiti uslugama
proveri_login();

$uspeh = $_SESSION['uspeh'] ?? '';
$greska = $_SESSION['greska'] ?? '';
unset($_SESSION['uspeh'], $_SESSION['greska']);

// Preuzmi sve usluge
$stmt = $conn->query("SELECT * FROM usluge ORDER BY naziv");
$usluge = $stmt->fetchAll();
?>

    <link rel="stylesheet" href="<?php echo $base_url; ?>assets/css/responsive-tables.css">

    <div class="container">
        <div class="page-header">
            <h1>🔧 Usluge</h1>
            <a href="dodaj.php" class="btn btn-primary">➕ Dodaj uslugu</a>
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

        <!-- TABELA SA RESPONSIVE WRAPPER -->
        <div class="table-container">
            <div class="table-wrapper">
                <?php if (empty($usluge)): ?>
                    <div class="no-data">
                        <div class="no-data-message">
                            <span class="no-data-icon">🔧</span>
                            <p>Nema definisanih usluga</p>
                            <a href="dodaj.php" class="btn btn-primary" style="margin-top: 20px;">➕ Dodaj prvu uslugu</a>
                        </div>
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
                                <td><strong><?php echo e($usluga['naziv']); ?></strong></td>
                                <td>
                                    <strong style="color: #667eea; font-size: 16px;">
                                        <?php echo number_format($usluga['cena'], 2, ',', '.'); ?> RSD
                                    </strong>
                                </td>
                                <td style="text-align: center; font-size: 20px;">
                                    <?php if ($usluga['aktivan']): ?>
                                        <span style="color: #28a745;" title="Aktivna">✓</span>
                                    <?php else: ?>
                                        <span style="color: #dc3545;" title="Neaktivna">✗</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small><?php echo formatuj_datum($usluga['datum_kreiranja']); ?></small>
                                </td>
                                <td>
                                    <div class="action-buttons-table">
                                        <a href="izmeni.php?id=<?php echo $usluga['id']; ?>" class="btn-table btn-edit" title="Izmeni">
                                            ✏️ Izmeni
                                        </a>
                                        <button onclick="obrisiUslugu(<?php echo $usluga['id']; ?>, '<?php echo e($usluga['naziv']); ?>')" class="btn-table btn-delete" title="Obriši">
                                            🗑️
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            <?php if (!empty($usluge)): ?>
                <div class="scroll-hint">← Scroll levo/desno da vidiš sve →</div>
            <?php endif; ?>
        </div>

        <?php if (!empty($usluge)): ?>
            <div class="table-info">
                <p>Ukupno: <strong><?php echo count($usluge); ?></strong> usluga</p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        function obrisiUslugu(id, naziv) {
            if (confirm('Da li ste sigurni da želite da obrišete uslugu "' + naziv + '"?\n\nOva akcija se ne može poništiti!')) {
                window.location.href = 'obrisi.php?id=' + id;
            }
        }
    </script>

<?php require_once '../../includes/footer.php'; ?>