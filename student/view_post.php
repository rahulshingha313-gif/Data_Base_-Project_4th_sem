<?php
require_once __DIR__ . '/../config/config.php';

$db = getDB();
$id = (int) ($_GET['id'] ?? 0);

$stmt = $db->prepare(
    "SELECT tp.*, s.subject_name, sp.class_level AS profile_class, sp.student_profile_id, u.full_name AS student_name, u.user_id AS student_user_id
     FROM tuition_posts tp
     JOIN subjects s ON s.subject_id = tp.subject_id
     JOIN student_profiles sp ON sp.student_profile_id = tp.student_profile_id
     JOIN users u ON u.user_id = sp.user_id
     WHERE tp.tuition_post_id = ?"
);
$stmt->execute([$id]);
$post = $stmt->fetch();

if (!$post) {
    setFlash('error', 'Tuition post not found.');
    redirect('/student/browse_tuition.php');
}

$user = currentUser();
$isOwner = $user && hasRole('student') && currentStudentProfileId() == $post['student_profile_id'];
$isTutor = $user && hasRole('tutor');
$myTutorProfileId = $isTutor ? currentTutorProfileId() : null;

$myApplication = null;
if ($myTutorProfileId) {
    $s = $db->prepare('SELECT * FROM applications WHERE tuition_post_id = ? AND tutor_profile_id = ?');
    $s->execute([$id, $myTutorProfileId]);
    $myApplication = $s->fetch();
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply'])) {
    requireRole('tutor');
    verifyCsrf();
    $message = trim($_POST['message'] ?? '');
    $proposedSalary = (float) ($_POST['proposed_salary'] ?? 0);

    if ($message === '') $errors[] = 'Please write a short application message.';
    if ($post['status'] !== 'open') $errors[] = 'This tuition post is no longer accepting applications.';

    if (!$errors) {
        $stmt = $db->prepare(
            'INSERT INTO applications (tuition_post_id, tutor_profile_id, message, proposed_salary) VALUES (?,?,?,?)'
        );
        $stmt->execute([$id, $myTutorProfileId, $message, $proposedSalary]);

        notify($post['student_user_id'], 'new_application', 'New Application Received',
            currentUser()['full_name'] . ' applied to your tuition post "' . $post['title'] . '".',
            '/student/applications.php?post_id=' . $id);

        setFlash('success', 'Your application has been submitted.');
        redirect('/student/view_post.php?id=' . $id);
    }
}

// Favorite toggle
if (isset($_GET['favorite']) && $user) {
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $_GET['csrf_token'] ?? '')) die('Invalid request.');
    $check = $db->prepare('SELECT favorite_id FROM favorites WHERE user_id = ? AND tuition_post_id = ?');
    $check->execute([$user['user_id'], $id]);
    if ($check->fetch()) {
        $db->prepare('DELETE FROM favorites WHERE user_id = ? AND tuition_post_id = ?')->execute([$user['user_id'], $id]);
        setFlash('success', 'Removed from favorites.');
    } else {
        $db->prepare('INSERT INTO favorites (user_id, tuition_post_id) VALUES (?, ?)')->execute([$user['user_id'], $id]);
        setFlash('success', 'Added to favorites.');
    }
    redirect('/student/view_post.php?id=' . $id);
}

$isFavorited = false;
if ($user) {
    $check = $db->prepare('SELECT favorite_id FROM favorites WHERE user_id = ? AND tuition_post_id = ?');
    $check->execute([$user['user_id'], $id]);
    $isFavorited = (bool) $check->fetch();
}

$applications = [];
if ($isOwner) {
    $stmt = $db->prepare(
        "SELECT a.*, tpf.education, tpf.experience_years, tpf.avg_rating, u.full_name AS tutor_name
         FROM applications a
         JOIN tutor_profiles tpf ON tpf.tutor_profile_id = a.tutor_profile_id
         JOIN users u ON u.user_id = tpf.user_id
         WHERE a.tuition_post_id = ? ORDER BY a.created_at DESC"
    );
    $stmt->execute([$id]);
    $applications = $stmt->fetchAll();
}

$pageTitle = $post['title'];
include __DIR__ . '/../includes/header.php';
?>
<div class="container mt-3">
    <div class="card">
        <div class="flex-between">
            <div>
                <span class="badge badge-<?= e($post['status']) ?>"><?= e($post['status']) ?></span>
                <h2 class="mt-1"><?= e($post['title']) ?></h2>
                <p class="text-muted">Posted by <?= e($post['student_name']) ?> &middot; <?= timeAgo($post['created_at']) ?></p>
            </div>
            <?php if ($user && !$isOwner): ?>
                <a href="?id=<?= $id ?>&favorite=1&csrf_token=<?= e(csrfToken()) ?>" class="btn btn-outline btn-sm"><?= $isFavorited ? 'Remove Favorite' : 'Save to Favorites' ?></a>
            <?php endif; ?>
        </div>

        <div class="grid grid-3 mt-2">
            <div><strong>Subject:</strong> <?= e($post['subject_name']) ?></div>
            <div><strong>Class:</strong> <?= e($post['class_level']) ?></div>
            <div><strong>Location:</strong> <?= e($post['location']) ?></div>
            <div><strong>Budget:</strong> <?= money($post['budget_min']) ?> - <?= money($post['budget_max']) ?></div>
            <div><strong>Days:</strong> <?= e($post['preferred_days'] ?: 'Flexible') ?></div>
            <div><strong>Time:</strong> <?= e($post['preferred_time'] ?: 'Flexible') ?></div>
            <div><strong>Mode:</strong> <?= ucfirst(e($post['mode'])) ?></div>
            <div><strong>Gender Pref:</strong> <?= ucfirst(e($post['gender_preference'])) ?></div>
        </div>

        <div class="section-title">Description</div>
        <p><?= nl2br(e($post['description'])) ?></p>

        <?php if ($user): ?>
        <a href="<?= BASE_URL ?>/reports/submit_report.php?post_id=<?= $id ?>" class="btn btn-secondary btn-sm mt-2">Report this Post</a>
        <?php endif; ?>
    </div>

    <?php if ($isTutor && !$isOwner): ?>
    <div class="card mt-2">
        <h3>Apply for this Tuition</h3>
        <?php if ($myApplication): ?>
            <p>You already applied — status: <span class="badge badge-<?= e($myApplication['status']) ?>"><?= e($myApplication['status']) ?></span></p>
        <?php elseif ($post['status'] !== 'open'): ?>
            <p class="text-muted">This post is no longer open for applications.</p>
        <?php else: ?>
            <?php foreach ($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>
            <form method="POST" action="">
                <?= csrfField() ?>
                <label>Application Message</label>
                <textarea name="message" placeholder="Introduce yourself and explain why you're a good fit" required></textarea>
                <label>Proposed Salary (BDT/month)</label>
                <input type="number" step="0.01" name="proposed_salary" value="<?= e((string)$post['budget_max']) ?>">
                <button type="submit" name="apply" value="1" class="btn btn-primary mt-2">Submit Application</button>
            </form>
        <?php endif; ?>
    </div>
    <?php elseif (!$user): ?>
        <div class="card mt-2 text-muted">Please <a href="<?= BASE_URL ?>/auth/login.php">log in as a tutor</a> to apply for this tuition.</div>
    <?php endif; ?>

    <?php if ($isOwner): ?>
    <div class="card mt-2">
        <h3>Applications (<?= count($applications) ?>)</h3>
        <?php if (!$applications): ?>
            <p class="empty-state">No applications received yet.</p>
        <?php else: ?>
            <?php foreach ($applications as $a): ?>
            <div class="card" style="background:var(--bg-navy-light)">
                <div class="flex-between">
                    <div>
                        <strong><?= e($a['tutor_name']) ?></strong>
                        <span class="text-muted">&middot; <?= e($a['experience_years']) ?> yrs experience &middot; rating <?= e($a['avg_rating']) ?>/5</span>
                    </div>
                    <span class="badge badge-<?= e($a['status']) ?>"><?= e($a['status']) ?></span>
                </div>
                <p class="mt-1"><?= nl2br(e($a['message'])) ?></p>
                <p>Proposed salary: <?= money($a['proposed_salary']) ?></p>
                <?php if ($a['status'] === 'pending'): ?>
                <form method="POST" action="<?= BASE_URL ?>/student/applications.php" style="display:inline">
                    <?= csrfField() ?>
                    <input type="hidden" name="application_id" value="<?= (int)$a['application_id'] ?>">
                    <input type="hidden" name="post_id" value="<?= $id ?>">
                    <button type="submit" name="action" value="accept" class="btn btn-primary btn-sm">Accept</button>
                    <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm">Reject</button>
                </form>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
