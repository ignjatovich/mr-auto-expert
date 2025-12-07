<?php
$base_url = '';
require_once 'config.php';
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/header.php';

proveri_login();

$ime = $_SESSION['ime'];
$prezime = $_SESSION['prezime'];
$tip = $_SESSION['tip_korisnika'];
$lokacija = $_SESSION['lokacija'];

// Statistika vozila za trenutnu lokaciju
$lokacija_korisnika = $_SESSION['lokacija'];

// Ako je administrator ili menadžer, uzmi sve lokacije
if ($tip == 'administrator' || $tip == 'menadzer') {
    $stmt = $conn->query("
        SELECT 
            status,
            COUNT(*) as broj
        FROM vozila
        GROUP BY status
    ");
} else {
    // Zaposleni vidi samo svoju lokaciju
    $stmt = $conn->prepare("
        SELECT 
            status,
            COUNT(*) as broj
        FROM vozila
        WHERE lokacija = ?
        GROUP BY status
    ");
    $stmt->execute([$lokacija_korisnika]);
}

$stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$u_radu = $stats['u_radu'] ?? 0;
$zavrseno = $stats['zavrseno'] ?? 0;
$placeno = $stats['placeno'] ?? 0;

// Ukupno vozila danas
if ($tip == 'administrator' || $tip == 'menadzer') {
    $stmt = $conn->query("SELECT COUNT(*) as broj FROM vozila WHERE DATE(datum_prijema) = CURDATE()");
} else {
    $stmt = $conn->prepare("SELECT COUNT(*) as broj FROM vozila WHERE lokacija = ? AND DATE(datum_prijema) = CURDATE()");
    $stmt->execute([$lokacija_korisnika]);
}
$vozila_danas = $stmt->fetch()['broj'];

// Link parametri za zaposlene (automatski dodaj lokaciju)
$lokacija_param = ($tip == 'zaposleni') ? '&lokacija=' . urlencode($lokacija_korisnika) : '';

// Statistika po lokacijama - samo za administratora
if ($tip == 'administrator') {
    $stmt = $conn->query("
        SELECT 
            lokacija,
            COUNT(*) as ukupno,
            SUM(CASE WHEN status = 'u_radu' THEN 1 ELSE 0 END) as u_radu,
            SUM(CASE WHEN status = 'zavrseno' THEN 1 ELSE 0 END) as zavrseno,
            SUM(CASE WHEN status = 'placeno' THEN 1 ELSE 0 END) as placeno
        FROM vozila
        GROUP BY lokacija
    ");
    $lokacije_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Formatiraj za lakši pristup
    $lokacije = [];
    foreach ($lokacije_stats as $stat) {
        $lokacije[$stat['lokacija']] = $stat;
    }
}
?>

    <div class="container">
        <div class="welcome-section">
            <h1>Dobrodošli, <?php echo htmlspecialchars($ime); ?>! 👋</h1>
            <?php if ($tip != 'administrator'): ?>
                <p>Lokacija: 📍<strong><?php echo htmlspecialchars($lokacija); ?></strong></p>
            <?php endif; ?>
        </div>

        <div class="dashboard-grid">
            <a href="lista_vozila.php?status=u_radu<?php echo $lokacija_param; ?>" class="card card-link card-danger">
                <h3>📋 Aktivni poslovi</h3>
                <p class="card-number"><?php echo $u_radu; ?></p>
                <p class="card-description">U toku</p>
            </a>

            <a href="lista_vozila.php?status=zavrseno<?php echo $lokacija_param; ?>" class="card card-link card-warning">
                <h3>✅ Završeni poslovi</h3>
                <p class="card-number"><?php echo $zavrseno; ?></p>
                <p class="card-description">Završeno</p>
            </a>

            <a href="lista_vozila.php?status=placeno<?php echo $lokacija_param; ?>" class="card card-link card-success">
                <h3>💰 Plaćeni poslovi</h3>
                <p class="card-number"><?php echo $placeno; ?></p>
                <p class="card-description">Plaćeno</p>
            </a>

            <a href="lista_vozila.php<?php echo $tip == 'zaposleni' ? '?lokacija=' . urlencode($lokacija_korisnika) : ''; ?>" class="card card-link card-info">
                <h3>🚗 Vozila danas</h3>
                <p class="card-number"><?php echo $vozila_danas; ?></p>
                <p class="card-description">Primljeno danas</p>
            </a>
        </div>

        <?php if ($tip == 'administrator'): ?>
            <!-- LOKACIJE - SAMO ZA ADMINISTRATORA -->
            <div class="locations-section">
                <h2>📍 Naše lokacije</h2>
                <div class="locations-grid">
                    <!-- Ostružnica -->
                    <a href="lista_vozila.php?lokacija=Ostružnica" class="location-card">
                        <div class="location-image">
                            <img src="assets/uploads/slike_za_sajt/ostruznica-dashboard.jpeg"
                                 alt="Ostružnica"
                                 onerror="this.src='assets/uploads/slike_za_sajt/placeholder.png'">
                            <div class="location-overlay">
                                <span class="location-icon">📍</span>
                                <span class="location-text">Pogledaj vozila</span>
                            </div>
                        </div>
                        <div class="location-info">
                            <h3>Ostružnica</h3>
                            <p class="location-address">Miroslava Belovića 13a</p>
                            <?php if (isset($lokacije['Ostružnica'])): ?>
                                <div class="location-stats">
                            <span class="stat-badge stat-danger" title="U radu">
                                🔴 <?php echo $lokacije['Ostružnica']['u_radu']; ?>
                            </span>
                                    <span class="stat-badge stat-warning" title="Završeno">
                                🟡 <?php echo $lokacije['Ostružnica']['zavrseno']; ?>
                            </span>
                                    <span class="stat-badge stat-success" title="Plaćeno">
                                🟢 <?php echo $lokacije['Ostružnica']['placeno']; ?>
                            </span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </a>

                    <!-- Žarkovo -->
                    <a href="lista_vozila.php?lokacija=Žarkovo" class="location-card">
                        <div class="location-image">
                            <img src="assets/uploads/slike_za_sajt/zarkovo-dashboard.jpeg"
                                 alt="Žarkovo"
                                 onerror="this.src='assets/uploads/slike_za_sajt/placeholder.png'">
                            <div class="location-overlay">
                                <span class="location-icon">📍</span>
                                <span class="location-text">Pogledaj vozila</span>
                            </div>
                        </div>
                        <div class="location-info">
                            <h3>Žarkovo</h3>
                            <p class="location-address">Trgovačka 16a</p>
                            <?php if (isset($lokacije['Žarkovo'])): ?>
                                <div class="location-stats">
                            <span class="stat-badge stat-danger" title="U radu">
                                🔴 <?php echo $lokacije['Žarkovo']['u_radu']; ?>
                            </span>
                                    <span class="stat-badge stat-warning" title="Završeno">
                                🟡 <?php echo $lokacije['Žarkovo']['zavrseno']; ?>
                            </span>
                                    <span class="stat-badge stat-success" title="Plaćeno">
                                🟢 <?php echo $lokacije['Žarkovo']['placeno']; ?>
                            </span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </a>

                    <!-- Mirijevo -->
                    <a href="lista_vozila.php?lokacija=Mirijevo" class="location-card">
                        <div class="location-image">
                            <img src="assets/uploads/slike_za_sajt/mirijevo-dashboard.jpg"
                                 alt="Mirijevo"
                                 onerror="this.src='assets/uploads/slike_za_sajt/placeholder.png'">
                            <div class="location-overlay">
                                <span class="location-icon">📍</span>
                                <span class="location-text">Pogledaj vozila</span>
                            </div>
                        </div>
                        <div class="location-info">
                            <h3>Mirijevo</h3>
                            <p class="location-address">Nine Kirsanove 33</p>
                            <?php if (isset($lokacije['Mirijevo'])): ?>
                                <div class="location-stats">
                            <span class="stat-badge stat-danger" title="U radu">
                                🔴 <?php echo $lokacije['Mirijevo']['u_radu']; ?>
                            </span>
                                    <span class="stat-badge stat-warning" title="Završeno">
                                🟡 <?php echo $lokacije['Mirijevo']['zavrseno']; ?>
                            </span>
                                    <span class="stat-badge stat-success" title="Plaćeno">
                                🟢 <?php echo $lokacije['Mirijevo']['placeno']; ?>
                            </span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <div class="quick-actions">
            <h2>Brze akcije</h2>
            <div class="action-buttons">
                <a href="modules/vozila/dodaj.php" class="btn btn-primary">➕ Dodaj vozilo</a>
                <a href="lista_vozila.php" class="btn btn-secondary">📊 Pregledaj sve poslove</a>
                <a href="modules/profil/moj_profil.php" class="btn btn-secondary">👤 Moj profil</a>
                <a href="modules/usluge/lista.php" class="btn btn-secondary">🔧 Usluge</a>
                <?php if ($tip != 'zaposleni'): ?>
                    <a href="modules/korisnici/lista.php" class="btn btn-secondary">👥 Upravljaj korisnicima</a>
                <?php endif; ?>
                <a href="modules/pravna_lica/lista.php" class="btn btn-secondary">🏢 Pravna lica</a>
            </div>
        </div>
    </div>

    <style>
        /* Klikabilne kartice */
        .card-link {
            text-decoration: none;
            color: inherit;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .card-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            transition: left 0.5s;
        }

        .card-link:hover::before {
            left: 100%;
        }

        .card-link:hover {
            transform: translateY(-8px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .card-link:active {
            transform: translateY(-5px);
        }

        /* Boje kartica */
        .card-danger .card-number {
            color: #dc3545;
        }

        .card-warning .card-number {
            color: #ffc107;
        }

        .card-success .card-number {
            color: #28a745;
        }

        .card-info .card-number {
            color: #FF411C;
        }



        /* Hover efekti */
        .card-danger:hover {
            border-left: 4px solid #dc3545;
        }

        .card-warning:hover {
            border-left: 4px solid #ffc107;
        }

        .card-success:hover {
            border-left: 4px solid #28a745;
        }

        .card-info:hover {
            border-left: 4px solid #FF411C;
        }

        /* ============================================
           LOCATIONS SECTION - ADMINISTRATOR ONLY
           ============================================ */
        .locations-section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        .locations-section h2 {
            color: #333;
            margin-bottom: 25px;
            font-size: 24px;
        }

        .locations-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 25px;
        }

        /* Location Card */
        .location-card {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            position: relative;
        }

        .location-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 30px rgba(255, 65, 28, 0.2);
        }

        /* Location Image */
        .location-image {
            position: relative;
            width: 100%;
            height: 220px;
            overflow: hidden;
            background: #f5f7fa;
        }

        .location-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s ease;
        }

        .location-card:hover .location-image img {
            transform: scale(1.08);
        }

        /* Overlay */
        .location-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(180deg, rgba(0,0,0,0) 0%, rgba(255,65,28,0.85) 100%);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .location-card:hover .location-overlay {
            opacity: 1;
        }

        .location-icon {
            font-size: 48px;
            margin-bottom: 10px;
            animation: bounce 1s infinite;
        }

        @keyframes bounce {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .location-text {
            color: white;
            font-size: 18px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Location Info */
        .location-info {
            padding: 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .location-info h3 {
            margin: 0 0 8px 0;
            font-size: 22px;
            color: #FF411C;
            font-weight: 700;
        }

        .location-address {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .location-address::before {
            content: "📍";
            font-size: 12px;
        }

        /* Location Stats */
        .location-stats {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: auto;
        }

        .stat-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            background: #f8f9fa;
            color: #333;
            transition: all 0.3s;
        }

        .stat-badge:hover {
            transform: scale(1.05);
        }

        .stat-danger {
            border: 2px solid #dc3545;
        }

        .stat-warning {
            border: 2px solid #ffc107;
        }

        .stat-success {
            border: 2px solid #28a745;
        }

        /* Mobile responsiveness */
        @media (max-width: 768px) {
            .card-link:hover {
                transform: translateY(-4px);
            }

            .dashboard-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .locations-section {
                padding: 20px;
            }

            .locations-section h2 {
                font-size: 20px;
                margin-bottom: 20px;
            }

            .locations-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }

            .location-image {
                height: 180px;
            }

            .location-info h3 {
                font-size: 20px;
            }

            .stat-badge {
                font-size: 12px;
                padding: 5px 10px;
            }
        }

        @media (max-width: 480px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }

            .locations-section {
                padding: 15px;
            }

            .location-image {
                height: 160px;
            }

            .location-info {
                padding: 15px;
            }

            .location-info h3 {
                font-size: 18px;
            }

            .location-text {
                font-size: 16px;
            }

            .location-icon {
                font-size: 36px;
            }
        }
    </style>

<?php require_once 'includes/footer.php'; ?>