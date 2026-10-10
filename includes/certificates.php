<?php
declare(strict_types=1);

function learnify_ensure_certificates_table(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS certificates (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NOT NULL,
            module_id BIGINT UNSIGNED NOT NULL,
            verification_code VARCHAR(32) NOT NULL,
            issued_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_certificates_user_module (user_id, module_id),
            UNIQUE KEY uq_certificates_verification_code (verification_code),
            KEY idx_certificates_module (module_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
}

function learnify_certificate_eligibility(PDO $pdo, int $userId, int $moduleId): array|false
{
    $statement = $pdo->prepare(
        'SELECT
            modules.id AS module_id,
            modules.name AS module_name,
            users.name AS student_name,
            (SELECT COUNT(*)
             FROM chapters
             WHERE chapters.module_id = modules.id
               AND chapters.is_published = 1) AS total_lessons,
            (SELECT COUNT(DISTINCT user_chapter_progress.chapter_id)
             FROM user_chapter_progress
             INNER JOIN chapters ON chapters.id = user_chapter_progress.chapter_id
             WHERE user_chapter_progress.user_id = users.id
               AND chapters.module_id = modules.id
               AND chapters.is_published = 1) AS completed_lessons,
            EXISTS (
                SELECT 1
                FROM quiz_attempts
                INNER JOIN quizzes ON quizzes.id = quiz_attempts.quiz_id
                WHERE quiz_attempts.user_id = users.id
                  AND quizzes.module_id = modules.id
                  AND quizzes.is_published = 1
            ) AS attempted_exam,
            (SELECT quiz_attempts.score_percentage
             FROM quiz_attempts
             INNER JOIN quizzes ON quizzes.id = quiz_attempts.quiz_id
             WHERE quiz_attempts.user_id = users.id
               AND quizzes.module_id = modules.id
               AND quizzes.is_published = 1
             ORDER BY
                (quiz_attempts.score_percentage >= quizzes.passing_score) DESC,
                quiz_attempts.score_percentage DESC,
                quiz_attempts.completed_at DESC
             LIMIT 1) AS best_quiz_score,
            (SELECT quizzes.passing_score
             FROM quiz_attempts
             INNER JOIN quizzes ON quizzes.id = quiz_attempts.quiz_id
             WHERE quiz_attempts.user_id = users.id
               AND quizzes.module_id = modules.id
               AND quizzes.is_published = 1
             ORDER BY
                (quiz_attempts.score_percentage >= quizzes.passing_score) DESC,
                quiz_attempts.score_percentage DESC,
                quiz_attempts.completed_at DESC
             LIMIT 1) AS best_quiz_passing_score,
            COALESCE(
                (SELECT MIN(quizzes.passing_score)
                 FROM quizzes
                 WHERE quizzes.module_id = modules.id
                   AND quizzes.is_published = 1),
                70
            ) AS required_passing_score,
            EXISTS (
                SELECT 1
                FROM quiz_attempts
                INNER JOIN quizzes ON quizzes.id = quiz_attempts.quiz_id
                WHERE quiz_attempts.user_id = users.id
                  AND quizzes.module_id = modules.id
                  AND quizzes.is_published = 1
                  AND quiz_attempts.score_percentage >= quizzes.passing_score
            ) AS passed_exam
         FROM modules
         INNER JOIN users ON users.id = :user_id
         WHERE modules.id = :module_id
           AND modules.is_published = 1
           AND users.role = \'student\'
           AND users.is_active = 1
         LIMIT 1'
    );
    $statement->execute(['user_id' => $userId, 'module_id' => $moduleId]);
    $record = $statement->fetch();

    return is_array($record) ? $record : false;
}

function learnify_issue_certificate(PDO $pdo, int $userId, int $moduleId): array|false
{
    $eligibility = learnify_certificate_eligibility($pdo, $userId, $moduleId);
    if (!is_array($eligibility)
        || (int) $eligibility['total_lessons'] < 1
        || (int) $eligibility['completed_lessons'] !== (int) $eligibility['total_lessons']
        || !(bool) $eligibility['passed_exam']) {
        return false;
    }

    learnify_ensure_certificates_table($pdo);

    $existingStatement = $pdo->prepare(
        'SELECT verification_code, issued_at
         FROM certificates
         WHERE user_id = :user_id AND module_id = :module_id
         LIMIT 1'
    );
    $existingStatement->execute(['user_id' => $userId, 'module_id' => $moduleId]);
    $existing = $existingStatement->fetch();
    if (is_array($existing)) {
        return $eligibility + $existing;
    }

    $insertStatement = $pdo->prepare(
        'INSERT INTO certificates (user_id, module_id, verification_code)
         VALUES (:user_id, :module_id, :verification_code)'
    );
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $code = 'LNF-CERT-' . strtoupper(bin2hex(random_bytes(4)));
        try {
            $insertStatement->execute([
                'user_id' => $userId,
                'module_id' => $moduleId,
                'verification_code' => $code,
            ]);
        } catch (PDOException $exception) {
            if ($exception->getCode() !== '23000') {
                throw $exception;
            }

            $existingStatement->execute(['user_id' => $userId, 'module_id' => $moduleId]);
            $existing = $existingStatement->fetch();
            if (is_array($existing)) {
                return $eligibility + $existing;
            }
            continue;
        }

        $existingStatement->execute(['user_id' => $userId, 'module_id' => $moduleId]);
        $issued = $existingStatement->fetch();
        if (is_array($issued)) {
            return $eligibility + $issued;
        }
    }

    throw new RuntimeException('Could not issue a unique certificate verification code.');
}

function learnify_pdf_text(string $value): string
{
    $converted = iconv('UTF-8', 'Windows-1252//TRANSLIT', $value);
    if (!is_string($converted)) {
        $converted = preg_replace('/[^\x20-\x7E]/', '?', $value) ?? '';
    }

    return str_replace(
        ["\\", '(', ')', "\r", "\n"],
        ["\\\\", '\\(', '\\)', ' ', ' '],
        $converted
    );
}

function learnify_certificate_pdf(array $certificate): string
{
    $pageWidth = 842.0;
    $pageHeight = 595.0;
    $commands = [];
    $commands[] = 'q 0.96 0.97 1 rg 0 0 ' . $pageWidth . ' ' . $pageHeight . ' re f Q';
    $commands[] = 'q 0.12 0.10 0.30 RG 3 w 28 28 786 539 re S Q';
    $commands[] = 'q 0.49 0.26 0.80 RG 1.2 w 40 40 762 515 re S Q';
    $commands[] = 'q 0.86 0.18 0.48 rg 0 0 12 595 re f Q';
    $commands[] = 'q 0.27 0.23 0.73 rg 830 0 12 595 re f Q';

    $addText = static function (
        string $text,
        float $x,
        float $y,
        int $size,
        string $font,
        array $color = [0.08, 0.07, 0.20]
    ) use (&$commands, $pageWidth): void {
        if ($x === 0.0) {
            $estimatedWidth = strlen($text) * $size * 0.26;
            $x = max(48, ($pageWidth - $estimatedWidth) / 2);
        }
        $commands[] = sprintf(
            'BT /%s %d Tf %.3f %.3f %.3f rg 1 0 0 1 %.2f %.2f Tm (%s) Tj ET',
            $font,
            $size,
            $color[0],
            $color[1],
            $color[2],
            $x,
            $y,
            learnify_pdf_text($text)
        );
    };

    $addText('LEARNIFY LMS', 0, 525, 16, 'F2', [0.31, 0.24, 0.68]);
    $addText('CERTIFICATE OF ACHIEVEMENT', 0, 476, 12, 'F2', [0.52, 0.20, 0.46]);
    $addText('This certificate is proudly presented to', 0, 425, 14, 'F1', [0.24, 0.27, 0.36]);
    $addText((string) $certificate['student_name'], 0, 373, 30, 'F2', [0.08, 0.07, 0.20]);
    $addText('for successfully completing all lessons and passing the quiz in', 0, 332, 13, 'F1', [0.24, 0.27, 0.36]);
    $addText((string) $certificate['module_name'], 0, 292, 22, 'F2', [0.31, 0.24, 0.68]);
    $addText('Issued ' . date('F j, Y', strtotime((string) $certificate['issued_at'])), 0, 246, 12, 'F1', [0.24, 0.27, 0.36]);
    $addText((string) $certificate['verification_code'], 0, 215, 11, 'F2', [0.31, 0.24, 0.68]);

    $commands[] = 'q 0.86 0.18 0.48 RG 2 w 403 166 m 403 175.94 411.06 184 421 184 c 430.94 184 439 175.94 439 166 c 439 156.06 430.94 148 421 148 c 411.06 148 403 156.06 403 166 c S Q';
    $commands[] = 'q 0.49 0.26 0.80 RG 1 w 411 166 m 418 159 l 421 163 l 425 157 l 431 166 l S Q';
    $commands[] = 'q 0.49 0.26 0.80 RG 1 w 105 130 m 310 130 l S Q';
    $commands[] = 'q 0.49 0.26 0.80 RG 1 w 532 130 m 737 130 l S Q';
    $addText('Learnify Academic Team', 143, 108, 10, 'F2', [0.24, 0.27, 0.36]);
    $addText('Official signature', 578, 108, 10, 'F1', [0.24, 0.27, 0.36]);
    $addText('Verify at Learnify LMS using the code above', 0, 68, 9, 'F1', [0.38, 0.40, 0.48]);

    $stream = implode("\n", $commands) . "\n";
    $objects = [
        '<< /Type /Catalog /Pages 2 0 R >>',
        '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
        '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 842 595] /Resources << /Font << /F1 4 0 R /F2 5 0 R >> >> /Contents 6 0 R >>',
        '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>',
        '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>',
        '<< /Length ' . strlen($stream) . " >>\nstream\n" . $stream . 'endstream',
    ];

    $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
    $offsets = [0];
    foreach ($objects as $index => $object) {
        $offsets[] = strlen($pdf);
        $pdf .= ($index + 1) . " 0 obj\n" . $object . "\nendobj\n";
    }

    $xrefOffset = strlen($pdf);
    $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";
    for ($index = 1; $index <= count($objects); $index++) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$index]);
    }
    $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
    $pdf .= "startxref\n" . $xrefOffset . "\n%%EOF";

    return $pdf;
}
