<?php
// Provera da li je korisnik ulogovan
function proveri_login() {
    if (!isset($_SESSION['korisnik_id'])) {
        // Sačuvaj URL koji je korisnik pokušao da pristupi
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        pokazi_access_denied();
        exit();
    }
}

// Provera tipa korisnika
function proveri_tip($dozvoljeni_tipovi = []) {
    proveri_login();

    if (!in_array($_SESSION['tip_korisnika'], $dozvoljeni_tipovi)) {
        header('Location: /mr-auto-expert/dashboard.php');
        exit();
    }
}

// Provera da li korisnik može da izmeni drugog korisnika
function moze_izmeniti_korisnika($ciljni_tip) {
    $trenutni_tip = $_SESSION['tip_korisnika'];

    // Administrator može sve
    if ($trenutni_tip == 'administrator') {
        return true;
    }

    // Menadžer može samo zaposlene
    if ($trenutni_tip == 'menadzer' && $ciljni_tip == 'zaposleni') {
        return true;
    }

    return false;
}

// NOVA FUNKCIJA: Dobavi lokacije korisnika
function get_korisnik_lokacije() {
    global $conn;

    $korisnik_id = $_SESSION['korisnik_id'];

    $stmt = $conn->prepare("SELECT sve_lokacije, lokacije, lokacija FROM korisnici WHERE id = ?");
    $stmt->execute([$korisnik_id]);
    $korisnik = $stmt->fetch();

    // Ako ima sve lokacije (administrator)
    if ($korisnik['sve_lokacije']) {
        return ['Ostružnica', 'Žarkovo', 'Mirijevo'];
    }

    // Ako ima JSON lokacije (menadžer sa više lokacija)
    if (!empty($korisnik['lokacije'])) {
        return json_decode($korisnik['lokacije'], true);
    }

    // Fallback na staru pojedinačnu lokaciju (zaposleni)
    if (!empty($korisnik['lokacija'])) {
        return [$korisnik['lokacija']];
    }

    return [];
}

// NOVA FUNKCIJA: Provera da li korisnik ima pristup lokaciji
function ima_pristup_lokaciji($lokacija) {
    $lokacije = get_korisnik_lokacije();
    return in_array($lokacija, $lokacije);
}

// Prikaži access denied stranicu
function pokazi_access_denied() {
    ?>
    <!DOCTYPE html>
    <html lang="sr">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Pristup odbijen - Mr Auto Expert DOO</title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }

            body {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                background: url('assets/uploads/slike_za_sajt/background-login.jpg') no-repeat center center/cover;
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                position: relative;
            }

            body::before {
                content: '';
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                backdrop-filter: blur(4px);
                z-index: -1;
            }

            .access-denied-container {
                background: white;
                border-radius: 16px;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                padding: 50px 40px;
                max-width: 500px;
                width: 90%;
                text-align: center;
                animation: slideDown 0.5s ease;
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

            .lock-icon {
                font-size: 80px;
                margin-bottom: 20px;
                animation: shake 0.5s ease-in-out;
            }

            @keyframes shake {
                0%, 100% { transform: rotate(0deg); }
                25% { transform: rotate(-5deg); }
                50% { transform: rotate(0deg); }
                75% { transform: rotate(5deg); }
            }

            h1 {
                color: #FF411C;
                font-size: 32px;
                margin-bottom: 15px;
            }

            p {
                color: #666;
                font-size: 16px;
                line-height: 1.6;
                margin-bottom: 25px;
            }

            .info-box {
                background: #f8f9fa;
                padding: 15px;
                border-radius: 8px;
                border-left: 4px solid #FF411C;
                margin-bottom: 30px;
                font-size: 14px;
                color: #555;
            }

            .btn-login {
                display: inline-block;
                background: #FF411C;
                color: white;
                padding: 14px 32px;
                border-radius: 8px;
                text-decoration: none;
                font-size: 16px;
                font-weight: 600;
                transition: all 0.3s;
                box-shadow: 0 4px 12px rgba(255, 65, 28, 0.3);
            }

            .btn-login:hover {
                background: #E63A19;
                transform: translateY(-2px);
                box-shadow: 0 6px 16px rgba(255, 65, 28, 0.4);
            }

            @media (max-width: 480px) {
                .access-denied-container {
                    padding: 40px 25px;
                }

                h1 {
                    font-size: 26px;
                }

                .lock-icon {
                    font-size: 64px;
                }
            }
        </style>
    </head>
    <body>
    <div class="access-denied-container">
        <div class="lock-icon">🔒</div>
        <h1>Pristup odbijen</h1>
        <p>Morate biti prijavljeni da biste pristupili ovoj stranici.</p>

        <?php if (isset($_SESSION['redirect_after_login'])): ?>
            <div class="info-box">
                Nakon prijave bićete automatski preusmereni na traženu stranicu.
            </div>
        <?php endif; ?>

        <a href="/mr-auto-expert/login.php" class="btn-login">
            🔑 Prijavite se
        </a>
    </div>
    </body>
    </html>
    <?php
}
?>