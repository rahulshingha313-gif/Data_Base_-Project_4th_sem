<?php
require_once __DIR__ . '/../config/config.php';
requireAdmin();

$db = getDB();
$admin = currentUser();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add'])) {
    verifyCsrf();
    $name = trim($_POST['subject_name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    if ($name === '') {
        $errors[] = 'Subject name is required.';
    } else {
        try {
            $db->prepare('INSERT INTO subjects (subject_name, category) VALUES (?, ?)')->execute([$name, $category]);
            logAdminAction($admin['user_id'], 'add_subject', 'subject', (int)$db->lastInsertId(), $name);
            setFlash('success', 'Subject added.');
            redirect('/admin/subjects.php');
        } catch (PDOException $e) {
            $errors[] = 'That subject already exists.';
        }
    }
}

if (isset($_GET['delete'])) {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_GET['csrf_token'] ?? '')) die('Invalid request.');
    $id = (int) $_GET['delete'];
    try {
        $db->prepare('DELETE FROM subjects WHERE subject_id = ?')->execute([$id]);
        logAdminAction($admin['user_id'], 'delete_subject', 'subject', $id, '');
        setFlash('success', 'Subject deleted.');
    } catch (PDOException $e) {
        setFlash('error', 'Cannot delete a subject that is currently in use.');
    }
    redirect('/admin/subjects.php');
}

$subjects = $db->query('SELECT * FROM subjects ORDER BY subject_name')->fetchAll();

$pageTitle = 'Manage Subjects';
include __DIR__ . '/../includes/header.php';
?>
<div class="dash-layout">
    <?php include __DIR__ . '/../includes/sidebar_admin.php'; ?>
    <div>
        <h2>Manage Subjects</h2>
        <div class="card">
            <?php foreach ($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>
            <form method="POST" action="" class="filter-bar">
                <?= csrfField() ?>
                <div><label>Subject Name</label><input type="text" name="subject_name" required></div>
                <div><label>Category</label><input type="text" name="category" placeholder="e.g. Science"></div>
                <div><button type="submit" name="add" value="1" class="btn btn-primary btn-block">Add Subject</button></div>
            </form>
        </div>
        <div class="card">
            <table>
                <tr><th>Subject</th><th>Category</th><th>Actions</th></tr>
                <?php foreach ($subjects as $s): ?>
                <tr>
                    <td><?= e($s['subject_name']) ?></td>
                    <td><?= e($s['category'] ?? '-') ?></td>
                    <td><a href="?delete=<?= (int)$s['subject_id'] ?>&csrf_token=<?= e(csrfToken()) ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this subject?')">Delete</a></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
