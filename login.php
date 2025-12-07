<?php
require_once 'config.php';
require_once 'includes/db.php';

// Ako je korisnik već ulogovan, preusmeri ga na dashboard
if (isset($_SESSION['korisnik_id'])) {
    header('Location: dashboard.php');
    exit();
}

$greska = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $korisnicko_ime = trim($_POST['korisnicko_ime'] ?? '');
    $sifra = $_POST['sifra'] ?? '';

    if (empty($korisnicko_ime) || empty($sifra)) {
        $greska = 'Molimo unesite korisničko ime (ili email) i šifru.';
    } else {
        // Pretraga po korisničkom imenu ili email adresi
        $stmt = $conn->prepare("
            SELECT * FROM korisnici 
            WHERE (korisnicko_ime = ? OR email = ?) 
              AND aktivan = 1
        ");
        $stmt->execute([$korisnicko_ime, $korisnicko_ime]);
        $korisnik = $stmt->fetch();

        if ($korisnik && password_verify($sifra, $korisnik['sifra'])) {
            // Uspešan login
            // Uspešan login
            $_SESSION['korisnik_id'] = $korisnik['id'];
            $_SESSION['korisnicko_ime'] = $korisnik['korisnicko_ime'];
            $_SESSION['ime'] = $korisnik['ime'];
            $_SESSION['prezime'] = $korisnik['prezime'];
            $_SESSION['tip_korisnika'] = $korisnik['tip_korisnika'];
            $_SESSION['lokacija'] = $korisnik['lokacija']; // Backward compatibility

// Dodaj lokacije u sesiju
            if ($korisnik['sve_lokacije']) {
                $_SESSION['lokacije'] = ['Ostružnica', 'Žarkovo', 'Mirijevo'];
                $_SESSION['sve_lokacije'] = true;
            } elseif (!empty($korisnik['lokacije'])) {
                $_SESSION['lokacije'] = json_decode($korisnik['lokacije'], true);
                $_SESSION['sve_lokacije'] = false;
            } else {
                $_SESSION['lokacije'] = [$korisnik['lokacija']];
                $_SESSION['sve_lokacije'] = false;
            }

// Redirect
            if (isset($_SESSION['redirect_after_login'])) {
                $redirect_url = $_SESSION['redirect_after_login'];
                unset($_SESSION['redirect_after_login']);
                header('Location: ' . $redirect_url);
            } else {
                header('Location: dashboard.php');
            }
            exit();

            // NOVO: Proveri da li ima sačuvanu stranicu za redirect
            if (isset($_SESSION['redirect_after_login'])) {
                $redirect_url = $_SESSION['redirect_after_login'];
                unset($_SESSION['redirect_after_login']);
                header('Location: ' . $redirect_url);
            } else {
                header('Location: dashboard.php');
            }
            exit();
        } else {
            $greska = 'Pogrešno korisničko ime/email ili šifra.';
        }
    }
}

?>
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prijava - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/login.css">

</head>

<script>
    function togglePassword() {
        const input = document.getElementById("sifra");
        const icon = document.querySelector(".toggle-password");

        if (input.type === "password") {
            input.type = "text";
            icon.textContent = "Sakrij šifru";
        } else {
            input.type = "password";
            icon.textContent = "Prikaži šifru";
        }
    }
</script>


<body class="login-page">
<div class="login-container">
    <div class="login-box">
        <div class="login-header">
            <h1> MR AUTO EXPERT DOO</h1>
            <p>Prijavite se na sistem</p>
        </div>

        <?php if ($greska): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($greska); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['redirect_after_login'])): ?>
            <div class="alert" style="background: #e7f3ff; color: #0066cc; border-left: 4px solid #0066cc;">
                💡 Prijavite se da biste pristupili stranici koju ste tražili.
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="login-form">
            <div class="form-group">
                <label for="korisnicko_ime">Korisničko ime ili email adresa</label>
                <input
                        type="text"
                        id="korisnicko_ime"
                        name="korisnicko_ime"
                        required
                        autofocus
                        value="<?php echo htmlspecialchars($_POST['korisnicko_ime'] ?? ''); ?>"
                >
            </div>

            <div class="form-group password-group">
                <label for="sifra">Šifra</label>

                <div class="password-wrapper">
                    <input
                            type="password"
                            id="sifra"
                            name="sifra"
                            required
                    >
                    <span class="toggle-password" onclick="togglePassword()">Prikaži šifru</span>
                </div>
            </div>


            <button type="submit" class="btn btn-primary btn-block">
                Prijavi se
            </button>
        </form>
        <div class="login-footer">
            <p>Ako imate poteškoća, kontaktirajte <strong> administratora </strong> ili <strong> menadžera </strong> sistema </p>
        </div>

    </div>
</div>
</body>
</html>