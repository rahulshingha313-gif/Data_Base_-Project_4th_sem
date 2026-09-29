<?php
require_once __DIR__ . '/../config/config.php';
requireRole('tutor');

$db = getDB();
$user = currentUser();

// Auto-mark overdue: pending payments where the month has clearly passed are flagged overdue when viewed
if (isset($_GET['mark_overdue'])) {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_GET['csrf_token'] ?? '')) die('Invalid request.');
    $db->prepare("UPDATE payments SET status='overdue' WHERE payment_id = ? AND tutor_user_id = ? AND status='pending'")
       ->execute([(int)$_GET['mark_overdue'], $user['user_id']]);
    setFlash('success', 'Payment marked as overdue.');
    redirect('/payments/tutor_view.php');
}

if (isset($_GET['confirm_paid'])) {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_GET['csrf_token'] ?? '')) die('Invalid request.');
    $id = (int) $_GET['confirm_paid'];
    $stmt = $db->prepare("UPDATE payments SET status='paid', payment_date=CURDATE() WHERE payment_id = ? AND tutor_user_id = ?");
    $stmt->execute([$id, $user['user_id']]);
    $p = $db->prepare('SELECT student_user_id, amount FROM payments WHERE payment_id = ?');
    $p->execute([$id]);
    if ($row = $p->fetch()) {
        notify($row['student_user_id'], 'payment_received', 'Payment Confirmed', 'Your payment of ' . money($row['amount']) . ' has been confirmed by the tutor.', '/payments/student_view.php');
    }
    setFlash('success', 'Payment confirmed as received.');
    redirect('/payments/tutor_view.php');
}

$payments = $db->prepare(
    "SELECT p.*, tp.title, u.full_name AS student_name FROM payments p
     JOIN tuition_posts tp ON tp.tuition_post_id = p.tuition_post_id
     JOIN users u ON u.user_id = p.student_user_id
     WHERE p.tutor_user_id = ? ORDER BY p.created_at DESC"
);
$payments->execute([$user['user_id']]);
$payments = $payments->fetchAll();

$pageTitle = 'Payments';
include __DIR__ . '/../includes/header.php';
?>
<div class="dash-layout">
    <?php include __DIR__ . '/../includes/sidebar_tutor.php'; ?>
    <div>
        <div class="flex-between">
            <h2>Payment History</h2>
            <a href="<?= BASE_URL ?>/payments/add.php" class="btn btn-primary btn-sm">+ New Payment Record</a>
        </div>
        <div class="card">
            <?php if (!$payments): ?>
                <p class="empty-state">No payment records yet.</p>
            <?php else: ?>
            <table>
                <tr><th>Student</th><th>Tuition</th><th>Month</th><th>Amount</th><th>Status</th><th>Method</th><th>Actions</th></tr>
                <?php foreach ($payments as $p): ?>
                <tr>
                    <td><?= e($p['student_name']) ?></td>
                    <td><?= e($p['title']) ?></td>
                    <td><?= e($p['for_month']) ?></td>
                    <td><?= money($p['amount']) ?></td>
                    <td><span class="badge badge-<?= e($p['status']) ?>"><?= e($p['status']) ?></span></td>
                    <td><?= $p['method'] ? ucfirst(e($p['method'])) : '-' ?></td>
                    <td>
                        <?php if ($p['status'] === 'pending'): ?>
                            <a href="?confirm_paid=<?= (int)$p['payment_id'] ?>&csrf_token=<?= e(csrfToken()) ?>" class="btn btn-primary btn-sm">Confirm Received</a>
                            <a href="?mark_overdue=<?= (int)$p['payment_id'] ?>&csrf_token=<?= e(csrfToken()) ?>" class="btn btn-danger btn-sm">Mark Overdue</a>
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
