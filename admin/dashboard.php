<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../includes/certificates.php';

$pageTitle = 'Admin analytics';
learnify_ensure_certificates_table($pdo);
$studentCount = (int) $pdo->query(
    'SELECT COUNT(*)
     FROM users
     WHERE role = \'student\''
)->fetchColumn();
$moduleCount = (int) $pdo->query(
    'SELECT COUNT(*)
     FROM modules
     WHERE is_published = 1'
)->fetchColumn();
$questionCount = (int) $pdo->query(
    'SELECT COUNT(*)
     FROM questions
     INNER JOIN quizzes ON quizzes.id = questions.quiz_id
     INNER JOIN modules ON modules.id = quizzes.module_id
     WHERE quizzes.is_published = 1 AND modules.is_published = 1'
)->fetchColumn();
$certificateCount = (int) $pdo->query('SELECT COUNT(*) FROM certificates')->fetchColumn();

$attempts = $pdo->query(
    'SELECT users.id AS user_id, users.name AS student_name, modules.name AS module_name,
            quizzes.title AS quiz_title, quiz_attempts.exam_mode,
            quiz_attempts.score_percentage, quiz_attempts.correct_count,
            quiz_attempts.question_count, quiz_attempts.completed_at
     FROM quiz_attempts
     INNER JOIN users ON users.id = quiz_attempts.user_id
     INNER JOIN quizzes ON quizzes.id = quiz_attempts.quiz_id
     INNER JOIN modules ON modules.id = quizzes.module_id
     ORDER BY quiz_attempts.completed_at DESC
     LIMIT 12'
)->fetchAll();
$topStudents = $pdo->query(
    'SELECT users.id, users.name, COUNT(quiz_attempts.attempt_id) AS attempts,
            ROUND(AVG(quiz_attempts.score_percentage), 1) AS average_score,
            MAX(quiz_attempts.score_percentage) AS best_score
     FROM users
     INNER JOIN quiz_attempts ON quiz_attempts.user_id = users.id
     WHERE users.role = \'student\' AND users.is_active = 1
     GROUP BY users.id, users.name
     ORDER BY average_score DESC, attempts DESC, users.name
     LIMIT 8'
)->fetchAll();

require __DIR__ . '/../includes/header.php';
?>
<main class="admin-page container-wide">
    <header class="admin-page-heading">
        <p class="eyebrow">Learnify administration</p>
        <h1>Admin analytics</h1>
        <p class="text-muted">Platform activity, learner outcomes, and content health at a glance.</p>
    </header>

    <section class="admin-metric-grid" aria-label="Platform metrics">
        <article class="glass-card admin-metric-card">
            <span class="admin-metric-icon"><i class="fa-solid fa-user-graduate" aria-hidden="true"></i></span>
            <p class="eyebrow">Total registered students</p>
            <strong><?= number_format($studentCount) ?></strong>
        </article>
        <article class="glass-card admin-metric-card">
            <span class="admin-metric-icon"><i class="fa-solid fa-book-open" aria-hidden="true"></i></span>
            <p class="eyebrow">Published modules</p>
            <strong><?= number_format($moduleCount) ?></strong>
        </article>
        <article class="glass-card admin-metric-card">
            <span class="admin-metric-icon"><i class="fa-solid fa-circle-question" aria-hidden="true"></i></span>
            <p class="eyebrow">Total questions in pool</p>
            <strong><?= number_format($questionCount) ?></strong>
        </article>
        <article class="glass-card admin-metric-card">
            <span class="admin-metric-icon"><i class="fa-solid fa-certificate" aria-hidden="true"></i></span>
            <p class="eyebrow">Total certificates awarded</p>
            <strong><?= number_format($certificateCount) ?></strong>
        </article>
    </section>

    <div class="admin-dashboard-grid">
        <section class="glass-card admin-panel">
            <div class="admin-panel-heading">
                <div><p class="eyebrow">Latest activity</p><h2>Recent exam attempts</h2></div>
                <a class="button button-secondary" href="students.php">Student audit</a>
            </div>
            <?php if ($attempts === []): ?>
                <p class="text-muted">No exam attempts have been submitted yet.</p>
            <?php else: ?>
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead>
                        <tr><th>Student</th><th>Module / quiz</th><th>Format</th><th>Score</th><th>Completed</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($attempts as $attempt): ?>
                            <tr>
                                <td><?= learnify_h($attempt['student_name']) ?></td>
                                <td>
                                    <strong><?= learnify_h($attempt['module_name']) ?></strong>
                                    <small><?= learnify_h($attempt['quiz_title']) ?></small>
                                </td>
                                <td><?= learnify_h(ucfirst((string) $attempt['exam_mode'])) ?></td>
                                <td><?= number_format((float) $attempt['score_percentage'], 1) ?>%
                                    <small><?= (int) $attempt['correct_count'] ?>/<?= (int) $attempt['question_count'] ?> correct</small>
                                </td>
                                <td><?= learnify_h(date('M j, Y H:i', strtotime((string) $attempt['completed_at']))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>

        <section class="glass-card admin-panel">
            <div class="admin-panel-heading">
                <div><p class="eyebrow">Learner outcomes</p><h2>Top-performing students</h2></div>
            </div>
            <?php if ($topStudents === []): ?>
                <p class="text-muted">Student rankings will appear after the first completed exam.</p>
            <?php else: ?>
                <ol class="admin-leaderboard">
                    <?php foreach ($topStudents as $index => $student): ?>
                        <li>
                            <span class="admin-rank"><?= $index + 1 ?></span>
                            <div class="admin-leaderboard-name">
                                <strong><?= learnify_h($student['name']) ?></strong>
                                <small><?= (int) $student['attempts'] ?> attempts · best <?= number_format((float) $student['best_score'], 1) ?>%</small>
                            </div>
                            <strong class="admin-leaderboard-score"><?= number_format((float) $student['average_score'], 1) ?>%</strong>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </section>
    </div>

    <nav class="admin-quick-links" aria-label="Admin management">
        <a class="glass-card" href="modules.php"><i class="fa-solid fa-layer-group" aria-hidden="true"></i><strong>Manage modules & lessons</strong></a>
        <a class="glass-card" href="quizzes.php"><i class="fa-solid fa-list-check" aria-hidden="true"></i><strong>Manage question bank</strong></a>
        <a class="glass-card" href="students.php"><i class="fa-solid fa-users" aria-hidden="true"></i><strong>Audit student progress</strong></a>
    </nav>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
