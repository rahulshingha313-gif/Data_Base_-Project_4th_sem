<?php
require_once __DIR__ . '/../config/config.php';
requireAdmin();

$db = getDB();
$admin = currentUser();

if (isset($_GET['verify'])) {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_GET['csrf_token'] ?? '')) die('Invalid request.');
    $id = (int) $_GET['verify'];
    $db->prepare('UPDATE tutor_profiles SET is_profile_verified = 1 WHERE tutor_profile_id = ?')->execute([$id]);
    logAdminAction($admin['user_id'], 'verify_tutor', 'tutor_profile', $id, 'Verified tutor profile.');
    setFlash('success', 'Tutor verified successfully.');
    redirect('/admin/tutor_verification.php');
}

if (isset($_GET['unverify'])) {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_GET['csrf_token'] ?? '')) die('Invalid request.');
    $id = (int) $_GET['unverify'];
    $db->prepare('UPDATE tutor_profiles SET is_profile_verified = 0 WHERE tutor_profile_id = ?')->execute([$id]);
    logAdminAction($admin['user_id'], 'unverify_tutor', 'tutor_profile', $id, 'Removed tutor verification.');
    setFlash('success', 'Verification removed.');
    redirect('/admin/tutor_verification.php');
}

$tutors = $db->query(
    "SELECT tpf.*, u.full_name, u.email FROM tutor_profiles tpf JOIN users u ON u.user_id = tpf.user_id ORDER BY tpf.is_profile_verified ASC, tpf.created_at DESC"
)->fetchAll();

$pageTitle = 'Verify Tutors';
include __DIR__ . '/../includes/header.php';
?>
<div class="dash-layout">
    <?php include __DIR__ . '/../includes/sidebar_admin.php'; ?>
    <div>
        <h2>Tutor Verification</h2>
        <div class="card">
            <table>
                <tr><th>Name</th><th>Education</th><th>Experience</th><th>Rating</th><th>Status</th><th>Actions</th></tr>
                <?php foreach ($tutors as $t): ?>
                <tr>
                    <td><?= e($t['full_name']) ?></td>
                    <td><?= e($t['education'] ?: '-') ?></td>
                    <td><?= e($t['experience_years']) ?> yrs</td>
                    <td><?= e($t['avg_rating']) ?>/5</td>
                    <td><?php if ($t['is_profile_verified']): ?><span class="badge badge-active">Verified</span><?php else: ?><span class="badge badge-pending">Unverified</span><?php endif; ?></td>
                    <td>
                        <?php if ($t['is_profile_verified']): ?>
                            <a href="?unverify=<?= (int)$t['tutor_profile_id'] ?>&csrf_token=<?= e(csrfToken()) ?>" class="btn btn-danger btn-sm">Unverify</a>
                        <?php else: ?>
                            <a href="?verify=<?= (int)$t['tutor_profile_id'] ?>&csrf_token=<?= e(csrfToken()) ?>" class="btn btn-primary btn-sm">Verify</a>
                        <?php endif; ?>
                        <a href="<?= BASE_URL ?>/admin/view_tutor.php?user_id=<?= (int)$t['user_id'] ?>" class="btn btn-secondary btn-sm">View</a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$tutors): ?><tr><td colspan="6" class="empty-state">No tutors registered yet.</td></tr><?php endif; ?>
            </table>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
