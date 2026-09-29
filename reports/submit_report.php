<?php
require_once __DIR__ . '/../config/config.php';
requireLogin();

$db = getDB();
$user = currentUser();

$postId = (int) ($_GET['post_id'] ?? $_POST['related_tuition_post_id'] ?? 0);
$reportedUserId = (int) ($_GET['user_id'] ?? $_POST['reported_user_id'] ?? 0);

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $reason = trim($_POST['reason'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($reason === '') $errors[] = 'Please select or enter a reason.';
    if ($description === '') $errors[] = 'Please describe the issue.';

    if (!$errors) {
        $db->prepare(
            'INSERT INTO reports (reporter_user_id, reported_user_id, related_tuition_post_id, reason, description) VALUES (?,?,?,?,?)'
        )->execute([$user['user_id'], $reportedUserId ?: null, $postId ?: null, $reason, $description]);
        setFlash('success', 'Your report has been submitted. Our admin team will review it shortly.');
        redirect('/index.php');
    }
}

$pageTitle = 'Report a Problem';
include __DIR__ . '/../includes/header.php';
$sidebarFile = hasRole('tutor') ? 'sidebar_tutor.php' : 'sidebar_student.php';
?>
<div class="dash-layout">
    <?php include __DIR__ . '/../includes/' . $sidebarFile; ?>
    <div>
        <div class="card">
            <h2>Report a Problem</h2>
            <p class="text-muted">Report a user, a tuition post, or an issue with a message.</p>
            <?php foreach ($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>
            <form method="POST" action="">
                <?= csrfField() ?>
                <input type="hidden" name="related_tuition_post_id" value="<?= $postId ?>">
                <input type="hidden" name="reported_user_id" value="<?= $reportedUserId ?>">
                <label>Reason</label>
                <select name="reason" required>
                    <option value="">Select a reason</option>
                    <option value="Fake or suspicious post">Fake or suspicious post</option>
                    <option value="Inappropriate behavior">Inappropriate behavior</option>
                    <option value="Payment dispute">Payment dispute</option>
                    <option value="Harassment">Harassment</option>
                    <option value="Other">Other</option>
                </select>
                <label>Description</label>
                <textarea name="description" placeholder="Please provide details" required></textarea>
                <button type="submit" class="btn btn-primary mt-3">Submit Report</button>
            </form>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
