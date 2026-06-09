<?php
require_once 'includes/config.php';

$reference = $_GET['ref'] ?? '';

$result = $supabase->select('participants', '*', ['reference_number' => 'eq.' . $reference], null, 1);
$participant = $result['data'][0] ?? null;

if (!$participant) {
    setFlash('error', 'Registration not found.');
    redirect('events.php');
}

$fbResult = $supabase->select('feedbacks', 'count', ['participant_id' => 'eq.' . $participant['id']]);
$alreadySubmitted = ($fbResult['data'][0]['count'] ?? 0) > 0;

if ($alreadySubmitted) {
    setFlash('error', 'You have already submitted feedback for this event.');
    redirect('events.php');
}

$evResult = $supabase->select('events', '*', ['id' => 'eq.' . $participant['event_id']], null, 1);
$event = $evResult['data'][0] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $overall = (int)($_POST['overall_rating'] ?? 5);
    $organization = (int)($_POST['organization_rating'] ?? 5);
    $speaker = (int)($_POST['speaker_rating'] ?? 5);
    $venue = (int)($_POST['venue_rating'] ?? 5);
    $comments = trim($_POST['comments'] ?? '');

    $insertData = [
        'participant_id' => $participant['id'],
        'event_id' => $participant['event_id'],
        'overall_rating' => $overall,
        'organization_rating' => $organization,
        'speaker_rating' => $speaker,
        'venue_rating' => $venue,
        'comments' => $comments
    ];

    $result = $supabase->insert('feedbacks', $insertData);
    if ($result['error']) {
        setFlash('error', 'Failed to submit feedback.');
    } else {
        setFlash('success', 'Thank you for your feedback!');
        redirect('events.php');
    }
}

include 'includes/header.php';
?>

<section class="container">
    <div class="form-container">
        <h2 class="form-heading">Event Feedback</h2>
        <h3 class="form-subheading"><?php echo $event ? htmlspecialchars($event['title']) : ''; ?></h3>
        <p class="form-meta">Participant: <strong><?php echo htmlspecialchars($participant['full_name']); ?></strong></p>

        <form method="post" action="feedback.php?ref=<?php echo $reference; ?>">
            <div class="rating-group">
                <label>Overall Rating</label>
                <div class="star-rating" data-rating="overall">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <span class="star" data-value="<?php echo $i; ?>"></span>
                    <?php endfor; ?>
                    <input type="hidden" name="overall_rating" id="overall_rating" value="5">
                </div>
            </div>
            <div class="rating-group">
                <label>Organization Rating</label>
                <div class="star-rating" data-rating="organization">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <span class="star" data-value="<?php echo $i; ?>"></span>
                    <?php endfor; ?>
                    <input type="hidden" name="organization_rating" id="organization_rating" value="5">
                </div>
            </div>
            <div class="rating-group">
                <label>Speaker / Presenter Rating</label>
                <div class="star-rating" data-rating="speaker">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <span class="star" data-value="<?php echo $i; ?>"></span>
                    <?php endfor; ?>
                    <input type="hidden" name="speaker_rating" id="speaker_rating" value="5">
                </div>
            </div>
            <div class="rating-group">
                <label>Venue Rating</label>
                <div class="star-rating" data-rating="venue">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <span class="star" data-value="<?php echo $i; ?>"></span>
                    <?php endfor; ?>
                    <input type="hidden" name="venue_rating" id="venue_rating" value="5">
                </div>
            </div>
            <div class="form-group">
                <label for="comments">Comments / Suggestions</label>
                <textarea id="comments" name="comments" rows="4" placeholder="Share your thoughts about the event..."></textarea>
            </div>
            <div class="form-actions">
                <button type="submit" class="btn btn-green btn-lg">Submit Feedback</button>
                <a href="events.php" class="btn btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
