<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Allow: POST');
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Please log in to update lesson progress.']);
    exit;
}
if (!learnify_csrf_is_valid($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Your session token is invalid. Refresh and try again.']);
    exit;
}

$rawBody = file_get_contents('php://input');
if ($rawBody === false || strlen($rawBody) > 8192) {
    http_response_code(413);
    echo json_encode(['success' => false, 'message' => 'The request is too large.']);
    exit;
}
try {
    $request = json_decode($rawBody, true, 16, JSON_THROW_ON_ERROR);
} catch (JsonException) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON request.']);
    exit;
}
if (!is_array($request)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request payload.']);
    exit;
}

$chapterId = filter_var($request['chapter_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$moduleId = filter_var($request['module_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$action = $request['action'] ?? 'complete';
if ($chapterId === false || $chapterId === null || $moduleId === false || $moduleId === null) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'A valid module and lesson are required.']);
    exit;
}
if (!is_string($action) || !in_array($action, ['complete', 'incomplete'], true)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Choose a valid lesson progress action.']);
    exit;
}

try {
    $chapterStatement = $pdo->prepare(
        'SELECT chapters.id
         FROM chapters
         INNER JOIN modules ON modules.id = chapters.module_id
         WHERE chapters.id = :chapter_id
           AND chapters.module_id = :module_id
           AND chapters.is_published = 1
           AND modules.is_published = 1
         LIMIT 1'
    );
    $chapterStatement->execute(['chapter_id' => $chapterId, 'module_id' => $moduleId]);
    if ($chapterStatement->fetchColumn() === false) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'This lesson is not available in the selected module.']);
        exit;
    }

    $pdo->beginTransaction();
    if ($action === 'complete') {
        $progressChange = $pdo->prepare(
            'INSERT IGNORE INTO user_chapter_progress (user_id, chapter_id)
             VALUES (:user_id, :chapter_id)'
        );
        $progressChange->execute([
            'user_id' => (int) $_SESSION['user_id'],
            'chapter_id' => $chapterId,
        ]);
    } else {
        $progressChange = $pdo->prepare(
            'DELETE FROM user_chapter_progress
             WHERE user_id = :user_id AND chapter_id = :chapter_id'
        );
        $progressChange->execute([
            'user_id' => (int) $_SESSION['user_id'],
            'chapter_id' => $chapterId,
        ]);
    }

    $progressStatement = $pdo->prepare(
        'SELECT
            COUNT(chapters.id) AS total_lessons,
            COUNT(user_chapter_progress.chapter_id) AS completed_lessons
         FROM chapters
         LEFT JOIN user_chapter_progress
            ON user_chapter_progress.chapter_id = chapters.id
           AND user_chapter_progress.user_id = :user_id
         WHERE chapters.module_id = :module_id AND chapters.is_published = 1'
    );
    $progressStatement->execute([
        'user_id' => (int) $_SESSION['user_id'],
        'module_id' => $moduleId,
    ]);
    $progress = $progressStatement->fetch();
    $totalLessons = (int) ($progress['total_lessons'] ?? 0);
    $completedLessons = (int) ($progress['completed_lessons'] ?? 0);
    $lessonCompletedStatement = $pdo->prepare(
        'SELECT 1
         FROM user_chapter_progress
         WHERE user_id = :user_id AND chapter_id = :chapter_id
         LIMIT 1'
    );
    $lessonCompletedStatement->execute([
        'user_id' => (int) $_SESSION['user_id'],
        'chapter_id' => $chapterId,
    ]);
    $lessonCompleted = $lessonCompletedStatement->fetchColumn() !== false;
    $pdo->commit();

    echo json_encode([
        'success' => true,
        'completed' => $lessonCompleted,
        'total_lessons' => $totalLessons,
        'completed_lessons' => $completedLessons,
        'completion_percentage' => $totalLessons > 0 ? (int) round(($completedLessons / $totalLessons) * 100) : 0,
        'module_completed' => $totalLessons > 0 && $completedLessons === $totalLessons,
    ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Learnify lesson completion failed: ' . $exception->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lesson progress could not be saved. Please try again.']);
}
