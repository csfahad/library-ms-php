<?php
/* Database Migration Runner */

require_once __DIR__ . '/../config/database.php';

class MigrationRunner {
    private $pdo;
    private $migrationsPath;
    
    public function __construct() {
        $this->pdo = getDB();
        $this->migrationsPath = __DIR__ . '/migrations/';
        $this->initializeMigrationsTable();
    }

    /* Initialize migrations table to track executed migrations */
    private function initializeMigrationsTable() {
        $sql = "CREATE TABLE IF NOT EXISTS migrations (
            id INT PRIMARY KEY AUTO_INCREMENT,
            migration VARCHAR(255) NOT NULL UNIQUE,
            executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $this->pdo->exec($sql);
    }

    /* Run pending migrations */
    public function runMigrations() {
        $executedMigrations = $this->getExecutedMigrations();
        $migrationFiles = $this->getMigrationFiles();
        
        foreach ($migrationFiles as $file) {
            $migrationName = basename($file, '.sql');
            
            if (!in_array($migrationName, $executedMigrations)) {
                echo "Running migration: $migrationName\n";
                
                try {
                    $sql = file_get_contents($file);
                    $this->pdo->exec($sql);
                    
                    // Mark migration as executed
                    $stmt = $this->pdo->prepare("INSERT INTO migrations (migration) VALUES (?)");
                    $stmt->execute([$migrationName]);
                    
                    echo "✓ Migration $migrationName completed successfully\n";
                } catch (Exception $e) {
                    echo "✗ Migration $migrationName failed: " . $e->getMessage() . "\n";
                    throw $e;
                }
            } else {
                echo "↻ Migration $migrationName already executed\n";
            }
        }
        
        echo "All migrations completed!\n";
    }

    /* Get list of executed migrations */
    private function getExecutedMigrations() {
        try {
            $stmt = $this->pdo->query("SELECT migration FROM migrations ORDER BY id");
            return $stmt->fetchAll(PDO::FETCH_COLUMN);
        } catch (Exception $e) {
            return [];
        }
    }

    /* Get list of migration files */
    private function getMigrationFiles() {
        $files = glob($this->migrationsPath . '*.sql');
        sort($files);
        return $files;
    }

    /* Show migration status */
    public function showStatus() {
        $executedMigrations = $this->getExecutedMigrations();
        $migrationFiles = $this->getMigrationFiles();
        
        echo "Migration Status:\n";
        echo "================\n";
        
        foreach ($migrationFiles as $file) {
            $migrationName = basename($file, '.sql');
            $status = in_array($migrationName, $executedMigrations) ? 'Executed' : 'Pending';
            echo "$status: $migrationName\n";
        }
    }
}

// Command line interface
if (php_sapi_name() === 'cli') {
    $runner = new MigrationRunner();
    
    $command = $argv[1] ?? 'run';
    
    switch ($command) {
        case 'run':
            $runner->runMigrations();
            break;
        case 'status':
            $runner->showStatus();
            break;
        default:
            echo "Usage: php migrate.php [run|status]\n";
            echo "  run    - Execute pending migrations\n";
            echo "  status - Show migration status\n";
            break;
    }
}
