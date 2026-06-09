<?php
require_once '../includes/config.php';
requireAdmin();

$action = $_GET['action'] ?? 'list';
$feedback_id = $_GET['id'] ?? '';

// Delete feedback
if ($action === 'delete' && $feedback_id) {
    $supabase->delete('feedbacks', ['id' => 'eq.' . $feedback_id]);
    setFlash('success', 'Feedback deleted successfully.');
    redirect('feedbacks.php');
}

// Filters
$search = $_GET['search'] ?? '';
$event_filter = $_GET['event'] ?? '';
$rating_filter = $_GET['rating'] ?? '';

$query = 'feedbacks?select=*,events(title),participants(full_name)';
if ($search) $query .= '&or=(comments.ilike.' . urlencode('%' . $search . '%') . ',participants.full_name.ilike.' . urlencode('%' . $search . '%') . ')';
if ($event_filter) $query .= '&event_id=eq.' . $event_filter;
if ($rating_filter) $query .= '&overall_rating=eq.' . $rating_filter;
$query .= '&order=submitted_at.desc';

$feedbacks = $supabase->query($query);
$feedbacksData = $feedbacks['data'] ?? [];

$events = $supabase->query('events?select=id,title&order=title.asc');
$eventsData = $events['data'] ?? [];

// Feedback analytics
$avgOverall = $avgOrg = $avgSpeaker = $avgVenue = 0;
if (count($feedbacksData) > 0) {
    $avgOverall = round(array_sum(array_column($feedbacksData, 'overall_rating')) / count($feedbacksData), 1);
    $avgOrg = round(array_sum(array_column($feedbacksData, 'organization_rating')) / count($feedbacksData), 1);
    $avgSpeaker = round(array_sum(array_column($feedbacksData, 'speaker_rating')) / count($feedbacksData), 1);
    $avgVenue = round(array_sum(array_column($feedbacksData, 'venue_rating')) / count($feedbacksData), 1);
}

$eventRatings = $supabase->query('events?select=title,feedbacks(overall_rating)&feedbacks.order=overall_rating.desc');
$eventRatingsData = $eventRatings['data'] ?? [];

include '../includes/admin_header.php';
?>

<div class="dashboard-cards" style="margin-bottom: 20px;">
    <div class="dashboard-card green">
        <div class="info">
            <h3><?php echo number_format($avgOverall, 1); ?></h3>
            <p>Avg Overall Rating</p>
        </div>
    </div>
    <div class="dashboard-card blue">
        <div class="info">
            <h3><?php echo number_format($avgOrg, 1); ?></h3>
            <p>Avg Organization</p>
        </div>
    </div>
    <div class="dashboard-card orange">
        <div class="info">
            <h3><?php echo number_format($avgSpeaker, 1); ?></h3>
            <p>Avg Speaker Rating</p>
        </div>
    </div>
    <div class="dashboard-card red">
        <div class="info">
            <h3><?php echo number_format($avgVenue, 1); ?></h3>
            <p>Avg Venue Rating</p>
        </div>
    </div>
</div>

<div class="chart-row" style="margin-bottom: 20px;">
    <div class="chart-container">
        <h3>Event Ratings</h3>
        <canvas id="eventRatingChart"></canvas>
    </div>
    <div class="chart-container">
        <h3>Top Rated Events</h3>
        <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr><th>Event</th><th>Avg Rating</th></tr>
            </thead>
            <tbody>
                <?php foreach ($eventRatingsData as $er):
                    $ratings = array_column($er['feedbacks'] ?? [], 'overall_rating');
                    $avg = count($ratings) > 0 ? round(array_sum($ratings) / count($ratings), 1) : 0;
                    if ($avg > 0):
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($er['title']); ?></td>
                    <td>
                        <span class="rating-display">
                            <span class="star-display"><?php echo str_repeat('★', round($avg)) . str_repeat('☆', 5 - round($avg)); ?></span>
                            (<?php echo $avg; ?>)
                        </span>
                    </td>
                </tr>
                <?php endif; endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<div class="search-bar">
    <form method="get" action="feedbacks.php">
        <input type="text" name="search" placeholder="Search feedback..." value="<?php echo htmlspecialchars($search); ?>">
        <select name="event">
            <option value="">All Events</option>
            <?php foreach ($eventsData as $ev): ?>
            <option value="<?php echo $ev['id']; ?>" <?php echo $event_filter == $ev['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($ev['title']); ?></option>
            <?php endforeach; ?>
        </select>
        <select name="rating">
            <option value="">All Ratings</option>
            <?php for ($i = 5; $i >= 1; $i--): ?>
            <option value="<?php echo $i; ?>" <?php echo $rating_filter == $i ? 'selected' : ''; ?>><?php echo $i; ?> Stars</option>
            <?php endfor; ?>
        </select>
        <div class="search-actions">
            <button type="submit" class="btn btn-green btn-sm">Filter</button>
            <?php if ($search || $event_filter || $rating_filter): ?>
            <a href="feedbacks.php" class="btn btn-ghost btn-sm">Clear</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="table-responsive">
<table class="data-table">
    <thead>
        <tr>
            <th>Participant</th>
            <th>Event</th>
            <th>Overall</th>
            <th>Org</th>
            <th>Speaker</th>
            <th>Venue</th>
            <th>Comments</th>
            <th>Submitted</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($feedbacksData as $f): ?>
        <tr>
            <td><?php echo htmlspecialchars($f['participants']['full_name']); ?></td>
            <td><?php echo htmlspecialchars($f['events']['title']); ?></td>
            <td><?php echo $f['overall_rating']; ?></td>
            <td><?php echo $f['organization_rating']; ?></td>
            <td><?php echo $f['speaker_rating']; ?></td>
            <td><?php echo $f['venue_rating']; ?></td>
            <td><?php echo $f['comments'] ? htmlspecialchars(substr($f['comments'], 0, 60)) . '...' : '-'; ?></td>
            <td><?php echo formatDate($f['submitted_at']); ?></td>
            <td class="table-actions">
                <a href="feedbacks.php?action=delete&id=<?php echo $f['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirmDelete()">Delete</a>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($feedbacksData)): ?>
        <tr><td colspan="9" style="text-align:center;">No feedback found.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
</div>

<script>
const eventRatingCtx = document.getElementById('eventRatingChart').getContext('2d');
const eventRatingData = <?php echo json_encode($eventRatingsData); ?>;
const chartData = eventRatingData.map(e => {
    const ratings = (e.feedbacks || []).map(f => f.overall_rating);
    return {
        title: e.title.substring(0, 20),
        avg: ratings.length > 0 ? (ratings.reduce((a,b) => a+b, 0) / ratings.length).toFixed(1) : 0
    };
}).filter(e => e.avg > 0);
new Chart(eventRatingCtx, {
    type: 'bar',
    data: {
        labels: chartData.map(e => e.title),
        datasets: [{
            label: 'Avg Rating',
            data: chartData.map(e => e.avg),
            backgroundColor: '#4CAF50',
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, max: 5 } }
    }
});
</script>

<?php include '../includes/admin_footer.php'; ?>