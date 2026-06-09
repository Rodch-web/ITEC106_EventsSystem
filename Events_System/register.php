<?php
require_once 'includes/config.php';

$event_id = $_GET['id'] ?? '';

$result = $supabase->select('events', '*', ['id' => 'eq.' . $event_id], null, 1);
$event = $result['data'][0] ?? null;

if (!$event) {
    setFlash('error', 'Event not found.');
    redirect('events.php');
}

$partResult = $supabase->select('participants', 'count', ['event_id' => 'eq.' . $event_id]);
$participantCount = $partResult['data'][0]['count'] ?? 0;

if ($participantCount >= $event['capacity']) {
    setFlash('error', 'This event is fully booked.');
    redirect('event_details.php?id=' . $event_id);
}

if ($event['status'] !== 'upcoming' && $event['status'] !== 'ongoing') {
    setFlash('error', 'Registration is closed for this event.');
    redirect('event_details.php?id=' . $event_id);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_number = trim($_POST['student_number'] ?? '');
    $full_name = trim($_POST['full_name'] ?? '');
    $course = trim($_POST['course'] ?? '');
    $year_level = trim($_POST['year_level'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');
    $reference = generateReference();

    $insertData = [
        'event_id' => $event_id,
        'student_number' => $student_number,
        'full_name' => $full_name,
        'course' => $course,
        'year_level' => $year_level,
        'email' => $email,
        'contact_number' => $contact_number,
        'reference_number' => $reference,
        'attendance_status' => 'registered'
    ];

    $result = $supabase->insert('participants', $insertData);
    if ($result['error']) {
        setFlash('error', 'Registration failed. Please try again.');
    } else {
        redirect('confirmation.php?ref=' . $reference);
    }
}

include 'includes/header.php';
?>

<section class="container">
    <div class="form-container">
        <h2 class="form-heading">Register for Event</h2>
        <h3 class="form-subheading"><?php echo htmlspecialchars($event['title']); ?></h3>
        <p class="form-meta">
            <strong>Date:</strong> <?php echo formatDate($event['event_date']); ?><br>
            <strong>Time:</strong> <?php echo formatTime($event['start_time']); ?> – <?php echo formatTime($event['end_time']); ?><br>
            <strong>Venue:</strong> <?php echo htmlspecialchars($event['venue']); ?>
        </p>

        <form method="post" action="register.php?id=<?php echo $event_id; ?>">
            <div class="form-row">
                <div class="form-group">
                    <label for="student_number">Student Number *</label>
                    <input type="text" id="student_number" name="student_number" required placeholder="e.g., 2020-00001">
                </div>
                <div class="form-group">
                    <label for="full_name">Full Name *</label>
                    <input type="text" id="full_name" name="full_name" required placeholder="Your full name">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="course">Course *</label>
                    <input type="text" id="course" name="course" required placeholder="e.g., BS Computer Science">
                </div>
                <div class="form-group">
                    <label for="year_level">Year Level *</label>
                    <select id="year_level" name="year_level" required>
                        <option value="">Select Year</option>
                        <option value="1st Year">1st Year</option>
                        <option value="2nd Year">2nd Year</option>
                        <option value="3rd Year">3rd Year</option>
                        <option value="4th Year">4th Year</option>
                        <option value="5th Year">5th Year</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="email">Email Address *</label>
                    <input type="email" id="email" name="email" required placeholder="your@email.com">
                </div>
                <div class="form-group">
                    <label for="contact_number">Contact Number *</label>
                    <input type="text" id="contact_number" name="contact_number" required placeholder="09171234567">
                </div>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-green btn-lg">Complete Registration</button>
                <a href="event_details.php?id=<?php echo $event_id; ?>" class="btn btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
