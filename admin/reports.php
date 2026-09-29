<?php
require_once __DIR__ . '/../config/config.php';
requireAdmin();

$db = getDB();
$admin = currentUser();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $id = (int) $_POST['report_id'];
    $status = $_POST['status'];
    $response = trim($_POST['admin_response'] ?? '');

    if (in_array($status, ['pending','reviewing','resolved','rejected'], true)) {
        $db->prepare('UPDATE reports SET status=?, admin_response=? WHERE report_id=?')->execute([$status, $response, $id]);
        logAdminAction($admin['user_id'], 'update_report', 'report', $id, 'Status set to ' . $status);

        $reporter = $db->prepare('SELECT reporter_user_id FROM reports WHERE report_id = ?');
        $reporter->execute([$id]);
        if ($row = $reporter->fetch()) {
            notify($row['reporter_user_id'], 'report_status_changed', 'Report Status Updated', 'Your report status changed to ' . $status . '.', '/index.php');
        }
        setFlash('success', 'Report updated.');
    }
    redirect('/admin/reports.php');
}

$statusFilter = $_GET['status'] ?? '';
$where = '1=1';
$params = [];
if ($statusFilter !== '') { $where = 'r.status = ?'; $params[] = $statusFilter; }

$stmt = $db->prepare(
    "SELECT r.*, u.full_name AS reporter_name, ru.full_name AS reported_name, tp.title
     FROM reports r
     JOIN users u ON u.user_id = r.reporter_user_id
     LEFT JOIN users ru ON ru.user_id = r.reported_user_id
     LEFT JOIN tuition_posts tp ON tp.tuition_post_id = r.related_tuition_post_id
     WHERE $where ORDER BY r.created_at DESC"
);
$stmt->execute($params);
$reports = $stmt->fetchAll();

$pageTitle = 'Reports & Complaints';
include __DIR__ . '/../includes/header.php';
?>
<div class="dash-layout">
    <?php include __DIR__ . '/../includes/sidebar_admin.php'; ?>
    <div>
        <h2>Reports &amp; Complaints</h2>
        <div class="filter-bar">
            <?php foreach (['' => 'All', 'pending' => 'Pending', 'reviewing' => 'Reviewing', 'resolved' => 'Resolved', 'rejected' => 'Rejected'] as $val => $label): ?>
                <a href="?status=<?= $val ?>" class="btn <?= $statusFilter==$val?'btn-primary':'btn-secondary' ?> btn-sm"><?= $label ?></a>
            <?php endforeach; ?>
        </div>

        <?php if (!$reports): ?>
            <div class="card"><p class="empty-state">No reports found.</p></div>
        <?php else: ?>
            <?php foreach ($reports as $r): ?>
            <div class="card">
                <div class="flex-between">
                    <div>
                        <strong><?= e($r['reporter_name']) ?></strong> reported
                        <?= $r['reported_name'] ? e($r['reported_name']) : ($r['title'] ? 'post "' . e($r['title']) . '"' : 'an item') ?>
                    </div>
                    <span class="badge badge-<?= e($r['status']) ?>"><?= e($r['status']) ?></span>
                </div>
                <p class="mt-1"><strong>Reason:</strong> <?= e($r['reason']) ?></p>
                <p><?= nl2br(e($r['description'])) ?></p>
                <p class="text-muted"><?= timeAgo($r['created_at']) ?></p>
                <form method="POST" action="" class="filter-bar mt-2">
                    <?= csrfField() ?>
                    <input type="hidden" name="report_id" value="<?= (int)$r['report_id'] ?>">
                    <div>
                        <label>Status</label>
                        <select name="status">
                            <?php foreach (['pending','reviewing','resolved','rejected'] as $st): ?>
                                <option value="<?= $st ?>" <?= $r['status']==$st?'selected':'' ?>><?= ucfirst($st) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div style="flex:2;"><label>Admin Response</label><input type="text" name="admin_response" value="<?= e($r['admin_response'] ?? '') ?>"></div>
                    <div><button type="submit" class="btn btn-primary btn-block">Update</button></div>
                </form>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
