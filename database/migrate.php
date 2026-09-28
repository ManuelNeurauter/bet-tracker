<?php
/**
 * BetLedger - Database Migration Runner
 *
 * Brings an existing database up to date with the current schema by running
 * the files in database/migrations/ that have not been applied yet.
 * Applied versions are recorded in the schema_migrations table.
 *
 * Usage (from the project root):
 *   php database/migrate.php            Apply all pending migrations
 *   php database/migrate.php status     List applied and pending migrations
 *   php database/migrate.php make NAME  Create a new, empty migration file
 *
 * Connection settings come from config/config.php (DB_HOST, DB_USER, ...),
 * which reads the same environment variables the app uses.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../config/config.php';

const MIGRATIONS_DIR = __DIR__ . '/migrations';
const MIGRATIONS_TABLE = 'schema_migrations';
const MIGRATIONS_LOCK = 'bet_tracker_migrations';

// ============================================
// HELPERS AVAILABLE TO PHP MIGRATIONS
// ============================================

/**
 * Check whether a table exists in the current database
 */
function tableExists(PDO $db, string $table): bool {
    $stmt = $db->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
    );
    $stmt->execute([$table]);
    return (int)$stmt->fetchColumn() > 0;
}

/**
 * Check whether a column exists on a table in the current database
 */
function columnExists(PDO $db, string $table, string $column): bool {
    $stmt = $db->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $stmt->execute([$table, $column]);
    return (int)$stmt->fetchColumn() > 0;
}

/**
 * Check whether an index exists on a table in the current database
 */
function indexExists(PDO $db, string $table, string $index): bool {
    $stmt = $db->prepare(
        'SELECT COUNT(*) FROM information_schema.STATISTICS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?'
    );
    $stmt->execute([$table, $index]);
    return (int)$stmt->fetchColumn() > 0;
}

// ============================================
// RUNNER
// ============================================

function connect(): PDO {
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    try {
        return new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (PDOException $e) {
        fail('Could not connect to ' . DB_NAME . ' on ' . DB_HOST . ':' . DB_PORT . ': ' . $e->getMessage());
    }
}

function fail(string $message): void {
    fwrite(STDERR, "Error: $message\n");
    exit(1);
}

function ensureMigrationsTable(PDO $db): void {
    $db->exec(
        'CREATE TABLE IF NOT EXISTS ' . MIGRATIONS_TABLE . ' (
            version VARCHAR(255) PRIMARY KEY,
            applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
}

/**
 * All migration files, keyed by version (file name without extension), in order
 */
function migrationFiles(): array {
    $files = [];
    foreach (scandir(MIGRATIONS_DIR) as $file) {
        $path = MIGRATIONS_DIR . '/' . $file;
        if (!in_array(pathinfo($file, PATHINFO_EXTENSION), ['sql', 'php'], true)) {
            continue;
        }
        $version = pathinfo($file, PATHINFO_FILENAME);
        if (!preg_match('/^\d{4}_[a-z0-9_]+$/', $version)) {
            fail('Migration file names must look like 0001_short_description.sql: ' . basename($path));
        }
        if (isset($files[$version])) {
            fail("Two migration files share the version $version");
        }
        $files[$version] = $path;
    }
    ksort($files, SORT_STRING);
    return $files;
}

function appliedVersions(PDO $db): array {
    $rows = $db->query('SELECT version, applied_at FROM ' . MIGRATIONS_TABLE)->fetchAll();
    return array_column($rows, 'applied_at', 'version');
}

/**
 * Split a SQL file into single statements.
 * Handles quoted strings, backticks and -- / # / block comments.
 */
function splitSql(string $sql): array {
    $statements = [];
    $current = '';
    $length = strlen($sql);
    $quote = null;

    for ($i = 0; $i < $length; $i++) {
        $char = $sql[$i];
        $next = $sql[$i + 1] ?? '';

        if ($quote !== null) {
            $current .= $char;
            if ($char === '\\' && $quote !== '`') {
                $current .= $next;
                $i++;
            } elseif ($char === $quote) {
                $quote = null;
            }
            continue;
        }

        if ($char === "'" || $char === '"' || $char === '`') {
            $quote = $char;
            $current .= $char;
        } elseif (($char === '-' && $next === '-') || $char === '#') {
            $end = strpos($sql, "\n", $i);
            $i = $end === false ? $length : $end;
            $current .= "\n";
        } elseif ($char === '/' && $next === '*') {
            $end = strpos($sql, '*/', $i + 2);
            $i = $end === false ? $length : $end + 1;
        } elseif ($char === ';') {
            $statements[] = trim($current);
            $current = '';
        } else {
            $current .= $char;
        }
    }
    $statements[] = trim($current);

    return array_values(array_filter($statements, 'strlen'));
}

function runMigration(PDO $db, string $path): void {
    if (pathinfo($path, PATHINFO_EXTENSION) === 'php') {
        $migration = require $path;
        if (!is_callable($migration)) {
            fail(basename($path) . ' must return a function(PDO $db)');
        }
        $migration($db);
        return;
    }

    foreach (splitSql(file_get_contents($path)) as $statement) {
        $db->exec($statement);
    }
}

function commandUp(PDO $db): void {
    if (!tableExists($db, 'users')) {
        fail('The database has no BetLedger tables yet. Import database/schema.sql first.');
    }

    $applied = appliedVersions($db);
    $pending = array_diff_key(migrationFiles(), $applied);

    if (!$pending) {
        echo "Database is up to date.\n";
        return;
    }

    $record = $db->prepare('INSERT INTO ' . MIGRATIONS_TABLE . ' (version) VALUES (?)');
    foreach ($pending as $version => $path) {
        echo "Applying $version ... ";
        try {
            runMigration($db, $path);
        } catch (Throwable $e) {
            echo "failed\n";
            fail("$version: " . $e->getMessage() . "\nMigrations after it were not run. Fix the problem and run the command again.");
        }
        $record->execute([$version]);
        echo "done\n";
    }
    echo count($pending) . " migration(s) applied.\n";
}

function commandStatus(PDO $db): void {
    $applied = appliedVersions($db);
    $files = migrationFiles();

    if (!$files) {
        echo "No migration files found.\n";
        return;
    }
    foreach ($files as $version => $path) {
        $state = isset($applied[$version]) ? 'applied ' . $applied[$version] : 'PENDING';
        printf("  %-50s %s\n", $version, $state);
    }
    foreach (array_diff_key($applied, $files) as $version => $appliedAt) {
        printf("  %-50s %s (file missing)\n", $version, 'applied ' . $appliedAt);
    }
    $pending = count(array_diff_key($files, $applied));
    echo $pending ? "$pending pending migration(s). Run: php database/migrate.php\n" : "Database is up to date.\n";
}

function commandMake(?string $name): void {
    $slug = trim(preg_replace('/[^a-z0-9]+/', '_', strtolower((string)$name)), '_');
    if ($slug === '') {
        fail('Give the migration a name, e.g. php database/migrate.php make add_bet_currency');
    }

    $versions = array_keys(migrationFiles());
    $number = $versions ? (int)substr(end($versions), 0, 4) + 1 : 1;
    $path = sprintf('%s/%04d_%s.sql', MIGRATIONS_DIR, $number, $slug);

    file_put_contents($path, "-- " . str_replace('_', ' ', $slug) . "\n"
        . "-- Also make the same change in database/schema.sql and add this version\n"
        . "-- to the schema_migrations INSERT at the bottom of that file.\n\n");
    echo "Created " . substr($path, strlen(dirname(__DIR__)) + 1) . "\n";
}

// ============================================
// ENTRY POINT
// ============================================

$command = $argv[1] ?? 'up';

if ($command === 'make') {
    commandMake($argv[2] ?? null);
    exit(0);
}
if (!in_array($command, ['up', 'status'], true)) {
    fwrite(STDERR, "Usage: php database/migrate.php [up|status|make NAME]\n");
    exit(1);
}

$db = connect();
ensureMigrationsTable($db);

// Stop two runs (e.g. two deploys) from applying the same migration at once
if ((int)$db->query("SELECT GET_LOCK('" . MIGRATIONS_LOCK . "', 30)")->fetchColumn() !== 1) {
    fail('Another migration run is in progress.');
}

$command === 'status' ? commandStatus($db) : commandUp($db);

$db->query("SELECT RELEASE_LOCK('" . MIGRATIONS_LOCK . "')");
