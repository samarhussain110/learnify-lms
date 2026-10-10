<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

$scriptName = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/'));
$isStudentArea = basename(dirname($scriptName)) === 'student';
$isAdminArea = basename(dirname($scriptName)) === 'admin';
$isNestedArea = $isStudentArea || $isAdminArea;
$appBase = $isNestedArea ? '..' : '.';
$stylesheet = $isNestedArea ? '../assets/css/style.css' : 'assets/css/style.css';
$isAuthenticated = isset($_SESSION['user_id'], $_SESSION['full_name']);
$studentAvatarUrl = '';
if ($isAuthenticated && ($_SESSION['role'] ?? '') === 'student') {
    $avatarStatement = $pdo->prepare(
        'SELECT avatar_path FROM users WHERE id = :user_id AND role = \'student\' LIMIT 1'
    );
    $avatarStatement->execute(['user_id' => (int) $_SESSION['user_id']]);
    $avatarPath = $avatarStatement->fetchColumn();
    if (is_string($avatarPath)
        && preg_match('~^uploads/avatars/[a-f0-9]{40}\.(?:jpg|png|webp)$~', $avatarPath)) {
        $avatarFile = dirname(__DIR__) . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, $avatarPath);
        if (is_file($avatarFile)) {
            $_SESSION['user_avatar'] = $avatarPath;
            $studentAvatarUrl = $appBase . '/' . $avatarPath;
        } else {
            $_SESSION['user_avatar'] = null;
        }
    } else {
        $_SESSION['user_avatar'] = null;
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#120B29">
    <title><?= isset($pageTitle) ? learnify_h($pageTitle) . ' | Learnify' : 'Learnify' ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="<?= learnify_h($stylesheet) ?>">
</head>
<body>
<header class="site-header">
    <nav class="navbar container-wide" aria-label="Main navigation">
        <a class="brand" href="<?= learnify_h($appBase) ?>/index.php" aria-label="Learnify home">
            <span class="brand-mark" aria-hidden="true"><i class="fa-solid fa-graduation-cap"></i></span>
            <span class="text-gradient">Learnify</span>
        </a>

        <?php if ($isAuthenticated): ?>
            <div class="nav-links">
                <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                    <a href="<?= learnify_h($appBase) ?>/admin/dashboard.php">Admin</a>
                    <a href="<?= learnify_h($appBase) ?>/admin/modules.php">Content</a>
                    <a class="nav-optional" href="<?= learnify_h($appBase) ?>/admin/quizzes.php">Question bank</a>
                    <a class="nav-optional" href="<?= learnify_h($appBase) ?>/admin/students.php">Students</a>
                <?php else: ?>
                    <a href="<?= learnify_h($appBase) ?>/student/dashboard.php">Dashboard</a>
                    <a href="<?= learnify_h($appBase) ?>/student/modules.php">Modules</a>
                    <a class="nav-optional" href="<?= learnify_h($appBase) ?>/student/take-exam.php">Take Exam</a>
                    <a class="nav-optional" href="<?= learnify_h($appBase) ?>/student/profile.php">My Profile</a>
                <?php endif; ?>
            </div>
            <div class="flex items-center gap-2">
                <?php if (($_SESSION['role'] ?? '') === 'student'): ?>
                    <a class="nav-optional nav-user-link" href="<?= learnify_h($appBase) ?>/student/profile.php">
                        <?php if ($studentAvatarUrl !== ''): ?>
                            <img class="nav-user-avatar" src="<?= learnify_h($studentAvatarUrl) ?>" alt="" width="32" height="32">
                        <?php endif; ?>
                        <?= learnify_h($_SESSION['full_name']) ?>
                    </a>
                <?php else: ?>
                    <span class="nav-optional"><?= learnify_h($_SESSION['full_name']) ?></span>
                <?php endif; ?>
                <form method="post" action="<?= learnify_h($appBase) ?>/logout.php">
                    <input type="hidden" name="_csrf_token" value="<?= learnify_h(learnify_csrf_token()) ?>">
                    <button class="button button-secondary" type="submit">Logout</button>
                </form>
            </div>
        <?php else: ?>
            <div class="nav-links">
                <a href="<?= learnify_h($appBase) ?>/login.php">Log in</a>
                <a class="button" href="<?= learnify_h($appBase) ?>/register.php">Create account</a>
            </div>
        <?php endif; ?>
    </nav>
</header>
