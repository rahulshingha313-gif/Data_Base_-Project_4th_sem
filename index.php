<?php
require_once __DIR__ . '/config/config.php';

$db = getDB();
$stats = [
    'students' => $db->query("SELECT COUNT(*) FROM users WHERE role IN ('student','both')")->fetchColumn(),
    'tutors'   => $db->query("SELECT COUNT(*) FROM users WHERE role IN ('tutor','both')")->fetchColumn(),
    'posts'    => $db->query("SELECT COUNT(*) FROM tuition_posts WHERE status = 'open'")->fetchColumn(),
    'completed'=> $db->query("SELECT COUNT(*) FROM tuition_schedule WHERE status = 'completed'")->fetchColumn(),
];

$recentPosts = $db->query(
    "SELECT tp.*, s.subject_name FROM tuition_posts tp
     JOIN subjects s ON s.subject_id = tp.subject_id
     WHERE tp.status = 'open' ORDER BY tp.created_at DESC LIMIT 3"
)->fetchAll();

$pageTitle = 'Home';
include __DIR__ . '/includes/header.php';
?>
<div class="hero">
    <h1>Find the right <span class="gold-text">tutor</span>.<br>Manage tuition the <span class="gold-text">professional</span> way.</h1>
    <p>TutorHub connects students and tutors, and handles applications, scheduling, attendance, payments and reviews — all backed by a real relational database.</p>
    <div class="hero-actions">
        <a href="<?= BASE_URL ?>/student/browse_tuition.php" class="btn btn-primary">Browse Tuition Posts</a>
        <a href="<?= BASE_URL ?>/auth/register.php" class="btn btn-outline">Get Started</a>
    </div>
</div>

<div class="container mt-3">
    <div class="grid grid-4">
        <div class="card stat-card"><div class="value"><?= (int)$stats['students'] ?></div><div class="label">Registered Students</div></div>
        <div class="card stat-card"><div class="value"><?= (int)$stats['tutors'] ?></div><div class="label">Registered Tutors</div></div>
        <div class="card stat-card"><div class="value"><?= (int)$stats['posts'] ?></div><div class="label">Open Tuition Posts</div></div>
        <div class="card stat-card"><div class="value"><?= (int)$stats['completed'] ?></div><div class="label">Classes Completed</div></div>
    </div>

    <h2 class="mt-3">How TutorHub Works</h2>
    <div class="grid grid-3">
        <div class="card"><div class="feature-icon">1</div><h3>Post or Browse</h3><p class="text-muted">Students post tuition requirements; tutors advertise their availability and subjects.</p></div>
        <div class="card"><div class="feature-icon">2</div><h3>Apply &amp; Accept</h3><p class="text-muted">Tutors apply with a message and rate; students review applications and accept the best fit.</p></div>
        <div class="card"><div class="feature-icon">3</div><h3>Schedule &amp; Track</h3><p class="text-muted">Manage class schedules, attendance, monthly payments, and leave reviews after completion.</p></div>
    </div>

    <h2 class="mt-3">Recently Posted Tuition</h2>
    <div class="grid grid-3">
        <?php foreach ($recentPosts as $p): ?>
            <div class="card">
                <span class="badge badge-<?= e($p['status']) ?>"><?= e($p['status']) ?></span>
                <h3 class="mt-1"><?= e($p['title']) ?></h3>
                <p class="text-muted"><?= e($p['subject_name']) ?> &middot; <?= e($p['class_level']) ?></p>
                <p class="text-muted"><?= e($p['location']) ?> &middot; <?= money($p['budget_min']) ?> - <?= money($p['budget_max']) ?></p>
                <a href="<?= BASE_URL ?>/student/view_post.php?id=<?= (int)$p['tuition_post_id'] ?>" class="btn btn-outline btn-sm mt-1">View Details</a>
            </div>
        <?php endforeach; ?>
        <?php if (!$recentPosts): ?><p class="text-muted">No open tuition posts yet.</p><?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
