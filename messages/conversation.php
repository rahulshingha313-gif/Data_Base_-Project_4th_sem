<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

$db = getDB();
$user = currentUser();
$id = (int) ($_GET['id'] ?? 0);

$conv = $db->prepare('SELECT * FROM conversations WHERE conversation_id = ? AND (student_user_id = ? OR tutor_user_id = ?)');
$conv->execute([$id, $user['user_id'], $user['user_id']]);
$conv = $conv->fetch();

if (!$conv) {
    setFlash('error', 'Conversation not found or access denied.');
    redirect('/messages/inbox.php');
}

$otherUserId = $conv['student_user_id'] == $user['user_id'] ? $conv['tutor_user_id'] : $conv['student_user_id'];
$otherUser = $db->prepare('SELECT full_name FROM users WHERE user_id = ?');
$otherUser->execute([$otherUserId]);
$otherUser = $otherUser->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $body = trim($_POST['body'] ?? '');
    if ($body !== '') {
        $db->prepare('INSERT INTO messages (conversation_id, sender_user_id, body) VALUES (?,?,?)')
           ->execute([$id, $user['user_id'], $body]);
        notify($otherUserId, 'new_message', 'New Message', $user['full_name'] . ' sent you a message.', '/messages/conversation.php?id=' . $id);
    }
    redirect('/messages/conversation.php?id=' . $id);
}

// Mark messages from the other user as read
$db->prepare('UPDATE messages SET is_read = 1 WHERE conversation_id = ? AND sender_user_id != ?')->execute([$id, $user['user_id']]);

$messages = $db->prepare('SELECT * FROM messages WHERE conversation_id = ? ORDER BY created_at ASC');
$messages->execute([$id]);
$messages = $messages->fetchAll();

$pageTitle = 'Conversation with ' . $otherUser['full_name'];
include __DIR__ . '/../includes/header.php';
?>
<div class="container mt-3">
    <a href="<?= BASE_URL ?>/messages/inbox.php" class="text-muted">&larr; Back to Inbox</a>
    <div class="card mt-2">
        <h3>Conversation with <?= e($otherUser['full_name']) ?></h3>
        <div style="max-height:420px; overflow-y:auto; display:flex; flex-direction:column; gap:10px; margin:16px 0;">
            <?php foreach ($messages as $m): $mine = $m['sender_user_id'] == $user['user_id']; ?>
                <div style="align-self: <?= $mine ? 'flex-end' : 'flex-start' ?>; max-width:70%;">
                    <div style="background: <?= $mine ? 'rgba(212,175,55,0.15)' : 'var(--bg-navy-light)' ?>; padding:10px 14px; border-radius:10px;">
                        <?= nl2br(e($m['body'])) ?>
                    </div>
                    <div class="text-muted" style="font-size:11px; margin-top:2px; text-align: <?= $mine?'right':'left' ?>;"><?= timeAgo($m['created_at']) ?></div>
                </div>
            <?php endforeach; ?>
            <?php if (!$messages): ?><p class="empty-state">No messages yet. Say hello!</p><?php endif; ?>
        </div>
        <form method="POST" action="">
            <?= csrfField() ?>
            <div style="display:flex; gap:10px;">
                <input type="text" name="body" placeholder="Type a message..." required autofocus>
                <button type="submit" class="btn btn-primary">Send</button>
            </div>
        </form>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
