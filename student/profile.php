<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/certificates.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}
if (($_SESSION['role'] ?? '') !== 'student') {
    http_response_code(403);
    exit('Student profile settings are available to student accounts only.');
}

$userId = (int) $_SESSION['user_id'];
$errors = [];
$successMessage = '';
$topics = [
    'Computer basics',
    'Web development',
    'PHP',
    'JavaScript',
    'Python',
    'C++',
    'C#',
    'Java',
    'Databases and SQL',
    'Data structures and algorithms',
    'Artificial intelligence',
    'Data science',
    'Software testing',
];

$requiredColumns = [
    'bio' => 'TEXT NULL',
    'avatar_path' => 'VARCHAR(255) NULL',
    'daily_study_minutes' => 'SMALLINT UNSIGNED NOT NULL DEFAULT 15',
    'weekly_module_target' => 'TINYINT UNSIGNED NOT NULL DEFAULT 1',
    'target_topics' => 'TEXT NULL',
];
$columnQuery = $pdo->prepare(
    'SELECT COLUMN_NAME
     FROM information_schema.COLUMNS
     WHERE TABLE_SCHEMA = DATABASE()
       AND TABLE_NAME = \'users\''
);
$columnQuery->execute();
$existingColumns = array_fill_keys($columnQuery->fetchAll(PDO::FETCH_COLUMN), true);
foreach ($requiredColumns as $column => $definition) {
    if (!isset($existingColumns[$column])) {
        $pdo->exec("ALTER TABLE users ADD COLUMN {$column} {$definition}");
    }
}

function learnify_profile_uploaded_avatar(array $file): string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Choose a profile picture to upload.');
    }
    if (!isset($file['tmp_name'], $file['size'])
        || !is_string($file['tmp_name'])
        || !is_uploaded_file($file['tmp_name'])
        || (int) $file['size'] < 1
        || (int) $file['size'] > 5 * 1024 * 1024) {
        throw new RuntimeException('Profile pictures must be smaller than 5 MB.');
    }

    $imageInfo = getimagesize($file['tmp_name']);
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    if (!is_array($imageInfo)
        || !isset($allowed[$mime])
        || ($imageInfo['mime'] ?? '') !== $mime
        || (int) $imageInfo[0] > 8000
        || (int) $imageInfo[1] > 8000) {
        throw new RuntimeException('Upload a valid JPEG, PNG, or WebP image (maximum dimensions 8000 × 8000).');
    }

    $uploadDirectory = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads'
        . DIRECTORY_SEPARATOR . 'avatars';
    if (!is_dir($uploadDirectory)
        && !mkdir($uploadDirectory, 0755, true)
        && !is_dir($uploadDirectory)) {
        throw new RuntimeException('Avatar storage is unavailable. Please contact support.');
    }

    $filename = bin2hex(random_bytes(20)) . '.' . $allowed[$mime];
    $destination = $uploadDirectory . DIRECTORY_SEPARATOR . $filename;
    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('The profile picture could not be saved. Please try again.');
    }

    return 'uploads/avatars/' . $filename;
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $action = learnify_post_string('action');
    if ($action === '') {
        $action = learnify_post_string('profile_action');
    }
    if (!learnify_csrf_is_valid(learnify_post_string('_csrf_token'))) {
        $errors[] = 'Your form session expired. Refresh the page and try again.';
    } elseif ($action === 'remove_avatar') {
        try {
            $existingAvatarStatement = $pdo->prepare(
                'SELECT avatar_path FROM users WHERE id = :user_id AND role = \'student\' LIMIT 1'
            );
            $existingAvatarStatement->execute(['user_id' => $userId]);
            $existingAvatarPath = $existingAvatarStatement->fetchColumn();
            if ($existingAvatarPath === false) {
                throw new RuntimeException('Your account could not be loaded. Please sign in again.');
            }

            $pdo->beginTransaction();
            $removeAvatarStatement = $pdo->prepare(
                'UPDATE users SET avatar_path = NULL WHERE id = :user_id AND role = \'student\''
            );
            $removeAvatarStatement->execute(['user_id' => $userId]);

            if (is_string($existingAvatarPath)
                && preg_match('~^uploads/avatars/[a-f0-9]{40}\.(?:jpg|png|webp)$~', $existingAvatarPath)) {
                $avatarFile = dirname(__DIR__) . DIRECTORY_SEPARATOR
                    . str_replace('/', DIRECTORY_SEPARATOR, $existingAvatarPath);
                if (is_file($avatarFile) && !unlink($avatarFile)) {
                    throw new RuntimeException('The profile picture could not be removed. Please try again.');
                }
            }

            $pdo->commit();
            $_SESSION['user_avatar'] = null;
            $successMessage = 'Your profile picture has been removed.';
        } catch (PDOException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log('Learnify avatar removal failed: ' . $exception->getMessage());
            $errors[] = 'Your profile picture could not be removed right now. Please try again.';
        } catch (RuntimeException $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errors[] = $exception->getMessage();
        }
    } elseif ($action === 'profile') {
        $name = trim(learnify_post_string('full_name'));
        $email = strtolower(trim(learnify_post_string('email')));
        $bio = trim(learnify_post_string('bio'));
        $nameLength = preg_match_all('/./us', $name);
        if ($name === '' || $nameLength === false || $nameLength > 120) {
            $errors[] = 'Enter your full name (up to 120 characters).';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
            $errors[] = 'Enter a valid email address.';
        }
        if (strlen($bio) > 1000) {
            $errors[] = 'Your bio or headline must be 1000 characters or fewer.';
        }

        $avatarPath = null;
        $newAvatarPath = null;
        if (isset($_FILES['avatar']) && ($_FILES['avatar']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            try {
                $newAvatarPath = learnify_profile_uploaded_avatar($_FILES['avatar']);
            } catch (RuntimeException $exception) {
                $errors[] = $exception->getMessage();
            }
        }

        if ($errors === []) {
            try {
                $existingUserStatement = $pdo->prepare(
                    'SELECT email, avatar_path FROM users WHERE id = :user_id LIMIT 1'
                );
                $existingUserStatement->execute(['user_id' => $userId]);
                $existingUser = $existingUserStatement->fetch();
                if (!is_array($existingUser)) {
                    throw new RuntimeException('Your account could not be loaded. Please sign in again.');
                }

                $duplicateEmailStatement = $pdo->prepare(
                    'SELECT id FROM users WHERE email = :email AND id <> :user_id LIMIT 1'
                );
                $duplicateEmailStatement->execute(['email' => $email, 'user_id' => $userId]);
                if ($duplicateEmailStatement->fetchColumn() !== false) {
                    throw new RuntimeException('That email address is already in use.');
                }

                $avatarPath = $newAvatarPath ?? (string) ($existingUser['avatar_path'] ?? '');
                $updateProfile = $pdo->prepare(
                    'UPDATE users
                     SET name = :name, email = :email, bio = :bio, avatar_path = :avatar_path
                     WHERE id = :user_id AND role = \'student\''
                );
                $updateProfile->execute([
                    'name' => $name,
                    'email' => $email,
                    'bio' => $bio !== '' ? $bio : null,
                    'avatar_path' => $avatarPath !== '' ? $avatarPath : null,
                    'user_id' => $userId,
                ]);
                $_SESSION['full_name'] = $name;
                $_SESSION['user_avatar'] = $avatarPath !== '' ? $avatarPath : null;
                $oldAvatarPath = (string) ($existingUser['avatar_path'] ?? '');
                if ($newAvatarPath !== null
                    && $oldAvatarPath !== ''
                    && $oldAvatarPath !== $newAvatarPath
                    && preg_match('~^uploads/avatars/[a-f0-9]{40}\.(?:jpg|png|webp)$~', $oldAvatarPath)) {
                    $oldAvatarFile = dirname(__DIR__) . DIRECTORY_SEPARATOR
                        . str_replace('/', DIRECTORY_SEPARATOR, $oldAvatarPath);
                    if (is_file($oldAvatarFile)) {
                        unlink($oldAvatarFile);
                    }
                }
                $successMessage = 'Your personal details have been updated.';
            } catch (PDOException $exception) {
                if ($newAvatarPath !== null) {
                    $newAvatarFile = dirname(__DIR__) . DIRECTORY_SEPARATOR
                        . str_replace('/', DIRECTORY_SEPARATOR, $newAvatarPath);
                    if (is_file($newAvatarFile)) {
                        unlink($newAvatarFile);
                    }
                }
                if ((string) $exception->getCode() === '23000') {
                    $errors[] = 'That email address is already in use.';
                } else {
                    error_log('Learnify profile update failed: ' . $exception->getMessage());
                    $errors[] = 'Your profile could not be updated right now. Please try again.';
                }
            } catch (RuntimeException $exception) {
                if ($newAvatarPath !== null) {
                    $newAvatarFile = dirname(__DIR__) . DIRECTORY_SEPARATOR
                        . str_replace('/', DIRECTORY_SEPARATOR, $newAvatarPath);
                    if (is_file($newAvatarFile)) {
                        unlink($newAvatarFile);
                    }
                }
                $errors[] = $exception->getMessage();
            }
        } elseif ($newAvatarPath !== null) {
            $newAvatarFile = dirname(__DIR__) . DIRECTORY_SEPARATOR
                . str_replace('/', DIRECTORY_SEPARATOR, $newAvatarPath);
            if (is_file($newAvatarFile)) {
                unlink($newAvatarFile);
            }
        }
    } elseif ($action === 'password') {
        $currentPassword = learnify_post_string('current_password');
        $newPassword = learnify_post_string('new_password');
        $confirmPassword = learnify_post_string('confirm_password');
        if (strlen($newPassword) < 8 || strlen($newPassword) > 72) {
            $errors[] = 'Your new password must be between 8 and 72 bytes.';
        }
        if ($newPassword !== $confirmPassword) {
            $errors[] = 'The new password confirmation does not match.';
        }
        if ($currentPassword === '') {
            $errors[] = 'Enter your current password.';
        }

        if ($errors === []) {
            $passwordStatement = $pdo->prepare(
                'SELECT password_hash FROM users WHERE id = :user_id AND role = \'student\' LIMIT 1'
            );
            $passwordStatement->execute(['user_id' => $userId]);
            $storedHash = $passwordStatement->fetchColumn();
            if (!is_string($storedHash) || !password_verify($currentPassword, $storedHash)) {
                $errors[] = 'Your current password is incorrect.';
            } else {
                $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $updatePassword = $pdo->prepare(
                    'UPDATE users SET password_hash = :password_hash WHERE id = :user_id'
                );
                $updatePassword->execute(['password_hash' => $newHash, 'user_id' => $userId]);
                session_regenerate_id(true);
                $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
                $successMessage = 'Your password has been changed.';
            }
        }
    } elseif ($action === 'preferences') {
        $dailyMinutes = filter_var(
            $_POST['daily_study_minutes'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 5, 'max_range' => 240]]
        );
        $weeklyModules = filter_var(
            $_POST['weekly_module_target'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1, 'max_range' => 7]]
        );
        $submittedTopics = $_POST['target_topics'] ?? [];
        if (!is_array($submittedTopics)) {
            $submittedTopics = [];
        }
        $selectedTopics = array_values(array_unique(array_filter(
            $submittedTopics,
            static fn (mixed $topic): bool => is_string($topic) && in_array($topic, $topics, true)
        )));
        if ($dailyMinutes === false || $weeklyModules === false) {
            $errors[] = 'Choose a daily goal between 5 and 240 minutes and a weekly goal between 1 and 7 modules.';
        }
        if (count($selectedTopics) > 8) {
            $errors[] = 'Select no more than 8 learning topics.';
        }

        if ($errors === []) {
            $updatePreferences = $pdo->prepare(
                'UPDATE users
                 SET daily_study_minutes = :daily_study_minutes,
                     weekly_module_target = :weekly_module_target,
                     target_topics = :target_topics
                 WHERE id = :user_id AND role = \'student\''
            );
            $updatePreferences->execute([
                'daily_study_minutes' => $dailyMinutes,
                'weekly_module_target' => $weeklyModules,
                'target_topics' => json_encode($selectedTopics, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
                'user_id' => $userId,
            ]);
            $successMessage = 'Your learning goals and topics have been saved.';
        }
    } else {
        $errors[] = 'Choose a valid profile action.';
    }
}

$profileStatement = $pdo->prepare(
    'SELECT name, email, bio, avatar_path, daily_study_minutes, weekly_module_target,
            target_topics, study_streak,
            CASE
                WHEN last_study_date >= CURRENT_DATE - INTERVAL 1 DAY THEN study_streak
                ELSE 0
            END AS current_streak
     FROM users
     WHERE id = :user_id AND role = \'student\'
     LIMIT 1'
);
$profileStatement->execute(['user_id' => $userId]);
$profile = $profileStatement->fetch();
if (!is_array($profile)) {
    http_response_code(404);
    exit('Student account not found.');
}

try {
    learnify_ensure_certificates_table($pdo);
    $earnedModulesStatement = $pdo->prepare(
        'SELECT modules.id
         FROM modules
         WHERE modules.is_published = 1
           AND EXISTS (
               SELECT 1 FROM chapters
               WHERE chapters.module_id = modules.id AND chapters.is_published = 1
           )
           AND NOT EXISTS (
               SELECT 1
               FROM chapters AS required_chapter
               WHERE required_chapter.module_id = modules.id
                 AND required_chapter.is_published = 1
                 AND NOT EXISTS (
                     SELECT 1
                     FROM user_chapter_progress
                     WHERE user_chapter_progress.user_id = :progress_user_id
                       AND user_chapter_progress.chapter_id = required_chapter.id
                 )
           )
           AND EXISTS (
               SELECT 1
               FROM quiz_attempts
               INNER JOIN quizzes ON quizzes.id = quiz_attempts.quiz_id
               WHERE quiz_attempts.user_id = :quiz_user_id
                 AND quizzes.module_id = modules.id
                 AND quizzes.is_published = 1
                 AND quiz_attempts.score_percentage >= quizzes.passing_score
           )'
    );
    $earnedModulesStatement->execute([
        'progress_user_id' => $userId,
        'quiz_user_id' => $userId,
    ]);
    foreach ($earnedModulesStatement->fetchAll(PDO::FETCH_COLUMN) as $earnedModuleId) {
        learnify_issue_certificate($pdo, $userId, (int) $earnedModuleId);
    }
    $certificateStatsStatement = $pdo->prepare(
        'SELECT COUNT(*)
         FROM certificates
         WHERE user_id = :user_id'
    );
    $certificateStatsStatement->execute(['user_id' => $userId]);
    $certificateCount = (int) $certificateStatsStatement->fetchColumn();
} catch (PDOException $exception) {
    error_log('Learnify profile certificate statistics failed: ' . $exception->getMessage());
    throw $exception;
}

$completedLessonsStatement = $pdo->prepare(
    'SELECT COUNT(DISTINCT user_chapter_progress.chapter_id)
     FROM user_chapter_progress
     INNER JOIN chapters ON chapters.id = user_chapter_progress.chapter_id
     WHERE user_chapter_progress.user_id = :user_id
       AND chapters.is_published = 1'
);
$completedLessonsStatement->execute(['user_id' => $userId]);
$completedLessons = (int) $completedLessonsStatement->fetchColumn();

$passedQuizzesStatement = $pdo->prepare(
    'SELECT COUNT(DISTINCT quiz_attempts.quiz_id)
     FROM quiz_attempts
     INNER JOIN quizzes ON quizzes.id = quiz_attempts.quiz_id
     WHERE quiz_attempts.user_id = :user_id
       AND quizzes.is_published = 1
       AND quiz_attempts.score_percentage >= quizzes.passing_score'
);
$passedQuizzesStatement->execute(['user_id' => $userId]);
$passedQuizzes = (int) $passedQuizzesStatement->fetchColumn();

$selectedTopics = [];
if (is_string($profile['target_topics']) && $profile['target_topics'] !== '') {
    $decodedTopics = json_decode($profile['target_topics'], true);
    if (is_array($decodedTopics)) {
        $selectedTopics = array_values(array_intersect($topics, $decodedTopics));
    }
}
$pageTitle = 'My Profile & Settings';
$csrfToken = learnify_csrf_token();
$avatarPath = is_string($profile['avatar_path']) ? $profile['avatar_path'] : '';
$avatarUrl = preg_match('~^uploads/avatars/[a-f0-9]{40}\.(?:jpg|png|webp)$~', $avatarPath)
    ? '../' . $avatarPath
    : '';
$_SESSION['user_avatar'] = $avatarUrl !== '' ? $avatarPath : null;
$avatarInitial = preg_match('/^./us', (string) $profile['name'], $initialMatch)
    ? strtoupper($initialMatch[0])
    : 'S';

require __DIR__ . '/../includes/header.php';
?>
<main class="section container-wide profile-page">
    <header class="section-heading profile-page-heading">
        <p class="eyebrow">Account settings</p>
        <h1>My Profile</h1>
        <p>Manage your personal details, account security, learning goals, and areas of interest.</p>
    </header>

    <?php if ($errors !== []): ?>
        <div class="form-alert form-alert-error profile-alert" role="alert">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= learnify_h($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php elseif ($successMessage !== ''): ?>
        <div class="form-alert form-alert-success profile-alert" role="status"><?= learnify_h($successMessage) ?></div>
    <?php endif; ?>

    <section class="profile-stats" aria-label="Learning statistics">
        <article class="glass-card profile-stat-card">
            <i class="fa-solid fa-book-open" aria-hidden="true"></i>
            <strong><?= $completedLessons ?></strong>
            <span>Lessons completed</span>
        </article>
        <article class="glass-card profile-stat-card">
            <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
            <strong><?= $passedQuizzes ?></strong>
            <span>Quizzes passed</span>
        </article>
        <article class="glass-card profile-stat-card">
            <i class="fa-solid fa-certificate" aria-hidden="true"></i>
            <strong><?= $certificateCount ?></strong>
            <span>Certificates earned</span>
        </article>
        <article class="glass-card profile-stat-card">
            <i class="fa-solid fa-fire" aria-hidden="true"></i>
            <strong><?= (int) $profile['current_streak'] ?></strong>
            <span>Day study streak</span>
        </article>
    </section>

    <div class="profile-sections">
        <section class="glass-card profile-panel" aria-labelledby="personal-details-title">
            <div class="profile-panel-heading">
                <span class="module-icon" aria-hidden="true"><i class="fa-solid fa-user"></i></span>
                <div><p class="eyebrow">Your account</p><h2 id="personal-details-title">Personal details</h2></div>
            </div>
            <form class="profile-form" method="post" enctype="multipart/form-data">
                <input type="hidden" name="_csrf_token" value="<?= learnify_h($csrfToken) ?>">
                <input type="hidden" name="profile_action" value="profile">
                <div class="profile-avatar-editor">
                    <div class="profile-avatar-preview" data-avatar-preview>
                        <?php if ($avatarUrl !== ''): ?>
                            <img src="<?= learnify_h($avatarUrl) ?>" alt="Your profile picture">
                        <?php else: ?>
                            <span><?= learnify_h($avatarInitial) ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="avatar">Profile picture</label>
                        <input class="input" id="avatar" name="avatar" type="file" accept="image/jpeg,image/png,image/webp">
                        <small class="text-muted">JPEG, PNG, or WebP · up to 5 MB.</small>
                        <?php if ($avatarUrl !== ''): ?>
                            <button class="button button-secondary profile-remove-avatar" type="submit" name="action" value="remove_avatar">
                                <i class="fa-solid fa-trash-can" aria-hidden="true"></i> Remove Picture
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="grid grid-2 profile-form-grid">
                    <div class="form-group">
                        <label class="form-label" for="full-name">Full name</label>
                        <input class="input" id="full-name" name="full_name" type="text" maxlength="120" autocomplete="name" value="<?= learnify_h($profile['name']) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="email">Email address</label>
                        <input class="input" id="email" name="email" type="email" maxlength="254" autocomplete="email" value="<?= learnify_h($profile['email']) ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label" for="bio">Bio / Headline</label>
                    <textarea class="textarea profile-bio-input" id="bio" name="bio" maxlength="1000" rows="4" placeholder="A short introduction or what you're working toward..."><?= learnify_h($profile['bio']) ?></textarea>
                    <small class="text-muted">Up to 1000 characters.</small>
                </div>
                <button class="button" type="submit"><i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save personal details</button>
            </form>
        </section>

        <section class="glass-card profile-panel" aria-labelledby="password-title">
            <div class="profile-panel-heading">
                <span class="module-icon" aria-hidden="true"><i class="fa-solid fa-shield-halved"></i></span>
                <div><p class="eyebrow">Keep your account safe</p><h2 id="password-title">Change password</h2></div>
            </div>
            <form class="profile-form" method="post">
                <input type="hidden" name="_csrf_token" value="<?= learnify_h($csrfToken) ?>">
                <input type="hidden" name="action" value="password">
                <div class="form-group">
                    <label class="form-label" for="current-password">Current password</label>
                    <input class="input" id="current-password" name="current_password" type="password" autocomplete="current-password" required>
                </div>
                <div class="grid grid-2 profile-form-grid">
                    <div class="form-group">
                        <label class="form-label" for="new-password">New password</label>
                        <input class="input" id="new-password" name="new_password" type="password" minlength="8" maxlength="72" autocomplete="new-password" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="confirm-password">Confirm new password</label>
                        <input class="input" id="confirm-password" name="confirm_password" type="password" minlength="8" maxlength="72" autocomplete="new-password" required>
                    </div>
                </div>
                <small class="text-muted">Use 8–72 characters. Your current password is required to confirm the change.</small>
                <button class="button" type="submit"><i class="fa-solid fa-key" aria-hidden="true"></i> Update password</button>
            </form>
        </section>

        <section class="glass-card profile-panel profile-panel-wide" aria-labelledby="goals-title">
            <div class="profile-panel-heading">
                <span class="module-icon" aria-hidden="true"><i class="fa-solid fa-bullseye"></i></span>
                <div><p class="eyebrow">Make learning a habit</p><h2 id="goals-title">Learning goals &amp; preferences</h2></div>
            </div>
            <form class="profile-form" method="post">
                <input type="hidden" name="_csrf_token" value="<?= learnify_h($csrfToken) ?>">
                <input type="hidden" name="action" value="preferences">
                <div class="grid grid-2 profile-form-grid">
                    <div class="form-group">
                        <label class="form-label" for="daily-study-minutes">Daily study target</label>
                        <select class="select" id="daily-study-minutes" name="daily_study_minutes">
                            <?php foreach ([5, 10, 15, 20, 30, 45, 60, 90, 120, 180, 240] as $minutes): ?>
                                <option value="<?= $minutes ?>" <?= (int) $profile['daily_study_minutes'] === $minutes ? 'selected' : '' ?>>
                                    <?= $minutes ?> minutes per day
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="weekly-module-target">Weekly module target</label>
                        <select class="select" id="weekly-module-target" name="weekly_module_target">
                            <?php for ($weeklyTarget = 1; $weeklyTarget <= 7; $weeklyTarget++): ?>
                                <option value="<?= $weeklyTarget ?>" <?= (int) $profile['weekly_module_target'] === $weeklyTarget ? 'selected' : '' ?>>
                                    <?= $weeklyTarget ?> <?= $weeklyTarget === 1 ? 'module' : 'modules' ?> per week
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                <fieldset class="profile-topic-fieldset">
                    <legend class="form-label">Primary technologies and topics</legend>
                    <p class="text-muted">Choose up to 8 areas you want to focus on.</p>
                    <div class="profile-topic-grid">
                        <?php foreach ($topics as $topic): ?>
                            <?php $topicId = 'topic-' . substr(hash('sha256', $topic), 0, 10); ?>
                            <label class="profile-topic-option" for="<?= $topicId ?>">
                                <input id="<?= $topicId ?>" type="checkbox" name="target_topics[]" value="<?= learnify_h($topic) ?>" <?= in_array($topic, $selectedTopics, true) ? 'checked' : '' ?>>
                                <span><?= learnify_h($topic) ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>
                <button class="button" type="submit"><i class="fa-solid fa-bullseye" aria-hidden="true"></i> Save learning goals</button>
            </form>
        </section>
    </div>
</main>
<script>
(() => {
    const input = document.querySelector('#avatar');
    const preview = document.querySelector('[data-avatar-preview]');
    if (!input || !preview) {
        return;
    }

    let previewUrl = null;
    input.addEventListener('change', () => {
        const file = input.files && input.files[0];
        if (!file) {
            return;
        }
        if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type) || file.size > 5 * 1024 * 1024) {
            input.setCustomValidity('Choose a JPEG, PNG, or WebP image smaller than 5 MB.');
            input.reportValidity();
            input.value = '';
            return;
        }
        input.setCustomValidity('');
        if (previewUrl) {
            URL.revokeObjectURL(previewUrl);
        }
        previewUrl = URL.createObjectURL(file);
        preview.replaceChildren();
        const image = document.createElement('img');
        image.src = previewUrl;
        image.alt = 'Selected profile picture preview';
        preview.append(image);
    });
})();
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
