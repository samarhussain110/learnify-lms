<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/admin-auth.php';

$students = $pdo->query(
    'SELECT users.id, users.name, users.email, users.study_streak, users.last_study_date,
            users.created_at,
            (SELECT COUNT(*)
             FROM modules
             WHERE modules.is_published = 1
               AND EXISTS (
                   SELECT 1 FROM chapters AS available
                   WHERE available.module_id = modules.id AND available.is_published = 1
               )
               AND (
                   SELECT COUNT(DISTINCT progress.chapter_id)
                   FROM user_chapter_progress AS progress
                   INNER JOIN chapters AS completed ON completed.id = progress.chapter_id
                   WHERE progress.user_id = users.id
                     AND completed.module_id = modules.id
                     AND completed.is_published = 1
               ) = (
                   SELECT COUNT(*)
                   FROM chapters AS total
                   WHERE total.module_id = modules.id AND total.is_published = 1
               )
            ) AS completed_modules,
            (SELECT COUNT(*)
             FROM quiz_attempts
             WHERE quiz_attempts.user_id = users.id) AS attempt_count,
            (SELECT COALESCE(AVG(score_percentage), 0)
             FROM quiz_attempts
             WHERE quiz_attempts.user_id = users.id) AS average_score
     FROM users
     WHERE users.role = \'student\'
     ORDER BY users.created_at DESC, users.name'
)->fetchAll();

$pageTitle = 'Student progress audit';
require __DIR__ . '/../includes/header.php';
?>
<main class="admin-page container-wide">
    <header class="admin-page-heading">
        <p class="eyebrow">Learner administration</p>
        <h1>Student progress audit</h1>
        <p class="text-muted">Review study streaks, module completions, exam outcomes, and certificate eligibility.</p>
    </header>
    <section class="glass-card admin-panel">
        <div class="admin-panel-heading">
            <div><p class="eyebrow">Learners</p><h2><?= number_format(count($students)) ?> registered students</h2></div>
        </div>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>Student</th><th>Study streak</th><th>Completed modules</th><th>Exam record</th><th>Certificates</th></tr></thead>
                <tbody>
                <?php foreach ($students as $student): ?>
                    <?php
                    $streak = $student['last_study_date'] !== null
                        && (string) $student['last_study_date'] >= date('Y-m-d', strtotime('-1 day'))
                        ? (int) $student['study_streak']
                        : 0;
                    ?>
                    <tr>
                        <td>
                            <strong><?= learnify_h($student['name']) ?></strong>
                            <small><?= learnify_h($student['email']) ?></small>
                            <small>Joined <?= learnify_h(date('M j, Y', strtotime((string) $student['created_at']))) ?></small>
                        </td>
                        <td><?= $streak ?> day<?= $streak === 1 ? '' : 's' ?></td>
                        <td><?= (int) $student['completed_modules'] ?></td>
                        <td><?= number_format((float) $student['average_score'], 1) ?>% average
                            <small><?= (int) $student['attempt_count'] ?> attempts</small>
                        </td>
                        <td><a class="button button-secondary" href="certificates.php?user_id=<?= (int) $student['id'] ?>">View certificates</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($students === []): ?><tr><td colspan="5">No student accounts are registered.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
