<?php
require_once __DIR__ . '/../config/config.php';
requireAdmin();

$db = getDB();

$statusFilter = $_GET['status'] ?? '';
$where = '1=1';
$params = [];
if ($statusFilter !== '') { $where = 'a.status = ?'; $params[] = $statusFilter; }

$stmt = $db->prepare(
    "SELECT a.*, tp.title, tu.full_name AS tutor_name, su.full_name AS student_name
     FROM applications a
     JOIN tuition_posts tp ON tp.tuition_post_id = a.tuition_post_id
     JOIN tutor_profiles tpf ON tpf.tutor_profile_id = a.tutor_profile_id
     JOIN users tu ON tu.user_id = tpf.user_id
     JOIN student_profiles sp ON sp.student_profile_id = tp.student_profile_id
     JOIN users su ON su.user_id = sp.user_id
     WHERE $where ORDER BY a.created_at DESC LIMIT 200"
);
$stmt->execute($params);
$applications = $stmt->fetchAll();

$pageTitle = 'Applications Overview';
include __DIR__ . '/../includes/header.php';
?>
<div class="dash-layout">
    <?php include __DIR__ . '/../includes/sidebar_admin.php'; ?>
    <div>
        <h2>Applications Overview</h2>
        <div class="filter-bar">
            <?php foreach (['' => 'All', 'pending' => 'Pending', 'accepted' => 'Accepted', 'rejected' => 'Rejected', 'withdrawn' => 'Withdrawn'] as $val => $label): ?>
                <a href="?status=<?= $val ?>" class="btn <?= $statusFilter==$val?'btn-primary':'btn-secondary' ?> btn-sm"><?= $label ?></a>
            <?php endforeach; ?>
        </div>
        <div class="card">
            <table>
                <tr><th>Tuition</th><th>Tutor</th><th>Student</th><th>Proposed</th><th>Status</th><th>Date</th></tr>
                <?php foreach ($applications as $a): ?>
                <tr>
                    <td><?= e($a['title']) ?></td>
                    <td><?= e($a['tutor_name']) ?></td>
                    <td><?= e($a['student_name']) ?></td>
                    <td><?= money($a['proposed_salary']) ?></td>
                    <td><span class="badge badge-<?= e($a['status']) ?>"><?= e($a['status']) ?></span></td>
                    <td><?= timeAgo($a['created_at']) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$applications): ?><tr><td colspan="6" class="empty-state">No applications found.</td></tr><?php endif; ?>
            </table>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
