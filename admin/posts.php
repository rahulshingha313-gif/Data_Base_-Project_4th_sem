<?php
require_once __DIR__ . '/../config/config.php';
requireAdmin();

$db = getDB();
$admin = currentUser();
$tab = $_GET['tab'] ?? 'tuition';

if (isset($_GET['remove_tuition'])) {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_GET['csrf_token'] ?? '')) die('Invalid request.');
    $id = (int) $_GET['remove_tuition'];
    $db->prepare("UPDATE tuition_posts SET status='closed' WHERE tuition_post_id = ?")->execute([$id]);
    logAdminAction($admin['user_id'], 'remove_tuition_post', 'tuition_post', $id, 'Closed inappropriate tuition post.');
    setFlash('success', 'Tuition post closed.');
    redirect('/admin/posts.php?tab=tuition');
}

if (isset($_GET['remove_tutor_post'])) {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_GET['csrf_token'] ?? '')) die('Invalid request.');
    $id = (int) $_GET['remove_tutor_post'];
    $db->prepare("UPDATE tutor_posts SET status='closed' WHERE tutor_post_id = ?")->execute([$id]);
    logAdminAction($admin['user_id'], 'remove_tutor_post', 'tutor_post', $id, 'Closed inappropriate tutor post.');
    setFlash('success', 'Tutor post closed.');
    redirect('/admin/posts.php?tab=tutor');
}

$tuitionPosts = $db->query(
    "SELECT tp.*, s.subject_name, u.full_name AS student_name FROM tuition_posts tp
     JOIN subjects s ON s.subject_id = tp.subject_id
     JOIN student_profiles sp ON sp.student_profile_id = tp.student_profile_id
     JOIN users u ON u.user_id = sp.user_id ORDER BY tp.created_at DESC"
)->fetchAll();

$tutorPosts = $db->query(
    "SELECT tp.*, s.subject_name, u.full_name AS tutor_name FROM tutor_posts tp
     JOIN subjects s ON s.subject_id = tp.subject_id
     JOIN tutor_profiles tpf ON tpf.tutor_profile_id = tp.tutor_profile_id
     JOIN users u ON u.user_id = tpf.user_id ORDER BY tp.created_at DESC"
)->fetchAll();

$pageTitle = 'Manage Posts';
include __DIR__ . '/../includes/header.php';
?>
<div class="dash-layout">
    <?php include __DIR__ . '/../includes/sidebar_admin.php'; ?>
    <div>
        <h2>Manage Posts</h2>
        <div class="filter-bar">
            <a href="?tab=tuition" class="btn <?= $tab=='tuition'?'btn-primary':'btn-secondary' ?> btn-sm">Tuition Posts (<?= count($tuitionPosts) ?>)</a>
            <a href="?tab=tutor" class="btn <?= $tab=='tutor'?'btn-primary':'btn-secondary' ?> btn-sm">Tutor Posts (<?= count($tutorPosts) ?>)</a>
        </div>

        <div class="card">
        <?php if ($tab === 'tuition'): ?>
            <table>
                <tr><th>Title</th><th>Student</th><th>Subject</th><th>Status</th><th>Actions</th></tr>
                <?php foreach ($tuitionPosts as $p): ?>
                <tr>
                    <td><a href="<?= BASE_URL ?>/student/view_post.php?id=<?= (int)$p['tuition_post_id'] ?>"><?= e($p['title']) ?></a></td>
                    <td><?= e($p['student_name']) ?></td>
                    <td><?= e($p['subject_name']) ?></td>
                    <td><span class="badge badge-<?= e($p['status']) ?>"><?= e($p['status']) ?></span></td>
                    <td><?php if ($p['status'] !== 'closed'): ?><a href="?remove_tuition=<?= (int)$p['tuition_post_id'] ?>&csrf_token=<?= e(csrfToken()) ?>&tab=tuition" class="btn btn-danger btn-sm" onclick="return confirm('Close this post?')">Close</a><?php endif; ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$tuitionPosts): ?><tr><td colspan="5" class="empty-state">No tuition posts.</td></tr><?php endif; ?>
            </table>
        <?php else: ?>
            <table>
                <tr><th>Title</th><th>Tutor</th><th>Subject</th><th>Status</th><th>Actions</th></tr>
                <?php foreach ($tutorPosts as $p): ?>
                <tr>
                    <td><?= e($p['title']) ?></td>
                    <td><?= e($p['tutor_name']) ?></td>
                    <td><?= e($p['subject_name']) ?></td>
                    <td><span class="badge badge-<?= e($p['status']) ?>"><?= e($p['status']) ?></span></td>
                    <td><?php if ($p['status'] !== 'closed'): ?><a href="?remove_tutor_post=<?= (int)$p['tutor_post_id'] ?>&csrf_token=<?= e(csrfToken()) ?>&tab=tutor" class="btn btn-danger btn-sm" onclick="return confirm('Close this post?')">Close</a><?php endif; ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$tutorPosts): ?><tr><td colspan="5" class="empty-state">No tutor posts.</td></tr><?php endif; ?>
            </table>
        <?php endif; ?>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
