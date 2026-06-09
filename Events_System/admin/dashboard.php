<?php
require_once '../includes/config.php';
requireAdmin();

// Stats
$eventsResult = $supabase->select('events', 'count', [], null, null);
$totalEvents = $eventsResult['data'][0]['count'] ?? 0;

$partResult = $supabase->select('participants', 'count', [], null, null);
$totalParticipants = $partResult['data'][0]['count'] ?? 0;

$upResult = $supabase->select('events', 'count', ['status' => 'eq.upcoming'], null, null);
$upcomingEvents = $upResult['data'][0]['count'] ?? 0;

$compResult = $supabase->select('events', 'count', ['status' => 'eq.completed'], null, null);
$completedEvents = $compResult['data'][0]['count'] ?? 0;

$fbResult = $supabase->select('feedbacks', 'count', [], null, null);
$totalFeedbacks = $fbResult['data'][0]['count'] ?? 0;

$avgResult = $supabase->query('feedbacks?select=overall_rating');
$allRatings = $avgResult['data'] ?? [];
$avgRating = count($allRatings) > 0 ? round(array_sum(array_column($allRatings, 'overall_rating')) / count($allRatings), 1) : 0;

// Recent events with participant counts
$recentEvents = $supabase->select('events', '*', [], 'created_at.desc', 5);
$recentEventsData = $recentEvents['data'] ?? [];

// Top rated events
$topRated = $supabase->query('events?select=id,title,feedbacks(overall_rating)&feedbacks.order=overall_rating.desc');
$topRatedData = $topRated['data'] ?? [];

// Category distribution
$catData = $supabase->query('events?select=category,count=count()&group=category');
$categoryData = $catData['data'] ?? [];

// Rating distribution
$ratingData = $supabase->query('feedbacks?select=overall_rating,count=count()&group=overall_rating');
$ratingDist = $ratingData['data'] ?? [];

// Monthly stats
$monthlyData = $supabase->query('events?select=created_at&order=created_at.asc');

include '../includes/admin_header.php';
?>

<div class="dashboard-cards">
    <div class="dashboard-card green">
        <div class="info">
            <h3><?php echo $totalEvents; ?></h3>
            <p>Total Events</p>
        </div>
    </div>
    <div class="dashboard-card blue">
        <div class="info">
            <h3><?php echo $totalParticipants; ?></h3>
            <p>Total Participants</p>
        </div>
    </div>
    <div class="dashboard-card orange">
        <div class="info">
            <h3><?php echo $upcomingEvents; ?></h3>
            <p>Upcoming Events</p>
        </div>
    </div>
    <div class="dashboard-card red">
        <div class="info">
            <h3><?php echo $completedEvents; ?></h3>
            <p>Completed Events</p>
        </div>
    </div>
    <div class="dashboard-card green">
        <div class="info">
            <h3><?php echo $totalFeedbacks; ?></h3>
            <p>Total Feedbacks</p>
        </div>
    </div>
    <div class="dashboard-card blue">
        <div class="info">
            <h3><?php echo number_format($avgRating, 1); ?></h3>
            <p>Avg Rating</p>
        </div>
    </div>
</div>

<div class="chart-row">
    <div class="chart-container">
        <h3>Event Category Distribution</h3>
        <canvas id="categoryChart"></canvas>
    </div>
    <div class="chart-container">
        <h3>Feedback Rating Distribution</h3>
        <canvas id="ratingChart"></canvas>
    </div>
</div>

<div class="chart-row">
    <div class="chart-container">
        <h3>Recent Events</h3>
        <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Event</th>
                    <th>Date</th>
                    <th>Status</th>
                    <th>Participants</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recentEventsData as $ev):
                    $pCount = $supabase->select('participants', 'count', ['event_id' => 'eq.' . $ev['id']]);
                    $partCount = $pCount['data'][0]['count'] ?? 0;
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($ev['title']); ?></td>
                    <td><?php echo formatDate($ev['event_date']); ?></td>
                    <td><span class="event-status status-<?php echo $ev['status']; ?>"><?php echo ucfirst($ev['status']); ?></span></td>
                    <td><?php echo $partCount; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
    <div class="chart-container">
        <h3>Top Rated Events</h3>
        <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr><th>Event</th><th>Avg Rating</th></tr>
            </thead>
            <tbody>
                <?php foreach ($topRatedData as $tr):
                    $ratings = array_column($tr['feedbacks'] ?? [], 'overall_rating');
                    $avg = count($ratings) > 0 ? round(array_sum($ratings) / count($ratings), 1) : 0;
                    if ($avg > 0):
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($tr['title']); ?></td>
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

<script>
// Category Distribution
const categoryCtx = document.getElementById('categoryChart').getContext('2d');
const categoryData = <?php echo json_encode($categoryData); ?>;
new Chart(categoryCtx, {
    type: 'doughnut',
    data: {
        labels: categoryData.map(c => c.category),
        datasets: [{
            data: categoryData.map(c => c.count),
            backgroundColor: ['#2E7D32', '#4CAF50', '#81C784', '#A5D6A7', '#66BB6A', '#388E3C'],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } }
    }
});

// Rating Distribution
const ratingCtx = document.getElementById('ratingChart').getContext('2d');
const ratingData = <?php echo json_encode($ratingDist); ?>;
const ratings = [1,2,3,4,5];
const ratingCounts = ratings.map(r => {
    const found = ratingData.find(d => parseInt(d.overall_rating) === r);
    return found ? parseInt(found.count) : 0;
});
new Chart(ratingCtx, {
    type: 'bar',
    data: {
        labels: ['1 Star', '2 Stars', '3 Stars', '4 Stars', '5 Stars'],
        datasets: [{
            label: 'Count',
            data: ratingCounts,
            backgroundColor: ['#FFEBEE', '#FFCC80', '#FFF59D', '#A5D6A7', '#2E7D32'],
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true } }
    }
});
</script>

<?php include '../includes/admin_footer.php'; ?>
