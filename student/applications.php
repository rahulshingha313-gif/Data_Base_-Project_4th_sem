<?php
require_once __DIR__ . '/../config/config.php';
requireRole('student');

$db = getDB();
$spId = currentStudentProfileId();
$user = currentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verifyCsrf();
    $appId = (int) $_POST['application_id'];
    $postId = (int) $_POST['post_id'];
    $action = $_POST['action'];

    // Ownership check: the post must belong to this student.
    $check = $db->prepare('SELECT * FROM tuition_posts WHERE tuition_post_id = ? AND student_profile_id = ?');
    $check->execute([$postId, $spId]);
    $post = $check->fetch();

    if (!$post) {
        setFlash('error', 'You do not have permission to modify this application.');
        redirect('/student/applications.php');
    }

    $appStmt = $db->prepare(
        "SELECT a.*, tpf.user_id AS tutor_user_id FROM applications a
         JOIN tutor_profiles tpf ON tpf.tutor_profile_id = a.tutor_profile_id
         WHERE a.application_id = ? AND a.tuition_post_id = ?"
    );
    $appStmt->execute([$appId, $postId]);
    $app = $appStmt->fetch();

    if ($app && $action === 'accept') {
        $db->prepare("UPDATE applications SET status='accepted' WHERE application_id = ?")->execute([$appId]);
        $db->prepare("UPDATE tuition_posts SET status='in_progress' WHERE tuition_post_id = ?")->execute([$postId]);

        // Create a conversation between student and tutor if one doesn't exist
        $convCheck = $db->prepare('SELECT conversation_id FROM conversations WHERE application_id = ?');
        $convCheck->execute([$appId]);
        if (!$convCheck->fetch()) {
            $db->prepare('INSERT INTO conversations (application_id, student_user_id, tutor_user_id) VALUES (?,?,?)')
               ->execute([$appId, $user['user_id'], $app['tutor_user_id']]);
        }

        notify($app['tutor_user_id'], 'application_accepted', 'Application Accepted',
            'Your application for "' . $post['title'] . '" was accepted.', '/tutor/my_applications.php');

        setFlash('success', 'Application accepted. A conversation has been started.');
    } elseif ($app && $action === 'reject') {
        $db->prepare("UPDATE applications SET status='rejected' WHERE application_id = ?")->execute([$appId]);
        notify($app['tutor_user_id'], 'application_rejected', 'Application Rejected',
            'Your application for "' . $post['title'] . '" was rejected.', '/tutor/my_applications.php');
        setFlash('success', 'Application rejected.');
    }
    redirect('/student/applications.php' . ($postId ? '?post_id=' . $postId : ''));
}

$postId = (int) ($_GET['post_id'] ?? 0);
$sql = "SELECT a.*, tp.title, tp.tuition_post_id, tpf.education, tpf.experience_years, tpf.avg_rating, u.full_name AS tutor_name
        FROM applications a
        JOIN tuition_posts tp ON tp.tuition_post_id = a.tuition_post_id
        JOIN tutor_profiles tpf ON tpf.tutor_profile_id = a.tutor_profile_id
        JOIN users u ON u.user_id = tpf.user_id
        WHERE tp.student_profile_id = ?";
$params = [$spId];
if ($postId) { $sql .= ' AND tp.tuition_post_id = ?'; $params[] = $postId; }
$sql .= ' ORDER BY a.created_at DESC';
$stmt = $db->prepare($sql);
$stmt->execute($params);
$applications = $stmt->fetchAll();

$pageTitle = 'Applications Received';
include __DIR__ . '/../includes/header.php';
?>
<div class="dash-layout">
    <?php include __DIR__ . '/../includes/sidebar_student.php'; ?>
    <div>
        <h2>Applications Received</h2>
        <div class="card">
            <?php if (!$applications): ?>
                <p class="empty-state">No applications yet.</p>
            <?php else: ?>
                <?php foreach ($applications as $a): ?>
                <div class="card" style="background:var(--bg-navy-light)">
                    <div class="flex-between">
                        <div>
                            <strong><?= e($a['tutor_name']) ?></strong>
                            <span class="text-muted">applied to "<?= e($a['title']) ?>"</span>
                        </div>
                        <span class="badge badge-<?= e($a['status']) ?>"><?= e($a['status']) ?></span>
                    </div>
                    <p class="text-muted mt-1"><?= e($a['experience_years']) ?> yrs experience &middot; rating <?= e($a['avg_rating']) ?>/5</p>
                    <p><?= nl2br(e($a['message'])) ?></p>
                    <p>Proposed salary: <?= money($a['proposed_salary']) ?> &middot; <?= timeAgo($a['created_at']) ?></p>
                    <?php if ($a['status'] === 'pending'): ?>
                    <form method="POST" action="">
                        <?= csrfField() ?>
                        <input type="hidden" name="application_id" value="<?= (int)$a['application_id'] ?>">
                        <input type="hidden" name="post_id" value="<?= (int)$a['tuition_post_id'] ?>">
                        <button type="submit" name="action" value="accept" class="btn btn-primary btn-sm">Accept</button>
                        <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm">Reject</button>
                    </form>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
