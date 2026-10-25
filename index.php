<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';

$pageTitle = 'Learn technology with confidence';
$isAuthenticated = isset($_SESSION['user_id']);
$modules = [];

if ($isAuthenticated) {
    $moduleStatement = $pdo->query(
        'SELECT id, name, category, description, icon
         FROM modules
         WHERE is_published = 1
         ORDER BY display_order, name
         LIMIT 6'
    );
    $modules = $moduleStatement->fetchAll();
}

$previewModules = [
    [
        'name' => 'Computer Basics',
        'category' => 'Foundations',
        'description' => 'Get comfortable with computer systems, operating systems, files, and essential digital skills.',
        'icon' => 'fa-solid fa-computer',
    ],
    [
        'name' => 'Programming Logic',
        'category' => 'Programming',
        'description' => 'Build problem-solving confidence with variables, decisions, loops, and clear program design.',
        'icon' => 'fa-solid fa-diagram-project',
    ],
    [
        'name' => 'Website Design',
        'category' => 'Web Development',
        'description' => 'Create responsive, accessible web pages with modern HTML and CSS fundamentals.',
        'icon' => 'fa-solid fa-palette',
    ],
    [
        'name' => 'JavaScript Basics',
        'category' => 'Web Development',
        'description' => 'Bring web pages to life with JavaScript, browser events, functions, and APIs.',
        'icon' => 'fa-brands fa-js',
    ],
    [
        'name' => 'Python Programming',
        'category' => 'Programming',
        'description' => 'Learn Python step by step, from core syntax to practical mini-projects.',
        'icon' => 'fa-brands fa-python',
    ],
    [
        'name' => 'Databases & SQL',
        'category' => 'Data',
        'description' => 'Organize information, design relational data, and query it with practical SQL.',
        'icon' => 'fa-solid fa-database',
    ],
];

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#120B29">
    <meta name="description" content="Build computer science and software engineering skills with Learnify's 20 guided modules, smart exam practice, study studio, and AI assistant.">
    <title>Learnify — Adaptive Learning &amp; Intelligent Examination Platform</title>
    <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<header class="site-header">
    <nav class="navbar container-wide landing-navbar" aria-label="Main navigation">
        <a class="brand" href="index.php" aria-label="Learnify home">
            <span class="brand-mark" aria-hidden="true"><i class="fa-solid fa-graduation-cap"></i></span>
            <span class="text-gradient">Learnify</span>
        </a>
        <div class="landing-nav-links">
            <a href="#features">Features</a>
            <a href="#modules">20 Modules</a>
            <a href="#exam-engine">Exam Engine</a>
            <a href="#about">About</a>
        </div>
        <div class="landing-nav-actions">
            <a class="landing-login-link" href="login.php">Log In</a>
            <a class="button landing-nav-cta" href="register.php">Get Started</a>
        </div>
        <button class="landing-menu-toggle" type="button" data-landing-menu-toggle aria-expanded="false" aria-controls="landing-mobile-menu" aria-label="Open navigation menu">
            <i class="fa-solid fa-bars" aria-hidden="true"></i>
        </button>
    </nav>
    <div class="landing-mobile-menu" id="landing-mobile-menu" data-landing-mobile-menu hidden>
        <a href="#features">Features</a>
        <a href="#modules">20 Modules</a>
        <a href="#exam-engine">Exam Engine</a>
        <a href="#about">About</a>
        <a href="login.php">Log In</a>
        <a class="button" href="register.php">Get Started</a>
    </div>
</header>
<main>
    <section class="landing-hero">
        <div class="landing-hero-orb landing-hero-orb-one" aria-hidden="true"></div>
        <div class="landing-hero-orb landing-hero-orb-two" aria-hidden="true"></div>
        <div class="container landing-hero-layout">
            <div class="landing-hero-copy">
                <span class="landing-kicker">
                    <i class="fa-solid fa-sparkles" aria-hidden="true"></i>
                    Your next chapter starts here
                </span>
                <h1>Master CS &amp; Software Engineering with <span class="text-gradient">AI-Powered Precision</span></h1>
                <p class="landing-hero-subtitle">
                    Learn in focused steps, practice with smarter exams, and keep every key idea close.
                    A clearer path from curious beginner to confident builder.
                </p>
                <div class="landing-hero-actions">
                    <a class="button landing-button-primary" href="#modules">
                        Explore Modules <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                    </a>
                    <a class="button button-secondary landing-button-secondary" href="register.php">
                        Start Free Trial <i class="fa-solid fa-sparkles" aria-hidden="true"></i>
                    </a>
                </div>
                <div class="landing-proof-row" aria-label="Learnify highlights">
                    <span><i class="fa-solid fa-circle-check" aria-hidden="true"></i> 20 guided learning paths</span>
                    <span><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Learn at your own pace</span>
                    <span><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Practice as you go</span>
                </div>
            </div>

            <div class="landing-hero-visual" aria-label="Learnify learning dashboard preview">
                <div class="hero-glass-window glass-card">
                    <div class="hero-window-top">
                        <div class="hero-window-dots" aria-hidden="true"><span></span><span></span><span></span></div>
                        <span>LEARNIFY / YOUR LEARNING SPACE</span>
                        <i class="fa-solid fa-ellipsis" aria-hidden="true"></i>
                    </div>
                    <div class="hero-window-body">
                        <div class="hero-window-welcome">
                            <div>
                                <span class="eyebrow">A little progress, every day</span>
                                <h2>Learn with momentum.</h2>
                            </div>
                            <span class="hero-window-icon"><i class="fa-solid fa-graduation-cap" aria-hidden="true"></i></span>
                        </div>
                        <div class="hero-progress-card">
                            <div class="flex items-center justify-between gap-2">
                                <span>Learning paths</span>
                                <strong>20</strong>
                            </div>
                            <div class="hero-progress-track"><span></span></div>
                            <small>Choose a topic and make it yours.</small>
                        </div>
                        <div class="hero-mini-grid">
                            <div class="hero-mini-card">
                                <span class="module-icon"><i class="fa-solid fa-code" aria-hidden="true"></i></span>
                                <span><strong>Build skills</strong><small>Clear, focused modules</small></span>
                            </div>
                            <div class="hero-mini-card">
                                <span class="module-icon"><i class="fa-solid fa-bolt" aria-hidden="true"></i></span>
                                <span><strong>Practice smart</strong><small>Exams that keep you sharp</small></span>
                            </div>
                        </div>
                    </div>
                    <div class="hero-floating-chip">
                        <span class="hero-chip-spark"><i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i></span>
                        <span><strong>AI learning companion</strong><small>Help when you need it</small></span>
                        <span class="hero-online-dot" aria-label="Available"></span>
                    </div>
                </div>
            </div>
        </div>
        <a class="hero-scroll-cue" href="#features" aria-label="Scroll to Learnify features">
            <i class="fa-solid fa-arrow-down" aria-hidden="true"></i>
        </a>
    </section>

    <section class="section landing-features-section" id="features" aria-labelledby="features-heading">
        <div class="container">
            <div class="section-heading landing-section-heading">
                <p class="eyebrow">Made for how you learn</p>
                <h2 id="features-heading">Everything you need to <span class="text-gradient">keep moving forward</span></h2>
                <p>Learnify brings learning, revision, and practice together in one focused workspace.</p>
            </div>
            <div class="grid grid-4 landing-feature-grid">
                <article class="glass-card card landing-feature-card">
                    <span class="landing-feature-icon"><i class="fa-solid fa-layer-group" aria-hidden="true"></i></span>
                    <span class="landing-feature-number">01 / LEARN</span>
                    <h3>20 Interactive Modules</h3>
                    <p>Explore essential computer science and software engineering topics with clear, approachable paths.</p>
                    <a href="#modules" aria-label="Explore the 20 modules">Explore the paths <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                </article>
                <article class="glass-card card landing-feature-card">
                    <span class="landing-feature-icon"><i class="fa-solid fa-stopwatch" aria-hidden="true"></i></span>
                    <span class="landing-feature-number">02 / PRACTICE</span>
                    <h3>Non-Repeating Exam Simulator</h3>
                    <p>Check your understanding with practice quizzes, timed tests, and certification-style exam sessions.</p>
                    <a href="#exam-engine" aria-label="Learn about the exam engine">See how it works <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                </article>
                <article class="glass-card card landing-feature-card">
                    <span class="landing-feature-icon"><i class="fa-solid fa-table-columns" aria-hidden="true"></i></span>
                    <span class="landing-feature-number">03 / FOCUS</span>
                    <h3>Split-Screen Study Studio</h3>
                    <p>Keep chapter resources, your outline, quick-review notes, and personal notes in one study space.</p>
                    <a href="register.php">Step into the studio <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                </article>
                <article class="glass-card card landing-feature-card">
                    <span class="landing-feature-icon"><i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i></span>
                    <span class="landing-feature-number">04 / GET SUPPORT</span>
                    <h3>24/7 AI Portal Assistant</h3>
                    <p>Get a helpful guide to Learnify features, study tools, exam practice, and learning modules.</p>
                    <a href="register.php">Meet your study companion <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></a>
                </article>
            </div>
        </div>
    </section>

    <section class="section landing-modules-section" id="modules" aria-labelledby="modules-heading">
        <div class="container">
            <div class="landing-modules-heading">
                <div class="section-heading">
                    <p class="eyebrow">Start with a subject you love</p>
                    <h2 id="modules-heading">A world of skills, <span class="text-gradient">one module at a time</span></h2>
                    <p>Explore a few of the learning paths waiting for you inside Learnify.</p>
                </div>
                <a class="text-link" href="<?= $isAuthenticated ? 'student/dashboard.php' : 'register.php' ?>">
                    View all 20 modules <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </a>
            </div>

            <?php if ($isAuthenticated && $modules === []): ?>
                <div class="glass-card card landing-module-notice" role="status">
                    Published modules are not available right now. Please check back soon.
                </div>
            <?php else: ?>
                <?php $displayModules = $isAuthenticated ? $modules : $previewModules; ?>
                <div class="grid grid-3 landing-module-grid">
                    <?php foreach ($displayModules as $module): ?>
                        <article class="glass-card card landing-module-card">
                            <div class="landing-module-topline">
                                <span class="module-icon" aria-hidden="true">
                                    <i class="<?= htmlspecialchars((string) $module['icon'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"></i>
                                </span>
                                <span class="badge"><?= htmlspecialchars((string) $module['category'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></span>
                            </div>
                            <h3><?= htmlspecialchars((string) $module['name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></h3>
                            <p class="text-muted"><?= htmlspecialchars((string) $module['description'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></p>
                            <a class="landing-module-link" href="<?= $isAuthenticated
                                ? 'student/module.php?module_id=' . (int) $module['id']
                                : 'register.php' ?>">
                                <?= $isAuthenticated ? 'Start learning' : 'Unlock this module' ?>
                                <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                            </a>
                        </article>
                    <?php endforeach; ?>
                </div>
                <?php if (!$isAuthenticated): ?>
                    <p class="landing-preview-note">A preview of Learnify’s 20 learning paths. Create a free account to get started.</p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>

    <section class="section landing-exam-section" id="exam-engine" aria-labelledby="exam-heading">
        <div class="container">
            <div class="landing-exam-card glass-card">
                <div class="landing-exam-content">
                    <span class="landing-kicker"><i class="fa-solid fa-bolt" aria-hidden="true"></i> Practice that moves you forward</span>
                    <h2 id="exam-heading">Turn what you’ve learned into <span class="text-gradient">real confidence.</span></h2>
                    <p>Use focused practice, beat the clock in a speed test, or prepare with a full certification-style exam. Review your answers and keep building.</p>
                    <a class="button landing-button-primary" href="<?= $isAuthenticated ? 'student/take-exam.php' : 'register.php' ?>">
                        <?= $isAuthenticated ? 'Go to the Exam Engine' : 'Start practicing for free' ?>
                        <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                    </a>
                </div>
                <div class="landing-exam-art" aria-hidden="true">
                    <div class="exam-art-orbit orbit-one"></div>
                    <div class="exam-art-orbit orbit-two"></div>
                    <div class="exam-art-center"><i class="fa-solid fa-brain"></i></div>
                    <span class="exam-art-chip chip-top"><i class="fa-solid fa-check"></i> Review</span>
                    <span class="exam-art-chip chip-bottom"><i class="fa-solid fa-stopwatch"></i> Focus</span>
                </div>
            </div>
        </div>
    </section>

    <section class="section landing-about-section" id="about" aria-labelledby="about-heading">
        <div class="container landing-about-content">
            <div>
                <p class="eyebrow">A clearer way to grow</p>
                <h2 id="about-heading">Big goals feel closer when the next step is <span class="text-gradient">clear.</span></h2>
            </div>
            <div>
                <p class="text-muted">Learnify gives curious learners a welcoming place to build technical foundations, revisit important ideas, and put knowledge into practice—without losing sight of what comes next.</p>
                <a class="button button-secondary" href="register.php">
                    Find your next step <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                </a>
            </div>
        </div>
    </section>
</main>
<script>
(() => {
    const toggle = document.querySelector('[data-landing-menu-toggle]');
    const menu = document.querySelector('[data-landing-mobile-menu]');
    if (!toggle || !menu) {
        return;
    }
    toggle.addEventListener('click', () => {
        const isOpen = menu.hidden;
        menu.hidden = !isOpen;
        toggle.setAttribute('aria-expanded', String(isOpen));
        toggle.setAttribute('aria-label', isOpen ? 'Close navigation menu' : 'Open navigation menu');
        toggle.innerHTML = isOpen
            ? '<i class="fa-solid fa-xmark" aria-hidden="true"></i>'
            : '<i class="fa-solid fa-bars" aria-hidden="true"></i>';
    });
    menu.querySelectorAll('a[href^="#"]').forEach((link) => {
        link.addEventListener('click', () => {
            menu.hidden = true;
            toggle.setAttribute('aria-expanded', 'false');
            toggle.setAttribute('aria-label', 'Open navigation menu');
            toggle.innerHTML = '<i class="fa-solid fa-bars" aria-hidden="true"></i>';
        });
    });
})();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>
