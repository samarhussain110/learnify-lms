<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$pageTitle = 'Dashboard';
$userId = (int) $_SESSION['user_id'];
$profileStatement = $pdo->prepare(
    'SELECT
        name,
        CASE
            WHEN last_study_date >= CURRENT_DATE - INTERVAL 1 DAY THEN study_streak
            ELSE 0
        END AS study_streak
     FROM users
     WHERE id = :user_id'
);
$profileStatement->execute(['user_id' => $userId]);
$profile = $profileStatement->fetch();
$userName = is_array($profile)
    ? (string) $profile['name']
    : (string) ($_SESSION['full_name'] ?? 'Learner');
$studyStreak = is_array($profile) ? (int) $profile['study_streak'] : 0;

$modulesStatement = $pdo->prepare(
    'SELECT
        modules.id,
        modules.name,
        modules.slug,
        modules.category,
        modules.description,
        modules.icon,
        COUNT(DISTINCT chapters.id) AS lesson_count,
        COUNT(DISTINCT progress.chapter_id) AS completed_lessons,
        EXISTS (
            SELECT 1
            FROM quiz_attempts
            INNER JOIN quizzes ON quizzes.id = quiz_attempts.quiz_id
            WHERE quiz_attempts.user_id = :exam_user_id
              AND quizzes.module_id = modules.id
              AND quizzes.is_published = 1
              AND quiz_attempts.score_percentage >= quizzes.passing_score
        ) AS passed_exam
     FROM modules
     LEFT JOIN chapters
        ON chapters.module_id = modules.id AND chapters.is_published = 1
     LEFT JOIN user_chapter_progress AS progress
        ON progress.chapter_id = chapters.id AND progress.user_id = :user_id
     WHERE modules.is_published = 1
     GROUP BY modules.id
     ORDER BY modules.display_order, modules.name'
);
$modulesStatement->execute(['user_id' => $userId, 'exam_user_id' => $userId]);
$modules = $modulesStatement->fetchAll();
$publishedModuleCount = count($modules);
$completedLessons = array_sum(array_map(
    static fn (array $module): int => (int) $module['completed_lessons'],
    $modules
));
$totalLessons = array_sum(array_map(
    static fn (array $module): int => (int) $module['lesson_count'],
    $modules
));
$lessonCompletionPercentage = $totalLessons > 0
    ? (int) round(($completedLessons / $totalLessons) * 100)
    : 0;

$testsStatement = $pdo->prepare(
    'SELECT COUNT(*) AS attempt_count, COALESCE(AVG(score_percentage), 0) AS average_score
     FROM quiz_attempts
     WHERE user_id = :user_id'
);
$testsStatement->execute(['user_id' => $userId]);
$quizStats = $testsStatement->fetch();
$testsTaken = (int) ($quizStats['attempt_count'] ?? 0);
$averageScore = (float) ($quizStats['average_score'] ?? 0);

require __DIR__ . '/../includes/header.php';
?>
<main>
    <section class="dashboard-hero">
        <div class="container-wide dashboard-hero-content">
            <div>
                <p class="eyebrow">Your learning space</p>
                <h1>Welcome back, <span class="text-gradient"><?= learnify_h($userName) ?></span></h1>
                <p class="text-muted">Pick up where you left off, or explore something new today.</p>
            </div>
            <div class="dashboard-stats" aria-label="Your learning statistics">
                <article class="stat-card glass-card">
                    <span class="stat-icon" aria-hidden="true"><i class="fa-solid fa-fire"></i></span>
                    <strong><?= $studyStreak ?></strong>
                    <span>Day study streak</span>
                </article>
                <article class="stat-card glass-card">
                    <span class="stat-icon" aria-hidden="true"><i class="fa-solid fa-book-open"></i></span>
                    <strong><?= $publishedModuleCount ?></strong>
                    <span>Published modules</span>
                </article>
                <article class="stat-card glass-card">
                    <span class="stat-icon" aria-hidden="true"><i class="fa-solid fa-clipboard-check"></i></span>
                    <strong><?= $testsTaken ?></strong>
                    <span>Quizzes attempted</span>
                    <small>Average score: <?= number_format($averageScore, 1) ?>%</small>
                </article>
                <article class="stat-card glass-card">
                    <span class="stat-icon" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
                    <strong><?= $completedLessons ?></strong>
                    <span>Lessons completed</span>
                    <small><?= $lessonCompletionPercentage ?>% across published modules</small>
                </article>
            </div>
        </div>
    </section>

    <section class="section container-wide" aria-labelledby="modules-heading">
        <div class="section-heading">
            <p class="eyebrow">Choose your next step</p>
            <h2 id="modules-heading">Explore learning modules</h2>
            <p>Build practical skills at your own pace with lessons, study resources, and quizzes.</p>
            <a class="button button-secondary" href="take-exam.php">
                Take an exam <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            </a>
        </div>

        <?php if ($modules === []): ?>
            <div class="glass-card card empty-state">
                <i class="fa-solid fa-book-open" aria-hidden="true"></i>
                <h3>No published modules yet</h3>
                <p class="text-muted mb-0">Please check back soon for new learning content.</p>
            </div>
        <?php else: ?>
            <div class="grid grid-4 module-grid">
                <?php foreach ($modules as $module): ?>
                    <article class="glass-card card module-card">
                        <span class="module-icon" aria-hidden="true">
                            <i class="<?= learnify_h($module['icon']) ?>"></i>
                        </span>
                        <span class="badge"><?= learnify_h($module['category']) ?></span>
                        <h3 class="module-title"><?= learnify_h($module['name']) ?></h3>
                        <p class="text-muted module-description"><?= learnify_h($module['description']) ?></p>
                        <?php
                        $lessonCount = (int) $module['lesson_count'];
                        $moduleCompleted = (int) $module['completed_lessons'];
                        $moduleProgress = $lessonCount > 0
                            ? (int) round(($moduleCompleted / $lessonCount) * 100)
                            : 0;
                        $isComplete = $lessonCount > 0 && $moduleCompleted >= $lessonCount;
                        $certificateUnlocked = $isComplete && (bool) $module['passed_exam'];
                        ?>
                        <span class="badge" aria-label="Module progress">
                            <?= $isComplete ? 'Complete' : ($moduleCompleted > 0 ? 'In progress' : 'Not started') ?>
                            · <?= $moduleCompleted ?>/<?= $lessonCount ?> lessons (<?= $moduleProgress ?>%)
                        </span>
                        <a class="button module-action" href="module.php?module_id=<?= (int) $module['id'] ?>">
                            <?= $moduleCompleted > 0 ? 'Continue learning' : 'Start learning' ?>
                            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </a>
                        <?php if ($certificateUnlocked): ?>
                            <a class="button certificate-download-button" href="download-certificate.php?module_id=<?= (int) $module['id'] ?>">
                                <i class="fa-solid fa-file-pdf" aria-hidden="true"></i> Download Certificate (PDF)
                            </a>
                        <?php else: ?>
                            <span class="certificate-locked-note" title="Complete all lessons and pass the module quiz to unlock your certificate">
                                <i class="fa-solid fa-lock" aria-hidden="true"></i>
                                Complete all lessons &amp; pass quiz to unlock certificate
                            </span>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
