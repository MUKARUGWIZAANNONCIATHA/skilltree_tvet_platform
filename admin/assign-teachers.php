<?php
/**
 * Admin: Assign Modules to Teachers
 * Path: /admin/assign-teachers.php
 */

require_once '../includes/auth/session-check.php';
requireRole(['admin']);

require_once '../config/database.php';
require_once '../includes/functions/common.php';

$message = '';
$error = '';

// Handle assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $teacherId = intval($_POST['teacher_id'] ?? 0);
    $moduleId = intval($_POST['module_id'] ?? 0);

    if ($action === 'assign') {
        if ($teacherId && $moduleId) {
            try {
                $stmt = $pdo->prepare("INSERT IGNORE INTO teacher_modules (teacher_id, module_id) VALUES (?, ?)");
                $stmt->execute([$teacherId, $moduleId]);
                $message = "Module assigned successfully.";
            } catch (PDOException $e) {
                $error = "Error: " . $e->getMessage();
            }
        } else {
            $error = "Please select both a teacher and a module.";
        }
    } elseif ($action === 'remove') {
        $assignmentId = intval($_POST['assignment_id'] ?? 0);
        if ($assignmentId) {
            $stmt = $pdo->prepare("DELETE FROM teacher_modules WHERE id = ?");
            $stmt->execute([$assignmentId]);
            $message = "Assignment removed.";
        }
    }
}

// Fetch all teachers (users with role 'teacher')
$teachers = $pdo->query("SELECT user_id, full_name, email FROM users WHERE role = 'teacher' ORDER BY full_name")->fetchAll();

// Fetch all modules
$modules = $pdo->query("SELECT module_id, module_code, module_name FROM modules ORDER BY module_code")->fetchAll();

// Fetch current assignments with teacher and module names
$assignments = $pdo->query("
    SELECT tm.id, tm.teacher_id, tm.module_id, 
           u.full_name as teacher_name, u.email,
           m.module_code, m.module_name
    FROM teacher_modules tm
    JOIN users u ON tm.teacher_id = u.user_id
    JOIN modules m ON tm.module_id = m.module_id
    ORDER BY u.full_name, m.module_code
")->fetchAll();

include_once '../includes/templates/header.php';
?>

<div class="assign-container">
    <div class="page-header">
        <h1><i class="fas fa-chalkboard-teacher"></i> Assign Modules to Teachers</h1>
        <p>Give teachers access to modules so they can upload curriculum and manage resources.</p>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- Assignment Form -->
    <div class="card">
        <h3>New Assignment</h3>
        <form method="post" class="assignment-form">
            <input type="hidden" name="action" value="assign">
            <div class="form-row">
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Teacher</label>
                    <select name="teacher_id" class="form-control" required>
                        <option value="">-- Select Teacher --</option>
                        <?php foreach ($teachers as $teacher): ?>
                            <option value="<?= $teacher['user_id'] ?>"><?= htmlspecialchars($teacher['full_name']) ?> (<?= htmlspecialchars($teacher['email']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-book"></i> Module</label>
                    <select name="module_id" class="form-control" required>
                        <option value="">-- Select Module --</option>
                        <?php foreach ($modules as $module): ?>
                            <option value="<?= $module['module_id'] ?>"><?= htmlspecialchars($module['module_code']) ?> - <?= htmlspecialchars($module['module_name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="display: flex; align-items: flex-end;">
                    <button type="submit" class="btn-primary"><i class="fas fa-plus"></i> Assign</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Current Assignments Table -->
    <div class="card">
        <h3>Current Teacher–Module Assignments</h3>
        <?php if (empty($assignments)): ?>
            <p>No assignments yet.</p>
        <?php else: ?>
            <table class="assignments-table">
                <thead>
                    <tr>
                        <th>Teacher</th>
                        <th>Email</th>
                        <th>Module Code</th>
                        <th>Module Name</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($assignments as $assign): ?>
                        <tr>
                            <td><?= htmlspecialchars($assign['teacher_name']) ?></td>
                            <td><?= htmlspecialchars($assign['email']) ?></td>
                            <td><?= htmlspecialchars($assign['module_code']) ?></td>
                            <td><?= htmlspecialchars($assign['module_name']) ?></td>
                            <td>
                                <form method="post" style="display:inline;" onsubmit="return confirm('Remove this assignment?')">
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="assignment_id" value="<?= $assign['id'] ?>">
                                    <button type="submit" class="btn-danger"><i class="fas fa-trash"></i> Remove</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<style>
.assign-container { max-width: 1000px; margin: 0 auto; padding: 30px 20px; }
.page-header { margin-bottom: 30px; }
.card { background: white; border-radius: 20px; padding: 24px; margin-bottom: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
.assignment-form .form-row { display: flex; gap: 20px; align-items: flex-end; flex-wrap: wrap; }
.form-group { flex: 1; min-width: 200px; }
.form-group label { display: block; margin-bottom: 8px; font-weight: 500; }
.form-control { width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 12px; }
.btn-primary { background: #667eea; color: white; border: none; padding: 10px 20px; border-radius: 30px; cursor: pointer; }
.btn-danger { background: #f44336; color: white; border: none; padding: 6px 12px; border-radius: 20px; cursor: pointer; }
.assignments-table { width: 100%; border-collapse: collapse; }
.assignments-table th, .assignments-table td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
.alert { padding: 12px 20px; border-radius: 12px; margin-bottom: 20px; }
.alert-success { background: #e8f5e9; color: #2e7d32; }
.alert-error { background: #ffebee; color: #c62828; }
@media (max-width: 700px) { .assignment-form .form-row { flex-direction: column; } }
</style>

<?php include_once '../includes/templates/footer.php'; ?>