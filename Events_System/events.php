<?php
require_once 'includes/config.php';

$category = $_GET['category'] ?? '';
$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

$query = 'events?select=*';
if ($category) $query .= '&category=eq.' . urlencode($category);
if ($status) $query .= '&status=eq.' . urlencode($status);
if ($search) $query .= '&or=(title.ilike.' . urlencode('%' . $search . '%') . ',description.ilike.' . urlencode('%' . $search . '%') . ')';
$query .= '&order=event_date.asc';

$result = $supabase->query($query);
$events = $result['data'] ?? [];

$catResult = $supabase->select('events', 'category', [], 'category.asc', null);
$categories = [];
foreach ($catResult['data'] ?? [] as $c) {
    if (!in_array($c['category'], $categories)) {
        $categories[] = $c['category'];
    }
}

include 'includes/header.php';
?>

<section class="container">
    <h1 class="page-title reveal">All Events</h1>

    <div class="search-bar reveal">
        <form method="get" action="events.php">
            <input type="text" name="search" placeholder="Search events..." value="<?php echo htmlspecialchars($search); ?>">
            <select name="category">
                <option value="">All Categories</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category === $cat ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat); ?></option>
                <?php endforeach; ?>
            </select>
            <select name="status">
                <option value="">All Status</option>
                <option value="upcoming" <?php echo $status === 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                <option value="ongoing" <?php echo $status === 'ongoing' ? 'selected' : ''; ?>>Ongoing</option>
                <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
            </select>
            <div class="search-actions">
                <button type="submit" class="btn btn-green btn-sm">Filter</button>
                <?php if ($search || $category || $status): ?>
                <a href="events.php" class="btn btn-ghost btn-sm">Clear</a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <?php if (!empty($events)): ?>
    <div class="events-grid">
        <?php foreach ($events as $event): ?>
        <div class="event-card">
            <div class="event-image" style="background-image: url('<?php echo $event['image'] ? htmlspecialchars($event['image']) : 'assets/images/event-placeholder.svg'; ?>')"></div>
            <div class="event-content">
                <span class="event-category"><?php echo htmlspecialchars($event['category']); ?></span>
                <h3 class="event-title"><?php echo htmlspecialchars($event['title']); ?></h3>
                <div class="event-details">
                    <div class="event-meta">
                        <span><span class="meta-label">Date</span> <?php echo formatDate($event['event_date']); ?></span>
                        <span><span class="meta-label">Time</span> <?php echo formatTime($event['start_time']); ?> – <?php echo formatTime($event['end_time']); ?></span>
                        <span><span class="meta-label">Venue</span> <?php echo htmlspecialchars($event['venue']); ?></span>
                    </div>
                </div>
                <span class="event-status status-<?php echo $event['status']; ?>"><?php echo ucfirst($event['status']); ?></span>
                <a href="event_details.php?id=<?php echo $event['id']; ?>" class="btn btn-green btn-sm">View Details</a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty-state reveal">
        <h3>No events found</h3>
        <p>Try adjusting your search or filter criteria.</p>
    </div>
    <?php endif; ?>
</section>

<?php include 'includes/footer.php'; ?>
