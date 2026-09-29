<?php
require_once __DIR__ . '/../config/config.php';
requireAdmin();

$db = getDB();
$admin = currentUser();

if (isset($_GET['suspend']) || isset($_GET['activate'])) {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_GET['csrf_token'] ?? '')) die('Invalid request.');
    $id = (int) ($_GET['suspend'] ?? $_GET['activate'] ?? 0);
    $newStatus = isset($_GET['suspend']) ? 'suspended' : 'active';

    if ($id === (int) $admin['user_id']) {
        setFlash('error', 'You cannot change your own account status.');
    } else {
        $db->prepare('UPDATE users SET status = ? WHERE user_id = ?')->execute([$newStatus, $id]);
        logAdminAction($admin['user_id'], $newStatus === 'suspended' ? 'suspend_user' : 'activate_user', 'user', $id, '');
        setFlash('success', 'User status updated.');
    }
    redirect('/admin/users.php');
}

$q = trim($_GET['q'] ?? '');
$roleFilter = $_GET['role'] ?? '';
$where = ['1=1'];
$params = [];
if ($q !== '') { $where[] = '(full_name LIKE ? OR email LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; }
if ($roleFilter !== '') { $where[] = 'role = ?'; $params[] = $roleFilter; }
$whereSql = implode(' AND ', $where);

$stmt = $db->prepare("SELECT * FROM users WHERE $whereSql ORDER BY created_at DESC");
$stmt->execute($params);
$users = $stmt->fetchAll();

$pageTitle = 'Manage Users';
include __DIR__ . '/../includes/header.php';
?>
<div class="dash-layout">
    <?php include __DIR__ . '/../includes/sidebar_admin.php'; ?>
    <div>
        <h2>Manage Users</h2>
        <div class="card">
            <form method="GET" action="" class="filter-bar">
                <div><label>Search</label><input type="text" name="q" value="<?= e($q) ?>" placeholder="Name or email"></div>
                <div><label>Role</label>
                    <select name="role">
                        <option value="">All</option>
                        <?php foreach (['student','tutor','both','admin'] as $r): ?>
                            <option value="<?= $r ?>" <?= $roleFilter==$r?'selected':'' ?>><?= ucfirst($r) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div><button type="submit" class="btn btn-primary btn-block">Filter</button></div>
            </form>
        </div>
        <div class="card">
            <table>
                <tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Joined</th><th>Actions</th></tr>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= e($u['full_name']) ?></td>
                    <td><?= e($u['email']) ?></td>
                    <td><?= ucfirst(e($u['role'])) ?></td>
                    <td><span class="badge badge-<?= e($u['status']) ?>"><?= e($u['status']) ?></span></td>
                    <td><?= timeAgo($u['created_at']) ?></td>
                    <td>
                        <?php if ($u['role'] !== 'admin' && $u['user_id'] != $admin['user_id']): ?>
                            <?php if ($u['status'] === 'active'): ?>
                                <a href="?suspend=<?= (int)$u['user_id'] ?>&csrf_token=<?= e(csrfToken()) ?>" class="btn btn-danger btn-sm" onclick="return confirm('Suspend this user?')">Suspend</a>
                            <?php else: ?>
                                <a href="?activate=<?= (int)$u['user_id'] ?>&csrf_token=<?= e(csrfToken()) ?>" class="btn btn-primary btn-sm">Activate</a>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="text-muted">-</span>
                        <?php endif; ?>
                        <?php if (in_array($u['role'], ['student','both'], true)): ?>
                            <a href="<?= BASE_URL ?>/admin/view_student.php?user_id=<?= (int)$u['user_id'] ?>" class="btn btn-secondary btn-sm">Student Profile</a>
                        <?php endif; ?>
                        <?php if (in_array($u['role'], ['tutor','both'], true)): ?>
                            <a href="<?= BASE_URL ?>/admin/view_tutor.php?user_id=<?= (int)$u['user_id'] ?>" class="btn btn-secondary btn-sm">Tutor Profile</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$users): ?><tr><td colspan="6" class="empty-state">No users found.</td></tr><?php endif; ?>
            </table>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
