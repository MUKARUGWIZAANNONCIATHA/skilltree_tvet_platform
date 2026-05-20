<?php
require_once '../config/database.php';
require_once 'includes/auth.php';

// Helper validation functions (kept within file)
function validateTitle($title) {
    $len = strlen(trim($title));
    if ($len < 3 || $len > 100) return false;
    // Allow letters, numbers, spaces, basic punctuation
    return preg_match('/^[A-Za-z0-9\s\-_,.!?]+$/', $title) === 1;
}

function validateContent($content) {
    $len = strlen(trim($content));
    return $len >= 3 && $len <= 5000;
}

function validateComment($comment) {
    $len = strlen(trim($comment));
    return $len >= 1 && $len <= 500;
}

// Handle new post
if (isset($_POST['new_post'])) {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $error = '';

    if (empty($title) || empty($content)) {
        $error = 'Title and content are required.';
    } elseif (!validateTitle($title)) {
        $error = 'Title must be 3–100 characters and contain only letters, numbers, spaces, and basic punctuation.';
    } elseif (!validateContent($content)) {
        $error = 'Content must be at least 3 characters and not exceed 5000 characters.';
    }

    if (!$error) {
        $ins = $pdo->prepare("INSERT INTO community_posts (student_id, title, content) VALUES (?, ?, ?)");
        $ins->execute([$studentId, $title, $content]);
        header("Location: community.php?success=post");
        exit;
    } else {
        // Store error to display after redirect
        $_SESSION['community_error'] = $error;
        header("Location: community.php");
        exit;
    }
}

// Handle new comment
if (isset($_POST['submit_comment'])) {
    $postId = intval($_POST['post_id']);
    $commentText = trim($_POST['comment_text']);
    $error = '';

    if (!$postId) {
        $error = 'Invalid post.';
    } elseif (empty($commentText)) {
        $error = 'Comment cannot be empty.';
    } elseif (!validateComment($commentText)) {
        $error = 'Comment must be between 1 and 500 characters.';
    }

    if (!$error) {
        $ins = $pdo->prepare("INSERT INTO community_comments (post_id, student_id, comment) VALUES (?, ?, ?)");
        $ins->execute([$postId, $studentId, $commentText]);
        header("Location: community.php?success=comment");
        exit;
    } else {
        $_SESSION['community_error'] = $error;
        header("Location: community.php");
        exit;
    }
}

// Fetch posts with comment counts and author names
$sql = "
    SELECT p.*, u.full_name as author_name,
           (SELECT COUNT(*) FROM community_comments WHERE post_id = p.post_id) as nb_comments
    FROM community_posts p
    JOIN users u ON p.student_id = u.user_id
    ORDER BY p.created_at DESC
";
$posts = $pdo->query($sql)->fetchAll();

// Display success/error messages
$successMsg = '';
if (isset($_GET['success'])) {
    if ($_GET['success'] == 'post') $successMsg = 'Your post has been published.';
    if ($_GET['success'] == 'comment') $successMsg = 'Your reply has been added.';
}
$errorMsg = $_SESSION['community_error'] ?? '';
unset($_SESSION['community_error']);
?>
<?php include 'includes/header.php'; ?>

<style>
    /* Same styles as before – kept for brevity */
    .community-header {
        background: linear-gradient(135deg, #1a5f7a, #0e3a4a);
        border-radius: 1.5rem;
        padding: 2rem;
        color: white;
        margin-bottom: 2rem;
    }
    .new-post-form {
        background: white;
        border-radius: 1.2rem;
        padding: 1.5rem;
        margin-bottom: 2rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
    }
    .post-card {
        background: white;
        border-radius: 1.2rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        transition: 0.2s;
    }
    .post-title {
        font-size: 1.2rem;
        font-weight: 700;
        margin-bottom: 0.3rem;
    }
    .post-meta {
        font-size: 0.7rem;
        color: #8aaec0;
        margin-bottom: 0.8rem;
    }
    .post-content {
        margin-bottom: 1rem;
        line-height: 1.5;
    }
    .comments-section {
        margin-top: 1rem;
        padding-top: 1rem;
        border-top: 1px solid #eef2f8;
    }
    .comment {
        background: #f8fafc;
        padding: 0.8rem;
        border-radius: 0.8rem;
        margin-bottom: 0.8rem;
    }
    .comment-author {
        font-weight: 600;
        font-size: 0.8rem;
        margin-bottom: 0.2rem;
    }
    .comment-text {
        font-size: 0.85rem;
        color: #4a6a82;
    }
    .reply-form {
        margin-top: 0.8rem;
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
        align-items: center;
    }
    .reply-form input[type="text"] {
        flex: 1;
        border: 1px solid #cbd5e1;
        border-radius: 2rem;
        padding: 0.5rem 1rem;
        font-size: 0.85rem;
    }
    .btn-primary {
        background: #2c7da0;
        border: none;
        border-radius: 2rem;
        padding: 0.5rem 1.2rem;
        color: white;
        cursor: pointer;
    }
    .btn-primary:hover {
        background: #1e5f7a;
    }
    .empty-state {
        text-align: center;
        padding: 3rem;
        background: white;
        border-radius: 1.5rem;
        color: #8aaec0;
    }
    .alert-success {
        background: #e8f5e9;
        color: #2e7d32;
        padding: 0.8rem;
        border-radius: 0.8rem;
        margin-bottom: 1rem;
    }
    .alert-error {
        background: #ffebee;
        color: #c62828;
        padding: 0.8rem;
        border-radius: 0.8rem;
        margin-bottom: 1rem;
    }
    @media (max-width: 700px) {
        .reply-form { flex-direction: column; align-items: stretch; }
    }
</style>

<div class="community-header">
    <h1><i class="fas fa-comments"></i> Student Community</h1>
    <p>Ask questions, share resources, and help each other succeed.</p>
</div>

<?php if ($successMsg): ?>
    <div class="alert-success"><?= htmlspecialchars($successMsg) ?></div>
<?php endif; ?>
<?php if ($errorMsg): ?>
    <div class="alert-error"><?= htmlspecialchars($errorMsg) ?></div>
<?php endif; ?>

<div class="new-post-form">
    <h3>➕ Start a new discussion</h3>
    <form method="post">
        <input type="text" name="title" placeholder="Title (3-100 characters, letters/numbers/basic punctuation)" required style="width:100%; margin-bottom:0.8rem; padding:0.6rem; border-radius:1rem; border:1px solid #cbd5e1;">
        <textarea name="content" rows="3" placeholder="Write your message (min 3 characters)..." required style="width:100%; margin-bottom:0.8rem; padding:0.6rem; border-radius:1rem; border:1px solid #cbd5e1;"></textarea>
        <button type="submit" name="new_post" class="btn-primary">Publish</button>
    </form>
</div>

<div class="posts-list">
    <?php if (empty($posts)): ?>
        <div class="empty-state">
            <i class="fas fa-comment-dots"></i>
            <p>No discussions yet. Be the first to start a conversation!</p>
        </div>
    <?php else: ?>
        <?php foreach ($posts as $post): ?>
        <div class="post-card">
            <div class="post-title"><?= htmlspecialchars($post['title']) ?></div>
            <div class="post-meta">
                By <?= htmlspecialchars($post['author_name']) ?> • <?= date('M d, Y H:i', strtotime($post['created_at'])) ?>
                • 💬 <?= $post['nb_comments'] ?> comments
            </div>
            <div class="post-content"><?= nl2br(htmlspecialchars($post['content'])) ?></div>

            <div class="comments-section">
                <?php
                $commentStmt = $pdo->prepare("
                    SELECT c.*, u.full_name as author_name
                    FROM community_comments c
                    JOIN users u ON c.student_id = u.user_id
                    WHERE c.post_id = ?
                    ORDER BY c.created_at ASC
                ");
                $commentStmt->execute([$post['post_id']]);
                $comments = $commentStmt->fetchAll();
                ?>
                <?php foreach ($comments as $cmt): ?>
                <div class="comment">
                    <div class="comment-author"><?= htmlspecialchars($cmt['author_name']) ?> • <small><?= date('M d, H:i', strtotime($cmt['created_at'])) ?></small></div>
                    <div class="comment-text"><?= nl2br(htmlspecialchars($cmt['comment'])) ?></div>
                </div>
                <?php endforeach; ?>

                <form method="post" class="reply-form">
                    <input type="hidden" name="post_id" value="<?= $post['post_id'] ?>">
                    <input type="text" name="comment_text" placeholder="Write a reply (max 500 characters)..." required maxlength="500">
                    <button type="submit" name="submit_comment" class="btn-primary">Reply</button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include_once '../includes/templates/footer.php'; ?>