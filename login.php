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
            $_SESSION['korisnik_id'] = $korisnik['id'];
            $_SESSION['korisnicko_ime'] = $korisnik['korisnicko_ime'];
            $_SESSION['ime'] = $korisnik['ime'];
            $_SESSION['prezime'] = $korisnik['prezime'];
            $_SESSION['tip_korisnika'] = $korisnik['tip_korisnika'];
            $_SESSION['lokacija'] = $korisnik['lokacija'];

            header('Location: dashboard.php');
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
<body class="login-page">
<div class="login-container">
    <div class="login-box">
        <div class="login-header">
            <h1>🚗 Mr Auto Expert DOO</h1>
            <p>Prijavite se na sistem</p>
        </div>

        <?php if ($greska): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($greska); ?>
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

            <div class="form-group">
                <label for="sifra">Šifra</label>
                <input
                    type="password"
                    id="sifra"
                    name="sifra"
                    required
                >
            </div>

            <button type="submit" class="btn btn-primary btn-block">
                Prijavi se
            </button>
        </form>

        <div class="login-footer">
            <p><strong></strong> / <strong></strong></p>
        </div>
    </div>
</div>
</body>
</html>