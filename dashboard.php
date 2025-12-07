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

// Dobavi lokacije korisnika
$korisnik_lokacije = $_SESSION['lokacije'] ?? [$_SESSION['lokacija']];

if ($_SESSION['sve_lokacije'] ?? false) {
    // Sve lokacije
    $stmt = $conn->query("
        SELECT 
            status,
            COUNT(*) as broj
        FROM vozila
        GROUP BY status
    ");
} else {
    // Specifične lokacije
    $placeholders = str_repeat('?,', count($korisnik_lokacije) - 1) . '?';
    $stmt = $conn->prepare("
        SELECT 
            status,
            COUNT(*) as broj
        FROM vozila
        WHERE lokacija IN ($placeholders)
        GROUP BY status
    ");
    $stmt->execute($korisnik_lokacije);
}

$stats = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$u_radu = $stats['u_radu'] ?? 0;
$zavrseno = $stats['zavrseno'] ?? 0;
$placeno = $stats['placeno'] ?? 0;

// Ukupno vozila danas
if ($_SESSION['sve_lokacije'] ?? false) {
    $stmt = $conn->query("SELECT COUNT(*) as broj FROM vozila WHERE DATE(datum_prijema) = CURDATE()");
} else {
    $placeholders = str_repeat('?,', count($korisnik_lokacije) - 1) . '?';
    $stmt = $conn->prepare("SELECT COUNT(*) as broj FROM vozila WHERE lokacija IN ($placeholders) AND DATE(datum_prijema) = CURDATE()");
    $stmt->execute($korisnik_lokacije);
}
$vozila_danas = $stmt->fetch()['broj'];

// Link parametri za zaposlene (automatski dodaj lokaciju)
$lokacija_param = ($tip == 'zaposleni') ? '&lokacija=' . urlencode($lokacija_korisnika) : '';

// Statistika po lokacijama - ZA SVE (administrator, menadžer, zaposleni)
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

// Funkcija za proveru da li korisnik ima pristup lokaciji

?>

    <div class="container">
        <div class="welcome-section">
            <h1>Dobrodošli, <?php echo htmlspecialchars($ime); ?>! 👋</h1>
            <?php
            // Prikaži dodeljene lokacije
            $dostupne_lokacije = get_korisnik_lokacije();

            if (isset($_SESSION['sve_lokacije']) && $_SESSION['sve_lokacije']):
                ?>
                <p>Lokacije: 📍<strong>Sve lokacije (Ostružnica, Žarkovo, Mirijevo)</strong></p>
            <?php elseif (count($dostupne_lokacije) > 1): ?>
                <p>Dodeljene lokacije: 📍<strong><?php echo implode(', ', $dostupne_lokacije); ?></strong></p>
            <?php elseif (count($dostupne_lokacije) == 1): ?>
                <p>Lokacija: 📍<strong><?php echo htmlspecialchars($dostupne_lokacije[0]); ?></strong></p>
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

        <!-- LOKACIJE - ZA SVE TIPOVE KORISNIKA -->
        <div class="locations-section">
            <h2>📍 Naše lokacije</h2>
            <div class="locations-grid">
                <!-- Ostružnica -->
                <?php
                $ostruznica_pristup = ima_pristup_lokaciji('Ostružnica');
                $ostruznica_class = $ostruznica_pristup ? 'location-card' : 'location-card location-locked';
                ?>
                <a href="<?php echo $ostruznica_pristup ? 'lista_vozila.php?lokacija=Ostružnica' : 'javascript:void(0)'; ?>"
                   class="<?php echo $ostruznica_class; ?>"
                    <?php if (!$ostruznica_pristup): ?>
                        onclick="pokaziPorukuPristupa('Ostružnica'); return false;"
                    <?php endif; ?>>
                    <div class="location-image">
                        <img src="assets/uploads/slike_za_sajt/ostruznica-dashboard.jpeg"
                             alt="Ostružnica"
                             onerror="this.src='assets/uploads/slike_za_sajt/placeholder.png'">
                        <?php if ($ostruznica_pristup): ?>
                            <div class="location-overlay">
                                <span class="location-icon">📍</span>
                                <span class="location-text">Pogledaj vozila</span>
                            </div>
                        <?php else: ?>
                            <div class="location-locked-overlay">
                                <span class="lock-icon">🔒</span>
                                <span class="lock-text">Nema pristupa</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="location-info">
                        <h3>Ostružnica</h3>
                        <p class="location-address">Miroslava Belovića 13a</p>
                        <?php if (isset($lokacije['Ostružnica']) && $ostruznica_pristup): ?>
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
                <?php
                $zarkovo_pristup = ima_pristup_lokaciji('Žarkovo');
                $zarkovo_class = $zarkovo_pristup ? 'location-card' : 'location-card location-locked';
                ?>
                <a href="<?php echo $zarkovo_pristup ? 'lista_vozila.php?lokacija=Žarkovo' : 'javascript:void(0)'; ?>"
                   class="<?php echo $zarkovo_class; ?>"
                    <?php if (!$zarkovo_pristup): ?>
                        onclick="pokaziPorukuPristupa('Žarkovo'); return false;"
                    <?php endif; ?>>
                    <div class="location-image">
                        <img src="assets/uploads/slike_za_sajt/zarkovo-dashboard.jpeg"
                             alt="Žarkovo"
                             onerror="this.src='assets/uploads/slike_za_sajt/placeholder.png'">
                        <?php if ($zarkovo_pristup): ?>
                            <div class="location-overlay">
                                <span class="location-icon">📍</span>
                                <span class="location-text">Pogledaj vozila</span>
                            </div>
                        <?php else: ?>
                            <div class="location-locked-overlay">
                                <span class="lock-icon">🔒</span>
                                <span class="lock-text">Nema pristupa</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="location-info">
                        <h3>Žarkovo</h3>
                        <p class="location-address">Trgovačka 16a</p>
                        <?php if (isset($lokacije['Žarkovo']) && $zarkovo_pristup): ?>

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
                <?php
                $mirijevo_pristup = ima_pristup_lokaciji('Mirijevo');
                $mirijevo_class = $mirijevo_pristup ? 'location-card' : 'location-card location-locked';
                ?>
                <a href="<?php echo $mirijevo_pristup ? 'lista_vozila.php?lokacija=Mirijevo' : 'javascript:void(0)'; ?>"
                   class="<?php echo $mirijevo_class; ?>"
                    <?php if (!$mirijevo_pristup): ?>
                        onclick="pokaziPorukuPristupa('Mirijevo'); return false;"
                    <?php endif; ?>>
                    <div class="location-image">
                        <img src="assets/uploads/slike_za_sajt/mirijevo-dashboard.jpg"
                             alt="Mirijevo"
                             onerror="this.src='assets/uploads/slike_za_sajt/placeholder.png'">
                        <?php if ($mirijevo_pristup): ?>
                            <div class="location-overlay">
                                <span class="location-icon">📍</span>
                                <span class="location-text">Pogledaj vozila</span>
                            </div>
                        <?php else: ?>
                            <div class="location-locked-overlay">
                                <span class="lock-icon">🔒</span>
                                <span class="lock-text">Nema pristupa</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="location-info">
                        <h3>Mirijevo</h3>
                        <p class="location-address">Nine Kirsanove 33</p>
                        <?php if (isset($lokacije['Mirijevo']) && $mirijevo_pristup): ?>
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

                <!-- NOVO DUGME -->
                <button onclick="openUputstva()" class="btn btn-secondary uputstvo-dugme">📖 Uputstva</button>
            </div>
        </div>
    </div>

    <!-- Modal za poruku o pristupu -->
    <div id="accessModal" class="access-modal">
        <div class="access-modal-content">
            <span class="access-modal-close" onclick="zatvoriModal()">&times;</span>
            <div class="access-modal-icon">🔒</div>
            <h2>Nema dozvole za pristup</h2>
            <p id="accessModalMessage">Nemate pristup lokaciji <strong id="lokacijaNaziv"></strong>.</p>
            <button onclick="zatvoriModal()" class="btn-modal-ok">U redu</button>
        </div>
    </div>

    <!-- MODAL ZA UPUTSTVA -->
    <div id="uputstva-modal" class="uputstva-modal">
        <div class="uputstva-container">
            <div class="uputstva-header">
                <h2>📖 Uputstvo za korišćenje</h2>
                <button class="uputstva-close" onclick="closeUputstva()">✕</button>
            </div>
            <div class="uputstva-content">
                <?php if ($tip == 'administrator'): ?>
                    <!-- ADMINISTRATOR UPUTSTVA -->
                    <h3>🔐 Administrator - Potpuna kontrola sistema</h3>

                    <div class="uputstvo-sekcija">
                        <h4>📊 Pregled sistema</h4>
                        <p>Kao administrator imate <strong>potpuni pristup</strong> svim funkcijama aplikacije za sve tri lokacije (Ostružnica, Žarkovo, Mirijevo).</p>
                    </div>

                    <div class="uputstvo-sekcija">
                        <h4>👥 Upravljanje korisnicima</h4>
                        <ul>
                            <li><strong>Dodavanje korisnika:</strong> Možete kreirati administratore, menadžere i zaposlene</li>
                            <li><strong>Dodela lokacija:</strong> Kod kreiranja menadžera/zaposlenih, dodelite im jednu ili više lokacija</li>
                            <li><strong>Izmena korisnika:</strong> Možete menjati sve podatke, tip korisnika, lokacije i šifre</li>
                            <li><strong>Brisanje:</strong> Možete obrisati korisnike koji nemaju vezanih vozila</li>
                            <li><strong>Aktivacija/deaktivacija:</strong> Kontrolišite ko može da se prijavi na sistem</li>
                        </ul>
                    </div>

                    <div class="uputstvo-sekcija">
                        <h4>🚗 Upravljanje vozilima</h4>
                        <ul>
                            <li><strong>Dodavanje vozila:</strong> Možete dodati vozilo za bilo koju lokaciju</li>
                            <li><strong>Izbor lokacije:</strong> Prilikom dodavanja, birajte za koju lokaciju dodajete vozilo</li>
                            <li><strong>Izmena:</strong> Možete izmeniti SVA vozila sa svih lokacija</li>
                            <li><strong>Promena lokacije:</strong> Možete premestiti vozilo sa jedne lokacije na drugu</li>
                            <li><strong>Brisanje:</strong> Možete obrisati bilo koje vozilo</li>
                            <li><strong>Statusi:</strong> U radu (🔴), Završeno (🟡), Plaćeno (🟢)</li>
                        </ul>
                    </div>

                    <div class="uputstvo-sekcija">
                        <h4>🔧 Usluge</h4>
                        <ul>
                            <li>Dodajte standardne usluge koje se nude na svim lokacijama</li>
                            <li>Postavite cene usluga</li>
                            <li>Aktivirajte/deaktivirajte usluge po potrebi</li>
                            <li>Custom usluge se dodaju direktno na vozilu</li>
                        </ul>
                    </div>

                    <div class="uputstvo-sekcija">
                        <h4>🏢 Pravna lica</h4>
                        <ul>
                            <li>Kreirajte firme koje redovno koriste usluge</li>
                            <li>Čuvajte PIB, kontakt telefon, email i adresu</li>
                            <li>Prilikom dodavanja vozila, birajte između fizičkog i pravnog lica</li>
                        </ul>
                    </div>

                    <div class="uputstvo-sekcija">
                        <h4>⚙️ Tipovi korisnika</h4>
                        <p><strong>Administrator:</strong> Vi - potpuna kontrola</p>
                        <p><strong>Menadžer:</strong> Može upravljati zaposlenima, vozilima i uslugama. Vidi samo dodeljene lokacije. Ne može menjati administratore ili druge menadžere.</p>
                        <p><strong>Zaposleni:</strong> Može dodavati/menjati vozila samo za svoju lokaciju. Nema pristup upravljanju korisnicima.</p>
                    </div>

                <?php elseif ($tip == 'menadzer'): ?>
                    <!-- MENADŽER UPUTSTVA -->
                    <h3>👔 Menadžer - Upravljanje zaposlenima i vozilima</h3>

                    <div class="uputstvo-sekcija">
                        <h4>📊 Vaš pristup</h4>
                        <p>Kao menadžer imate pristup <strong>dodeljenim lokacijama</strong>:</p>
                        <p><strong><?php echo implode(', ', get_korisnik_lokacije()); ?></strong></p>
                        <p>Vidite i upravljate vozilima samo sa ovih lokacija.</p>
                    </div>

                    <div class="uputstvo-sekcija">
                        <h4>👥 Upravljanje zaposlenima</h4>
                        <ul>
                            <li><strong>Dodavanje zaposlenih:</strong> Možete kreirati nove zaposlene za vaše lokacije</li>
                            <li><strong>Dodela lokacije:</strong> Odredite na kojoj lokaciji zaposleni radi</li>
                            <li><strong>Izmena podataka:</strong> Možete menjati podatke zaposlenih (ime, email, telefon, lokaciju)</li>
                            <li><strong>Promena šifre:</strong> Možete resetovati šifre zaposlenima</li>
                            <li><strong>Brisanje:</strong> Možete obrisati zaposlene koji nemaju vezanih vozila</li>
                            <li><strong>⚠️ NE možete:</strong> Menjati administratore ili druge menadžere</li>
                        </ul>
                    </div>

                    <div class="uputstvo-sekcija">
                        <h4>🚗 Upravljanje vozilima</h4>
                        <ul>
                            <li><strong>Dodavanje vozila:</strong> Dodajete vozila za svoje dodeljene lokacije</li>
                            <li><strong>Izbor lokacije:</strong> Birajte iz dropdown menija za koju lokaciju dodajete vozilo</li>
                            <li><strong>Izmena:</strong> Možete izmeniti sva vozila sa vaših lokacija</li>
                            <li><strong>Promena lokacije:</strong> Možete premestiti vozilo između vaših dodeljenih lokacija</li>
                            <li><strong>Brisanje:</strong> Možete obrisati vozila sa vaših lokacija</li>
                            <li><strong>Promjena statusa:</strong> U radu → Završeno → Plaćeno</li>
                        </ul>
                    </div>

                    <div class="uputstvo-sekcija">
                        <h4>📋 Korak po korak - Dodavanje vozila</h4>
                        <ol>
                            <li>Kliknite "➕ Dodaj vozilo"</li>
                            <li><strong>Izaberite lokaciju</strong> vozila iz dropdown menija</li>
                            <li>Odaberite tip klijenta (fizičko ili pravno lice)</li>
                            <li>Unesite registraciju, marku, kontakt</li>
                            <li>Uslikajte vozilo ili upload-ujte sliku</li>
                            <li>Izaberite parking poziciju (Silos, Balon, Veliki parking)</li>
                            <li>Štiklirajte potrebne usluge</li>
                            <li>Dodajte custom usluge ako je potrebno</li>
                            <li>Cena se računa automatski</li>
                            <li>Kliknite "Dodaj vozilo"</li>
                        </ol>
                    </div>

                    <div class="uputstvo-sekcija">
                        <h4>🔧 Usluge</h4>
                        <ul>
                            <li>Možete dodavati i menjati standardne usluge</li>
                            <li>Postavite cene usluga</li>
                            <li>Aktivirajte/deaktivirajte usluge</li>
                        </ul>
                    </div>

                    <div class="uputstvo-sekcija">
                        <h4>🏢 Pravna lica</h4>
                        <ul>
                            <li>Dodajte firme koje redovno koriste usluge</li>
                            <li>Čuvajte kontakt podatke firmi</li>
                            <li>Prilikom dodavanja vozila, birajte pravno lice umesto fizičkog</li>
                        </ul>
                    </div>

                <?php else: // Zaposleni ?>
                    <!-- ZAPOSLENI UPUTSTVA -->
                    <h3>👷 Zaposleni - Rad sa vozilima</h3>

                    <div class="uputstvo-sekcija">
                        <h4>📊 Vaš pristup</h4>
                        <p>Kao zaposleni imate pristup <strong>samo svojoj lokaciji</strong>:</p>
                        <p><strong>📍 <?php echo implode(', ', get_korisnik_lokacije()); ?></strong></p>
                        <p>Vidite i upravljate vozilima samo sa ove lokacije.</p>
                    </div>

                    <div class="uputstvo-sekcija">
                        <h4>📋 Korak po korak - Dodavanje vozila</h4>
                        <ol>
                            <li>Kliknite na <strong>"➕ Dodaj vozilo"</strong></li>
                            <li><strong>Lokacija je automatski postavljena</strong> na vašu lokaciju (<?php echo get_korisnik_lokacije()[0]; ?>)</li>
                            <li><strong>Tip klijenta:</strong> Kliknite na "👤 Fizičko lice" ili "🏢 Pravno lice"</li>
                            <li>Ako je <strong>fizičko lice:</strong> Unesite ime i prezime vlasnika</li>
                            <li>Ako je <strong>pravno lice:</strong> Počnite kucati naziv firme i izaberite iz liste</li>
                        </ol>
                    </div>

                    <div class="uputstvo-sekcija">
                        <h4>🚗 Unos podataka o vozilu</h4>
                        <ol>
                            <li><strong>Registarska oznaka:</strong> npr. BG-123-AB (obavezno)</li>
                            <li><strong>Broj šasije (VIN):</strong> npr. WBA12345678901234 (opciono)</li>
                            <li><strong>Marka vozila:</strong> npr. BMW X5 (obavezno)</li>
                            <li><strong>Kontakt telefon:</strong> npr. 061 123 4567</li>
                        </ol>
                    </div>

                    <div class="uputstvo-sekcija">
                        <h4>📷 Slikanje vozila</h4>
                        <p>Imate <strong>2 opcije</strong> za dodavanje slike:</p>
                        <ol>
                            <li><strong>"📷 Uslikaj kamerom":</strong>
                                <ul>
                                    <li>Kliknite na dugme</li>
                                    <li>Dozvolite pristup kameri</li>
                                    <li>Usmerite kameru na vozilo</li>
                                    <li>Kliknite "Uslikaj"</li>
                                    <li>Slika se automatski dodaje</li>
                                </ul>
                            </li>
                            <li><strong>"📁 Upload sa uređaja":</strong>
                                <ul>
                                    <li>Kliknite na dugme</li>
                                    <li>Izaberite sliku iz galerije</li>
                                    <li>Slika se automatski dodaje</li>
                                </ul>
                            </li>
                        </ol>
                    </div>

                    <div class="uputstvo-sekcija">
                        <h4>🅿️ Parking lokacija</h4>
                        <p>Izaberite gde je vozilo parkirano:</p>
                        <ul>
                            <li><strong>Silos</strong></li>
                            <li><strong>Balon parking</strong></li>
                            <li><strong>Veliki parking</strong></li>
                        </ul>
                    </div>

                    <div class="uputstvo-sekcija">
                        <h4>🔧 Izbor usluga</h4>
                        <p><strong>Standardne usluge:</strong> Štiklirajte sve potrebne usluge</p>
                        <ul>
                            <li>Tehnički pregled</li>
                            <li>Registracija vozila</li>
                            <li>Carina</li>
                            <li>Ugradnja tahografa</li>
                            <li>Ispitivanje vozila</li>
                            <li>Reatest TNG/KPG</li>
                            <li>Utiskivanje identifikacionih oznaka</li>
                            <li>Izdavanje probnih tablica</li>
                        </ul>

                        <p style="margin-top: 15px;"><strong>Custom usluge (dodatne):</strong></p>
                        <ul>
                            <li>Unesite naziv custom usluge (npr. "Popravka haube")</li>
                            <li>Unesite cenu</li>
                            <li>Možete dodati više custom usluga klikom na "➕ Dodaj još jednu"</li>
                        </ul>

                        <p style="margin-top: 15px;"><strong>💰 Cena se automatski računa!</strong></p>
                    </div>

                    <div class="uputstvo-sekcija">
                        <h4>📝 Napomena (opciono)</h4>
                        <p>Unesite bilo kakve dodatne informacije o vozilu ili poslu:</p>
                        <ul>
                            <li>Posebne napomene vlasnika</li>
                            <li>Hitnost posla</li>
                            <li>Uočeni problemi</li>
                        </ul>
                    </div>

                    <div class="uputstvo-sekcija">
                        <h4>📊 Promjena statusa vozila</h4>
                        <p>Nakon dodavanja, vozilo je automatski u statusu <strong>🔴 U radu</strong></p>
                        <ol>
                            <li>Kliknite na vozilo da vidite detalje</li>
                            <li>U sekciji "Status vozila" možete promeniti status:
                                <ul>
                                    <li><strong>🔴 U radu:</strong> Posao je u toku</li>
                                    <li><strong>🟡 Završeno:</strong> Posao je gotov, čeka se plaćanje</li>
                                    <li><strong>🟢 Plaćeno:</strong> Posao je završen i plaćen</li>
                                </ul>
                            </li>
                        </ol>
                    </div>

                    <div class="uputstvo-sekcija">
                        <h4>✏️ Izmena vozila</h4>
                        <ul>
                            <li>Možete izmeniti <strong>sva vozila sa vaše lokacije</strong></li>
                            <li>Kliknite "Vidi detalje" pa "✏️ Izmeni"</li>
                            <li>Izmenite potrebne podatke</li>
                            <li><strong>NE možete promeniti lokaciju vozila</strong> (to mogu samo menadžeri i administratori)</li>
                            <li>Kliknite "Sačuvaj izmene"</li>
                        </ul>
                    </div>

                    <div class="uputstvo-sekcija">
                        <h4>🗑️ Brisanje vozila</h4>
                        <ul>
                            <li>Možete obrisati <strong>samo vozila koja ste Vi dodali</strong></li>
                            <li>Kliknite "Vidi detalje" pa "🗑️ Obriši"</li>
                            <li>Potvrdite brisanje</li>
                            <li><strong>⚠️ Brisanje se ne može poništiti!</strong></li>
                        </ul>
                    </div>

                    <div class="uputstvo-sekcija">
                        <h4>🏢 Pravna lica</h4>
                        <ul>
                            <li>Možete dodavati nove firme u bazu</li>
                            <li>Unesite naziv firme, PIB, kontakt telefon</li>
                            <li>Kada dodajete vozilo, birajte pravno lice umesto fizičkog</li>
                        </ul>
                    </div>

                    <div class="uputstvo-sekcija">
                        <h4>💡 Saveti</h4>
                        <ul>
                            <li><strong>Uvek slikajte vozilo</strong> - dokaz stanja pri prijemu</li>
                            <li><strong>Proverite registraciju</strong> - mora biti tačna</li>
                            <li><strong>Unesite tačan kontakt telefon</strong> - da možete nazvati vlasnika</li>
                            <li><strong>Birajte tačnu parking poziciju</strong> - lakše ćete naći vozilo</li>
                            <li><strong>Redovno menjajte status</strong> - svi znaju gde je posao</li>
                        </ul>
                    </div>

                <?php endif; ?>

                <div class="uputstvo-sekcija" style="background: #e7f3ff; border-left: 4px solid #0066cc; padding: 15px; margin-top: 20px;">
                    <h4>❓ Imate pitanja?</h4>
                    <p>Kontaktirajte svog administratora ili menadžera za dodatnu pomoć.</p>
                </div>
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

        .uputstvo-dugme {
            background-color: #FF411C !important;
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
        .card-danger .card-number { color: #dc3545; }
        .card-warning .card-number { color: #ffc107; }
        .card-success .card-number { color: #28a745; }
        .card-info .card-number { color: #FF411C; }

        .card-link:hover .card-number {
            transform: scale(1.1);
            transition: transform 0.3s;
        }

        /* Hover efekti */
        .card-danger:hover { border-left: 4px solid #dc3545; }
        .card-warning:hover { border-left: 4px solid #ffc107; }
        .card-success:hover { border-left: 4px solid #28a745; }
        .card-info:hover { border-left: 4px solid #FF411C; }

        /* LOCATIONS SECTION */
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

        /* LOCKED Location Card */
        .location-locked {
            opacity: 0.7;
            cursor: not-allowed;
            filter: grayscale(0.5);
        }

        .location-locked:hover {
            transform: translateY(-4px);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
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

        .location-card:not(.location-locked):hover .location-image img {
            transform: scale(1.08);
        }

        /* Normal Overlay */
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

        .location-card:not(.location-locked):hover .location-overlay {
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

        /* LOCKED Overlay */
        .location-locked-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
        }

        .lock-icon {
            font-size: 48px;
            margin-bottom: 10px;
            animation: shake 1s infinite;
        }

        @keyframes shake {
            0%, 100% { transform: rotate(0deg); }
            25% { transform: rotate(-5deg); }
            75% { transform: rotate(5deg); }
        }

        .lock-text {
            color: white;
            font-size: 16px;
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

        .location-locked .location-info h3 {
            color: #999;
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

        .stat-danger { border: 2px solid #dc3545; }
        .stat-warning { border: 2px solid #ffc107; }
        .stat-success { border: 2px solid #28a745; }

        /* ACCESS MODAL */
        .access-modal {
            display: none;
            position: fixed;
            z-index: 9999;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(4px);
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .access-modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 40px;
            border-radius: 16px;
            max-width: 450px;
            width: 90%;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideDown 0.4s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-50px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .access-modal-close {
            color: #aaa;
            float: right;
            font-size: 32px;
            font-weight: bold;
            line-height: 1;
            cursor: pointer;
            transition: color 0.3s;
        }

        .access-modal-close:hover {
            color: #FF411C;
        }

        .access-modal-icon {
            font-size: 64px;
            margin-bottom: 20px;
            animation: shake 0.5s ease-in-out;
        }

        .access-modal-content h2 {
            color: #FF411C;
            margin-bottom: 15px;
            font-size: 26px;
        }

        .access-modal-content p {
            color: #666;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .access-modal-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            border-left: 4px solid #FF411C;
            margin-bottom: 25px;
        }

        .btn-modal-ok {
            background: #FF411C;
            color: white;
            border: none;
            padding: 12px 32px;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(255, 65, 28, 0.3);
        }

        .btn-modal-ok:hover {
            background: #E63A19;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(255, 65, 28, 0.4);
        }

        /* Mobile responsiveness */
        @media (max-width: 768px) {
            .locations-section { padding: 20px; }
            .locations-section h2 { font-size: 20px; margin-bottom: 20px; }
            .locations-grid { grid-template-columns: 1fr; gap: 20px; }
            .location-image { height: 180px; }
            .location-info h3 { font-size: 20px; }
            .stat-badge { font-size: 12px; padding: 5px 10px; }
            .access-modal-content { margin: 20% auto; padding: 30px; }
        }

        @media (max-width: 480px) {
            .locations-section { padding: 15px; }
            .location-image { height: 160px; }
            .location-info { padding: 15px; }
            .location-info h3 { font-size: 18px; }
            .location-text { font-size: 16px; }
            .location-icon, .lock-icon { font-size: 36px; }
            .access-modal-content { padding: 25px; }
            .access-modal-icon { font-size: 48px; }
        }

        /* UPUTSTVA MODAL */
        .uputstva-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            z-index: 10000;
            overflow-y: auto;
            padding: 20px;
        }

        .uputstva-modal.active {
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding-top: 40px;
            padding-bottom: 40px;
        }

        .uputstva-container {
            background: white;
            border-radius: 16px;
            max-width: 900px;
            width: 100%;
            max-height: 85vh;
            display: flex;
            flex-direction: column;
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.3);
            animation: slideDown 0.3s ease;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .uputstva-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 25px 30px;
            background: linear-gradient(135deg, #FF411C 0%, #E63A19 100%);
            color: white;
            border-radius: 16px 16px 0 0;
        }

        .uputstva-header h2 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }

        .uputstva-close {
            background: rgba(255, 255, 255, 0.2);
            border: none;
            color: white;
            font-size: 28px;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
        }

        .uputstva-close:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg);
        }

        .uputstva-content {
            padding: 30px;
            overflow-y: auto;
            flex: 1;
        }

        .uputstva-content h3 {
            color: #FF411C;
            font-size: 22px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #FFE5E0;
        }

        .uputstva-content h4 {
            color: #333;
            font-size: 18px;
            margin-top: 20px;
            margin-bottom: 12px;
        }

        .uputstvo-sekcija {
            margin-bottom: 25px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 12px;
            border-left: 4px solid #FF411C;
        }

        .uputstvo-sekcija ul {
            margin: 10px 0;
            padding-left: 25px;
        }

        .uputstvo-sekcija li {
            margin-bottom: 8px;
            line-height: 1.6;
        }

        .uputstvo-sekcija ol {
            margin: 10px 0;
            padding-left: 25px;
        }

        .uputstvo-sekcija ol li {
            margin-bottom: 10px;
            line-height: 1.6;
        }

        .uputstvo-sekcija p {
            margin: 10px 0;
            line-height: 1.6;
        }

        .uputstvo-sekcija strong {
            color: #FF411C;
        }

        /* Scrollbar styling */
        .uputstva-content::-webkit-scrollbar {
            width: 10px;
        }

        .uputstva-content::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .uputstva-content::-webkit-scrollbar-thumb {
            background: #FF411C;
            border-radius: 10px;
        }

        .uputstva-content::-webkit-scrollbar-thumb:hover {
            background: #E63A19;
        }

        /* Mobile responsive */
        @media (max-width: 768px) {
            .uputstva-modal {
                padding: 10px;
            }

            .uputstva-modal.active {
                padding-top: 20px;
                padding-bottom: 20px;
            }

            .uputstva-container {
                max-height: 90vh;
            }

            .uputstva-header {
                padding: 20px;
            }

            .uputstva-header h2 {
                font-size: 20px;
            }

            .uputstva-content {
                padding: 20px;
            }

            .uputstva-content h3 {
                font-size: 19px;
            }

            .uputstva-content h4 {
                font-size: 16px;
            }

            .uputstvo-sekcija {
                padding: 15px;
            }
        }
    </style>

    <script>
        // Prikaži modal sa porukom o pristupu
        function pokaziPorukuPristupa(lokacija) {
            document.getElementById('lokacijaNaziv').textContent = lokacija;
            document.getElementById('accessModal').style.display = 'block';
            document.body.style.overflow = 'hidden';
        }

        // Zatvori modal
        function zatvoriModal() {
            document.getElementById('accessModal').style.display = 'none';
            document.body.style.overflow = 'auto';
        }

        // Zatvori modal klikom na overlay
        window.onclick = function(event) {
            const modal = document.getElementById('accessModal');
            if (event.target == modal) {
                zatvoriModal();
            }
        }

        // Zatvori modal sa ESC tasterom
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                zatvoriModal();
            }
        });

        function openUputstva() {
            document.getElementById('uputstva-modal').classList.add('active');
            document.body.style.overflow = 'hidden'; // Prevent body scroll
        }

        function closeUputstva() {
            document.getElementById('uputstva-modal').classList.remove('active');
            document.body.style.overflow = 'auto'; // Re-enable body scroll
        }

        // Close on ESC key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeUputstva();
            }
        });

        // Close on backdrop click
        document.getElementById('uputstva-modal')?.addEventListener('click', function(e) {
            if (e.target === this) {
                closeUputstva();
            }
        });
    </script>

<?php require_once 'includes/footer.php'; ?>