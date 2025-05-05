<?php
// Database connection info - adjust as needed
$host = 'localhost';
$db   = 'pafe_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

// Set up DSN, options
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
];

// Connect to DB
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    exit('Database connection failed: '.$e->getMessage());
}

$successMessage = '';
$errors = [];

// Handle Delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_event'])) {
    $deleteId = (int)($_POST['event_id'] ?? 0);
    if ($deleteId > 0) {
        $stmt = $pdo->prepare("DELETE FROM events WHERE id = ?");
        $stmt->execute([$deleteId]);
        header("Location: " . $_SERVER['PHP_SELF'] . "#viewEventsSection");
        exit();
    }
}

// Handle Update request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_event'])) {
    $updateId = (int)($_POST['event_id'] ?? 0);
    $eventname = trim($_POST['eventName'] ?? '');
    $status = $_POST['eventStatus'] ?? '';
    $startdate = $_POST['startDate'] ?? '';
    $enddate = $_POST['endDate'] ?? '';
    $description = trim($_POST['eventDescription'] ?? '');

    // Validation
    if ($eventname === '') {
        $errors[] = 'Event Name is required.';
    }
    if (!in_array($status, ['Scheduled', 'Ongoing', 'Completed', 'Cancelled'])) {
        $errors[] = 'Invalid Event Status selected.';
    }
    if (!$startdate) {
        $errors[] = 'Start Date is required.';
    }
    if (!$enddate) {
        $errors[] = 'End Date is required.';
    }
    if ($startdate && $enddate && strtotime($enddate) < strtotime($startdate)) {
        $errors[] = 'End Date cannot be before Start Date.';
    }

    if (empty($errors) && $updateId > 0) {
        $stmt = $pdo->prepare("UPDATE events SET eventname = ?, status = ?, startdate = ?, enddate = ?, description = ? WHERE id = ?");
        $stmt->execute([$eventname, $status, $startdate, $enddate, $description, $updateId]);
        $successMessage = 'Event updated successfully.';
        // reload events after update
        header("Location: " . $_SERVER['PHP_SELF'] . "#viewEventsSection");
        exit();
    }
}

// Handle Create submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_event'])) {
    $eventname = trim($_POST['eventName'] ?? '');
    $status = $_POST['eventStatus'] ?? '';
    $startdate = $_POST['startDate'] ?? '';
    $enddate = $_POST['endDate'] ?? '';
    $description = trim($_POST['eventDescription'] ?? '');

    // Validation
    if ($eventname === '') {
        $errors[] = 'Event Name is required.';
    }
    if (!in_array($status, ['Scheduled', 'Ongoing', 'Completed', 'Cancelled'])) {
        $errors[] = 'Invalid Event Status selected.';
    }
    if (!$startdate) {
        $errors[] = 'Start Date is required.';
    }
    if (!$enddate) {
        $errors[] = 'End Date is required.';
    }
    if ($startdate && $enddate && strtotime($enddate) < strtotime($startdate)) {
        $errors[] = 'End Date cannot be before Start Date.';
    }

    if (empty($errors)) {
        // Insert into DB
        $stmt = $pdo->prepare("INSERT INTO events (eventname, status, startdate, enddate, description) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$eventname, $status, $startdate, $enddate, $description]);
        $successMessage = 'Event created successfully.';
        // Redirect to avoid form resubmission
        header("Location: " . $_SERVER['PHP_SELF'] . "?msg=" . urlencode($successMessage) . "#viewEventsSection");
        exit();
    }
}

// Fetch events for display with error handling
try {
    $stmt = $pdo->query("SELECT * FROM events ORDER BY startdate DESC");
    $events = $stmt->fetchAll();
} catch (\PDOException $e) {
    exit("Error fetching events: " . htmlspecialchars($e->getMessage()) . "<br>Please ensure your database table 'events' has the required columns.");
}

// Calculate summary stats
$totalEvents = count($events);
$now = new DateTime();
$weekLater = (new DateTime())->modify('+7 days');
$upcomingCount = 0;
foreach ($events as $event) {
    $eventStart = new DateTime($event['startdate']);
    if ($eventStart >= $now && $eventStart <= $weekLater) {
        $upcomingCount++;
    }
}

// Check for success message from redirect
if (isset($_GET['msg'])) {
    $successMessage = htmlspecialchars($_GET['msg']);
}

// If editing an event, get id and event data
$editEvent = null;
if (isset($_GET['edit_id'])) {
    $editId = (int)$_GET['edit_id'];
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([$editId]);
    $editEvent = $stmt->fetch();
}
?>