<?php
require_once __DIR__ . '/../config/config.php';
requireRole('student');

$db = getDB();
$user = currentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pay'])) {
    verifyCsrf();
    $id = (int) $_POST['payment_id'];
    $method = $_POST['method'] ?? 'cash';
    $reference = trim($_POST['transaction_reference'] ?? '');

    $stmt = $db->prepare("UPDATE payments SET status='paid', payment_date=CURDATE(), method=?, transaction_reference=? WHERE payment_id=? AND student_user_id=?");
    $stmt->execute([$method, $reference, $id, $user['user_id']]);

    $p = $db->prepare('SELECT tutor_user_id, amount FROM payments WHERE payment_id = ?');
    $p->execute([$id]);
    if ($row = $p->fetch()) {
        notify($row['tutor_user_id'], 'payment_received', 'Payment Marked as Paid', 'A payment of ' . money($row['amount']) . ' was marked as paid by the student.', '/payments/tutor_view.php');
    }
    setFlash('success', 'Payment recorded. Thank you!');
    redirect('/payments/student_view.php');
}

$payments = $db->prepare(
    "SELECT p.*, tp.title, u.full_name AS tutor_name FROM payments p
     JOIN tuition_posts tp ON tp.tuition_post_id = p.tuition_post_id
     JOIN users u ON u.user_id = p.tutor_user_id
     WHERE p.student_user_id = ? ORDER BY p.created_at DESC"
);
$payments->execute([$user['user_id']]);
$payments = $payments->fetchAll();

$pageTitle = 'Payments';
include __DIR__ . '/../includes/header.php';
?>
<div class="dash-layout">
    <?php include __DIR__ . '/../includes/sidebar_student.php'; ?>
    <div>
        <h2>Payment History</h2>
        <div class="card">
            <?php if (!$payments): ?>
                <p class="empty-state">No payment records yet.</p>
            <?php else: ?>
            <table>
                <tr><th>Tutor</th><th>Tuition</th><th>Month</th><th>Amount</th><th>Status</th><th>Actions</th></tr>
                <?php foreach ($payments as $p): ?>
                <tr>
                    <td><?= e($p['tutor_name']) ?></td>
                    <td><?= e($p['title']) ?></td>
                    <td><?= e($p['for_month']) ?></td>
                    <td><?= money($p['amount']) ?></td>
                    <td><span class="badge badge-<?= e($p['status']) ?>"><?= e($p['status']) ?></span></td>
                    <td>
                        <?php if ($p['status'] !== 'paid'): ?>
                        <button class="btn btn-primary btn-sm" onclick="document.getElementById('pay-<?= $p['payment_id'] ?>').style.display='block'">Mark as Paid</button>
                        <?php else: ?>
                            <span class="text-muted"><?= e($p['method'] ?? '') ?> <?= e($p['transaction_reference'] ?? '') ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php if ($p['status'] !== 'paid'): ?>
                <tr id="pay-<?= $p['payment_id'] ?>" style="display:none;">
                    <td colspan="6">
                        <form method="POST" action="" class="filter-bar">
                            <?= csrfField() ?>
                            <input type="hidden" name="payment_id" value="<?= (int)$p['payment_id'] ?>">
                            <div>
                                <label>Payment Method</label>
                                <select name="method">
                                    <option value="cash">Cash</option>
                                    <option value="bkash">bKash</option>
                                    <option value="nagad">Nagad</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div><label>Transaction Reference</label><input type="text" name="transaction_reference" placeholder="Optional"></div>
                            <div><button type="submit" name="pay" value="1" class="btn btn-primary btn-block">Confirm Payment</button></div>
                        </form>
                    </td>
                </tr>
                <?php endif; ?>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
