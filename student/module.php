<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../api/seed-lessons.php';

function learnify_lesson_video_embed_url(mixed $value): ?string
{
    if (!is_string($value) || $value === '' || strlen($value) > 255) {
        return null;
    }

    $parts = parse_url($value);
    if (
        !is_array($parts)
        || strtolower((string) ($parts['scheme'] ?? '')) !== 'https'
        || !in_array(strtolower((string) ($parts['host'] ?? '')), [
            'youtube.com',
            'www.youtube.com',
            'youtube-nocookie.com',
            'www.youtube-nocookie.com',
        ], true)
        || !preg_match('~^/embed/[A-Za-z0-9_-]{11}/?$~', (string) ($parts['path'] ?? ''))
    ) {
        return null;
    }

    return $value;
}

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$seedingFailed = false;
try {
    learnify_seed_lessons($pdo);
} catch (Throwable $exception) {
    error_log('Learnify automatic lesson seeding failed: ' . $exception->getMessage());
    $seedingFailed = true;
}

$moduleId = filter_input(INPUT_GET, 'module_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($moduleId === false || $moduleId === null) {
    header('Location: dashboard.php');
    exit;
}
$userId = (int) $_SESSION['user_id'];
$moduleStatement = $pdo->prepare(
    'SELECT id, name, category, description
     FROM modules
     WHERE id = :module_id AND is_published = 1
     LIMIT 1'
);
$moduleStatement->execute(['module_id' => $moduleId]);
$module = $moduleStatement->fetch();
if (!is_array($module)) {
    http_response_code(404);
    $pageTitle = 'Module not found';
    require __DIR__ . '/../includes/header.php';
    ?>
    <main class="section container">
        <div class="glass-card card empty-state">
            <h1>Module not found</h1>
            <p class="text-muted">This module may have been removed or is not available.</p>
            <a class="button" href="dashboard.php">Return to dashboard</a>
        </div>
    </main>
    <?php
    require __DIR__ . '/../includes/footer.php';
    exit;
}

$lessonsStatement = $pdo->prepare(
    'SELECT
        chapters.id,
        chapters.title,
        chapters.slug,
        chapters.description,
        chapters.content,
        chapters.video_url,
        chapters.estimated_minutes,
        chapters.display_order,
        (user_chapter_progress.chapter_id IS NOT NULL) AS is_completed
     FROM chapters
     LEFT JOIN user_chapter_progress
        ON user_chapter_progress.chapter_id = chapters.id
       AND user_chapter_progress.user_id = :user_id
     WHERE chapters.module_id = :module_id AND chapters.is_published = 1
     ORDER BY chapters.display_order, chapters.id'
);
$lessonsStatement->execute(['user_id' => $userId, 'module_id' => $moduleId]);
$lessons = $lessonsStatement->fetchAll();
$requestedLessonId = filter_input(INPUT_GET, 'lesson_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$selectedIndex = 0;
if ($requestedLessonId !== false && $requestedLessonId !== null) {
    foreach ($lessons as $index => $lesson) {
        if ((int) $lesson['id'] === $requestedLessonId) {
            $selectedIndex = $index;
            break;
        }
    }
}
$selectedLesson = $lessons[$selectedIndex] ?? null;
$completedCount = count(array_filter($lessons, static fn (array $lesson): bool => (bool) $lesson['is_completed']));
$lessonCount = count($lessons);
$completionPercentage = $lessonCount > 0 ? (int) round(($completedCount / $lessonCount) * 100) : 0;
$passedExamStatement = $pdo->prepare(
    'SELECT 1
     FROM quiz_attempts
     INNER JOIN quizzes ON quizzes.id = quiz_attempts.quiz_id
     WHERE quiz_attempts.user_id = :user_id
       AND quizzes.module_id = :module_id
       AND quiz_attempts.score_percentage >= quizzes.passing_score
     LIMIT 1'
);
$passedExamStatement->execute(['user_id' => $userId, 'module_id' => $moduleId]);
$hasPassedExam = $passedExamStatement->fetchColumn() !== false;
$moduleCompleted = $lessonCount > 0 && $completedCount === $lessonCount;
$completionBannerVisible = $moduleCompleted || $hasPassedExam;
$certificateAvailable = $completionBannerVisible;
$csrfToken = learnify_csrf_token();
$pageTitle = (string) $module['name'] . ' Lessons';

$lessonContent = null;
$lessonVideoUrl = learnify_lesson_video_embed_url($selectedLesson['video_url'] ?? null);
if ($selectedLesson !== null) {
    try {
        $decodedContent = json_decode((string) $selectedLesson['content'], true, 32, JSON_THROW_ON_ERROR);
        if (is_array($decodedContent) && (int) ($decodedContent['version'] ?? 0) === 1) {
            $lessonContent = $decodedContent;
        }
    } catch (JsonException) {
        $lessonContent = null;
    }
}

require __DIR__ . '/../includes/header.php';
?>
<main
    class="lesson-page container-wide"
    data-chat-module-id="<?= (int) $moduleId ?>"
    data-chat-lesson-id="<?= (int) ($selectedLesson['id'] ?? 0) ?>"
>
    <header class="lesson-page-header">
        <a class="studio-back-link" href="dashboard.php">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> All modules
        </a>
        <p class="eyebrow"><?= learnify_h($module['category']) ?></p>
        <h1><?= learnify_h($module['name']) ?></h1>
        <p class="text-muted"><?= learnify_h($module['description']) ?></p>
        <?php if ($seedingFailed): ?>
            <p class="form-alert form-alert-error" role="alert">Some lesson content could not be prepared. Please try again later.</p>
        <?php endif; ?>
    </header>

    <section
        class="glass-card module-completion-banner"
        data-module-completion-banner
        data-module-name="<?= learnify_h($module['name']) ?>"
        aria-labelledby="module-completion-title"
        <?= !$completionBannerVisible ? 'hidden' : '' ?>
    >
        <div class="module-completion-message">
            <span class="module-completion-icon" aria-hidden="true"><i class="fa-solid fa-award"></i></span>
            <div>
                <p class="eyebrow" data-completion-banner-eyebrow><?= $moduleCompleted ? 'Module complete' : 'Exam passed' ?></p>
                <h2 id="module-completion-title" data-completion-banner-title>
                    <?= $moduleCompleted
                        ? 'Congratulations! You completed ' . learnify_h($module['name']) . '.'
                        : 'Congratulations! You passed the ' . learnify_h($module['name']) . ' exam.' ?>
                </h2>
                <p class="text-muted" data-completion-banner-description>
                    <?= $moduleCompleted
                        ? 'Your lesson progress is saved. Celebrate your progress or put your knowledge to the test.'
                        : 'Complete all lessons and pass the module quiz to unlock your certificate.' ?>
                </p>
            </div>
        </div>
        <div class="module-completion-actions">
            <a class="button" href="take-exam.php?module_id=<?= (int) $moduleId ?>">
                <i class="fa-solid fa-clipboard-question" aria-hidden="true"></i> Take Final Quiz / Exam
            </a>
            <div class="certificate-action-group" data-certificate-action-group <?= !$certificateAvailable ? 'hidden' : '' ?>>
                <a
                    class="button button-secondary"
                    href="certificate.php?module_id=<?= (int) $moduleId ?>"
                    data-certificate-action
                >
                    <i class="fa-solid fa-certificate" aria-hidden="true"></i> Claim / View Certificate
                </a>
                <small class="certificate-action-hint">
                    Requires 100% lesson completion + passing module quiz score.
                </small>
            </div>
        </div>
    </section>

    <div class="lesson-layout">
        <aside class="glass-card lesson-sidebar" aria-labelledby="lesson-outline-title">
            <div class="lesson-progress-heading">
                <div>
                    <p class="eyebrow">Module progress</p>
                    <h2 id="lesson-outline-title">Lesson outline</h2>
                </div>
                <strong data-completion-percentage><?= $completionPercentage ?>%</strong>
            </div>
            <div class="lesson-progress-track" role="progressbar" aria-label="Module completion" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= $completionPercentage ?>" data-progress-track>
                <span style="width: <?= $completionPercentage ?>%" data-progress-fill></span>
            </div>
            <p class="text-muted" data-progress-count><?= $completedCount ?> of <?= $lessonCount ?> lessons completed</p>
            <nav class="lesson-navigation" aria-label="Lessons in this module">
                <?php foreach ($lessons as $index => $lesson): ?>
                    <a
                        class="lesson-navigation-link<?= $index === $selectedIndex ? ' is-active' : '' ?>"
                        href="module.php?module_id=<?= (int) $moduleId ?>&amp;lesson_id=<?= (int) $lesson['id'] ?>"
                        <?= $index === $selectedIndex ? 'aria-current="page"' : '' ?>
                    >
                        <span class="lesson-navigation-number"><?= $index + 1 ?></span>
                        <span class="lesson-navigation-title"><?= learnify_h($lesson['title']) ?></span>
                        <?php if ((bool) $lesson['is_completed']): ?>
                            <i class="fa-solid fa-circle-check lesson-complete-icon" aria-label="Completed"></i>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </nav>
            <?php if ($lessonVideoUrl !== null): ?>
                <div class="sidebar-video-title">Lesson Video</div>
                <div class="sidebar-video-frame">
                    <iframe
                        src="<?= learnify_h($lessonVideoUrl) ?>"
                        title="<?= learnify_h('Lesson video: ' . $selectedLesson['title']) ?>"
                        width="100%"
                        height="100%"
                        loading="lazy"
                        referrerpolicy="strict-origin-when-cross-origin"
                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                        allowfullscreen
                    ></iframe>
                </div>
            <?php endif; ?>
        </aside>

        <section class="glass-card lesson-content-panel" aria-labelledby="active-lesson-title">
            <?php if ($selectedLesson === null): ?>
                <div class="empty-state">
                    <i class="fa-solid fa-book-open" aria-hidden="true"></i>
                    <h2>No lessons are available yet</h2>
                    <p class="text-muted">Published lessons for this module will appear here.</p>
                </div>
            <?php else: ?>
                <div class="lesson-content-header">
                    <div>
                        <p class="eyebrow">Lesson <?= $selectedIndex + 1 ?> of <?= $lessonCount ?>
                            <?php if ($selectedLesson['estimated_minutes'] !== null): ?>
                                · <?= (int) $selectedLesson['estimated_minutes'] ?> min
                            <?php endif; ?>
                        </p>
                        <h2 id="active-lesson-title"><?= learnify_h($selectedLesson['title']) ?></h2>
                    </div>
                    <span class="badge" data-lesson-status><?= (bool) $selectedLesson['is_completed'] ? 'Completed' : 'In progress' ?></span>
                </div>

                <?php if ($lessonContent !== null): ?>
                    <?php if (is_string($lessonContent['summary'] ?? null)): ?>
                        <p class="lesson-summary"><?= learnify_h($lessonContent['summary']) ?></p>
                    <?php endif; ?>
                    <?php foreach (($lessonContent['sections'] ?? []) as $section): ?>
                        <?php if (!is_array($section)): continue; endif; ?>
                        <section class="lesson-section">
                            <h3><?= learnify_h((string) ($section['heading'] ?? 'Lesson')) ?></h3>
                            <?php if (is_string($section['body'] ?? null)): ?>
                                <p><?= nl2br(learnify_h($section['body'])) ?></p>
                            <?php endif; ?>
                            <?php if (is_string($section['code'] ?? null)): ?>
                                <pre class="lesson-code"><code><?= learnify_h(str_replace('\\n', "\n", $section['code'])) ?></code></pre>
                            <?php endif; ?>
                            <?php if (is_array($section['breakdown'] ?? null)): ?>
                                <h4><?= learnify_h((string) ($section['breakdown_title'] ?? 'Implementation notes')) ?></h4>
                                <ul>
                                    <?php foreach ($section['breakdown'] as $item): ?>
                                        <?php if (is_string($item)): ?>
                                            <li><?= learnify_h($item) ?></li>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </section>
                    <?php endforeach; ?>
                    <?php if (is_array($lessonContent['takeaways'] ?? null)): ?>
                        <section class="lesson-takeaways">
                            <h3><i class="fa-solid fa-lightbulb" aria-hidden="true"></i> Key takeaways</h3>
                            <ul>
                                <?php foreach ($lessonContent['takeaways'] as $takeaway): ?>
                                    <?php if (is_string($takeaway)): ?>
                                        <li><?= learnify_h($takeaway) ?></li>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </ul>
                        </section>
                    <?php endif; ?>
                <?php else: ?>
                    <p class="lesson-summary"><?= learnify_h((string) ($selectedLesson['description'] ?? '')) ?></p>
                    <div class="lesson-legacy-content"><?= nl2br(learnify_h((string) ($selectedLesson['content'] ?? ''))) ?></div>
                <?php endif; ?>

                <div class="lesson-actions">
                    <?php if ($selectedIndex > 0): ?>
                        <a class="button button-secondary" href="module.php?module_id=<?= (int) $moduleId ?>&amp;lesson_id=<?= (int) $lessons[$selectedIndex - 1]['id'] ?>">
                            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Previous Lesson
                        </a>
                    <?php else: ?>
                        <span></span>
                    <?php endif; ?>
                    <button
                        class="button<?= (bool) $selectedLesson['is_completed'] ? ' button-secondary' : '' ?>"
                        type="button"
                        data-complete-lesson
                        data-completed="<?= (bool) $selectedLesson['is_completed'] ? 'true' : 'false' ?>"
                        data-chapter-id="<?= (int) $selectedLesson['id'] ?>"
                        data-module-id="<?= (int) $moduleId ?>"
                        data-csrf-token="<?= learnify_h($csrfToken) ?>"
                        aria-pressed="<?= (bool) $selectedLesson['is_completed'] ? 'true' : 'false' ?>"
                    >
                        <i class="fa-solid <?= (bool) $selectedLesson['is_completed'] ? 'fa-circle-check' : 'fa-check' ?>" aria-hidden="true"></i>
                        <span data-completion-button-label><?= (bool) $selectedLesson['is_completed'] ? 'Completed ✓' : 'Mark as Completed' ?></span>
                    </button>
                    <?php if ($selectedIndex < $lessonCount - 1): ?>
                        <a class="button" href="module.php?module_id=<?= (int) $moduleId ?>&amp;lesson_id=<?= (int) $lessons[$selectedIndex + 1]['id'] ?>">
                            Next Lesson <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </a>
                    <?php else: ?>
                        <span></span>
                    <?php endif; ?>
                </div>
                <p class="lesson-feedback text-muted" data-completion-feedback role="status" aria-live="polite"></p>
            <?php endif; ?>
        </section>
    </div>
</main>
<?php if ($selectedLesson !== null): ?>
<script>
(() => {
    const hasPassedExam = <?= $hasPassedExam ? 'true' : 'false' ?>;
    const activeLink = document.querySelector('.lesson-navigation-link[aria-current="page"]');
    if (activeLink) {
        activeLink.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'nearest' });
    }

    const button = document.querySelector('[data-complete-lesson]');
    if (!button) return;

    button.addEventListener('click', async () => {
        const markComplete = button.dataset.completed !== 'true';
        button.disabled = true;
        const feedback = document.querySelector('[data-completion-feedback]');
        feedback.classList.remove('is-error');
        feedback.textContent = markComplete ? 'Saving your progress...' : 'Updating your progress...';
        try {
            const response = await fetch('../api/complete-lesson.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': button.dataset.csrfToken
                },
                body: JSON.stringify({
                    module_id: Number(button.dataset.moduleId),
                    chapter_id: Number(button.dataset.chapterId),
                    action: markComplete ? 'complete' : 'incomplete'
                })
            });
            const result = await response.json();
            if (!response.ok || result.success !== true) {
                throw new Error(result.message || 'Lesson progress could not be saved.');
            }

            button.dataset.completed = result.completed ? 'true' : 'false';
            button.setAttribute('aria-pressed', result.completed ? 'true' : 'false');
            button.classList.toggle('button-secondary', result.completed);
            button.querySelector('i').className = `fa-solid ${result.completed ? 'fa-circle-check' : 'fa-check'}`;
            button.querySelector('[data-completion-button-label]').textContent =
                result.completed ? 'Completed ✓' : 'Mark as Completed';
            button.title = result.completed ? 'Click to mark this lesson incomplete' : '';
            document.querySelector('[data-lesson-status]').textContent =
                result.completed ? 'Completed' : 'In progress';
            document.querySelector('[data-completion-percentage]').textContent = `${result.completion_percentage}%`;
            document.querySelector('[data-progress-count]').textContent =
                `${result.completed_lessons} of ${result.total_lessons} lessons completed`;
            const track = document.querySelector('[data-progress-track]');
            track.setAttribute('aria-valuenow', String(result.completion_percentage));
            document.querySelector('[data-progress-fill]').style.width = `${result.completion_percentage}%`;

            const lessonLink = document.querySelector('.lesson-navigation-link[aria-current="page"]');
            if (lessonLink && result.completed && !lessonLink.querySelector('.lesson-complete-icon')) {
                const icon = document.createElement('i');
                icon.className = 'fa-solid fa-circle-check lesson-complete-icon';
                icon.setAttribute('aria-label', 'Completed');
                lessonLink.append(icon);
            } else if (lessonLink && !result.completed) {
                lessonLink.querySelector('.lesson-complete-icon')?.remove();
            }
            const completionBanner = document.querySelector('[data-module-completion-banner]');
            completionBanner.hidden = result.module_completed !== true && !hasPassedExam;
            if (result.module_completed) {
                document.querySelector('[data-completion-banner-eyebrow]').textContent = 'Module complete';
                document.querySelector('[data-completion-banner-title]').textContent =
                    `Congratulations! You completed ${completionBanner.dataset.moduleName}.`;
                document.querySelector('[data-completion-banner-description]').textContent =
                    'Your lesson progress is saved. Celebrate your progress or put your knowledge to the test.';
            } else if (hasPassedExam) {
                document.querySelector('[data-completion-banner-eyebrow]').textContent = 'Exam passed';
                document.querySelector('[data-completion-banner-title]').textContent =
                    `Congratulations! You passed the ${completionBanner.dataset.moduleName} exam.`;
                document.querySelector('[data-completion-banner-description]').textContent =
                    'Complete all lessons and pass the module quiz to unlock your certificate.';
            }
            const certificateActionGroup = document.querySelector('[data-certificate-action-group]');
            certificateActionGroup.hidden = result.module_completed !== true && !hasPassedExam;
            feedback.textContent = result.completed ? 'Progress saved.' : 'Lesson marked as incomplete.';
        } catch (error) {
            feedback.textContent = error instanceof Error ? error.message : 'Lesson progress could not be saved.';
            feedback.classList.add('is-error');
        } finally {
            button.disabled = false;
        }
    });
})();
</script>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
