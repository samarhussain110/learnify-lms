<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function learnify_note_response(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    header('Allow: POST');
    learnify_note_response(405, ['success' => false, 'message' => 'Method not allowed.']);
}

if (!isset($_SESSION['user_id'])) {
    learnify_note_response(401, ['success' => false, 'message' => 'Please log in to save notes.']);
}

$requestBody = file_get_contents('php://input');
if ($requestBody === false || strlen($requestBody) > 110000) {
    learnify_note_response(413, ['success' => false, 'message' => 'The notes request is too large.']);
}

try {
    $payload = json_decode($requestBody, true, 16, JSON_THROW_ON_ERROR);
} catch (JsonException) {
    learnify_note_response(400, ['success' => false, 'message' => 'Invalid JSON request.']);
}

if (!is_array($payload)) {
    learnify_note_response(400, ['success' => false, 'message' => 'Invalid request payload.']);
}

$csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!learnify_csrf_is_valid($csrfToken)) {
    learnify_note_response(403, ['success' => false, 'message' => 'Your session token is invalid. Refresh the page and try again.']);
}

$resourceId = filter_var($payload['resource_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$noteContent = $payload['note_content'] ?? null;
if ($resourceId === false || $resourceId === null || !is_string($noteContent)) {
    learnify_note_response(422, ['success' => false, 'message' => 'A valid resource and note are required.']);
}
if (strlen($noteContent) > 100000) {
    learnify_note_response(422, ['success' => false, 'message' => 'Notes must not exceed 100,000 bytes.']);
}

$userId = (int) $_SESSION['user_id'];
try {
    $resourceStatement = $pdo->prepare(
        'SELECT resources.chapter_id, chapters.module_id
         FROM resources
         INNER JOIN chapters ON chapters.id = resources.chapter_id
         INNER JOIN modules ON modules.id = chapters.module_id
         WHERE resources.id = :resource_id
           AND chapters.is_published = 1
           AND modules.is_published = 1
         LIMIT 1'
    );
    $resourceStatement->execute(['resource_id' => $resourceId]);
    $resource = $resourceStatement->fetch();
    if ($resource === false) {
        learnify_note_response(404, ['success' => false, 'message' => 'The selected learning resource is unavailable.']);
    }

    $moduleId = (int) $resource['module_id'];
    $chapterId = (int) $resource['chapter_id'];
    $pdo->beginTransaction();

    // Serialize writes to a user's chapter note so simultaneous autosaves do not create duplicates.
    $moduleLock = $pdo->prepare('SELECT id FROM modules WHERE id = :module_id FOR UPDATE');
    $moduleLock->execute(['module_id' => $moduleId]);

    $noteStatement = $pdo->prepare(
        'SELECT id
         FROM student_notes
         WHERE user_id = :user_id AND module_id = :module_id AND chapter_id = :chapter_id
         ORDER BY updated_at DESC, id DESC
         LIMIT 1
         FOR UPDATE'
    );
    $noteStatement->execute([
        'user_id' => $userId,
        'module_id' => $moduleId,
        'chapter_id' => $chapterId,
    ]);
    $noteId = $noteStatement->fetchColumn();

    if ($noteId !== false) {
        $saveStatement = $pdo->prepare(
            'UPDATE student_notes
             SET note_content = :note_content, updated_at = CURRENT_TIMESTAMP
             WHERE id = :id AND user_id = :user_id'
        );
        $saveStatement->execute([
            'note_content' => $noteContent,
            'id' => (int) $noteId,
            'user_id' => $userId,
        ]);
    } else {
        $saveStatement = $pdo->prepare(
            'INSERT INTO student_notes (user_id, module_id, chapter_id, note_content)
             VALUES (:user_id, :module_id, :chapter_id, :note_content)'
        );
        $saveStatement->execute([
            'user_id' => $userId,
            'module_id' => $moduleId,
            'chapter_id' => $chapterId,
            'note_content' => $noteContent,
        ]);
    }

    $pdo->commit();
    learnify_note_response(200, ['success' => true, 'message' => 'Notes saved.']);
} catch (Throwable $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Learnify note save failed: ' . $exception->getMessage());
    learnify_note_response(500, ['success' => false, 'message' => 'We could not save your notes. Please try again.']);
}
