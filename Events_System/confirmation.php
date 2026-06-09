<?php
require_once 'includes/config.php';

$reference = $_GET['ref'] ?? '';

$result = $supabase->select('participants', '*, events(title, event_date, start_time, end_time, venue)', ['reference_number' => 'eq.' . $reference], null, 1);
$participant = $result['data'][0] ?? null;

if (!$participant) {
    setFlash('error', 'Registration not found.');
    redirect('events.php');
}

include 'includes/header.php';
?>

<section class="container">
    <div class="confirmation-card">
        <div class="success-badge"></div>
        <h2 style="text-align:center;color:var(--primary-green);margin-bottom:10px;font-weight:800;letter-spacing:-0.02em;">Registration Successful</h2>
        <p style="text-align:center;color:var(--medium-text);margin-bottom:32px;">You have successfully registered for this event.</p>

        <div class="registration-details">
            <h3>Registration Details</h3>
            <div class="detail-row highlight">
                <span class="detail-label">Reference Number</span>
                <span class="detail-value"><?php echo htmlspecialchars($participant['reference_number']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Event</span>
                <span class="detail-value"><?php echo htmlspecialchars($participant['events']['title']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Date</span>
                <span class="detail-value"><?php echo formatDate($participant['events']['event_date']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Time</span>
                <span class="detail-value"><?php echo formatTime($participant['events']['start_time']); ?> – <?php echo formatTime($participant['events']['end_time']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Venue</span>
                <span class="detail-value"><?php echo htmlspecialchars($participant['events']['venue']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Name</span>
                <span class="detail-value"><?php echo htmlspecialchars($participant['full_name']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Student No.</span>
                <span class="detail-value"><?php echo htmlspecialchars($participant['student_number']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Course</span>
                <span class="detail-value"><?php echo htmlspecialchars($participant['course']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Year Level</span>
                <span class="detail-value"><?php echo htmlspecialchars($participant['year_level']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Email</span>
                <span class="detail-value"><?php echo htmlspecialchars($participant['email']); ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Contact</span>
                <span class="detail-value"><?php echo htmlspecialchars($participant['contact_number']); ?></span>
            </div>
        </div>

        <p style="text-align:center;color:var(--medium-text);margin-bottom:20px;font-size:0.9rem;">Please save your reference number for check-in.</p>
        <div class="form-actions" style="justify-content:center;">
            <a href="feedback.php?ref=<?php echo $participant['reference_number']; ?>" class="btn btn-green">Submit Feedback</a>
            <a href="events.php" class="btn btn-ghost">Back to Events</a>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
