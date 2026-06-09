<?php
require_once 'includes/config.php';

$events = $supabase->select('events', '*', [], 'event_date.asc', null);
$eventsData = $events['data'] ?? [];
$totalEvents = count($eventsData);
$upcomingEvents = array_filter($eventsData, fn($e) => $e['status'] === 'upcoming');
$totalParticipants = 0;
foreach ($eventsData as $ev) {
    $participants = $supabase->select('participants', 'count', ['event_id' => 'eq.' . $ev['id']]);
    $totalParticipants += $participants['data'][0]['count'] ?? 0;
}
$totalFeedbacks = $supabase->select('feedbacks', 'count', [], null, null);
$totalFeedbacksCount = $totalFeedbacks['data'][0]['count'] ?? 0;

$featured = array_slice(array_values($upcomingEvents), 0, 3);
$recent = array_slice($eventsData, 0, 6);

include 'includes/header.php';
?>

<section class="hero">
    <div class="hero-content animate-in">
        <span class="hero-badge">Cavite State University</span>
        <h1>Campus Event<br>Management System</h1>
        <p>Discover, register, and participate in campus events that enrich your university experience.</p>
        <a href="events.php" class="btn btn-green btn-lg animate-in animate-in-delay-2">Explore Events</a>
    </div>
</section>

<section class="stats-bar reveal">
    <div class="stat-item">
        <div class="stat-number" data-count="<?php echo $totalEvents; ?>">0</div>
        <div class="stat-label">Total Events</div>
    </div>
    <div class="stat-item">
        <div class="stat-number" data-count="<?php echo $totalParticipants; ?>">0</div>
        <div class="stat-label">Participants</div>
    </div>
    <div class="stat-item">
        <div class="stat-number" data-count="<?php echo count($upcomingEvents); ?>">0</div>
        <div class="stat-label">Upcoming</div>
    </div>
    <div class="stat-item">
        <div class="stat-number" data-count="<?php echo $totalFeedbacksCount; ?>">0</div>
        <div class="stat-label">Feedbacks</div>
    </div>
</section>

<section class="container">
    <div class="section-header reveal">
        <h2 class="section-title">Featured Events</h2>
        <a href="events.php" class="btn btn-outline btn-sm">View All</a>
    </div>
    <div class="events-grid">
        <?php foreach ($featured as $event): ?>
        <div class="event-card">
            <div class="event-image" style="background-image: url('<?php echo $event['image'] ? htmlspecialchars($event['image']) : 'assets/images/event-placeholder.svg'; ?>')"></div>
            <div class="event-content">
                <span class="event-category"><?php echo htmlspecialchars($event['category']); ?></span>
                <h3 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h3>
                <div class="event-details">
                    <div class="event-meta">
                        <span><span class="meta-label">Date</span> <?php echo formatDate($event['event_date']); ?></span>
                        <span><span class="meta-label">Venue</span> <?php echo htmlspecialchars($event['venue']); ?></span>
                    </div>
                </div>
                <span class="event-status status-<?php echo $event['status']; ?>"><?php echo ucfirst($event['status']); ?></span>
                <a href="event_details.php?id=<?php echo $event['id']; ?>" class="btn btn-green btn-sm">View Details</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <h2 class="section-title reveal">Recent Events</h2>
    <div class="events-grid">
        <?php foreach ($recent as $event): ?>
        <div class="event-card">
            <div class="event-image" style="background-image: url('<?php echo $event['image'] ? htmlspecialchars($event['image']) : 'assets/images/event-placeholder.svg'; ?>')"></div>
            <div class="event-content">
                <span class="event-category"><?php echo htmlspecialchars($event['category']); ?></span>
                <h3 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h3>
                <div class="event-details">
                    <div class="event-meta">
                        <span><span class="meta-label">Date</span> <?php echo formatDate($event['event_date']); ?></span>
                        <span><span class="meta-label">Venue</span> <?php echo htmlspecialchars($event['venue']); ?></span>
                    </div>
                </div>
                <span class="event-status status-<?php echo $event['status']; ?>"><?php echo ucfirst($event['status']); ?></span>
                <a href="event_details.php?id=<?php echo $event['id']; ?>" class="btn btn-green btn-sm">View Details</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="cta-section reveal">
    <div class="cta-content">
        <h2>Stay Connected with Campus Life</h2>
        <p>Never miss an important event. Explore upcoming activities and be part of the vibrant campus community.</p>
        <a href="events.php" class="btn btn-green btn-lg">View All Events</a>
    </div>
</section>

<?php include 'includes/footer.php'; ?>
