<?php
require_once '../includes/config.php';
requireAdmin();

$action = $_GET['action'] ?? 'list';
$event_id = $_GET['id'] ?? '';

// Delete event
if ($action === 'delete' && $event_id) {
    $supabase->delete('events', ['id' => 'eq.' . $event_id]);
    setFlash('success', 'Event deleted successfully.');
    redirect('events.php');
}

// Create/Edit event
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($action === 'create' || $action === 'edit')) {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $venue = trim($_POST['venue'] ?? '');
    $event_date = $_POST['event_date'] ?? '';
    $start_time = $_POST['start_time'] ?? '';
    $end_time = $_POST['end_time'] ?? '';
    $capacity = (int)($_POST['capacity'] ?? 0);
    $status = $_POST['status'] ?? 'upcoming';
    $image = '';

    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $upload = uploadImage($_FILES['image']);
        if (isset($upload['success'])) {
            $image = $upload['success'];
        }
    }

    $data = [
        'title' => $title,
        'description' => $description,
        'category' => $category,
        'venue' => $venue,
        'event_date' => $event_date,
        'start_time' => $start_time,
        'end_time' => $end_time,
        'capacity' => $capacity,
        'status' => $status,
        'created_by' => $_SESSION['admin_id']
    ];
    if ($image) $data['image'] = $image;

    if ($action === 'create') {
        $result = $supabase->insert('events', $data);
        if (!$result['error']) {
            setFlash('success', 'Event created successfully.');
        } else {
            setFlash('error', 'Failed to create event.');
        }
    } else {
        $result = $supabase->update('events', $data, ['id' => 'eq.' . $event_id]);
        if (!$result['error']) {
            setFlash('success', 'Event updated successfully.');
        } else {
            setFlash('error', 'Failed to update event.');
        }
    }
    redirect('events.php');
}

// Get event for edit
$editEvent = null;
if ($action === 'edit' && $event_id) {
    $result = $supabase->select('events', '*', ['id' => 'eq.' . $event_id], null, 1);
    $editEvent = $result['data'][0] ?? null;
}

// List events with filters
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';
$status_filter = $_GET['status'] ?? '';

$query = 'events?select=*';
if ($search) $query .= '&or=(title.ilike.' . urlencode('%' . $search . '%') . ',description.ilike.' . urlencode('%' . $search . '%') . ')';
if ($category_filter) $query .= '&category=eq.' . urlencode($category_filter);
if ($status_filter) $query .= '&status=eq.' . urlencode($status_filter);
$query .= '&order=event_date.desc';

$events = $supabase->query($query);
$eventsData = $events['data'] ?? [];

// Get categories
$catResult = $supabase->query('events?select=category,count=count()&group=category');
$categories = array_column($catResult['data'] ?? [], 'category');

include '../includes/admin_header.php';
?>

<?php if ($action === 'create' || ($action === 'edit' && $editEvent)): ?>
<div class="form-container" style="max-width: 800px;">
    <h2 class="form-heading"><?php echo $action === 'create' ? 'Create Event' : 'Edit Event'; ?></h2>
    <form method="post" action="events.php?action=<?php echo $action; ?><?php echo $event_id ? '&id=' . $event_id : ''; ?>" enctype="multipart/form-data">
        <div class="form-group">
            <label for="title">Event Title *</label>
            <input type="text" id="title" name="title" required value="<?php echo $editEvent ? htmlspecialchars($editEvent['title']) : ''; ?>">
        </div>
        <div class="form-group">
            <label for="description">Description *</label>
            <textarea id="description" name="description" required><?php echo $editEvent ? htmlspecialchars($editEvent['description']) : ''; ?></textarea>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="category">Category *</label>
                <input type="text" id="category" name="category" required value="<?php echo $editEvent ? htmlspecialchars($editEvent['category']) : ''; ?>">
            </div>
            <div class="form-group">
                <label for="venue">Venue *</label>
                <input type="text" id="venue" name="venue" required value="<?php echo $editEvent ? htmlspecialchars($editEvent['venue']) : ''; ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="event_date">Date *</label>
                <input type="date" id="event_date" name="event_date" required value="<?php echo $editEvent ? $editEvent['event_date'] : ''; ?>">
            </div>
            <div class="form-group">
                <label for="capacity">Max Participants *</label>
                <input type="number" id="capacity" name="capacity" required min="1" value="<?php echo $editEvent ? $editEvent['capacity'] : ''; ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="start_time">Start Time *</label>
                <input type="time" id="start_time" name="start_time" required value="<?php echo $editEvent ? $editEvent['start_time'] : ''; ?>">
            </div>
            <div class="form-group">
                <label for="end_time">End Time *</label>
                <input type="time" id="end_time" name="end_time" required value="<?php echo $editEvent ? $editEvent['end_time'] : ''; ?>">
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <label for="status">Status *</label>
                <select id="status" name="status" required>
                    <option value="upcoming" <?php echo ($editEvent && $editEvent['status'] == 'upcoming') ? 'selected' : ''; ?>>Upcoming</option>
                    <option value="ongoing" <?php echo ($editEvent && $editEvent['status'] == 'ongoing') ? 'selected' : ''; ?>>Ongoing</option>
                    <option value="completed" <?php echo ($editEvent && $editEvent['status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                </select>
            </div>
            <div class="form-group">
                <label for="image">Event Image</label>
                <input type="file" id="image" name="image" accept="image/*">
                <?php if ($editEvent && $editEvent['image']): ?>
                <p style="font-size: 0.85rem; color: var(--medium-text); margin-top: 5px;">Current: <?php echo $editEvent['image']; ?></p>
                <?php endif; ?>
            </div>
        </div>
        <div class="form-group">
            <button type="submit" class="btn btn-green"><?php echo $action === 'create' ? 'Create Event' : 'Update Event'; ?></button>
            <a href="events.php" class="btn btn-outline">Cancel</a>
        </div>
    </form>
</div>

<?php else: ?>

<div class="search-bar">
    <form method="get" action="events.php">
        <input type="text" name="search" placeholder="Search events..." value="<?php echo htmlspecialchars($search); ?>">
        <select name="category">
            <option value="">All Categories</option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category_filter == $cat ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat); ?></option>
            <?php endforeach; ?>
        </select>
        <select name="status">
            <option value="">All Status</option>
            <option value="upcoming" <?php echo $status_filter == 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
            <option value="ongoing" <?php echo $status_filter == 'ongoing' ? 'selected' : ''; ?>>Ongoing</option>
            <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
        </select>
        <div class="search-actions">
            <button type="submit" class="btn btn-green btn-sm">Filter</button>
            <?php if ($search || $category_filter || $status_filter): ?>
            <a href="events.php" class="btn btn-ghost btn-sm">Clear</a>
            <?php endif; ?>
            <a href="events.php?action=create" class="btn btn-green btn-sm">New Event</a>
        </div>
    </form>
</div>

<div class="table-responsive">
<table class="data-table">
    <thead>
        <tr>
            <th>Title</th>
            <th>Category</th>
            <th>Date</th>
            <th>Status</th>
            <th>Capacity</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($eventsData as $event): ?>
        <tr>
            <td><?php echo htmlspecialchars($event['title']); ?></td>
            <td><?php echo htmlspecialchars($event['category']); ?></td>
            <td><?php echo formatDate($event['event_date']); ?></td>
            <td><span class="event-status status-<?php echo $event['status']; ?>"><?php echo ucfirst($event['status']); ?></span></td>
            <td><?php echo $event['capacity']; ?></td>
            <td class="table-actions">
                <a href="events.php?action=edit&id=<?php echo $event['id']; ?>" class="btn btn-green btn-sm">Edit</a>
                <a href="events.php?action=delete&id=<?php echo $event['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirmDelete()">Delete</a>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($eventsData)): ?>
        <tr><td colspan="6" style="text-align:center;">No events found.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
</div>

<?php endif; ?>

<?php include '../includes/admin_footer.php'; ?>
