<?php
require_once '../config/database.php';
require_once 'includes/auth.php';

// Check if student already has enrollments
$check = $pdo->prepare("SELECT enrollment_id FROM student_enrollments WHERE student_id = ? LIMIT 1");
$check->execute([$studentId]);
if ($check->fetch()) {
    header('Location: dashboard.php');
    exit;
}

$sectors = $pdo->query("SELECT * FROM sectors ORDER BY sector_name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedTrade = trim($_POST['trade_name']);

    // Get all modules that belong to this trade
    $modulesStmt = $pdo->prepare("SELECT module_id FROM modules WHERE trade = ? AND status = 'published' ORDER BY module_id");
    $modulesStmt->execute([$selectedTrade]);
    $modules = $modulesStmt->fetchAll();

    if ($modules) {
        $first = true;
        foreach ($modules as $mod) {
            $status = $first ? 'in_progress' : 'enrolled';
            $ins = $pdo->prepare("INSERT INTO student_enrollments (student_id, module_id, status) VALUES (?, ?, ?)");
            $ins->execute([$studentId, $mod['module_id'], $status]);
            $first = false;
        }
        header('Location: dashboard.php');
        exit;
    } else {
        $error = "No modules found for this trade.";
    }
}
?>
<?php include 'includes/header.php'; ?>

<!-- Custom CSS for this page only -->
<style>
    .trade-selection-container {
        max-width: 1200px;
        margin: 2rem auto;
        padding: 0 1rem;
    }
    .hero-section {
        text-align: center;
        margin-bottom: 3rem;
    }
    .hero-section h1 {
        font-size: 2.2rem;
        font-weight: 700;
        color: #1a3e50;
        margin-bottom: 0.5rem;
    }
    .hero-section p {
        font-size: 1.1rem;
        color: #5b7f95;
        max-width: 600px;
        margin: 0 auto;
    }
    .sector-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        gap: 2rem;
        margin-top: 1rem;
    }
    .sector-card {
        background: white;
        border-radius: 1.5rem;
        box-shadow: 0 12px 30px rgba(0,0,0,0.05);
        overflow: hidden;
        transition: transform 0.2s, box-shadow 0.2s;
        border: 1px solid #eef2f8;
    }
    .sector-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 20px 35px rgba(0,0,0,0.1);
    }
    .sector-header {
        background: linear-gradient(135deg, #f0f6fa 0%, #e9f0f5 100%);
        padding: 1.2rem 1.5rem;
        border-bottom: 1px solid #e2e9f0;
    }
    .sector-header h3 {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 600;
        color: #1e5a7a;
        display: flex;
        align-items: center;
        gap: 0.8rem;
    }
    .sector-header h3 i {
        font-size: 1.8rem;
        color: #2c7da0;
    }
    .trades-list {
        padding: 1.5rem;
        display: flex;
        flex-direction: column;
        gap: 0.8rem;
    }
    .trade-option {
        display: flex;
        align-items: center;
        background: #fafcfe;
        border: 1px solid #e2edf4;
        border-radius: 1rem;
        padding: 0.8rem 1rem;
        cursor: pointer;
        transition: all 0.2s;
    }
    .trade-option:hover {
        background: #eef4fa;
        border-color: #bdd4e2;
        transform: translateX(5px);
    }
    .trade-option input[type="radio"] {
        width: 1.2rem;
        height: 1.2rem;
        margin-right: 1rem;
        accent-color: #2c7da0;
        cursor: pointer;
    }
    .trade-option label {
        flex: 1;
        font-weight: 500;
        color: #1f5068;
        cursor: pointer;
        margin: 0;
        font-size: 1rem;
    }
    .trade-option i {
        color: #6d8faa;
        font-size: 1.2rem;
        margin-left: 0.5rem;
    }
    .btn-start {
        display: block;
        width: 100%;
        background: linear-gradient(135deg, #2c7da0, #1e5f7e);
        border: none;
        border-radius: 3rem;
        padding: 1rem;
        font-size: 1.2rem;
        font-weight: 600;
        color: white;
        margin-top: 2rem;
        cursor: pointer;
        transition: background 0.2s, transform 0.1s;
        text-align: center;
    }
    .btn-start:hover {
        background: linear-gradient(135deg, #236b8a, #18506b);
        transform: scale(1.01);
    }
    .alert.error {
        background: #ffe6e5;
        border-left: 4px solid #e55353;
        color: #b33;
        padding: 0.8rem 1rem;
        border-radius: 1rem;
        margin-bottom: 1rem;
        text-align: center;
    }
    @media (max-width: 700px) {
        .hero-section h1 { font-size: 1.8rem; }
        .sector-grid { grid-template-columns: 1fr; }
    }
</style>

<div class="trade-selection-container">
    <div class="hero-section">
        <h1>🚀 Choose Your Professional Path</h1>
        <p>Select the trade you are enrolled in. This will unlock your personalized learning journey.</p>
    </div>

    <?php if (isset($error)): ?>
        <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post" id="tradeForm">
        <div class="sector-grid">
            <?php foreach ($sectors as $sector): ?>
                <div class="sector-card">
                    <div class="sector-header">
                        <h3>
                            <i class="fas fa-<?= 
                                $sector['sector_name'] == 'Information Technology' ? 'laptop-code' : 
                                ($sector['sector_name'] == 'Hospitality & Tourism' ? 'utensils' : 
                                ($sector['sector_name'] == 'Construction & Building' ? 'hard-hat' : 'industry')) 
                            ?>"></i>
                            <?= htmlspecialchars($sector['sector_name']) ?>
                        </h3>
                    </div>
                    <div class="trades-list">
                        <?php
                        $tradesStmt = $pdo->prepare("SELECT * FROM trades WHERE sector_id = ?");
                        $tradesStmt->execute([$sector['sector_id']]);
                        while ($trade = $tradesStmt->fetch()):
                        ?>
                        <div class="trade-option" onclick="this.querySelector('input').click();">
                            <input type="radio" name="trade_name" value="<?= htmlspecialchars($trade['trade_name']) ?>" id="trade_<?= $trade['trade_id'] ?>" required>
                            <label for="trade_<?= $trade['trade_id'] ?>"><?= htmlspecialchars($trade['trade_name']) ?></label>
                            <i class="fas fa-chevron-right"></i>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="submit" class="btn-start">✨ Start My Learning Journey</button>
    </form>
</div>

<script>
// Add a subtle animation on radio selection
document.querySelectorAll('.trade-option input').forEach(radio => {
    radio.addEventListener('change', function() {
        if(this.checked) {
            document.querySelectorAll('.trade-option').forEach(opt => opt.style.background = '#fafcfe');
            this.closest('.trade-option').style.background = '#e3f0f7';
        }
    });
});
</script>

<?php include 'includes/footer.php'; ?>