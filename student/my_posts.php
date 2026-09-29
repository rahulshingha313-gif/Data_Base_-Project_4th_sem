<?php
require_once __DIR__ . '/../config/config.php';
requireRole('student');

$db = getDB();
$spId = currentStudentProfileId();

// Handle delete
if (isset($_GET['delete'])) {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_GET['csrf_token'] ?? '')) {
        die('Invalid request.');
    }
    $id = (int) $_GET['delete'];
    $stmt = $db->prepare('DELETE FROM tuition_posts WHERE tuition_post_id = ? AND student_profile_id = ?');
    $stmt->execute([$id, $spId]);
    setFlash('success', 'Tuition post deleted.');
    redirect('/student/my_posts.php');
}

$posts = $db->prepare(
    "SELECT tp.*, s.subject_name,
        (SELECT COUNT(*) FROM applications a WHERE a.tuition_post_id = tp.tuition_post_id) AS app_count
     FROM tuition_posts tp JOIN subjects s ON s.subject_id = tp.subject_id
     WHERE tp.student_profile_id = ? ORDER BY tp.created_at DESC"
);
$posts->execute([$spId]);
$posts = $posts->fetchAll();

$pageTitle = 'My Tuition Posts';
include __DIR__ . '/../includes/header.php';
?>
<div class="dash-layout">
    <?php include __DIR__ . '/../includes/sidebar_student.php'; ?>
    <div>
        <div class="flex-between">
            <h2>My Tuition Posts</h2>
            <a href="<?= BASE_URL ?>/student/post_tuition.php" class="btn btn-primary btn-sm">+ New Post</a>
        </div>
        <div class="card">
            <?php if (!$posts): ?>
                <p class="empty-state">You haven't posted any tuition requirements yet.</p>
            <?php else: ?>
            <table>
                <tr><th>Title</th><th>Subject</th><th>Budget</th><th>Status</th><th>Applications</th><th>Actions</th></tr>
                <?php foreach ($posts as $p): ?>
                <tr>
                    <td><?= e($p['title']) ?></td>
                    <td><?= e($p['subject_name']) ?></td>
                    <td><?= money($p['budget_min']) ?>-<?= money($p['budget_max']) ?></td>
                    <td><span class="badge badge-<?= e($p['status']) ?>"><?= e($p['status']) ?></span></td>
                    <td><a href="<?= BASE_URL ?>/student/applications.php?post_id=<?= (int)$p['tuition_post_id'] ?>"><?= (int)$p['app_count'] ?></a></td>
                    <td>
                        <a href="<?= BASE_URL ?>/student/view_post.php?id=<?= (int)$p['tuition_post_id'] ?>" class="btn btn-secondary btn-sm">View</a>
                        <a href="<?= BASE_URL ?>/student/edit_post.php?id=<?= (int)$p['tuition_post_id'] ?>" class="btn btn-outline btn-sm">Edit</a>
                        <a href="?delete=<?= (int)$p['tuition_post_id'] ?>&csrf_token=<?= e(csrfToken()) ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this post?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
