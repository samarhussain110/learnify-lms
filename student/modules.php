<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$modulesStatement = $pdo->prepare(
    'SELECT
        modules.id,
        modules.name,
        modules.category,
        modules.description,
        modules.icon,
        GROUP_CONCAT(DISTINCT chapters.title SEPARATOR \' \') AS topic_keywords,
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
        ON chapters.module_id = modules.id
       AND chapters.is_published = 1
     LEFT JOIN user_chapter_progress AS progress
        ON progress.chapter_id = chapters.id
       AND progress.user_id = :user_id
     WHERE modules.is_published = 1
     GROUP BY modules.id
     ORDER BY modules.display_order, modules.name'
);
$modulesStatement->execute(['user_id' => $userId, 'exam_user_id' => $userId]);
$modules = $modulesStatement->fetchAll();
$pageTitle = 'Modules';

require __DIR__ . '/../includes/header.php';
?>
<main class="section container-wide">
    <div class="section-heading">
        <p class="eyebrow">Your curriculum</p>
        <h1>Learning modules</h1>
        <p>Explore published modules, track your lesson progress, and continue learning at your own pace.</p>
    </div>

    <?php if ($modules === []): ?>
        <div class="glass-card card empty-state">
            <i class="fa-solid fa-book-open" aria-hidden="true"></i>
            <h2>No published modules yet</h2>
            <p class="text-muted mb-0">Please check back soon for new learning content.</p>
        </div>
    <?php else: ?>
        <div class="modules-search-toolbar">
            <label class="sr-only" for="module-search">Search modules by title, topic, or keyword</label>
            <div class="modules-search-field">
                <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                <input
                    class="modules-search-input"
                    id="module-search"
                    type="search"
                    placeholder="Search modules by title, topic, or keyword..."
                    autocomplete="off"
                    aria-controls="modules-grid"
                >
                <button
                    class="modules-search-clear"
                    type="button"
                    data-clear-module-search
                    aria-label="Clear module search"
                    hidden
                >
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </div>
            <span class="badge modules-result-count" data-module-result-count role="status" aria-live="polite">
                Showing <?= count($modules) ?> of <?= count($modules) ?> modules
            </span>
        </div>

        <div class="grid grid-4 module-grid" id="modules-grid" data-modules-grid>
            <?php foreach ($modules as $module): ?>
                <?php
                $lessonCount = (int) $module['lesson_count'];
                $completedLessons = (int) $module['completed_lessons'];
                $progressPercentage = $lessonCount > 0
                    ? min(100, (int) round(($completedLessons / $lessonCount) * 100))
                    : 0;
                $isComplete = $lessonCount > 0 && $completedLessons >= $lessonCount;
                $certificateUnlocked = $isComplete && (bool) $module['passed_exam'];
                ?>
                <article
                    class="glass-card card module-card searchable-module-card"
                    data-module-search="<?= learnify_h(
                        $module['name'] . ' ' . $module['description'] . ' '
                        . $module['category'] . ' ' . ($module['topic_keywords'] ?? '')
                    ) ?>"
                >
                    <span class="module-icon" aria-hidden="true">
                        <i class="<?= learnify_h($module['icon']) ?>"></i>
                    </span>
                    <span class="badge"><?= learnify_h($module['category']) ?></span>
                    <h2 class="module-title"><?= learnify_h($module['name']) ?></h2>
                    <p class="text-muted module-description"><?= learnify_h($module['description']) ?></p>

                    <div class="w-full">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-muted"><?= $lessonCount ?> <?= $lessonCount === 1 ? 'lesson' : 'lessons' ?></span>
                            <span class="badge">
                                <?= $isComplete ? 'Complete' : ($completedLessons > 0 ? 'In progress' : 'Not started') ?>
                            </span>
                        </div>
                        <div
                            class="progress"
                            role="progressbar"
                            aria-label="<?= learnify_h($module['name']) ?> lesson progress"
                            aria-valuemin="0"
                            aria-valuemax="100"
                            aria-valuenow="<?= $progressPercentage ?>"
                        >
                            <div class="progress-bar" style="width: <?= $progressPercentage ?>%"></div>
                        </div>
                        <small class="text-muted">
                            <?= $completedLessons ?> of <?= $lessonCount ?> lessons · <?= $progressPercentage ?>%
                        </small>
                    </div>

                    <a class="button module-action" href="module.php?module_id=<?= (int) $module['id'] ?>">
                        <?= $completedLessons > 0 ? 'Continue' : 'Start Learning' ?>
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
        <div class="glass-card card empty-state modules-no-results" data-no-module-results hidden>
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
            <h2>No modules found matching your search</h2>
            <p class="text-muted">Try another title, topic, or keyword.</p>
            <button class="button button-secondary" type="button" data-reset-module-search>
                Reset search
            </button>
        </div>
        <script>
        (() => {
            const input = document.querySelector('#module-search');
            const grid = document.querySelector('[data-modules-grid]');
            if (!input || !grid) {
                return;
            }

            const cards = Array.from(grid.querySelectorAll('[data-module-search]'));
            const count = document.querySelector('[data-module-result-count]');
            const clearButton = document.querySelector('[data-clear-module-search]');
            const noResults = document.querySelector('[data-no-module-results]');
            const resetButton = document.querySelector('[data-reset-module-search]');
            const animationDuration = 180;
            let pendingHide = new WeakMap();
            let noResultsTimer = 0;

            const updateResults = () => {
                const query = input.value.trim().toLocaleLowerCase();
                let visibleCount = 0;
                window.clearTimeout(noResultsTimer);
                noResults.hidden = true;
                grid.hidden = false;

                cards.forEach((card) => {
                    const matches = card.dataset.moduleSearch.toLocaleLowerCase().includes(query);
                    const pendingTimer = pendingHide.get(card);
                    if (pendingTimer) {
                        window.clearTimeout(pendingTimer);
                        pendingHide.delete(card);
                    }

                    if (matches) {
                        if (card.hidden) {
                            card.hidden = false;
                            card.classList.add('is-filtering-out');
                            requestAnimationFrame(() => {
                                requestAnimationFrame(() => card.classList.remove('is-filtering-out'));
                            });
                        } else {
                            card.classList.remove('is-filtering-out');
                        }
                        visibleCount += 1;
                    } else if (!card.hidden && !card.classList.contains('is-filtering-out')) {
                        card.classList.add('is-filtering-out');
                        pendingHide.set(card, window.setTimeout(() => {
                            if (card.classList.contains('is-filtering-out')) {
                                card.hidden = true;
                            }
                            pendingHide.delete(card);
                        }, animationDuration));
                    }
                });

                count.textContent = `Showing ${visibleCount} of ${cards.length} modules`;
                clearButton.hidden = input.value.length === 0;
                if (visibleCount === 0) {
                    noResultsTimer = window.setTimeout(() => {
                        grid.hidden = true;
                        noResults.hidden = false;
                    }, animationDuration);
                }
            };

            input.addEventListener('input', updateResults);
            clearButton.addEventListener('click', () => {
                input.value = '';
                updateResults();
                input.focus();
            });
            resetButton.addEventListener('click', () => {
                input.value = '';
                updateResults();
                input.focus();
            });
        })();
        </script>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
