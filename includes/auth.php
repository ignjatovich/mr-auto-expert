<?php
// Provera da li je korisnik ulogovan
function proveri_login() {
    if (!isset($_SESSION['korisnik_id'])) {
        // Sačuvaj originalnu stranicu na koju je pokušao da pristupi
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];

        // Prikaži access denied stranicu
        pokazi_access_denied();
        exit();
    }
}

// Provera tipa korisnika
function proveri_tip($dozvoljeni_tipovi = []) {
    proveri_login();

    if (!in_array($_SESSION['tip_korisnika'], $dozvoljeni_tipovi)) {
        pokazi_access_denied('Nemate dozvolu za pristup ovoj stranici.');
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

// Prikaži access denied stranicu
function pokazi_access_denied($poruka = 'Morate biti ulogovani da biste pristupili ovoj stranici.') {
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
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
                background: url('assets/uploads/slike_za_sajt/background-login.jpg') no-repeat center center/cover;
                position: relative;
                display: flex;
                align-items: center;
                justify-content: center;
                min-height: 100vh;
                padding: 20px;
            }

            body::before {
                content: '';
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                backdrop-filter: blur(3px);
                z-index: 0;
            }

            .access-denied-container {
                position: relative;
                z-index: 1;
                background: white;
                border-radius: 16px;
                padding: 50px 40px;
                max-width: 500px;
                width: 100%;
                text-align: center;
                box-shadow: 0 20px 60px rgba(0, 0, 0, 0.4);
                animation: slideIn 0.5s ease-out;
            }

            @keyframes slideIn {
                from {
                    opacity: 0;
                    transform: translateY(-30px);
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
                25% { transform: rotate(-10deg); }
                75% { transform: rotate(10deg); }
            }

            h1 {
                color: #FF411C;
                font-size: 32px;
                margin-bottom: 15px;
                font-weight: 700;
            }

            p {
                color: #666;
                font-size: 16px;
                line-height: 1.6;
                margin-bottom: 30px;
            }

            .btn {
                padding: 14px 32px;
                border: none;
                border-radius: 8px;
                font-size: 16px;
                font-weight: 600;
                cursor: pointer;
                text-decoration: none;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                transition: all 0.3s ease;
                width: 100%;
                max-width: 300px;
            }

            .btn-primary {
                background: #FF411C;
                color: white;
                box-shadow: 0 4px 15px rgba(255, 65, 28, 0.3);
            }

            .btn-primary:hover {
                background: #E63A19;
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(255, 65, 28, 0.4);
            }

            .btn-primary:active {
                transform: translateY(0);
            }

            .info-box {
                margin-top: 30px;
                padding: 15px;
                background: #f8f9fa;
                border-left: 4px solid #FF411C;
                border-radius: 8px;
                text-align: left;
            }

            .info-box p {
                margin: 0;
                font-size: 14px;
                color: #666;
            }

            .info-box strong {
                color: #333;
            }

            @media (max-width: 480px) {
                .access-denied-container {
                    padding: 40px 25px;
                }

                h1 {
                    font-size: 26px;
                }

                .lock-icon {
                    font-size: 60px;
                }

                .btn {
                    width: 100%;
                }
            }
        </style>
    </head>
    <body>
    <div class="access-denied-container">
        <div class="lock-icon">🔒</div>
        <h1>Pristup odbijen</h1>
        <p><?php echo htmlspecialchars($poruka); ?></p>

        <a href="/mr-auto-expert/login.php" class="btn btn-primary">
            🔑 Prijavite se
        </a>

        <?php if (isset($_SESSION['redirect_after_login'])): ?>
            <div class="info-box">
                <p><strong>💡 Savet:</strong> Nakon prijave, bićete automatski preusmereni na stranicu kojoj ste pokušali da pristupite.</p>
            </div>
        <?php endif; ?>
    </div>
    </body>
    </html>
    <?php
}
?>