<?php
require_once '../includes/config.php';
requireAdmin();

$report = $_GET['report'] ?? 'events';
$eventFilter = $_GET['event_filter'] ?? '';
$dateFrom = $_GET['date_from'] ?? '';
$dateTo = $_GET['date_to'] ?? '';

$data = [];

switch ($report) {
    case 'events':
        $query = 'events?select=*';
        if ($dateFrom) $query .= '&event_date=gte.' . $dateFrom;
        if ($dateTo) $query .= '&event_date=lte.' . $dateTo;
        $query .= '&order=event_date.desc';
        $result = $supabase->query($query);
        $data = $result['data'] ?? [];
        break;

    case 'participants':
        $query = 'participants?select=*,events(title)';
        if ($eventFilter) $query .= '&event_id=eq.' . $eventFilter;
        $query .= '&order=registration_date.desc';
        $result = $supabase->query($query);
        $data = $result['data'] ?? [];
        break;

    case 'attendance':
        $query = 'participants?select=*,events(title)';
        if ($eventFilter) $query .= '&event_id=eq.' . $eventFilter;
        $query .= '&order=attendance_status,registration_date.desc';
        $result = $supabase->query($query);
        $data = $result['data'] ?? [];
        break;

    case 'feedback':
        $query = 'feedbacks?select=*,events(title),participants(full_name)';
        if ($eventFilter) $query .= '&event_id=eq.' . $eventFilter;
        $query .= '&order=submitted_at.desc';
        $result = $supabase->query($query);
        $data = $result['data'] ?? [];
        break;

    case 'satisfaction':
        $events = $supabase->query('events?select=id,title,feedbacks(overall_rating,organization_rating,speaker_rating,venue_rating)');
        $eventsData = $events['data'] ?? [];
        foreach ($eventsData as $ev) {
            $feedbacks = $ev['feedbacks'] ?? [];
            $data[] = [
                'id' => $ev['id'],
                'title' => $ev['title'],
                'feedback_count' => count($feedbacks),
                'avg_rating' => count($feedbacks) > 0 ? round(array_sum(array_column($feedbacks, 'overall_rating')) / count($feedbacks), 2) : null,
                'avg_org' => count($feedbacks) > 0 ? round(array_sum(array_column($feedbacks, 'organization_rating')) / count($feedbacks), 2) : null,
                'avg_speaker' => count($feedbacks) > 0 ? round(array_sum(array_column($feedbacks, 'speaker_rating')) / count($feedbacks), 2) : null,
                'avg_venue' => count($feedbacks) > 0 ? round(array_sum(array_column($feedbacks, 'venue_rating')) / count($feedbacks), 2) : null,
            ];
        }
        break;
}

$events = $supabase->query('events?select=id,title&order=title.asc');
$eventsData = $events['data'] ?? [];

include '../includes/admin_header.php';
?>

<div class="report-tabs">
    <a href="reports.php?report=events" class="btn <?php echo $report == 'events' ? 'btn-green' : 'btn-outline'; ?> btn-sm">Events</a>
    <a href="reports.php?report=participants" class="btn <?php echo $report == 'participants' ? 'btn-green' : 'btn-outline'; ?> btn-sm">Participants</a>
    <a href="reports.php?report=attendance" class="btn <?php echo $report == 'attendance' ? 'btn-green' : 'btn-outline'; ?> btn-sm">Attendance</a>
    <a href="reports.php?report=feedback" class="btn <?php echo $report == 'feedback' ? 'btn-green' : 'btn-outline'; ?> btn-sm">Feedback</a>
    <a href="reports.php?report=satisfaction" class="btn <?php echo $report == 'satisfaction' ? 'btn-green' : 'btn-outline'; ?> btn-sm">Satisfaction</a>
</div>

<div class="search-bar">
    <form method="get" action="reports.php">
        <input type="hidden" name="report" value="<?php echo $report; ?>">
        <?php if ($report != 'events'): ?>
        <select name="event_filter">
            <option value="">All Events</option>
            <?php foreach ($eventsData as $ev): ?>
            <option value="<?php echo $ev['id']; ?>" <?php echo $eventFilter == $ev['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($ev['title']); ?></option>
            <?php endforeach; ?>
        </select>
        <?php endif; ?>
        <?php if ($report == 'events'): ?>
        <input type="date" name="date_from" value="<?php echo $dateFrom; ?>" placeholder="From Date">
        <input type="date" name="date_to" value="<?php echo $dateTo; ?>" placeholder="To Date">
        <?php endif; ?>
        <div class="search-actions">
            <button type="submit" class="btn btn-green btn-sm">Filter</button>
            <?php if ($eventFilter || $dateFrom || $dateTo): ?>
            <a href="reports.php?report=<?php echo $report; ?>" class="btn btn-ghost btn-sm">Clear</a>
            <?php endif; ?>
            <button type="button" onclick="printPage()" class="btn btn-outline btn-sm">Print</button>
            <button type="button" onclick="exportTableToExcel('reportTable', '<?php echo ucfirst($report); ?>_Report')" class="btn btn-outline btn-sm">Export</button>
        </div>
    </form>
</div>

<div class="chart-container">
    <h3><?php echo ucfirst($report); ?> Report</h3>
    <div class="table-responsive">
    <table class="data-table" id="reportTable">
        <thead>
            <?php if ($report == 'events'): ?>
            <tr>
                <th>Title</th>
                <th>Category</th>
                <th>Venue</th>
                <th>Date</th>
                <th>Time</th>
                <th>Capacity</th>
                <th>Status</th>
            </tr>
            <?php elseif ($report == 'participants'): ?>
            <tr>
                <th>Name</th>
                <th>Student No</th>
                <th>Course</th>
                <th>Year</th>
                <th>Email</th>
                <th>Contact</th>
                <th>Event</th>
                <th>Reference</th>
                <th>Registration Date</th>
            </tr>
            <?php elseif ($report == 'attendance'): ?>
            <tr>
                <th>Name</th>
                <th>Student No</th>
                <th>Event</th>
                <th>Attendance</th>
                <th>Registration Date</th>
            </tr>
            <?php elseif ($report == 'feedback'): ?>
            <tr>
                <th>Participant</th>
                <th>Event</th>
                <th>Overall</th>
                <th>Organization</th>
                <th>Speaker</th>
                <th>Venue</th>
                <th>Comments</th>
                <th>Submitted</th>
            </tr>
            <?php elseif ($report == 'satisfaction'): ?>
            <tr>
                <th>Event</th>
                <th>Feedback Count</th>
                <th>Avg Overall</th>
                <th>Avg Organization</th>
                <th>Avg Speaker</th>
                <th>Avg Venue</th>
                <th>Satisfaction %</th>
            </tr>
            <?php endif; ?>
        </thead>
        <tbody>
            <?php if ($report == 'events'): ?>
                <?php foreach ($data as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                    <td><?php echo htmlspecialchars($row['category']); ?></td>
                    <td><?php echo htmlspecialchars($row['venue']); ?></td>
                    <td><?php echo formatDate($row['event_date']); ?></td>
                    <td><?php echo formatTime($row['start_time']); ?> - <?php echo formatTime($row['end_time']); ?></td>
                    <td><?php echo $row['capacity']; ?></td>
                    <td><span class="event-status status-<?php echo $row['status']; ?>"><?php echo ucfirst($row['status']); ?></span></td>
                </tr>
                <?php endforeach; ?>
            <?php elseif ($report == 'participants'): ?>
                <?php foreach ($data as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['student_number']); ?></td>
                    <td><?php echo htmlspecialchars($row['course']); ?></td>
                    <td><?php echo htmlspecialchars($row['year_level']); ?></td>
                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                    <td><?php echo htmlspecialchars($row['contact_number']); ?></td>
                    <td><?php echo htmlspecialchars($row['events']['title']); ?></td>
                    <td><?php echo htmlspecialchars($row['reference_number']); ?></td>
                    <td><?php echo formatDate($row['registration_date']); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php elseif ($report == 'attendance'): ?>
                <?php foreach ($data as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['student_number']); ?></td>
                    <td><?php echo htmlspecialchars($row['events']['title']); ?></td>
                    <td><span class="event-status status-<?php echo $row['attendance_status']; ?>"><?php echo ucfirst($row['attendance_status']); ?></span></td>
                    <td><?php echo formatDate($row['registration_date']); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php elseif ($report == 'feedback'): ?>
                <?php foreach ($data as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['participants']['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['events']['title']); ?></td>
                    <td><?php echo $row['overall_rating']; ?></td>
                    <td><?php echo $row['organization_rating']; ?></td>
                    <td><?php echo $row['speaker_rating']; ?></td>
                    <td><?php echo $row['venue_rating']; ?></td>
                    <td><?php echo $row['comments'] ? htmlspecialchars(substr($row['comments'], 0, 80)) . '...' : '-'; ?></td>
                    <td><?php echo formatDate($row['submitted_at']); ?></td>
                </tr>
                <?php endforeach; ?>
            <?php elseif ($report == 'satisfaction'): ?>
                <?php foreach ($data as $row):
                    $satisfaction = $row['avg_rating'] ? ($row['avg_rating'] / 5) * 100 : 0;
                ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['title']); ?></td>
                    <td><?php echo $row['feedback_count']; ?></td>
                    <td><?php echo $row['avg_rating'] ? number_format($row['avg_rating'], 2) : '-'; ?></td>
                    <td><?php echo $row['avg_org'] ? number_format($row['avg_org'], 2) : '-'; ?></td>
                    <td><?php echo $row['avg_speaker'] ? number_format($row['avg_speaker'], 2) : '-'; ?></td>
                    <td><?php echo $row['avg_venue'] ? number_format($row['avg_venue'], 2) : '-'; ?></td>
                    <td><?php echo $satisfaction ? number_format($satisfaction, 1) . '%' : 'N/A'; ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            <?php if (empty($data)): ?>
                <tr><td colspan="20" style="text-align:center;">No data found for the selected report.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    </div>
</div>

<?php include '../includes/admin_footer.php'; ?>