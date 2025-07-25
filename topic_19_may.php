<?php
// Start session only if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set cache control headers
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../app/helpers.php';
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/models/User.php';

// Initialize User model
$userModel = new User($pdo);

// Get topic slug from URL
$topic_slug = $_GET['topic'] ?? '';
if (empty($topic_slug)) {
    header("Location: " . BASE_URL . "/public/error.php?code=400");
    exit;
}

// Fetch topic
$stmt = $pdo->prepare("SELECT * FROM topics WHERE slug = :slug LIMIT 1");
$stmt->execute(['slug' => $topic_slug]);
$topic = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$topic) {
    header("Location: " . BASE_URL . "/public/error.php?code=404");
    exit;
}

// Fetch subtopics in ascending order by name
$stmt = $pdo->prepare("SELECT * FROM subtopics WHERE topic_id = :topic_id ORDER BY name ASC");
$stmt->execute(['topic_id' => $topic['id']]);
$subtopics = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Determine user status
$userId = $_SESSION['user']['id'] ?? null;
$userStatus = $userId ? 'logged_in' : 'guest';

// Display all subtopics for both logged-in and guest users
$display_subtopics = $subtopics;

// Prepare subtopic IDs for JavaScript to clear localStorage
$subtopicIds = array_column($subtopics, 'id');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" />
    <title><?php echo htmlspecialchars($topic['name']); ?> | Digital SAT Practice</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        body { font-family: 'Inter', sans-serif; }
        .shadow-soft { box-shadow: 0 10px 40px -10px rgba(0,0,0,0.08); }
        .hover-scale { transition: transform 0.2s; }
        .hover-scale:hover { transform: translateY(-2px); }
        .locked { background-color: #e5e7eb; cursor: not-allowed; }
        .locked:hover { background-color: #e5e7eb; }
        .lock-icon { margin-left: 4px; }
        .tutor-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; }
        .tutor-modal-content { background: white; padding: 1.5rem; border-radius: 0.5rem; width: 90%; max-width: 400px; }
        .tutor-modal-content h3 { font-size: 1.25rem; font-weight: 600; margin-bottom: 1rem; }
        .tutor-modal-content button { width: 100%; padding: 0.75rem; margin-bottom: 0.5rem; border-radius: 0.25rem; font-weight: 500; }
        @media (max-width: 640px) {
            .mobile-padding { padding: 1rem !important; }
            .mobile-text { font-size: 1.25rem; line-height: 1.75rem; }
            .tutor-modal-content { width: 95%; }
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <aside id="sidebar" class="w-64 bg-gradient-to-b from-blue-900 to-blue-800 shadow-xl h-screen overflow-y-auto fixed md:static left-0 top-0 transform -translate-x-full md:translate-x-0 transition-transform duration-300 z-50">
            <button id="closeSidebar" class="md:hidden absolute top-4 right-4 bg-red-500 text-white p-2 rounded-full">
                <i class="fas fa-times"></i>
            </button>
            <?php require __DIR__ . '/../app/views/layout/sidebar.php'; ?>
        </aside>
        <div class="flex flex-col flex-1">
            <header class="sticky top-0 bg-white shadow-md z-10">
                <?php require __DIR__ . '/../app/views/layout/header.php'; ?>
            </header>
            <main class="flex-1 overflow-y-auto pt-4 px-2 sm:px-6 lg:px-8">
                <div class="mb-4 sm:mb-8 p-4 sm:p-8 bg-gradient-to-r from-blue-600 to-indigo-700 rounded-xl sm:rounded-2xl shadow-soft relative overflow-hidden text-white">
                    <div class="absolute right-2 sm:right-4 top-2 sm:top-4 opacity-20">
                        <svg class="w-20 h-20 sm:w-32 sm:h-32 text-white" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12 2l-5.5 9h11L12 2zm0 3.84L13.93 9h-3.87L12 5.84zM17.5 13c-2.49 0-4.5 2.01-4.5 4.5s2.01 4.5 4.5 4.5 4.5-2.01 4.5-4.5-2.01-4.5-4.5-4.5zm0 7a2.5 2.5 0 010-5 2.5 2.5 0 010 5z"/>
                        </svg>
                    </div>
                    <div class="flex flex-col sm:flex-row items-start sm:items-center gap-4 relative">
                        <div class="p-3 sm:p-4 bg-white/10 rounded-lg sm:rounded-xl backdrop-blur-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 sm:h-10 sm:w-10 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253"/>
                            </svg>
                        </div>
                        <div class="space-y-1 sm:space-y-2">
                            <h1 class="text-2xl sm:text-4xl font-bold text-white capitalize tracking-tight">
                                <?php echo htmlspecialchars($topic['name']); ?>
                            </h1>
                            <p class="text-sm sm:text-lg text-white/90 font-medium">
                                Master <?php echo htmlspecialchars($topic['name']); ?> with comprehensive practice and expert resources
                            </p>
                        </div>
                    </div>
                </div>
                <!-- Prompt for Guests -->
                <?php if ($userStatus === 'guest' && count($subtopics) > 2): ?>
                    <div class="mb-4 mx-2 sm:mx-0 p-4 bg-yellow-100 rounded-xl text-yellow-800 text-sm sm:text-base">
                        <i class="fas fa-lock mr-2"></i>
                        Only the first two subtopics are available for guests. <a href="login.php" class="underline hover:text-yellow-900">Log in</a> to unlock all subtopics.
                    </div>
                <?php endif; ?>
                <!-- Mobile View -->
                <div class="sm:hidden space-y-3 mx-2">
                    <?php if ($display_subtopics): ?>
                        <?php foreach ($display_subtopics as $index => $subtopic): ?>
                            <?php
                            $is_locked = ($userStatus === 'guest' && $index >= 2);
                            $practice_link = $is_locked ? '#' : "practice.php?subtopic_id=" . $subtopic['id'];
                            $formulae_link = $is_locked ? '#' : "fetch_features.php?subtopic_id=" . $subtopic['id'] . "&feature_type=Formula";
                            $hint_link = $is_locked ? '#' : "fetch_features.php?subtopic_id=" . $subtopic['id'] . "&feature_type=Hints";
                            $papers_link = $is_locked ? '#' : "fetch_features.php?subtopic_id=" . $subtopic['id'] . "&feature_type=Papers";
                            $button_class = $is_locked ? 'locked' : '';
                            ?>
                            <div class="bg-white p-4 rounded-xl shadow-soft">
                                <h3 class="font-semibold text-gray-800 mb-3">
                                    <?php echo htmlspecialchars($subtopic['name']); ?>
                                    <?php if ($is_locked): ?>
                                        <i class="fas fa-lock text-gray-500 text-sm lock-icon" title="Log in to unlock"></i>
                                    <?php endif; ?>
                                </h3>
                                <div class="grid grid-cols-2 gap-2">
                                    <a href="<?php echo $practice_link; ?>" class="flex items-center justify-center gap-2 p-2 bg-green-500 hover:bg-green-600 text-white rounded-lg <?php echo $button_class; ?>">
                                        <i class="fas fa-pen text-sm"></i>
                                        <span class="text-sm">Practice Remembering</span>
                                    </a>
                                    <a href="<?php echo $formulae_link; ?>" class="flex items-center justify-center gap-2 p-2 bg-purple-500 hover:bg-purple-600 text-white rounded-lg <?php echo $button_class; ?>">
                                        <i class="fas fa-book text-sm"></i>
                                        <span class="text-sm">Formulae</span>
                                    </a>
                                    <a href="<?php echo $hint_link; ?>" class="flex items-center justify-center gap-2 p-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg <?php echo $button_class; ?>">
                                        <i class="fas fa-lightbulb text-sm"></i>
                                        <span class="text-sm">Hint</span>
                                    </a>
                                    <a href="<?php echo $papers_link; ?>" class="flex items-center justify-center gap-2 p-2 bg-red-500 hover:bg-red-600 text-white rounded-lg <?php echo $button_class; ?>">
                                        <i class="fas fa-file text-sm"></i>
                                        <span class="text-sm">Previous Papers</span>
                                    </a>
                                </div>
                                <div class="mt-2">
                                    <button class="tutor-help-btn flex items-center justify-center gap-2 p-2 bg-green-500 hover:bg-green-600 text-white rounded-lg w-full <?php echo $button_class; ?>" data-subtopic-id="<?php echo $subtopic['id']; ?>" <?php echo $is_locked ? 'disabled' : ''; ?>>
                                        <i class="fas fa-chalkboard-teacher text-sm"></i>
                                        <span class="text-sm">Tutor Help</span>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="bg-white p-4 rounded-xl shadow-soft text-center text-gray-600">
                            No subtopics available
                        </div>
                    <?php endif; ?>
                </div>
                <!-- Desktop/Table View -->
                <div class="hidden sm:block overflow-x-auto bg-white shadow-soft rounded-xl mx-2 sm:mx-0">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-blue-600 text-white">
                            <tr>
                                <th class="px-4 py-3 text-left text-sm font-medium">Subtopic</th>
                                <th class="px-4 py-3 text-left text-sm font-medium">Practice</th>
                                <th class="px-4 py-3 text-left text-sm font-medium">Formulae</th>
                                <th class="px-4 py-3 text-left text-sm font-medium">Hint</th>
                                <th class="px-4 py-3 text-left text-sm font-medium">Papers</th>
                                <th class="px-4 py-3 text-left text-sm font-medium">Tutor Help</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if ($display_subtopics): ?>
                                <?php foreach ($display_subtopics as $index => $subtopic): ?>
                                    <?php
                                    $is_locked = ($userStatus === 'guest' && $index >= 2);
                                    $practice_link = $is_locked ? '#' : "practice.php?subtopic_id=" . $subtopic['id'];
                                    $formulae_link = $is_locked ? '#' : "fetch_features.php?subtopic_id=" . $subtopic['id'] . "&feature_type=Formula";
                                    $hint_link = $is_locked ? '#' : "fetch_features.php?subtopic_id=" . $subtopic['id'] . "&feature_type=Hints";
                                    $papers_link = $is_locked ? '#' : "fetch_features.php?subtopic_id=" . $subtopic['id'] . "&feature_type=Papers";
                                    $button_class = $is_locked ? 'locked' : '';
                                    ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-4 py-3 font-medium text-gray-800">
                                            <?php echo htmlspecialchars($subtopic['name']); ?>
                                            <?php if ($is_locked): ?>
                                                <i class="fas fa-lock text-gray-500 text-sm lock-icon" title="Log in to unlock"></i>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-3">
                                            <a href="<?php echo $practice_link; ?>" class="inline-flex items-center px-3 py-1.5 bg-green-500 hover:bg-green-600 text-white rounded-lg <?php echo $button_class; ?>">
                                                <i class="fas fa-pen mr-2 text-sm"></i>
                                                <span>Practice</span>
                                            </a>
                                        </td>
                                        <td class="px-4 py-3">
                                            <a href="<?php echo $formulae_link; ?>" class="inline-flex items-center px-3 py-1.5 bg-purple-500 hover:bg-purple-600 text-white rounded-lg <?php echo $button_class; ?>">
                                                <i class="fas fa-book mr-2 text-sm"></i>
                                                <span>Formulae</span>
                                            </a>
                                        </td>
                                        <td class="px-4 py-3">
                                            <a href="<?php echo $hint_link; ?>" class="inline-flex items-center px-3 py-1.5 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg <?php echo $button_class; ?>">
                                                <i class="fas fa-lightbulb mr-2 text-sm"></i>
                                                <span>Hint</span>
                                            </a>
                                        </td>
                                        <td class="px-4 py-3">
                                            <a href="<?php echo $papers_link; ?>" class="inline-flex items-center px-3 py-1.5 bg-red-500 hover:bg-red-600 text-white rounded-lg <?php echo $button_class; ?>">
                                                <i class="fas fa-file mr-2 text-sm"></i>
                                                <span>Papers</span>
                                            </a>
                                        </td>
                                        <td class="px-4 py-3">
                                            <button class="tutor-help-btn inline-flex items-center px-3 py-1.5 bg-green-500 hover:bg-green-600 text-white rounded-lg <?php echo $button_class; ?>" data-subtopic-id="<?php echo $subtopic['id']; ?>" <?php echo $is_locked ? 'disabled' : ''; ?>>
                                                <i class="fas fa-chalkboard-teacher mr-2 text-sm"></i>
                                                <span>Tutor Help</span>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="px-4 py-4 text-center text-gray-600">
                                        No subtopics available for this topic
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <!-- Learning Resources -->
                <div class="bg-white rounded-xl shadow-soft p-4 mt-6 mx-2 sm:mx-0 sm:p-6 sm:mt-8">
                    <h2 class="text-xl sm:text-2xl font-bold mb-4 text-gray-800">Learning Resources</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <a href="view_questions.php?category=non_calculator&q=0" class="p-3 bg-blue-50 rounded-xl hover-scale">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-blue-600 text-white rounded-lg">
                                    <i class="fas fa-video text-lg"></i>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-800 text-sm sm:text-base">Non Calculator</h3>
                                    <p class="text-gray-600 text-xs sm:text-sm">100 Important Practice Questions</p>
                                </div>
                            </div>
                        </a>
                        <a href="view_questions.php?category=calculator&q=0" class="p-3 bg-purple-50 rounded-xl hover-scale">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-purple-600 text-white rounded-lg">
                                    <i class="fas fa-file-pdf text-lg"></i>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-800 text-sm sm:text-base">Calculator</h3>
                                    <p class="text-gray-600 text-xs sm:text-sm">100 Important Practice Questions</p>
                                </div>
                            </div>
                        </a>
                        <a href="view_questions.php?category=reading_writing&q=0" class="p-3 bg-green-50 rounded-xl hover-scale">
                            <div class="flex items-center gap-3">
                                <div class="p-2 bg-green-600 text-white rounded-lg">
                                    <i class="fas fa-chart-line text-lg"></i>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-800 text-sm sm:text-base">Reading Section</h3>
                                    <p class="text-gray-600 text-xs sm:text-sm">100 Important Practice Questions</p>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>
            </main>
        </div>
    </div>
    <!-- Tutor Help Modal -->
    <div id="tutorHelpModal" class="tutor-modal">
        <div class="tutor-modal-content">
            <h3 class="text-gray-800">Select Tutor Help Option</h3>
            <button id="bookStudySession" class="bg-blue-500 hover:bg-blue-600 text-white">Book Study Session</button>
            <button id="bookAppointment" class="bg-green-500 hover:bg-green-600 text-white">Book Appointment</button>
            <button id="closeTutorModal" class="bg-gray-300 hover:bg-gray-400 text-gray-800">Cancel</button>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const toggleMobile = document.getElementById('toggleSidebarMobile');
            const closeSidebar = document.getElementById('closeSidebar');
            const toggleMinimize = document.getElementById('toggleSidebarMinimize');
            const toggleIcon = document.getElementById('sidebarToggleIcon');
            const tutorModal = document.getElementById('tutorHelpModal');
            const bookStudySessionBtn = document.getElementById('bookStudySession');
            const bookAppointmentBtn = document.getElementById('bookAppointment');
            const closeTutorModalBtn = document.getElementById('closeTutorModal');

            // Clear localStorage for all subtopics under this topic
            const subtopicIds = <?php echo json_encode($subtopicIds); ?>;
            subtopicIds.forEach(id => {
                localStorage.removeItem(`quiz_timer_${id}`);
                Object.keys(localStorage).forEach(key => {
                    if (key.startsWith(`user_answers_${id}_`)) {
                        localStorage.removeItem(key);
                    }
                });
            });

            // Handle Tutor Help buttons
            let selectedSubtopicId = null;
            document.querySelectorAll('.tutor-help-btn').forEach(button => {
                button.addEventListener('click', () => {
                    if (!button.classList.contains('locked')) {
                        selectedSubtopicId = button.getAttribute('data-subtopic-id');
                        tutorModal.style.display = 'flex';
                    }
                });
            });

            // Book Study Session
            bookStudySessionBtn.addEventListener('click', () => {
                if (selectedSubtopicId) {
                    window.location.href = `book_study_session.php?subtopic_id=${selectedSubtopicId}`;
                }
                tutorModal.style.display = 'none';
            });

            // Book Appointment
            bookAppointmentBtn.addEventListener('click', () => {
                if (selectedSubtopicId) {
                    window.location.href = `book_appointment.php?subtopic_id=${selectedSubtopicId}`;
                }
                tutorModal.style.display = 'none';
            });

            // Close Modal
            closeTutorModalBtn.addEventListener('click', () => {
                tutorModal.style.display = 'none';
                selectedSubtopicId = null;
            });

            // Close Modal on outside click
            tutorModal.addEventListener('click', (e) => {
                if (e.target === tutorModal) {
                    tutorModal.style.display = 'none';
                    selectedSubtopicId = null;
                }
            });

            if (toggleMobile) {
                toggleMobile.addEventListener('click', () => {
                    sidebar.classList.toggle('-translate-x-full');
                });
            }

            if (closeSidebar) {
                closeSidebar.addEventListener('click', () => {
                    sidebar.classList.add('-translate-x-full');
                });
            }

            if (toggleMinimize && toggleIcon) {
                toggleMinimize.addEventListener('click', () => {
                    sidebar.classList.toggle('w-64');
                    sidebar.classList.toggle('w-20');
                    toggleIcon.classList.toggle('fa-chevron-left');
                    toggleIcon.classList.toggle('fa-chevron-right');
                    sidebar.querySelectorAll('.sidebar-text').forEach(span => {
                        span.style.opacity = sidebar.classList.contains('w-20') ? '0' : '1';
                    });
                });
            }

            // Redirect to login on locked button click
            document.querySelectorAll('.locked').forEach(button => {
                button.addEventListener('click', (e) => {
                    e.preventDefault();
                    window.location.href = 'login.php';
                });
            });
        });
    </script>
</body>
</html>



<?php
require_once __DIR__ . '/../config/database.php';

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1. Get the subtopic ID from the URL
$subtopic_id = $_GET['subtopic_id'] ?? '';
if (empty($subtopic_id)) {
    echo json_encode(['success' => false, 'message' => 'Subtopic not specified']);
    exit;
}

// 2. Fetch the subtopic record
$stmt = $pdo->prepare("SELECT s.*, t.name as topic_name, t.slug as topic_slug FROM subtopics s JOIN topics t ON s.topic_id = t.id WHERE s.id = :id LIMIT 1");
$stmt->execute(['id' => $subtopic_id]);
$subtopic = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$subtopic) {
    echo json_encode(['success' => false, 'message' => 'Subtopic not found']);
    exit;
}

// 3. Fetch questions for this subtopic
$stmt = $pdo->prepare("SELECT id, question_type, question_level, question_text, passage, solution, explanation, marks, negative_marks, time_limit FROM questions WHERE subtopic_id = :subtopic_id");
$stmt->execute(['subtopic_id' => $subtopic_id]);
$questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (!$questions) {
    echo json_encode(['success' => false, 'message' => 'No questions found for this subtopic']);
    exit;
}

// Determine the current question index from GET (default to 0)
$currentQuestionIndex = isset($_GET['q']) ? (int)$_GET['q'] : 0;
$totalQuestions = count($questions);
if ($currentQuestionIndex < 0) {
    $currentQuestionIndex = 0;
} elseif ($currentQuestionIndex >= $totalQuestions) {
    $currentQuestionIndex = $totalQuestions - 1;
}

// Get the current question and its options
$question = $questions[$currentQuestionIndex];
$stmtOptions = $pdo->prepare("SELECT id, text, is_correct FROM options WHERE question_id = :question_id ORDER BY id");
$stmtOptions->execute(['question_id' => $question['id']]);
$options = $stmtOptions->fetchAll(PDO::FETCH_ASSOC);

// Calculate progress percentage
$progressPercent = (($currentQuestionIndex + 1) / $totalQuestions) * 100;

// Set difficulty (lowercase) from question level; default to 'medium'
$difficulty = strtolower($question['question_level'] ?? 'medium');

// ***** Timer Logic *****
// Use question-specific time_limit if set, otherwise subtopic time_limit (in minutes), convert to seconds, default to 30 minutes if not set
$totalTime = ($question['time_limit'] !== null ? $question['time_limit'] * 60 : ($subtopic['time_limit'] !== null ? $subtopic['time_limit'] * 60 : 30 * 60));
$hasTimer = $question['time_limit'] !== null || $subtopic['time_limit'] !== null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>MCQ Practice - <?php echo htmlspecialchars($subtopic['name']); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Remix Icon for icons -->
    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.3.0/fonts/remixicon.css" rel="stylesheet">
    <!-- Google Font for premium look -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">
    <!-- MathJax for LaTeX rendering -->
    <script src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js"></script>
    <style>
        body { font-family: 'Poppins', sans-serif; }
        .option { transition: all 0.2s ease-in-out; }
        .option:hover { transform: scale(1.02); box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1); }
        .question-content img, .option-content img, .passage-content img { 
            max-width: 100%; 
            height: auto; 
            margin-bottom: 1rem; 
            border: 1px solid #e5e7eb; 
            border-radius: 8px; 
            display: block; 
            margin-left: auto; 
            margin-right: auto; 
        }
        .modal-enter { opacity: 0; transform: translateY(-10px); }
        .modal-enter-active { opacity: 1; transform: translateY(0); transition: opacity 0.2s ease, transform 0.2s ease; }
        .mathjax { display: inline; }
        .option-content { width: 100%; }
    </style>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <!-- Timer (Sticky Top) -->
    <div class="w-full bg-white shadow-sm py-4 sticky top-0 z-10 <?php echo $hasTimer ? '' : 'hidden'; ?>">
        <div class="max-w-4xl mx-auto px-4 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <div class="flex items-center gap-2">
                    <i class="ri-timer-line text-blue-600"></i>
                    <span id="timerDisplay" class="font-medium text-gray-700">00:00</span>
                </div>
                <span class="text-gray-400">|</span>
                <div class="text-sm text-gray-600">
                    Question <?php echo ($currentQuestionIndex + 1) . ' of ' . $totalQuestions; ?>
                </div>
            </div>
            <button onclick="toggleTimer()" class="text-gray-500 hover:text-gray-700" id="toggleTimerBtn">
                <i class="ri-eye-line" id="timerIcon"></i>
            </button>
        </div>
    </div>

    <!-- Main Content -->
    <div class="flex-grow flex items-center justify-center px-4 py-8">
        <div class="max-w-4xl w-full space-y-8">
            <!-- Progress Bar -->
            <div class="w-full">
                <div class="w-full h-3 bg-gray-200 rounded-full overflow-hidden">
                    <div id="progress" class="h-full bg-gradient-to-r from-blue-500 to-purple-600 transition-all duration-300" style="width: <?php echo $progressPercent; ?>%;"></div>
                </div>
                <p class="text-right text-sm text-gray-600 mt-1">
                    Question <?php echo ($currentQuestionIndex + 1) . ' of ' . $totalQuestions; ?>
                </p>
            </div>

            <!-- Question Card -->
            <div class="bg-white rounded-xl shadow-xl p-6">
                <!-- Question Header with Info & Navigation Buttons -->
                <div class="flex flex-col md:flex-row justify-between items-center mb-4">
                    <div class="flex items-center gap-4 flex-wrap">
                        <h1 class="text-lg md:text-2xl font-semibold text-gray-800"><?php echo htmlspecialchars($subtopic['name']); ?></h1>
                        <div class="flex items-center gap-2">
                            <span class="px-3 py-1 rounded-full text-sm text-white capitalize <?php
                                if ($difficulty === 'easy') { echo 'bg-green-500'; }
                                elseif ($difficulty === 'medium') { echo 'bg-yellow-500'; }
                                elseif ($difficulty === 'hard') { echo 'bg-red-500'; }
                                else { echo 'bg-gray-500'; }
                            ?>">
                                <?php echo htmlspecialchars($difficulty); ?>
                            </span>
                            <div class="flex items-end gap-1">
                                <?php if ($difficulty === 'easy'): ?>
                                    <div class="w-1 h-2 bg-green-500 rounded-md"></div>
                                    <div class="w-1 h-3 bg-gray-300 rounded-md"></div>
                                    <div class="w-1 h-4 bg-gray-300 rounded-md"></div>
                                <?php elseif ($difficulty === 'medium'): ?>
                                    <div class="w-1 h-2 bg-yellow-500 rounded-md"></div>
                                    <div class="w-1 h-3 bg-yellow-500 rounded-md"></div>
                                    <div class="w-1 h-4 bg-gray-300 rounded-md"></div>
                                <?php elseif ($difficulty === 'hard'): ?>
                                    <div class="w-1 h-2 bg-red-500 rounded-md"></div>
                                    <div class="w-1 h-3 bg-red-500 rounded-md"></div>
                                    <div class="w-1 h-4 bg-red-500 rounded-md"></div>
                                <?php else: ?>
                                    <div class="w-1 h-2 bg-gray-500 rounded-md"></div>
                                    <div class="w-1 h-3 bg-gray-500 rounded-md"></div>
                                    <div class="w-1 h-4 bg-gray-500 rounded-md"></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if ($question['marks'] !== null): ?>
                            <span class="text-sm text-gray-600">
                                Marks: <?php echo htmlspecialchars($question['marks']); ?>
                                <?php if ($question['negative_marks'] !== null): ?>
                                    (Negative: <?php echo htmlspecialchars($question['negative_marks']); ?>)
                                <?php endif; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="flex items-center gap-4">
                        <a href="/public/topic.php?topic=<?php echo urlencode($subtopic['topic_slug']); ?>" 
                           class="flex items-center text-gray-700 hover:text-gray-900" 
                           title="Go to Topics Page">
                            <i class="ri-home-3-line text-2xl"></i>
                        </a>
                        <button class="flex items-center text-gray-700 hover:text-gray-900" title="Bookmark Question">
                            <i class="ri-bookmark-line text-2xl"></i>
                        </button>
                    </div>
                </div>

                <!-- Passage (if exists) -->
                <?php if (!empty($question['passage'])): ?>
                    <div class="mb-6 bg-gray-50 p-4 rounded-lg">
                        <h3 class="text-lg font-semibold text-gray-800 mb-2">Passage</h3>
                        <div class="passage-content text-gray-700">
                            <?php echo $question['passage']; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Question Content -->
                <div class="mb-6">
                    <div id="question" class="question-content text-lg text-gray-700">
                        <?php echo $question['question_text']; ?>
                    </div>
                </div>

                <!-- Options -->
                <div id="options" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <?php 
                    $letters = ['A', 'B', 'C', 'D'];
                    foreach ($options as $index => $option):
                    ?>
                    <div class="option border-2 rounded-lg p-4 cursor-pointer flex justify-between items-center border-gray-200"
                         data-correct="<?php echo $option['is_correct'] ? 'true' : 'false'; ?>" 
                         data-option-id="<?php echo $option['id']; ?>"
                         data-question-id="<?php echo $question['id']; ?>">
                        <div class="flex items-center option-content">
                            <span class="font-bold mr-2"><?php echo $letters[$index] ?? ''; ?>.</span>
                            <span><?php echo $option['text']; ?></span>
                        </div>
                        <div class="w-6 h-6 flex items-center justify-center">
                            <!-- Icon will be injected by JavaScript -->
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Answer Feedback -->
                <div id="feedback" class="mt-6 text-center hidden">
                    <p class="text-xl font-semibold"></p>
                    <div class="flex justify-center gap-4 mt-2">
                        <?php if (!empty($question['solution'])): ?>
                            <button class="text-blue-600 hover:underline" onclick="openDetailModal('Solution', questionDetails.solution)">
                                View Solution
                            </button>
                        <?php endif; ?>
                        <?php if (!empty($question['explanation'])): ?>
                            <button class="text-blue-600 hover:underline" onclick="openDetailModal('Explanation', questionDetails.explanation)">
                                View Explanation
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Navigation Buttons -->
            <div class="flex justify-between">
                <a href="?subtopic_id=<?php echo urlencode($subtopic_id); ?>&q=<?php echo max($currentQuestionIndex - 1, 0); ?>" 
                   class="px-6 py-3 rounded-full bg-blue-100 text-blue-600 hover:bg-blue-200 <?php echo ($currentQuestionIndex <= 0) ? 'opacity-50 cursor-not-allowed' : ''; ?>">
                    ← Previous
                </a>
                <?php if ($currentQuestionIndex < $totalQuestions - 1): ?>
                    <a href="?subtopic_id=<?php echo urlencode($subtopic_id); ?>&q=<?php echo $currentQuestionIndex + 1; ?>" 
                       class="px-6 py-3 rounded-full bg-blue-600 text-white hover:bg-blue-700">
                        Next →
                    </a>
                <?php else: ?>
                    <a href="result.php?subtopic_id=<?php echo urlencode($subtopic_id); ?>" 
                       class="px-6 py-3 rounded-full bg-green-500 text-white hover:bg-green-600 finish-practice">
                        Finish <i class="ri-check-line align-middle"></i>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Detail Modal (for Solution/Explanation) -->
    <div id="detailModal" class="fixed inset-0 bg-black bg-opacity-40 flex items-center justify-center hidden">
        <div class="bg-white rounded-xl p-6 w-11/12 max-w-lg transform transition-all modal-enter">
            <div class="flex justify-between items-center mb-4">
                <h3 id="detailModalTitle" class="text-xl font-semibold"></h3>
                <button onclick="closeDetailModal()" class="text-2xl leading-none">×</button>
            </div>
            <div id="detailModalBody" class="text-gray-700"></div>
        </div>
    </div>

    <!-- Embed Question Details in JS -->
    <script>
        const questionDetails = {
            solution: <?php echo json_encode($question['solution'] ?? ''); ?>,
            explanation: <?php echo json_encode($question['explanation'] ?? ''); ?>,
            questionType: <?php echo json_encode($question['question_type']); ?>,
            subtopicId: <?php echo json_encode($subtopic_id); ?>,
            questionIndex: <?php echo $currentQuestionIndex; ?>,
            totalTime: <?php echo $hasTimer ? $totalTime : 'null'; ?>,
            hasTimer: <?php echo json_encode($hasTimer); ?>
        };

        // Timer Logic
        function initializeTimer() {
            if (!questionDetails.hasTimer) {
                return; // No timer if not specified
            }

            const timerKey = `quiz_timer_${questionDetails.subtopicId}_${questionDetails.questionIndex}`;
            let timerData = localStorage.getItem(timerKey);
            let timeLeft;

            if (timerData) {
                timerData = JSON.parse(timerData);
                const now = Math.floor(Date.now() / 1000);
                timeLeft = timerData.totalTime - (now - timerData.startTime);
                if (timeLeft <= 0) {
                    timeLeft = 0;
                    localStorage.removeItem(timerKey);
                    moveToNextOrResults();
                    return;
                }
            } else {
                timeLeft = questionDetails.totalTime;
                localStorage.setItem(timerKey, JSON.stringify({
                    startTime: Math.floor(Date.now() / 1000),
                    totalTime: questionDetails.totalTime
                }));
            }

            const timerDisplay = document.getElementById('timerDisplay');
            let timerInterval;

            function updateTimerDisplay() {
                const minutes = Math.floor(timeLeft / 60).toString().padStart(2, '0');
                const seconds = (timeLeft % 60).toString().padStart(2, '0');
                timerDisplay.textContent = `${minutes}:${seconds}`;
            }

            function startTimer() {
                updateTimerDisplay();
                timerInterval = setInterval(() => {
                    timeLeft--;
                    updateTimerDisplay();
                    if (timeLeft <= 0) {
                        clearInterval(timerInterval);
                        localStorage.removeItem(timerKey);
                        moveToNextOrResults();
                    }
                }, 1000);
            }

            function moveToNextOrResults() {
                localStorage.removeItem(timerKey);
                <?php if ($currentQuestionIndex < $totalQuestions - 1): ?>
                    window.location.href = "?subtopic_id=<?php echo urlencode($subtopic_id); ?>&q=<?php echo $currentQuestionIndex + 1; ?>";
                <?php else: ?>
                    window.location.href = "result.php?subtopic_id=<?php echo urlencode($subtopic_id); ?>";
                <?php endif; ?>
            }

            startTimer();
        }

        function toggleTimer() {
            if (!questionDetails.hasTimer) return;
            const timerDisplay = document.getElementById('timerDisplay');
            const toggleBtn = document.getElementById('toggleTimerBtn');
            timerDisplay.classList.toggle('hidden');
            toggleBtn.innerHTML = timerDisplay.classList.contains('hidden') ? '<i class="ri-eye-line"></i>' : '<i class="ri-eye-off-line"></i>';
        }

        // Option Selection and Feedback
        document.addEventListener('DOMContentLoaded', () => {
            const optionElements = document.querySelectorAll('.option');
            const feedbackContainer = document.getElementById('feedback');
            const feedbackText = feedbackContainer.querySelector('p');

            // Load selected answers from localStorage
            const answerKey = `user_answers_${questionDetails.subtopicId}_${questionDetails.questionIndex}`;
            let selectedAnswers = localStorage.getItem(answerKey) ? JSON.parse(localStorage.getItem(answerKey)) : [];

            // Apply initial selection styles
            optionElements.forEach(option => {
                const optionId = option.getAttribute('data-option-id');
                if (selectedAnswers.includes(optionId)) {
                    const isCorrect = option.getAttribute('data-correct') === 'true';
                    option.classList.add('selected', isCorrect ? 'border-green-500' : 'border-red-500', isCorrect ? 'bg-green-50' : 'bg-red-50');
                    option.querySelector('div.w-6').innerHTML = isCorrect ?
                        '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>' :
                        '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12" /></svg>';
                    feedbackContainer.classList.remove('hidden');
                    feedbackText.textContent = isCorrect ? 'Correct! 🎉' : 'Incorrect! Please try again.';
                }
            });

            optionElements.forEach(option => {
                option.addEventListener('click', function() {
                    const questionType = questionDetails.questionType;
                    const isCorrect = this.getAttribute('data-correct') === 'true';
                    const optionId = this.getAttribute('data-option-id');
                    const questionId = this.getAttribute('data-question-id');

                    if (questionType === 'single') {
                        // Single correct: clear all selections
                        optionElements.forEach(opt => {
                            opt.classList.remove('selected', 'border-green-500', 'bg-green-50', 'border-red-500', 'bg-red-50');
                            opt.querySelector('div.w-6').innerHTML = '';
                        });
                        selectedAnswers = [optionId];
                    } else {
                        // Multiple correct: toggle selection
                        if (this.classList.contains('selected')) {
                            this.classList.remove('selected', 'border-green-500', 'bg-green-50', 'border-red-500', 'bg-red-50');
                            this.querySelector('div.w-6').innerHTML = '';
                            selectedAnswers = selectedAnswers.filter(id => id !== optionId);
                            if (selectedAnswers.length === 0) {
                                feedbackContainer.classList.add('hidden');
                            }
                            localStorage.setItem(answerKey, JSON.stringify(selectedAnswers));
                            MathJax.typesetPromise(['#options']);
                            return;
                        } else {
                            selectedAnswers.push(optionId);
                        }
                    }

                    // Apply styling
                    this.classList.add('selected', isCorrect ? 'border-green-500' : 'border-red-500', isCorrect ? 'bg-green-50' : 'bg-red-50');
                    const iconDiv = this.querySelector('div.w-6');
                    if (iconDiv) {
                        iconDiv.innerHTML = isCorrect ?
                            '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" /></svg>' :
                            '<svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12" /></svg>';
                    }

                    // Store answers in localStorage
                    localStorage.setItem(answerKey, JSON.stringify(selectedAnswers));

                    // Show feedback
                    feedbackText.textContent = isCorrect ? 'Correct! 🎉' : 'Incorrect! Please try again.';
                    feedbackContainer.classList.remove('hidden');

                    // Re-render MathJax for options
                    MathJax.typesetPromise(['#options']).catch(err => console.error('MathJax error:', err));
                });
            });

            // Initial MathJax rendering for all content
            MathJax.typesetPromise(['#question', '#options', '.passage-content']).catch(err => console.error('MathJax error:', err));

            // Initialize timer
            initializeTimer();

            // Clear timer on finish
            const finishButton = document.querySelector('.finish-practice');
            if (finishButton) {
                finishButton.addEventListener('click', () => {
                    localStorage.removeItem(`quiz_timer_${questionDetails.subtopicId}_${questionDetails.questionIndex}`);
                });
            }
        });

        // Modal Functions
        function openDetailModal(title, content) {
            document.getElementById('detailModalTitle').textContent = title;
            document.getElementById('detailModalBody').innerHTML = `<div>${content}</div>`;
            const modal = document.getElementById('detailModal');
            modal.classList.remove('hidden');
            setTimeout(() => {
                modal.querySelector('div').classList.add('modal-enter-active');
                MathJax.typesetPromise(['#detailModalBody']).catch(err => console.error('MathJax error:', err));
            }, 10);
        }

        function closeDetailModal() {
            const modalDiv = document.getElementById('detailModal').querySelector('div');
            modalDiv.classList.remove('modal-enter-active');
            setTimeout(() => {
                document.getElementById('detailModal').classList.add('hidden');
            }, 200);
        }
    </script>
</body>
</html>