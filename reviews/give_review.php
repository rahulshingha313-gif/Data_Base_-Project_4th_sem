<?php
require_once __DIR__ . '/../config/config.php';
requireRole('student');

$db = getDB();
$user = currentUser();

$postId = (int) ($_GET['post_id'] ?? $_POST['tuition_post_id'] ?? 0);
$scheduleId = (int) ($_GET['schedule_id'] ?? $_POST['schedule_id'] ?? 0);

// Find completed tuitions this student can review (accepted application + completed schedule)
$completedTuitions = $db->prepare(
    "SELECT DISTINCT tp.tuition_post_id, tp.title, tpf.user_id AS tutor_user_id, u.full_name AS tutor_name
     FROM tuition_schedule ts
     JOIN tuition_posts tp ON tp.tuition_post_id = ts.tuition_post_id
     JOIN applications a ON a.tuition_post_id = tp.tuition_post_id AND a.status = 'accepted'
     JOIN tutor_profiles tpf ON tpf.tutor_profile_id = a.tutor_profile_id
     JOIN users u ON u.user_id = tpf.user_id
     WHERE ts.student_user_id = ? AND ts.status = 'completed'"
);
$completedTuitions->execute([$user['user_id']]);
$completedTuitions = $completedTuitions->fetchAll();

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $rating = (int) ($_POST['rating'] ?? 0);
    $text = trim($_POST['review_text'] ?? '');

    $match = null;
    foreach ($completedTuitions as $t) if ($t['tuition_post_id'] == $postId) $match = $t;

    if (!$match) $errors[] = 'You can only review tutors from completed tuitions.';
    if ($rating < 1 || $rating > 5) $errors[] = 'Please choose a rating between 1 and 5.';

    if (!$errors) {
        // Prevent duplicate review for same tuition_post/reviewer/reviewee (DB also enforces via UNIQUE key)
        try {
            $db->prepare(
                'INSERT INTO reviews (tuition_schedule_ref, tuition_post_id, reviewer_user_id, reviewee_user_id, rating, review_text) VALUES (?,?,?,?,?,?)'
            )->execute([$scheduleId ?: null, $postId, $user['user_id'], $match['tutor_user_id'], $rating, $text]);

            // Recalculate tutor's average rating
            $agg = $db->prepare('SELECT AVG(rating) avg_r, COUNT(*) c FROM reviews WHERE reviewee_user_id = ?');
            $agg->execute([$match['tutor_user_id']]);
            $aggRow = $agg->fetch();
            $db->prepare('UPDATE tutor_profiles SET avg_rating = ?, total_reviews = ? WHERE user_id = ?')
               ->execute([round($aggRow['avg_r'], 2), $aggRow['c'], $match['tutor_user_id']]);

            notify($match['tutor_user_id'], 'new_review', 'New Review Received', $user['full_name'] . ' left you a ' . $rating . '-star review.', '/tutor/reviews.php');

            setFlash('success', 'Thank you for your review!');
            redirect('/reviews/give_review.php');
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $errors[] = 'You have already reviewed this tuition.';
            } else {
                throw $e;
            }
        }
    }
}

$pageTitle = 'Give a Review';
include __DIR__ . '/../includes/header.php';
?>
<div class="dash-layout">
    <?php include __DIR__ . '/../includes/sidebar_student.php'; ?>
    <div>
        <div class="card">
            <h2>Give a Review</h2>
            <?php foreach ($errors as $err): ?><div class="alert alert-error"><?= e($err) ?></div><?php endforeach; ?>
            <?php if (!$completedTuitions): ?>
                <p class="empty-state">You can leave a review once a tuition class is marked completed.</p>
            <?php else: ?>
            <form method="POST" action="">
                <?= csrfField() ?>
                <input type="hidden" name="schedule_id" value="<?= $scheduleId ?>">
                <label>Tuition</label>
                <select name="tuition_post_id" required>
                    <?php foreach ($completedTuitions as $t): ?>
                        <option value="<?= (int)$t['tuition_post_id'] ?>" <?= $postId==$t['tuition_post_id']?'selected':'' ?>><?= e($t['title']) ?> — <?= e($t['tutor_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <label>Rating</label>
                <select name="rating" required>
                    <?php for ($i=5;$i>=1;$i--): ?><option value="<?= $i ?>"><?= $i ?> Star<?= $i>1?'s':'' ?></option><?php endfor; ?>
                </select>
                <label>Review</label>
                <textarea name="review_text" placeholder="Share your experience..." required></textarea>
                <button type="submit" class="btn btn-primary mt-3">Submit Review</button>
            </form>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
