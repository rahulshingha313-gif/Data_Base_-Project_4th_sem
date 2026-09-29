<?php $current = basename($_SERVER['SCRIPT_NAME']); ?>
<div class="sidebar">
    <a href="<?= BASE_URL ?>/student/dashboard.php" class="<?= $current === 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
    <a href="<?= BASE_URL ?>/student/profile.php" class="<?= $current === 'profile.php' ? 'active' : '' ?>">My Profile</a>
    <a href="<?= BASE_URL ?>/student/my_posts.php" class="<?= $current === 'my_posts.php' ? 'active' : '' ?>">My Tuition Posts</a>
    <a href="<?= BASE_URL ?>/student/post_tuition.php" class="<?= $current === 'post_tuition.php' ? 'active' : '' ?>">Post a Tuition</a>
    <a href="<?= BASE_URL ?>/student/browse_tuition.php" class="<?= $current === 'browse_tuition.php' ? 'active' : '' ?>">Browse Marketplace</a>
    <a href="<?= BASE_URL ?>/student/applications.php" class="<?= $current === 'applications.php' ? 'active' : '' ?>">Applications Received</a>
    <a href="<?= BASE_URL ?>/student/favorites.php" class="<?= $current === 'favorites.php' ? 'active' : '' ?>">Favorites</a>
    <a href="<?= BASE_URL ?>/schedule/index.php?view=student" class="<?= $current === 'index.php' && ($_GET['view'] ?? '') === 'student' ? 'active' : '' ?>">Schedule</a>
    <a href="<?= BASE_URL ?>/attendance/student_view.php" class="<?= $current === 'student_view.php' ? 'active' : '' ?>">Attendance</a>
    <a href="<?= BASE_URL ?>/payments/student_view.php" class="<?= $current === 'student_view.php' ? 'active' : '' ?>">Payments</a>
    <a href="<?= BASE_URL ?>/messages/inbox.php" class="<?= $current === 'inbox.php' ? 'active' : '' ?>">Messages</a>
    <a href="<?= BASE_URL ?>/reviews/give_review.php" class="<?= $current === 'give_review.php' ? 'active' : '' ?>">Give Review</a>
    <a href="<?= BASE_URL ?>/reports/submit_report.php" class="<?= $current === 'submit_report.php' ? 'active' : '' ?>">Report a Problem</a>
</div>
