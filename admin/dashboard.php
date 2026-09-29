<?php
require_once __DIR__ . '/../config/config.php';
requireAdmin();

$db = getDB();

$stats = [
    'total_users'        => $db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'total_students'     => $db->query("SELECT COUNT(*) FROM users WHERE role IN ('student','both')")->fetchColumn(),
    'total_tutors'       => $db->query("SELECT COUNT(*) FROM users WHERE role IN ('tutor','both')")->fetchColumn(),
    'active_tuition'     => $db->query("SELECT COUNT(*) FROM tuition_posts WHERE status = 'open'")->fetchColumn(),
    'active_tutor_posts' => $db->query("SELECT COUNT(*) FROM tutor_posts WHERE status = 'active'")->fetchColumn(),
    'pending_apps'       => $db->query("SELECT COUNT(*) FROM applications WHERE status = 'pending'")->fetchColumn(),
    'completed_tuitions' => $db->query("SELECT COUNT(*) FROM tuition_schedule WHERE status = 'completed'")->fetchColumn(),
    'total_payments'     => $db->query("SELECT COALESCE(SUM(amount),0) FROM payments WHERE status = 'paid'")->fetchColumn(),
    'pending_payments'   => $db->query("SELECT COUNT(*) FROM payments WHERE status IN ('pending','overdue')")->fetchColumn(),
    'pending_reports'    => $db->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'")->fetchColumn(),
];

$recentActivity = $db->query(
    "SELECT aa.*, u.full_name FROM admin_actions aa JOIN users u ON u.user_id = aa.admin_user_id ORDER BY aa.created_at DESC LIMIT 8"
)->fetchAll();

$recentUsers = $db->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll();

$pageTitle = 'Admin Dashboard';
include __DIR__ . '/../includes/header.php';
?>
<div class="dash-layout">
    <?php include __DIR__ . '/../includes/sidebar_admin.php'; ?>
    <div>
        <h2>Admin Dashboard</h2>

        <div class="grid grid-4">
            <div class="card stat-card"><div class="value"><?= (int)$stats['total_users'] ?></div><div class="label">Total Users</div></div>
            <div class="card stat-card"><div class="value"><?= (int)$stats['total_students'] ?></div><div class="label">Total Students</div></div>
            <div class="card stat-card"><div class="value"><?= (int)$stats['total_tutors'] ?></div><div class="label">Total Tutors</div></div>
            <div class="card stat-card"><div class="value"><?= (int)$stats['active_tuition'] ?></div><div class="label">Active Tuition Posts</div></div>
            <div class="card stat-card"><div class="value"><?= (int)$stats['active_tutor_posts'] ?></div><div class="label">Active Tutor Posts</div></div>
            <div class="card stat-card"><div class="value"><?= (int)$stats['pending_apps'] ?></div><div class="label">Pending Applications</div></div>
            <div class="card stat-card"><div class="value"><?= (int)$stats['completed_tuitions'] ?></div><div class="label">Completed Tuitions</div></div>
            <div class="card stat-card"><div class="value"><?= money($stats['total_payments']) ?></div><div class="label">Total Payments Collected</div></div>
            <div class="card stat-card"><div class="value"><?= (int)$stats['pending_payments'] ?></div><div class="label">Pending / Overdue Payments</div></div>
            <div class="card stat-card"><div class="value"><?= (int)$stats['pending_reports'] ?></div><div class="label">Pending Reports</div></div>
        </div>

        <div class="grid grid-2 mt-3">
            <div class="card">
                <h3>Recently Registered Users</h3>
                <?php if (!$recentUsers): ?><p class="empty-state">No users yet.</p><?php else: ?>
                <table>
                    <tr><th>Name</th><th>Role</th><th>Joined</th></tr>
                    <?php foreach ($recentUsers as $u): ?>
                        <tr><td><?= e($u['full_name']) ?></td><td><?= ucfirst(e($u['role'])) ?></td><td><?= timeAgo($u['created_at']) ?></td></tr>
                    <?php endforeach; ?>
                </table>
                <?php endif; ?>
            </div>
            <div class="card">
                <h3>Recent System Activity</h3>
                <?php if (!$recentActivity): ?><p class="empty-state">No admin actions logged yet.</p><?php else: ?>
                <table>
                    <tr><th>Admin</th><th>Action</th><th>When</th></tr>
                    <?php foreach ($recentActivity as $a): ?>
                        <tr><td><?= e($a['full_name']) ?></td><td><?= e($a['action_type']) ?></td><td><?= timeAgo($a['created_at']) ?></td></tr>
                    <?php endforeach; ?>
                </table>
                <?php endif; ?>
                <a href="<?= BASE_URL ?>/admin/activity_log.php" class="btn btn-outline btn-sm mt-2">View Full Log</a>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
