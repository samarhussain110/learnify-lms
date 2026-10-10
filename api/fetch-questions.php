<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/exam.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Allow: POST');
    learnify_exam_json_response(405, ['success' => false, 'message' => 'Method not allowed.']);
}
if (!isset($_SESSION['user_id'])) {
    learnify_exam_json_response(401, ['success' => false, 'message' => 'Please log in to start an exam.']);
}

$rawBody = file_get_contents('php://input');
if ($rawBody === false || strlen($rawBody) > 8192) {
    learnify_exam_json_response(413, ['success' => false, 'message' => 'The request is too large.']);
}
try {
    $request = json_decode($rawBody, true, 16, JSON_THROW_ON_ERROR);
} catch (JsonException) {
    learnify_exam_json_response(400, ['success' => false, 'message' => 'Invalid JSON request.']);
}
if (!is_array($request)) {
    learnify_exam_json_response(400, ['success' => false, 'message' => 'Invalid request payload.']);
}
if (!learnify_csrf_is_valid($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) {
    learnify_exam_json_response(403, ['success' => false, 'message' => 'Your session token is invalid. Refresh and try again.']);
}

$mode = $request['mode'] ?? null;
if (!is_string($mode) || !in_array($mode, ['practice', 'timed', 'certification'], true)) {
    learnify_exam_json_response(422, ['success' => false, 'message' => 'Choose a valid exam mode.']);
}
$quizId = filter_var($request['quiz_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$moduleId = filter_var($request['module_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (($quizId === false || $quizId === null) && ($moduleId === false || $moduleId === null)) {
    learnify_exam_json_response(422, ['success' => false, 'message' => 'Choose a quiz or module.']);
}

try {
    if ($quizId !== false && $quizId !== null) {
        $quizStatement = $pdo->prepare(
            'SELECT quizzes.id, quizzes.title, quizzes.time_limit_minutes, quizzes.passing_score, quizzes.module_id,
                    modules.name AS module_name
             FROM quizzes
             INNER JOIN modules ON modules.id = quizzes.module_id
             WHERE quizzes.id = :quiz_id
               AND quizzes.is_published = 1
               AND modules.is_published = 1
             LIMIT 1'
        );
        $quizStatement->execute(['quiz_id' => $quizId]);
    } else {
        $quizStatement = $pdo->prepare(
            'SELECT quizzes.id, quizzes.title, quizzes.time_limit_minutes, quizzes.passing_score, quizzes.module_id,
                    modules.name AS module_name
             FROM quizzes
             INNER JOIN modules ON modules.id = quizzes.module_id
             WHERE quizzes.module_id = :module_id
               AND quizzes.is_published = 1
               AND modules.is_published = 1
             ORDER BY quizzes.id'
        );
        $quizStatement->execute(['module_id' => $moduleId]);
    }
    $availableQuizzes = $quizStatement->fetchAll();
    if ($availableQuizzes === []) {
        learnify_exam_json_response(404, ['success' => false, 'message' => 'No published quizzes are available for that selection.']);
    }

    $quiz = $availableQuizzes[array_rand($availableQuizzes)];
    $settings = learnify_exam_mode_settings($mode, (int) ($quiz['time_limit_minutes'] ?? 0));
    $questionStatement = $pdo->prepare(
        'SELECT questions.id, questions.question_text, questions.question_type,
                questions.options, questions.points
         FROM questions
         INNER JOIN quizzes AS question_quiz ON question_quiz.id = questions.quiz_id
         WHERE question_quiz.module_id = :module_id
           AND question_quiz.is_published = 1
           AND questions.question_type IN (\'multiple_choice\', \'true_false\', \'short_answer\', \'mcq\', \'tf\', \'fill\')
         ORDER BY RAND()
         LIMIT :question_limit'
    );
    $questionStatement->bindValue(':module_id', (int) $quiz['module_id'], PDO::PARAM_INT);
    $questionStatement->bindValue(':question_limit', $settings['limit'], PDO::PARAM_INT);
    $questionStatement->execute();
    $questionRows = $questionStatement->fetchAll();

    if ($questionRows === []) {
        learnify_exam_json_response(404, [
            'success' => false,
            'message' => 'No published questions are available for this module yet.',
        ]);
    }

    $questions = [];
    $questionIds = [];
    foreach ($questionRows as $row) {
        $options = learnify_exam_options($row['options'], (string) $row['question_type']);
        $type = learnify_exam_normalize_question_type((string) $row['question_type']);
        if ($type !== 'fill' && $options === []) {
            continue;
        }
        $questionIds[] = (int) $row['id'];
        $questions[] = [
            'id' => (int) $row['id'],
            'text' => (string) $row['question_text'],
            'type' => $type,
            'options' => $options,
            'points' => (float) $row['points'],
        ];
    }

    if ($questions === []) {
        learnify_exam_json_response(422, [
            'success' => false,
            'message' => 'This quiz has no usable questions yet.',
        ]);
    }

    if (!isset($_SESSION['exam_attempts']) || !is_array($_SESSION['exam_attempts'])) {
        $_SESSION['exam_attempts'] = [];
    }
    foreach ($_SESSION['exam_attempts'] as $attemptKey => $attempt) {
        if (!is_array($attempt) || (int) ($attempt['created_at'] ?? 0) < time() - 86400) {
            unset($_SESSION['exam_attempts'][$attemptKey]);
        }
    }

    $attemptId = bin2hex(random_bytes(32));
    $startedAt = time();
    $_SESSION['exam_attempts'][$attemptId] = [
        'user_id' => (int) $_SESSION['user_id'],
        'quiz_id' => (int) $quiz['id'],
        'module_id' => (int) $quiz['module_id'],
        'passing_score' => (float) $quiz['passing_score'],
        'mode' => $mode,
        'question_ids' => $questionIds,
        'started_at' => $startedAt,
        'expires_at' => $settings['duration'] === null ? null : $startedAt + $settings['duration'],
        'created_at' => $startedAt,
        'result' => null,
    ];

    learnify_exam_json_response(200, [
        'success' => true,
        'attempt_id' => $attemptId,
        'quiz_id' => (int) $quiz['id'],
        'quiz_title' => (string) $quiz['title'],
        'module_name' => (string) $quiz['module_name'],
        'mode' => $mode,
        'mode_label' => $settings['label'],
        'duration_seconds' => $settings['duration'],
        'server_time' => time(),
        'expires_at' => $settings['duration'] === null ? null : $startedAt + $settings['duration'],
        'questions' => $questions,
    ]);
} catch (Throwable $exception) {
    error_log('Learnify question fetch failed: ' . $exception->getMessage());
    learnify_exam_json_response(500, ['success' => false, 'message' => 'Questions could not be loaded. Please try again.']);
}
