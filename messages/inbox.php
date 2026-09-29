<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

$db = getDB();
$user = currentUser();

$conversations = $db->prepare(
    "SELECT c.*,
        CASE WHEN c.student_user_id = ? THEN tu.full_name ELSE su.full_name END AS other_name,
        CASE WHEN c.student_user_id = ? THEN c.tutor_user_id ELSE c.student_user_id END AS other_user_id,
        (SELECT body FROM messages m WHERE m.conversation_id = c.conversation_id ORDER BY m.created_at DESC LIMIT 1) AS last_message,
        (SELECT created_at FROM messages m WHERE m.conversation_id = c.conversation_id ORDER BY m.created_at DESC LIMIT 1) AS last_time,
        (SELECT COUNT(*) FROM messages m WHERE m.conversation_id = c.conversation_id AND m.is_read = 0 AND m.sender_user_id != ?) AS unread_count
     FROM conversations c
     JOIN users su ON su.user_id = c.student_user_id
     JOIN users tu ON tu.user_id = c.tutor_user_id
     WHERE c.student_user_id = ? OR c.tutor_user_id = ?
     ORDER BY last_time DESC"
);
$conversations->execute([$user['user_id'], $user['user_id'], $user['user_id'], $user['user_id'], $user['user_id']]);
$conversations = $conversations->fetchAll();

$pageTitle = 'Messages';
include __DIR__ . '/../includes/header.php';
$sidebarFile = $user['role'] === 'admin' ? null : (hasRole('tutor') ? 'sidebar_tutor.php' : 'sidebar_student.php');
?>
<div class="dash-layout">
    <?php if ($sidebarFile) include __DIR__ . '/../includes/' . $sidebarFile; ?>
    <div>
        <h2>Messages</h2>
        <div class="card">
            <?php if (!$conversations): ?>
                <p class="empty-state">No conversations yet. Conversations start automatically once a tutor's application is accepted.</p>
            <?php else: ?>
                <?php foreach ($conversations as $c): ?>
                <a href="<?= BASE_URL ?>/messages/conversation.php?id=<?= (int)$c['conversation_id'] ?>" style="display:block;color:inherit;">
                    <div class="flex-between" style="padding:12px 0; border-bottom:1px solid var(--border-subtle);">
                        <div>
                            <strong><?= e($c['other_name']) ?></strong>
                            <?php if ($c['unread_count'] > 0): ?><span class="nav-badge"><?= (int)$c['unread_count'] ?></span><?php endif; ?>
                            <p class="text-muted" style="margin:4px 0 0;"><?= e(mb_strimwidth($c['last_message'] ?? 'No messages yet', 0, 80, '...')) ?></p>
                        </div>
                        <span class="text-muted"><?= $c['last_time'] ? timeAgo($c['last_time']) : '' ?></span>
                    </div>
                </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
