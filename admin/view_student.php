<?php
require_once __DIR__ . '/../config/config.php';
requireAdmin();

$db = getDB();
$userId = (int) ($_GET['user_id'] ?? 0);

$profile = $db->prepare('SELECT sp.*, u.full_name, u.email, u.phone, u.status FROM student_profiles sp JOIN users u ON u.user_id = sp.user_id WHERE sp.user_id = ?');
$profile->execute([$userId]);
$profile = $profile->fetch();

if (!$profile) { setFlash('error', 'Student profile not found.'); redirect('/admin/users.php'); }

$posts = $db->prepare('SELECT * FROM tuition_posts WHERE student_profile_id = ? ORDER BY created_at DESC');
$posts->execute([$profile['student_profile_id']]);
$posts = $posts->fetchAll();

$pageTitle = 'Student Profile — ' . $profile['full_name'];
include __DIR__ . '/../includes/header.php';
?>
<div class="dash-layout">
    <?php include __DIR__ . '/../includes/sidebar_admin.php'; ?>
    <div>
        <a href="<?= BASE_URL ?>/admin/users.php" class="text-muted">&larr; Back to Users</a>
        <div class="card mt-2">
            <h2><?= e($profile['full_name']) ?></h2>
            <p class="text-muted"><?= e($profile['email']) ?> &middot; <?= e($profile['phone'] ?? '') ?></p>
            <div class="grid grid-3 mt-2">
                <div><strong>Class:</strong> <?= e($profile['class_level'] ?? '-') ?></div>
                <div><strong>Institution:</strong> <?= e($profile['institution'] ?? '-') ?></div>
                <div><strong>Address:</strong> <?= e($profile['address'] ?? '-') ?></div>
                <div><strong>Guardian:</strong> <?= e($profile['guardian_name'] ?? '-') ?></div>
                <div><strong>Guardian Phone:</strong> <?= e($profile['guardian_phone'] ?? '-') ?></div>
                <div><strong>Status:</strong> <span class="badge badge-<?= e($profile['status']) ?>"><?= e($profile['status']) ?></span></div>
            </div>
            <p class="mt-2"><?= nl2br(e($profile['bio'] ?? '')) ?></p>
        </div>
        <div class="card mt-2">
            <h3>Tuition Posts (<?= count($posts) ?>)</h3>
            <?php if (!$posts): ?><p class="empty-state">No posts.</p><?php else: ?>
            <table>
                <tr><th>Title</th><th>Status</th><th>Created</th></tr>
                <?php foreach ($posts as $p): ?>
                <tr><td><?= e($p['title']) ?></td><td><span class="badge badge-<?= e($p['status']) ?>"><?= e($p['status']) ?></span></td><td><?= timeAgo($p['created_at']) ?></td></tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
