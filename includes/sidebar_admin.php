<?php $current = basename($_SERVER['SCRIPT_NAME']); ?>
<div class="sidebar">
    <a href="<?= BASE_URL ?>/admin/dashboard.php" class="<?= $current === 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
    <a href="<?= BASE_URL ?>/admin/users.php" class="<?= $current === 'users.php' ? 'active' : '' ?>">Manage Users</a>
    <a href="<?= BASE_URL ?>/admin/tutor_verification.php" class="<?= $current === 'tutor_verification.php' ? 'active' : '' ?>">Verify Tutors</a>
    <a href="<?= BASE_URL ?>/admin/posts.php" class="<?= $current === 'posts.php' ? 'active' : '' ?>">Tuition / Tutor Posts</a>
    <a href="<?= BASE_URL ?>/admin/applications.php" class="<?= $current === 'applications.php' ? 'active' : '' ?>">Applications</a>
    <a href="<?= BASE_URL ?>/admin/reviews.php" class="<?= $current === 'reviews.php' ? 'active' : '' ?>">Reviews</a>
    <a href="<?= BASE_URL ?>/admin/payments.php" class="<?= $current === 'payments.php' ? 'active' : '' ?>">Payments</a>
    <a href="<?= BASE_URL ?>/admin/reports.php" class="<?= $current === 'reports.php' ? 'active' : '' ?>">Reports</a>
    <a href="<?= BASE_URL ?>/admin/subjects.php" class="<?= $current === 'subjects.php' ? 'active' : '' ?>">Manage Subjects</a>
    <a href="<?= BASE_URL ?>/admin/activity_log.php" class="<?= $current === 'activity_log.php' ? 'active' : '' ?>">System Activity</a>
</div>
