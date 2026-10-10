<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/admin-auth.php';

$userId = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($userId === false || $userId === null) {
    http_response_code(400);
    exit('A valid student is required.');
}
$studentStatement = $pdo->prepare(
    'SELECT id, name, email
     FROM users
     WHERE id = :user_id AND role = \'student\'
     LIMIT 1'
);
$studentStatement->execute(['user_id' => $userId]);
$student = $studentStatement->fetch();
if (!is_array($student)) {
    http_response_code(404);
    exit('Student not found.');
}

$moduleStatement = $pdo->prepare(
    'SELECT modules.id, modules.name,
            (SELECT COUNT(*)
             FROM chapters
             WHERE chapters.module_id = modules.id AND chapters.is_published = 1) AS total_lessons,
            (SELECT COUNT(DISTINCT progress.chapter_id)
             FROM user_chapter_progress AS progress
             INNER JOIN chapters ON chapters.id = progress.chapter_id
             WHERE progress.user_id = :progress_user_id
               AND chapters.module_id = modules.id
               AND chapters.is_published = 1) AS completed_lessons,
            EXISTS (
                SELECT 1
                FROM quiz_attempts
                INNER JOIN quizzes ON quizzes.id = quiz_attempts.quiz_id
                WHERE quiz_attempts.user_id = :exam_user_id
                  AND quizzes.module_id = modules.id
                  AND quiz_attempts.score_percentage >= quizzes.passing_score
            ) AS passed_exam
     FROM modules
     WHERE modules.is_published = 1
     ORDER BY modules.display_order, modules.name'
);
$moduleStatement->execute(['progress_user_id' => $userId, 'exam_user_id' => $userId]);
$modules = $moduleStatement->fetchAll();
$eligibleModules = array_values(array_filter($modules, static function (array $module): bool {
    $total = (int) $module['total_lessons'];
    return $total > 0
        && (int) $module['completed_lessons'] === $total
        && (bool) $module['passed_exam'];
}));

$requestedModuleId = filter_input(INPUT_GET, 'module_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$certificateModule = null;
if ($requestedModuleId !== false && $requestedModuleId !== null) {
    foreach ($eligibleModules as $module) {
        if ((int) $module['id'] === $requestedModuleId) {
            $certificateModule = $module;
            break;
        }
    }
    if ($certificateModule === null) {
        http_response_code(403);
    }
}
$pageTitle = $certificateModule === null ? 'Student certificates' : 'Certificate preview';
require __DIR__ . '/../includes/header.php';
?>
<main class="certificate-page container-wide">
    <?php if ($certificateModule === null): ?>
        <header class="admin-page-heading">
            <a class="studio-back-link" href="students.php">Back to students</a>
            <p class="eyebrow">Certificate audit</p>
            <h1><?= learnify_h($student['name']) ?></h1>
            <p class="text-muted"><?= learnify_h($student['email']) ?> · <?= count($eligibleModules) ?> earned certificate<?= count($eligibleModules) === 1 ? '' : 's' ?></p>
        </header>
        <section class="glass-card admin-panel">
            <?php if ($eligibleModules === []): ?>
                <p class="text-muted">This student has not yet completed a module or passed a module exam.</p>
            <?php else: ?>
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead><tr><th>Module</th><th>Completion</th><th>Eligibility</th><th>Certificate</th></tr></thead>
                        <tbody>
                        <?php foreach ($eligibleModules as $module): ?>
                            <tr>
                                <td><?= learnify_h($module['name']) ?></td>
                                <td><?= (int) $module['completed_lessons'] ?>/<?= (int) $module['total_lessons'] ?> lessons</td>
                                <td><?= (int) $module['total_lessons'] > 0 && (int) $module['completed_lessons'] === (int) $module['total_lessons'] ? 'Lessons completed' : 'Passing exam' ?></td>
                                <td><a class="button button-secondary" href="certificates.php?user_id=<?= (int) $student['id'] ?>&amp;module_id=<?= (int) $module['id'] ?>">View / print</a></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    <?php else: ?>
        <div class="certificate-actions">
            <a class="button button-secondary" href="certificates.php?user_id=<?= (int) $student['id'] ?>"><i class="fa-solid fa-arrow-left" aria-hidden="true"></i> All certificates</a>
            <button class="button" type="button" data-print-certificate><i class="fa-solid fa-print" aria-hidden="true"></i> Print / Save certificate</button>
        </div>
        <article class="glass-card certificate-card">
            <div class="certificate-inner">
                <p class="eyebrow">Learnify · Certificate of Achievement</p>
                <div class="certificate-seal" aria-hidden="true"><i class="fa-solid fa-graduation-cap"></i></div>
                <h1>Certificate of Completion</h1>
                <p class="certificate-intro">This certificate is presented to</p>
                <p class="certificate-recipient"><?= learnify_h($student['name']) ?></p>
                <p class="certificate-intro">for successfully completing</p>
                <h2><?= learnify_h($certificateModule['name']) ?></h2>
                <p class="certificate-date">Awarded <?= learnify_h(date('F j, Y')) ?></p>
                <p class="certificate-eligibility"><?= (int) $certificateModule['total_lessons'] > 0 && (int) $certificateModule['completed_lessons'] === (int) $certificateModule['total_lessons'] ? 'All published lessons completed' : 'Module passing score achieved' ?></p>
            </div>
        </article>
        <script>document.querySelector('[data-print-certificate]').addEventListener('click', () => window.print());</script>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
