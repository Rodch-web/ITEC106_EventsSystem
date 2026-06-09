<?php
require_once '../includes/config.php';
requireAdmin();

$action = $_GET['action'] ?? 'list';
$participant_id = $_GET['id'] ?? '';

// Update attendance status
if ($action === 'attendance' && $participant_id && isset($_GET['status'])) {
    $status = $_GET['status'];
    if (in_array($status, ['registered', 'attended', 'absent'])) {
        $supabase->update('participants', ['attendance_status' => $status], ['id' => 'eq.' . $participant_id]);
        setFlash('success', 'Attendance status updated.');
    }
    redirect('participants.php');
}

// Search and filter
$search = $_GET['search'] ?? '';
$event_filter = $_GET['event'] ?? '';
$status_filter = $_GET['status'] ?? '';

$query = 'participants?select=*,events(title)';
if ($search) $query .= '&or=(full_name.ilike.' . urlencode('%' . $search . '%') . ',student_number.ilike.' . urlencode('%' . $search . '%') . ',email.ilike.' . urlencode('%' . $search . '%') . ')';
if ($event_filter) $query .= '&event_id=eq.' . $event_filter;
if ($status_filter) $query .= '&attendance_status=eq.' . $status_filter;
$query .= '&order=registration_date.desc';

$participants = $supabase->query($query);
$participantsData = $participants['data'] ?? [];

$events = $supabase->query('events?select=id,title&order=title.asc');
$eventsData = $events['data'] ?? [];

include '../includes/admin_header.php';
?>

<div class="search-bar">
    <form method="get" action="participants.php">
        <input type="text" name="search" placeholder="Search participants..." value="<?php echo htmlspecialchars($search); ?>">
        <select name="event">
            <option value="">All Events</option>
            <?php foreach ($eventsData as $ev): ?>
            <option value="<?php echo $ev['id']; ?>" <?php echo $event_filter == $ev['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($ev['title']); ?></option>
            <?php endforeach; ?>
        </select>
        <select name="status">
            <option value="">All Status</option>
            <option value="registered" <?php echo $status_filter == 'registered' ? 'selected' : ''; ?>>Registered</option>
            <option value="attended" <?php echo $status_filter == 'attended' ? 'selected' : ''; ?>>Attended</option>
            <option value="absent" <?php echo $status_filter == 'absent' ? 'selected' : ''; ?>>Absent</option>
        </select>
        <div class="search-actions">
            <button type="submit" class="btn btn-green btn-sm">Filter</button>
            <?php if ($search || $event_filter || $status_filter): ?>
            <a href="participants.php" class="btn btn-ghost btn-sm">Clear</a>
            <?php endif; ?>
            <button type="button" onclick="exportTableToExcel('participantsTable', 'Participants')" class="btn btn-outline btn-sm">Export</button>
        </div>
    </form>
</div>

<div class="table-responsive">
<table class="data-table" id="participantsTable">
    <thead>
        <tr>
            <th>Name</th>
            <th>Student No</th>
            <th>Course</th>
            <th>Year</th>
            <th>Event</th>
            <th>Registration</th>
            <th>Attendance</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($participantsData as $p): ?>
        <tr>
            <td><?php echo htmlspecialchars($p['full_name']); ?></td>
            <td><?php echo htmlspecialchars($p['student_number']); ?></td>
            <td><?php echo htmlspecialchars($p['course']); ?></td>
            <td><?php echo htmlspecialchars($p['year_level']); ?></td>
            <td><?php echo htmlspecialchars($p['events']['title']); ?></td>
            <td><?php echo formatDate($p['registration_date']); ?></td>
            <td>
                <span class="event-status status-<?php echo $p['attendance_status']; ?>">
                    <?php echo ucfirst($p['attendance_status']); ?>
                </span>
            </td>
            <td class="table-actions">
                <a href="participants.php?action=attendance&id=<?php echo $p['id']; ?>&status=attended" class="btn btn-green btn-sm">Attended</a>
                <a href="participants.php?action=attendance&id=<?php echo $p['id']; ?>&status=absent" class="btn btn-danger btn-sm">Absent</a>
                <a href="participants.php?action=attendance&id=<?php echo $p['id']; ?>&status=registered" class="btn btn-ghost btn-sm">Reset</a>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($participantsData)): ?>
        <tr><td colspan="8" style="text-align:center;">No participants found.</td></tr>
        <?php endif; ?>
    </tbody>
</table>
</div>

<?php include '../includes/admin_footer.php'; ?>
