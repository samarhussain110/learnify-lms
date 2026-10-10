<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/admin-auth.php';

function learnify_admin_slug(string $value): string
{
    $value = strtolower(trim($value));
    $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
    return trim($value, '-');
}

function learnify_admin_length(string $value): int
{
    return function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
}

function learnify_admin_video_embed_url(string $value): ?string
{
    $value = trim($value);
    if ($value === '') {
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
        || strlen($value) > 255
    ) {
        throw new InvalidArgumentException('Enter a valid HTTPS YouTube embed URL, or leave the field blank.');
    }

    return $value;
}

function learnify_admin_optional_id(string $key): ?int
{
    $value = $_POST[$key] ?? '0';
    if ($value === '0' || $value === 0 || $value === '') {
        return null;
    }

    $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id === false || $id === null) {
        throw new InvalidArgumentException('The selected record is invalid. Please reopen the edit form.');
    }

    return $id;
}

$errorMessage = '';
$successMessage = '';
if (isset($_SESSION['admin_flash']) && is_string($_SESSION['admin_flash'])) {
    $successMessage = $_SESSION['admin_flash'];
    unset($_SESSION['admin_flash']);
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    if (!learnify_csrf_is_valid($_POST['_csrf_token'] ?? '')) {
        http_response_code(403);
        $errorMessage = 'Your session token is invalid. Refresh and try again.';
    } else {
        $action = learnify_post_string('action');
        try {
            if ($action === 'save_module') {
                $moduleId = learnify_admin_optional_id('module_id');
                $name = trim(learnify_post_string('name'));
                $slug = learnify_admin_slug(learnify_post_string('slug') ?: $name);
                $category = trim(learnify_post_string('category'));
                $description = trim(learnify_post_string('description'));
                $icon = trim(learnify_post_string('icon')) ?: 'fa-solid fa-book';
                $displayOrder = filter_var($_POST['display_order'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 65535]]);
                $published = isset($_POST['is_published']) ? 1 : 0;
                if ($name === '' || learnify_admin_length($name) > 150 || $slug === '' || learnify_admin_length($slug) > 170
                    || $category === '' || learnify_admin_length($category) > 80 || $description === ''
                    || learnify_admin_length($icon) > 80 || $displayOrder === false) {
                    throw new InvalidArgumentException('Enter a valid name, unique slug, category, description, icon, and display order.');
                }

                if ($moduleId !== null) {
                    $exists = $pdo->prepare('SELECT 1 FROM modules WHERE id = :module_id');
                    $exists->execute(['module_id' => $moduleId]);
                    if ($exists->fetchColumn() === false) {
                        throw new InvalidArgumentException('That module no longer exists. Refresh and try again.');
                    }
                    $statement = $pdo->prepare(
                        'UPDATE modules
                         SET name = :name, slug = :slug, category = :category, description = :description,
                             icon = :icon, display_order = :display_order, is_published = :is_published
                         WHERE id = :module_id'
                    );
                    $statement->execute([
                        'name' => $name, 'slug' => $slug, 'category' => $category, 'description' => $description,
                        'icon' => $icon, 'display_order' => $displayOrder, 'is_published' => $published,
                        'module_id' => $moduleId,
                    ]);
                    $_SESSION['admin_flash'] = 'Module updated.';
                } else {
                    $statement = $pdo->prepare(
                        'INSERT INTO modules (name, slug, category, description, icon, display_order, is_published)
                         VALUES (:name, :slug, :category, :description, :icon, :display_order, :is_published)'
                    );
                    $statement->execute([
                        'name' => $name, 'slug' => $slug, 'category' => $category, 'description' => $description,
                        'icon' => $icon, 'display_order' => $displayOrder, 'is_published' => $published,
                    ]);
                    $_SESSION['admin_flash'] = 'Module added.';
                }
                header('Location: modules.php');
                exit;
            }

            if ($action === 'save_lesson') {
                $lessonId = learnify_admin_optional_id('lesson_id');
                $moduleId = filter_var($_POST['module_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                $title = trim(learnify_post_string('lesson_title'));
                $slug = learnify_admin_slug(learnify_post_string('lesson_slug') ?: $title);
                $description = trim(learnify_post_string('lesson_description'));
                $content = trim(learnify_post_string('lesson_content'));
                $videoUrl = learnify_admin_video_embed_url(learnify_post_string('video_url'));
                $displayOrder = filter_var($_POST['lesson_order'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 65535]]);
                $minutes = filter_var($_POST['estimated_minutes'] ?? 15, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 65535]]);
                $published = isset($_POST['lesson_published']) ? 1 : 0;
                if ($moduleId === false || $moduleId === null || $title === '' || learnify_admin_length($title) > 180
                    || $slug === '' || learnify_admin_length($slug) > 200 || $displayOrder === false || $minutes === false) {
                    throw new InvalidArgumentException('Enter a valid module, lesson title, slug, order, and duration.');
                }
                $moduleCheck = $pdo->prepare('SELECT 1 FROM modules WHERE id = :id');
                $moduleCheck->execute(['id' => $moduleId]);
                if ($moduleCheck->fetchColumn() === false) {
                    throw new InvalidArgumentException('Choose an existing module.');
                }

                $storedVideoUrl = $videoUrl ?? ($lessonId === null ? null : '');
                if ($lessonId !== null) {
                    $exists = $pdo->prepare('SELECT 1 FROM chapters WHERE id = :lesson_id');
                    $exists->execute(['lesson_id' => $lessonId]);
                    if ($exists->fetchColumn() === false) {
                        throw new InvalidArgumentException('That lesson no longer exists. Refresh and try again.');
                    }
                    $statement = $pdo->prepare(
                        'UPDATE chapters
                         SET module_id = :module_id, title = :title, slug = :slug, description = :description,
                             content = :content, video_url = :video_url, display_order = :display_order, estimated_minutes = :minutes,
                             is_published = :is_published
                         WHERE id = :lesson_id'
                    );
                    $statement->execute([
                        'module_id' => $moduleId, 'title' => $title, 'slug' => $slug,
                        'description' => $description, 'content' => $content, 'video_url' => $storedVideoUrl,
                        'display_order' => $displayOrder,
                        'minutes' => $minutes, 'is_published' => $published, 'lesson_id' => $lessonId,
                    ]);
                    $_SESSION['admin_flash'] = 'Lesson updated.';
                } else {
                    $statement = $pdo->prepare(
                        'INSERT INTO chapters
                            (module_id, title, slug, description, content, video_url, display_order, estimated_minutes, is_published)
                         VALUES
                            (:module_id, :title, :slug, :description, :content, :video_url, :display_order, :minutes, :is_published)'
                    );
                    $statement->execute([
                        'module_id' => $moduleId, 'title' => $title, 'slug' => $slug,
                        'description' => $description, 'content' => $content, 'video_url' => $storedVideoUrl,
                        'display_order' => $displayOrder,
                        'minutes' => $minutes, 'is_published' => $published,
                    ]);
                    $_SESSION['admin_flash'] = 'Lesson added.';
                }
                header('Location: modules.php?module_id=' . (int) $moduleId);
                exit;
            }

            if (in_array($action, ['toggle_module', 'toggle_lesson'], true)) {
                $recordId = filter_var($_POST['record_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                if ($recordId === false || $recordId === null) {
                    throw new InvalidArgumentException('Choose a valid record.');
                }
                $table = $action === 'toggle_module' ? 'modules' : 'chapters';
                $idColumn = $action === 'toggle_module' ? 'id' : 'id';
                $statement = $pdo->prepare(
                    'UPDATE ' . $table . ' SET is_published = 1 - is_published WHERE ' . $idColumn . ' = :id'
                );
                $statement->execute(['id' => $recordId]);
                $_SESSION['admin_flash'] = ucfirst($action === 'toggle_module' ? 'module' : 'lesson') . ' publication updated.';
                $redirect = 'modules.php';
                if ($action === 'toggle_lesson') {
                    $moduleId = filter_var($_POST['module_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                    if ($moduleId !== false && $moduleId !== null) {
                        $redirect .= '?module_id=' . (int) $moduleId;
                    }
                }
                header('Location: ' . $redirect);
                exit;
            }
            throw new InvalidArgumentException('Unknown content action.');
        } catch (Throwable $exception) {
            $errorMessage = $exception instanceof InvalidArgumentException
                ? $exception->getMessage()
                : 'The content could not be saved. Check unique slugs and try again.';
            if (!$exception instanceof InvalidArgumentException) {
                error_log('Learnify admin module management failed: ' . $exception->getMessage());
            }
        }
    }
}

$modules = $pdo->query(
    'SELECT modules.id, modules.name, modules.slug, modules.category, modules.description,
            modules.icon, modules.display_order, modules.is_published, COUNT(chapters.id) AS lesson_count
     FROM modules
     LEFT JOIN chapters ON chapters.module_id = modules.id
     GROUP BY modules.id
     ORDER BY modules.display_order, modules.name'
)->fetchAll();
$requestedModule = filter_input(INPUT_GET, 'module_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$selectedModuleId = $requestedModule !== false && $requestedModule !== null
    ? (int) $requestedModule
    : (int) ($modules[0]['id'] ?? 0);
$editModuleId = filter_input(INPUT_GET, 'edit_module', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$editLessonId = filter_input(INPUT_GET, 'edit_lesson', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$editingModule = null;
foreach ($modules as $module) {
    if ($editModuleId !== false && $editModuleId !== null && (int) $module['id'] === $editModuleId) {
        $editingModule = $module;
        break;
    }
}
$editingLesson = null;
if ($editLessonId !== false && $editLessonId !== null) {
    $editLessonStatement = $pdo->prepare(
        'SELECT id, module_id, title, slug, description, content, video_url, display_order,
                estimated_minutes, is_published
         FROM chapters
         WHERE id = :lesson_id
         LIMIT 1'
    );
    $editLessonStatement->execute(['lesson_id' => $editLessonId]);
    $editingLesson = $editLessonStatement->fetch() ?: null;
    if ($editingLesson !== null) {
        $selectedModuleId = (int) $editingLesson['module_id'];
    }
}
$selectedModule = null;
foreach ($modules as $module) {
    if ((int) $module['id'] === $selectedModuleId) {
        $selectedModule = $module;
        break;
    }
}
$lessons = [];
if ($selectedModule !== null) {
    $lessonStatement = $pdo->prepare(
        'SELECT chapters.id, chapters.module_id, chapters.title, chapters.slug, chapters.description,
                chapters.content, chapters.video_url, chapters.display_order, chapters.estimated_minutes, chapters.is_published,
                COUNT(user_chapter_progress.user_id) AS completion_count
         FROM chapters
         LEFT JOIN user_chapter_progress ON user_chapter_progress.chapter_id = chapters.id
         WHERE chapters.module_id = :module_id
         GROUP BY chapters.id
         ORDER BY chapters.display_order, chapters.id'
    );
    $lessonStatement->execute(['module_id' => $selectedModuleId]);
    $lessons = $lessonStatement->fetchAll();
}
$csrfToken = learnify_csrf_token();
$pageTitle = 'Module & lesson manager';
require __DIR__ . '/../includes/header.php';
?>
<main class="admin-page container-wide">
    <header class="admin-page-heading">
        <p class="eyebrow">Content administration</p>
        <h1>Module & lesson manager</h1>
        <p class="text-muted">Create, edit, publish, and archive learning content. Archiving preserves historical progress.</p>
    </header>
    <?php if ($successMessage !== ''): ?><div class="form-alert form-alert-success" role="status"><?= learnify_h($successMessage) ?></div><?php endif; ?>
    <?php if ($errorMessage !== ''): ?><div class="form-alert form-alert-error" role="alert"><?= learnify_h($errorMessage) ?></div><?php endif; ?>

    <section class="glass-card admin-panel">
        <div class="admin-panel-heading"><div><p class="eyebrow">Modules</p><h2>Published and archived modules</h2></div></div>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>Module</th><th>Category</th><th>Lessons</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($modules as $module): ?>
                    <tr>
                        <td><strong><?= learnify_h($module['name']) ?></strong><small><?= learnify_h($module['slug']) ?></small></td>
                        <td><?= learnify_h($module['category']) ?></td>
                        <td><?= (int) $module['lesson_count'] ?></td>
                        <td><span class="badge"><?= (bool) $module['is_published'] ? 'Published' : 'Archived' ?></span></td>
                        <td>
                            <div class="admin-row-actions">
                                <a class="btn-sm action-edit" href="modules.php?edit_module=<?= (int) $module['id'] ?>#module-editor">Edit</a>
                                <a class="btn-sm action-lessons" href="modules.php?module_id=<?= (int) $module['id'] ?>">Lessons</a>
                                <form method="post">
                                    <input type="hidden" name="_csrf_token" value="<?= learnify_h($csrfToken) ?>">
                                    <input type="hidden" name="action" value="toggle_module">
                                    <input type="hidden" name="record_id" value="<?= (int) $module['id'] ?>">
                                    <button class="btn-sm <?= (bool) $module['is_published'] ? 'action-archive' : 'action-publish' ?>" type="submit"><?= (bool) $module['is_published'] ? 'Archive' : 'Publish' ?></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <div class="admin-form-grid">
        <section class="glass-card admin-panel" id="module-editor">
            <div class="admin-panel-heading"><div><p class="eyebrow"><?= $editingModule ? 'Edit module' : 'New content' ?></p><h2><?= $editingModule ? 'Update module' : 'Add module' ?></h2></div></div>
            <form method="post" class="admin-form">
                <input type="hidden" name="_csrf_token" value="<?= learnify_h($csrfToken) ?>">
                <input type="hidden" name="action" value="save_module">
                <input type="hidden" name="module_id" value="<?= (int) ($editingModule['id'] ?? 0) ?>">
                <label class="form-group"><span class="form-label">Name</span><input class="input" name="name" maxlength="150" required value="<?= learnify_h($editingModule['name'] ?? '') ?>"></label>
                <label class="form-group"><span class="form-label">Slug</span><input class="input" name="slug" maxlength="170" value="<?= learnify_h($editingModule['slug'] ?? '') ?>" placeholder="generated-from-name"></label>
                <div class="admin-inline-fields">
                    <label class="form-group"><span class="form-label">Category</span><input class="input" name="category" maxlength="80" required value="<?= learnify_h($editingModule['category'] ?? '') ?>"></label>
                    <label class="form-group"><span class="form-label">Icon class</span><input class="input" name="icon" maxlength="80" value="<?= learnify_h($editingModule['icon'] ?? 'fa-solid fa-book') ?>"></label>
                </div>
                <label class="form-group"><span class="form-label">Description</span><textarea class="textarea" name="description" required><?= learnify_h($editingModule['description'] ?? '') ?></textarea></label>
                <label class="form-group"><span class="form-label">Display order</span><input class="input" type="number" name="display_order" min="0" max="65535" required value="<?= (int) ($editingModule['display_order'] ?? 0) ?>"></label>
                <label class="admin-checkbox"><input type="checkbox" name="is_published" value="1" <?= $editingModule === null || (bool) $editingModule['is_published'] ? 'checked' : '' ?>> Published</label>
                <div class="admin-row-actions"><button class="button" type="submit"><?= $editingModule ? 'Save module' : 'Add module' ?></button><?php if ($editingModule): ?><a class="button button-secondary" href="modules.php">Cancel</a><?php endif; ?></div>
            </form>
        </section>

        <section class="glass-card admin-panel" id="lesson-editor">
            <div class="admin-panel-heading"><div><p class="eyebrow"><?= $editingLesson ? 'Edit lesson' : 'Lesson content' ?></p><h2><?= $editingLesson ? 'Update lesson' : 'Add lesson' ?></h2></div></div>
            <?php if ($modules === []): ?>
                <p class="text-muted">Add a module before creating lessons.</p>
            <?php else: ?>
            <form method="post" class="admin-form">
                <input type="hidden" name="_csrf_token" value="<?= learnify_h($csrfToken) ?>">
                <input type="hidden" name="action" value="save_lesson">
                <input type="hidden" name="lesson_id" value="<?= (int) ($editingLesson['id'] ?? 0) ?>">
                <label class="form-group"><span class="form-label">Module</span><select class="select" name="module_id" required>
                    <?php foreach ($modules as $module): ?><option value="<?= (int) $module['id'] ?>" <?= (int) ($editingLesson['module_id'] ?? $selectedModuleId) === (int) $module['id'] ? 'selected' : '' ?>><?= learnify_h($module['name']) ?></option><?php endforeach; ?>
                </select></label>
                <label class="form-group"><span class="form-label">Lesson title</span><input class="input" name="lesson_title" maxlength="180" required value="<?= learnify_h($editingLesson['title'] ?? '') ?>"></label>
                <label class="form-group"><span class="form-label">Slug</span><input class="input" name="lesson_slug" maxlength="200" value="<?= learnify_h($editingLesson['slug'] ?? '') ?>" placeholder="generated-from-title"></label>
                <label class="form-group"><span class="form-label">Short description</span><textarea class="textarea" name="lesson_description"><?= learnify_h($editingLesson['description'] ?? '') ?></textarea></label>
                <label class="form-group"><span class="form-label">Content (plain text or reader JSON)</span><textarea class="textarea admin-code-input" name="lesson_content" rows="9"><?= learnify_h($editingLesson['content'] ?? '') ?></textarea></label>
                <label class="form-group"><span class="form-label">Video Embed URL</span><input class="input" type="url" name="video_url" maxlength="255" value="<?= learnify_h($editingLesson['video_url'] ?? '') ?>" placeholder="https://www.youtube.com/embed/VIDEO_ID" pattern="https://(www\.)?(youtube\.com/embed/|youtube-nocookie\.com/embed/)[A-Za-z0-9_-]{11}"></label>
                <div class="admin-inline-fields">
                    <label class="form-group"><span class="form-label">Display order</span><input class="input" type="number" name="lesson_order" min="0" max="65535" required value="<?= (int) ($editingLesson['display_order'] ?? (count($lessons) + 1)) ?>"></label>
                    <label class="form-group"><span class="form-label">Minutes</span><input class="input" type="number" name="estimated_minutes" min="1" max="65535" required value="<?= (int) ($editingLesson['estimated_minutes'] ?? 15) ?>"></label>
                </div>
                <label class="admin-checkbox"><input type="checkbox" name="lesson_published" value="1" <?= $editingLesson === null || (bool) $editingLesson['is_published'] ? 'checked' : '' ?>> Published</label>
                <div class="admin-row-actions"><button class="button" type="submit"><?= $editingLesson ? 'Save lesson' : 'Add lesson' ?></button><?php if ($editingLesson): ?><a class="button button-secondary" href="modules.php?module_id=<?= $selectedModuleId ?>">Cancel</a><?php endif; ?></div>
            </form>
            <?php endif; ?>
        </section>
    </div>

    <?php if ($selectedModule !== null): ?>
    <section class="glass-card admin-panel">
        <div class="admin-panel-heading"><div><p class="eyebrow">Lesson outline</p><h2><?= learnify_h($selectedModule['name']) ?></h2></div></div>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>Lesson</th><th>Order</th><th>Completions</th><th>Status</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($lessons as $lesson): ?>
                    <tr>
                        <td><strong><?= learnify_h($lesson['title']) ?></strong><small><?= learnify_h($lesson['slug']) ?></small></td>
                        <td><?= (int) $lesson['display_order'] ?></td>
                        <td><?= (int) $lesson['completion_count'] ?></td>
                        <td><span class="badge"><?= (bool) $lesson['is_published'] ? 'Published' : 'Archived' ?></span></td>
                        <td>
                            <div class="admin-row-actions">
                                <a class="btn-sm action-edit" href="modules.php?module_id=<?= $selectedModuleId ?>&amp;edit_lesson=<?= (int) $lesson['id'] ?>#lesson-editor">Edit</a>
                                <form method="post">
                                    <input type="hidden" name="_csrf_token" value="<?= learnify_h($csrfToken) ?>">
                                    <input type="hidden" name="action" value="toggle_lesson">
                                    <input type="hidden" name="record_id" value="<?= (int) $lesson['id'] ?>">
                                    <input type="hidden" name="module_id" value="<?= $selectedModuleId ?>">
                                    <button class="btn-sm <?= (bool) $lesson['is_published'] ? 'action-archive' : 'action-publish' ?>" type="submit"><?= (bool) $lesson['is_published'] ? 'Archive' : 'Publish' ?></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
    <?php endif; ?>
</main>
<?php require __DIR__ . '/../includes/footer.php'; ?>
