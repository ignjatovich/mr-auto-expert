<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/db.php';

class Migrator {
    private $conn;
    private $migrations_path;

    public function __construct($conn) {
        $this->conn = $conn;
        $this->migrations_path = __DIR__ . '/migrations/';
    }

    // Pokreni sve migracije koje nisu izvršene
    public function migrate() {
        echo "🚀 Pokrećem migracije...\n\n";

        $migration_files = $this->getMigrationFiles();
        $executed = $this->getExecutedMigrations();

        $pending = array_diff($migration_files, $executed);

        if (empty($pending)) {
            echo "✅ Sve migracije su već izvršene!\n";
            return;
        }

        foreach ($pending as $migration) {
            $this->runMigration($migration, 'up');
        }

        echo "\n✅ Sve migracije su uspešno izvršene!\n";
    }

    // Vrati poslednju migraciju (rollback)
    public function rollback($steps = 1) {
        echo "⏪ Vraćam migracije unazad...\n\n";

        $executed = $this->getExecutedMigrations();

        if (empty($executed)) {
            echo "⚠️ Nema migracija za vraćanje!\n";
            return;
        }

        $to_rollback = array_slice(array_reverse($executed), 0, $steps);

        foreach ($to_rollback as $migration) {
            $this->runMigration($migration, 'down');
        }

        echo "\n✅ Rollback uspešno izvršen!\n";
    }

    // Resetuj sve migracije (drop sve pa ponovo kreiraj)
    public function reset() {
        echo "🔄 Resetujem bazu podataka...\n\n";

        $executed = $this->getExecutedMigrations();

        foreach (array_reverse($executed) as $migration) {
            $this->runMigration($migration, 'down');
        }

        echo "\n✅ Baza resetovana! Sada pokreni migrate() da kreiraš sve tabele.\n";
    }

    // Status migracija
    public function status() {
        echo "📊 Status migracija:\n\n";

        $all = $this->getMigrationFiles();
        $executed = $this->getExecutedMigrations();

        foreach ($all as $migration) {
            $status = in_array($migration, $executed) ? '✅ Izvršena' : '⏳ Na čekanju';
            echo "$status - $migration\n";
        }
    }

    // Privatne helper metode
    private function getMigrationFiles() {
        $files = scandir($this->migrations_path);
        $migrations = [];

        foreach ($files as $file) {
            if (preg_match('/^\d{3}_.*\.php$/', $file)) {
                $migrations[] = $file;
            }
        }

        sort($migrations);
        return $migrations;
    }

    private function getExecutedMigrations() {
        try {
            $stmt = $this->conn->query("SELECT migration FROM migrations ORDER BY id");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (PDOException $e) {
            // Ako tabela migrations ne postoji, vrati prazan niz
            return [];
        }
    }

    private function runMigration($migration, $direction) {
        $filepath = $this->migrations_path . $migration;

        if (!file_exists($filepath)) {
            echo "❌ Fajl ne postoji: $migration\n";
            return;
        }

        $sql_array = require $filepath;
        $sql = $sql_array[$direction];

        try {
            $this->conn->beginTransaction();

            // Izvršavanje SQL-a
            $this->conn->exec($sql);

            // Ažuriranje migrations tabele
            if ($direction == 'up') {
                $stmt = $this->conn->prepare("INSERT INTO migrations (migration) VALUES (?)");
                $stmt->execute([$migration]);
                echo "✅ Izvršena: $migration\n";
            } else {
                $stmt = $this->conn->prepare("DELETE FROM migrations WHERE migration = ?");
                $stmt->execute([$migration]);
                echo "⏪ Vraćena: $migration\n";
            }

            $this->conn->commit();

        } catch (PDOException $e) {
            // Proveri da li je transakcija aktivna pre rollback-a
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            echo "❌ Greška u migraciji $migration: " . $e->getMessage() . "\n";
        }
    }
}

// CLI interfejs
if (php_sapi_name() === 'cli') {
    $migrator = new Migrator($conn);

    $command = $argv[1] ?? 'help';

    switch ($command) {
        case 'migrate':
            $migrator->migrate();
            break;

        case 'rollback':
            $steps = isset($argv[2]) ? intval($argv[2]) : 1;
            $migrator->rollback($steps);
            break;

        case 'reset':
            echo "⚠️ UPOZORENJE: Ovo će obrisati SVE podatke!\n";
            echo "Da li ste sigurni? (yes/no): ";
            $handle = fopen("php://stdin", "r");
            $line = fgets($handle);
            if (trim($line) == 'yes') {
                $migrator->reset();
            } else {
                echo "Otkazano.\n";
            }
            fclose($handle);
            break;

        case 'status':
            $migrator->status();
            break;

        default:
            echo "📖 Komande:\n";
            echo "  php migrate.php migrate   - Pokreni sve nove migracije\n";
            echo "  php migrate.php rollback  - Vrati poslednju migraciju\n";
            echo "  php migrate.php rollback 2 - Vrati poslednje 2 migracije\n";
            echo "  php migrate.php reset     - Resetuj SVE (obriši sve tabele)\n";
            echo "  php migrate.php status    - Prikaži status migracija\n";
            break;
    }
} else {
    // Ako nije CLI, koristi preko browsera
    echo "<pre>";
    $migrator = new Migrator($conn);

    $action = $_GET['action'] ?? 'status';

    switch ($action) {
        case 'migrate':
            $migrator->migrate();
            break;
        case 'status':
            $migrator->status();
            break;
        default:
            echo "Dostupne akcije:\n";
            echo "<a href='?action=status'>Status</a> | ";
            echo "<a href='?action=migrate'>Migrate</a>\n\n";
            $migrator->status();
    }
    echo "</pre>";
}
?>