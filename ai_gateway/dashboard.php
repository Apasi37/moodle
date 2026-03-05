<?php

require('../../config.php');
require_login();

// Set page context and URL
$PAGE->set_context(\context_system::instance());
$PAGE->set_url(new \moodle_url('/local/ai_gateway/dashboard.php'));
$PAGE->set_title('My AI Plugin Dashboard');
$PAGE->set_heading('My AI Plugin Dashboard');

echo $OUTPUT->header();

// Get usage statistics
global $DB;

// Total usage stats
$total_stats = $DB->get_record_sql("
    SELECT
        COUNT(*) as total_requests,
        SUM(input_tokens) as total_input_tokens,
        SUM(output_tokens) as total_output_tokens,
        SUM(cost) as total_cost
    FROM {local_ai_gateway_log}
");

// Today's usage
$today_start = strtotime('today');
$today_end = strtotime('tomorrow') - 1;
$today_stats = $DB->get_record_sql("
    SELECT
        COUNT(*) as today_requests,
        SUM(input_tokens) as today_input_tokens,
        SUM(output_tokens) as today_output_tokens,
        SUM(cost) as today_cost
    FROM {local_ai_gateway_log}
    WHERE timecreated >= ? AND timecreated <= ?
", [$today_start, $today_end]);

// Recent usage (last 50 entries)
$recent_usage = $DB->get_records_sql("
    SELECT l.*, u.firstname, u.lastname, u.email
    FROM {local_ai_gateway_log} l
    JOIN {user} u ON l.userid = u.id
    ORDER BY l.timecreated DESC
    LIMIT 50
");

?>
<style>
    .dashboard-container {
        max-width: 1200px;
        margin: 20px auto;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: white;
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 20px;
        text-align: center;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .stat-value {
        font-size: 2em;
        font-weight: bold;
        color: #0066cc;
        margin: 10px 0;
    }

    .stat-label {
        color: #666;
        font-size: 0.9em;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .usage-table {
        background: white;
        border: 1px solid #ddd;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .usage-table table {
        width: 100%;
        border-collapse: collapse;
    }

    .usage-table th {
        background: #f5f5f5;
        padding: 12px;
        text-align: left;
        font-weight: 600;
        color: #333;
        border-bottom: 2px solid #ddd;
    }

    .usage-table td {
        padding: 12px;
        border-bottom: 1px solid #eee;
    }

    .usage-table tr:hover {
        background: #f9f9f9;
    }

    .user-info {
        font-weight: 500;
    }

    .token-info {
        font-family: monospace;
        font-size: 0.9em;
    }

    .cost-info {
        color: #e91e63;
        font-weight: 500;
    }

    .time-info {
        color: #666;
        font-size: 0.9em;
    }
</style>

<div class="dashboard-container">

    <!-- Statistics Cards -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-label">Total Requests</div>
            <div class="stat-value"><?php echo number_format($total_stats->total_requests ?? 0); ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Total Input Tokens</div>
            <div class="stat-value"><?php echo number_format($total_stats->total_input_tokens ?? 0); ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Total Output Tokens</div>
            <div class="stat-value"><?php echo number_format($total_stats->total_output_tokens ?? 0); ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Total Cost</div>
            <div class="stat-value">$<?php echo number_format($total_stats->total_cost ?? 0, 4); ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Today's Requests</div>
            <div class="stat-value"><?php echo number_format($today_stats->today_requests ?? 0); ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Today's Tokens</div>
            <div class="stat-value"><?php echo number_format(($today_stats->today_input_tokens ?? 0) + ($today_stats->today_output_tokens ?? 0)); ?></div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Today's Cost</div>
            <div class="stat-value">$<?php echo number_format($today_stats->today_cost ?? 0, 4); ?></div>
        </div>
    </div>

    <!-- Recent Usage Table -->
    <div class="usage-table">
        <table>
            <thead>
                <tr>
                    <th>User</th>
                    <th>Input Tokens</th>
                    <th>Output Tokens</th>
                    <th>Total Tokens</th>
                    <th>Cost</th>
                    <th>Time</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recent_usage)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px; color: #666;">
                            No usage data available yet.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recent_usage as $usage): ?>
                        <tr>
                            <td>
                                <div class="user-info">
                                    <?php echo htmlspecialchars($usage->firstname . ' ' . $usage->lastname); ?>
                                </div>
                                <div style="color: #666; font-size: 0.8em;">
                                    <?php echo htmlspecialchars($usage->email); ?>
                                </div>
                            </td>
                            <td class="token-info"><?php echo number_format($usage->input_tokens ?? 0); ?></td>
                            <td class="token-info"><?php echo number_format($usage->output_tokens ?? 0); ?></td>
                            <td class="token-info"><?php echo number_format(($usage->input_tokens ?? 0) + ($usage->output_tokens ?? 0)); ?></td>
                            <td class="cost-info">$<?php echo number_format($usage->cost ?? 0, 4); ?></td>
                            <td class="time-info"><?php echo date('M j, Y g:i A', $usage->timecreated); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php echo $OUTPUT->footer(); ?>
