<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/certificates.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$moduleId = filter_input(INPUT_GET, 'module_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($moduleId === false || $moduleId === null) {
    http_response_code(400);
    exit('A valid module is required.');
}

$moduleStatement = $pdo->prepare(
    'SELECT id, name
     FROM modules
     WHERE id = :module_id AND is_published = 1
     LIMIT 1'
);
$moduleStatement->execute(['module_id' => $moduleId]);
$module = $moduleStatement->fetch();
if (!is_array($module)) {
    http_response_code(404);
    exit('This module is not available.');
}

$certificate = learnify_issue_certificate($pdo, (int) $_SESSION['user_id'], $moduleId);
$eligibility = $certificate ?: learnify_certificate_eligibility(
    $pdo,
    (int) $_SESSION['user_id'],
    $moduleId
);
$totalLessons = is_array($eligibility) ? (int) $eligibility['total_lessons'] : 0;
$completedLessons = is_array($eligibility) ? (int) $eligibility['completed_lessons'] : 0;
$completedModule = $totalLessons > 0 && $completedLessons === $totalLessons;
$passedExam = is_array($eligibility) && (bool) $eligibility['passed_exam'];
$attemptedExam = is_array($eligibility) && (bool) $eligibility['attempted_exam'];
$bestQuizScore = is_array($eligibility) && $eligibility['best_quiz_score'] !== null
    ? (float) $eligibility['best_quiz_score']
    : null;
$requiredPassingScore = is_array($eligibility)
    ? (float) ($eligibility['best_quiz_passing_score'] ?? $eligibility['required_passing_score'])
    : 70.0;
$eligible = $certificate !== false;

if (!$eligible) {
    http_response_code(403);
}

$pageTitle = $eligible ? 'Module Certificate' : 'Certificate unavailable';
require __DIR__ . '/../includes/header.php';
?>
<main class="certificate-page container-wide">
    <?php if (!$eligible): ?>
        <section class="glass-card certificate-unavailable">
            <p class="eyebrow">Certificate unavailable</p>
            <h1>Keep learning to unlock your certificate</h1>
            <div class="certificate-eligibility-breakdown" aria-label="Certificate eligibility requirements">
                <p class="<?= $completedModule ? 'is-requirement-met' : 'is-requirement-pending' ?>">
                    <?= $completedModule
                        ? '✓ Lessons Completed (100%)'
                        : '✕ Lessons Incomplete (' . $completedLessons . '/' . $totalLessons . ' completed)' ?>
                </p>
                <p class="<?= $passedExam ? 'is-requirement-met' : 'is-requirement-pending' ?>">
                    <?php if ($passedExam): ?>
                        ✓ Quiz Passed (Score: <?= number_format((float) $bestQuizScore, 1) ?>%)
                    <?php elseif ($attemptedExam): ?>
                        ✕ Quiz Not Passed (Best Score: <?= number_format((float) $bestQuizScore, 1) ?>%; required: <?= number_format($requiredPassingScore, 1) ?>%)
                    <?php else: ?>
                        ✕ Quiz Not Attempted
                    <?php endif; ?>
                </p>
            </div>
            <p class="text-muted">
                To unlock your certificate, please pass the module quiz with a score of
                <?= number_format($requiredPassingScore, 1) ?>% or higher and complete 100% of the published lessons.
            </p>
            <div class="certificate-actions">
                <?php if (!$passedExam): ?>
                    <a class="button certificate-download-button" href="take-exam.php?module_id=<?= (int) $moduleId ?>">
                        <i class="fa-solid fa-clipboard-question" aria-hidden="true"></i> Take Final Quiz / Exam Now
                    </a>
                <?php endif; ?>
                <?php if (!$completedModule): ?>
                    <a class="button button-secondary" href="module.php?module_id=<?= (int) $moduleId ?>">
                        <i class="fa-solid fa-book-open" aria-hidden="true"></i> Continue Lessons
                    </a>
                <?php endif; ?>
            </div>
        </section>
    <?php else: ?>
        <div class="certificate-actions">
            <a class="button button-secondary" href="module.php?module_id=<?= (int) $moduleId ?>">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to module
            </a>
            <button class="button" type="button" data-print-certificate>
                <i class="fa-solid fa-print" aria-hidden="true"></i> Print / Save certificate
            </button>
            <a class="button" href="download-certificate.php?module_id=<?= (int) $moduleId ?>">
                <i class="fa-solid fa-file-pdf" aria-hidden="true"></i> Download PDF
            </a>
        </div>
        <article class="glass-card certificate-card">
            <div class="certificate-inner">
                <p class="eyebrow">Learnify · Certificate of Achievement</p>
                <div class="certificate-seal" aria-hidden="true"><i class="fa-solid fa-graduation-cap"></i></div>
                <h1>Certificate of Completion</h1>
                <p class="certificate-intro">This certificate is presented to</p>
                <p class="certificate-recipient"><?= learnify_h((string) ($_SESSION['full_name'] ?? 'Learnify Student')) ?></p>
                <p class="certificate-intro">for successfully completing</p>
                <h2><?= learnify_h($module['name']) ?></h2>
                <p class="certificate-date">Awarded <?= learnify_h(date('F j, Y', (int) strtotime((string) $certificate['issued_at']))) ?></p>
                <p class="certificate-eligibility">
                    All published lessons completed and module passing score achieved
                </p>
                <p class="certificate-verification-code">
                    Verification code: <strong><?= learnify_h($certificate['verification_code']) ?></strong>
                </p>
                <a class="certificate-verification-link" href="../verify-certificate.php?code=<?= rawurlencode((string) $certificate['verification_code']) ?>">
                    Verify this certificate
                </a>
            </div>
        </article>
    <?php endif; ?>
</main>
<?php if ($eligible): ?>
<script>
document.querySelector('[data-print-certificate]').addEventListener('click', () => window.print());
</script>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>