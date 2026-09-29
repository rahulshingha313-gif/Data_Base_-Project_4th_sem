<?php
require_once __DIR__ . '/../config/config.php';

$db = getDB();
$subjects = $db->query('SELECT * FROM subjects ORDER BY subject_name')->fetchAll();

$q = trim($_GET['q'] ?? '');
$subjectId = (int) ($_GET['subject_id'] ?? 0);
$location = trim($_GET['location'] ?? '');
$mode = $_GET['mode'] ?? '';
$minRating = (float) ($_GET['min_rating'] ?? 0);

$where = ["1=1"];
$params = [];
$joinSubject = '';

if ($subjectId) {
    $joinSubject = 'JOIN tutor_subjects tsub ON tsub.tutor_profile_id = tpf.tutor_profile_id';
    $where[] = 'tsub.subject_id = ?';
    $params[] = $subjectId;
}
if ($q !== '') { $where[] = '(u.full_name LIKE ? OR tpf.bio LIKE ? OR tpf.education LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; $params[] = "%$q%"; }
if ($location !== '') { $where[] = 'tpf.teaching_location LIKE ?'; $params[] = "%$location%"; }
if ($mode !== '') { $where[] = "(tpf.teaching_mode = ? OR tpf.teaching_mode = 'both')"; $params[] = $mode; }
if ($minRating > 0) { $where[] = 'tpf.avg_rating >= ?'; $params[] = $minRating; }

$whereSql = implode(' AND ', $where);

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 9;
$countSql = "SELECT COUNT(DISTINCT tpf.tutor_profile_id) FROM tutor_profiles tpf JOIN users u ON u.user_id = tpf.user_id $joinSubject WHERE $whereSql";
$total = $db->prepare($countSql);
$total->execute($params);
$total = (int) $total->fetchColumn();
[$totalPages, $page, $offset] = paginate($total, $perPage, $page);

$sql = "SELECT DISTINCT tpf.*, u.full_name, u.gender FROM tutor_profiles tpf JOIN users u ON u.user_id = tpf.user_id $joinSubject
        WHERE $whereSql ORDER BY tpf.avg_rating DESC, tpf.created_at DESC LIMIT $perPage OFFSET $offset";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$tutors = $stmt->fetchAll();

$pageTitle = 'Find Tutors';
include __DIR__ . '/../includes/header.php';
?>
<div class="container mt-3">
    <h2>Tutor Marketplace</h2>
    <div class="card">
        <form method="GET" action="" class="filter-bar">
            <div><label>Keyword</label><input type="text" name="q" value="<?= e($q) ?>" placeholder="Name, bio, education"></div>
            <div><label>Subject</label>
                <select name="subject_id">
                    <option value="0">Any</option>
                    <?php foreach ($subjects as $s): ?>
                        <option value="<?= (int)$s['subject_id'] ?>" <?= $subjectId==$s['subject_id']?'selected':'' ?>><?= e($s['subject_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div><label>Location</label><input type="text" name="location" value="<?= e($location) ?>"></div>
            <div><label>Mode</label>
                <select name="mode">
                    <option value="">Any</option>
                    <option value="online" <?= $mode=='online'?'selected':'' ?>>Online</option>
                    <option value="offline" <?= $mode=='offline'?'selected':'' ?>>Offline</option>
                </select>
            </div>
            <div><label>Min Rating</label>
                <select name="min_rating">
                    <option value="0">Any</option>
                    <?php foreach ([4,3,2,1] as $r): ?>
                        <option value="<?= $r ?>" <?= $minRating==$r?'selected':'' ?>><?= $r ?>+ stars</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div><button type="submit" class="btn btn-primary btn-block">Search</button></div>
        </form>
    </div>

    <p class="text-muted mt-2"><?= $total ?> tutor(s) found.</p>
    <div class="grid grid-3">
        <?php foreach ($tutors as $t): ?>
            <div class="card">
                <div class="avatar"><?= strtoupper(substr($t['full_name'],0,1)) ?></div>
                <h3 class="mt-1"><?= e($t['full_name']) ?> <?php if ($t['is_profile_verified']): ?><span class="badge badge-active">Verified</span><?php endif; ?></h3>
                <p class="text-muted"><?= e($t['education']) ?: 'Education not specified' ?></p>
                <p class="text-muted"><?= e($t['teaching_location']) ?: 'Location flexible' ?> &middot; <?= ucfirst(e($t['teaching_mode'])) ?></p>
                <p><span class="rating-stars"><?= str_repeat('★', (int)round($t['avg_rating'])) . str_repeat('☆', 5-(int)round($t['avg_rating'])) ?></span> (<?= (int)$t['total_reviews'] ?>)</p>
                <p><?= money($t['expected_salary']) ?>/month</p>
                <a href="<?= BASE_URL ?>/tutor/view_tutor.php?id=<?= (int)$t['tutor_profile_id'] ?>" class="btn btn-outline btn-sm mt-1">View Profile</a>
            </div>
        <?php endforeach; ?>
        <?php if (!$tutors): ?><p class="empty-state">No tutors match your filters.</p><?php endif; ?>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="pagination">
        <?php for ($i = 1; $i <= $totalPages; $i++): $qs = $_GET; $qs['page'] = $i; ?>
            <a href="?<?= http_build_query($qs) ?>" class="<?= $i==$page?'active':'' ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
