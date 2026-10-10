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

try {
    $certificate = learnify_issue_certificate($pdo, (int) $_SESSION['user_id'], $moduleId);
    if (!is_array($certificate)) {
        header('Location: certificate.php?module_id=' . $moduleId);
        exit;
    }

    $pdf = learnify_certificate_pdf($certificate);
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="learnify-certificate-' . strtolower(substr(
        (string) $certificate['verification_code'],
        -8
    )) . '.pdf"');
    header('Content-Length: ' . strlen($pdf));
    header('Cache-Control: private, no-store, max-age=0');
    echo $pdf;
} catch (Throwable $exception) {
    error_log('Learnify certificate generation failed: ' . $exception->getMessage());
    http_response_code(500);
    exit('The certificate could not be generated right now. Please try again later.');
}
