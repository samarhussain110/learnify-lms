<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/admin-auth.php';
require_once __DIR__ . '/../includes/exam.php';

function learnify_admin_question_excerpt(string $question): string
{
    if (function_exists('mb_strimwidth')) {
        return mb_strimwidth($question, 0, 180, '…', 'UTF-8');
    }

    return strlen($question) > 180 ? substr($question, 0, 177) . '...' : $question;
}

function learnify_admin_question_optional_id(): ?int
{
    $value = $_POST['question_id'] ?? '0';
    if ($value === '0' || $value === 0 || $value === '') {
        return null;
    }

    $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id === false || $id === null) {
        throw new InvalidArgumentException('The selected question is invalid. Please reopen the edit form.');
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
            $questionId = learnify_admin_question_optional_id();
            if ($action === 'create_pool') {
                $moduleId = filter_var($_POST['module_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                if ($moduleId === false || $moduleId === null) {
                    throw new InvalidArgumentException('Choose a valid module.');
                }
                $moduleStatement = $pdo->prepare(
                    'SELECT name
                     FROM modules
                     WHERE id = :module_id AND is_published = 1
                     LIMIT 1'
                );
                $moduleStatement->execute(['module_id' => $moduleId]);
                $moduleName = $moduleStatement->fetchColumn();
                if ($moduleName === false) {
                    throw new InvalidArgumentException('Choose a published module.');
                }
                $existingPool = $pdo->prepare(
                    'SELECT id FROM quizzes
                     WHERE module_id = :module_id AND title = :title
                     LIMIT 1'
                );
                $existingPool->execute(['module_id' => $moduleId, 'title' => 'Learnify Question Pool']);
                if ($existingPool->fetchColumn() === false) {
                    $createPool = $pdo->prepare(
                        'INSERT INTO quizzes
                            (module_id, title, description, passing_score, time_limit_minutes, is_published)
                         VALUES
                            (:module_id, :title, :description, 70.00, 60, 1)'
                    );
                    $createPool->execute([
                        'module_id' => $moduleId,
                        'title' => 'Learnify Question Pool',
                        'description' => 'Admin-managed question pool for ' . (string) $moduleName . '.',
                    ]);
                    $_SESSION['admin_flash'] = 'Question pool created.';
                } else {
                    $_SESSION['admin_flash'] = 'This module already has a Learnify question pool.';
                }
                header('Location: quizzes.php?module_id=' . (int) $moduleId);
                exit;
            }
            if ($action === 'delete_question') {
                if ($questionId === null) {
                    throw new InvalidArgumentException('Choose a valid question.');
                }
                $delete = $pdo->prepare('DELETE FROM questions WHERE id = :question_id');
                $delete->execute(['question_id' => $questionId]);
                if ($delete->rowCount() !== 1) {
                    throw new InvalidArgumentException('That question no longer exists.');
                }
                $_SESSION['admin_flash'] = 'Question deleted.';
                $moduleId = filter_var($_POST['module_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                header('Location: quizzes.php' . ($moduleId !== false && $moduleId !== null ? '?module_id=' . (int) $moduleId : ''));
                exit;
            }
            if ($action !== 'save_question') {
                throw new InvalidArgumentException('Unknown question action.');
            }

            $quizId = filter_var($_POST['quiz_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $questionType = learnify_post_string('question_type');
            $questionText = trim(learnify_post_string('question_text'));
            $answer = trim(learnify_post_string('correct_answer'));
            $explanation = trim(learnify_post_string('explanation'));
            $points = filter_var($_POST['points'] ?? '1', FILTER_VALIDATE_FLOAT);
            $displayOrder = filter_var($_POST['display_order'] ?? 0, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 65535]]);
            $validPoints = is_float($points) || is_int($points);
            if ($quizId === false || $quizId === null || $questionText === ''
                || (function_exists('mb_strlen') ? mb_strlen($questionText, 'UTF-8') : strlen($questionText)) > 10000
                || !in_array($questionType, ['mcq', 'tf', 'fill'], true) || $answer === ''
                || !$validPoints || (float) $points <= 0 || $displayOrder === false) {
                throw new InvalidArgumentException('Provide a valid quiz, question type, prompt, answer, points, and display order.');
            }
            $options = null;
            if ($questionType === 'mcq') {
                $options = array_values(array_filter(
                    array_map('trim', preg_split('/\R/u', learnify_post_string('options')) ?: []),
                    static fn (string $option): bool => $option !== ''
                ));
                if (count($options) < 2 || count($options) > 26
                    || !preg_match('/^[A-' . chr(64 + count($options)) . ']$/i', $answer)) {
                    throw new InvalidArgumentException('MCQs need 2–26 options and a correct answer letter matching an option.');
                }
                $answer = strtoupper($answer);
            } elseif ($questionType === 'tf') {
                $options = ['True', 'False'];
                if (!in_array(strtolower($answer), ['true', 'false'], true)) {
                    throw new InvalidArgumentException('True/False answers must be True or False.');
                }
                $answer = ucfirst(strtolower($answer));
            }
            $quizCheck = $pdo->prepare(
                'SELECT quizzes.id
                 FROM quizzes
                 INNER JOIN modules ON modules.id = quizzes.module_id
                 WHERE quizzes.id = :quiz_id
                 LIMIT 1'
            );
            $quizCheck->execute(['quiz_id' => $quizId]);
            if ($quizCheck->fetchColumn() === false) {
                throw new InvalidArgumentException('Choose a quiz in a published module.');
            }
            $encodedOptions = $options === null ? null : json_encode($options, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
            if ($questionId !== null) {
                $questionExists = $pdo->prepare('SELECT 1 FROM questions WHERE id = :question_id');
                $questionExists->execute(['question_id' => $questionId]);
                if ($questionExists->fetchColumn() === false) {
                    throw new InvalidArgumentException('That question no longer exists. Refresh and try again.');
                }
                $statement = $pdo->prepare(
                    'UPDATE questions
                     SET quiz_id = :quiz_id, question_text = :question_text, question_type = :question_type,
                         options = :options, correct_answer = :correct_answer, explanation = :explanation,
                         points = :points, display_order = :display_order
                     WHERE id = :question_id'
                );
                $statement->execute([
                    'quiz_id' => $quizId, 'question_text' => $questionText, 'question_type' => $questionType,
                    'options' => $encodedOptions, 'correct_answer' => $answer, 'explanation' => $explanation,
                    'points' => $points, 'display_order' => $displayOrder, 'question_id' => $questionId,
                ]);
                $_SESSION['admin_flash'] = 'Question updated.';
            } else {
                $statement = $pdo->prepare(
                    'INSERT INTO questions
                        (quiz_id, question_text, question_type, options, correct_answer, explanation, points, display_order)
                     VALUES
                        (:quiz_id, :question_text, :question_type, :options, :correct_answer, :explanation, :points, :display_order)'
                );
                $statement->execute([
                    'quiz_id' => $quizId, 'question_text' => $questionText, 'question_type' => $questionType,
                    'options' => $encodedOptions, 'correct_answer' => $answer, 'explanation' => $explanation,
                    'points' => $points, 'display_order' => $displayOrder,
                ]);
                $_SESSION['admin_flash'] = 'Question added.';
            }
            $moduleId = filter_var($_POST['module_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            header('Location: quizzes.php' . ($moduleId !== false && $moduleId !== null ? '?module_id=' . (int) $moduleId : ''));
            exit;
        } catch (Throwable $exception) {
            $errorMessage = $exception instanceof InvalidArgumentException
                ? $exception->getMessage()
                : 'The question could not be saved. Check the submitted values and try again.';
            if (!$exception instanceof InvalidArgumentException) {
                error_log('Learnify admin question management failed: ' . $exception->getMessage());
            }
        }
    }
}

$modules = $pdo->query('SELECT id, name, is_published FROM modules ORDER BY display_order, name')->fetchAll();
$allQuizzes = $pdo->query(
    'SELECT quizzes.id, quizzes.module_id, quizzes.title, modules.name AS module_name
     FROM quizzes
     INNER JOIN modules ON modules.id = quizzes.module_id
     ORDER BY modules.display_order, quizzes.title'
)->fetchAll();
$filterModuleId = filter_input(INPUT_GET, 'module_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$filterModuleId = $filterModuleId !== false && $filterModuleId !== null ? (int) $filterModuleId : 0;
$quizSql = 'SELECT quizzes.id, quizzes.module_id, quizzes.title, modules.name AS module_name
            FROM quizzes INNER JOIN modules ON modules.id = quizzes.module_id';
if ($filterModuleId > 0) {
    $quizStatement = $pdo->prepare($quizSql . ' WHERE modules.id = :module_id ORDER BY quizzes.title');
    $quizStatement->execute(['module_id' => $filterModuleId]);
    $quizzes = $quizStatement->fetchAll();
} else {
    $quizzes = $pdo->query($quizSql . ' ORDER BY modules.display_order, quizzes.title')->fetchAll();
}
$editQuestionId = filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$editingQuestion = null;
$questions = [];
if ($editQuestionId !== false && $editQuestionId !== null) {
    $editStatement = $pdo->prepare(
        'SELECT questions.*, quizzes.module_id
         FROM questions INNER JOIN quizzes ON quizzes.id = questions.quiz_id
         WHERE questions.id = :question_id LIMIT 1'
    );
    $editStatement->execute(['question_id' => $editQuestionId]);
    $editingQuestion = $editStatement->fetch() ?: null;
}
$questionSql = 'SELECT questions.id, questions.quiz_id, questions.question_text, questions.question_type,
                       questions.options, questions.correct_answer, questions.display_order,
                       quizzes.title AS quiz_title, modules.id AS module_id, modules.name AS module_name
                FROM questions
                INNER JOIN quizzes ON quizzes.id = questions.quiz_id
                INNER JOIN modules ON modules.id = quizzes.module_id';
if ($filterModuleId > 0) {
    $questionStatement = $pdo->prepare($questionSql . ' WHERE modules.id = :module_id ORDER BY modules.name, quizzes.title, questions.display_order, questions.id LIMIT 250');
    $questionStatement->execute(['module_id' => $filterModuleId]);
    $questions = $questionStatement->fetchAll();
} else {
    $questions = $pdo->query($questionSql . ' ORDER BY questions.id DESC LIMIT 150')->fetchAll();
}
$editingOptions = [];
if (is_array($editingQuestion) && is_string($editingQuestion['options'])) {
    $decodedOptions = json_decode($editingQuestion['options'], true);
    if (is_array($decodedOptions)) {
        $editingOptions = array_is_list($decodedOptions)
            ? $decodedOptions
            : array_values(array_map(static fn ($option) => is_array($option) ? ($option['label'] ?? $option['text'] ?? '') : $option, $decodedOptions));
    }
}
$csrfToken = learnify_csrf_token();
$pageTitle = 'Question bank manager';
require __DIR__ . '/../includes/header.php';
?>
<main class="admin-page container-wide">
    <header class="admin-page-heading">
        <p class="eyebrow">Assessment administration</p>
        <h1>Question bank</h1>
        <p class="text-muted">Filter by module, manage all supported question formats, and keep answer keys aligned with the options students see.</p>
    </header>
    <?php if ($successMessage !== ''): ?><div class="form-alert form-alert-success" role="status"><?= learnify_h($successMessage) ?></div><?php endif; ?>
    <?php if ($errorMessage !== ''): ?><div class="form-alert form-alert-error" role="alert"><?= learnify_h($errorMessage) ?></div><?php endif; ?>

    <section class="glass-card admin-panel">
        <form method="get" class="admin-filter-form">
            <label class="form-group"><span class="form-label">Filter question bank by module</span>
                <select class="select" name="module_id">
                    <option value="0">All modules (latest 150 questions)</option>
                    <?php foreach ($modules as $module): ?><?php if ((bool) $module['is_published']): ?><option value="<?= (int) $module['id'] ?>" <?= $filterModuleId === (int) $module['id'] ? 'selected' : '' ?>><?= learnify_h($module['name']) ?></option><?php endif; ?><?php endforeach; ?>
                </select>
            </label>
            <button class="button" type="submit">Apply filter</button>
        </form>
    </section>

    <section class="glass-card admin-panel">
        <div class="admin-panel-heading"><div><p class="eyebrow">Pool setup</p><h2>Create a question pool</h2></div></div>
        <form method="post" class="admin-filter-form">
            <input type="hidden" name="_csrf_token" value="<?= learnify_h($csrfToken) ?>">
            <input type="hidden" name="action" value="create_pool">
            <label class="form-group"><span class="form-label">Published module</span>
                <select class="select" name="module_id" required>
                    <?php foreach ($modules as $module): ?><option value="<?= (int) $module['id'] ?>" <?= $filterModuleId === (int) $module['id'] ? 'selected' : '' ?>><?= learnify_h($module['name']) ?></option><?php endforeach; ?>
                </select>
            </label>
            <button class="button" type="submit">Create Learnify pool</button>
        </form>
    </section>

    <section class="glass-card admin-panel" id="question-editor">
        <div class="admin-panel-heading"><div><p class="eyebrow"><?= $editingQuestion ? 'Update item' : 'Question authoring' ?></p><h2><?= $editingQuestion ? 'Edit question' : 'Add question' ?></h2></div></div>
        <form method="post" class="admin-form">
            <input type="hidden" name="_csrf_token" value="<?= learnify_h($csrfToken) ?>">
            <input type="hidden" name="action" value="save_question">
            <input type="hidden" name="question_id" value="<?= (int) ($editingQuestion['id'] ?? 0) ?>">
            <input type="hidden" name="module_id" value="<?= $filterModuleId ?>">
            <div class="admin-inline-fields">
                <label class="form-group"><span class="form-label">Quiz pool</span><select class="select" name="quiz_id" required>
                    <?php foreach ($allQuizzes as $quiz): ?><option value="<?= (int) $quiz['id'] ?>" <?= (int) ($editingQuestion['quiz_id'] ?? 0) === (int) $quiz['id'] ? 'selected' : '' ?>><?= learnify_h($quiz['module_name'] . ' — ' . $quiz['title']) ?></option><?php endforeach; ?>
                </select></label>
                <label class="form-group"><span class="form-label">Format</span><select class="select" name="question_type" data-question-type required>
                    <?php foreach (['mcq' => 'Multiple choice', 'tf' => 'True / False', 'fill' => 'Fill in / code'] as $value => $label): ?><option value="<?= $value ?>" <?= learnify_exam_normalize_question_type((string) ($editingQuestion['question_type'] ?? 'mcq')) === $value ? 'selected' : '' ?>><?= $label ?></option><?php endforeach; ?>
                </select></label>
            </div>
            <label class="form-group"><span class="form-label">Question / scenario</span><textarea class="textarea" name="question_text" rows="4" required><?= learnify_h($editingQuestion['question_text'] ?? '') ?></textarea></label>
            <label class="form-group" data-options-field><span class="form-label">Options (one per line; answer is A, B, C...)</span><textarea class="textarea" name="options" rows="4"><?= learnify_h(implode("\n", $editingOptions)) ?></textarea></label>
            <div class="admin-inline-fields">
                <label class="form-group"><span class="form-label">Correct answer</span><input class="input" name="correct_answer" required value="<?= learnify_h($editingQuestion['correct_answer'] ?? '') ?>" placeholder="A, True, or accepted text"></label>
                <label class="form-group"><span class="form-label">Points</span><input class="input" type="number" min="0.01" step="0.01" name="points" required value="<?= learnify_h($editingQuestion['points'] ?? '1.00') ?>"></label>
                <label class="form-group"><span class="form-label">Display order</span><input class="input" type="number" min="0" max="65535" name="display_order" required value="<?= (int) ($editingQuestion['display_order'] ?? 0) ?>"></label>
            </div>
            <label class="form-group"><span class="form-label">Explanation (optional)</span><textarea class="textarea" name="explanation"><?= learnify_h($editingQuestion['explanation'] ?? '') ?></textarea></label>
            <div class="admin-row-actions"><button class="button" type="submit"><?= $editingQuestion ? 'Save question' : 'Add question' ?></button><?php if ($editingQuestion): ?><a class="button button-secondary" href="quizzes.php<?= $filterModuleId > 0 ? '?module_id=' . $filterModuleId : '' ?>">Cancel</a><?php endif; ?></div>
        </form>
    </section>

    <section class="glass-card admin-panel">
        <div class="admin-panel-heading"><div><p class="eyebrow">Question inventory</p><h2><?= number_format(count($questions)) ?> questions shown</h2></div></div>
        <div class="admin-table-wrap">
            <table class="admin-table question-bank-table">
                <colgroup>
                    <col class="question-column">
                    <col class="module-column">
                    <col class="type-column">
                    <col class="answer-column">
                    <col class="actions-column">
                </colgroup>
                <thead><tr><th>Question</th><th>Module / quiz</th><th>Type</th><th>Answer</th><th>Actions</th></tr></thead>
                <tbody>
                <?php foreach ($questions as $question): ?>
                    <tr>
                        <td><strong><?= learnify_h(learnify_admin_question_excerpt((string) $question['question_text'])) ?></strong><small>Order <?= (int) $question['display_order'] ?></small></td>
                        <td><strong><?= learnify_h($question['module_name']) ?></strong><small><?= learnify_h($question['quiz_title']) ?></small></td>
                        <?php
                        $questionTypeLabel = match (learnify_exam_normalize_question_type((string) $question['question_type'])) {
                            'mcq' => 'MCQ',
                            'tf' => 'TRUE_FALSE',
                            default => 'FILL_IN',
                        };
                        ?>
                        <td><span class="question-type-badge question-type-<?= learnify_h(learnify_exam_normalize_question_type((string) $question['question_type'])) ?>"><?= learnify_h($questionTypeLabel) ?></span></td>
                        <td><?= learnify_h($question['correct_answer']) ?></td>
                        <td>
                            <div class="actions-cell">
                                <a class="btn-sm action-edit" href="quizzes.php?<?= $filterModuleId > 0 ? 'module_id=' . $filterModuleId . '&amp;' : '' ?>edit=<?= (int) $question['id'] ?>#question-editor">Edit</a>
                                <form method="post" onsubmit="return confirm('Delete this question? Existing attempt history will also be removed.');">
                                    <input type="hidden" name="_csrf_token" value="<?= learnify_h($csrfToken) ?>">
                                    <input type="hidden" name="action" value="delete_question">
                                    <input type="hidden" name="question_id" value="<?= (int) $question['id'] ?>">
                                    <input type="hidden" name="module_id" value="<?= $filterModuleId ?>">
                                    <button class="btn-sm action-archive" type="submit">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if ($questions === []): ?><tr><td colspan="5">No questions match this filter.</td></tr><?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>
</main>
<script>
document.querySelector('[data-question-type]').addEventListener('change', (event) => {
    const options = document.querySelector('[data-options-field]');
    options.hidden = event.target.value !== 'mcq';
});
document.querySelector('[data-question-type]').dispatchEvent(new Event('change'));
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
