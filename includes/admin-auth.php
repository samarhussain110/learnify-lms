<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$adminCheck = $pdo->prepare(
    'SELECT id, name
     FROM users
     WHERE id = :user_id AND role = \'admin\' AND is_active = 1
     LIMIT 1'
);
$adminCheck->execute(['user_id' => (int) $_SESSION['user_id']]);
$adminUser = $adminCheck->fetch();

if (!is_array($adminUser)) {
    http_response_code(403);
    $pageTitle = 'Access denied';
    require __DIR__ . '/header.php';
    ?>
    <main class="section container">
        <section class="glass-card card empty-state">
            <h1>Admin access required</h1>
            <p class="text-muted">Your account is not authorized to view this page.</p>
            <a class="button" href="../index.php">Return to Learnify</a>
        </section>
    </main>
    <?php
    require __DIR__ . '/footer.php';
    exit;
}
