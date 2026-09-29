<?php
require_once __DIR__ . '/../config/config.php';
requireRole('tutor');

$db = getDB();
$user = currentUser();
$tpId = currentTutorProfileId();

$acceptedPosts = $db->prepare(
    "SELECT DISTINCT tp.tuition_post_id, tp.title, sp.user_id AS student_user_id
     FROM applications a
     JOIN tuition_posts tp ON tp.tuition_post_id = a.tuition_post_id
     JOIN student_profiles sp ON sp.student_profile_id = tp.student_profile_id
     WHERE a.tutor_profile_id = ? AND a.status = 'accepted'"
);
$acceptedPosts->execute([$tpId]);
$acceptedPosts = $acceptedPosts->fetchAll();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $postId = (int) $_POST['tuition_post_id'];
    $amount = (float) $_POST['amount'];
    $month = trim($_POST['for_month'] ?? '');

    $match = null;
    foreach ($acceptedPosts as $p) if ($p['tuition_post_id'] == $postId) $match = $p;

    if (!$match) $errors[] = 'Invalid tuition selected.';
    if ($amount <= 0) $errors[] = 'Amount must be greater than zero.';
    if ($month === '') $errors[] = 'Please specify the month.';

    if (!$errors) {
        $db->prepare(
            'INSERT INTO payments (tuition_post_id, student_user_id, tutor_user_id, amount, for_month, status) VALUES (?,?,?,?,?,\'pending\')'
        )->execute([$postId, $match['student_user_id'], $user['user_id'], $amount, $month]);

        notify($match['student_user_id'], 'payment_due', 'Payment Due', 'A payment of ' . money($amount) . ' for ' . $month . ' is due for "' . $match['title'] . '".', '/payments/student_view.php');

        setFlash('success', 'Payment record created.');
        redirect('/payments/tutor_view.php');
    }
}

$pageTitle = 'Add Payment Record';
include __DIR__ . '/../includes/header.php';
?>
<div class="dash-layout">
    <?php include __DIR__ . '/../includes/sidebar_tutor.php'; ?>
    <div>
        <div class="card">
            <h2>Create Monthly Payment Record</h2>
            <?php foreach ($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>
            <?php if (!$acceptedPosts): ?>
                <p class="empty-state">You need at least one accepted tuition to create a payment record.</p>
            <?php else: ?>
            <form method="POST" action="">
                <?= csrfField() ?>
                <label>Tuition</label>
                <select name="tuition_post_id" required>
                    <?php foreach ($acceptedPosts as $p): ?>
                        <option value="<?= (int)$p['tuition_post_id'] ?>"><?= e($p['title']) ?></option>
                    <?php endforeach; ?>
                </select>
                <div class="form-row">
                    <div><label>Amount (BDT)</label><input type="number" step="0.01" name="amount" required></div>
                    <div><label>For Month</label><input type="text" name="for_month" placeholder="e.g. October 2026" required></div>
                </div>
                <button type="submit" class="btn btn-primary mt-3">Create Payment Record</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
