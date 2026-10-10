<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../api/seed-lessons.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$seedingFailed = false;
try {
    learnify_seed_lessons($pdo);
} catch (Throwable $exception) {
    error_log('Learnify automatic quiz seeding failed: ' . $exception->getMessage());
    $seedingFailed = true;
}

$pageTitle = 'Take an exam';
$quizStatement = $pdo->query(
    'SELECT quizzes.id, quizzes.title, quizzes.module_id, modules.name AS module_name,
            (SELECT COUNT(*)
             FROM chapters
             WHERE chapters.module_id = modules.id AND chapters.is_published = 1) AS lesson_count,
            (SELECT COUNT(*)
              FROM questions
              INNER JOIN quizzes AS pool_quiz ON pool_quiz.id = questions.quiz_id
              WHERE pool_quiz.module_id = modules.id AND pool_quiz.is_published = 1) AS question_count
     FROM quizzes
     INNER JOIN modules ON modules.id = quizzes.module_id
     WHERE quizzes.is_published = 1 AND modules.is_published = 1
       AND EXISTS (
           SELECT 1
           FROM questions
           WHERE questions.quiz_id = quizzes.id
             AND questions.question_type IN (\'multiple_choice\', \'true_false\', \'short_answer\', \'mcq\', \'tf\', \'fill\')
       )
     ORDER BY modules.display_order, quizzes.title'
);
$quizzes = $quizStatement->fetchAll();
$requestedQuizId = filter_input(INPUT_GET, 'quiz_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$selectedQuizId = $requestedQuizId !== false && $requestedQuizId !== null ? (int) $requestedQuizId : 0;
$requestedModuleId = filter_input(INPUT_GET, 'module_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$selectedModuleId = $requestedModuleId !== false && $requestedModuleId !== null ? (int) $requestedModuleId : 0;
if ($selectedQuizId === 0 && $selectedModuleId > 0) {
    foreach ($quizzes as $quiz) {
        if ((int) $quiz['module_id'] === $selectedModuleId) {
            $selectedQuizId = (int) $quiz['id'];
            break;
        }
    }
}
$csrfToken = learnify_csrf_token();

require __DIR__ . '/../includes/header.php';
?>
<main class="exam-page container-wide">
    <div class="exam-page-heading">
        <a class="studio-back-link" href="dashboard.php">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Dashboard
        </a>
        <p class="eyebrow">Test your knowledge</p>
        <h1>Exam practice</h1>
        <p class="text-muted">Choose a published quiz and format. Each attempt draws a fresh random question batch, so you can retake exams whenever you like.</p>
    </div>

    <?php if ($seedingFailed): ?>
        <div class="form-alert form-alert-error" role="alert">
            Sample quiz pools could not be prepared. Please try again later or contact support.
        </div>
    <?php endif; ?>

    <section class="glass-card exam-setup" data-exam-setup aria-labelledby="exam-setup-heading">
        <div class="studio-panel-heading">
            <div>
                <p class="eyebrow">Get ready</p>
                <h2 id="exam-setup-heading">Choose your exam</h2>
            </div>
        </div>
        <?php if ($quizzes === []): ?>
            <div class="form-alert form-alert-error" role="status">
                No published quizzes are available yet.
            </div>
        <?php else: ?>
            <form class="exam-setup-form" data-exam-start-form>
                <input type="hidden" data-csrf-token value="<?= learnify_h($csrfToken) ?>">
                <div class="form-group">
                    <label class="form-label" for="exam-quiz">Quiz</label>
                    <select class="select" id="exam-quiz" data-quiz-select required>
                        <option value="">Select a quiz</option>
                        <?php foreach ($quizzes as $quiz): ?>
                            <option
                                value="<?= (int) $quiz['id'] ?>"
                                data-module-id="<?= (int) $quiz['module_id'] ?>"
                                <?= (int) $quiz['id'] === $selectedQuizId ? 'selected' : '' ?>
                            >
                                <?= learnify_h(
                                    $quiz['module_name'] . ' — ' . $quiz['title']
                                    . ' (' . (int) $quiz['lesson_count'] . ' lessons · '
                                    . (int) $quiz['question_count'] . ' questions)'
                                ) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <fieldset class="exam-mode-fieldset">
                    <legend class="form-label">Exam format</legend>
                    <label class="exam-mode-option">
                        <input type="radio" name="exam_mode" value="practice" checked>
                        <span>
                            <strong>Practice Quiz</strong>
                            <small>10 questions · no timer</small>
                        </span>
                    </label>
                    <label class="exam-mode-option">
                        <input type="radio" name="exam_mode" value="timed">
                        <span>
                            <strong>Timed Speed Test</strong>
                            <small>20 questions · 10 minutes</small>
                        </span>
                    </label>
                    <label class="exam-mode-option">
                        <input type="radio" name="exam_mode" value="certification">
                        <span>
                            <strong>Full Certification Exam</strong>
                            <small>50 questions · 60 minutes</small>
                        </span>
                    </label>
                </fieldset>
                <button class="button" type="submit">Start exam <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></button>
                <p class="exam-feedback text-muted" data-exam-feedback role="status"></p>
            </form>
        <?php endif; ?>
    </section>

    <section class="glass-card exam-session" data-exam-session hidden aria-labelledby="exam-title">
        <div class="exam-session-header">
            <div>
                <p class="eyebrow" data-exam-mode></p>
                <h2 id="exam-title" data-exam-title></h2>
                <p class="text-muted mb-0" data-exam-subtitle></p>
            </div>
            <div class="exam-timer" data-exam-timer hidden aria-live="off" role="timer" aria-label="Time remaining">
                <i class="fa-regular fa-clock" aria-hidden="true"></i>
                <span data-timer-text>00:00</span>
            </div>
        </div>
        <form data-exam-form>
            <div class="exam-questions" data-question-list></div>
            <div class="exam-submit-row">
                <p class="text-muted mb-0" data-question-progress></p>
                <button class="button" type="submit" data-submit-exam>Submit exam</button>
            </div>
        </form>
        <p class="exam-feedback text-muted" data-session-feedback role="status"></p>
    </section>

    <section class="glass-card exam-results" data-exam-results hidden aria-label="Quiz results">
        <div class="results-summary" data-results-summary></div>
        <div class="results-review" id="results-review" data-results-review hidden>
            <h2 id="results-heading">Answer breakdown</h2>
            <div class="results-review-filters" role="group" aria-label="Filter questions by result">
                <button class="button button-secondary is-active" type="button" data-review-filter="all" aria-pressed="true">All Questions</button>
                <button class="button button-secondary" type="button" data-review-filter="incorrect" aria-pressed="false">Incorrect Only</button>
                <button class="button button-secondary" type="button" data-review-filter="correct" aria-pressed="false">Correct Only</button>
            </div>
            <div class="results-list" data-results-list></div>
        </div>
        <div class="results-actions">
            <button class="button button-secondary" type="button" data-review-all aria-controls="results-review" aria-expanded="false" hidden>
                Review All Questions
            </button>
            <button class="button" type="button" data-exam-again>Try another exam</button>
        </div>
    </section>
</main>
<script>
(() => {
    const setup = document.querySelector('[data-exam-setup]');
    if (!setup) {
        return;
    }

    const startForm = setup.querySelector('[data-exam-start-form]');
    if (!startForm) {
        return;
    }
    const quizSelect = startForm.querySelector('[data-quiz-select]');
    const token = startForm.querySelector('[data-csrf-token]').value;
    const sessionSection = document.querySelector('[data-exam-session]');
    const resultsSection = document.querySelector('[data-exam-results]');
    const resultsReview = document.querySelector('[data-results-review]');
    const questionsContainer = document.querySelector('[data-question-list]');
    const reviewAllButton = document.querySelector('[data-review-all]');
    const reviewFilters = Array.from(document.querySelectorAll('[data-review-filter]'));
    const feedback = startForm.querySelector('[data-exam-feedback]');
    const sessionFeedback = document.querySelector('[data-session-feedback]');
    const timer = document.querySelector('[data-exam-timer]');
    const timerText = document.querySelector('[data-timer-text]');
    const submitButton = document.querySelector('[data-submit-exam]');
    let attempt = null;
    let countdownHandle = null;
    let submitting = false;

    const setFeedback = (target, message, isError = false) => {
        target.textContent = message;
        target.classList.toggle('is-error', isError);
    };

    const appendFormattedText = (container, source) => {
        const pattern = /```([A-Za-z0-9_+#.-]*)\s*\n([\s\S]*?)```|`([^`\n]+)`/g;
        let lastIndex = 0;
        let match;

        while ((match = pattern.exec(source)) !== null) {
            if (match.index > lastIndex) {
                container.append(document.createTextNode(source.slice(lastIndex, match.index)));
            }
            if (match[2] !== undefined) {
                const pre = document.createElement('pre');
                const code = document.createElement('code');
                if (match[1]) {
                    code.className = `language-${match[1]}`;
                }
                code.textContent = match[2];
                pre.append(code);
                container.append(pre);
            } else {
                const code = document.createElement('code');
                code.className = 'inline-code';
                code.textContent = match[3];
                container.append(code);
            }
            lastIndex = pattern.lastIndex;
        }
        if (lastIndex < source.length) {
            container.append(document.createTextNode(source.slice(lastIndex)));
        }
    };

    const renderQuestions = (questions) => {
        questionsContainer.replaceChildren();
        questions.forEach((question, index) => {
            const card = document.createElement('fieldset');
            card.className = 'exam-question glass-card';
            card.dataset.questionId = String(question.id);

            const legend = document.createElement('legend');
            legend.className = 'exam-question-legend';
            const number = document.createElement('span');
            number.className = 'question-number';
            number.textContent = String(index + 1).padStart(2, '0');
            const prompt = document.createElement('span');
            prompt.className = 'exam-question-prompt';
            appendFormattedText(prompt, question.text);
            legend.append(number, prompt);
            card.append(legend);

            const type = question.type;
            if (type === 'fill' || type === 'short_answer') {
                const answerLabel = document.createElement('label');
                answerLabel.className = 'form-label exam-fill-label';
                answerLabel.textContent = 'Your answer';
                const answerInput = document.createElement('input');
                answerInput.className = 'input exam-fill-input';
                answerInput.type = 'text';
                answerInput.name = `answer-${question.id}`;
                answerInput.maxLength = 2000;
                answerInput.autocomplete = 'off';
                answerInput.dataset.answerFor = String(question.id);
                answerInput.dataset.freeResponse = 'true';
                answerLabel.append(answerInput);
                card.append(answerLabel);
            } else {
                const options = document.createElement('div');
                options.className = 'exam-options';
                const isTrueFalse = type === 'tf' || type === 'true_false';
                if (isTrueFalse) {
                    const answerInput = document.createElement('input');
                    answerInput.type = 'hidden';
                    answerInput.dataset.answerFor = String(question.id);
                    options.append(answerInput);
                }
                question.options.forEach((option, optionIndex) => {
                    if (isTrueFalse) {
                        const button = document.createElement('button');
                        button.className = 'exam-option exam-choice-button';
                        button.type = 'button';
                        button.textContent = option.label;
                        button.setAttribute('aria-pressed', 'false');
                        button.addEventListener('click', () => {
                            const answerInput = options.querySelector('input[data-answer-for]');
                            answerInput.value = option.value;
                            options.querySelectorAll('button').forEach((choice) => {
                                const selected = choice === button;
                                choice.classList.toggle('is-selected', selected);
                                choice.setAttribute('aria-pressed', String(selected));
                            });
                        });
                        options.append(button);
                        return;
                    }

                    const label = document.createElement('label');
                    label.className = 'exam-option';
                    const input = document.createElement('input');
                    input.type = 'radio';
                    input.name = `answer-${question.id}`;
                    input.value = option.value;
                    input.dataset.answerFor = String(question.id);
                    const optionKey = document.createElement('span');
                    optionKey.className = 'option-key';
                    optionKey.textContent = String.fromCharCode(65 + optionIndex);
                    const optionText = document.createElement('span');
                    optionText.className = 'option-label';
                    appendFormattedText(optionText, option.label);
                    label.append(input, optionKey, optionText);
                    options.append(label);
                });
                card.append(options);
            }
            questionsContainer.append(card);
        });

        const progress = document.querySelector('[data-question-progress]');
        progress.textContent = `${questions.length} questions · unanswered questions count as incorrect`;
    };

    const submitExam = async (automatic = false) => {
        if (!attempt || submitting) {
            return;
        }
        submitting = true;
        window.clearInterval(countdownHandle);
        submitButton.disabled = true;
        submitButton.textContent = automatic ? 'Time is up — submitting...' : 'Submitting...';
        setFeedback(sessionFeedback, automatic ? 'Time expired. Your answers are being submitted.' : 'Submitting your answers...');

        const answers = {};
        questionsContainer.querySelectorAll('input[data-answer-for]:checked').forEach((input) => {
            answers[input.dataset.answerFor] = input.value;
        });
        questionsContainer.querySelectorAll('input[data-free-response][data-answer-for]').forEach((input) => {
            if (input.value.trim() !== '') {
                answers[input.dataset.answerFor] = input.value;
            }
        });
        questionsContainer.querySelectorAll('input[type="hidden"][data-answer-for]').forEach((input) => {
            if (input.value !== '') {
                answers[input.dataset.answerFor] = input.value;
            }
        });

        try {
            const response = await fetch('../api/submit-quiz.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': token
                },
                body: JSON.stringify({
                    attempt_id: attempt.attempt_id,
                    answers,
                    timed_out: automatic
                })
            });
            const result = await response.json();
            if (!response.ok || result.success !== true) {
                throw new Error(result.message || 'Your exam could not be submitted.');
            }
            window.clearInterval(countdownHandle);
            sessionSection.hidden = true;
            setup.hidden = true;
            renderResults(result);
        } catch (error) {
            submitting = false;
            submitButton.disabled = false;
            submitButton.textContent = attempt.deadline !== null && performance.now() >= attempt.deadline
                ? 'Retry submission'
                : 'Submit exam';
            setFeedback(sessionFeedback, error.message || 'Submission failed. Please try again.', true);
            if (attempt.deadline !== null && performance.now() < attempt.deadline) {
                startCountdown();
            }
        }
    };

    const startCountdown = () => {
        if (!attempt || attempt.duration_seconds === null) {
            return;
        }
        timer.hidden = false;
        const tick = () => {
            const remaining = Math.max(0, Math.ceil((attempt.deadline - performance.now()) / 1000));
            const minutes = Math.floor(remaining / 60);
            const seconds = remaining % 60;
            timerText.textContent = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
            timer.classList.toggle('is-urgent', remaining <= 60);
            if (remaining === 0) {
                submitExam(true);
            }
        };
        tick();
        countdownHandle = window.setInterval(tick, 1000);
    };

    const renderResults = (result) => {
        const summary = document.querySelector('[data-results-summary]');
        const list = document.querySelector('[data-results-list]');
        summary.replaceChildren();
        list.replaceChildren();
        const correctCount = result.review.filter((item) => item.is_correct).length;
        const incorrectCount = result.review.length - correctCount;
        const icon = document.createElement('span');
        icon.className = 'results-icon';
        icon.innerHTML = '<i class="fa-solid fa-chart-simple" aria-hidden="true"></i>';
        const title = document.createElement('p');
        title.className = 'eyebrow';
        title.textContent = result.mode_label;
        const heading = document.createElement('h1');
        heading.textContent = `${result.percentage}%`;
        const detail = document.createElement('p');
        detail.className = 'text-muted';
        detail.textContent = `${result.correct_count} correct out of ${result.question_count} · ${result.passed ? 'Passing score achieved' : `Passing score: ${result.passing_score}%`}`;
        const reviewNotice = document.createElement('p');
        reviewNotice.className = 'text-muted';
        reviewNotice.textContent = incorrectCount === 0
            ? 'Excellent work — you answered every question correctly.'
            : `${correctCount} correct · ${incorrectCount} incorrect. Review every answer below.`;
        summary.append(icon, title, heading, detail, reviewNotice);

        result.review.forEach((item, index) => {
            const card = document.createElement('article');
            card.className = `result-item glass-card ${item.is_correct ? 'is-correct' : 'is-incorrect'}`;
            card.dataset.reviewResult = item.is_correct ? 'correct' : 'incorrect';
            const status = document.createElement('span');
            status.className = 'result-status';
            status.textContent = item.is_correct ? '✓ Correct' : '✕ Incorrect';
            const prompt = document.createElement('h3');
            prompt.textContent = `${item.question_number ?? index + 1}. ${item.question}`;
            const yourAnswer = document.createElement('p');
            const hasSubmittedAnswer = typeof item.submitted_answer === 'string' && item.submitted_answer !== '';
            yourAnswer.className = item.is_correct ? 'answer-correct' : 'answer-selected-incorrect';
            yourAnswer.textContent = item.is_correct
                ? `Your answer (correct): ${item.submitted_answer ?? item.correct_answer}`
                : `Your answer: ${hasSubmittedAnswer ? item.submitted_answer : 'Not answered'}`;
            card.append(status, prompt, yourAnswer);
            if (!item.is_correct) {
                const expected = document.createElement('p');
                expected.className = 'correct-answer';
                expected.textContent = `Correct answer: ${item.correct_answer}`;
                card.append(expected);
            }
            if (item.explanation) {
                const explanation = document.createElement('p');
                explanation.className = 'text-muted result-explanation';
                explanation.textContent = item.explanation;
                card.append(explanation);
            }
            list.append(card);
        });
        reviewAllButton.hidden = result.review.length === 0;
        reviewAllButton.textContent = 'Review All Questions';
        reviewAllButton.setAttribute('aria-expanded', 'false');
        reviewFilters.forEach((filter) => {
            filter.classList.toggle('is-active', filter.dataset.reviewFilter === 'all');
            filter.setAttribute('aria-pressed', String(filter.dataset.reviewFilter === 'all'));
            filter.hidden = filter.dataset.reviewFilter !== 'all'
                && (filter.dataset.reviewFilter === 'correct' ? correctCount === 0 : incorrectCount === 0);
        });
        resultsReview.hidden = true;
        resultsSection.hidden = false;
        resultsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
    };

    const highlightCode = () => {
        if (window.hljs) {
            document.querySelectorAll('[data-question-list] pre code').forEach((block) => {
                window.hljs.highlightElement(block);
            });
        }
    };

    const syntaxCss = document.createElement('link');
    syntaxCss.rel = 'stylesheet';
    syntaxCss.href = 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/styles/github-dark.min.css';
    document.head.append(syntaxCss);
    const syntaxScript = document.createElement('script');
    syntaxScript.src = 'https://cdnjs.cloudflare.com/ajax/libs/highlight.js/11.9.0/highlight.min.js';
    syntaxScript.defer = true;
    syntaxScript.onload = highlightCode;
    document.head.append(syntaxScript);

    startForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const selectedOption = quizSelect.selectedOptions[0];
        const mode = startForm.querySelector('input[name="exam_mode"]:checked')?.value;
        if (!selectedOption?.value || !mode) {
            setFeedback(feedback, 'Select a quiz and exam format to continue.', true);
            return;
        }
        const startButton = startForm.querySelector('button[type="submit"]');
        startButton.disabled = true;
        setFeedback(feedback, 'Loading a random question batch...');

        try {
            const response = await fetch('../api/fetch-questions.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': token
                },
                body: JSON.stringify({
                    quiz_id: Number(selectedOption.value),
                    module_id: Number(selectedOption.dataset.moduleId),
                    mode
                })
            });
            const result = await response.json();
            if (!response.ok || result.success !== true) {
                throw new Error(result.message || 'The exam could not be started.');
            }
            attempt = result;
            attempt.deadline = result.expires_at === null
                ? null
                : performance.now() + Math.max(0, (Number(result.expires_at) - Number(result.server_time)) * 1000);
            document.querySelector('[data-exam-mode]').textContent = result.mode_label;
            document.querySelector('[data-exam-title]').textContent = result.quiz_title;
            document.querySelector('[data-exam-subtitle]').textContent =
                `${result.module_name} · ${result.questions.length} questions`;
            renderQuestions(result.questions);
            setup.hidden = true;
            sessionSection.hidden = false;
            sessionSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            startCountdown();
            highlightCode();
        } catch (error) {
            setFeedback(feedback, error.message || 'The exam could not be started.', true);
        } finally {
            startButton.disabled = false;
        }
    });

    document.querySelector('[data-exam-form]').addEventListener('submit', (event) => {
        event.preventDefault();
        submitExam(false);
    });

    document.querySelector('[data-exam-again]').addEventListener('click', () => {
        window.location.reload();
    });

    reviewAllButton.addEventListener('click', () => {
        const isExpanded = reviewAllButton.getAttribute('aria-expanded') === 'true';
        resultsReview.hidden = isExpanded;
        reviewAllButton.setAttribute('aria-expanded', String(!isExpanded));
        reviewAllButton.textContent = isExpanded ? 'Review All Questions' : 'Hide Questions';
    });

    reviewFilters.forEach((filter) => {
        filter.addEventListener('click', () => {
            const selectedFilter = filter.dataset.reviewFilter;
            document.querySelectorAll('[data-results-list] .result-item').forEach((item) => {
                item.hidden = selectedFilter !== 'all' && item.dataset.reviewResult !== selectedFilter;
            });
            reviewFilters.forEach((button) => {
                const selected = button === filter;
                button.classList.toggle('is-active', selected);
                button.setAttribute('aria-pressed', String(selected));
            });
        });
    });
})();
</script>
<?php require __DIR__ . '/../includes/footer.php'; ?>
