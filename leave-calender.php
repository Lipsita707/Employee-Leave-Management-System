<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include('includes/config.php');

if(strlen($_SESSION['emplogin'])==0) {   
    header('location:index.php');
    exit();
}

$eid = $_SESSION['eid'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Employee | Leave Calendar</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"/>
    <meta charset="UTF-8">
    
    <!-- Styles -->
    <link type="text/css" rel="stylesheet" href="assets/plugins/materialize/css/materialize.min.css"/>
    <link href="http://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="assets/plugins/material-preloader/css/materialPreloader.min.css" rel="stylesheet">
    <link href="assets/css/alpha.min.css" rel="stylesheet" type="text/css"/>
    <link href="assets/css/custom.css" rel="stylesheet" type="text/css"/>
    
    <style>
    .calendar-container {
        margin-top: 20px;
        padding: 20px;
        background: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .calendar-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
    }
    .calendar-table th {
        background: #00acc1;
        color: white;
        padding: 12px;
        text-align: center;
        font-weight: 500;
    }
    .calendar-table td {
        border: 1px solid #ddd;
        padding: 10px;
        vertical-align: top;
        height: 100px;
        width: 14.28%;
        transition: all 0.3s ease;
    }
    .calendar-table td:hover {
        background: #f5f5f5;
    }
    .calendar-table td .date {
        font-weight: bold;
        margin-bottom: 5px;
        color: #333;
        font-size: 16px;
    }
    .leave-event {
        background: #4caf50;
        color: white;
        padding: 3px 6px;
        margin: 3px 0;
        border-radius: 3px;
        font-size: 11px;
        cursor: pointer;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .calendar-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }
    .calendar-title {
        font-size: 24px;
        font-weight: 600;
        color: #333;
        display: flex;
        align-items: center;
    }
    .calendar-title i {
        margin-right: 10px;
        color: #00acc1;
    }
    .calendar-nav {
        display: flex;
        gap: 10px;
    }
    .calendar-legend {
        display: flex;
        gap: 20px;
        margin-top: 20px;
        padding: 10px 0;
        border-top: 1px solid #eee;
    }
    .legend-item {
        display: flex;
        align-items: center;
        font-size: 13px;
    }
    .legend-color {
        width: 20px;
        height: 20px;
        border-radius: 3px;
        margin-right: 8px;
    }
    .today {
        background-color: #e8f5e8 !important;
    }
    .empty-day {
        background-color: #f9f9f9;
    }
    </style>
</head>
<body>
    <?php include('includes/header.php');?>
    <?php include('includes/sidebar.php');?>

    <main class="mn-inner">
        <div class="row">
            <div class="col s12">
                <div class="page-title">
                    <i class="material-icons left">date_range</i> 
                    Leave Calendar
                </div>
            </div>
            
            <div class="col s12">
                <div class="card calendar-container">
                    <div class="card-content">
                        <?php
                        // Get month and year from URL or use current
                        $month = isset($_GET['month']) ? (int)$_GET['month'] : (int)date('m');
                        $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
                        
                        // Validate month and year
                        if($month < 1) $month = 1;
                        if($month > 12) $month = 12;
                        if($year < 2020) $year = date('Y');
                        
                        // Create month name
                        $monthName = date('F', mktime(0, 0, 0, $month, 1, $year));
                        
                        // Get first day of month and total days
                        $firstDay = mktime(0, 0, 0, $month, 1, $year);
                        $totalDays = (int)date('t', $firstDay);
                        $startDay = (int)date('w', $firstDay); // 0 = Sunday
                        
                        // Get current date for highlighting today
                        $today = (int)date('j');
                        $currentMonth = (int)date('m');
                        $currentYear = (int)date('Y');
                        
                        // Get all approved leaves for this month
                        $sql = "SELECT tblleaves.FromDate, tblleaves.ToDate, 
                                       tblemployees.FirstName, tblemployees.LastName,
                                       tblleaves.LeaveType, tblleaves.id as lid
                                FROM tblleaves 
                                JOIN tblemployees ON tblleaves.empid = tblemployees.id
                                WHERE tblleaves.Status = 1
                                ORDER BY tblleaves.FromDate ASC";
                        
                        $query = $dbh->prepare($sql);
                        $query->execute();
                        $allLeaves = $query->fetchAll(PDO::FETCH_OBJ);
                        
                        // Organize leaves by date
                        $leaveDates = array();
                        
                        foreach($allLeaves as $leave) {
                            // Parse dates - handle dd/mm/yyyy format
                            $fromParts = explode('/', $leave->FromDate);
                            $toParts = explode('/', $leave->ToDate);
                            
                            if(count($fromParts) == 3 && count($toParts) == 3) {
                                // Convert to YYYY-MM-DD for comparison
                                $fromDateStr = $fromParts[2] . '-' . $fromParts[1] . '-' . $fromParts[0];
                                $toDateStr = $toParts[2] . '-' . $toParts[1] . '-' . $toParts[0];
                                
                                $fromTimestamp = strtotime($fromDateStr);
                                $toTimestamp = strtotime($toDateStr);
                                
                                if($fromTimestamp && $toTimestamp) {
                                    // Check if this leave falls in current month/year
                                    $fromMonth = (int)date('m', $fromTimestamp);
                                    $fromYear = (int)date('Y', $fromTimestamp);
                                    $toMonth = (int)date('m', $toTimestamp);
                                    $toYear = (int)date('Y', $toTimestamp);
                                    
                                    // If leave spans across our target month
                                    if(($fromYear == $year && $fromMonth == $month) || 
                                       ($toYear == $year && $toMonth == $month) ||
                                       ($fromYear < $year && $toYear > $year) ||
                                       ($fromYear == $year && $fromMonth < $month && $toMonth > $month)) {
                                        
                                        // Get all days in this leave that are in the current month
                                        $currentStart = max($fromTimestamp, mktime(0, 0, 0, $month, 1, $year));
                                        $currentEnd = min($toTimestamp, mktime(0, 0, 0, $month, $totalDays, $year));
                                        
                                        for($time = $currentStart; $time <= $currentEnd; $time += 86400) {
                                            $dayNum = (int)date('j', $time);
                                            if(!isset($leaveDates[$dayNum])) {
                                                $leaveDates[$dayNum] = array();
                                            }
                                            
                                            // Check if this employee already added for this day (avoid duplicates)
                                            $exists = false;
                                            foreach($leaveDates[$dayNum] as $existing) {
                                                if($existing['name'] == $leave->FirstName . ' ' . $leave->LastName) {
                                                    $exists = true;
                                                    break;
                                                }
                                            }
                                            
                                            if(!$exists) {
                                                $leaveDates[$dayNum][] = array(
                                                    'name' => $leave->FirstName . ' ' . $leave->LastName,
                                                    'type' => $leave->LeaveType,
                                                    'lid' => $leave->lid
                                                );
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        
                        // Count total leaves this month
                        $totalLeavesThisMonth = 0;
                        foreach($leaveDates as $dayLeaves) {
                            $totalLeavesThisMonth += count($dayLeaves);
                        }
                        ?>

                        <div class="calendar-header">
                            <div class="calendar-title">
                                <i class="material-icons">date_range</i>
                                <?php echo $monthName . ' ' . $year; ?>
                                <span class="badge blue" style="margin-left: 15px; background: #00acc1; color: white; padding: 5px 10px; border-radius: 4px;">
                                    <?php echo $totalLeavesThisMonth; ?> Leaves
                                </span>
                            </div>
                            <div class="calendar-nav">
                                <?php
                                $prevMonth = $month - 1;
                                $prevYear = $year;
                                if($prevMonth == 0) {
                                    $prevMonth = 12;
                                    $prevYear = $year - 1;
                                }
                                
                                $nextMonth = $month + 1;
                                $nextYear = $year;
                                if($nextMonth == 13) {
                                    $nextMonth = 1;
                                    $nextYear = $year + 1;
                                }
                                ?>
                                <a href="?month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>" class="btn waves-effect waves-light blue">
                                    <i class="material-icons left">chevron_left</i> Prev
                                </a>
                                <a href="?month=<?php echo date('m'); ?>&year=<?php echo date('Y'); ?>" class="btn waves-effect waves-light blue">
                                    <i class="material-icons left">today</i> This Month
                                </a>
                                <a href="?month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>" class="btn waves-effect waves-light blue">
                                    Next <i class="material-icons right">chevron_right</i>
                                </a>
                            </div>
                        </div>

                        <table class="calendar-table">
                            <thead>
                                <tr>
                                    <th>Sun</th>
                                    <th>Mon</th>
                                    <th>Tue</th>
                                    <th>Wed</th>
                                    <th>Thu</th>
                                    <th>Fri</th>
                                    <th>Sat</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <?php
                                    // Fill empty cells before month starts
                                    for($i = 0; $i < $startDay; $i++) {
                                        echo '<td class="empty-day"></td>';
                                    }
                                    
                                    // Fill the days
                                    for($day = 1; $day <= $totalDays; $day++) {
                                        if(($day + $startDay - 1) % 7 == 0 && $day != 1) {
                                            echo '</tr><tr>';
                                        }
                                        
                                        $todayClass = '';
                                        if($day == $today && $month == $currentMonth && $year == $currentYear) {
                                            $todayClass = 'today';
                                        }
                                        
                                        echo '<td class="' . $todayClass . '">';
                                        echo '<div class="date">' . $day . '</div>';
                                        
                                        // Show leaves for this day
                                        if(isset($leaveDates[$day])) {
                                            foreach($leaveDates[$day] as $leave) {
                                                echo '<a href="leave-details.php?leaveid=' . $leave['lid'] . '" style="text-decoration: none;">';
                                                echo '<div class="leave-event tooltipped" data-position="top" data-tooltip="' . $leave['name'] . ' - ' . $leave['type'] . '">';
                                                echo '<i class="material-icons tiny">event_busy</i> ';
                                                $nameParts = explode(' ', $leave['name']);
                                                $initial = substr($nameParts[0], 0, 1) . '.';
                                                $lastName = isset($nameParts[1]) ? $nameParts[1] : '';
                                                echo $initial . ' ' . $lastName;
                                                echo '</div>';
                                                echo '</a>';
                                            }
                                        }
                                        
                                        echo '</td>';
                                    }
                                    
                                    // Fill remaining empty cells
                                    $totalCells = $totalDays + $startDay;
                                    $remainingCells = 7 - ($totalCells % 7);
                                    if($remainingCells < 7) {
                                        for($i = 0; $i < $remainingCells; $i++) {
                                            echo '<td class="empty-day"></td>';
                                        }
                                    }
                                    ?>
                                </tr>
                            </tbody>
                        </table>
                        
                        <div class="calendar-legend">
                            <div class="legend-item">
                                <div class="legend-color" style="background-color: #4caf50;"></div>
                                <span>Employee on Leave</span>
                            </div>
                            <div class="legend-item">
                                <div class="legend-color" style="background-color: #e8f5e8;"></div>
                                <span>Today</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Javascripts -->
    <script src="assets/plugins/jquery/jquery-2.2.0.min.js"></script>
    <script src="assets/plugins/materialize/js/materialize.min.js"></script>
    <script src="assets/plugins/material-preloader/js/materialPreloader.min.js"></script>
    <script src="assets/plugins/jquery-blockui/jquery.blockui.js"></script>
    <script src="assets/js/alpha.min.js"></script>
    
    <script>
    $(document).ready(function() {
        $('.tooltipped').tooltip();
    });
    </script>
</body>
</html>