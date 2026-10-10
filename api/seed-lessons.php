<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/seed-quizzes.php';

function learnify_lesson_example(string $moduleSlug, string $term, string $definition, int $index): array
{
    $examples = [
        'computer-basics' => [
            '$activeProgram = "editor";' . "\n" . '$workingMemory = ["editor", "document"];' . "\n" . '$savedFile = "notes.txt";',
            'if ($availableRam < $requiredRam) {' . "\n" . '    closeUnusedPrograms();' . "\n" . '}',
        ],
        'programming-logic' => [
            '$total = $price * $quantity;' . "\n" . 'if ($total <= $budget) {' . "\n" . '    placeOrder();' . "\n" . '}',
            'foreach ($items as $item) {' . "\n" . '    if (isValid($item)) {' . "\n" . '        process($item);' . "\n" . '    }' . "\n" . '}',
        ],
        'easy-cpp-java' => [
            'int total = price * quantity;' . "\n" . 'if (total <= budget) {' . "\n" . '    placeOrder();' . "\n" . '}',
            'for (int i = 0; i < values.length; i++) {' . "\n" . '    System.out.println(values[i]);' . "\n" . '}',
        ],
        'easy-csharp-programming' => [
            'var total = price * quantity;' . "\n" . 'if (total <= budget)' . "\n" . '{' . "\n" . '    PlaceOrder();' . "\n" . '}',
            'foreach (var item in items)' . "\n" . '{' . "\n" . '    if (IsValid(item)) Process(item);' . "\n" . '}',
        ],
        'object-oriented-coding' => [
            'public sealed class Account' . "\n" . '{' . "\n" . '    private decimal balance;' . "\n" . '    public void Deposit(decimal amount) => balance += amount;' . "\n" . '}',
            'interface INotifier' . "\n" . '{' . "\n" . '    void Send(string message);' . "\n" . '}' . "\n" . 'notifier.Send(message);',
        ],
        'data-structures-made-easy' => [
            '$queue = new SplQueue();' . "\n" . '$queue->enqueue($request);' . "\n" . '$next = $queue->dequeue();',
            '$byId = [];' . "\n" . '$byId[$record["id"]] = $record;' . "\n" . '$record = $byId[$requestedId] ?? null;',
        ],
        'algorithms-logic' => [
            '$low = 0;' . "\n" . '$high = count($values) - 1;' . "\n" . 'while ($low <= $high) {' . "\n" . '    $middle = intdiv($low + $high, 2);' . "\n" . '}',
            'foreach ($values as $value) {' . "\n" . '    if ($value === $target) return $value;' . "\n" . '}' . "\n" . 'return null;',
        ],
        'website-design' => [
            '<main>' . "\n" . '  <h1>Course overview</h1>' . "\n" . '  <button type="button">Continue</button>' . "\n" . '</main>',
            '.cards {' . "\n" . '  display: grid;' . "\n" . '  grid-template-columns: repeat(auto-fit, minmax(16rem, 1fr));' . "\n" . '}',
        ],
        'javascript-basics' => [
            'const total = price * quantity;' . "\n" . 'if (total <= budget) {' . "\n" . '  placeOrder();' . "\n" . '}',
            'button.addEventListener("click", async () => {' . "\n" . '  await saveChanges();' . "\n" . '});',
        ],
        'modern-web-apps' => [
            'const response = await fetch("/api/items");' . "\n" . 'if (!response.ok) throw new Error("Request failed");' . "\n" . 'const items = await response.json();',
            'POST /api/orders' . "\n" . 'Content-Type: application/json' . "\n" . '' . "\n" . '{"item_id": 42}',
        ],
        'php-web-development' => [
            '$statement = $pdo->prepare(' . "\n" . '    "SELECT id FROM users WHERE email = :email"' . "\n" . ');' . "\n" . '$statement->execute(["email" => $email]);',
            'if (!hash_equals($_SESSION["csrf"], $submittedToken)) {' . "\n" . '    http_response_code(403);' . "\n" . '    exit;' . "\n" . '}',
        ],
        'aspnet-core-mvc' => [
            '[HttpGet("/courses/{id:int}")]' . "\n" . 'public IActionResult Details(int id)' . "\n" . '{' . "\n" . '    return View(repository.Find(id));' . "\n" . '}',
            'if (!ModelState.IsValid)' . "\n" . '{' . "\n" . '    return View(model);' . "\n" . '}',
        ],
        'databases-sql' => [
            'SELECT customers.name, orders.id' . "\n" . 'FROM customers' . "\n" . 'JOIN orders ON orders.customer_id = customers.id' . "\n" . 'WHERE orders.status = :status;',
            'BEGIN;' . "\n" . 'UPDATE inventory SET quantity = quantity - 1 WHERE id = :id;' . "\n" . 'COMMIT;',
        ],
        'python-programming' => [
            'def calculate_total(price, quantity):' . "\n" . '    return price * quantity' . "\n" . '' . "\n" . 'total = calculate_total(5, 3)',
            'with open("notes.txt", encoding="utf-8") as file:' . "\n" . '    text = file.read()',
        ],
        'artificial-intelligence' => [
            'train(model, training_data)' . "\n" . 'validate(model, validation_data)' . "\n" . 'report_metrics(model, held_out_test_data)',
            'prediction = model.predict(example)' . "\n" . 'if needs_review(prediction):' . "\n" . '    request_human_review(prediction)',
        ],
        'data-science' => [
            'clean = frame.drop_duplicates()' . "\n" . 'median = clean["duration"].median()' . "\n" . 'plot_histogram(clean["duration"])',
            'train, test = split(data, test_size=0.2, random_state=7)' . "\n" . 'model.fit(train.features, train.labels)' . "\n" . 'evaluate(model, test)',
        ],
        'software-testing' => [
            'def test_rejects_negative_quantity():' . "\n" . '    result = checkout(quantity=-1)' . "\n" . '    assert result.error == "invalid quantity"',
            'arrange_test_data()' . "\n" . '$result = $service->run($input);' . "\n" . 'assertSame($expected, $result);',
        ],
        'project-management' => [
            '$work = ["design", "build", "verify"];' . "\n" . 'foreach ($work as $task) {' . "\n" . '    assignOwner($task);' . "\n" . '    trackDependency($task);' . "\n" . '}',
            '$risk = ["likelihood" => 3, "impact" => 4];' . "\n" . '$priority = $risk["likelihood"] * $risk["impact"];',
        ],
        'certification-test' => [
            '$objectives = readExamBlueprint();' . "\n" . '$gaps = selfAssess($objectives);' . "\n" . 'scheduleSpacedReview($gaps);',
            '$deadline = timeLimit();' . "\n" . 'answerKnownQuestions();' . "\n" . 'reviewFlaggedQuestions($deadline);',
        ],
        'job-interview-prep' => [
            '$example = ["situation", "task", "action", "result"];' . "\n" . 'explainInOrder($example);',
            '$cases = [normalInput(), boundaryInput(), invalidInput()];' . "\n" . 'foreach ($cases as $case) testSolution($case);',
        ],
    ];

    $conceptExample = learnify_quiz_code_example($moduleSlug, $term);
    $moduleExamples = $examples[$moduleSlug] ?? [];
    if ($conceptExample !== null) {
        $code = $conceptExample;
    } elseif ($moduleExamples !== []) {
        $code = $moduleExamples[$index % count($moduleExamples)];
    } else {
        $code = 'concept = "' . addslashes($term) . '"' . "\n"
            . 'requirement = "' . addslashes($definition) . '"' . "\n"
            . 'verify(concept, requirement)';
    }

    return [
        'code' => $code,
        'breakdown' => [
            'The first statement establishes the example inputs or operation.',
            'The following expression applies a rule and makes its result observable.',
            'In production, validate external values and verify the result with a focused test.',
        ],
    ];
}

function learnify_lesson_video_for_topic(string $moduleSlug, string $moduleName, string $title): string
{
    $module = strtolower($moduleSlug . ' ' . $moduleName);
    $topic = strtolower($title);
    $videos = [
        'cpu' => 'https://www.youtube.com/embed/FZGugFqdr60',
        'ram' => 'https://www.youtube.com/embed/fpnE6UAfbtU',
        'input' => 'https://www.youtube.com/embed/JG_pea_aqpQ',
        'output' => 'https://www.youtube.com/embed/kTbXMkSOin4',
        'loops' => 'https://www.youtube.com/embed/qUPXsPtWGoY',
        'oop' => 'https://www.youtube.com/embed/pTB0EiLXUC8',
        'data_structures' => 'https://www.youtube.com/embed/nfOV4vwXjoc',
        'binary_search' => 'https://www.youtube.com/embed/uTyxscVC_-U',
        'algorithms' => 'https://www.youtube.com/embed/XMUe3zFhM5c',
        'php' => 'https://www.youtube.com/embed/OK_JCtrrv-c',
        'cpp' => 'https://www.youtube.com/embed/vLnPwxZdW4Y',
        'csharp' => 'https://www.youtube.com/embed/GhQdlIFylQ8',
        'java' => 'https://www.youtube.com/embed/GoXwIVyNvX0',
        'sql' => 'https://www.youtube.com/embed/HXV3zeQKqGY',
        'python' => 'https://www.youtube.com/embed/rfscVS0vtbw',
        'web' => 'https://www.youtube.com/embed/a_iQb1lnAEQ',
        'ai' => 'https://www.youtube.com/embed/JMUxmLyrhSk',
        'testing' => 'https://www.youtube.com/embed/9gu4BsqjQrA',
        'javascript' => 'https://www.youtube.com/embed/PkZNo7MFNFg',
        'aspnet' => 'https://www.youtube.com/embed/RWXKysImabs',
        'data_science' => 'https://www.youtube.com/embed/CMEWVn1uZpQ',
        'project_management' => 'https://www.youtube.com/embed/rck3MnC7OXA',
        'api' => 'https://www.youtube.com/embed/-mN3VyJuCjM',
        'architecture' => 'https://www.youtube.com/embed/8UlLgOf20Ho',
        'fallback' => 'https://www.youtube.com/embed/8mAITcNt710',
    ];

    if (preg_match('/\b(cpu|central processing unit)\b/i', $topic)) {
        return $videos['cpu'];
    }
    if (preg_match('/\b(ram|random access memory|cache memory|volatile memory)\b/i', $topic)) {
        return $videos['ram'];
    }
    if (preg_match('/\b(input device|input devices)\b/i', $topic)) {
        return $videos['input'];
    }
    if (preg_match('/\b(output device|output devices)\b/i', $topic)) {
        return $videos['output'];
    }
    if (preg_match('/\b(loops?|iterations?|counter loops?)\b/i', $topic)) {
        return $videos['loops'];
    }
    if (preg_match('/\b(object[- ]oriented|oop|class(?:es)?|objects?|encapsulation|inheritance|polymorphism|abstraction|single responsibility|open.closed|liskov|constructors?)\b/i', $topic)) {
        return $videos['oop'];
    }
    if (preg_match('/\b(data structures?|arrays?|linked lists?|stacks?|queues?|heaps?|hash ?maps?|binary trees?|graphs?)\b/i', $topic)) {
        return $videos['data_structures'];
    }
    if (preg_match('/\b(binary search|binary searching)\b/i', $topic)) {
        return $videos['binary_search'];
    }
    if (preg_match('/\b(algorithms?|big o|time complexity|space complexity|sorting)\b/i', $topic)) {
        return $videos['algorithms'];
    }
    if (preg_match('/\b(aspnet|asp\.net|\.net core|aspnet core)\b/i', $module . ' ' . $topic)) {
        return $videos['aspnet'];
    }
    if (preg_match('/\b(javascript|ecmascript|dom manipulation)\b/i', $module . ' ' . $topic)) {
        return $videos['javascript'];
    }
    if (preg_match('/\b(data science|pandas|data visualization|data analysis|statistics)\b/i', $module . ' ' . $topic)) {
        return $videos['data_science'];
    }
    if (preg_match('/\b(project management|stakeholder|sprint planning|risk register|project scope)\b/i', $module . ' ' . $topic)) {
        return $videos['project_management'];
    }
    if (preg_match('/\b(rest api|restful|api endpoint|http request|microservice)\b|modern[- ]web[- ]apps?/i', $module . ' ' . $topic)) {
        return $videos['api'];
    }
    if (preg_match('/\b(software architecture|system design|architecture pattern|scalability)\b/i', $module . ' ' . $topic)) {
        return $videos['architecture'];
    }
    if (preg_match('/\bphp\b/i', $module . ' ' . $topic)) {
        return $videos['php'];
    }
    if (preg_match('/\bc#|csharp\b/i', $module . ' ' . $topic)) {
        return $videos['csharp'];
    }
    if (preg_match('/\b(java|jvm|package|garbage collection)\b/i', $topic)) {
        return $videos['java'];
    }
    if (preg_match('/\bc\+\+|cpp\b/i', $module . ' ' . $topic)) {
        return $videos['cpp'];
    }
    if (preg_match('/\bjava\b/i', $module) && !str_contains($moduleSlug, 'cpp')) {
        return $videos['java'];
    }
    if (preg_match('/\b(sql|database|relational|query|transaction|normalization)\b/i', $module . ' ' . $topic)) {
        return $videos['sql'];
    }
    if (preg_match('/\bpython\b/i', $module . ' ' . $topic)) {
        return $videos['python'];
    }
    if (preg_match('/\b(artificial intelligence|machine learning|neural network|ai model)\b/i', $module . ' ' . $topic)) {
        return $videos['ai'];
    }
    if (preg_match('/\b(test|testing|test case|assertion|quality assurance)\b/i', $module . ' ' . $topic)) {
        return $videos['testing'];
    }
    if (preg_match('/\b(html|css|website|web development|web design)\b/i', $module . ' ' . $topic)) {
        return $videos['web'];
    }

    return $videos['fallback'];
}

function learnify_seed_lesson_videos(PDO $pdo): int
{
    $lessons = $pdo->query(
        'SELECT chapters.id, chapters.title, modules.slug AS module_slug, modules.name AS module_name
         FROM chapters
         INNER JOIN modules ON modules.id = chapters.module_id
         WHERE chapters.video_url IS NULL
         ORDER BY modules.id, chapters.display_order, chapters.id'
    )->fetchAll();
    $update = $pdo->prepare(
        'UPDATE chapters
         SET video_url = :video_url
         WHERE id = :chapter_id
           AND video_url IS NULL'
    );
    $seeded = 0;
    $pdo->beginTransaction();
    try {
        foreach ($lessons as $lesson) {
            $update->execute([
                'video_url' => learnify_lesson_video_for_topic(
                    (string) $lesson['module_slug'],
                    (string) $lesson['module_name'],
                    (string) $lesson['title']
                ),
                'chapter_id' => (int) $lesson['id'],
            ]);
            $seeded += $update->rowCount();
        }
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }

    return $seeded;
}

function learnify_seed_lessons(PDO $pdo): array
{
    learnify_seed_sample_quizzes($pdo);
    $curriculum = learnify_quiz_curriculum();

    $modules = $pdo->query(
        'SELECT id, slug, name, description
         FROM modules
         WHERE is_published = 1
         ORDER BY display_order, id'
    )->fetchAll();
    $conceptsStatement = $pdo->prepare(
        'SELECT questions.correct_answer, questions.explanation
         FROM quizzes
         INNER JOIN questions ON questions.quiz_id = quizzes.id
         WHERE quizzes.module_id = :module_id
           AND quizzes.title = :pool_title
           AND quizzes.is_published = 1
           AND questions.question_type = \'fill\'
           AND questions.question_text LIKE \'Complete the statement:%\'
         ORDER BY questions.display_order, questions.id'
    );
    $findLesson = $pdo->prepare(
        'SELECT id, title, description, content, display_order, estimated_minutes, is_published
         FROM chapters
         WHERE module_id = :module_id AND slug = :slug
         LIMIT 1'
    );
    $findLegacyLesson = $pdo->prepare(
        'SELECT id, slug, title, description, content, display_order, estimated_minutes, is_published
         FROM chapters
         WHERE module_id = :module_id
           AND slug NOT LIKE \'learnify-lesson-%\'
           AND is_published = 1
         ORDER BY display_order, id
         LIMIT 1'
    );
    $insertLesson = $pdo->prepare(
        'INSERT INTO chapters
            (module_id, title, slug, description, content, display_order, estimated_minutes, is_published)
         VALUES
            (:module_id, :title, :slug, :description, :content, :display_order, 15, 1)'
    );
    $updateLesson = $pdo->prepare(
        'UPDATE chapters
         SET title = :title,
             description = :description,
             content = :content,
             display_order = :display_order,
             estimated_minutes = 15,
             is_published = 1
         WHERE id = :chapter_id'
    );
    $added = 0;
    $updated = 0;
    $pdo->beginTransaction();
    try {
        foreach ($modules as $module) {
            $conceptsStatement->execute([
                'module_id' => (int) $module['id'],
                'pool_title' => 'Learnify Question Pool',
            ]);
            $concepts = $conceptsStatement->fetchAll();
            $moduleSlug = (string) $module['slug'];
            $expectedLessons = count($curriculum[$moduleSlug][1] ?? []);
            if ($expectedLessons < 15 || $expectedLessons > 35 || count($concepts) !== $expectedLessons) {
                throw new RuntimeException(
                    'Expected ' . $expectedLessons . ' seeded exam concepts for module ' . $moduleSlug
                );
            }
            $findLegacyLesson->execute(['module_id' => (int) $module['id']]);
            $legacyLesson = $findLegacyLesson->fetch();

            foreach ($concepts as $index => $concept) {
                $term = trim((string) $concept['correct_answer']);
                $explanation = trim((string) $concept['explanation']);
                $definition = preg_replace('/^' . preg_quote($term, '/') . '\s+/iu', '', rtrim($explanation, '. '));
                $definition = is_string($definition) && $definition !== ''
                    ? $definition
                    : $explanation;
                $title = ucfirst($term);
                $summary = 'Understand ' . $term . ', how it works in ' . (string) $module['name']
                    . ', and when to apply it in a practical system.';
                $example = learnify_lesson_example(
                    (string) $module['slug'],
                    $term,
                    $definition,
                    $index
                );
                $scenario = 'A team working on ' . (string) $module['name']
                    . ' needs to apply this idea while building or evaluating a real feature. '
                    . ucfirst($term) . ' matters because it ' . rtrim($definition, '.') . '. '
                    . 'The team first confirms the acceptance criteria and constraints, then identifies the component '
                    . 'responsible for this behavior. It records assumptions, handles invalid or missing inputs, and '
                    . 'checks both ordinary and boundary cases before deployment.';
                $content = json_encode([
                    'version' => 1,
                    'summary' => $summary,
                    'sections' => [
                        [
                            'heading' => 'Engineering context',
                            'body' => ucfirst($term) . ' ' . rtrim($definition, '.') . '. '
                                . 'In a production system, this concept belongs in an explicit design boundary with '
                                . 'defined inputs, outputs, ownership, and failure behavior. Keep the implementation '
                                . 'consistent with the module’s established conventions; validate untrusted values at '
                                . 'the boundary; and avoid coupling the rule to transport, persistence, or presentation '
                                . 'details unless that is a deliberate requirement. Document assumptions and measure '
                                . 'the relevant quality attribute, such as correctness, latency, memory use, or safety.',
                        ],
                        [
                            'heading' => 'Scenario walkthrough',
                            'body' => $scenario,
                            'breakdown_title' => 'Decision steps',
                            'breakdown' => [
                                'Translate the request into a testable requirement and identify the responsible component.',
                                'Choose the smallest design that satisfies the requirement and state its assumptions.',
                                'Handle invalid, missing, and boundary inputs explicitly; do not rely on client-side checks alone.',
                                'Verify expected behavior with focused tests and review operational failure signals.',
                            ],
                        ],
                        [
                            'heading' => 'Code example and syntax guide',
                            'code' => $example['code'],
                            'breakdown_title' => 'Syntax guide',
                            'breakdown' => $example['breakdown'],
                        ],
                    ],
                    'takeaways' => [
                        ucfirst($term) . ' ' . rtrim($definition, '.') . '.',
                        'Place the behavior behind a clear boundary and make its assumptions and failure cases explicit.',
                        'Validate external data and preserve the system’s security, performance, and correctness constraints.',
                        'Use repeatable tests that include expected, invalid, and boundary inputs before shipping.',
                    ],
                ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                $slugNumber = $index + 1;
                $useLegacyLesson = $slugNumber === $expectedLessons && $legacyLesson !== false;
                $slug = $useLegacyLesson
                    ? (string) $legacyLesson['slug']
                    : 'learnify-lesson-' . $slugNumber;
                if ($useLegacyLesson) {
                    $existingLesson = $legacyLesson;
                } else {
                    $findLesson->execute(['module_id' => (int) $module['id'], 'slug' => $slug]);
                    $existingLesson = $findLesson->fetch();
                }
                $values = [
                    'module_id' => (int) $module['id'],
                    'title' => $title,
                    'slug' => $slug,
                    'description' => $summary,
                    'content' => $content,
                    'display_order' => $slugNumber,
                ];
                if ($existingLesson === false) {
                    $insertLesson->execute($values);
                    $added++;
                } elseif (
                    (string) $existingLesson['title'] !== $title
                    || (string) $existingLesson['description'] !== $summary
                    || (string) $existingLesson['content'] !== $content
                    || (int) $existingLesson['display_order'] !== $slugNumber
                    || (int) $existingLesson['estimated_minutes'] !== 15
                    || !(bool) $existingLesson['is_published']
                ) {
                    $updateLesson->execute([
                        'title' => $title,
                        'description' => $summary,
                        'content' => $content,
                        'display_order' => $slugNumber,
                        'chapter_id' => (int) $existingLesson['id'],
                    ]);
                    $updated++;
                }
            }
        }
        $pdo->commit();
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $exception;
    }

    $videosAdded = learnify_seed_lesson_videos($pdo);

    return [
        'modules_checked' => count($modules),
        'lessons_added' => $added,
        'lessons_updated' => $updated,
        'videos_added' => $videosAdded,
    ];
}

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath((string) $_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    require_once __DIR__ . '/../includes/bootstrap.php';
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        header('Allow: POST');
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Use POST to seed lessons.']);
        exit;
    }
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Please log in to seed lessons.']);
        exit;
    }
    if (!learnify_csrf_is_valid($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Your session token is invalid. Refresh and try again.']);
        exit;
    }

    try {
        echo json_encode(['success' => true] + learnify_seed_lessons($pdo), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    } catch (Throwable $exception) {
        error_log('Learnify lesson seeding failed: ' . $exception->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Lessons could not be prepared.']);
    }
}
