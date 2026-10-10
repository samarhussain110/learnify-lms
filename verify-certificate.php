<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/certificates.php';

$requestedCode = $_GET['code'] ?? '';
$code = is_string($requestedCode) ? strtoupper(trim($requestedCode)) : '';
$certificate = null;
$lookupError = '';

if ($code !== '') {
    if (preg_match('/^LNF-CERT-[A-F0-9]{8}$/', $code) !== 1) {
        $lookupError = 'Enter a certificate code in the format LNF-CERT-XXXXXXXX.';
    } else {
        try {
            learnify_ensure_certificates_table($pdo);
            $statement = $pdo->prepare(
                'SELECT certificates.verification_code, certificates.issued_at,
                        users.name AS student_name, modules.name AS module_name
                 FROM certificates
                 INNER JOIN users ON users.id = certificates.user_id
                 INNER JOIN modules ON modules.id = certificates.module_id
                 WHERE certificates.verification_code = :verification_code
                 LIMIT 1'
            );
            $statement->execute(['verification_code' => $code]);
            $record = $statement->fetch();
            if (is_array($record)) {
                $certificate = $record;
            } else {
                $lookupError = 'No certificate was found for that verification code.';
            }
        } catch (Throwable $exception) {
            error_log('Learnify certificate lookup failed: ' . $exception->getMessage());
            http_response_code(500);
            $lookupError = 'Certificate verification is temporarily unavailable.';
        }
    }
}

$pageTitle = 'Verify Certificate';
require __DIR__ . '/includes/header.php';
?>
<main class="section container-wide">
    <section class="glass-card certificate-verification-card">
        <p class="eyebrow">Learnify LMS</p>
        <h1>Verify a certificate</h1>
        <p class="text-muted">Enter the unique code printed on a Learnify certificate to confirm its holder, module, and issue date.</p>
        <form class="certificate-verify-form" method="get" action="verify-certificate.php">
            <label class="form-label" for="certificate-code">Verification code</label>
            <div class="certificate-verify-input-row">
                <input
                    class="input"
                    id="certificate-code"
                    name="code"
                    type="text"
                    maxlength="32"
                    pattern="LNF-CERT-[A-Fa-f0-9]{8}"
                    value="<?= learnify_h($code) ?>"
                    placeholder="LNF-CERT-XXXXXXXX"
                    autocomplete="off"
                    required
                >
                <button class="button" type="submit">Verify</button>
            </div>
        </form>
        <?php if ($certificate !== null): ?>
            <div class="certificate-verification-result is-valid" role="status">
                <span class="certificate-verification-icon" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
                <div>
                    <p class="eyebrow">Certificate verified</p>
                    <h2><?= learnify_h($certificate['student_name']) ?></h2>
                    <p class="text-muted">Successfully completed <?= learnify_h($certificate['module_name']) ?>.</p>
                    <p class="certificate-date">
                        Issued <?= learnify_h(date('F j, Y', (int) strtotime((string) $certificate['issued_at']))) ?>
                    </p>
                    <p class="text-muted mb-0">Code: <strong><?= learnify_h($certificate['verification_code']) ?></strong></p>
                </div>
            </div>
        <?php elseif ($lookupError !== ''): ?>
            <p class="form-alert form-alert-error" role="alert"><?= learnify_h($lookupError) ?></p>
        <?php endif; ?>
    </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
