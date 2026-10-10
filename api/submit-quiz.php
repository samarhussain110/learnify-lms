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
    learnify_exam_json_response(401, ['success' => false, 'message' => 'Please log in to submit an exam.']);
}

$rawBody = file_get_contents('php://input');
if ($rawBody === false || strlen($rawBody) > 150000) {
    learnify_exam_json_response(413, ['success' => false, 'message' => 'The submission is too large.']);
}
try {
    $request = json_decode($rawBody, true, 32, JSON_THROW_ON_ERROR);
} catch (JsonException) {
    learnify_exam_json_response(400, ['success' => false, 'message' => 'Invalid JSON request.']);
}
if (!is_array($request)) {
    learnify_exam_json_response(400, ['success' => false, 'message' => 'Invalid submission payload.']);
}
if (!learnify_csrf_is_valid($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) {
    learnify_exam_json_response(403, ['success' => false, 'message' => 'Your session token is invalid. Refresh and try again.']);
}

$attemptId = $request['attempt_id'] ?? null;
$answers = $request['answers'] ?? [];
if (
    !is_string($attemptId)
    || preg_match('/^[a-f0-9]{64}$/', $attemptId) !== 1
    || !is_array($answers)
    || count($answers) > 50
) {
    learnify_exam_json_response(422, ['success' => false, 'message' => 'Invalid exam submission.']);
}

$attempt = $_SESSION['exam_attempts'][$attemptId] ?? null;
if (
    !is_array($attempt)
    || (int) ($attempt['user_id'] ?? 0) !== (int) $_SESSION['user_id']
    || !is_array($attempt['question_ids'] ?? null)
) {
    learnify_exam_json_response(409, ['success' => false, 'message' => 'This exam attempt has expired or is invalid.']);
}
$expiresAt = $attempt['expires_at'] ?? null;
if (is_int($expiresAt) && time() >= $expiresAt) {
    $answers = [];
}
if (is_array($attempt['result'] ?? null)) {
    learnify_exam_json_response(200, $attempt['result']);
}

foreach ($answers as $questionId => $answer) {
    if (!is_string((string) $questionId) || !is_string($answer) || strlen($answer) > 2000) {
        learnify_exam_json_response(422, ['success' => false, 'message' => 'One or more submitted answers are invalid.']);
    }
    if (!in_array((int) $questionId, $attempt['question_ids'], true)) {
        learnify_exam_json_response(422, ['success' => false, 'message' => 'The submission contains a question outside this exam.']);
    }
}

$userId = (int) $_SESSION['user_id'];
$questionIds = array_values(array_unique(array_map('intval', $attempt['question_ids'])));
if ($questionIds === [] || count($questionIds) > 50) {
    learnify_exam_json_response(409, ['success' => false, 'message' => 'This exam attempt has no valid questions.']);
}

try {
    $placeholders = implode(',', array_fill(0, count($questionIds), '?'));
    $questionStatement = $pdo->prepare(
        "SELECT questions.id, questions.question_text, questions.question_type,
                questions.options, questions.correct_answer, questions.explanation, questions.points
         FROM questions
         INNER JOIN quizzes ON quizzes.id = questions.quiz_id
         WHERE quizzes.module_id = ? AND quizzes.is_published = 1
           AND questions.id IN ({$placeholders})"
    );
    $questionStatement->execute(array_merge([(int) $attempt['module_id']], $questionIds));
    $questionRows = $questionStatement->fetchAll();
    if (count($questionRows) !== count($questionIds)) {
        learnify_exam_json_response(409, ['success' => false, 'message' => 'One or more exam questions are no longer available.']);
    }

    $questionsById = [];
    foreach ($questionRows as $row) {
        $questionsById[(int) $row['id']] = $row;
    }

    $totalPoints = 0.0;
    $earnedPoints = 0.0;
    $correctCount = 0;
    $review = [];
    $historyRows = [];
    foreach ($questionIds as $questionIndex => $questionId) {
        $row = $questionsById[$questionId];
        $type = learnify_exam_normalize_question_type((string) $row['question_type']);
        $options = learnify_exam_options($row['options'], $type);
        $submittedValue = $answers[(string) $questionId] ?? $answers[$questionId] ?? null;
        if ($submittedValue !== null && !is_string($submittedValue)) {
            learnify_exam_json_response(422, ['success' => false, 'message' => 'One or more submitted answers are invalid.']);
        }

        $normalizedAnswer = null;
        $isCorrect = false;
        $submittedLabel = null;
        if (is_string($submittedValue) && $type === 'fill') {
            $submittedLabel = trim($submittedValue);
            $normalizedAnswer = preg_replace('/\s+/u', ' ', $submittedLabel);
            $normalizedAnswer = is_string($normalizedAnswer) ? $normalizedAnswer : $submittedLabel;
            $isCorrect = learnify_exam_fill_answer_matches(
                $submittedLabel,
                (string) $row['correct_answer']
            );
        } elseif (is_string($submittedValue)) {
            $normalizedAnswer = learnify_exam_normalize_answer($submittedValue, $options);
            if ($normalizedAnswer === null) {
                learnify_exam_json_response(422, ['success' => false, 'message' => 'An answer does not match the available choices.']);
            }
            $correctOption = learnify_exam_correct_option((string) $row['correct_answer'], $options);
            $isCorrect = $correctOption !== null
                && hash_equals((string) $correctOption['value'], $normalizedAnswer);
            foreach ($options as $option) {
                if (hash_equals((string) $option['value'], $normalizedAnswer)) {
                    $submittedLabel = (string) $option['label'];
                    break;
                }
            }
        }
        if ($type !== 'fill' && is_string($submittedValue) && $normalizedAnswer === null) {
            learnify_exam_json_response(422, ['success' => false, 'message' => 'An answer does not match the available choices.']);
        }

        $points = (float) $row['points'];
        $awarded = $isCorrect ? $points : 0.0;
        $totalPoints += $points;
        $earnedPoints += $awarded;
        if ($isCorrect) {
            $correctCount++;
        }

        $correctOption = $type === 'fill'
            ? null
            : learnify_exam_correct_option((string) $row['correct_answer'], $options);
        $correctLabel = $type === 'fill'
            ? trim(explode('|', (string) $row['correct_answer'])[0])
            : ($correctOption['label'] ?? (string) $row['correct_answer']);
        $review[] = [
            'question_number' => $questionIndex + 1,
            'question' => (string) $row['question_text'],
            'is_correct' => $isCorrect,
            'submitted_answer' => $submittedLabel,
            'correct_answer' => (string) $correctLabel,
            'explanation' => (string) ($row['explanation'] ?? ''),
            'points_awarded' => $awarded,
            'points_possible' => $points,
        ];
        $historyRows[] = [
            'question_id' => $questionId,
            'submitted_answer' => $submittedLabel,
            'is_correct' => $isCorrect ? 1 : 0,
            'points_awarded' => $awarded,
        ];
    }

    $percentage = $totalPoints > 0.0 ? round(($earnedPoints / $totalPoints) * 100, 2) : 0.0;
    $pdo->beginTransaction();
    $quizAttemptStatement = $pdo->prepare(
        'INSERT INTO quiz_attempts
            (attempt_id, user_id, quiz_id, exam_mode, score_percentage, correct_count, question_count)
         VALUES
            (:attempt_id, :user_id, :quiz_id, :exam_mode, :score_percentage, :correct_count, :question_count)'
    );
    $quizAttemptStatement->execute([
        'attempt_id' => $attemptId,
        'user_id' => $userId,
        'quiz_id' => (int) $attempt['quiz_id'],
        'exam_mode' => (string) $attempt['mode'],
        'score_percentage' => $percentage,
        'correct_count' => $correctCount,
        'question_count' => count($questionIds),
    ]);

    $historyStatement = $pdo->prepare(
        'INSERT INTO user_question_history
            (user_id, attempt_id, question_id, submitted_answer, is_correct, points_awarded)
         VALUES
            (:user_id, :attempt_id, :question_id, :submitted_answer, :is_correct, :points_awarded)'
    );
    foreach ($historyRows as $history) {
        $historyStatement->execute([
            'user_id' => $userId,
            'attempt_id' => $attemptId,
            'question_id' => $history['question_id'],
            'submitted_answer' => $history['submitted_answer'],
            'is_correct' => $history['is_correct'],
            'points_awarded' => $history['points_awarded'],
        ]);
    }

    $streakUpdate = $pdo->prepare(
        'UPDATE users
         SET study_streak = CASE
                WHEN last_study_date = CURRENT_DATE THEN study_streak
                WHEN last_study_date = CURRENT_DATE - INTERVAL 1 DAY THEN study_streak + 1
                ELSE 1
             END,
             last_study_date = CURRENT_DATE
         WHERE id = :user_id'
    );
    $streakUpdate->execute(['user_id' => $userId]);
    if ($streakUpdate->rowCount() === 0) {
        $existsStatement = $pdo->prepare('SELECT id FROM users WHERE id = :user_id');
        $existsStatement->execute(['user_id' => $userId]);
        if ($existsStatement->fetchColumn() === false) {
            throw new RuntimeException('The authenticated user no longer exists.');
        }
    }
    $streakStatement = $pdo->prepare('SELECT study_streak FROM users WHERE id = :user_id');
    $streakStatement->execute(['user_id' => $userId]);
    $studyStreak = (int) $streakStatement->fetchColumn();

    $passingScore = (float) ($attempt['passing_score'] ?? 70.0);
    $result = [
        'success' => true,
        'mode' => (string) $attempt['mode'],
        'mode_label' => learnify_exam_mode_settings((string) $attempt['mode'], 0)['label'],
        'question_count' => count($questionIds),
        'correct_count' => $correctCount,
        'percentage' => $percentage,
        'passed' => $percentage >= $passingScore,
        'passing_score' => $passingScore,
        'study_streak' => $studyStreak,
        'review' => $review,
    ];
    $pdo->commit();

    $_SESSION['exam_attempts'][$attemptId]['result'] = $result;
    learnify_exam_json_response(200, $result);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Learnify exam processing failed: ' . $exception->getMessage());
    learnify_exam_json_response(500, ['success' => false, 'message' => 'Your exam could not be processed. Please try again.']);
}
