<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

$db = getDB();
$user = currentUser();

if (isset($_GET['read'])) {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_GET['csrf_token'] ?? '')) die('Invalid request.');
    $db->prepare('UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?')->execute([(int)$_GET['read'], $user['user_id']]);
    $target = $_GET['redirect'] ?? BASE_URL . '/notifications/index.php';
    header('Location: ' . $target);
    exit;
}

if (isset($_GET['mark_all'])) {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_GET['csrf_token'] ?? '')) die('Invalid request.');
    $db->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?')->execute([$user['user_id']]);
    redirect('/notifications/index.php');
}

$notifications = $db->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50');
$notifications->execute([$user['user_id']]);
$notifications = $notifications->fetchAll();

$pageTitle = 'Notifications';
include __DIR__ . '/../includes/header.php';
?>
<div class="container mt-3">
    <div class="flex-between">
        <h2>Notifications</h2>
        <a href="?mark_all=1&csrf_token=<?= e(csrfToken()) ?>" class="btn btn-outline btn-sm">Mark all as read</a>
    </div>
    <div class="card">
        <?php if (!$notifications): ?>
            <p class="empty-state">No notifications yet.</p>
        <?php else: ?>
            <?php foreach ($notifications as $n): ?>
                <a href="?read=<?= (int)$n['notification_id'] ?>&csrf_token=<?= e(csrfToken()) ?>&redirect=<?= urlencode(BASE_URL . $n['related_url']) ?>" style="display:block;color:inherit;">
                    <div style="padding:12px 0; border-bottom:1px solid var(--border-subtle); <?= $n['is_read'] ? 'opacity:0.6;' : '' ?>">
                        <div class="flex-between">
                            <strong><?= e($n['title']) ?></strong>
                            <span class="text-muted"><?= timeAgo($n['created_at']) ?></span>
                        </div>
                        <p class="text-muted" style="margin:4px 0 0;"><?= e($n['message']) ?></p>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
