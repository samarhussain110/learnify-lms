<?php
declare(strict_types=1);

function learnify_exam_normalize_question_type(string $questionType): string
{
    return match ($questionType) {
        'mcq', 'multiple_choice' => 'mcq',
        'tf', 'true_false' => 'tf',
        'fill', 'short_answer' => 'fill',
        default => $questionType,
    };
}

function learnify_exam_options(mixed $jsonOptions, string $questionType): array
{
    $questionType = learnify_exam_normalize_question_type($questionType);

    try {
        $decoded = is_string($jsonOptions) && $jsonOptions !== ''
            ? json_decode($jsonOptions, true, 16, JSON_THROW_ON_ERROR)
            : [];
    } catch (JsonException) {
        $decoded = [];
    }

    if (!is_array($decoded)) {
        $decoded = [];
    }
    if ($decoded === [] && $questionType === 'tf') {
        $decoded = ['true' => 'True', 'false' => 'False'];
    }

    $options = [];
    foreach ($decoded as $key => $option) {
        $fallbackValue = array_is_list($decoded)
            ? (is_int($key) && $key < 26 ? chr(65 + $key) : (string) $key)
            : $key;
        if (is_array($option)) {
            $value = $option['value'] ?? $option['id'] ?? $option['key'] ?? $fallbackValue;
            $label = $option['label'] ?? $option['text'] ?? $option['value'] ?? $option['id'] ?? null;
            if (!is_scalar($value) || !is_scalar($label)) {
                continue;
            }
        } elseif (is_scalar($option)) {
            $value = $fallbackValue;
            $label = $option;
        } else {
            continue;
        }

        $options[] = [
            'value' => (string) $value,
            'label' => (string) $label,
        ];
    }

    return $options;
}

function learnify_exam_normalize_answer(string $answer, array $options): ?string
{
    $answer = trim($answer);
    foreach ($options as $option) {
        if (
            hash_equals((string) $option['value'], $answer)
            || strcasecmp((string) $option['label'], $answer) === 0
        ) {
            return (string) $option['value'];
        }
    }

    return null;
}

function learnify_exam_fill_answer_matches(string $submittedAnswer, string $correctAnswers): bool
{
    $normalizedSubmission = preg_replace('/\s+/u', ' ', trim($submittedAnswer));
    $normalizedSubmission = is_string($normalizedSubmission) ? $normalizedSubmission : trim($submittedAnswer);
    $submissionKey = function_exists('mb_strtolower')
        ? mb_strtolower($normalizedSubmission, 'UTF-8')
        : strtolower($normalizedSubmission);

    foreach (explode('|', $correctAnswers) as $correctAnswer) {
        $normalizedCorrect = preg_replace('/\s+/u', ' ', trim($correctAnswer));
        $normalizedCorrect = is_string($normalizedCorrect) ? $normalizedCorrect : trim($correctAnswer);
        $correctKey = function_exists('mb_strtolower')
            ? mb_strtolower($normalizedCorrect, 'UTF-8')
            : strtolower($normalizedCorrect);
        if ($correctKey !== '' && hash_equals($correctKey, $submissionKey)) {
            return true;
        }
    }

    return false;
}

function learnify_exam_correct_option(string $answer, array $options): ?array
{
    $normalized = learnify_exam_normalize_answer($answer, $options);
    if ($normalized === null) {
        return null;
    }

    foreach ($options as $option) {
        if (hash_equals((string) $option['value'], $normalized)) {
            return $option;
        }
    }

    return null;
}

function learnify_exam_mode_settings(string $mode, int $quizTimeLimit): array
{
    return match ($mode) {
        'practice' => ['label' => 'Practice Quiz', 'limit' => 10, 'duration' => null],
        'timed' => ['label' => 'Timed Speed Test', 'limit' => 20, 'duration' => 600],
        'certification' => ['label' => 'Full Certification Exam', 'limit' => 50, 'duration' => 3600],
        default => throw new InvalidArgumentException('Unsupported exam mode.'),
    };
}

function learnify_exam_json_response(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}
