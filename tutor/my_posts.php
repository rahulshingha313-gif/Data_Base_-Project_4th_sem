<?php
require_once __DIR__ . '/../config/config.php';
requireRole('tutor');

$db = getDB();
$tpId = currentTutorProfileId();

if (isset($_GET['delete'])) {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_GET['csrf_token'] ?? '')) die('Invalid request.');
    $db->prepare('DELETE FROM tutor_posts WHERE tutor_post_id = ? AND tutor_profile_id = ?')->execute([(int)$_GET['delete'], $tpId]);
    setFlash('success', 'Post deleted.');
    redirect('/tutor/my_posts.php');
}

if (isset($_GET['toggle'])) {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_GET['csrf_token'] ?? '')) die('Invalid request.');
    $id = (int) $_GET['toggle'];
    $stmt = $db->prepare('SELECT status FROM tutor_posts WHERE tutor_post_id = ? AND tutor_profile_id = ?');
    $stmt->execute([$id, $tpId]);
    if ($row = $stmt->fetch()) {
        $newStatus = $row['status'] === 'active' ? 'closed' : 'active';
        $db->prepare('UPDATE tutor_posts SET status = ? WHERE tutor_post_id = ?')->execute([$newStatus, $id]);
        setFlash('success', 'Post status updated.');
    }
    redirect('/tutor/my_posts.php');
}

$posts = $db->prepare(
    "SELECT tp.*, s.subject_name FROM tutor_posts tp JOIN subjects s ON s.subject_id = tp.subject_id
     WHERE tp.tutor_profile_id = ? ORDER BY tp.created_at DESC"
);
$posts->execute([$tpId]);
$posts = $posts->fetchAll();

$pageTitle = 'My Tutor Posts';
include __DIR__ . '/../includes/header.php';
?>
<div class="dash-layout">
    <?php include __DIR__ . '/../includes/sidebar_tutor.php'; ?>
    <div>
        <div class="flex-between">
            <h2>My Tutor Posts</h2>
            <a href="<?= BASE_URL ?>/tutor/create_post.php" class="btn btn-primary btn-sm">+ New Post</a>
        </div>
        <div class="card">
            <?php if (!$posts): ?>
                <p class="empty-state">You haven't advertised any availability yet.</p>
            <?php else: ?>
            <table>
                <tr><th>Title</th><th>Subject</th><th>Rate</th><th>Status</th><th>Actions</th></tr>
                <?php foreach ($posts as $p): ?>
                <tr>
                    <td><?= e($p['title']) ?></td>
                    <td><?= e($p['subject_name']) ?></td>
                    <td><?= money($p['rate']) ?></td>
                    <td><span class="badge badge-<?= e($p['status']) ?>"><?= e($p['status']) ?></span></td>
                    <td>
                        <a href="?toggle=<?= (int)$p['tutor_post_id'] ?>&csrf_token=<?= e(csrfToken()) ?>" class="btn btn-secondary btn-sm"><?= $p['status']==='active'?'Close':'Reopen' ?></a>
                        <a href="?delete=<?= (int)$p['tutor_post_id'] ?>&csrf_token=<?= e(csrfToken()) ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this post?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
