<?php
require_once __DIR__ . '/../config/config.php';
requireAdmin();

$db = getDB();

$statusFilter = $_GET['status'] ?? '';
$where = '1=1';
$params = [];
if ($statusFilter !== '') { $where = 'p.status = ?'; $params[] = $statusFilter; }

$stmt = $db->prepare(
    "SELECT p.*, tp.title, su.full_name AS student_name, tu.full_name AS tutor_name
     FROM payments p
     JOIN tuition_posts tp ON tp.tuition_post_id = p.tuition_post_id
     JOIN users su ON su.user_id = p.student_user_id
     JOIN users tu ON tu.user_id = p.tutor_user_id
     WHERE $where ORDER BY p.created_at DESC LIMIT 200"
);
$stmt->execute($params);
$payments = $stmt->fetchAll();

$totals = $db->query("SELECT status, SUM(amount) total, COUNT(*) c FROM payments GROUP BY status")->fetchAll();

$pageTitle = 'Payments Overview';
include __DIR__ . '/../includes/header.php';
?>
<div class="dash-layout">
    <?php include __DIR__ . '/../includes/sidebar_admin.php'; ?>
    <div>
        <h2>Payments Overview</h2>
        <div class="grid grid-3">
            <?php foreach ($totals as $t): ?>
            <div class="card stat-card">
                <div class="value"><?= money($t['total']) ?></div>
                <div class="label"><?= ucfirst(e($t['status'])) ?> (<?= (int)$t['c'] ?> records)</div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="filter-bar mt-2">
            <?php foreach (['' => 'All', 'pending' => 'Pending', 'paid' => 'Paid', 'overdue' => 'Overdue'] as $val => $label): ?>
                <a href="?status=<?= $val ?>" class="btn <?= $statusFilter==$val?'btn-primary':'btn-secondary' ?> btn-sm"><?= $label ?></a>
            <?php endforeach; ?>
        </div>

        <div class="card">
            <table>
                <tr><th>Tuition</th><th>Student</th><th>Tutor</th><th>Month</th><th>Amount</th><th>Status</th><th>Method</th></tr>
                <?php foreach ($payments as $p): ?>
                <tr>
                    <td><?= e($p['title']) ?></td>
                    <td><?= e($p['student_name']) ?></td>
                    <td><?= e($p['tutor_name']) ?></td>
                    <td><?= e($p['for_month']) ?></td>
                    <td><?= money($p['amount']) ?></td>
                    <td><span class="badge badge-<?= e($p['status']) ?>"><?= e($p['status']) ?></span></td>
                    <td><?= $p['method'] ? ucfirst(e($p['method'])) : '-' ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$payments): ?><tr><td colspan="7" class="empty-state">No payment records.</td></tr><?php endif; ?>
            </table>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
