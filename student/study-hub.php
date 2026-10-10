<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$userId = (int) $_SESSION['user_id'];
$moduleId = filter_input(INPUT_GET, 'module_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($moduleId === false || $moduleId === null) {
    header('Location: dashboard.php');
    exit;
}

$moduleStatement = $pdo->prepare(
    'SELECT id, name, category, description
     FROM modules
     WHERE id = :module_id AND is_published = 1
     LIMIT 1'
);
$moduleStatement->execute(['module_id' => $moduleId]);
$module = $moduleStatement->fetch();
if ($module === false) {
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

$csrfToken = learnify_csrf_token();
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && learnify_post_string('action') === 'complete_chapter') {
    if (!learnify_csrf_is_valid($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        exit('Your session token is invalid. Refresh and try again.');
    }

    $chapterIdToComplete = filter_var(
        $_POST['chapter_id'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );
    if (!is_int($chapterIdToComplete)) {
        http_response_code(422);
        exit('A valid chapter is required.');
    }

    $chapterOwnershipStatement = $pdo->prepare(
        'SELECT id
         FROM chapters
         WHERE id = :chapter_id
           AND module_id = :module_id
           AND is_published = 1
         LIMIT 1'
    );
    $chapterOwnershipStatement->execute([
        'chapter_id' => $chapterIdToComplete,
        'module_id' => $moduleId,
    ]);
    if ($chapterOwnershipStatement->fetchColumn() === false) {
        http_response_code(404);
        exit('This chapter is not available in the selected module.');
    }

    $completeChapterStatement = $pdo->prepare(
        'INSERT IGNORE INTO user_chapter_progress (user_id, chapter_id)
         VALUES (:user_id, :chapter_id)'
    );
    $completeChapterStatement->execute([
        'user_id' => $userId,
        'chapter_id' => $chapterIdToComplete,
    ]);

    $returnResourceId = filter_var(
        $_POST['resource_id'] ?? null,
        FILTER_VALIDATE_INT,
        ['options' => ['min_range' => 1]]
    );
    $redirectUrl = 'study-hub.php?module_id=' . (int) $moduleId;
    if (is_int($returnResourceId)) {
        $redirectUrl .= '&resource_id=' . $returnResourceId;
    }
    header('Location: ' . $redirectUrl);
    exit;
}

$chaptersStatement = $pdo->prepare(
    'SELECT
        chapters.id AS chapter_id,
        chapters.title AS chapter_title,
        chapters.description AS chapter_description,
        chapters.content AS chapter_content,
        chapters.display_order AS chapter_order,
        resources.id AS resource_id,
        resources.title AS resource_title,
        resources.description AS resource_description,
        resources.resource_type,
        resources.url,
        resources.file_path,
        (user_chapter_progress.chapter_id IS NOT NULL) AS is_completed
     FROM chapters
     LEFT JOIN resources ON resources.chapter_id = chapters.id
     LEFT JOIN user_chapter_progress
        ON user_chapter_progress.chapter_id = chapters.id
       AND user_chapter_progress.user_id = :user_id
     WHERE chapters.module_id = :module_id AND chapters.is_published = 1
     ORDER BY chapters.display_order, chapters.id, resources.display_order, resources.id'
);
$chaptersStatement->execute(['user_id' => $userId, 'module_id' => $moduleId]);
$rows = $chaptersStatement->fetchAll();

$chapters = [];
$resources = [];
foreach ($rows as $row) {
    $chapterId = (int) $row['chapter_id'];
    if (!isset($chapters[$chapterId])) {
        $chapters[$chapterId] = [
            'id' => $chapterId,
            'title' => (string) $row['chapter_title'],
            'description' => (string) ($row['chapter_description'] ?? ''),
            'content' => (string) ($row['chapter_content'] ?? ''),
            'is_completed' => (bool) $row['is_completed'],
            'resources' => [],
        ];
    }
    if ($row['resource_id'] !== null) {
        $resource = [
            'id' => (int) $row['resource_id'],
            'chapter_id' => $chapterId,
            'chapter_title' => (string) $row['chapter_title'],
            'title' => (string) $row['resource_title'],
            'description' => (string) ($row['resource_description'] ?? ''),
            'type' => (string) $row['resource_type'],
            'url' => (string) ($row['url'] ?? ''),
            'file_path' => (string) ($row['file_path'] ?? ''),
        ];
        $chapters[$chapterId]['resources'][] = $resource;
        $resources[] = $resource;
    }
}

$requestedResourceId = filter_input(
    INPUT_GET,
    'resource_id',
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);
$selectedResource = null;
if ($requestedResourceId !== false && $requestedResourceId !== null) {
    foreach ($resources as $resource) {
        if ($resource['id'] === $requestedResourceId) {
            $selectedResource = $resource;
            break;
        }
    }
}
if ($selectedResource === null && $resources !== []) {
    $selectedResource = $resources[0];
}

$selectedChapter = null;
$noteContent = '';
$cheatSheet = '';
if ($selectedResource !== null) {
    $selectedChapter = $chapters[$selectedResource['chapter_id']];
    $notesStatement = $pdo->prepare(
        'SELECT note_content
         FROM student_notes
         WHERE user_id = :user_id AND module_id = :module_id AND chapter_id = :chapter_id
         ORDER BY updated_at DESC, id DESC
         LIMIT 1'
    );
    $notesStatement->execute([
        'user_id' => $userId,
        'module_id' => $moduleId,
        'chapter_id' => $selectedResource['chapter_id'],
    ]);
    $savedNote = $notesStatement->fetchColumn();
    $noteContent = $savedNote === false ? '' : (string) $savedNote;
    $cheatSheet = $selectedResource['description'] !== ''
        ? $selectedResource['description']
        : ($selectedChapter['description'] !== '' ? $selectedChapter['description'] : $selectedChapter['content']);
}

$pageTitle = (string) $module['name'] . ' Study Hub';

function learnify_study_viewer(array $resource): array
{
    $candidate = $resource['url'] !== '' ? $resource['url'] : $resource['file_path'];
    if ($candidate === '' || preg_match('/[\x00-\x20]/', $candidate) === 1) {
        return ['kind' => 'none', 'src' => ''];
    }

    if (str_starts_with($candidate, '/')) {
        $src = $candidate;
    } elseif (filter_var($candidate, FILTER_VALIDATE_URL)) {
        $scheme = strtolower((string) parse_url($candidate, PHP_URL_SCHEME));
        if (!in_array($scheme, ['http', 'https'], true)) {
            return ['kind' => 'none', 'src' => ''];
        }
        $src = $candidate;
    } else {
        if (str_contains($candidate, '..') || str_starts_with($candidate, '\\')) {
            return ['kind' => 'none', 'src' => ''];
        }
        $src = '../' . ltrim($candidate, '/');
    }

    $path = strtolower((string) parse_url($src, PHP_URL_PATH));
    if ($resource['type'] === 'video') {
        $host = strtolower((string) parse_url($src, PHP_URL_HOST));
        if ($host === 'youtu.be') {
            $videoId = trim((string) parse_url($src, PHP_URL_PATH), '/');
            if (preg_match('/^[A-Za-z0-9_-]{11}$/', $videoId) === 1) {
                return ['kind' => 'video-embed', 'src' => 'https://www.youtube-nocookie.com/embed/' . $videoId];
            }
        }
        if (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com'], true)) {
            parse_str((string) parse_url($src, PHP_URL_QUERY), $query);
            $videoId = (string) ($query['v'] ?? '');
            if (str_starts_with((string) parse_url($src, PHP_URL_PATH), '/embed/')) {
                $videoId = basename((string) parse_url($src, PHP_URL_PATH));
            }
            if (preg_match('/^[A-Za-z0-9_-]{11}$/', $videoId) === 1) {
                return ['kind' => 'video-embed', 'src' => 'https://www.youtube-nocookie.com/embed/' . $videoId];
            }
        }
        if (str_ends_with($path, '.mp4') || str_ends_with($path, '.webm') || str_ends_with($path, '.ogg')) {
            return ['kind' => 'video-file', 'src' => $src];
        }
        return ['kind' => 'link', 'src' => $src];
    }
    if ($resource['type'] === 'file' || str_ends_with($path, '.pdf')) {
        return ['kind' => 'pdf', 'src' => $src];
    }

    return ['kind' => 'link', 'src' => $src];
}

$viewer = $selectedResource === null
    ? ['kind' => 'none', 'src' => '']
    : learnify_study_viewer($selectedResource);

require __DIR__ . '/../includes/header.php';
?>
<main class="studio-page">
    <div class="container-wide studio-topbar">
        <div>
            <a class="studio-back-link" href="dashboard.php">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> All modules
            </a>
            <p class="eyebrow"><?= learnify_h($module['category']) ?></p>
            <h1><?= learnify_h($module['name']) ?></h1>
            <p class="text-muted mb-0"><?= learnify_h($module['description']) ?></p>
        </div>
        <span class="badge"><?= count($chapters) ?> chapters</span>
    </div>

    <div class="container-wide studio-layout">
        <section class="studio-viewer glass-card" aria-labelledby="viewer-heading">
            <div class="studio-panel-heading">
                <div>
                    <p class="eyebrow">Learning studio</p>
                    <h2 id="viewer-heading"><?= $selectedResource !== null
                        ? learnify_h($selectedResource['title'])
                        : 'Your learning materials' ?></h2>
                </div>
                <?php if ($selectedResource !== null): ?>
                    <span class="badge"><?= learnify_h(ucfirst($selectedResource['type'])) ?></span>
                <?php endif; ?>
            </div>

            <div class="viewer-frame">
                <?php if ($selectedResource === null): ?>
                    <div class="viewer-placeholder">
                        <i class="fa-solid fa-photo-film" aria-hidden="true"></i>
                        <h3>No learning resources yet</h3>
                        <p>Published chapter resources will appear here when they are added.</p>
                    </div>
                <?php elseif ($viewer['kind'] === 'pdf'): ?>
                    <iframe
                        class="document-viewer"
                        src="<?= learnify_h($viewer['src']) ?>"
                        title="<?= learnify_h($selectedResource['title']) ?>"
                        sandbox="allow-scripts"
                        referrerpolicy="no-referrer"
                    ></iframe>
                <?php elseif ($viewer['kind'] === 'video-embed'): ?>
                    <iframe
                        class="video-viewer"
                        src="<?= learnify_h($viewer['src']) ?>"
                        title="<?= learnify_h($selectedResource['title']) ?>"
                        allow="accelerometer; autoplay; encrypted-media; picture-in-picture"
                        allowfullscreen
                        referrerpolicy="no-referrer"
                    ></iframe>
                <?php elseif ($viewer['kind'] === 'video-file'): ?>
                    <video class="video-viewer" controls preload="metadata">
                        <source src="<?= learnify_h($viewer['src']) ?>">
                        Your browser does not support embedded video.
                    </video>
                <?php elseif ($viewer['kind'] === 'link'): ?>
                    <div class="viewer-placeholder">
                        <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
                        <h3>Open this learning resource</h3>
                        <p>The resource is available in a new tab.</p>
                        <a class="button" href="<?= learnify_h($viewer['src']) ?>" target="_blank" rel="noopener noreferrer">
                            Open resource <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
                        </a>
                    </div>
                <?php else: ?>
                    <div class="viewer-placeholder">
                        <i class="fa-solid fa-circle-info" aria-hidden="true"></i>
                        <h3>Resource preview unavailable</h3>
                        <p>This resource has no supported video or PDF address.</p>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($selectedChapter !== null): ?>
                <article class="chapter-reading">
                    <h3><?= learnify_h($selectedChapter['title']) ?></h3>
                    <?php if ($selectedChapter['content'] !== ''): ?>
                        <div><?= nl2br(learnify_h($selectedChapter['content'])) ?></div>
                    <?php endif; ?>
                    <form method="post" class="mt-2">
                        <input type="hidden" name="action" value="complete_chapter">
                        <input type="hidden" name="csrf_token" value="<?= learnify_h($csrfToken) ?>">
                        <input type="hidden" name="chapter_id" value="<?= (int) $selectedChapter['id'] ?>">
                        <input type="hidden" name="resource_id" value="<?= (int) $selectedResource['id'] ?>">
                        <button class="button<?= $selectedChapter['is_completed'] ? ' button-secondary' : '' ?>" type="submit" <?= $selectedChapter['is_completed'] ? 'disabled' : '' ?>>
                            <i class="fa-solid <?= $selectedChapter['is_completed'] ? 'fa-circle-check' : 'fa-check' ?>" aria-hidden="true"></i>
                            <?= $selectedChapter['is_completed'] ? 'Lesson completed' : 'Mark lesson complete' ?>
                        </button>
                    </form>
                </article>
            <?php endif; ?>
        </section>

        <aside class="studio-sidebar">
            <section class="glass-card studio-sidebar-panel" aria-labelledby="outline-heading">
                <div class="studio-panel-heading">
                    <div>
                        <p class="eyebrow">Study plan</p>
                        <h2 id="outline-heading">Chapter outline</h2>
                    </div>
                </div>
                <div class="chapter-outline">
                    <?php if ($chapters === []): ?>
                        <p class="text-muted mb-0">No published chapters are available yet.</p>
                    <?php else: ?>
                        <?php foreach ($chapters as $chapter): ?>
                            <section class="outline-chapter">
                                <h3>
                                    <?= learnify_h($chapter['title']) ?>
                                    <?php if ($chapter['is_completed']): ?>
                                        <span class="badge">Complete</span>
                                    <?php endif; ?>
                                </h3>
                                <?php if ($chapter['resources'] === []): ?>
                                    <p class="outline-empty text-muted">Resources coming soon</p>
                                <?php else: ?>
                                    <ul>
                                        <?php foreach ($chapter['resources'] as $resource): ?>
                                            <li>
                                                <a
                                                    class="outline-resource<?= $selectedResource !== null && $selectedResource['id'] === $resource['id'] ? ' is-active' : '' ?>"
                                                    href="study-hub.php?module_id=<?= (int) $moduleId ?>&amp;resource_id=<?= $resource['id'] ?>"
                                                >
                                                    <i class="<?= $resource['type'] === 'video' ? 'fa-solid fa-circle-play' : ($resource['type'] === 'file' ? 'fa-solid fa-file-pdf' : 'fa-solid fa-file-lines') ?>" aria-hidden="true"></i>
                                                    <span><?= learnify_h($resource['title']) ?></span>
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                            </section>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>

            <section class="glass-card studio-sidebar-panel cheat-sheet" aria-labelledby="cheat-sheet-heading">
                <div class="studio-panel-heading">
                    <div>
                        <p class="eyebrow">Quick review</p>
                        <h2 id="cheat-sheet-heading">Cheat sheet</h2>
                    </div>
                    <i class="fa-solid fa-bolt" aria-hidden="true"></i>
                </div>
                <p class="text-muted mb-0"><?= $cheatSheet !== ''
                    ? nl2br(learnify_h($cheatSheet))
                    : 'Quick revision notes for this lesson will appear here when provided.' ?></p>
            </section>

            <section class="glass-card studio-sidebar-panel notes-panel" aria-labelledby="notes-heading">
                <div class="studio-panel-heading">
                    <div>
                        <p class="eyebrow">Just for you</p>
                        <h2 id="notes-heading">Personal notes</h2>
                    </div>
                    <span class="note-save-status" data-note-status aria-live="polite">Saved</span>
                </div>
                <label class="sr-only" for="student-notes">Your personal notes</label>
                <textarea
                    class="textarea notes-textarea"
                    id="student-notes"
                    data-note-editor
                    data-resource-id="<?= $selectedResource === null ? '' : (int) $selectedResource['id'] ?>"
                    data-csrf-token="<?= learnify_h($csrfToken) ?>"
                    placeholder="<?= $selectedResource === null ? 'Select a resource to take chapter notes.' : 'Capture key ideas, questions, and reminders...' ?>"
                    maxlength="100000"
                    <?= $selectedResource === null ? 'disabled' : '' ?>
                ><?= learnify_h($noteContent) ?></textarea>
                <p class="notes-help text-muted">Your notes save automatically. Notes are stored per chapter.</p>
            </section>
        </aside>
    </div>
</main>
<script>
(() => {
    const editor = document.querySelector('[data-note-editor]');
    const status = document.querySelector('[data-note-status]');
    if (!editor || !status || editor.disabled) {
        return;
    }

    let debounceTimer;
    let inFlight = null;
    let isDirty = false;

    const saveNotes = () => {
        if (inFlight) {
            return inFlight.then((saved) => saved && isDirty ? saveNotes() : saved);
        }
        if (!isDirty) {
            return Promise.resolve(true);
        }

        const noteContent = editor.value;
        isDirty = false;
        status.textContent = 'Saving...';
        inFlight = fetch('../api/save-note.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': editor.dataset.csrfToken || ''
            },
            body: JSON.stringify({
                resource_id: Number(editor.dataset.resourceId),
                note_content: noteContent
            })
        }).then(async (response) => {
            const result = await response.json();
            if (!response.ok || result.success !== true) {
                throw new Error(result.message || 'Your notes could not be saved.');
            }
            isDirty = editor.value !== noteContent;
            status.textContent = isDirty ? 'Unsaved changes' : 'Saved';
            return true;
        }).catch((error) => {
            isDirty = true;
            status.textContent = error.message || 'Save failed';
            return false;
        }).finally(() => {
            inFlight = null;
        });

        return inFlight.then((saved) => saved && isDirty ? saveNotes() : saved);
    };

    editor.addEventListener('input', () => {
        isDirty = true;
        status.textContent = 'Unsaved changes';
        window.clearTimeout(debounceTimer);
        debounceTimer = window.setTimeout(saveNotes, 700);
    });

    document.querySelectorAll('.outline-resource').forEach((link) => {
        link.addEventListener('click', async (event) => {
            if (!isDirty && !inFlight) {
                return;
            }
            event.preventDefault();
            window.clearTimeout(debounceTimer);
            await saveNotes();
            if (!isDirty) {
                window.location.assign(link.href);
            }
        });
    });

    window.addEventListener('beforeunload', (event) => {
        if (isDirty) {
            event.preventDefault();
            event.returnValue = '';
        }
    });
})();
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
