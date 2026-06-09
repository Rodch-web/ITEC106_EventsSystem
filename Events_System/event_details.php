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
$isFull = $participantCount >= $event['capacity'];

$feedbackResult = $supabase->query('feedbacks?select=overall_rating&event_id=eq.' . $event_id);
$feedbacks = $feedbackResult['data'] ?? [];
$totalFeedback = count($feedbacks);
$avgRating = $totalFeedback > 0 ? round(array_sum(array_column($feedbacks, 'overall_rating')) / $totalFeedback, 1) : 0;

include 'includes/header.php';
?>

<section class="container">
    <div class="event-details-card">
        <div class="event-details-image" style="background-image: url('<?php echo $event['image'] ? htmlspecialchars($event['image']) : 'assets/images/event-placeholder.svg'; ?>')"></div>
        <div class="event-details-content">
            <span class="event-category"><?php echo htmlspecialchars($event['category']); ?></span>
            <h1 class="event-details-title"><?php echo htmlspecialchars($event['title']); ?></h1>
            <span class="event-status status-<?php echo $event['status']; ?>"><?php echo ucfirst($event['status']); ?></span>

            <div class="event-details-info">
                <div class="info-item">
                    <span class="info-label">Date</span>
                    <span class="info-value"><?php echo formatDate($event['event_date']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Time</span>
                    <span class="info-value"><?php echo formatTime($event['start_time']); ?> – <?php echo formatTime($event['end_time']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Venue</span>
                    <span class="info-value"><?php echo htmlspecialchars($event['venue']); ?></span>
                </div>
                <div class="info-item">
                    <span class="info-label">Capacity</span>
                    <span class="info-value"><?php echo $participantCount; ?> / <?php echo $event['capacity']; ?> registered</span>
                </div>
                <?php if ($totalFeedback > 0): ?>
                <div class="info-item">
                    <span class="info-label">Rating</span>
                    <span class="info-value"><?php echo $avgRating; ?> / 5 (<?php echo $totalFeedback; ?> reviews)</span>
                </div>
                <?php endif; ?>
            </div>

            <h3 style="font-weight:800;margin-bottom:12px;letter-spacing:-0.02em;">About This Event</h3>
            <p style="color:var(--medium-text);line-height:1.75;margin-bottom:24px;"><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>

            <?php if ($event['status'] === 'upcoming' && !$isFull): ?>
            <a href="register.php?id=<?php echo $event['id']; ?>" class="btn btn-green btn-lg">Register Now</a>
            <?php elseif ($isFull): ?>
            <div class="alert alert-warning">This event is fully booked.</div>
            <?php elseif ($event['status'] === 'completed'): ?>
            <div class="alert alert-info">This event has ended. You can still submit feedback if you attended.</div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
