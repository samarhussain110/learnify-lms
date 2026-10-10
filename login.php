<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ' . ((string) ($_SESSION['role'] ?? '') === 'admin'
        ? 'admin/dashboard.php'
        : 'student/dashboard.php'));
    exit;
}

$pageTitle = 'Log in';
$errors = [];
$email = '';
$selectedRole = 'student';
$registered = isset($_GET['registered']) && $_GET['registered'] === '1';

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $email = strtolower(trim(learnify_post_string('email')));
    $password = learnify_post_string('password');
    $requestedRole = learnify_post_string('role');
    if (in_array($requestedRole, ['student', 'admin'], true)) {
        $selectedRole = $requestedRole;
    }

    if (!in_array($requestedRole, ['student', 'admin'], true)) {
        $errors[] = 'Choose whether you are logging in as a student or admin.';
    } elseif (!learnify_csrf_is_valid(learnify_post_string('_csrf_token'))) {
        $errors[] = 'Your form session expired. Please try again.';
    } elseif (
        !filter_var($email, FILTER_VALIDATE_EMAIL)
        || strlen($email) > 254
        || strlen($password) > 72
    ) {
        $errors[] = 'Enter a valid email address and password.';
    } else {
        try {
            $statement = $pdo->prepare(
                'SELECT id, name, email, password_hash, role, is_active
                 FROM users
                 WHERE email = :email
                 LIMIT 1'
            );
            $statement->execute(['email' => $email]);
            $user = $statement->fetch();

            if (
                $user === false
                || (int) $user['is_active'] !== 1
                || !password_verify($password, (string) $user['password_hash'])
            ) {
                $errors[] = 'The email address or password is incorrect.';
            } elseif ((string) $user['role'] !== $selectedRole) {
                $actualRole = (string) $user['role'];
                if (in_array($actualRole, ['student', 'admin'], true)) {
                    $actualRoleLabel = $actualRole === 'admin' ? 'Admin' : 'Student';
                    $errors[] = 'This account is registered as ' . $actualRoleLabel
                        . '. Select the ' . $actualRoleLabel . ' tab to continue.';
                } else {
                    $errors[] = 'This account type cannot sign in through the student or admin login.';
                }
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int) $user['id'];
                $_SESSION['full_name'] = (string) $user['name'];
                $_SESSION['role'] = (string) $user['role'];
                $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));

                header('Location: ' . ($selectedRole === 'admin'
                    ? 'admin/dashboard.php'
                    : 'student/dashboard.php'));
                exit;
            }
        } catch (PDOException $exception) {
            error_log('Learnify login failed: ' . $exception->getMessage());
            $errors[] = 'We could not sign you in right now. Please try again later.';
        }
    }
}

require __DIR__ . '/includes/header.php';
?>
<main class="auth-shell">
    <section class="auth-card glass-card" aria-labelledby="login-heading">
        <div class="auth-heading">
            <span class="module-icon" aria-hidden="true"><i class="fa-solid fa-right-to-bracket"></i></span>
            <p class="eyebrow">Welcome back</p>
            <h1 id="login-heading">Log in to Learnify</h1>
            <p class="text-muted">Continue where you left off and keep learning.</p>
        </div>

        <?php if ($registered): ?>
            <div class="form-alert form-alert-success" role="status">
                Your account is ready. Log in to continue.
            </div>
        <?php endif; ?>

        <?php if ($errors !== []): ?>
            <div class="form-alert form-alert-error" role="alert">
                <ul>
                    <?php foreach ($errors as $error): ?>
                        <li><?= learnify_h($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form class="stack" method="post" action="login.php">
            <input type="hidden" name="_csrf_token" value="<?= learnify_h(learnify_csrf_token()) ?>">

            <fieldset class="login-role-selector" aria-label="Choose account type">
                <legend class="form-label">Log in as</legend>
                <div class="login-role-tabs" role="radiogroup" aria-label="Account type">
                    <label class="login-role-option">
                        <input
                            type="radio"
                            name="role"
                            value="student"
                            <?= $selectedRole === 'student' ? 'checked' : '' ?>
                        >
                        <span><i class="fa-solid fa-user-graduate" aria-hidden="true"></i> Student</span>
                    </label>
                    <label class="login-role-option">
                        <input
                            type="radio"
                            name="role"
                            value="admin"
                            <?= $selectedRole === 'admin' ? 'checked' : '' ?>
                        >
                        <span><i class="fa-solid fa-user-shield" aria-hidden="true"></i> Admin</span>
                    </label>
                </div>
                <p class="login-role-hint text-muted" data-role-hint>
                    <?= $selectedRole === 'admin'
                        ? 'Sign in to manage Learnify content and view platform analytics.'
                        : 'Sign in to continue learning and track your progress.' ?>
                </p>
            </fieldset>

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
                    autocomplete="current-password"
                    required
                >
            </div>

            <button class="button w-full" type="submit">Log in</button>
        </form>

        <p class="auth-switch text-muted">
            New to Learnify? <a href="register.php">Create an account</a>
        </p>
    </section>
</main>
<script>
(() => {
    const roleInputs = document.querySelectorAll('input[name="role"]');
    const roleHint = document.querySelector('[data-role-hint]');
    const roleMessages = {
        student: 'Sign in to continue learning and track your progress.',
        admin: 'Sign in to manage Learnify content and view platform analytics.'
    };

    roleInputs.forEach((input) => {
        input.addEventListener('change', () => {
            if (input.checked) roleHint.textContent = roleMessages[input.value];
        });
    });
})();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
