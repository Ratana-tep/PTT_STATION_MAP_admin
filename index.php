<?php
session_start();
date_default_timezone_set('Asia/Phnom_Penh');

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header('Location: login.php');
    exit();
}

// Check if the login session is older than 12 hours
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > 43200) {
    // Session has expired
    session_unset();
    session_destroy();
    header('Location: login.php');
    exit();
}

// If session is still valid, update login time to reset 12-hour timer
$_SESSION['login_time'] = time();
?>

<?php
date_default_timezone_set('Asia/Phnom_Penh');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle form submission
    $promotions = json_decode(file_get_contents('./data/promotions.json'), true);

    // Retrieve data from the form
    $station_id = $_POST['station_id'] ?? null;
    $promotion_id = $_POST['promotion_id'];
    $new_promotion_id = $_POST['new_promotion_id'] ?? '';
    $end_time = $_POST['end_time'];
    $action = $_POST['action'];

    // Convert end_time to Cambodia time zone
    $end_time = (new DateTime($end_time, new DateTimeZone('Asia/Phnom_Penh')))->format('Y-m-d\TH:i:s\Z');

    if ($action === 'add_to_all') {
        // Add promotion to all stations
        foreach ($promotions['PROMOTIONS'] as &$station) {
            $station['promotions'][] = [
                'promotion_id' => $promotion_id,
                'end_time' => $end_time
            ];
        }
    } else {
        // Find the station in the JSON data
        foreach ($promotions['PROMOTIONS'] as &$station) {
            if ($station['station_id'] == $station_id) {
                if ($action == 'add') {
                    $station['promotions'][] = [
                        'promotion_id' => $promotion_id,
                        'end_time' => $end_time
                    ];
                } elseif ($action == 'edit') {
                    foreach ($station['promotions'] as &$promotion) {
                        if ($promotion['promotion_id'] == $promotion_id) {
                            $promotion['promotion_id'] = $new_promotion_id;
                            $promotion['end_time'] = $end_time;
                        }
                    }
                } elseif ($action == 'delete') {
                    foreach ($station['promotions'] as $key => $promotion) {
                        if ($promotion['promotion_id'] == $promotion_id) {
                            unset($station['promotions'][$key]);
                        }
                    }
                }
                break;
            }
        }
    }

    // Save the updated data back to the JSON file
    file_put_contents('./data/promotions.json', json_encode($promotions, JSON_PRETTY_PRINT));

    header('Location: index.php');
    exit();
}

// Load promotions data
$promotions = json_decode(file_get_contents('./data/promotions.json'), true);

// Load markers data
$markers = json_decode(file_get_contents('./data/markers.json'), true);

// Combine promotions with markers data based on station ID
$combined_data = [];
foreach ($promotions['PROMOTIONS'] as $promotion) {
    foreach ($markers['STATION'] as $station) {
        if ($station['id'] == $promotion['station_id']) {
            $promotion['title'] = $station['title'];
            $promotion['address'] = $station['address'];
            $combined_data[] = $promotion;
            break;
        }
    }
}

// Prepare data for charts
$station_titles = [];
$promotion_counts = [];
$monthly_promotions = [];
$promotion_distribution = [];
$province_promotion_status = [];
$total_stations = 0;
$total_fleet = 0;
$total_ev = 0;

foreach ($combined_data as $promotion) {
    $station_titles[] = $promotion['title'];
    $promotion_counts[] = count($promotion['promotions']);

    foreach ($promotion['promotions'] as $promo) {
        $month = date('F', strtotime($promo['end_time']));
        if (!isset($monthly_promotions[$month])) {
            $monthly_promotions[$month] = 0;
        }
        $monthly_promotions[$month]++;

        if (!isset($promotion_distribution[$promo['promotion_id']])) {
            $promotion_distribution[$promo['promotion_id']] = 0;
        }
        $promotion_distribution[$promo['promotion_id']]++;

        // Province promotion status
        foreach ($markers['STATION'] as $station) {
            if ($station['id'] == $promotion['station_id']) {
                $province = $station['province'];
                if (!isset($province_promotion_status[$province])) {
                    $province_promotion_status[$province] = ['active' => 0, 'expired' => 0];
                }
                $current_time = new DateTime('now', new DateTimeZone('Asia/Phnom_Penh'));
                $end_time = new DateTime($promo['end_time']);
                if ($end_time < $current_time) {
                    $province_promotion_status[$province]['expired']++;
                } else {
                    $province_promotion_status[$province]['active']++;
                }
                break;
            }
        }
    }
}

// Calculate totals
foreach ($markers['STATION'] as $station) {
    $total_stations++;
    if (isset($station['service']) && is_array($station['service']) && in_array('Fleet card', $station['service'])) {
        $total_fleet++;
    }
    if (isset($station['other_product']) && is_array($station['other_product']) && in_array('EV', $station['other_product'])) {
        $total_ev++;
    }
}

// Process data for expiration status
$active_count = 0;
$expired_count = 0;
$current_time = new DateTime('now', new DateTimeZone('Asia/Phnom_Penh'));

foreach ($combined_data as $promotion) {
    foreach ($promotion['promotions'] as $promo) {
        $end_time = new DateTime($promo['end_time']);
        if ($end_time < $current_time) {
            $expired_count++;
        } else {
            $active_count++;
        }
    }
}

// Prepare data for countdowns
$promotion_end_times = [];
foreach ($combined_data as $promotion) {
    foreach ($promotion['promotions'] as $promo) {
        $promotion_end_times[$promo['promotion_id']] = $promo['end_time'];
    }
}

// Convert data for use in JS
$station_titles_json = json_encode($station_titles);
$promotion_counts_json = json_encode($promotion_counts);
$monthly_promotions_json = json_encode(array_values($monthly_promotions));
$monthly_labels_json = json_encode(array_keys($monthly_promotions));
$promotion_distribution_json = json_encode(array_values($promotion_distribution));
$promotion_labels_json = json_encode(array_keys($promotion_distribution));
$expiration_status_json = json_encode([$active_count, $expired_count]);
$promotion_end_times_json = json_encode($promotion_end_times);

// Data for province promotions chart
$province_labels = json_encode(array_keys($province_promotion_status));
$province_active_counts = json_encode(array_column($province_promotion_status, 'active'));
$province_expired_counts = json_encode(array_column($province_promotion_status, 'expired'));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f0f2f5;
            margin: 0;
        }

        #wrapper {
            display: flex;
            height: 100vh;
        }

        #sidebar-wrapper {
            width: 250px;
            background-color: #343a40;
            color: white;
            transition: width 0.3s ease;
        }

        #sidebar-wrapper.toggled {
            width: 60px;
        }

        .sidebar-heading {
            padding: 20px;
            font-size: 1.25em;
            font-weight: bold;
            background: #007bff;
            text-align: center;
        }

        .sidebar-heading img {
            margin-right: 10px;
        }

        .list-group-item {
            border: none;
            color: white;
            background-color: #343a40;
            transition: background-color 0.3s ease;
        }

        .list-group-item:hover {
            background-color: #495057;
        }

        .list-group-item-action {
            color: white;
        }

        #page-content-wrapper {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            transition: margin-left 0.3s ease;
        }

        #page-content-wrapper.toggled {
            margin-left: -190px;
        }

        .navbar {
            padding: 10px 15px;
            background-color: whitesmoke;
            color: white;
            box-shadow: 0 10px 16px -4px rgba(0, 0, 0, 0.6);
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            color: white;
        }

        .navbar-brand img {
            margin-right: 10px;
        }

        .content-section {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            opacity: 0;
            transform: translateY(20px);
            animation: fadeInUp 0.6s forwards;
        }

        .content-section:nth-child(even) {
            animation-delay: 0.2s;
        }

        .content-section:nth-child(odd) {
            animation-delay: 0.4s;
        }

        .content-section h2 {
            font-size: 1.5em;
            margin-bottom: 20px;
            color: #343a40;
        }

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            margin-bottom: 20px;
        }

        .card:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
        }

        .card-header {
            background-color: transparent;
            color: white;
            border-bottom: none;
            font-size: 1.25em;
            font-weight: bold;
            padding: 20px;
            text-align: center;
        }

        .card-body {
            padding: 30px;
            text-align: center;
        }

        .card-body h1 {
            margin: 0;
            font-size: 2.5em;
        }

        .card-icon {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 2em;
            color: rgba(255, 255, 255, 0.7);
        }

        .bg-primary {
            background-color: #007bff !important;
        }

        .bg-success {
            background-color: #28a745 !important;
        }

        .bg-info {
            background-color: #17a2b8 !important;
        }

        .countdown-timer {
            font-size: 1.2em;
            font-weight: bold;
            color: #28a745;
        }

        .promotion-id {
            font-size: 1.2em;
        }

        .chart-container {
            position: relative;
            height: 40vh;
        }

        h1 {
            color: #343a40;
            font-size: 2em;
            margin-bottom: 20px;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        list-group-item {
            display: flex;
            align-items: center;
        }

        .material-icons {
            margin-right: 8px;
        }
    </style>
</head>

<body>
    <div class="d-flex" id="wrapper">
        <!-- Sidebar -->
        <div id="sidebar-wrapper">
            <div class="sidebar-heading">
                <img src="" width="30" height="30" alt="">
                PTT Map Finding
            </div>
            <div class="list-group list-group-flush">
                <a href="index.php" class="list-group-item list-group-item-action"><i class="material-icons">dashboard</i> Overview</a>
                <a href="manage.php" class="list-group-item list-group-item-action"><i class="material-icons">campaign</i> Marketing</a>
                <a href="station_admin.php" class="list-group-item list-group-item-action"><i class="material-icons">local_gas_station</i> Station</a>
            </div>
        </div>
        <!-- /#sidebar-wrapper -->

        <!-- Page Content -->
        <div id="page-content-wrapper">
            <nav class="navbar navbar-expand-lg navbar-light">
                <!-- <button class="btn btn-primary" id="menu-toggle">Toggle Menu</button> -->
                <a class="navbar-brand ml-3" href="#">
                    <img src="./pictures/logo_Station.png" width="200" height="auto" alt="Logo">
                </a>
                <div class="ml-auto">
                    <a href="logout.php" class="btn btn-danger">Logout</a>
                </div>
            </nav>
            <div class="container-fluid mt-5">
                <h1>Promotions Dashboard</h1>

                <div class="row">
                    <div class="col-lg-4 col-md-6">
                        <div class="card bg-primary text-white mb-3">
                            <div class="card-icon"><i class="fas fa-gas-pump"></i></div>
                            <div class="card-header">TOTAL STATIONS</div>
                            <div class="card-body">
                                <h1><?php echo $total_stations; ?></h1>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <div class="card bg-success text-white mb-3">
                            <div class="card-icon"><i class="fas fa-id-card"></i></div>
                            <!-- Corrected the icon for fleet card -->
                            <div class="card-header">TOTAL FLEET</div>
                            <div class="card-body">
                                <h1><?php echo $total_fleet; ?></h1>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <div class="card bg-info text-white mb-3">
                            <div class="card-icon"><i class="fas fa-charging-station"></i></div>
                            <div class="card-header">TOTAL EV</div>
                            <div class="card-body">
                                <h1><?php echo $total_ev; ?></h1>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="content-section">
                    <h2>Promotion Countdowns</h2>
                    <ul id="promotion-list" class="list-group">
                        <?php foreach ($promotion_end_times as $promotion_id => $end_time) : ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span class="promotion-id"><?php echo $promotion_id; ?></span>
                                <span class="countdown-timer" data-end-time="<?php echo $end_time; ?>"></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="row mt-4">
                    <div class="col-lg-6">
                        <div class="content-section">
                            <h2>Promotion Distribution</h2>
                            <div class="chart-container">
                                <canvas id="chart3"></canvas>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="content-section">
                            <h2>Province Promotion Status</h2>
                            <div class="chart-container">
                                <canvas id="chart5"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const ctx3 = document.getElementById('chart3').getContext('2d');
        const ctx5 = document.getElementById('chart5').getContext('2d');

        const promotionDistribution = <?php echo $promotion_distribution_json; ?>;
        const promotionLabels = <?php echo $promotion_labels_json; ?>;
        const provinceLabels = <?php echo $province_labels; ?>;
        const provinceActiveCounts = <?php echo $province_active_counts; ?>;
        const provinceExpiredCounts = <?php echo $province_expired_counts; ?>;

        const chart3 = new Chart(ctx3, {
            type: 'pie',
            data: {
                labels: promotionLabels,
                datasets: [{
                    label: 'Promotion Distribution',
                    data: promotionDistribution,
                    backgroundColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)'
                    ],
                    borderColor: [
                        'rgba(255, 99, 132, 1)',
                        'rgba(54, 162, 235, 1)',
                        'rgba(255, 206, 86, 1)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    },
                    animation: {
                        duration: 2000,
                        easing: 'easeInOutBounce'
                    }
                }
            }
        });

        const chart5 = new Chart(ctx5, {
            type: 'bar',
            data: {
                labels: provinceLabels,
                datasets: [{
                        label: 'Active Promotions',
                        data: provinceActiveCounts,
                        backgroundColor: 'rgba(75, 192, 192, 1)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Expired Promotions',
                        data: provinceExpiredCounts,
                        backgroundColor: 'rgba(255, 99, 132, 1)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom'
                    },
                    animation: {
                        duration: 2000,
                        easing: 'easeInOutBounce'
                    }
                }
            }
        });

        function updateCountdowns() {
            const now = new Date().getTime();

            document.querySelectorAll('.countdown-timer').forEach(timer => {
                const endTime = new Date(timer.dataset.endTime).getTime();
                const distance = endTime - now;

                if (distance < 0) {
                    timer.innerHTML = "EXPIRED";
                    timer.style.color = 'red';
                    return;
                }

                const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                timer.innerHTML = `${days}d ${hours}h ${minutes}m ${seconds}s`;
            });
        }

        setInterval(updateCountdowns, 1000);
        updateCountdowns(); // Initial call to display countdowns immediately
    </script>
</body>

</html>