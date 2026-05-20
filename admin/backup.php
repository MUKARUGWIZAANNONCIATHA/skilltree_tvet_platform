<?php
/**
 * Admin Database & Files Backup Tool
 * Path: /admin/backup.php
 */

require_once '../includes/auth/session-check.php';
requireRole(['admin']);

// Load database config and ensure it returns an array
$globalDbConfig = require_once '../config/database.php';
if (!is_array($globalDbConfig)) {
    // Fallback for misconfigured config file
    $globalDbConfig = [
        'host' => defined('DB_HOST') ? DB_HOST : 'localhost',
        'username' => defined('DB_USER') ? DB_USER : 'root',
        'password' => defined('DB_PASS') ? DB_PASS : '',
        'dbname' => defined('DB_NAME') ? DB_NAME : 'skilltree_tvet'
    ];
}

$message = '';
$error = '';
$backupDir = __DIR__ . '/../backups/';

// Create backup directory if not exists
if (!is_dir($backupDir)) {
    mkdir($backupDir, 0755, true);
}
// Protect backups directory from direct access (create .htaccess if on Apache)
$htaccess = $backupDir . '.htaccess';
if (!file_exists($htaccess)) {
    file_put_contents($htaccess, "Deny from all\n");
}

// Helper: pure PHP database dump (fallback)
function phpDatabaseDump($pdo) {
    $tables = [];
    $stmt = $pdo->query("SHOW TABLES");
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        $tables[] = $row[0];
    }
    $output = "-- SkillTree TVET Database Backup\n";
    $output .= "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
    $output .= "SET FOREIGN_KEY_CHECKS=0;\n\n";
    foreach ($tables as $table) {
        $output .= "DROP TABLE IF EXISTS `$table`;\n";
        $createResult = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
        if (is_array($createResult) && isset($createResult['Create Table'])) {
            $output .= $createResult['Create Table'] . ";\n\n";
        } else {
            $output .= "-- Unable to fetch structure for table `$table`\n\n";
            continue;
        }
        
        $rows = $pdo->query("SELECT * FROM `$table`");
        while ($row = $rows->fetch(PDO::FETCH_ASSOC)) {
            $columns = array_keys($row);
            $escaped = array_map(function($val) use ($pdo) {
                return $val === null ? 'NULL' : $pdo->quote($val);
            }, array_values($row));
            $output .= "INSERT INTO `$table` (`" . implode('`,`', $columns) . "`) VALUES (" . implode(',', $escaped) . ");\n";
        }
        $output .= "\n";
    }
    $output .= "SET FOREIGN_KEY_CHECKS=1;\n";
    return $output;
}

// Attempt to use mysqldump, fallback to pure PHP
function backupDatabase($pdo, $backupDir) {
    global $globalDbConfig;
    $timestamp = date('Y-m-d_H-i-s');
    $sqlFile = $backupDir . "db_backup_{$timestamp}.sql";
    
    // Try mysqldump first
    $dumpCmd = sprintf(
        'mysqldump --host=%s --user=%s --password=%s %s > %s 2>&1',
        escapeshellarg($globalDbConfig['host']),
        escapeshellarg($globalDbConfig['username']),
        escapeshellarg($globalDbConfig['password']),
        escapeshellarg($globalDbConfig['dbname']),
        escapeshellarg($sqlFile)
    );
    exec($dumpCmd, $output, $returnVar);
    
    if ($returnVar === 0 && file_exists($sqlFile) && filesize($sqlFile) > 0) {
        return $sqlFile;
    }
    // Fallback to PHP method
    $sqlContent = phpDatabaseDump($pdo);
    if (file_put_contents($sqlFile, $sqlContent)) {
        return $sqlFile;
    }
    return false;
}

// Create full backup (database + uploads) as ZIP
function createFullBackup($pdo, $backupDir) {
    $timestamp = date('Y-m-d_H-i-s');
    $zipFile = $backupDir . "full_backup_{$timestamp}.zip";
    
    // 1. Database backup
    $dbFile = backupDatabase($pdo, $backupDir);
    if (!$dbFile || !file_exists($dbFile)) {
        return false;
    }
    
    $zip = new ZipArchive();
    if ($zip->open($zipFile, ZipArchive::CREATE) !== true) {
        @unlink($dbFile);
        return false;
    }
    
    // Add database SQL file (rename inside zip)
    $zip->addFile($dbFile, 'database.sql');
    
    // 2. Add uploads folder (recursively)
    $uploadsPath = __DIR__ . '/../uploads/';
    if (is_dir($uploadsPath)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($uploadsPath, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        foreach ($files as $file) {
            if (!$file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = 'uploads/' . substr($filePath, strlen($uploadsPath));
                $zip->addFile($filePath, $relativePath);
            }
        }
    }
    
    $zip->close();
    @unlink($dbFile);
    return $zipFile;
}

// Handle download of existing backup
if (isset($_GET['download'])) {
    $file = basename($_GET['download']);
    $filepath = $backupDir . $file;
    if (file_exists($filepath) && preg_match('/\.zip$/', $file)) {
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $file . '"');
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
    } else {
        $error = "Backup file not found.";
    }
}

// Handle manual backup request (save only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_backup'])) {
    $zipFile = createFullBackup($pdo, $backupDir);
    if ($zipFile && file_exists($zipFile)) {
        $message = "Full backup created successfully and saved on server.";
    } else {
        $error = "Backup failed. Please check directory permissions and try again.";
    }
}

// Handle manual backup request (create & download)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_and_download'])) {
    $zipFile = createFullBackup($pdo, $backupDir);
    if ($zipFile && file_exists($zipFile)) {
        // Serve the file for download and delete after sending
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . basename($zipFile) . '"');
        header('Content-Length: ' . filesize($zipFile));
        readfile($zipFile);
        @unlink($zipFile); // remove temporary backup after download
        exit;
    } else {
        $error = "Backup failed. Please check directory permissions and try again.";
    }
}

// Handle deletion of backup files
if (isset($_GET['delete'])) {
    $file = basename($_GET['delete']);
    $filepath = $backupDir . $file;
    if (file_exists($filepath) && unlink($filepath)) {
        $message = "Deleted: $file";
    } else {
        $error = "Could not delete $file";
    }
}

// List existing backups
$backups = glob($backupDir . '*.zip');
rsort($backups);

include_once '../includes/templates/header.php';
?>

<div class="backup-container">
    <div class="page-header">
        <h1><i class="fas fa-database"></i> Backup & Restore</h1>
        <p>Create full backups (database + uploaded files) and manage existing archives</p>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="card">
        <h3>Create New Backup</h3>
        <form method="post" class="backup-form">
            <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                <button type="submit" name="save_backup" class="btn-primary">Create & Save on Server</button>
                <button type="submit" name="create_and_download" class="btn-secondary">Create & Download</button>
            </div>
        </form>
        <p class="info-note"><i class="fas fa-info-circle"></i> Backup includes: full database + all uploaded files (profile pictures, library resources). The archive is saved in the <code>/backups/</code> folder.</p>
    </div>

    <div class="card">
        <h3>Existing Backups</h3>
        <?php if (empty($backups)): ?>
            <p>No backups found.</p>
        <?php else: ?>
            <table class="backup-table">
                <thead>
                    <tr><th>Filename</th><th>Size</th><th>Date</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($backups as $file): ?>
                        <?php $filename = basename($file); ?>
                        <tr>
                            <td><?= htmlspecialchars($filename) ?></td>
                            <td><?= round(filesize($file) / 1048576, 2) ?> MB</td>
                            <td><?= date('Y-m-d H:i:s', filemtime($file)) ?></td>
                            <td>
                                <a href="?download=<?= urlencode($filename) ?>" class="btn-sm">Download</a>
                                <a href="?delete=<?= urlencode($filename) ?>" onclick="return confirm('Delete this backup?')" class="btn-sm danger">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<style>
    .backup-container { max-width: 1000px; margin: 2rem auto; padding: 0 1rem; }
    .card { background: white; border-radius: 1rem; padding: 1.5rem; margin-bottom: 2rem; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
    .btn-primary, .btn-secondary { padding: 0.5rem 1rem; border-radius: 2rem; border: none; cursor: pointer; }
    .btn-primary { background: #2c7da0; color: white; }
    .btn-secondary { background: #eef2fa; color: #1a5f7a; }
    .btn-sm { padding: 0.2rem 0.6rem; border-radius: 1rem; font-size: 0.8rem; text-decoration: none; display: inline-block; margin-right: 0.5rem; }
    .btn-sm.danger { background: #f44336; color: white; }
    .backup-table { width: 100%; border-collapse: collapse; }
    .backup-table th, .backup-table td { padding: 0.5rem; text-align: left; border-bottom: 1px solid #eee; }
    .alert { padding: 0.8rem; border-radius: 0.5rem; margin-bottom: 1rem; }
    .alert-success { background: #e8f5e9; color: #2e7d32; }
    .alert-error { background: #ffebee; color: #c62828; }
    .info-note { margin-top: 1rem; font-size: 0.8rem; color: #666; }
</style>

<?php include_once '../includes/templates/footer.php'; ?>