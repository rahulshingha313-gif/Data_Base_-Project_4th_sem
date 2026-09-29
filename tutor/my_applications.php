<?php
require_once __DIR__ . '/../config/config.php';
requireRole('tutor');

$db = getDB();
$tpId = currentTutorProfileId();

if (isset($_GET['withdraw'])) {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_GET['csrf_token'] ?? '')) die('Invalid request.');
    $id = (int) $_GET['withdraw'];
    $stmt = $db->prepare("UPDATE applications SET status='withdrawn' WHERE application_id = ? AND tutor_profile_id = ? AND status = 'pending'");
    $stmt->execute([$id, $tpId]);
    setFlash('success', 'Application withdrawn.');
    redirect('/tutor/my_applications.php');
}

$applications = $db->prepare(
    "SELECT a.*, tp.title, tp.tuition_post_id, tp.location, tp.budget_min, tp.budget_max, s.subject_name
     FROM applications a
     JOIN tuition_posts tp ON tp.tuition_post_id = a.tuition_post_id
     JOIN subjects s ON s.subject_id = tp.subject_id
     WHERE a.tutor_profile_id = ? ORDER BY a.created_at DESC"
);
$applications->execute([$tpId]);
$applications = $applications->fetchAll();

$pageTitle = 'My Applications';
include __DIR__ . '/../includes/header.php';
?>
<div class="dash-layout">
    <?php include __DIR__ . '/../includes/sidebar_tutor.php'; ?>
    <div>
        <h2>My Applications</h2>
        <div class="card">
            <?php if (!$applications): ?>
                <p class="empty-state">You haven't applied to any tuition posts yet.</p>
            <?php else: ?>
            <table>
                <tr><th>Tuition Post</th><th>Subject</th><th>Proposed</th><th>Status</th><th>Applied</th><th>Actions</th></tr>
                <?php foreach ($applications as $a): ?>
                <tr>
                    <td><a href="<?= BASE_URL ?>/student/view_post.php?id=<?= (int)$a['tuition_post_id'] ?>"><?= e($a['title']) ?></a></td>
                    <td><?= e($a['subject_name']) ?></td>
                    <td><?= money($a['proposed_salary']) ?></td>
                    <td><span class="badge badge-<?= e($a['status']) ?>"><?= e($a['status']) ?></span></td>
                    <td><?= timeAgo($a['created_at']) ?></td>
                    <td>
                        <?php if ($a['status'] === 'pending'): ?>
                            <a href="?withdraw=<?= (int)$a['application_id'] ?>&csrf_token=<?= e(csrfToken()) ?>" class="btn btn-danger btn-sm" onclick="return confirm('Withdraw this application?')">Withdraw</a>
                        <?php elseif ($a['status'] === 'accepted'): ?>
                            <a href="<?= BASE_URL ?>/messages/inbox.php" class="btn btn-outline btn-sm">Message</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
