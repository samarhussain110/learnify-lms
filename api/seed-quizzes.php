<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';

function learnify_quiz_curriculum(): array
{
    $modules = [
        'computer-basics' => ['Computer Basics', [
            ['CPU', 'executes program instructions and performs calculations'],
            ['RAM', 'temporarily stores data used by running programs'],
            ['input device', 'sends data or commands into a computer'],
            ['output device', 'presents information produced by a computer'],
            ['operating system', 'manages hardware and provides services to applications'],
            ['SSD', 'stores files persistently using flash memory'],
            ['motherboard', 'connects the main components of a computer'],
            ['GPU', 'processes graphics and parallel visual computations'],
            ['peripheral', 'is an external device connected to a computer'],
            ['backup', 'is a separate copy used to restore lost data'],
        ]],
        'programming-logic' => ['Programming Logic', [
            ['algorithm', 'is a finite sequence of steps for solving a problem'],
            ['variable', 'names a value that a program can use or update'],
            ['condition', 'chooses a path based on whether an expression is true'],
            ['loop', 'repeats instructions while a condition holds or over a collection'],
            ['function', 'packages reusable instructions that may accept inputs'],
            ['pseudocode', 'describes an algorithm without requiring exact language syntax'],
            ['input', 'is data supplied to a program for processing'],
            ['output', 'is information produced by a program'],
            ['debugging', 'locates and corrects defects in a program'],
            ['edge case', 'is an unusual boundary input that should still be handled'],
        ]],
        'easy-cpp-java' => ['C++ and Java', [
            ['compiler', 'translates source code into a form a computer can execute'],
            ['main function', 'is a conventional entry point for a program'],
            ['integer', 'stores a whole-number value'],
            ['boolean', 'represents a true or false value'],
            ['array', 'stores an indexed sequence of elements'],
            ['loop', 'repeats a block of code according to a control rule'],
            ['class', 'defines a type that can group data and behavior'],
            ['exception', 'represents an error or unusual condition during execution'],
            ['C++ reference', 'is an alias that refers to an existing object'],
            ['Java virtual machine', 'runs Java bytecode on supported platforms'],
        ]],
        'easy-csharp-programming' => ['C# Programming', [
            ['.NET', 'is a development platform used to build and run C# applications'],
            ['namespace', 'organizes related types and helps avoid naming collisions'],
            ['method', 'is a named block of behavior that can accept parameters'],
            ['property', 'provides a controlled way to read or assign object data'],
            ['interface', 'defines a contract that implementing types must provide'],
            ['List<T>', 'is a resizable collection of values of type T'],
            ['async method', 'can await asynchronous work without blocking its caller'],
            ['LINQ', 'provides a consistent way to query data collections'],
            ['exception handling', 'uses try and catch to respond to runtime errors'],
            ['dependency injection', 'supplies a class with the services it needs'],
        ]],
        'object-oriented-coding' => ['Object-Oriented Programming', [
            ['class', 'is a definition from which objects can be created'],
            ['object', 'is an instance of a class with state and behavior'],
            ['encapsulation', 'protects internal state behind a controlled interface'],
            ['inheritance', 'lets a type derive and reuse behavior from a base type'],
            ['polymorphism', 'lets different types respond through a shared interface'],
            ['abstraction', 'exposes essential behavior while hiding implementation detail'],
            ['constructor', 'initializes a new object when it is created'],
            ['composition', 'builds a type by combining objects with focused responsibilities'],
            ['method', 'defines behavior associated with a type or object'],
            ['interface', 'specifies behavior that unrelated types can both implement'],
        ]],
        'data-structures-made-easy' => ['Data Structures', [
            ['array', 'stores elements at indexed positions for direct access'],
            ['linked list', 'connects elements using references to neighboring nodes'],
            ['stack', 'follows last-in, first-out order'],
            ['queue', 'follows first-in, first-out order'],
            ['hash map', 'maps keys to values using a hash function'],
            ['binary search tree', 'organizes values so left and right subtrees follow an ordering'],
            ['graph', 'models entities as vertices connected by edges'],
            ['set', 'stores distinct values without duplicates'],
            ['heap', 'keeps a highest- or lowest-priority item readily accessible'],
            ['tree traversal', 'visits the nodes of a tree in a defined order'],
        ]],
        'algorithms-logic' => ['Algorithms and Logic', [
            ['linear search', 'checks items one by one until a match is found'],
            ['binary search', 'repeatedly halves an ordered search range'],
            ['sorting algorithm', 'reorders values according to a comparison rule'],
            ['time complexity', 'describes how an algorithm’s running work grows with input size'],
            ['O(1)', 'describes constant-time work independent of input size'],
            ['O(n)', 'describes work that grows linearly with input size'],
            ['recursion', 'solves a problem by calling the same procedure on smaller cases'],
            ['base case', 'stops a recursive process from continuing indefinitely'],
            ['greedy algorithm', 'chooses a locally favorable option at each step'],
            [' Big O notation', 'summarizes an upper growth bound for resource use'],
        ]],
        'website-design' => ['Website Design', [
            ['semantic HTML', 'uses elements whose names communicate document structure'],
            ['CSS', 'controls the presentation and layout of web documents'],
            ['responsive design', 'adapts a page layout to different screen sizes'],
            ['CSS Grid', 'lays out content in rows and columns'],
            ['Flexbox', 'aligns items along a one-dimensional row or column'],
            ['alt text', 'describes meaningful images for people who cannot see them'],
            ['landmark element', 'identifies a major page region for navigation'],
            ['contrast', 'helps text and controls remain distinguishable from backgrounds'],
            ['media query', 'applies styles when device or viewport conditions match'],
            ['HTML form label', 'provides an accessible name for a form control'],
        ]],
        'javascript-basics' => ['JavaScript Basics', [
            ['const', 'declares a block-scoped binding that cannot be reassigned'],
            ['let', 'declares a block-scoped binding that can be reassigned'],
            ['function', 'defines reusable behavior that can be called'],
            ['array', 'stores an ordered collection of values'],
            ['object', 'groups related values under named properties'],
            ['event listener', 'runs a callback when a specified browser event occurs'],
            ['Promise', 'represents the eventual result of asynchronous work'],
            ['JSON', 'is a text format commonly used to exchange structured data'],
            ['DOM', 'is the browser object model for a document’s elements'],
            ['strict equality', 'compares values without coercing their types'],
        ]],
        'modern-web-apps' => ['Modern Web Applications', [
            ['HTTP request', 'asks a server to perform an operation on a resource'],
            ['HTTP response', 'returns a status, headers, and possibly a body'],
            ['REST API', 'exposes resources through a consistent HTTP interface'],
            ['JSON', 'is a common text representation for API data'],
            ['authentication', 'establishes who a user or client is'],
            ['authorization', 'determines what an authenticated user may do'],
            ['client-side routing', 'changes views in the browser without a full page load'],
            ['server-side validation', 'checks untrusted input before the application accepts it'],
            ['cache', 'reuses stored results to reduce repeated work or latency'],
            ['HTTPS', 'protects HTTP traffic using transport-layer encryption'],
        ]],
        'php-web-development' => ['PHP Web Development', [
            ['PHP opening tag', 'starts a PHP code block with <?php'],
            ['echo', 'writes output from PHP to the response'],
            ['POST method', 'sends submitted form values in the HTTP request body'],
            ['superglobal', 'is a built-in PHP variable available across scopes'],
            ['PDO prepared statement', 'keeps SQL parameter values separate from query syntax'],
            ['htmlspecialchars', 'escapes special characters for safe HTML output'],
            ['session', 'stores server-side state associated with a browser session'],
            ['password_hash', 'creates a one-way password hash for secure storage'],
            ['CSRF token', 'helps verify that a state-changing request came from the intended session'],
            ['filter_input', 'retrieves and optionally validates a value from an input source'],
        ]],
        'aspnet-core-mvc' => ['ASP.NET Core MVC', [
            ['controller', 'handles requests and coordinates application behavior'],
            ['action method', 'responds to a route by returning a result'],
            ['model', 'represents application data and may include validation rules'],
            ['view', 'renders a user interface from data supplied by an action'],
            ['routing', 'maps an incoming URL and HTTP method to an endpoint'],
            ['model binding', 'maps request data to action parameters or model properties'],
            ['dependency injection', 'provides registered services to application components'],
            ['middleware', 'processes requests and responses in an ordered pipeline'],
            ['Razor', 'combines HTML markup with server-side C# expressions'],
            ['antiforgery token', 'helps protect state-changing form requests from CSRF'],
        ]],
        'databases-sql' => ['Databases and SQL', [
            ['primary key', 'uniquely identifies each row in a table'],
            ['foreign key', 'enforces a relationship to a key in another table'],
            ['SELECT', 'reads rows and columns from a database'],
            ['WHERE', 'filters rows according to a condition'],
            ['JOIN', 'combines related rows from multiple tables'],
            ['INSERT', 'adds new rows to a table'],
            ['UPDATE', 'changes values in existing rows'],
            ['transaction', 'groups database operations into a unit of work'],
            ['index', 'can speed up lookups at the cost of storage and write overhead'],
            ['prepared statement', 'separates SQL code from supplied parameter values'],
        ]],
        'python-programming' => ['Python Programming', [
            ['indentation', 'defines code blocks in Python'],
            ['list', 'is an ordered and mutable collection of values'],
            ['tuple', 'is an ordered collection that cannot be modified after creation'],
            ['dictionary', 'maps keys to values'],
            ['def', 'begins a function definition in Python'],
            ['for loop', 'iterates over items in an iterable'],
            ['exception', 'signals an error or unusual runtime condition'],
            ['with statement', 'manages a context such as safely closing a file'],
            ['virtual environment', 'isolates a project’s Python packages'],
            ['list comprehension', 'creates a list using a compact expression over an iterable'],
        ]],
        'artificial-intelligence' => ['Artificial Intelligence', [
            ['machine learning', 'uses data to train a model to perform a task'],
            ['training data', 'provides examples from which a model learns patterns'],
            ['classification', 'predicts a category for an input'],
            ['regression', 'predicts a numeric value from input features'],
            ['overfitting', 'occurs when a model memorizes training examples and generalizes poorly'],
            ['inference', 'uses a trained model to produce a prediction'],
            ['feature', 'is an input variable used by a model'],
            ['bias', 'can cause systematic errors or unfair outcomes in model results'],
            ['validation set', 'helps tune a model without using the final test set'],
            ['human oversight', 'helps identify harmful or incorrect AI outputs before relying on them'],
        ]],
        'data-science' => ['Data Science', [
            ['data cleaning', 'finds and corrects missing, inconsistent, or invalid data'],
            ['median', 'is the middle value of ordered observations'],
            ['mean', 'is the sum of values divided by their count'],
            ['outlier', 'is an observation unusually far from the rest of the data'],
            ['visualization', 'communicates patterns in data using graphical representations'],
            ['correlation', 'measures how two variables vary together'],
            ['sampling', 'selects a subset of a population for analysis'],
            ['data leakage', 'uses information unavailable at prediction time during model training'],
            ['reproducibility', 'allows an analysis to be repeated with the same inputs and methods'],
            ['exploratory analysis', 'summarizes and investigates data before formal modeling'],
        ]],
        'software-testing' => ['Software Testing', [
            ['test case', 'specifies inputs, preconditions, and expected behavior to check'],
            ['unit test', 'checks a small isolated piece of software'],
            ['integration test', 'checks whether multiple components work together'],
            ['regression test', 'checks that existing behavior still works after a change'],
            ['assertion', 'states an expected condition that a test verifies'],
            ['test coverage', 'measures which code or behavior is exercised by tests'],
            ['boundary value', 'is an input at or near the edge of an allowed range'],
            ['mock', 'replaces a dependency with a controlled test double'],
            ['defect report', 'records reproduction steps and observed versus expected behavior'],
            ['exploratory testing', 'uses learning and investigation to guide test execution'],
        ]],
        'project-management' => ['Project Management', [
            ['project scope', 'defines the work and outcomes included in a project'],
            ['milestone', 'marks a significant point or deliverable in a project schedule'],
            ['stakeholder', 'is a person or group affected by or able to affect a project'],
            ['risk', 'is an uncertain event that could affect project objectives'],
            ['backlog', 'is an ordered list of work that may be done by a team'],
            ['dependency', 'is a relationship where one task relies on another'],
            ['retrospective', 'reviews how a team worked and identifies improvements'],
            ['acceptance criteria', 'describe conditions a deliverable must satisfy'],
            ['critical path', 'is the longest dependent sequence that determines shortest project duration'],
            ['change control', 'evaluates and manages proposed changes to agreed project scope'],
        ]],
        'certification-test' => ['Certification Preparation', [
            ['practice exam', 'helps identify knowledge gaps under exam-like conditions'],
            ['exam blueprint', 'describes topics and their relative emphasis on an exam'],
            ['active recall', 'strengthens memory by retrieving information without looking at notes'],
            ['spaced practice', 'revisits material over increasing intervals to improve retention'],
            ['time management', 'allocates exam time so questions receive appropriate attention'],
            ['distractor', 'is an incorrect answer choice designed to test understanding'],
            ['review flag', 'marks a question to revisit if time remains'],
            ['elimination', 'removes clearly incorrect choices before selecting an answer'],
            ['objective', 'states a skill or knowledge area that an exam may assess'],
            ['post-exam review', 'uses missed questions to plan focused follow-up study'],
        ]],
        'job-interview-prep' => ['Job Interview Preparation', [
            ['STAR method', 'organizes an interview example as situation, task, action, and result'],
            ['clarifying question', 'resolves ambiguity before proposing a technical solution'],
            ['complexity analysis', 'explains how an algorithm’s resource use grows with input size'],
            ['trade-off', 'describes a benefit gained at the cost of another consideration'],
            ['active listening', 'uses focused attention and follow-up questions to understand a prompt'],
            ['behavioral example', 'uses a specific past experience to demonstrate a skill'],
            ['test case', 'checks a solution against an input and an expected outcome'],
            ['edge case', 'tests unusual or boundary inputs that may expose a defect'],
            ['structured explanation', 'communicates a problem, approach, and reasoning in a clear order'],
            ['follow-up', 'revisits an interview topic to clarify reasoning or improve an answer'],
        ]],
    ];

    $additionalFacts = [
        'computer-basics' => [
            ['cache memory', 'keeps frequently used data close to the processor to reduce access latency'],
            ['clock speed', 'describes how many processor cycles occur per second but does not alone determine performance'],
            ['core', 'is an independent processing unit within a processor package'],
            ['volatile memory', 'requires power to retain its current contents'],
            ['file system', 'organizes named files and directories on a storage device'],
            ['device driver', 'lets the operating system communicate with a hardware device'],
            ['power supply', 'converts incoming electrical power into suitable power for computer components'],
            ['network adapter', 'connects a computer to a wired or wireless network'],
        ],
        'programming-logic' => [
            ['sequence', 'executes instructions in a deliberate order'],
            ['Boolean expression', 'evaluates to true or false and can control a decision'],
            ['comparison operator', 'compares values to produce a Boolean result'],
            ['logical operator', 'combines or negates Boolean conditions'],
            ['counter loop', 'repeats a known number of times using an initialized counter'],
            ['sentinel value', 'signals that an input sequence or loop should stop'],
            ['parameter', 'names an input a function receives when it is called'],
            ['return value', 'sends a computed result from a function to its caller'],
            ['trace table', 'records changing variable values while following an algorithm'],
            ['decomposition', 'breaks a complex problem into smaller independently solvable parts'],
        ],
        'easy-cpp-java' => [
            ['pointer', 'stores a memory address and requires careful lifetime and access management'],
            ['value type', 'stores a value directly or as part of its containing object'],
            ['reference type', 'refers to an object whose identity and lifetime are managed separately'],
            ['constructor overload', 'provides multiple valid ways to initialize an object'],
            ['const correctness', 'marks values and operations that must not modify an object'],
            ['vector', 'provides a resizable contiguous sequence in standard C++'],
            ['RAII', 'ties resource acquisition and release to object lifetime in C++'],
            ['garbage collection', 'reclaims unreachable managed objects automatically'],
            ['generic type', 'allows reusable code to operate with specified type parameters'],
            ['access modifier', 'controls which code can use a class member'],
            ['static member', 'belongs to a type rather than a particular object instance'],
            ['compile-time error', 'is reported while source code is translated rather than during normal execution'],
            ['runtime exception', 'interrupts ordinary control flow when an exceptional condition occurs'],
            ['package', 'groups related Java types into a named namespace'],
            ['header file', 'declares C++ interfaces that can be included by source files'],
            ['linker', 'combines compiled units and libraries into a program'],
            ['stack frame', 'holds call-specific state such as local variables and return information'],
            ['heap allocation', 'reserves dynamic memory whose lifetime is not limited to one local scope'],
            ['unit test', 'checks a small behavior with controlled inputs and expected results'],
            ['build configuration', 'defines compiler settings, dependencies, and target outputs'],
        ],
        'easy-csharp-programming' => [
            ['nullable reference type', 'helps identify references that may be absent and require null handling'],
            ['record type', 'models data-centric values with concise value-oriented equality'],
            ['generic constraint', 'limits which types can be supplied to a generic type parameter'],
            ['Task<T>', 'represents asynchronous work that eventually produces a value of type T'],
            ['cancellation token', 'communicates a cooperative request to stop asynchronous work'],
            ['extension method', 'adds callable method syntax to a type without modifying that type'],
            ['entity framework core', 'maps .NET objects and queries to relational database operations'],
            ['configuration provider', 'loads application settings from a source such as JSON or environment variables'],
            ['logging provider', 'routes structured application events to a chosen output'],
            ['middleware pipeline', 'runs ordered request and response components in an ASP.NET application'],
            ['unit test fixture', 'sets up reusable test dependencies and cleanup for a group of tests'],
            ['NuGet package', 'distributes reusable .NET libraries and their dependency metadata'],
        ],
        'object-oriented-coding' => [
            ['single responsibility', 'keeps a class focused on one cohesive reason to change'],
            ['open-closed principle', 'encourages extension of behavior without repeatedly modifying stable code'],
            ['Liskov substitution', 'requires derived types to remain usable wherever their base contract is expected'],
            ['interface segregation', 'keeps contracts focused so clients do not depend on unused operations'],
            ['dependency inversion', 'makes high-level policy depend on abstractions rather than concrete details'],
            ['immutable object', 'cannot have its observable state changed after construction'],
            ['accessor', 'controls reading or writing a property while preserving class invariants'],
            ['factory method', 'centralizes object creation when construction choices vary'],
            ['strategy pattern', 'encapsulates interchangeable algorithms behind a common contract'],
            ['observer pattern', 'notifies subscribed dependents when a subject changes state'],
            ['value object', 'represents a concept by its attributes rather than a unique identity'],
            ['aggregate', 'groups domain objects under a consistency boundary and root'],
        ],
        'data-structures-made-easy' => [
            ['dynamic array', 'resizes a contiguous indexed sequence while keeping amortized append efficient'],
            ['circular buffer', 'reuses a fixed array by wrapping read and write positions around its ends'],
            ['deque', 'supports efficient insertion and removal at both ends'],
            ['priority queue', 'returns the next element according to an explicit priority ordering'],
            ['trie', 'shares prefixes in a tree structure for efficient string or key lookup'],
            ['balanced tree', 'maintains a height bound so ordered operations avoid a long skewed path'],
            ['adjacency list', 'stores each graph vertex with the vertices connected to it'],
            ['adjacency matrix', 'stores graph connectivity in a two-dimensional indexed structure'],
            ['disjoint set', 'tracks connected components with efficient union and find operations'],
            ['bloom filter', 'tests set membership probabilistically with possible false positives'],
            ['load factor', 'measures occupied entries relative to hash table capacity'],
            ['hash collision', 'occurs when distinct keys map to the same hash table location'],
            ['open addressing', 'resolves hash collisions by probing other slots in the table'],
            ['separate chaining', 'stores colliding hash entries together in a bucket collection'],
            ['inorder traversal', 'visits a binary search tree in sorted key order'],
            ['breadth-first traversal', 'visits graph or tree nodes level by level using a queue'],
            ['depth-first traversal', 'explores a path deeply before backtracking to alternatives'],
            ['sparse graph', 'has substantially fewer edges than a complete graph on the same vertices'],
            ['amortized analysis', 'bounds average operation cost across a sequence of operations'],
            ['iterator', 'provides a standard way to visit elements without exposing collection internals'],
        ],
        'algorithms-logic' => [
            ['stable sort', 'preserves the relative order of equal-key items'],
            ['merge sort', 'sorts by dividing the input, sorting parts, and merging them'],
            ['quicksort partition', 'rearranges values around a pivot before sorting the resulting ranges'],
            ['insertion sort', 'builds a sorted prefix by inserting each next item in position'],
            ['two-pointer technique', 'moves two indices through data to solve a scan efficiently'],
            ['sliding window', 'updates a contiguous range incrementally instead of recomputing it'],
            ['prefix sum', 'precomputes cumulative totals to answer range-sum queries efficiently'],
            ['divide and conquer', 'splits a problem into smaller instances and combines their solutions'],
            ['dynamic programming', 'reuses solutions to overlapping subproblems under a recurrence'],
            ['memoization', 'caches results of function calls so repeated subproblems are not recomputed'],
            ['topological sort', 'orders directed acyclic graph vertices before their dependents'],
            ['Dijkstra algorithm', 'finds shortest paths from a source when edge weights are nonnegative'],
            ['breadth-first search', 'finds shortest paths in an unweighted graph by exploring distance layers'],
            ['depth-first search', 'explores graph paths and supports reachability and cycle analysis'],
            ['invariant', 'is a condition that must remain true throughout an algorithm'],
            ['worst-case complexity', 'bounds resource use for the most demanding input of a given size'],
            ['space complexity', 'describes how additional memory use grows with input size'],
            ['lower bound', 'proves a minimum resource requirement for a class of algorithmic problems'],
            ['short-circuit evaluation', 'skips a Boolean operand when the result is already determined'],
            ['overflow check', 'detects arithmetic results that exceed a numeric type’s representable range'],
        ],
        'website-design' => [
            ['box model', 'defines how content, padding, border, and margin contribute to element size'],
            ['cascade', 'resolves competing CSS declarations using origin, importance, specificity, and order'],
            ['specificity', 'ranks selectors when the cascade chooses a declaration'],
            ['focus indicator', 'visually shows which interactive control receives keyboard input'],
            ['keyboard navigation', 'allows users to operate interactive controls without a pointer'],
            ['responsive image', 'delivers an appropriate image source and size for the viewing context'],
            ['CSS custom property', 'stores a reusable CSS value that can be referenced by declarations'],
            ['progressive enhancement', 'provides core content first and layers richer capabilities where supported'],
        ],
        'javascript-basics' => [
            ['closure', 'retains access to lexical variables after the outer function has returned'],
            ['scope', 'determines where a binding can be accessed in a program'],
            ['rest parameter', 'collects remaining function arguments into an array'],
            ['destructuring', 'extracts values from arrays or properties from objects into bindings'],
            ['map method', 'creates a new array by transforming each element'],
            ['filter method', 'creates a new array containing elements that pass a predicate'],
            ['async function', 'returns a promise and allows asynchronous operations to be awaited'],
            ['try-catch', 'handles synchronous exceptions and rejected awaited operations'],
            ['event delegation', 'handles descendant events through a listener on a stable ancestor'],
            ['local storage', 'persists string data in a browser origin across page reloads'],
            ['module export', 'makes a binding available to other JavaScript modules'],
            ['nullish coalescing', 'uses a fallback only when a value is null or undefined'],
        ],
        'modern-web-apps' => [
            ['idempotent request', 'can be repeated without changing the intended result after the first success'],
            ['HTTP status code', 'communicates the outcome category of a server response'],
            ['content negotiation', 'lets a client and server agree on a representation format'],
            ['CORS policy', 'controls which browser origins may access a cross-origin response'],
            ['rate limiting', 'restricts request frequency to protect service capacity and fairness'],
            ['pagination', 'returns a large result set in manageable segments'],
            ['optimistic concurrency', 'detects conflicting writes using a version or validator'],
            ['ETag', 'identifies a particular representation version for caching or conditional requests'],
            ['service worker', 'intercepts eligible browser requests and enables offline or cached behavior'],
            ['code splitting', 'loads application code in smaller chunks as routes or features need them'],
            ['content security policy', 'restricts allowed content sources to reduce script injection impact'],
            ['observability', 'uses logs, metrics, and traces to explain system behavior in production'],
            ['health check', 'reports whether a service or one of its dependencies can operate'],
            ['database migration', 'applies a versioned and repeatable change to persistent schema'],
            ['API versioning', 'allows a service to evolve contracts while managing client compatibility'],
            ['request validation', 'rejects malformed or unauthorized data at the service boundary'],
            ['structured logging', 'records queryable event fields instead of relying only on free-form text'],
            ['retry policy', 'repeats transiently failed operations with bounded delay and limits'],
            ['circuit breaker', 'temporarily stops calls to a failing dependency to prevent cascading failures'],
            ['secret management', 'stores credentials outside source code and limits their exposure'],
        ],
        'php-web-development' => [
            ['strict types', 'asks PHP to enforce declared scalar parameter and return types'],
            ['type declaration', 'documents and checks the accepted types of parameters and results'],
            ['associative array', 'maps string or integer keys to values in a PHP array'],
            ['namespace declaration', 'places PHP symbols in a named scope to avoid collisions'],
            ['composer autoloading', 'loads PHP classes according to configured namespace mappings'],
            ['HTTP response header', 'sends metadata such as content type or cache policy before the body'],
            ['output encoding', 'escapes data for its specific HTML, attribute, URL, or JavaScript context'],
            ['password verification', 'checks a submitted password against a stored password hash'],
            ['session regeneration', 'rotates the session identifier after an authentication state change'],
            ['file upload validation', 'checks uploaded file errors, size, type, and safe storage location'],
            ['PDO transaction', 'groups database changes so they can commit together or roll back'],
            ['SQL parameter binding', 'passes data values separately from SQL syntax in a prepared statement'],
            ['CSRF verification', 'compares a session-bound token before accepting a state-changing request'],
            ['authorization check', 'confirms the current identity may perform the requested operation'],
            ['request method validation', 'accepts only the HTTP methods supported by an endpoint'],
            ['JSON response', 'serializes structured server data with the correct content type and status'],
            ['exception boundary', 'translates internal failures into controlled responses while retaining logs'],
            ['dependency injection', 'passes a required service into a component instead of constructing it internally'],
            ['unit test', 'verifies a PHP function or class behavior with controlled dependencies'],
            ['integration test', 'checks PHP request, persistence, and service behavior across component boundaries'],
            ['configuration secret', 'loads sensitive deployment values from protected environment configuration'],
            ['database migration', 'changes schema through a tracked, repeatable deployment step'],
        ],
        'aspnet-core-mvc' => [
            ['endpoint filter', 'runs reusable validation or policy logic around a minimal API endpoint'],
            ['authorization policy', 'expresses reusable requirements for access to an endpoint or resource'],
            ['claims principal', 'represents an authenticated identity and its associated claims'],
            ['model validation', 'checks bound input against declared rules before application processing'],
            ['action result', 'describes the HTTP response or view result returned by an MVC action'],
            ['partial view', 'renders a reusable fragment of Razor markup'],
            ['tag helper', 'adds server-side behavior to an HTML-like Razor element'],
            ['options pattern', 'binds related configuration values to a typed options object'],
            ['scoped service', 'lives for one request scope and is disposed when that scope ends'],
            ['hosted service', 'runs background work under the ASP.NET Core host lifecycle'],
            ['EF Core migration', 'records a versioned change to an Entity Framework Core schema'],
            ['asynchronous action', 'awaits nonblocking I/O and returns a task-backed MVC result'],
            ['request cancellation', 'stops request work when the client disconnects or its deadline expires'],
            ['problem details', 'returns a standardized machine-readable description of an HTTP error'],
            ['output caching', 'stores selected endpoint responses to reduce repeated server work'],
            ['health check registration', 'exposes checks for application readiness and dependency health'],
            ['structured logging', 'records named fields and event identifiers for searchable diagnostics'],
            ['global exception handler', 'converts unhandled request failures into a controlled error response'],
            ['integration test host', 'runs an ASP.NET application in-process for end-to-end request tests'],
            ['environment configuration', 'overrides deployment-specific settings without editing application code'],
        ],
        'databases-sql' => [
            ['normalization', 'reduces update anomalies by organizing facts into related tables'],
            ['unique constraint', 'prevents duplicate values in a designated column or column set'],
            ['check constraint', 'rejects rows whose values violate a declared Boolean condition'],
            ['one-to-many relationship', 'links one parent row to multiple child rows through a foreign key'],
            ['many-to-many relationship', 'connects two entity sets through a junction table'],
            ['composite index', 'indexes multiple columns in a defined order for matching query patterns'],
            ['query plan', 'shows how a database intends to access and combine rows'],
            ['aggregate function', 'calculates a summary such as count, sum, minimum, or average'],
            ['GROUP BY', 'forms groups of rows for aggregate calculations'],
            ['HAVING', 'filters grouped results after aggregate expressions are evaluated'],
            ['isolation level', 'defines which concurrent transaction effects may be observed'],
            ['deadlock', 'occurs when transactions wait cyclically for resources held by each other'],
        ],
        'python-programming' => [
            ['type hint', 'documents expected Python value types for tools and readers'],
            ['generator', 'yields values lazily instead of materializing the entire sequence'],
            ['iterator protocol', 'uses iteration methods to produce values one at a time'],
            ['decorator', 'wraps a callable to add behavior while preserving a reusable interface'],
            ['lambda expression', 'creates a small anonymous function expression'],
            ['dataclass', 'generates common methods for a class primarily storing data'],
            ['enumerate', 'iterates over values while also providing their index'],
            ['zip', 'iterates over corresponding values from multiple iterables'],
            ['pytest fixture', 'provides reusable setup and teardown for tests'],
            ['package', 'organizes importable Python modules under a project namespace'],
            ['requirements lock', 'records exact dependency versions for repeatable installation'],
            ['logging module', 'emits severity-aware diagnostic events instead of ad hoc prints'],
            ['pathlib', 'provides object-oriented operations for filesystem paths'],
            ['context manager', 'acquires and reliably releases a resource around a with block'],
            ['JSON serialization', 'converts supported Python values to a JSON representation'],
            ['type checker', 'finds likely type mismatches by analyzing annotations and code'],
            ['mutable default argument', 'can accidentally retain changes across calls when a shared object is used'],
            ['exception chaining', 'preserves the underlying cause while raising a more meaningful error'],
            ['command-line parser', 'defines and validates options supplied to a Python program'],
            ['dependency pinning', 'constrains package versions to make environments predictable'],
        ],
        'artificial-intelligence' => [
            ['supervised learning', 'learns a mapping from labeled examples to target outputs'],
            ['unsupervised learning', 'finds structure in data without supplied target labels'],
            ['feature engineering', 'transforms raw inputs into signals useful to a learning algorithm'],
            ['data split', 'separates training, validation, and test examples to measure generalization'],
            ['precision', 'measures the fraction of predicted positives that are correct'],
            ['recall', 'measures the fraction of actual positives that are found'],
            ['confusion matrix', 'counts predicted and actual class combinations'],
            ['class imbalance', 'occurs when target categories have substantially different frequencies'],
            ['regularization', 'penalizes model complexity to reduce overfitting risk'],
            ['cross-validation', 'estimates generalization by repeating evaluation over data folds'],
            ['threshold tuning', 'chooses a decision cutoff based on error costs and validation evidence'],
            ['model drift', 'describes degraded model behavior as data or operating conditions change'],
            ['data provenance', 'records where training or evaluation data originated and how it was transformed'],
            ['fairness metric', 'measures an aspect of disparate model outcomes across groups'],
            ['explainability', 'helps people understand factors influencing a model output'],
            ['prompt injection', 'is untrusted input that attempts to override instructions to an AI system'],
            ['retrieval augmented generation', 'grounds generated responses in retrieved source material'],
            ['model card', 'documents a model’s intended use, evaluation, limitations, and risks'],
            ['human escalation', 'routes uncertain or high-impact predictions to a qualified reviewer'],
            ['production monitoring', 'tracks quality, safety, latency, and drift after model deployment'],
        ],
        'data-science' => [
            ['data type', 'describes whether a field contains numeric, categorical, temporal, or textual values'],
            ['missingness analysis', 'investigates which values are absent and whether absence has a pattern'],
            ['categorical encoding', 'maps category labels into a representation suitable for analysis or modeling'],
            ['standardization', 'scales numeric values using a center and spread estimate'],
            ['confidence interval', 'expresses uncertainty around an estimated population quantity'],
            ['hypothesis test', 'evaluates whether sample evidence is inconsistent with a specified null model'],
            ['p-value', 'measures how surprising data at least this extreme would be under a null hypothesis'],
            ['stratified sampling', 'preserves selected subgroup proportions when drawing a sample'],
            ['train-test split', 'holds out observations to estimate performance on unseen data'],
            ['feature leakage', 'lets unavailable or target-derived information distort model evaluation'],
            ['data dictionary', 'documents fields, types, meanings, and allowed values in a dataset'],
            ['dashboard metric', 'summarizes a clearly defined measure for repeated decision monitoring'],
        ],
        'software-testing' => [
            ['test double', 'substitutes controlled behavior for a dependency during a test'],
            ['parameterized test', 'runs the same assertion logic against multiple input cases'],
            ['property-based test', 'checks general properties over many generated inputs'],
            ['end-to-end test', 'verifies a user-visible flow across integrated application boundaries'],
            ['test pyramid', 'balances fast focused checks with fewer broad integration checks'],
            ['mutation testing', 'checks whether tests detect deliberate small changes to program behavior'],
            ['test fixture', 'provides known setup data and state for a repeatable test'],
            ['flaky test', 'passes and fails inconsistently without a relevant code change'],
            ['test isolation', 'prevents one test’s state from changing another test’s result'],
            ['continuous integration', 'runs automated checks when changes are proposed or integrated'],
        ],
        'project-management' => [
            ['work breakdown structure', 'decomposes project scope into manageable deliverables and work packages'],
            ['effort estimate', 'forecasts work using explicit assumptions, uncertainty, and available evidence'],
            ['risk response', 'defines an owner and action for reducing or accepting a project risk'],
            ['definition of done', 'sets shared completion criteria for a deliverable or increment'],
            ['scope baseline', 'records the approved scope used to evaluate proposed changes'],
            ['burndown chart', 'visualizes remaining planned work over a time period'],
            ['resource allocation', 'assigns available people and capacity to prioritized work'],
            ['lessons learned', 'captures evidence-based improvements for current or future delivery'],
        ],
        'certification-test' => [
            ['diagnostic assessment', 'identifies a learner’s current strengths and gaps before focused study'],
            ['retrieval practice', 'improves recall by requiring an answer before reviewing the material'],
            ['error log', 'records missed concepts and the reason an answer was incorrect'],
            ['exam pacing', 'allocates limited time according to question value and uncertainty'],
            ['confidence rating', 'records certainty so review effort can target fragile knowledge'],
        ],
        'job-interview-prep' => [
            ['requirements clarification', 'confirms constraints and expected behavior before implementation'],
            ['complexity trade-off', 'compares runtime and memory costs of alternative approaches'],
            ['test-driven explanation', 'uses examples and edge cases to make solution correctness clear'],
            ['incremental refinement', 'starts with a correct simple approach before optimizing bottlenecks'],
            ['system design boundary', 'identifies which responsibilities belong in each service or component'],
            ['assumption statement', 'makes an uncertain interpretation explicit so it can be confirmed'],
            ['communication checkpoint', 'summarizes progress and invites feedback during a technical discussion'],
            ['post-interview reflection', 'turns feedback and uncertainty into a targeted practice plan'],
        ],
    ];

    foreach ($additionalFacts as $slug => $facts) {
        $modules[$slug][1] = array_merge($modules[$slug][1], $facts);
    }

    return $modules;
}

function learnify_quiz_code_example(string $moduleSlug, string $term): ?string
{
    $examples = [
        'computer-basics' => [
            'RAM' => '$workingSet = ["editor", "document"]; // data actively used by running programs',
        ],
        'programming-logic' => [
            'condition' => 'if ($total <= $budget) {' . "\n" . '    placeOrder();' . "\n" . '}',
        ],
        'easy-cpp-java' => [
            'array' => 'int values[] = {4, 8, 12};' . "\n" . 'System.out.println(values[1]);',
        ],
        'easy-csharp-programming' => [
            'async method' => 'public async Task<Order> LoadAsync(int id)' . "\n"
                . '{' . "\n" . '    return await repository.FindAsync(id);' . "\n" . '}',
        ],
        'object-oriented-coding' => [
            'encapsulation' => 'public sealed class Account' . "\n" . '{' . "\n"
                . '    private decimal balance;' . "\n" . '    public decimal Balance => balance;' . "\n" . '}',
        ],
        'data-structures-made-easy' => [
            'queue' => '$requests = new SplQueue();' . "\n"
                . '$requests->enqueue($firstRequest);' . "\n" . '$next = $requests->dequeue();',
        ],
        'algorithms-logic' => [
            'binary search' => 'while ($low <= $high) {' . "\n"
                . '    $middle = intdiv($low + $high, 2);' . "\n"
                . '    if ($values[$middle] < $target) $low = $middle + 1;' . "\n" . '}',
        ],
        'website-design' => [
            'semantic HTML' => '<main>' . "\n" . '  <h1>Course overview</h1>' . "\n" . '</main>',
        ],
        'javascript-basics' => [
            'Promise' => 'fetch("/api/items")' . "\n"
                . '  .then(response => response.json())' . "\n"
                . '  .then(items => render(items));',
        ],
        'modern-web-apps' => [
            'REST API' => 'GET /api/courses/42' . "\n"
                . 'Accept: application/json' . "\n" . '' . "\n" . '200 OK',
        ],
        'php-web-development' => [
            'PDO prepared statement' => '$statement = $pdo->prepare("SELECT id FROM users WHERE email = :email");'
                . "\n" . '$statement->execute(["email" => $email]);',
        ],
        'aspnet-core-mvc' => [
            'controller' => '[HttpGet("/courses/{id:int}")]' . "\n"
                . 'public IActionResult Details(int id)' . "\n" . '{' . "\n"
                . '    return View(repository.Find(id));' . "\n" . '}',
        ],
        'databases-sql' => [
            'transaction' => 'BEGIN;' . "\n"
                . 'UPDATE inventory SET quantity = quantity - 1 WHERE id = :id;' . "\n"
                . 'COMMIT;',
        ],
        'python-programming' => [
            'with statement' => 'with open("notes.txt", encoding="utf-8") as file:' . "\n"
                . '    text = file.read()',
        ],
        'artificial-intelligence' => [
            'validation set' => 'model.fit(training_data)' . "\n"
                . 'tune_threshold(model, validation_data)' . "\n"
                . 'evaluate_once(model, test_data)',
        ],
        'data-science' => [
            'data leakage' => 'train, test = split(data, test_size=0.2)' . "\n"
                . 'pipeline.fit(train.features, train.labels)' . "\n"
                . 'score = pipeline.evaluate(test)',
        ],
        'software-testing' => [
            'unit test' => 'def test_rejects_negative_quantity():' . "\n"
                . '    result = checkout(quantity=-1)' . "\n"
                . '    assert result.error == "invalid quantity"',
        ],
        'project-management' => [
            'risk' => '$risk = ["likelihood" => 3, "impact" => 4];' . "\n"
                . '$priority = $risk["likelihood"] * $risk["impact"];',
        ],
        'certification-test' => [
            'exam blueprint' => '$objectives = readExamBlueprint();' . "\n"
                . '$gaps = selfAssess($objectives);' . "\n" . 'scheduleSpacedReview($gaps);',
        ],
        'job-interview-prep' => [
            'STAR method' => '$example = ["situation", "task", "action", "result"];' . "\n"
                . 'explainInOrder($example);',
        ],
    ];

    return $examples[$moduleSlug][$term] ?? null;
}

function learnify_seed_sample_quizzes(PDO $pdo): array
{
    $modules = learnify_quiz_curriculum();

    $column = $pdo->query("SHOW COLUMNS FROM questions LIKE 'question_type'")->fetch();
    if (!is_array($column)) {
        throw new RuntimeException('The questions.question_type column is missing.');
    }
    $questionType = (string) ($column['Type'] ?? $column['type'] ?? '');
    if (!str_contains($questionType, "'mcq'") || !str_contains($questionType, "'tf'") || !str_contains($questionType, "'fill'")) {
        $pdo->exec(
            "ALTER TABLE questions
             MODIFY question_type ENUM('multiple_choice', 'true_false', 'short_answer', 'mcq', 'tf', 'fill')
             NOT NULL DEFAULT 'multiple_choice'"
        );
    }
    $attemptModeColumn = $pdo->query("SHOW COLUMNS FROM quiz_attempts LIKE 'exam_mode'")->fetch();
    if (!is_array($attemptModeColumn)) {
        $pdo->exec(
            "ALTER TABLE quiz_attempts
             ADD exam_mode ENUM('practice', 'timed', 'certification') NOT NULL DEFAULT 'practice'
             AFTER quiz_id"
        );
    }
    $historyAttemptColumn = $pdo->query("SHOW COLUMNS FROM user_question_history LIKE 'attempt_id'")->fetch();
    if (!is_array($historyAttemptColumn)) {
        $pdo->exec(
            'ALTER TABLE user_question_history
             ADD attempt_id CHAR(64) NULL AFTER user_id,
             ADD KEY idx_question_history_attempt (attempt_id),
             ADD CONSTRAINT fk_question_history_attempt
                 FOREIGN KEY (attempt_id) REFERENCES quiz_attempts (attempt_id)
                 ON DELETE CASCADE ON UPDATE CASCADE'
        );
    }

    $insertedQuizzes = 0;
    $insertedQuestions = 0;
    $updatedQuestions = 0;
    $pdo->beginTransaction();

    try {
        $moduleStatement = $pdo->prepare(
            'SELECT id
             FROM modules
             WHERE slug = :slug AND is_published = 1
             LIMIT 1
             FOR UPDATE'
        );
        $quizStatement = $pdo->prepare(
            'SELECT id
             FROM quizzes
             WHERE module_id = :module_id AND title = :title
             LIMIT 1'
        );
        $insertQuiz = $pdo->prepare(
            'INSERT INTO quizzes
                (module_id, title, description, passing_score, time_limit_minutes, is_published)
             VALUES
                (:module_id, :title, :description, 70.00, 60, 1)'
        );
        $publishQuiz = $pdo->prepare('UPDATE quizzes SET is_published = 1 WHERE id = :quiz_id');
        $existingQuestions = $pdo->prepare(
            'SELECT id, question_text, question_type, options, correct_answer, explanation
             FROM questions
             WHERE quiz_id = :quiz_id'
        );
        $insertQuestion = $pdo->prepare(
            'INSERT INTO questions
                (quiz_id, question_text, question_type, options, correct_answer, explanation, points, display_order)
             VALUES
                (:quiz_id, :question_text, :question_type, :options, :correct_answer, :explanation, 1.00, :display_order)'
        );
        $updateQuestion = $pdo->prepare(
            'UPDATE questions
             SET question_type = :question_type,
                 options = :options,
                 correct_answer = :correct_answer,
                 explanation = :explanation
             WHERE id = :question_id'
        );

        foreach ($modules as $slug => [$moduleName, $facts]) {
            $moduleStatement->execute(['slug' => $slug]);
            $moduleId = $moduleStatement->fetchColumn();
            if ($moduleId === false) {
                continue;
            }

            $poolTitle = 'Learnify Question Pool';
            $quizStatement->execute(['module_id' => (int) $moduleId, 'title' => $poolTitle]);
            $quizId = $quizStatement->fetchColumn();
            if ($quizId === false) {
                $insertQuiz->execute([
                    'module_id' => (int) $moduleId,
                    'title' => $poolTitle,
                    'description' => 'A randomized question pool for ' . $moduleName . ' practice, timed, and certification exams.',
                ]);
                $quizId = (int) $pdo->lastInsertId();
                $insertedQuizzes++;
            } else {
                $quizId = (int) $quizId;
                $publishQuiz->execute(['quiz_id' => $quizId]);
            }

            $existingQuestions->execute(['quiz_id' => $quizId]);
            $existingQuestionTexts = [];
            foreach ($existingQuestions->fetchAll() as $existingQuestion) {
                $existingQuestionTexts[(string) $existingQuestion['question_text']] = $existingQuestion;
            }
            $questionNumber = 0;
            foreach ($facts as $factIndex => [$term, $definition]) {
                $term = trim($term);
                $otherDefinition = $facts[($factIndex + 1) % count($facts)][1];
                $nextDefinition = $facts[($factIndex + 2) % count($facts)][1];
                $thirdDefinition = $facts[($factIndex + 3) % count($facts)][1];
                $questionSeeds = [];

                foreach ([
                    'Which statement best describes ' . $term . '?',
                    'Choose the accurate description of ' . $term . '.',
                ] as $prompt) {
                    $choiceValues = [$definition, $otherDefinition, $nextDefinition, $thirdDefinition];
                    $rotation = $factIndex % count($choiceValues);
                    $choiceValues = array_merge(
                        array_slice($choiceValues, $rotation),
                        array_slice($choiceValues, 0, $rotation)
                    );
                    $answerLetter = chr(65 + (int) array_search($definition, $choiceValues, true));
                    $questionSeeds[] = [
                        'type' => 'mcq',
                        'text' => $prompt,
                        'options' => $choiceValues,
                        'answer' => $answerLetter,
                        'explanation' => ucfirst($term) . ' ' . $definition . '.',
                    ];
                    $questionNumber++;
                }

                $questionSeeds[] = [
                    'type' => 'tf',
                    'text' => 'True or false: ' . ucfirst($term) . ' ' . $definition . '.',
                    'options' => ['True', 'False'],
                    'answer' => 'True',
                    'explanation' => ucfirst($term) . ' ' . $definition . '.',
                ];
                $questionSeeds[] = [
                    'type' => 'tf',
                    'text' => 'True or false: ' . ucfirst($term) . ' ' . $otherDefinition . '.',
                    'options' => ['True', 'False'],
                    'answer' => 'False',
                    'explanation' => ucfirst($term) . ' ' . $definition . '.',
                ];
                $questionSeeds[] = [
                    'type' => 'fill',
                    'text' => 'Complete the statement: ' . ucfirst($definition) . '. The concept is ____.',
                    'options' => null,
                    'answer' => $term,
                    'explanation' => ucfirst($term) . ' ' . $definition . '.',
                ];

                $scenario = 'A team building a ' . $moduleName . ' feature must account for this requirement: '
                    . rtrim($definition, '.') . '.';
                $conceptOptions = [];
                for ($offset = 0; $offset < 4; $offset++) {
                    $conceptOptions[] = trim((string) $facts[($factIndex + $offset) % count($facts)][0]);
                }
                $conceptOptions = array_values(array_unique($conceptOptions));
                if (!in_array($term, $conceptOptions, true)) {
                    $conceptOptions[] = $term;
                }
                $conceptOptions = array_slice($conceptOptions, 0, 4);
                $questionSeeds[] = [
                    'type' => 'mcq',
                    'text' => $scenario . ' Which concept should the team apply?',
                    'options' => $conceptOptions,
                    'answer' => chr(65 + (int) array_search($term, $conceptOptions, true)),
                    'explanation' => ucfirst($term) . ' ' . $definition . '.',
                ];
                $questionSeeds[] = [
                    'type' => 'tf',
                    'text' => $scenario . ' This requirement is an example of ' . $term . '. True or false?',
                    'options' => ['True', 'False'],
                    'answer' => 'True',
                    'explanation' => ucfirst($term) . ' ' . $definition . '.',
                ];
                $questionSeeds[] = [
                    'type' => 'fill',
                    'text' => $scenario . ' Name the concept: ____.',
                    'options' => null,
                    'answer' => $term,
                    'explanation' => ucfirst($term) . ' ' . $definition . '.',
                ];
                $codeExample = learnify_quiz_code_example($slug, $term);
                if ($codeExample !== null) {
                    $questionSeeds[] = [
                        'type' => 'fill',
                        'text' => 'Code review for ' . $moduleName . ':' . "\n"
                            . $codeExample . "\n"
                            . 'Which lesson concept best explains this code? ____.',
                        'options' => null,
                        'answer' => $term,
                        'explanation' => ucfirst($term) . ' ' . $definition . '.',
                    ];
                }

                foreach ($questionSeeds as $seed) {
                    $questionValues = [
                        'question_type' => $seed['type'],
                        'options' => $seed['options'] === null
                            ? null
                            : json_encode($seed['options'], JSON_THROW_ON_ERROR),
                        'correct_answer' => $seed['answer'],
                        'explanation' => $seed['explanation'],
                    ];
                    if (isset($existingQuestionTexts[$seed['text']])) {
                        $existing = $existingQuestionTexts[$seed['text']];
                        try {
                            $existingOptions = $existing['options'] === null
                                ? null
                                : json_decode((string) $existing['options'], true, 16, JSON_THROW_ON_ERROR);
                        } catch (JsonException) {
                            $existingOptions = false;
                        }
                        if (
                            (string) $existing['question_type'] === $questionValues['question_type']
                            && $existingOptions === $seed['options']
                            && (string) $existing['correct_answer'] === $questionValues['correct_answer']
                            && (string) ($existing['explanation'] ?? '') === $questionValues['explanation']
                        ) {
                            continue;
                        }
                        $updateQuestion->execute($questionValues + [
                            'question_id' => (int) $existing['id'],
                        ]);
                        $updatedQuestions++;
                        continue;
                    }

                    $insertQuestion->execute([
                        'quiz_id' => $quizId,
                        'question_text' => $seed['text'],
                    ] + $questionValues + [
                        'display_order' => ++$questionNumber,
                    ]);
                    $insertedQuestions++;
                    $existingQuestionTexts[$seed['text']] = [
                        'id' => (int) $pdo->lastInsertId(),
                        'question_type' => $questionValues['question_type'],
                        'options' => $questionValues['options'],
                        'correct_answer' => $questionValues['correct_answer'],
                        'explanation' => $questionValues['explanation'],
                    ];
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

    return [
        'quizzes_added' => $insertedQuizzes,
        'questions_added' => $insertedQuestions,
        'questions_updated' => $updatedQuestions,
    ];
}

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath((string) $_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    require_once __DIR__ . '/../includes/bootstrap.php';

    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');

    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        header('Allow: POST');
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Use POST to run the quiz seeder.']);
        exit;
    }
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Please log in to seed sample quizzes.']);
        exit;
    }
    if (!learnify_csrf_is_valid($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Your session token is invalid. Refresh and try again.']);
        exit;
    }

    try {
        $result = learnify_seed_sample_quizzes($pdo);
        echo json_encode(['success' => true] + $result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    } catch (Throwable $exception) {
        error_log('Learnify quiz seeding failed: ' . $exception->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Sample quizzes could not be created.']);
    }
}
