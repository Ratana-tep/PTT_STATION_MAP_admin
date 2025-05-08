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
}date_default_timezone_set('Asia/Phnom_Penh');
	$current_time = new DateTime('now');
	
	$promotions = json_decode(file_get_contents('./data/promotions.json'), true, 512, JSON_UNESCAPED_UNICODE);
	$active_promotions = [];
	
	foreach ($promotions['PROMOTIONS'] as $station) {
		foreach ($station['promotions'] as $promo) {
			$end_time = new DateTime($promo['end_time']);
			if ($end_time > $current_time) {
				$time_diff = $end_time->diff($current_time);
				$days = $time_diff->days;
				$hours = $time_diff->h;
				$minutes = $time_diff->i;
				$seconds = $time_diff->s;
				$promo['countdown'] = sprintf("%dd %dh %dm %ds", $days, $hours, $minutes, $seconds);
				$active_promotions[] = $promo;
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
	if (isset($station['other_product']) && is_array($station['other_product']) && in_array('Onion', $station['other_product'])) {
		$total_onion++;
	}
	if (isset($station['description']) && is_array($station['description']) && in_array('Amazon', $station['description'])) {
		$total_amazon++;
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
    <title>PTT Promotions Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.12.0/dist/cdn.min.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        :root {
            --primary: #3a86ff;
            --secondary: #8338ec;
            --accent: #ff006e;
            --dark: #1a1a2e;
            --light: #f8f9fa;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f5f7fa;
            color: #1e293b;
        }
        
        .glass-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.18);
            box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.1);
        }
        
        .gradient-bg {
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
        }
        
        .countdown-active {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(74, 222, 128, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(74, 222, 128, 0); }
            100% { box-shadow: 0 0 0 0 rgba(74, 222, 128, 0); }
        }
        
        .sidebar-item {
            transition: all 0.3s ease;
            border-left: 3px solid transparent;
        }
        
        .sidebar-item:hover {
            background-color: rgba(255, 255, 255, 0.1);
            border-left: 3px solid white;
        }
        
        .sidebar-item.active {
            background-color: rgba(255, 255, 255, 0.2);
            border-left: 3px solid white;
        }
        
        .chart-container {
            height: 350px;
        }
    </style>
</head>

<body class="min-h-screen">
  <div class="flex flex-col h-full" x-data="{ mobileMenuOpen: false }">
    <!-- Header Bar -->
    <header class="bg-white shadow-md">
        <div class="container mx-auto px-4">
            <div class="flex items-center justify-between h-16">
                <!-- Logo and Brand -->
                <div class="flex items-center space-x-2">
                    <img src="./pictures/logo_Station.png" alt="PTT Logo" class="h-8 w-auto">
                </div>
                
                <!-- Main Navigation - Desktop -->
                <nav class="hidden md:flex items-center space-x-4">
                    <a href="index.php" class="flex items-center px-4 py-2 rounded-lg <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php' ? 'bg-blue-100 text-blue-600' : 'text-gray-600 hover:bg-gray-100'); ?> transition-all">
                        <i class="fas fa-chart-pie w-6 text-center"></i>
                        <span class="ml-2">Overview</span>
                    </a>
                    <a href="manage.php" class="flex items-center px-4 py-2 rounded-lg <?php echo (basename($_SERVER['PHP_SELF']) == 'manage.php' ? 'bg-blue-100 text-blue-600' : 'text-gray-600 hover:bg-gray-100'); ?> transition-all">
                        <i class="fas fa-bullhorn w-6 text-center"></i>
                        <span class="ml-2">Marketing</span>
                    </a>
                    <a href="station_admin.php" class="flex items-center px-4 py-2 rounded-lg <?php echo (basename($_SERVER['PHP_SELF']) == 'station_admin.php' ? 'bg-blue-100 text-blue-600' : 'text-gray-600 hover:bg-gray-100'); ?> transition-all">
                        <i class="fas fa-gas-pump w-6 text-center"></i>
                        <span class="ml-2">Stations</span>
                    </a>
                </nav>
                
                <!-- Right Side Controls -->
                <div class="flex items-center space-x-4">
                    <div class="relative hidden md:block">
                        <input type="text" placeholder="Search..." class="pl-10 pr-4 py-2 rounded-full border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" aria-label="Search">
                        <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    </div>

                    <button class="md:hidden text-gray-600 focus:outline-none" @click="mobileMenuOpen = !mobileMenuOpen" aria-label="Toggle menu">
                        <i class="fas fa-bars text-xl"></i>
                    </button>
                </div>
            </div>
            
            <!-- Mobile Menu -->
            <div x-show="mobileMenuOpen" x-transition class="md:hidden py-2 px-4 bg-white border-t border-gray-200">
                <div class="flex flex-col space-y-2">
                    <a href="index.php" class="flex items-center px-3 py-2 rounded-lg <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php' ? 'bg-blue-100 text-blue-600 font-semibold' : 'text-gray-600 hover:bg-gray-100'); ?>">
                        <i class="fas fa-chart-pie w-6 text-center"></i>
                        <span class="ml-2">Overview</span>
                        <?php if (basename($_SERVER['PHP_SELF']) == 'index.php'): ?>
                            <span class="ml-auto text-xs bg-blue-100 text-blue-600 px-2 py-1 rounded-full">Current</span>
                        <?php endif; ?>
                    </a>
                    <a href="manage.php" class="flex items-center px-3 py-2 rounded-lg <?php echo (basename($_SERVER['PHP_SELF']) == 'manage.php' ? 'bg-blue-100 text-blue-600 font-semibold' : 'text-gray-600 hover:bg-gray-100'); ?>">
                        <i class="fas fa-bullhorn w-6 text-center"></i>
                        <span class="ml-2">Marketing</span>
                        <?php if (basename($_SERVER['PHP_SELF']) == 'manage.php'): ?>
                            <span class="ml-auto text-xs bg-blue-100 text-blue-600 px-2 py-1 rounded-full">Current</span>
                        <?php endif; ?>
                    </a>
                    <a href="station_admin.php" class="flex items-center px-3 py-2 rounded-lg <?php echo (basename($_SERVER['PHP_SELF']) == 'station_admin.php' ? 'bg-blue-100 text-blue-600 font-semibold' : 'text-gray-600 hover:bg-gray-100'); ?>">
                        <i class="fas fa-gas-pump w-6 text-center"></i>
                        <span class="ml-2">Stations</span>
                        <?php if (basename($_SERVER['PHP_SELF']) == 'station_admin.php'): ?>
                            <span class="ml-auto text-xs bg-blue-100 text-blue-600 px-2 py-1 rounded-full">Current</span>
                        <?php endif; ?>
                    </a>
                    <div class="relative pt-2">
                        <input type="text" placeholder="Search..." class="w-full pl-10 pr-4 py-2 rounded-full border border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent" aria-label="Search">
                        <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    </div>
                    <a href="logout.php" class="flex items-center px-3 py-2 rounded-lg text-gray-600 hover:bg-gray-100">
                        <i class="fas fa-sign-out-alt w-6 text-center"></i>
                        <span class="ml-2">Logout</span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <div class="flex-1 flex flex-col">
        <!-- Content Header -->
        <div class="bg-white shadow-sm">
            <div class="container mx-auto px-6 py-4">
                <h1 class="text-3xl font-bold text-gray-800">Promotions Dashboard</h1>
                <p class="text-gray-600">Monitor and manage all station promotions in real-time</p>
            </div>
        </div>

        <!-- Main Content Area -->
        <main class="flex-1 p-6 container mx-auto">
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Total Stations -->
                <div class="glass-card p-6 rounded-xl shadow-md hover:shadow-lg transition-shadow">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-gray-500 font-medium">Total Stations</p>
                            <h3 class="text-3xl font-bold mt-2"><?php echo $total_stations; ?></h3>
                        </div>
                        <div class="p-3 rounded-full bg-indigo-100 text-indigo-600">
                            <i class="fas fa-gas-pump text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <span class="text-sm text-gray-500">Updated just now</span>
                    </div>
                </div>

                <!-- Total Fleet -->
                <div class="glass-card p-6 rounded-xl shadow-md hover:shadow-lg transition-shadow">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-gray-500 font-medium">Total Fleet</p>
                            <h3 class="text-3xl font-bold mt-2"><?php echo $total_fleet; ?></h3>
                        </div>
                        <div class="p-3 rounded-full bg-green-100 text-green-600">
                            <i class="fas fa-id-card text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <span class="text-sm text-gray-500"></span>
                    </div>
                </div>

                <!-- Total EV -->
                <div class="glass-card p-6 rounded-xl shadow-md hover:shadow-lg transition-shadow">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-gray-500 font-medium">Total EV Stations</p>
                            <h3 class="text-3xl font-bold mt-2"><?php echo $total_ev; ?></h3>
                        </div>
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                            <i class="fas fa-charging-station text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <span class="text-sm text-gray-500"></span>
                    </div>
                </div>
                <div class="glass-card p-6 rounded-xl shadow-md hover:shadow-lg transition-shadow">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-gray-500 font-medium">Total Onion Stations</p>
                            <h3 class="text-3xl font-bold mt-2"><?php echo $total_onion; ?></h3>
                        </div>
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                            <i class="fas fa-car-battery text-xl"></i>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <span class="text-sm text-gray-500"></span>
                    </div>
                </div>
                <div class="glass-card p-6 rounded-xl shadow-md hover:shadow-lg transition-shadow">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-gray-500 font-medium">Total Amazon Stations</p>
                            <h3 class="text-3xl font-bold mt-2"><?php echo $total_amazon; ?></h3>
                        </div>
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                            <i class="fas fa-store text-xl"></i> <i class="fas fa-coffee text-xl ml-2"></i>

                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <span class="text-sm text-gray-500"></span>
                    </div>
                </div>
                                <div class="glass-card p-6 rounded-xl shadow-md hover:shadow-lg transition-shadow">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="text-gray-500 font-medium">Total EV Stations</p>
                            <h3 class="text-3xl font-bold mt-2"><?php echo $total_ev; ?></h3>
                        </div>
                        <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                           <i class="fas fa-store text-xl"></i> <span class="ml-2 text-xl font-bold">7-Eleven</span>
                        </div>
                    </div>
                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <span class="text-sm text-gray-500"></span>
                    </div>
                </div>
            </div>

            <!-- Promotion Countdowns -->
            <div class="glass-card p-6 rounded-xl shadow-md mb-8">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold text-gray-800">Active Promotions Countdown</h2>
                    <button class="text-indigo-600 hover:text-indigo-800">
                        <i class="fas fa-sync-alt"></i> Refresh
                    </button>
                </div>
                <div class="space-y-3">
                    <?php foreach ($promotion_end_times as $promotion_id => $end_time):
                        $current_time = new DateTime('now', new DateTimeZone('Asia/Phnom_Penh'));
                        $end_time_obj = new DateTime($end_time);
                        $is_active = $end_time_obj > $current_time;
                    ?>
                    <div class="flex items-center justify-between p-4 rounded-lg <?php echo $is_active ? 'bg-green-50 countdown-active' : 'bg-red-50'; ?>">
                        <div class="flex items-center space-x-3">
                            <div class="p-2 rounded-full <?php echo $is_active ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'; ?>">
                                <i class="fas <?php echo $is_active ? 'fa-clock' : 'fa-exclamation-circle'; ?>"></i>
                            </div>
                            <span class="font-medium"><?php echo $promotion_id; ?></span>
                        </div>
                        <span class="<?php echo $is_active ? 'text-green-600 font-bold' : 'text-red-600'; ?>"
                              data-end-time="<?php echo $end_time; ?>">
                            <?php
                                if ($is_active) {
                                    $interval = $current_time->diff($end_time_obj);
                                    echo $interval->format('%ad %hh %im %ss');
                                } else {
                                    echo "EXPIRED";
                                }
                            ?>
                        </span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Charts Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Promotion Distribution -->
                <div class="glass-card p-6 rounded-xl shadow-md">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Promotion Distribution</h2>
                    <div class="chart-container">
                        <canvas id="promotionDistributionChart"></canvas>
                    </div>
                </div>

                <!-- Province Promotion Status -->
                <div class="glass-card p-6 rounded-xl shadow-md">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Province Promotion Status</h2>
                    <div class="chart-container">
                        <canvas id="provincePromotionChart"></canvas>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

    <script>
        // Promotion Distribution Chart
        const promotionDistributionCtx = document.getElementById('promotionDistributionChart').getContext('2d');
        const promotionDistributionChart = new Chart(promotionDistributionCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo $promotion_labels_json; ?>,
                datasets: [{
                    data: <?php echo $promotion_distribution_json; ?>,
                    backgroundColor: [
                        '#3a86ff',
                        '#8338ec',
                        '#ff006e',
                        '#ffbe0b',
                        '#fb5607',
                        '#9b5de5'
                    ],
                    borderWidth: 0,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                },
                cutout: '70%',
            }
        });

        // Province Promotion Chart
        const provincePromotionCtx = document.getElementById('provincePromotionChart').getContext('2d');
        const provincePromotionChart = new Chart(provincePromotionCtx, {
            type: 'bar',
            data: {
                labels: <?php echo $province_labels; ?>,
                datasets: [
                    {
                        label: 'Active Promotions',
                        data: <?php echo $province_active_counts; ?>,
                        backgroundColor: '#3a86ff',
                        borderRadius: 6,
                    },
                    {
                        label: 'Expired Promotions',
                        data: <?php echo $province_expired_counts; ?>,
                        backgroundColor: '#ff006e',
                        borderRadius: 6,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: '#e5e7eb'
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    tooltip: {
                        mode: 'index',
                        intersect: false,
                    }
                }
            }
        });

        // Update countdowns every second
        function updateCountdowns() {
            document.querySelectorAll('[data-end-time]').forEach(element => {
                const endTime = new Date(element.dataset.endTime).getTime();
                const now = new Date().getTime();
                const distance = endTime - now;

                if (distance < 0) {
                    element.textContent = "EXPIRED";
                    element.classList.remove('text-green-600');
                    element.classList.add('text-red-600');
                    return;
                }

                const days = Math.floor(distance / (1000 * 60 * 60 * 24));
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                element.textContent = `${days}d ${hours}h ${minutes}m ${seconds}s`;
            });
        }

        setInterval(updateCountdowns, 1000);
    </script>
</body>
</html>