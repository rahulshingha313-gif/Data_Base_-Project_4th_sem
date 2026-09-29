<?php
require_once __DIR__ . '/../config/config.php';
requireAdmin();

$db = getDB();

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 25;
$total = (int) $db->query('SELECT COUNT(*) FROM admin_actions')->fetchColumn();
[$totalPages, $page, $offset] = paginate($total, $perPage, $page);

$stmt = $db->prepare(
    "SELECT aa.*, u.full_name FROM admin_actions aa JOIN users u ON u.user_id = aa.admin_user_id
     ORDER BY aa.created_at DESC LIMIT $perPage OFFSET $offset"
);
$stmt->execute();
$actions = $stmt->fetchAll();

$pageTitle = 'System Activity Log';
include __DIR__ . '/../includes/header.php';
?>
<div class="dash-layout">
    <?php include __DIR__ . '/../includes/sidebar_admin.php'; ?>
    <div>
        <h2>System Activity Log</h2>
        <div class="card">
            <?php if (!$actions): ?><p class="empty-state">No admin actions recorded yet.</p><?php else: ?>
            <table>
                <tr><th>Admin</th><th>Action</th><th>Target</th><th>Details</th><th>When</th></tr>
                <?php foreach ($actions as $a): ?>
                <tr>
                    <td><?= e($a['full_name']) ?></td>
                    <td><?= e($a['action_type']) ?></td>
                    <td><?= e($a['target_type'] ?? '-') ?> <?= $a['target_id'] ? '#'.(int)$a['target_id'] : '' ?></td>
                    <td><?= e($a['details'] ?? '-') ?></td>
                    <td><?= timeAgo($a['created_at']) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php endif; ?>
        </div>
        <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <a href="?page=<?= $i ?>" class="<?= $i==$page?'active':'' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
