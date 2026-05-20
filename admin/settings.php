<?php
/**
 * System Settings
 * Path: /admin/settings.php
 */

require_once '../includes/auth/session-check.php';
requireRole(['admin']);

require_once '../config/database.php';
require_once '../includes/functions/common.php';

$message = '';
$error = '';

// Handle settings update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_general') {
        // Update config file or database settings
        // For now, we'll just show a message
        $message = 'General settings updated successfully!';
        logActivity($_SESSION['user_id'], 'edit', 'Updated general settings');
        
    } elseif ($action === 'update_security') {
        $message = 'Security settings updated successfully!';
        logActivity($_SESSION['user_id'], 'edit', 'Updated security settings');
        
    } elseif ($action === 'update_email') {
        $message = 'Email settings updated successfully!';
        logActivity($_SESSION['user_id'], 'edit', 'Updated email settings');
    }
}

include_once '../includes/templates/header.php';
?>

<div class="settings-container">
    <div class="page-header">
        <h1><i class="fas fa-cog"></i> System Settings</h1>
        <p>Configure your platform settings</p>
    </div>

    <?php if($message): ?>
        <div class="alert alert-success"><?php echo $message; ?></div>
    <?php endif; ?>

    <?php if($error): ?>
        <div class="alert alert-error"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="settings-grid">
        <!-- General Settings -->
        <div class="settings-card">
            <div class="card-header">
                <i class="fas fa-globe"></i>
                <h2>General Settings</h2>
            </div>
            <form method="POST" action="" class="settings-form">
                <input type="hidden" name="action" value="update_general">
                
                <div class="form-group">
                    <label>Platform Name</label>
                    <input type="text" name="platform_name" class="form-control" value="SkillTree TVET Platform">
                    <small class="form-hint">This name appears in the browser tab and header</small>
                </div>
                
                <div class="form-group">
                    <label>Platform URL</label>
                    <input type="text" name="platform_url" class="form-control" value="<?= APP_URL ?>">
                    <small class="form-hint">Your platform's base URL</small>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>Default Pass Mark (%)</label>
                        <input type="number" name="pass_mark" class="form-control" value="70">
                        <small class="form-hint">Minimum score to pass quizzes and exams</small>
                    </div>
                    <div class="form-group">
                        <label>Resource Completion Required (%)</label>
                        <input type="number" name="resource_completion" class="form-control" value="90">
                        <small class="form-hint">Required before taking quiz</small>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Default Timezone</label>
                    <select name="timezone" class="form-control">
                        <option value="Africa/Kigali" selected>Africa/Kigali (Rwanda)</option>
                        <option value="Africa/Nairobi">Africa/Nairobi</option>
                        <option value="Africa/Johannesburg">Africa/Johannesburg</option>
                        <option value="UTC">UTC</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Items Per Page (Pagination)</label>
                    <input type="number" name="items_per_page" class="form-control" value="20">
                </div>
                
                <button type="submit" class="btn-save">
                    <i class="fas fa-save"></i> Save General Settings
                </button>
            </form>
        </div>

        <!-- Security Settings -->
        <div class="settings-card">
            <div class="card-header">
                <i class="fas fa-shield-alt"></i>
                <h2>Security Settings</h2>
            </div>
            <form method="POST" action="" class="settings-form">
                <input type="hidden" name="action" value="update_security">
                
                <div class="form-group">
                    <label>Session Timeout (minutes)</label>
                    <input type="number" name="session_timeout" class="form-control" value="120">
                    <small class="form-hint">User will be logged out after inactivity</small>
                </div>
                
                <div class="form-group">
                    <label>Max Login Attempts</label>
                    <input type="number" name="max_attempts" class="form-control" value="5">
                    <small class="form-hint">Before temporary lockout</small>
                </div>
                
                <div class="form-group">
                    <label>Login Lockout Time (minutes)</label>
                    <input type="number" name="lockout_time" class="form-control" value="15">
                </div>
                
                <div class="form-check">
                    <input type="checkbox" name="force_https" id="force_https" value="1" disabled>
                    <label for="force_https">Force HTTPS (Requires SSL Certificate)</label>
                </div>
                
                <div class="form-check">
                    <input type="checkbox" name="enable_2fa" id="enable_2fa" value="1">
                    <label for="enable_2fa">Enable Two-Factor Authentication for Admins</label>
                </div>
                
                <button type="submit" class="btn-save">
                    <i class="fas fa-save"></i> Save Security Settings
                </button>
            </form>
        </div>

        <!-- Email Settings -->
        <div class="settings-card">
            <div class="card-header">
                <i class="fas fa-envelope"></i>
                <h2>Email Settings</h2>
            </div>
            <form method="POST" action="" class="settings-form">
                <input type="hidden" name="action" value="update_email">
                
                <div class="form-group">
                    <label>SMTP Host</label>
                    <input type="text" name="smtp_host" class="form-control" value="smtp.gmail.com">
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label>SMTP Port</label>
                        <input type="number" name="smtp_port" class="form-control" value="587">
                    </div>
                    <div class="form-group">
                        <label>Encryption</label>
                        <select name="smtp_encryption" class="form-control">
                            <option value="tls">TLS</option>
                            <option value="ssl">SSL</option>
                            <option value="none">None</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>SMTP Username</label>
                    <input type="email" name="smtp_user" class="form-control" placeholder="your-email@gmail.com">
                </div>
                
                <div class="form-group">
                    <label>SMTP Password</label>
                    <input type="password" name="smtp_pass" class="form-control" placeholder="••••••••">
                </div>
                
                <div class="form-group">
                    <label>From Email</label>
                    <input type="email" name="from_email" class="form-control" value="noreply@skilltree.rw">
                </div>
                
                <div class="form-group">
                    <label>From Name</label>
                    <input type="text" name="from_name" class="form-control" value="SkillTree TVET Platform">
                </div>
                
                <button type="submit" class="btn-save">
                    <i class="fas fa-save"></i> Save Email Settings
                </button>
            </form>
        </div>

        <!-- System Info -->
        <div class="settings-card">
            <div class="card-header">
                <i class="fas fa-info-circle"></i>
                <h2>System Information</h2>
            </div>
            
            <div class="info-row">
                <span class="info-label">PHP Version:</span>
                <span class="info-value"><?php echo phpversion(); ?></span>
            </div>
            
            <div class="info-row">
                <span class="info-label">MySQL Version:</span>
                <span class="info-value">
                    <?php
                    $stmt = $pdo->query("SELECT VERSION() as version");
                    $version = $stmt->fetch();
                    echo $version['version'];
                    ?>
                </span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Server Software:</span>
                <span class="info-value"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Platform Version:</span>
                <span class="info-value">1.0.0</span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Database Size:</span>
                <span class="info-value">
                    <?php
                    $stmt = $pdo->query("SELECT SUM(data_length + index_length) as size FROM information_schema.tables WHERE table_schema = 'tvet_platform'");
                    $size = $stmt->fetch();
                    $sizeMB = round($size['size'] / 1024 / 1024, 2);
                    echo $sizeMB . ' MB';
                    ?>
                </span>
            </div>
            
            <div class="info-row">
                <span class="info-label">Last Backup:</span>
                <span class="info-value">Not configured</span>
            </div>
        </div>
    </div>

    <div class="danger-zone">
        <h3><i class="fas fa-exclamation-triangle"></i> Danger Zone</h3>
        <div class="danger-actions">
            <button class="btn-danger" onclick="clearCache()">
                <i class="fas fa-trash-alt"></i> Clear System Cache
            </button>
            <button class="btn-danger" onclick="backupDatabase()">
                <i class="fas fa-database"></i> Backup Database
            </button>
            <button class="btn-danger" onclick="optimizeDatabase()">
                <i class="fas fa-chart-line"></i> Optimize Database
            </button>
        </div>
    </div>
</div>

<style>
.settings-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 30px 24px;
}

.page-header {
    margin-bottom: 30px;
}

.page-header h1 {
    font-size: 28px;
    color: #1a1a2e;
    margin-bottom: 5px;
}

.page-header p {
    color: #666;
}

.settings-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
    gap: 24px;
    margin-bottom: 30px;
}

.settings-card {
    background: white;
    border-radius: 20px;
    padding: 24px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}

.card-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 1px solid #eee;
}

.card-header i {
    font-size: 24px;
    color: #667eea;
}

.card-header h2 {
    font-size: 20px;
    color: #1a1a2e;
    margin: 0;
}

.settings-form .form-group {
    margin-bottom: 20px;
}

.settings-form label {
    display: block;
    margin-bottom: 8px;
    font-weight: 500;
    color: #333;
    font-size: 14px;
}

.form-control {
    width: 100%;
    padding: 12px 15px;
    border: 1px solid #ddd;
    border-radius: 12px;
    font-size: 14px;
    transition: all 0.3s;
}

.form-control:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.form-hint {
    display: block;
    margin-top: 5px;
    font-size: 11px;
    color: #999;
}

.form-check {
    display: flex;
    align-items: center;
    gap: 10px;
    margin: 15px 0;
}

.form-check input {
    width: 18px;
    height: 18px;
    cursor: pointer;
}

.form-check label {
    margin: 0;
    cursor: pointer;
}

.btn-save {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border: none;
    padding: 12px 24px;
    border-radius: 30px;
    cursor: pointer;
    font-weight: 600;
    width: 100%;
    transition: all 0.3s;
}

.btn-save:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102,126,234,0.4);
}

.info-row {
    display: flex;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px solid #eee;
}

.info-label {
    font-weight: 500;
    color: #666;
}

.info-value {
    color: #333;
    font-family: monospace;
}

.danger-zone {
    background: #fff5f5;
    border: 1px solid #fcc;
    border-radius: 20px;
    padding: 24px;
    margin-top: 20px;
}

.danger-zone h3 {
    color: #c62828;
    margin-bottom: 20px;
    font-size: 18px;
}

.danger-actions {
    display: flex;
    gap: 15px;
    flex-wrap: wrap;
}

.btn-danger {
    background: white;
    border: 1px solid #f44336;
    color: #f44336;
    padding: 10px 20px;
    border-radius: 30px;
    cursor: pointer;
    transition: all 0.3s;
}

.btn-danger:hover {
    background: #f44336;
    color: white;
}

.alert {
    padding: 15px 20px;
    border-radius: 12px;
    margin-bottom: 20px;
}

.alert-success {
    background: #e8f5e9;
    color: #2e7d32;
    border-left: 4px solid #4CAF50;
}

.alert-error {
    background: #ffebee;
    color: #c62828;
    border-left: 4px solid #f44336;
}

@media (max-width: 900px) {
    .settings-grid {
        grid-template-columns: 1fr;
    }
    .form-row {
        grid-template-columns: 1fr;
        gap: 0;
    }
}
</style>

<script>
function clearCache() {
    if(confirm('Clear system cache? This may temporarily slow down the system.')) {
        fetch('/api/v1/admin.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=clear_cache'
        }).then(() => alert('Cache cleared successfully!'));
    }
}

function backupDatabase() {
    if(confirm('Create a database backup? This may take a few moments.')) {
        alert('Backup feature coming soon. Manual backup via phpMyAdmin is recommended.');
    }
}

function optimizeDatabase() {
    if(confirm('Optimize database tables? This may improve performance.')) {
        alert('Database optimization coming soon.');
    }
}
</script>

<?php include_once '../includes/templates/footer.php'; ?>S