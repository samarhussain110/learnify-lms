CREATE DATABASE IF NOT EXISTS learnify
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE learnify;

CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(254) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('student', 'instructor', 'admin') NOT NULL DEFAULT 'student',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    study_streak INT UNSIGNED NOT NULL DEFAULT 0,
    last_study_date DATE NULL,
    bio TEXT NULL,
    avatar_path VARCHAR(255) NULL,
    daily_study_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 15,
    weekly_module_target TINYINT UNSIGNED NOT NULL DEFAULT 1,
    target_topics TEXT NULL,
    email_verified_at DATETIME NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email),
    KEY idx_users_role_active (role, is_active)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS modules (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(170) NOT NULL,
    category VARCHAR(80) NOT NULL,
    description TEXT NOT NULL,
    icon VARCHAR(80) NOT NULL,
    display_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    is_published TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_modules_slug (slug),
    KEY idx_modules_category_published (category, is_published),
    KEY idx_modules_display_order (display_order)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS chapters (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    module_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    slug VARCHAR(200) NOT NULL,
    description TEXT NULL,
    content LONGTEXT NULL,
    video_url VARCHAR(255) NULL,
    display_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    estimated_minutes SMALLINT UNSIGNED NULL,
    is_published TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_chapters_module_slug (module_id, slug),
    KEY idx_chapters_module_order (module_id, display_order),
    CONSTRAINT fk_chapters_module
        FOREIGN KEY (module_id) REFERENCES modules (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_chapter_progress (
    user_id BIGINT UNSIGNED NOT NULL,
    chapter_id BIGINT UNSIGNED NOT NULL,
    completed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, chapter_id),
    KEY idx_chapter_progress_chapter (chapter_id),
    CONSTRAINT fk_chapter_progress_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_chapter_progress_chapter
        FOREIGN KEY (chapter_id) REFERENCES chapters (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS resources (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    chapter_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(180) NOT NULL,
    description TEXT NULL,
    resource_type ENUM('article', 'video', 'link', 'file', 'code') NOT NULL DEFAULT 'article',
    url VARCHAR(2048) NULL,
    file_path VARCHAR(500) NULL,
    display_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_resources_chapter_order (chapter_id, display_order),
    CONSTRAINT fk_resources_chapter
        FOREIGN KEY (chapter_id) REFERENCES chapters (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS quizzes (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    module_id BIGINT UNSIGNED NOT NULL,
    chapter_id BIGINT UNSIGNED NULL,
    title VARCHAR(180) NOT NULL,
    description TEXT NULL,
    passing_score DECIMAL(5,2) NOT NULL DEFAULT 70.00,
    time_limit_minutes SMALLINT UNSIGNED NULL,
    is_published TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_quizzes_module_published (module_id, is_published),
    KEY idx_quizzes_chapter (chapter_id),
    CONSTRAINT chk_quizzes_passing_score CHECK (passing_score >= 0 AND passing_score <= 100),
    CONSTRAINT fk_quizzes_module
        FOREIGN KEY (module_id) REFERENCES modules (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_quizzes_chapter
        FOREIGN KEY (chapter_id) REFERENCES chapters (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS quiz_attempts (
    attempt_id CHAR(64) NOT NULL,
    user_id BIGINT UNSIGNED NOT NULL,
    quiz_id BIGINT UNSIGNED NOT NULL,
    exam_mode ENUM('practice', 'timed', 'certification') NOT NULL DEFAULT 'practice',
    score_percentage DECIMAL(5,2) NOT NULL,
    correct_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    question_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    completed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (attempt_id),
    KEY idx_quiz_attempts_user_date (user_id, completed_at),
    KEY idx_quiz_attempts_quiz (quiz_id),
    CONSTRAINT chk_quiz_attempts_score CHECK (score_percentage >= 0 AND score_percentage <= 100),
    CONSTRAINT fk_quiz_attempts_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_quiz_attempts_quiz
        FOREIGN KEY (quiz_id) REFERENCES quizzes (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS questions (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    quiz_id BIGINT UNSIGNED NOT NULL,
    question_text TEXT NOT NULL,
    question_type ENUM('multiple_choice', 'true_false', 'short_answer', 'mcq', 'tf', 'fill') NOT NULL DEFAULT 'multiple_choice',
    options JSON NULL,
    correct_answer TEXT NOT NULL,
    explanation TEXT NULL,
    points DECIMAL(7,2) NOT NULL DEFAULT 1.00,
    display_order SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_questions_quiz_order (quiz_id, display_order),
    CONSTRAINT chk_questions_points CHECK (points > 0),
    CONSTRAINT fk_questions_quiz
        FOREIGN KEY (quiz_id) REFERENCES quizzes (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_question_history (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    attempt_id CHAR(64) NULL,
    question_id BIGINT UNSIGNED NOT NULL,
    submitted_answer TEXT NULL,
    is_correct TINYINT(1) NOT NULL,
    points_awarded DECIMAL(7,2) NOT NULL DEFAULT 0.00,
    answered_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_question_history_attempt (attempt_id),
    KEY idx_question_history_user_time (user_id, answered_at),
    KEY idx_question_history_question_time (question_id, answered_at),
    CONSTRAINT chk_question_history_points CHECK (points_awarded >= 0),
    CONSTRAINT fk_question_history_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_question_history_attempt
        FOREIGN KEY (attempt_id) REFERENCES quiz_attempts (attempt_id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_question_history_question
        FOREIGN KEY (question_id) REFERENCES questions (id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS student_notes (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id BIGINT UNSIGNED NOT NULL,
    module_id BIGINT UNSIGNED NOT NULL,
    chapter_id BIGINT UNSIGNED NULL,
    title VARCHAR(180) NULL,
    note_content LONGTEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_student_notes_user_module (user_id, module_id),
    KEY idx_student_notes_chapter (chapter_id),
    CONSTRAINT fk_student_notes_user
        FOREIGN KEY (user_id) REFERENCES users (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_student_notes_module
        FOREIGN KEY (module_id) REFERENCES modules (id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_student_notes_chapter
        FOREIGN KEY (chapter_id) REFERENCES chapters (id)
        ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

INSERT INTO modules (name, slug, category, description, icon, display_order) VALUES
('Computer Basics', 'computer-basics', 'Foundations', 'Build confidence with computer hardware, operating systems, files, and essential digital skills.', 'fa-solid fa-computer', 1),
('Programming Logic', 'programming-logic', 'Programming', 'Learn problem-solving fundamentals, variables, conditions, loops, and step-by-step program design.', 'fa-solid fa-diagram-project', 2),
('Easy C++ & Java', 'easy-cpp-java', 'Programming', 'Start writing programs in C++ and Java with clear examples of syntax, control flow, and core concepts.', 'fa-solid fa-code', 3),
('Easy C# Programming', 'easy-csharp-programming', 'Programming', 'Explore C# fundamentals, .NET basics, and practical application development in approachable lessons.', 'fa-brands fa-microsoft', 4),
('Object Oriented Coding', 'object-oriented-coding', 'Programming', 'Understand classes, objects, encapsulation, inheritance, and polymorphism through practical coding patterns.', 'fa-solid fa-cubes', 5),
('Data Structures Made Easy', 'data-structures-made-easy', 'Computer Science', 'Learn arrays, linked lists, stacks, queues, trees, and maps with clear use cases and examples.', 'fa-solid fa-layer-group', 6),
('Algorithms & Logic', 'algorithms-logic', 'Computer Science', 'Practice searching, sorting, complexity analysis, and algorithmic thinking for real problems.', 'fa-solid fa-gears', 7),
('Website Design', 'website-design', 'Web Development', 'Create accessible, responsive web pages using HTML, CSS, layout fundamentals, and visual design principles.', 'fa-solid fa-palette', 8),
('JavaScript Basics', 'javascript-basics', 'Web Development', 'Build a strong JavaScript foundation with variables, functions, events, arrays, and browser APIs.', 'fa-brands fa-js', 9),
('Modern Web Apps', 'modern-web-apps', 'Web Development', 'Learn the building blocks of interactive web applications, client-server communication, and modern workflows.', 'fa-solid fa-window-maximize', 10),
('PHP Web Development', 'php-web-development', 'Web Development', 'Develop dynamic server-rendered applications with PHP, secure form handling, sessions, and PDO.', 'fa-brands fa-php', 11),
('ASP.NET Core MVC', 'aspnet-core-mvc', 'Web Development', 'Build structured web applications with ASP.NET Core MVC, routing, controllers, views, and models.', 'fa-solid fa-server', 12),
('Databases & SQL', 'databases-sql', 'Data', 'Design relational databases and query, update, and organize information using practical SQL.', 'fa-solid fa-database', 13),
('Python Programming', 'python-programming', 'Programming', 'Learn Python from fundamentals through functions, collections, files, and small practical projects.', 'fa-brands fa-python', 14),
('Artificial Intelligence', 'artificial-intelligence', 'Data & AI', 'Explore AI concepts, machine learning fundamentals, responsible use, and common real-world applications.', 'fa-solid fa-brain', 15),
('Data Science', 'data-science', 'Data & AI', 'Get started with data preparation, visualization, statistics, and the data science workflow.', 'fa-solid fa-chart-line', 16),
('Software Testing', 'software-testing', 'Software Engineering', 'Practice test planning, test-case design, manual testing, and automated testing fundamentals.', 'fa-solid fa-vial', 17),
('Project Management', 'project-management', 'Professional Skills', 'Learn how to plan, organize, track, and deliver technology projects with collaborative workflows.', 'fa-solid fa-list-check', 18),
('Certification Test', 'certification-test', 'Exam Preparation', 'Prepare for technology certification exams with focused review strategies and practice questions.', 'fa-solid fa-award', 19),
('Job Interview Prep', 'job-interview-prep', 'Career Preparation', 'Prepare for technical interviews with structured practice in coding, communication, and career readiness.', 'fa-solid fa-briefcase', 20)
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    category = VALUES(category),
    description = VALUES(description),
    icon = VALUES(icon),
    display_order = VALUES(display_order);

INSERT INTO chapters
    (module_id, title, slug, description, content, display_order, estimated_minutes, is_published)
SELECT
    m.id,
    seed.title,
    seed.chapter_slug,
    seed.description,
    seed.content,
    1,
    20,
    1
FROM modules AS m
INNER JOIN (
    SELECT 'computer-basics' AS module_slug, 'Computer Hardware and Software' AS title, 'hardware-software' AS chapter_slug, 'Identify the core parts of a computer and how software uses them.' AS description, 'A computer system combines hardware and software. The CPU executes instructions, RAM holds data in active use, and storage keeps files when power is off. The operating system coordinates devices and provides services to applications.' AS content
    UNION ALL SELECT 'programming-logic', 'Breaking Problems into Steps', 'problem-solving-steps', 'Turn a problem into clear, testable instructions.', 'Start by describing the input, desired output, and constraints. Split the task into smaller steps, represent decisions with conditions, and repeat work with loops. Test the plan using ordinary and edge-case inputs before translating it into code.'
    UNION ALL SELECT 'easy-cpp-java', 'Variables and Program Flow', 'variables-and-flow', 'Use variables, expressions, and control flow in introductory programs.', 'Variables give names to values. Expressions combine values and operators. Conditional statements choose a path, while loops repeat a block. C++ and Java have different syntax, but both use types, functions, and structured control flow.'
    UNION ALL SELECT 'easy-csharp-programming', 'C# Types and Methods', 'csharp-types-methods', 'Understand C# values, methods, and the .NET runtime.', 'C# is a statically typed language in the .NET ecosystem. A method groups reusable statements and may accept parameters and return a value. Use clear types, descriptive names, and small methods to keep programs understandable.'
    UNION ALL SELECT 'object-oriented-coding', 'Classes and Encapsulation', 'classes-encapsulation', 'Model behavior with classes while protecting internal state.', 'A class defines data and behavior; an object is an instance of that class. Encapsulation protects an object''s invariants by controlling access to its state through methods or properties. Prefer small, cohesive classes with explicit responsibilities.'
    UNION ALL SELECT 'data-structures-made-easy', 'Choosing a Data Structure', 'choosing-data-structures', 'Compare common collections and their operations.', 'Arrays provide indexed access, linked lists connect elements by references, stacks use last-in-first-out order, and queues use first-in-first-out order. Trees represent hierarchies. Choose a structure based on the operations and performance your task requires.'
    UNION ALL SELECT 'algorithms-logic', 'Searching and Complexity', 'searching-complexity', 'Learn to reason about search algorithms and input growth.', 'Linear search checks items one by one and takes O(n) time in the worst case. Binary search repeatedly halves a sorted search space and takes O(log n). Big O describes how resource needs grow as input size grows.'
    UNION ALL SELECT 'website-design', 'Semantic HTML and CSS Layout', 'semantic-html-layout', 'Build accessible page structure and responsive layouts.', 'Use semantic HTML elements to describe page structure and CSS to control presentation. Flexbox is useful for one-dimensional alignment; Grid handles two-dimensional layouts. Responsive design adapts content to available space, and accessible labels help assistive technology users.'
    UNION ALL SELECT 'javascript-basics', 'JavaScript Values and Functions', 'javascript-values-functions', 'Work with JavaScript data, functions, and browser events.', 'JavaScript functions package reusable behavior. Use const for bindings that are not reassigned and let when reassignment is needed. Event listeners respond to user actions, and arrays and objects organize related data.'
    UNION ALL SELECT 'modern-web-apps', 'HTTP and Client-Server Flow', 'http-client-server', 'Trace a request from a browser to an application server.', 'A browser sends an HTTP request to a server and receives a response with a status code, headers, and optional body. APIs commonly exchange JSON. Validate data on the server even when the client also performs validation.'
    UNION ALL SELECT 'php-web-development', 'PHP Requests and PDO', 'php-requests-pdo', 'Handle server requests and use prepared database statements.', 'PHP can generate dynamic responses on the server. Read request data carefully, validate it, and escape output for its destination. PDO prepared statements separate SQL code from values and help prevent SQL injection.'
    UNION ALL SELECT 'aspnet-core-mvc', 'Controllers, Views, and Models', 'mvc-components', 'Understand the responsibilities of ASP.NET Core MVC components.', 'In MVC, controllers coordinate requests, models represent application data or behavior, and views render output. Routing maps incoming URLs to endpoints. Keep validation and authorization on the server.'
    UNION ALL SELECT 'databases-sql', 'Relational Tables and Queries', 'relational-tables-queries', 'Read relational data with SQL and connect tables using keys.', 'A relational database stores records in tables. Primary keys identify rows and foreign keys express relationships. SELECT retrieves data, WHERE filters it, and JOIN combines related rows. Parameterize user-provided values.'
    UNION ALL SELECT 'python-programming', 'Python Collections and Functions', 'python-collections-functions', 'Use functions and built-in collections to organize Python programs.', 'Python functions are defined with def and can return values. Lists are ordered and mutable, tuples are ordered and immutable, and dictionaries map keys to values. Use exceptions to handle expected error conditions explicitly.'
    UNION ALL SELECT 'artificial-intelligence', 'AI and Machine Learning Basics', 'ai-machine-learning-basics', 'Distinguish AI, machine learning, training data, and evaluation.', 'Artificial intelligence describes systems that perform tasks associated with intelligent behavior. Machine learning is one approach that learns patterns from data. Evaluate models on data that was not used for training and consider privacy, bias, and limitations.'
    UNION ALL SELECT 'data-science', 'Data Preparation and Visualization', 'data-preparation-visualization', 'Prepare data carefully and choose clear visual encodings.', 'A data science workflow often includes defining a question, collecting and cleaning data, exploring patterns, modeling, and communicating results. Document missing values and transformations. Select charts that make comparisons and distributions easy to interpret.'
    UNION ALL SELECT 'software-testing', 'Test Cases and Coverage', 'test-cases-coverage', 'Design useful tests for expected behavior and edge cases.', 'A test case defines preconditions, input, action, and expected result. Test normal values, boundary values, and invalid input. Coverage can show which code was executed, but high coverage alone does not prove that tests are effective.'
    UNION ALL SELECT 'project-management', 'Scope, Milestones, and Risks', 'scope-milestones-risks', 'Plan deliverables and make project uncertainty visible.', 'Project planning defines goals, scope, deliverables, owners, and milestones. Break work into manageable tasks and track dependencies. A risk register records uncertain events, their likelihood, impact, and response plans.'
    UNION ALL SELECT 'certification-test', 'Certification Exam Strategy', 'certification-exam-strategy', 'Plan review sessions and use practice exams effectively.', 'Read the exam objectives and map each topic to a study plan. Use spaced review and practice questions to identify gaps. During an exam, manage time, read qualifiers carefully, and revisit flagged questions when time permits.'
    UNION ALL SELECT 'job-interview-prep', 'Technical Interview Practice', 'technical-interview-practice', 'Explain your approach and validate solutions during interviews.', 'Clarify requirements before coding. Explain a simple approach, analyze its time and space complexity, then improve it if needed. Test with examples and edge cases, communicate tradeoffs, and reflect on feedback after practice.'
) AS seed ON seed.module_slug = m.slug
WHERE NOT EXISTS (
    SELECT 1
    FROM chapters AS existing
    WHERE existing.module_id = m.id AND existing.slug = seed.chapter_slug
);

INSERT INTO chapters
    (module_id, title, slug, description, content, display_order, estimated_minutes, is_published)
SELECT
    m.id,
    seed.title,
    seed.chapter_slug,
    seed.description,
    seed.content,
    2,
    25,
    1
FROM modules AS m
INNER JOIN (
    SELECT 'computer-basics' AS module_slug, 'Files, Networks, and Online Safety' AS title, 'files-networks-safety' AS chapter_slug, 'Manage files and use networked computers more safely.' AS description, 'Organize files with meaningful names and folders, and understand that file extensions suggest formats rather than guaranteeing contents. Networks move data between devices. Use unique passwords, updates, and caution with unexpected links.'
    UNION ALL SELECT 'programming-logic', 'Functions and Reusable Patterns', 'functions-reusable-patterns', 'Organize solutions into reusable, testable pieces.', 'A function names a unit of work and can accept inputs and produce an output. Use parameters for changing values and keep each function focused. Reusable functions reduce duplication and make programs easier to test.'
    UNION ALL SELECT 'easy-cpp-java', 'Functions and Collections', 'functions-collections', 'Organize code and store groups of related values.', 'Functions make program logic reusable. Collections store multiple values; choose arrays or library collection types according to whether size is fixed and which operations are needed. Keep input, processing, and output responsibilities clear.'
    UNION ALL SELECT 'easy-csharp-programming', 'Collections and Exceptions', 'csharp-collections-exceptions', 'Work with collection types and handle exceptional conditions.', 'The .NET libraries provide collection types such as List<T> and Dictionary<TKey,TValue>. Use exceptions for exceptional failures, not routine branching, and handle only errors that the current layer can resolve meaningfully.'
    UNION ALL SELECT 'object-oriented-coding', 'Inheritance and Composition', 'inheritance-composition', 'Reuse behavior with clear relationships between types.', 'Inheritance models an is-a relationship and enables polymorphic behavior. Composition builds a type from collaborating objects and is often more flexible. Choose the relationship that best reflects the domain and keeps responsibilities focused.'
    UNION ALL SELECT 'data-structures-made-easy', 'Trees, Hashing, and Tradeoffs', 'trees-hashing-tradeoffs', 'Understand hierarchical and key-based structures.', 'Trees model parent-child relationships, while hash tables map keys to values using a hash function. Average lookup in a hash table is often O(1), but collisions and resizing affect performance. Consider ordering, lookup patterns, and memory.'
    UNION ALL SELECT 'algorithms-logic', 'Sorting and Efficiency', 'sorting-efficiency', 'Compare sorting methods and interpret their complexity.', 'Sorting arranges values according to an ordering rule. Comparison sorts commonly take O(n log n) time. The right choice depends on input size, stability, memory limits, and whether data is already partially ordered.'
    UNION ALL SELECT 'website-design', 'Responsive and Accessible Interfaces', 'responsive-accessible-interfaces', 'Design interfaces that work for different users and devices.', 'Use flexible layouts and media queries rather than fixed page widths. Provide visible focus indicators, sufficient contrast, descriptive link text, and form labels. Test with keyboard navigation and multiple viewport sizes.'
    UNION ALL SELECT 'javascript-basics', 'DOM Updates and Async Basics', 'dom-async-basics', 'Change page content safely and understand asynchronous work.', 'The DOM represents a document as objects that scripts can inspect and update. Use textContent for plain user-provided text. Promises represent work that may complete later; async and await make promise-based code easier to follow.'
    UNION ALL SELECT 'modern-web-apps', 'JSON APIs and Error States', 'json-apis-errors', 'Handle API responses, validation, and failures reliably.', 'An API client should check response status, parse expected data, and handle network or server errors. Display loading, empty, and failure states. Never treat client-side input validation as a security boundary.'
    UNION ALL SELECT 'php-web-development', 'Sessions, Validation, and Output Safety', 'php-sessions-validation', 'Protect user sessions and process untrusted input safely.', 'Sessions store server-side state associated with a session identifier. Validate input according to its purpose, authorize every protected operation, and escape output with the correct HTML context. Use CSRF tokens for state-changing forms.'
    UNION ALL SELECT 'aspnet-core-mvc', 'Model Binding and Validation', 'model-binding-validation', 'Validate bound request data before performing application work.', 'Model binding maps request values to action parameters or models. Apply validation rules and check ModelState before saving changes. Authorization policies should restrict actions based on the authenticated user and role.'
    UNION ALL SELECT 'databases-sql', 'Constraints, Indexes, and Transactions', 'constraints-indexes-transactions', 'Protect data integrity and make common queries efficient.', 'Constraints enforce rules such as uniqueness and referential integrity. Indexes can speed up lookups while adding storage and write costs. Transactions group related changes so they succeed or roll back together.'
    UNION ALL SELECT 'python-programming', 'Files, Modules, and Exceptions', 'python-files-modules', 'Organize Python projects and manage files safely.', 'Modules split code into reusable files. Use context managers such as with open(...) to close files reliably. Catch specific exceptions you can handle and allow unexpected failures to surface rather than hiding them.'
    UNION ALL SELECT 'artificial-intelligence', 'Model Evaluation and Responsible AI', 'model-evaluation-responsible-ai', 'Evaluate model quality and identify responsible-use concerns.', 'Choose evaluation metrics that match the task and the costs of different errors. Inspect data quality, representation, and privacy. Communicate uncertainty and avoid using a model outside its validated context.'
    UNION ALL SELECT 'data-science', 'Statistics and Communicating Findings', 'statistics-communicating-findings', 'Summarize data with appropriate methods and clear caveats.', 'Descriptive statistics summarize observed data but do not prove causation. Examine distributions and outliers before selecting summaries. Explain the sample, assumptions, uncertainty, and limitations alongside a result.'
    UNION ALL SELECT 'software-testing', 'Automation and Regression Testing', 'automation-regression-testing', 'Automate repeatable checks while keeping tests maintainable.', 'Automated tests provide fast repeatable feedback. Unit tests focus on small components; integration tests check collaborations. Regression tests preserve behavior after changes. Keep assertions specific and test setup isolated.'
    UNION ALL SELECT 'project-management', 'Team Communication and Change Control', 'team-communication-change-control', 'Coordinate stakeholders and manage changes transparently.', 'Agree on communication channels, decision owners, and update frequency. Record scope changes with impact on schedule, cost, and quality. Review progress against outcomes and adjust plans when assumptions change.'
    UNION ALL SELECT 'certification-test', 'Practice Review and Knowledge Gaps', 'practice-review-gaps', 'Use results to focus your next round of exam preparation.', 'After a practice test, review every missed or guessed question and record why the correct answer is right. Group gaps by exam objective, revisit the source material, then retest after a delay to check retention.'
    UNION ALL SELECT 'job-interview-prep', 'Behavioral Answers and Portfolio Stories', 'behavioral-portfolio-stories', 'Prepare concise evidence-based examples of your work.', 'For behavioral questions, structure an example around the situation, your responsibility, actions, and measurable outcome. Describe your own contribution accurately and connect the example to the role. Keep a portfolio ready to support technical discussion.'
) AS seed ON seed.module_slug = m.slug
WHERE NOT EXISTS (
    SELECT 1
    FROM chapters AS existing
    WHERE existing.module_id = m.id AND existing.slug = seed.chapter_slug
);

INSERT INTO resources (chapter_id, title, description, resource_type, display_order)
SELECT chapters.id, seed.title, seed.description, 'article', 1
FROM chapters
INNER JOIN modules ON modules.id = chapters.module_id
INNER JOIN (
    SELECT 'computer-basics' AS module_slug, 'hardware-software' AS chapter_slug, 'Hardware and software review' AS title, 'Review the roles of the CPU, memory, storage, operating systems, and applications.' AS description
    UNION ALL SELECT 'programming-logic', 'problem-solving-steps', 'Problem decomposition guide', 'Practice identifying inputs, outputs, decisions, and repeatable steps.'
    UNION ALL SELECT 'easy-cpp-java', 'variables-and-flow', 'Variables and control flow', 'Review types, expressions, branches, and loops across C++ and Java.'
    UNION ALL SELECT 'easy-csharp-programming', 'csharp-types-methods', 'C# methods reference', 'Review method declarations, parameters, return values, and basic types.'
    UNION ALL SELECT 'object-oriented-coding', 'classes-encapsulation', 'Encapsulation checklist', 'Check class responsibilities, access boundaries, and object invariants.'
    UNION ALL SELECT 'data-structures-made-easy', 'choosing-data-structures', 'Data structure comparison', 'Compare access, insertion, removal, and ordering needs.'
    UNION ALL SELECT 'algorithms-logic', 'searching-complexity', 'Search algorithm notes', 'Compare linear and binary search and state their input requirements.'
    UNION ALL SELECT 'website-design', 'semantic-html-layout', 'Semantic layout review', 'Review document structure, CSS Grid, Flexbox, and responsive behavior.'
    UNION ALL SELECT 'javascript-basics', 'javascript-values-functions', 'JavaScript fundamentals', 'Review declarations, arrays, objects, functions, and events.'
    UNION ALL SELECT 'modern-web-apps', 'http-client-server', 'HTTP request lifecycle', 'Trace a browser request, server response, and API data exchange.'
    UNION ALL SELECT 'php-web-development', 'php-requests-pdo', 'Secure PHP and PDO checklist', 'Review validation, output escaping, and parameterized queries.'
    UNION ALL SELECT 'aspnet-core-mvc', 'mvc-components', 'MVC responsibilities', 'Review routing, controllers, models, and views.'
    UNION ALL SELECT 'databases-sql', 'relational-tables-queries', 'SQL query practice', 'Review keys, SELECT, WHERE, JOIN, and safe parameters.'
    UNION ALL SELECT 'python-programming', 'python-collections-functions', 'Python collections review', 'Compare lists, tuples, dictionaries, functions, and exceptions.'
    UNION ALL SELECT 'artificial-intelligence', 'ai-machine-learning-basics', 'AI concepts review', 'Review training data, evaluation data, model limitations, and responsible use.'
    UNION ALL SELECT 'data-science', 'data-preparation-visualization', 'Data workflow notes', 'Review data cleaning, visualization choices, and reproducible transformations.'
    UNION ALL SELECT 'software-testing', 'test-cases-coverage', 'Test design worksheet', 'Draft normal, boundary, and invalid-input test cases.'
    UNION ALL SELECT 'project-management', 'scope-milestones-risks', 'Project planning worksheet', 'Record deliverables, dependencies, milestones, and risks.'
    UNION ALL SELECT 'certification-test', 'certification-exam-strategy', 'Certification review plan', 'Map exam objectives to practice sessions and focused review.'
    UNION ALL SELECT 'job-interview-prep', 'technical-interview-practice', 'Interview practice checklist', 'Practice clarification, explanation, complexity, and edge-case testing.'
) AS seed
    ON seed.module_slug = modules.slug
   AND seed.chapter_slug = chapters.slug
WHERE NOT EXISTS (
    SELECT 1
    FROM resources AS existing
    WHERE existing.chapter_id = chapters.id AND existing.title = seed.title
);

INSERT INTO resources (chapter_id, title, description, resource_type, display_order)
SELECT
    chapters.id,
    CONCAT(chapters.title, ' Lesson'),
    COALESCE(chapters.description, 'Read the lesson and review its key concepts.'),
    'article',
    1
FROM chapters
WHERE NOT EXISTS (
    SELECT 1
    FROM resources AS existing
    WHERE existing.chapter_id = chapters.id
);

INSERT INTO quizzes
    (module_id, chapter_id, title, description, passing_score, time_limit_minutes, is_published)
SELECT
    modules.id,
    chapters.id,
    'Foundations Check',
    CONCAT('A short practice quiz for ', modules.name, '.'),
    70.00,
    10,
    1
FROM modules
INNER JOIN chapters
    ON chapters.module_id = modules.id
   AND chapters.slug = CASE modules.slug
        WHEN 'computer-basics' THEN 'hardware-software'
        WHEN 'programming-logic' THEN 'problem-solving-steps'
        WHEN 'easy-cpp-java' THEN 'variables-and-flow'
        WHEN 'easy-csharp-programming' THEN 'csharp-types-methods'
        WHEN 'object-oriented-coding' THEN 'classes-encapsulation'
        WHEN 'data-structures-made-easy' THEN 'choosing-data-structures'
        WHEN 'algorithms-logic' THEN 'searching-complexity'
        WHEN 'website-design' THEN 'semantic-html-layout'
        WHEN 'javascript-basics' THEN 'javascript-values-functions'
        WHEN 'modern-web-apps' THEN 'http-client-server'
        WHEN 'php-web-development' THEN 'php-requests-pdo'
        WHEN 'aspnet-core-mvc' THEN 'mvc-components'
        WHEN 'databases-sql' THEN 'relational-tables-queries'
        WHEN 'python-programming' THEN 'python-collections-functions'
        WHEN 'artificial-intelligence' THEN 'ai-machine-learning-basics'
        WHEN 'data-science' THEN 'data-preparation-visualization'
        WHEN 'software-testing' THEN 'test-cases-coverage'
        WHEN 'project-management' THEN 'scope-milestones-risks'
        WHEN 'certification-test' THEN 'certification-exam-strategy'
        WHEN 'job-interview-prep' THEN 'technical-interview-practice'
    END
WHERE NOT EXISTS (
    SELECT 1
    FROM quizzes AS existing
    WHERE existing.module_id = modules.id AND existing.title = 'Foundations Check'
);

INSERT INTO questions
    (quiz_id, question_text, question_type, options, correct_answer, explanation, points, display_order)
SELECT
    quizzes.id,
    seed.question_text,
    'multiple_choice',
    seed.options,
    seed.correct_answer,
    seed.explanation,
    1.00,
    seed.display_order
FROM quizzes
INNER JOIN modules ON modules.id = quizzes.module_id
INNER JOIN (
    SELECT 'computer-basics' AS module_slug, 1 AS display_order, 'Which component executes program instructions?' AS question_text, JSON_ARRAY('CPU', 'Monitor', 'Keyboard', 'Printer') AS options, 'A' AS correct_answer, 'The CPU executes instructions; peripherals provide input or output.' AS explanation
    UNION ALL SELECT 'computer-basics', 2, 'Which type of memory normally loses its contents when power is removed?', JSON_ARRAY('RAM', 'SSD', 'Hard drive', 'USB flash drive'), 'A', 'RAM is volatile working memory; persistent storage retains data without power.'
    UNION ALL SELECT 'programming-logic', 1, 'What is the first useful step when solving a programming problem?', JSON_ARRAY('Clarify inputs and expected outputs', 'Write random code immediately', 'Ignore edge cases', 'Choose variable names before understanding the task'), 'A', 'Understanding inputs, outputs, and constraints gives a solution a clear target.'
    UNION ALL SELECT 'programming-logic', 2, 'Which construct repeats a group of instructions?', JSON_ARRAY('Loop', 'Constant', 'Comment', 'Import'), 'A', 'Loops repeat instructions while a condition holds or for each item.'
    UNION ALL SELECT 'easy-cpp-java', 1, 'What is a variable primarily used for?', JSON_ARRAY('To name and store a value', 'To compile the operating system', 'To replace every function', 'To guarantee a program has no bugs'), 'A', 'A variable is a named storage location or binding for a value.'
    UNION ALL SELECT 'easy-cpp-java', 2, 'Which construct selects between paths based on a condition?', JSON_ARRAY('if statement', 'Array declaration', 'Comment', 'Package name'), 'A', 'An if statement runs a branch when its condition evaluates as true.'
    UNION ALL SELECT 'easy-csharp-programming', 1, 'What does a C# method commonly define?', JSON_ARRAY('Reusable behavior with optional parameters and a return value', 'A physical processor', 'A database server only', 'A CSS selector'), 'A', 'Methods package reusable behavior and may receive inputs and return a result.'
    UNION ALL SELECT 'easy-csharp-programming', 2, 'C# is primarily associated with which platform ecosystem?', JSON_ARRAY('.NET', 'JVM only', 'WordPress only', 'Apache Spark only'), 'A', 'C# is commonly developed and run with the .NET platform.'
    UNION ALL SELECT 'object-oriented-coding', 1, 'What is an object?', JSON_ARRAY('An instance of a class', 'A loop condition', 'A SQL index', 'A compiler warning'), 'A', 'A class defines a type; an object is a particular instance of it.'
    UNION ALL SELECT 'object-oriented-coding', 2, 'What is the main goal of encapsulation?', JSON_ARRAY('Protect internal state behind a controlled interface', 'Make every field globally writable', 'Remove all methods', 'Convert code to machine instructions'), 'A', 'Encapsulation controls access to state so an object can preserve its invariants.'
    UNION ALL SELECT 'data-structures-made-easy', 1, 'Which structure follows last-in-first-out order?', JSON_ARRAY('Stack', 'Queue', 'Sorted table', 'Graph edge'), 'A', 'A stack removes the most recently added item first.'
    UNION ALL SELECT 'data-structures-made-easy', 2, 'Which structure is designed for first-in-first-out processing?', JSON_ARRAY('Queue', 'Stack', 'Binary search tree', 'Hash function'), 'A', 'A queue serves elements in the order they were added.'
    UNION ALL SELECT 'algorithms-logic', 1, 'What requirement does binary search rely on?', JSON_ARRAY('The search range is ordered', 'The data must be stored as text', 'Every value is unique', 'The input must contain exactly ten items'), 'A', 'Binary search discards half the range using ordering, so the range must be sorted.'
    UNION ALL SELECT 'algorithms-logic', 2, 'What does O(n) describe?', JSON_ARRAY('Linear growth with input size in the stated analysis', 'A guaranteed constant runtime', 'A programming language', 'A memory address'), 'A', 'O(n) indicates that resource use grows proportionally to input size.'
    UNION ALL SELECT 'website-design', 1, 'What is a key benefit of semantic HTML?', JSON_ARRAY('It gives document regions meaningful structure', 'It automatically encrypts all data', 'It replaces CSS entirely', 'It guarantees identical rendering on every device'), 'A', 'Semantic elements communicate structure to browsers, developers, and assistive technologies.'
    UNION ALL SELECT 'website-design', 2, 'Which CSS tool is especially suited to two-dimensional page layouts?', JSON_ARRAY('CSS Grid', 'HTML comments', 'HTTP headers', 'SQL JOIN'), 'A', 'CSS Grid manages rows and columns for two-dimensional layouts.'
    UNION ALL SELECT 'javascript-basics', 1, 'Which keyword declares a block-scoped binding that is not reassigned?', JSON_ARRAY('const', 'goto', 'define', 'static only'), 'A', 'Use const when the binding itself will not be reassigned.'
    UNION ALL SELECT 'javascript-basics', 2, 'How can JavaScript respond to a button click?', JSON_ARRAY('Register an event listener', 'Add a SQL foreign key', 'Use a CSS media query alone', 'Rename the HTML file'), 'A', 'An event listener runs a callback when the selected event occurs.'
    UNION ALL SELECT 'modern-web-apps', 1, 'What does an HTTP response commonly include?', JSON_ARRAY('A status code, headers, and optional body', 'Only a CPU instruction', 'A database primary key only', 'A CSS class name only'), 'A', 'HTTP responses communicate status and metadata, and may include a body.'
    UNION ALL SELECT 'modern-web-apps', 2, 'Why must a server validate client-submitted data?', JSON_ARRAY('Clients can be bypassed or modified', 'Browsers cannot send strings', 'Validation is only for styling', 'Servers cannot read request bodies'), 'A', 'Client checks improve usability but are not a trusted security boundary.'
    UNION ALL SELECT 'php-web-development', 1, 'What is an important benefit of PDO prepared statements?', JSON_ARRAY('They separate SQL structure from parameter values', 'They automatically design the page', 'They remove the need for authorization', 'They encrypt every database column'), 'A', 'Bound parameters keep supplied values separate from SQL syntax.'
    UNION ALL SELECT 'php-web-development', 2, 'What should be done before rendering untrusted text into HTML?', JSON_ARRAY('Escape it for the HTML output context', 'Trust it if it came from a form', 'Place it inside a script element', 'Convert it to a SQL query'), 'A', 'Context-aware output escaping helps prevent markup injection and XSS.'
    UNION ALL SELECT 'aspnet-core-mvc', 1, 'Which MVC component commonly coordinates a web request?', JSON_ARRAY('Controller', 'View stylesheet', 'Database index', 'Browser cache'), 'A', 'Controllers handle route actions and coordinate application work.'
    UNION ALL SELECT 'aspnet-core-mvc', 2, 'What should an MVC action do after model validation fails?', JSON_ARRAY('Return an appropriate validation response without saving invalid data', 'Ignore the errors and persist everything', 'Disable authentication', 'Delete the database'), 'A', 'Invalid data should not be persisted; return useful validation feedback.'
    UNION ALL SELECT 'databases-sql', 1, 'What does a primary key do?', JSON_ARRAY('Uniquely identifies a row in a table', 'Sorts every table automatically', 'Encrypts the database', 'Stores a web page layout'), 'A', 'A primary key uniquely identifies each row.'
    UNION ALL SELECT 'databases-sql', 2, 'Why use a foreign key?', JSON_ARRAY('To enforce a relationship between records', 'To format a heading', 'To cache HTTP responses', 'To declare a loop'), 'A', 'Foreign keys enforce referential integrity between related tables.'
    UNION ALL SELECT 'python-programming', 1, 'Which Python collection maps keys to values?', JSON_ARRAY('Dictionary', 'List', 'Tuple', 'Range'), 'A', 'A dictionary stores key-value pairs.'
    UNION ALL SELECT 'python-programming', 2, 'What does a Python context manager help with when opening a file?', JSON_ARRAY('Reliable cleanup such as closing the file', 'Automatically write a unit test', 'Guarantee the file exists remotely', 'Compile Python to SQL'), 'A', 'A with statement ensures the file is closed when its block exits.'
    UNION ALL SELECT 'artificial-intelligence', 1, 'Why evaluate a model on data not used for training?', JSON_ARRAY('To estimate how it generalizes to unseen examples', 'To make training data larger after evaluation', 'To guarantee zero bias', 'To avoid defining a metric'), 'A', 'Held-out data helps assess performance beyond memorized training examples.'
    UNION ALL SELECT 'artificial-intelligence', 2, 'Which is a responsible AI practice?', JSON_ARRAY('Assess data quality, privacy, and potential bias', 'Assume a model is always correct', 'Hide uncertainty from users', 'Use any data without permission'), 'A', 'Responsible use considers data, privacy, bias, limits, and context.'
    UNION ALL SELECT 'data-science', 1, 'What is a common early data science step?', JSON_ARRAY('Inspect and clean the data', 'Publish conclusions before checking data', 'Remove all documentation', 'Assume missing values mean zero'), 'A', 'Data inspection and cleaning help detect quality issues before analysis.'
    UNION ALL SELECT 'data-science', 2, 'What should a chart choice support?', JSON_ARRAY('Clear communication of the comparison or distribution', 'Maximum decoration regardless of the question', 'Hiding the sample size', 'Replacing all analysis'), 'A', 'Choose visual encodings that make the analytical question easy to understand.'
    UNION ALL SELECT 'software-testing', 1, 'Which tests are especially useful for input validation?', JSON_ARRAY('Normal, boundary, and invalid-input cases', 'Only one happy-path case', 'Only tests that never assert results', 'Tests run without expected behavior'), 'A', 'These cases exercise typical behavior and important edge conditions.'
    UNION ALL SELECT 'software-testing', 2, 'What does code coverage tell you?', JSON_ARRAY('Which code was executed during tests', 'That all requirements are correct', 'That there are no bugs', 'The project delivery date'), 'A', 'Coverage measures execution, not the quality or completeness of assertions.'
    UNION ALL SELECT 'project-management', 1, 'What is a milestone?', JSON_ARRAY('A significant checkpoint or deliverable in a plan', 'A programming language keyword', 'A random task with no owner', 'A database transaction'), 'A', 'Milestones mark meaningful progress or delivery points.'
    UNION ALL SELECT 'project-management', 2, 'What information belongs in a risk register?', JSON_ARRAY('Risk, likelihood, impact, and planned response', 'Only the project logo', 'A list of completed unit tests only', 'Passwords for project accounts'), 'A', 'A risk register tracks uncertainty and the planned mitigation or response.'
    UNION ALL SELECT 'certification-test', 1, 'How should practice exam mistakes be used?', JSON_ARRAY('Review the reasoning and map gaps to exam objectives', 'Ignore them after seeing the score', 'Memorize letters without concepts', 'Stop studying all weak topics'), 'A', 'Error review identifies gaps and guides focused study.'
    UNION ALL SELECT 'certification-test', 2, 'What is a useful exam time-management habit?', JSON_ARRAY('Read qualifiers carefully and revisit flagged questions if time remains', 'Spend all time on the first question', 'Skip every instruction', 'Change every answer at random'), 'A', 'Careful reading and planned review help use exam time effectively.'
    UNION ALL SELECT 'job-interview-prep', 1, 'What is a good first step before implementing an interview problem?', JSON_ARRAY('Clarify requirements and constraints', 'Start coding without reading', 'Avoid asking about inputs', 'Claim the problem is impossible'), 'A', 'Clarification prevents solving the wrong problem and exposes edge cases.'
    UNION ALL SELECT 'job-interview-prep', 2, 'What should you do after describing a coding solution?', JSON_ARRAY('Test examples and edge cases, then discuss complexity', 'Avoid checking the result', 'Hide tradeoffs', 'Use unexplained variables'), 'A', 'Validation and complexity analysis demonstrate correctness and reasoning.'
) AS seed ON seed.module_slug = modules.slug
WHERE quizzes.module_id = modules.id
  AND quizzes.title = 'Foundations Check'
  AND NOT EXISTS (
      SELECT 1
      FROM questions AS existing
      WHERE existing.quiz_id = quizzes.id
        AND existing.display_order = seed.display_order
  );
