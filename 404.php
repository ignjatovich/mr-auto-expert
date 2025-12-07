<?php
session_start();
define('SITE_NAME', 'MR AUTO EXPERT DOO');
?>
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Stranica ne postoji | <?php echo SITE_NAME; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #FF411C 0%, #E63A19 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .error-container {
            background: white;
            border-radius: 20px;
            padding: 60px 40px;
            max-width: 600px;
            width: 100%;
            text-align: center;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideIn 0.5s ease;
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

        .error-icon {
            font-size: 120px;
            margin-bottom: 20px;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
        }

        .error-code {
            font-size: 80px;
            font-weight: bold;
            color: #FF411C;
            margin-bottom: 10px;
            line-height: 1;
        }

        .error-title {
            font-size: 28px;
            color: #333;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .error-message {
            font-size: 16px;
            color: #666;
            margin-bottom: 40px;
            line-height: 1.6;
        }

        .btn-home {
            display: inline-block;
            padding: 15px 40px;
            background: linear-gradient(135deg, #FF411C 0%, #E63A19 100%);
            color: white;
            text-decoration: none;
            border-radius: 50px;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(255, 65, 28, 0.3);
        }

        .btn-home:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 25px rgba(255, 65, 28, 0.4);
        }

        .btn-home:active {
            transform: translateY(-1px);
        }

        .error-details {
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid #e1e8ed;
            font-size: 14px;
            color: #999;
        }

        /* Mobile responsive */
        @media (max-width: 768px) {
            .error-container {
                padding: 40px 30px;
            }

            .error-icon {
                font-size: 80px;
            }

            .error-code {
                font-size: 60px;
            }

            .error-title {
                font-size: 22px;
            }

            .error-message {
                font-size: 15px;
            }

            .btn-home {
                padding: 12px 30px;
                font-size: 15px;
            }
        }

        @media (max-width: 480px) {
            .error-container {
                padding: 30px 20px;
            }

            .error-icon {
                font-size: 60px;
            }

            .error-code {
                font-size: 50px;
            }

            .error-title {
                font-size: 20px;
            }

            .error-message {
                font-size: 14px;
                margin-bottom: 30px;
            }

            .btn-home {
                width: 100%;
                padding: 14px 30px;
            }
        }
    </style>
</head>
<body>
<div class="error-container">
    <div class="error-icon">🚗💨</div>
    <div class="error-code">404</div>
    <h1 class="error-title">Željena stranica ne postoji</h1>
    <p class="error-message">
        Stranica koju tražite nije pronađena ili je uklonjena.<br>
        Proverite URL adresu ili se vratite na početnu stranicu.
    </p>

    <?php if (isset($_SESSION['korisnik_id'])): ?>
        <a href="/mr-auto-expert/dashboard.php" class="btn-home">
            🏠 Nazad na Dashboard
        </a>
    <?php else: ?>
        <a href="/mr-auto-expert/login.php" class="btn-home">
            🔐 Nazad na Prijavu
        </a>
    <?php endif; ?>

    <div class="error-details">
        <strong>MR AUTO EXPERT DOO</strong><br>
        Tehnički pregled vozila | Ostružnica • Žarkovo • Mirijevo
    </div>
</div>
</body>
</html>