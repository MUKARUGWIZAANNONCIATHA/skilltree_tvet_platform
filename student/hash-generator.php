<?php
/**
 * Hash Generator - Simple Password Hash Tool
 * Path: /hash-generator.php
 */

echo "<!DOCTYPE html>
<html>
<head>
    <title>Password Hash Generator</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 30px; background: #f0f2f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 16px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        input, button { padding: 12px; margin: 10px 0; width: 100%; border: 1px solid #ddd; border-radius: 8px; font-size: 16px; }
        button { background: #667eea; color: white; cursor: pointer; border: none; }
        button:hover { background: #5a67d8; }
        .hash-box { background: #f8f9fa; padding: 20px; border-radius: 10px; margin: 20px 0; word-break: break-all; font-family: monospace; border-left: 4px solid #667eea; }
        .sql-box { background: #1a1a2e; color: #4CAF50; padding: 15px; border-radius: 10px; overflow-x: auto; font-family: monospace; font-size: 13px; }
        h1 { color: #1e3a5f; }
        .note { background: #fff3cd; padding: 10px; border-radius: 8px; margin: 15px 0; }
    </style>
</head>
<body>
<div class='container'>
    <h1>🔐 Password Hash Generator</h1>
    <p>Generate hash for any password and get SQL commands</p>
    
    <form method='POST'>
        <label><strong>Enter Password:</strong></label>
        <input type='text' name='password' placeholder='e.g., admin123, MyPass@2024' required>
        <button type='submit'>Generate Hash & SQL</button>
    </form>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    $password = $_POST['password'];
    $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    
    echo "<div class='hash-box'>";
    echo "<strong>✅ Generated Hash:</strong><br>";
    echo "<code>" . $hash . "</code>";
    echo "</div>";
    
    echo "<div class='hash-box'>";
    echo "<strong>🔑 Your Password:</strong> " . htmlspecialchars($password);
    echo "</div>";
    
    echo "<h3>📋 Copy and run these SQL commands in phpMyAdmin or MySQL:</h3>";
    echo "<div class='sql-box'>";
    echo "USE tvet_platform;<br><br>";
    echo "-- Update all users with same password<br>";
    echo "UPDATE users SET password = '" . $hash . "' WHERE email = 'admin@tvet.rw';<br>";
    echo "UPDATE users SET password = '" . $hash . "' WHERE email = 'teacher@tvet.rw';<br>";
    echo "UPDATE users SET password = '" . $hash . "' WHERE email = 'student@tvet.rw';<br>";
    echo "UPDATE users SET password = '" . $hash . "' WHERE email = 'company@tvet.rw';<br><br>";
    echo "-- Verify<br>";
    echo "SELECT email, role FROM users;";
    echo "</div>";
}

echo "<hr>";

// Try to show users if database is accessible
$dbFile = __DIR__ . '/config/database.php';
if (file_exists($dbFile)) {
    try {
        require_once $dbFile;
        $stmt = $pdo->query("SELECT email, role FROM users ORDER BY user_id");
        $users = $stmt->fetchAll();
        
        echo "<h3>📋 Current Users in Database:</h3>";
        echo "<table style='width:100%; border-collapse:collapse;'>";
        echo "<tr style='background:#667eea; color:white;'><th style='padding:10px;'>Email</th><th style='padding:10px;'>Role</th></tr>";
        foreach($users as $user) {
            echo "<tr>";
            echo "<td style='padding:8px; border-bottom:1px solid #ddd;'>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td style='padding:8px; border-bottom:1px solid #ddd;'>" . $user['role'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } catch(Exception $e) {
        echo "<div class='note'>⚠️ Could not connect to database. Run SQL commands manually in phpMyAdmin.</div>";
    }
} else {
    echo "<div class='note'>⚠️ Run the SQL commands above directly in phpMyAdmin (http://localhost/phpmyadmin)</div>";
}
?>

<div class='note'>
    <strong>📌 How to run SQL:</strong><br>
    1. Open phpMyAdmin: <a href='http://localhost/phpmyadmin' target='_blank'>http://localhost/phpmyadmin</a><br>
    2. Select database: <strong>tvet_platform</strong><br>
    3. Click on <strong>SQL</strong> tab<br>
    4. Paste the SQL commands above<br>
    5. Click <strong>Go</strong>
</div>

</div>
</body>
</html>