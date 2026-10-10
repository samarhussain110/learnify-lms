<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (isset($_SESSION['user_id'])) {
    header('Location: student/dashboard.php');
    exit;
}

$pageTitle = 'Create your account';
$errors = [];
$fullName = '';
$email = '';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $fullName = trim(learnify_post_string('full_name'));
    $email = strtolower(trim(learnify_post_string('email')));
    $password = learnify_post_string('password');
    $confirmPassword = learnify_post_string('confirm_password');

    if (!learnify_csrf_is_valid(learnify_post_string('_csrf_token'))) {
        $errors[] = 'Your form session expired. Please try again.';
    }
    $nameCharacterCount = preg_match_all('/./us', $fullName);
    if ($fullName === '' || $nameCharacterCount === false || $nameCharacterCount > 120) {
        $errors[] = 'Enter your full name (up to 120 characters).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
        $errors[] = 'Enter a valid email address.';
    }
    if (strlen($password) < 8 || strlen($password) > 72) {
        $errors[] = 'Your password must be between 8 and 72 bytes.';
    }
    if ($password !== $confirmPassword) {
        $errors[] = 'The password confirmation does not match.';
    }

    if ($errors === []) {
        try {
            $existingUser = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
            $existingUser->execute(['email' => $email]);

            if ($existingUser->fetch() !== false) {
                $errors[] = 'An account with that email address already exists.';
            } else {
                $passwordHash = password_hash($password, PASSWORD_BCRYPT);
                $insertUser = $pdo->prepare(
                    'INSERT INTO users (name, email, password_hash) VALUES (:name, :email, :password_hash)'
                );
                $insertUser->execute([
                    'name' => $fullName,
                    'email' => $email,
                    'password_hash' => $passwordHash,
                ]);

                header('Location: login.php?registered=1');
                exit;
            }
        } catch (PDOException $exception) {
            if ((string) $exception->getCode() === '23000') {
                $errors[] = 'An account with that email address already exists.';
            } else {
                error_log('Learnify registration failed: ' . $exception->getMessage());
                $errors[] = 'We could not create your account right now. Please try again later.';
            }
        }
    }
}

require __DIR__ . '/includes/header.php';
?>
<main class="auth-shell">
    <section class="auth-card glass-card" aria-labelledby="register-heading">
        <div class="auth-heading">
            <span class="module-icon" aria-hidden="true"><i class="fa-solid fa-user-plus"></i></span>
            <p class="eyebrow">Start learning today</p>
            <h1 id="register-heading">Create your Learnify account</h1>
            <p class="text-muted">Build your skills with guided lessons and practice.</p>
        </div>

        <?php if ($errors !== []): ?>
            <div class="form-alert form-alert-error" role="alert">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= learnify_h($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form class="stack" method="post" action="register.php">
            <input type="hidden" name="_csrf_token" value="<?= learnify_h(learnify_csrf_token()) ?>">

            <div class="form-group">
                <label class="form-label" for="full_name">Full name</label>
                <input
                    class="input"
                    id="full_name"
                    name="full_name"
                    type="text"
                    autocomplete="name"
                    maxlength="120"
                    value="<?= learnify_h($fullName) ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label class="form-label" for="email">Email</label>
                <input
                    class="input"
                    id="email"
                    name="email"
                    type="email"
                    autocomplete="email"
                    maxlength="254"
                    value="<?= learnify_h($email) ?>"
                    required
                >
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <input
                    class="input"
                    id="password"
                    name="password"
                    type="password"
                    autocomplete="new-password"
                    minlength="8"
                    maxlength="72"
                    required
                >
                <small class="text-muted">Use at least 8 characters.</small>
            </div>

            <div class="form-group">
                <label class="form-label" for="confirm_password">Confirm password</label>
                <input
                    class="input"
                    id="confirm_password"
                    name="confirm_password"
                    type="password"
                    autocomplete="new-password"
                    minlength="8"
                    maxlength="72"
                    required
                >
            </div>

            <button class="button w-full" type="submit">Create account</button>
        </form>

        <p class="auth-switch text-muted">
            Already have an account? <a href="login.php">Log in</a>
        </p>
    </section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
