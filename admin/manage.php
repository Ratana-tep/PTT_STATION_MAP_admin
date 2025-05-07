<?php
//session_start();
//date_default_timezone_set('Asia/Phnom_Penh');
//
//// Ensure the correct content type for handling Khmer characters
//header('Content-Type: text/html; charset=utf-8');
//
//// Check if the user is logged in
//if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
//    header('Location: login.php');
//    exit();
//}
//
//// Check if the login session is older than 12 hours
//if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > 43200) {
//    // Session has expired
//    session_unset();
//    session_destroy();
//    header('Location: login.php');
//    exit();
//}
//
//// If session is still valid, update login time to reset 12-hour timer
//$_SESSION['login_time'] = time();
//?>
<?php
	date_default_timezone_set('Asia/Phnom_Penh');
	
	// Function to synchronize promotions.json with markers.json and prevent duplicate station_id
	function syncPromotionsWithMarkers(&$promotions, $markers) {
		// Get all station IDs from markers.json
		$marker_station_ids = array_column($markers['STATION'], 'id');
		
		// Deduplicate promotions by station_id, keeping the last entry
		$deduplicated_promotions = [];
		$seen_station_ids = [];
		
		foreach ($promotions['PROMOTIONS'] as $promotion) {
			$station_id = $promotion['station_id'];
			
			// Skip if we've already processed this station_id
			if (isset($seen_station_ids[$station_id])) {
				continue;
			}
			
			// Mark this station_id as seen
			$seen_station_ids[$station_id] = true;
			$deduplicated_promotions[] = $promotion;
		}
		
		// Update promotions array with deduplicated data
		$promotions['PROMOTIONS'] = $deduplicated_promotions;
		
		// Add missing stations from markers.json
		foreach ($marker_station_ids as $station_id) {
			if (!isset($seen_station_ids[$station_id])) {
				$promotions['PROMOTIONS'][] = [
					'id' => $station_id,
					'station_id' => $station_id,
					'promotions' => []
				];
				$seen_station_ids[$station_id] = true;
			}
		}
		
		// Remove stations from promotions.json that no longer exist in markers.json
		$promotions['PROMOTIONS'] = array_filter($promotions['PROMOTIONS'], function ($promotion) use ($marker_station_ids) {
			return in_array($promotion['station_id'], $marker_station_ids);
		});
		
		// Reindex the array to avoid gaps
		$promotions['PROMOTIONS'] = array_values($promotions['PROMOTIONS']);
	}
	
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		// Load promotions and markers data
		$promotions = json_decode(file_get_contents('./data/promotions.json'), true, 512, JSON_UNESCAPED_UNICODE);
		$markers = json_decode(file_get_contents('./data/markers.json'), true, 512, JSON_UNESCAPED_UNICODE);
		
		// Check if JSON files loaded correctly
		if (!$promotions || !$markers) {
			die('Error: Unable to load JSON files.');
		}
		
		// Synchronize promotions with markers
		syncPromotionsWithMarkers($promotions, $markers);
		
		$messages = [];
		
		// Handle deleting selected promotions
		if (isset($_POST['delete_all_promotions'])) {
			$selected_promotions = $_POST['selected_promotions'] ?? [];
			foreach ($promotions['PROMOTIONS'] as &$station) {
				$station['promotions'] = array_filter($station['promotions'], function ($promo) use ($selected_promotions) {
					return !in_array($promo['promotion_id'], $selected_promotions);
				});
			}
			file_put_contents('./data/promotions.json', json_encode($promotions, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
			header('Location: manage.php');
			exit();
		}
		
		// Handle clearing a specific promotion
		if (isset($_POST['clear_promotions'])) {
			$selected_promotion = $_POST['selected_promotion'];
			foreach ($promotions['PROMOTIONS'] as &$station) {
				$station['promotions'] = array_filter($station['promotions'], function ($promo) use ($selected_promotion) {
					return $promo['promotion_id'] !== $selected_promotion;
				});
			}
			file_put_contents('./data/promotions.json', json_encode($promotions, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
			header('Location: manage.php');
			exit();
		}
		
		// Handle clearing all expired promotions
		if (isset($_POST['clear_all_expired'])) {
			$current_time = new DateTime('now', new DateTimeZone('Asia/Phnom_Penh'));
			foreach ($promotions['PROMOTIONS'] as &$station) {
				$station['promotions'] = array_filter($station['promotions'], function ($promo) use ($current_time) {
					$end_time = new DateTime($promo['end_time']);
					return $end_time >= $current_time;
				});
			}
			file_put_contents('./data/promotions.json', json_encode($promotions, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
			header('Location: manage.php');
			exit();
		}
		
		// Extract form data
		$station_id = $_POST['station_id'] ?? null;
		$promotion_id = $_POST['promotion_id'];
		$new_promotion_id = $_POST['new_promotion_id'] ?? '';
		$end_time = $_POST['end_time'];
		$description = $_POST['description'];
		$action = $_POST['action'];
		
		// Format end time to the correct format
		$end_time = (new DateTime($end_time, new DateTimeZone('Asia/Phnom_Penh')))->format('Y-m-d\TH:i:s\Z');
		
		// Handle adding promotions
		if ($action === 'add_to_all') {
			$selected_provinces = !empty($_POST['provinces']) ? explode(',', $_POST['provinces']) : [];
			$selected_stations = !empty($_POST['stations']) ? array_map('trim', explode(',', $_POST['stations'])) : [];
			
			// Get valid station IDs from markers.json
			$valid_station_ids = array_column($markers['STATION'], 'id');
			
			foreach ($promotions['PROMOTIONS'] as &$station) {
				$station_id = $station['station_id'];
				$station_province = null;
				
				// Find the province for the current station
				foreach ($markers['STATION'] as $marker) {
					if ($marker['id'] == $station_id) {
						$station_province = $marker['province'];
						break;
					}
				}
				
				if (!empty($selected_stations) && in_array($station_id, $selected_stations) && in_array($station_id, $valid_station_ids)) {
					// Add promotion to specific selected stations
					addPromotionToStation($station, $promotion_id, $end_time, $description);
				} elseif (empty($selected_stations) && !empty($selected_provinces) && in_array($station_province, $selected_provinces)) {
					// Add promotion only to stations in the selected provinces
					addPromotionToStation($station, $promotion_id, $end_time, $description);
				}
			}
		} else {
			foreach ($promotions['PROMOTIONS'] as &$station) {
				if ($station['station_id'] == $station_id) {
					if ($action == 'add') {
						addPromotionToStation($station, $promotion_id, $end_time, $description);
					} elseif ($action == 'edit') {
						foreach ($station['promotions'] as &$promotion) {
							if ($promotion['promotion_id'] == $promotion_id) {
								$promotion['promotion_id'] = $new_promotion_id;
								$promotion['end_time'] = $end_time;
								$promotion['description'] = $description;
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
		
		// Handle image upload
		if (isset($_FILES['promotion_image']) && $_FILES['promotion_image']['error'] == UPLOAD_ERR_OK) {
			$upload_dir = './pictures/promotion/';
			if (!is_dir($upload_dir)) {
				mkdir($upload_dir, 0755, true);
			}
			
			$uploaded_file = $_FILES['promotion_image']['tmp_name'];
			$uploaded_file_type = getimagesize($uploaded_file)['mime'] ?? '';
			$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
			
			if (in_array($uploaded_file_type, $allowed_types)) {
				// Sanitize the promotion_id by replacing spaces with underscores
				$promotion_id = str_replace(' ', '_', trim($promotion_id));
				$new_file_name = $promotion_id . '.jpg';
				$destination = $upload_dir . $new_file_name;
				
				if ($uploaded_file_type == 'image/png' || $uploaded_file_type == 'image/gif') {
					// Convert to JPG
					$image = null;
					if ($uploaded_file_type == 'image/png') {
						$image = imagecreatefrompng($uploaded_file);
					} elseif ($uploaded_file_type == 'image/gif') {
						$image = imagecreatefromgif($uploaded_file);
					}
					
					if ($image !== null) {
						imagejpeg($image, $destination, 100);
						imagedestroy($image);
						echo "<script>alert('Promotion image uploaded and converted to JPG successfully.');</script>";
					} else {
						echo "<script>alert('Failed to convert image to JPG.');</script>";
					}
				} else {
					// Move the JPG file as is
					if (move_uploaded_file($uploaded_file, $destination)) {
						echo "<script>alert('Promotion image uploaded successfully.');</script>";
					} else {
						echo "<script>alert('Failed to upload promotion image.');</script>";
					}
				}
			} else {
				echo "<script>alert('Invalid file type. Only JPG, PNG, and GIF files are allowed.');</script>";
			}
		}
		
		// Save the updated promotions data
		file_put_contents('./data/promotions.json', json_encode($promotions, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
		
		// If there are no messages, redirect to manage.php
		if (empty($messages)) {
			header('Location: manage.php');
			exit();
		}
	}
	
	function addPromotionToStation(&$station, $promotion_id, $end_time, $description) {
		$already_exists = false;
		foreach ($station['promotions'] as $promo) {
			if ($promo['promotion_id'] == $promotion_id) {
				$already_exists = true;
				break;
			}
		}
		if (!$already_exists) {
			$station['promotions'][] = [
				'promotion_id' => $promotion_id,
				'end_time' => $end_time,
				'description' => $description,
			];
		}
	}
	
	// Load promotions and markers data
	$promotions = json_decode(file_get_contents('./data/promotions.json'), true, 512, JSON_UNESCAPED_UNICODE);
	$markers = json_decode(file_get_contents('./data/markers.json'), true, 512, JSON_UNESCAPED_UNICODE);
	
	// Check if JSON files loaded correctly
	if (!$promotions || !$markers) {
		die('Error: Unable to load JSON files.');
	}
	
	// Synchronize promotions with markers
	syncPromotionsWithMarkers($promotions, $markers);
	// Save the synchronized promotions data
	file_put_contents('./data/promotions.json', json_encode($promotions, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
	
	// Collect unique promotion IDs
	$unique_promotions = [];
	foreach ($promotions['PROMOTIONS'] as $promotion) {
		foreach ($promotion['promotions'] as $promo) {
			if (!in_array($promo['promotion_id'], $unique_promotions)) {
				$unique_promotions[] = $promo['promotion_id'];
			}
		}
	}
	
	// Combine promotions and markers data
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
	
	// Handle search and filter
	$search_query = isset($_GET['search']) ? $_GET['search'] : '';
	$selected_province = isset($_GET['province']) ? $_GET['province'] : '';
	
	$filtered_promotions = array_filter($combined_data, function ($promotion) use ($search_query, $selected_province, $markers) {
		$matches_search_query = empty($search_query) || stripos($promotion['title'], $search_query) !== false ||
			array_reduce($promotion['promotions'], function ($carry, $promo) use ($search_query) {
				return $carry || stripos($promo['promotion_id'], $search_query) !== false;
			}, false);
		
		if (!empty($selected_province)) {
			foreach ($markers['STATION'] as $marker) {
				if ($marker['id'] == $promotion['station_id'] && $marker['province'] == $selected_province) {
					return $matches_search_query;
				}
			}
			return false;
		}
		
		return $matches_search_query;
	});
	
	// Pagination setup
	$per_page = 10;
	$total_stations = count($filtered_promotions);
	$total_pages = ceil($total_stations / $per_page);
	
	$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
	$page = max(1, min($total_pages, $page));
	
	$offset = ($page - 1) * $per_page;
	
	$current_page_promotions = array_slice($filtered_promotions, $offset, $per_page);
	
	// Prepare data for charts
	$promotion_ids = json_decode(file_get_contents('./data/promotion_ids.json'), true);
	$station_titles = [];
	$promotion_counts = [];
	$monthly_promotions = [];
	$promotion_distribution = [];
	
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
		}
	}
	
	// Calculate active and expired promotions
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
	
	// Encode data for JavaScript
	$station_titles_json = json_encode($station_titles);
	$promotion_counts_json = json_encode($promotion_counts);
	$monthly_promotions_json = json_encode(array_values($monthly_promotions));
	$monthly_labels_json = json_encode(array_keys($monthly_promotions));
	$promotion_distribution_json = json_encode(array_values($promotion_distribution));
	$promotion_labels_json = json_encode(array_keys($promotion_distribution));
	$expiration_status_json = json_encode([$active_count, $expired_count]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Promotions Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            200: '#bae6fd',
                            300: '#7dd3fc',
                            400: '#38bdf8',
                            500: '#0ea5e9',
                            600: '#0284c7',
                            700: '#0369a1',
                            800: '#075985',
                            900: '#0c4a6e',
                        },
                        secondary: {
                            50: '#f8fafc',
                            100: '#f1f5f9',
                            200: '#e2e8f0',
                            300: '#cbd5e1',
                            400: '#94a3b8',
                            500: '#64748b',
                            600: '#475569',
                            700: '#334155',
                            800: '#1e293b',
                            900: '#0f172a',
                        },
                        accent: {
                            50: '#f0fdf4',
                            100: '#dcfce7',
                            200: '#bbf7d0',
                            300: '#86efac',
                            400: '#4ade80',
                            500: '#22c55e',
                            600: '#16a34a',
                            700: '#15803d',
                            800: '#166534',
                            900: '#14532d',
                        },
                        danger: {
                            50: '#fef2f2',
                            100: '#fee2e2',
                            200: '#fecaca',
                            300: '#fca5a5',
                            400: '#f87171',
                            500: '#ef4444',
                            600: '#dc2626',
                            700: '#b91c1c',
                            800: '#991b1b',
                            900: '#7f1d1d',
                        },
                        warning: {
                            50: '#fffbeb',
                            100: '#fef3c7',
                            200: '#fde68a',
                            300: '#fcd34d',
                            400: '#fbbf24',
                            500: '#f59e0b',
                            600: '#d97706',
                            700: '#b45309',
                            800: '#92400e',
                            900: '#78350f',
                        }
                    },
                    fontFamily: {
                        sans: ['Inter', 'ui-sans-serif', 'system-ui'],
                    },
                }
            }
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
        }

        .animated-heading {
            background: linear-gradient(90deg, #0ea5e9, #22c55e);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            display: inline-block;
            animation: gradient 3s ease infinite;
            background-size: 200% 200%;
        }

        @keyframes gradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .card-hover {
            transition: all 0.3s ease;
            border-left: 4px solid transparent;
        }

        .card-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            border-left-color: #0ea5e9;
        }

        .tag {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            line-height: 1.25rem;
            font-weight: 500;
            background-color: #e2e8f0;
            color: #334155;
            margin-right: 0.5rem;
            margin-bottom: 0.5rem;
        }

        .tag-remove {
            margin-left: 0.25rem;
            cursor: pointer;
            color: #64748b;
        }

        .tag-remove:hover {
            color: #475569;
        }

        .content-section {
            display: none;
            animation: fadeIn 0.3s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .active-section {
            display: block;
        }

        .nav-tab {
            position: relative;
            padding-bottom: 0.5rem;
        }

        .nav-tab::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background-color: #0ea5e9;
            transition: width 0.3s ease;
        }

        .nav-tab:hover::after {
            width: 100%;
        }

        .nav-tab.active::after {
            width: 100%;
        }
    </style>
</head>
<body class="bg-gray-50">
<div class="flex flex-col min-h-screen" x-data="{ mobileMenuOpen: false }">
    <!-- Header -->
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

    <!-- JavaScript for Mobile Menu Toggle -->
    <script>
        document.getElementById('mobile-menu-button').addEventListener('click', function () {
            const mobileMenu = document.getElementById('mobile-menu');
            mobileMenu.classList.toggle('hidden');
        });
    </script>
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
 

        <!-- Alert Messages -->
        <?php if (!empty($messages)) : ?>
            <div class="bg-warning-100 border-l-4 border-warning-500 text-warning-700 p-4 mb-6 rounded-lg shadow-sm" role="alert">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-3 text-warning-500"></i>
                    <div>
                        <?php echo implode('<br>', $messages); ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Navigation Tabs -->
        <div class="flex space-x-6 border-b border-gray-200 mb-8">
            <button class="nav-tab flex items-center space-x-2 text-gray-600 hover:text-primary-600 font-medium transition-colors duration-200 active"
                    onclick="showSection(3)">
                <i class="fas fa-plus-circle"></i>
                <span>Add</span>
            </button>
            <button class="nav-tab flex items-center space-x-2 text-gray-600 hover:text-primary-600 font-medium transition-colors duration-200"
                    onclick="showSection(4)">
                <i class="fas fa-trash-alt"></i>
                <span>Clear </span>
            </button>
            <button class="nav-tab flex items-center space-x-2 text-gray-600 hover:text-primary-600 font-medium transition-colors duration-200"
                    onclick="showSection(5)">
                <i class="fas fa-search"></i>
                <span>Search</span>
            </button>
        </div>

        <!-- Section 3: Add Promotion -->
        <div id="section3" class="content-section active-section">
            <div class="bg-white rounded-xl shadow-md overflow-hidden mb-8">
                <div class="p-6">
                    <h2 class="text-2xl font-semibold text-gray-800 mb-6 flex items-center">
                        <i class="fas fa-plus-circle text-primary-500 mr-3"></i>
                        Add New Promotion
                    </h2>

                   <form action="manage.php" method="post" enctype="multipart/form-data">
    <input type="hidden" name="action" value="add_to_all">

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div>
            <label for="promotion_id" class="block text-sm font-medium text-gray-700 mb-1">Promotion ID</label>
            <select class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-all duration-200"
                    name="promotion_id" required>
                <?php foreach ($promotion_ids as $promo) : ?>
                    <option value="<?php echo $promo['promotion_id']; ?>"><?php echo $promo['promotion_id']; ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div>
            <label for="end_time" class="block text-sm font-medium text-gray-700 mb-1">End Time</label>
            <input type="datetime-local"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-all duration-200"
                   name="end_time" required>
        </div>
    </div>

    <div class="mb-6">
        <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
        <textarea class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-all duration-200"
                  name="description" rows="3" required></textarea>
    </div>

    <div class="mb-6">
        <label for="promotion_image" class="block text-sm font-medium text-gray-700 mb-1">Promotion Image</label>
        <div class="mt-1 flex items-center">
            <label for="promotion_image" class="cursor-pointer">
                <div class="flex flex-col items-center justify-center px-6 py-8 border-2 border-gray-300 border-dashed rounded-lg bg-gray-50 hover:bg-gray-100 transition-colors duration-200">
                    <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                    <p class="text-sm text-gray-500">Click to upload image</p>
                    <p class="text-xs text-gray-400 mt-1">PNG, JPG, GIF up to 5MB</p>
                </div>
                <input id="promotion_image" name="promotion_image" type="file" class="hidden" accept="image/*">
            </label>
        </div>
        <!-- Image Preview Section -->
        <div id="image-preview" class="mt-4 hidden">
            <p class="text-sm font-medium text-gray-700 mb-2">Selected Image:</p>
            <div class="flex items-center space-x-4">
                <img id="preview-img" src="#" alt="Image Preview" class="h-20 w-20 object-cover rounded-lg border border-gray-300">
                <div>
                    <p id="image-name" class="text-sm text-gray-600"></p>
                    <button type="button" id="remove-image" class="mt-2 text-sm text-red-600 hover:text-red-800">
                        <i class="fas fa-trash mr-1"></i> Remove
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
        <div>
            <label for="province-select" class="block text-sm font-medium text-gray-700 mb-1">Provinces</label>
            <select id="province-select"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-all duration-200">
                <option value="">Select a province</option>
                <?php
                $provinces = array_unique(array_column($markers['STATION'], 'province'));
                foreach ($provinces as $province) {
                    echo "<option value=\"$province\">$province</option>";
                }
                ?>
            </select>
        </div>

        <div>
            <label for="station-select" class="block text-sm font-medium text-gray-700 mb-1">Stations</label>
            <select id="station-select"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-all duration-200">
                <option value="">Select a station</option>
                <?php foreach ($markers['STATION'] as $station) : ?>
                    <option value="<?php echo $station['id']; ?>">
                        <?php echo $station['id'] . ' - ' . $station['title']; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700 mb-1">Selected Provinces</label>
        <div id="selected-provinces-container" class="p-3 border border-gray-300 rounded-lg bg-gray-50 min-h-12">
            <!-- Selected provinces will appear here -->
        </div>
    </div>

    <div class="mb-6">
        <label class="block text-sm font-medium text-gray-700 mb-1">Selected Stations</label>
        <div id="selected-stations-container" class="p-3 border border-gray-300 rounded-lg bg-gray-50 min-h-12">
            <!-- Selected stations will appear here -->
        </div>
    </div>

    <div class="mb-8">
        <label for="station-ids-text" class="block text-sm font-medium text-gray-700 mb-1">Paste Station IDs (comma separated)</label>
        <textarea id="station-ids-text"
                  class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-all duration-200"
                  rows="3"></textarea>
        <button type="button" id="selectStationsFromText"
                class="mt-2 inline-flex items-center px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors duration-200">
            <i class="fas fa-check mr-2"></i>
            Select Stations from Text
        </button>
    </div>

    <input type="hidden" name="provinces" id="selected-provinces" value="">
    <input type="hidden" name="stations" id="selected-stations" value="">

    <div class="flex justify-end space-x-4">
        <button type="reset"
                class="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-100 transition-colors duration-200">
            <i class="fas fa-undo mr-2"></i>
            Reset Form
        </button>
        <button type="submit"
                class="px-6 py-2.5 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors duration-200 shadow-md">
            <i class="fas fa-plus mr-2"></i>
            Add
        </button>
    </div>
</form>

<!-- JavaScript for Image Preview -->
<script>
    const imageInput = document.getElementById('promotion_image');
    const imagePreview = document.getElementById('image-preview');
    const previewImg = document.getElementById('preview-img');
    const imageName = document.getElementById('image-name');
    const removeButton = document.getElementById('remove-image');

    imageInput.addEventListener('change', function () {
        const file = this.files[0];
        if (file) {
            // Show the preview section
            imagePreview.classList.remove('hidden');
            
            // Display the image name
            imageName.textContent = file.name;
            
            // Create a URL for the image preview
            const reader = new FileReader();
            reader.onload = function (e) {
                previewImg.src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    });

    removeButton.addEventListener('click', function () {
        // Reset the file input
        imageInput.value = '';
        // Hide the preview section
        imagePreview.classList.add('hidden');
        // Clear the image name and preview
        imageName.textContent = '';
        previewImg.src = '#';
    });
</script>
                </div>
            </div>
        </div>

        <!-- Section 4: Clear Promotions -->
        <div id="section4" class="content-section">
            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <div class="p-6">
                    <h2 class="text-2xl font-semibold text-gray-800 mb-6 flex items-center">
                        <i class="fas fa-trash-alt text-danger-500 mr-3"></i>
                        Clear Promotions
                    </h2>

                    <form id="clearAllPromotionsForm" action="manage.php" method="post">
                        <input type="hidden" name="delete_all_promotions" value="1">

                        <div class="mb-6">
                            <label class="block text-sm font-medium text-gray-700 mb-3">Select Promotions to Clear</label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                                <?php foreach ($unique_promotions as $promotion_id) : ?>
                                    <div class="flex items-center">
                                        <input id="promo_<?php echo $promotion_id; ?>"
                                               name="selected_promotions[]"
                                               type="checkbox"
                                               value="<?php echo $promotion_id; ?>"
                                               class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded">
                                        <label for="promo_<?php echo $promotion_id; ?>"
                                               class="ml-2 text-sm text-gray-700">
                                            <?php echo $promotion_id; ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button type="button"
                                    onclick="confirmAction('Are you sure you want to clear the selected promotions?', 'clearAllPromotionsForm')"
                                    class="px-6 py-2.5 bg-danger-600 text-white rounded-lg hover:bg-danger-700 transition-colors duration-200 shadow-md">
                                <i class="fas fa-trash mr-2"></i>
                                Clear Selected Promotions
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Section 5: Search Promotions -->
<div id="section5" class="content-section">
    <div class="bg-white rounded-xl shadow-md overflow-hidden mb-6">
        <div class="p-6">
            <h2 class="text-2xl font-semibold text-gray-800 mb-6 flex items-center">
                <i class="fas fa-search text-primary-500 mr-3"></i>
                Search Promotions
            </h2>

            <div class="mb-6">
                <button id="checkExpiredPromotionsBtn"
                        class="px-6 py-2.5 bg-warning-600 text-white rounded-lg hover:bg-warning-700 transition-colors duration-200 shadow-md">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    Check Expired Promotions
                </button>
            </div>

            <form id="searchForm" method="get" action="manage.php" class="mb-6">
                <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                    <div class="md:col-span-5">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                            <input id="search" name="search"
                                   value="<?php echo htmlspecialchars($search_query); ?>"
                                   class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-all duration-200"
                                   placeholder="Search by Station Title or Promotion ID">
                        </div>
                    </div>

                    <div class="md:col-span-4">
                        <select id="province-filter" name="province"
                                class="block w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary-500 focus:border-primary-500 transition-all duration-200">
                            <option value="">All Provinces</option>
                            <?php
                            $provinces = array_unique(array_column($markers['STATION'], 'province'));
                            foreach ($provinces as $province) {
                                echo "<option value=\"$province\"" . ($province === $selected_province ? " selected" : "") . ">$province</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="md:col-span-3 flex space-x-2">
                        <button type="submit"
                                class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition-colors duration-200 shadow-md flex-1">
                            <i class="fas fa-filter mr-2"></i>
                            Filter
                        </button>
                        <button type="button" id="clearFilter"
                                class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors duration-200 flex-1">
                            <i class="fas fa-times mr-2"></i>
                            Clear
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div id="results" class="<?php echo (!empty($selected_province) || !empty($search_query)) ? 'block' : 'hidden'; ?>">
        <?php if (!empty($filtered_promotions)) : ?>
            <?php foreach ($current_page_promotions as $promotion) : ?>
                <div class="bg-white rounded-xl shadow-md overflow-hidden mb-6 card-hover">
                    <div class="p-6 border-b border-gray-200">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="text-lg font-semibold text-gray-800">
                                    <?php echo $promotion['title']; ?>
                                </h3>
                                <p class="text-sm text-gray-600 mt-1">
                                    <span class="font-medium">Station ID:</span> <?php echo $promotion['station_id']; ?>
                                </p>
                                <p class="text-sm text-gray-500 mt-2">
                                    <i class="fas fa-map-marker-alt mr-1"></i> <?php echo $promotion['address']; ?>
                                </p>
                            </div>
                            <div class="flex space-x-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-primary-100 text-primary-800">
                                    <?php echo count($promotion['promotions']); ?> Promotions
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="p-6">
                        <?php if (!empty($promotion['promotions'])) : ?>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Promotion ID</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">End Time</th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        <?php foreach ($promotion['promotions'] as $promo) : ?>
                                            <tr data-promo-id="<?php echo htmlspecialchars($promo['promotion_id']); ?>"
                                                data-end-time="<?php echo htmlspecialchars($promo['end_time']); ?>">
                                                <form action="manage.php" method="post">
                                                    <input type="hidden" name="station_id" value="<?php echo htmlspecialchars($promotion['station_id']); ?>">
                                                    <input type="hidden" name="promotion_id" value="<?php echo htmlspecialchars($promo['promotion_id']); ?>">
                                                    <input type="hidden" name="action" value="edit">

                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <select name="new_promotion_id" required
                                                                class="block w-full px-3 py-1 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                                                            <?php foreach ($promotion_ids as $promo_option) : ?>
                                                                <option value="<?php echo htmlspecialchars($promo_option['promotion_id']); ?>" <?php echo ($promo_option['promotion_id'] == $promo['promotion_id']) ? 'selected' : ''; ?>>
                                                                    <?php echo htmlspecialchars($promo_option['promotion_id']); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </td>

                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <input type="datetime-local" name="end_time"
                                                               value="<?php echo date('Y-m-d\TH:i', strtotime($promo['end_time'])); ?>"
                                                               required
                                                               class="block w-full px-3 py-1 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                                                    </td>

                                                    <td class="px-6 py-4 whitespace-nowrap space-x-2">
                                                        <button type="submit"
                                                                class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded shadow-sm text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500">
                                                            <i class="fas fa-save mr-1"></i> Update
                                                        </button>
                                                        <button type="button"
                                                                onclick="deletePromotion('<?php echo htmlspecialchars($promotion['station_id']); ?>', '<?php echo htmlspecialchars($promo['promotion_id']); ?>')"
                                                                class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded shadow-sm text-white bg-danger-600 hover:bg-danger-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-danger-500">
                                                            <i class="fas fa-trash mr-1"></i> Delete
                                                        </button>
                                                    </td>
                                                </form>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else : ?>
                            <div class="text-center py-8">
                                <i class="fas fa-tag text-gray-300 text-4xl mb-3"></i>
                                <p class="text-gray-500">No promotions available for this station</p>
                            </div>
                        <?php endif; ?>

                        <div class="mt-8 pt-6 border-t border-gray-200">
                            <h4 class="text-lg font-medium text-gray-800 mb-4">Add New Promotion</h4>
                            <form action="manage.php" method="post">
                                <input type="hidden" name="station_id" value="<?php echo htmlspecialchars($promotion['station_id']); ?>">
                                <input type="hidden" name="action" value="add">

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="promotion_id" class="block text-sm font-medium text-gray-700 mb-1">Promotion ID</label>
                                        <select name="promotion_id" required
                                                class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                                            <?php foreach ($promotion_ids as $promo) : ?>
                                                <option value="<?php echo htmlspecialchars($promo['promotion_id']); ?>">
                                                    <?php echo htmlspecialchars($promo['promotion_id']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div>
                                        <label for="end_time" class="block text-sm font-medium text-gray-700 mb-1">End Time</label>
                                        <input type="datetime-local" name="end_time" required
                                               class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                                    </div>
                                </div>

                                <div class="mt-6">
                                    <button type="submit"
                                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-accent-600 hover:bg-accent-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-accent-500">
                                        <i class="fas fa-plus mr-2"></i> Add Promotion
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>

            <!-- Pagination -->
            <nav class="flex items-center justify-between border-t border-gray-200 px-4 py-6">
                <div class="flex-1 flex justify-between sm:hidden">
                    <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search_query); ?>&province=<?php echo urlencode($selected_province); ?>"
                       class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50<?php if ($page <= 1) echo ' opacity-50 cursor-not-allowed'; ?>">
                        Previous
                    </a>
                    <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search_query); ?>&province=<?php echo urlencode($selected_province); ?>"
                       class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50<?php if ($page >= $total_pages) echo ' opacity-50 cursor-not-allowed'; ?>">
                        Next
                    </a>
                </div>
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700">
                            Showing page <span class="font-medium"><?php echo $page; ?></span> of <span class="font-medium"><?php echo $total_pages; ?></span>
                        </p>
                    </div>
                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                            <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search_query); ?>&province=<?php echo urlencode($selected_province); ?>"
                               class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50<?php if ($page <= 1) echo ' opacity-50 cursor-not-allowed'; ?>">
                                <span class="sr-only">Previous</span>
                                <i class="fas fa-chevron-left"></i>
                            </a>

                            <?php for ($i = 1; $i <= $total_pages; $i++) : ?>
                                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search_query); ?>&province=<?php echo urlencode($selected_province); ?>"
                                   class="<?php if ($page == $i) echo 'z-10 bg-primary-50 border-primary-500 text-primary-600'; else echo 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'; ?> relative inline-flex items-center px-4 py-2 border text-sm font-medium">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>

                            <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search_query); ?>&province=<?php echo urlencode($selected_province); ?>"
                               class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50<?php if ($page >= $total_pages) echo ' opacity-50 cursor-not-allowed'; ?>">
                                <span class="sr-only">Next</span>
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </nav>
                    </div>
                </div>
            </nav>
        <?php else : ?>
            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <div class="p-12 text-center">
                    <i class="fas fa-search text-gray-300 text-5xl mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No promotions found</h3>
                    <p class="text-gray-500">Try adjusting your search or filter to find what you're looking for.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Modal for Expired Promotions (Tailwind Styled) -->
    <div id="expiredPromotionsModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        <!-- Overlay -->
        <div class="absolute inset-0 bg-gray-900 bg-opacity-50 backdrop-blur-sm" id="modalOverlay"></div>

        <!-- Modal Content -->
        <div class="relative bg-white rounded-xl shadow-2xl max-w-4xl w-full mx-4 transform transition-all duration-300 scale-95">
            <div class="p-6">
                <div class="flex items-start">
                    <div class="flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-warning-100 mr-4">
                        <i class="fas fa-exclamation-triangle text-warning-600 text-xl"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4" id="expiredPromotionsModalLabel">
                            Expired Promotions
                        </h3>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Station ID</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Promotion ID</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">End Time</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200" id="expiredPromotionsTable">
                                    <!-- Rows will be populated by JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-6 py-4 flex justify-end space-x-3">
                <form action="manage.php" method="post">
                    <input type="hidden" name="clear_all_expired" value="1">
                    <button type="submit" id="clearExpiredPromotionsBtn"
                            class="px-4 py-2 bg-danger-600 text-white rounded-lg hover:bg-danger-700 transition-colors duration-200 shadow-md">
                        <i class="fas fa-trash mr-2"></i> Clear All Expired Promotions
                    </button>
                </form>
                <button type="button" onclick="closeModal()"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors duration-200">
                    Close
                </button>
            </div>
        </div>
    </div>
</div>

    </div>

    <!-- Modal for Expired Promotions -->
    <div class="modal fade fixed inset-0 overflow-y-auto" id="expiredPromotionsModal" tabindex="-1" aria-labelledby="expiredPromotionsModalLabel" aria-hidden="true" style="display: none;">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
            </div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-warning-100 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-exclamation-triangle text-warning-600"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900" id="expiredPromotionsModalLabel">
                                Expired Promotions
                            </h3>
                            <div class="mt-4">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Station ID</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Promotion ID</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">End Time</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200" id="expiredPromotionsTable">
                                            <!-- Rows will be populated by JavaScript -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" id="clearExpiredPromotionsBtn"
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-danger-600 text-base font-medium text-white hover:bg-danger-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-danger-500 sm:ml-3 sm:w-auto sm:text-sm">
                        <i class="fas fa-trash mr-2"></i> Clear All Expired Promotions
                    </button>
                    <button type="button" onclick="closeModal()"
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Close
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    // Show/hide sections
    function showSection(sectionNumber) {
        document.querySelectorAll('.content-section').forEach(section => {
            section.classList.remove('active-section');
        });
        document.getElementById('section' + sectionNumber).classList.add('active-section');
        const newUrl = new URL(window.location.href);
        newUrl.searchParams.set('active_section', sectionNumber);
        window.history.pushState({}, '', newUrl);

        document.querySelectorAll('.nav-tab').forEach(tab => {
            tab.classList.remove('active');
        });
        const buttons = document.querySelectorAll('button[onclick^="showSection"]');
        buttons.forEach(button => {
            if (button.getAttribute('onclick').includes(sectionNumber)) {
                button.classList.add('active');
            }
        });
    }

    // Confirm action dialog
    function confirmAction(message, formId) {
        if (confirm(message)) {
            document.getElementById(formId).submit();
        }
    }

    // Delete promotion
    function deletePromotion(stationId, promotionId) {
        if (confirm('Are you sure you want to delete this promotion?')) {
            const form = document.createElement('form');
            form.method = 'post';
            form.action = 'manage.php';

            const stationInput = document.createElement('input');
            stationInput.type = 'hidden';
            stationInput.name = 'station_id';
            stationInput.value = stationId;
            form.appendChild(stationInput);

            const promoInput = document.createElement('input');
            promoInput.type = 'hidden';
            promoInput.name = 'promotion_id';
            promoInput.value = promotionId;
            form.appendChild(promoInput);

            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'delete';
            form.appendChild(actionInput);

            document.body.appendChild(form);
            form.submit();
        }
    }

    // Close modal
    window.closeModal = function() {
        const modal = document.getElementById('expiredPromotionsModal');
        if (modal) {
            modal.classList.add('hidden');
            modal.classList.remove('scale-95');
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);
        const activeSection = urlParams.get('active_section') || '3';
        showSection(parseInt(activeSection));

        const searchForm = document.getElementById('searchForm');
        if (searchForm) {
            if (!searchForm.querySelector('input[name="active_section"]')) {
                const activeSectionInput = document.createElement('input');
                activeSectionInput.type = 'hidden';
                activeSectionInput.name = 'active_section';
                activeSectionInput.value = '5';
                searchForm.appendChild(activeSectionInput);
            }
        }

        const provinceSelect = document.getElementById('province-select');
        const stationSelect = document.getElementById('station-select');
        const selectedProvincesContainer = document.getElementById('selected-provinces-container');
        const selectedStationsContainer = document.getElementById('selected-stations-container');
        const selectedProvincesInput = document.getElementById('selected-provinces');
        const selectedStationsInput = document.getElementById('selected-stations');
        const stationIdsText = document.getElementById('station-ids-text');
        const selectStationsFromText = document.getElementById('selectStationsFromText');

        let selectedProvinces = [];
        let selectedStations = [];

        function updateHiddenInputs() {
            selectedProvincesInput.value = selectedProvinces.join(',');
            selectedStationsInput.value = selectedStations.join(',');
        }

        if (provinceSelect) {
            provinceSelect.addEventListener('change', function() {
                const province = this.value;
                if (province && !selectedProvinces.includes(province)) {
                    selectedProvinces.push(province);
                    renderSelectedProvinces();
                    updateHiddenInputs();
                    this.value = '';
                }
            });
        }

        if (stationSelect) {
            stationSelect.addEventListener('change', function() {
                const stationId = this.value;
                if (stationId && !selectedStations.includes(stationId)) {
                    selectedStations.push(stationId);
                    renderSelectedStations();
                    updateHiddenInputs();
                    this.value = '';
                }
            });
        }

        function renderSelectedProvinces() {
            if (!selectedProvincesContainer) return;
            selectedProvincesContainer.innerHTML = '';
            selectedProvinces.forEach(province => {
                const tag = document.createElement('span');
                tag.className = 'tag';
                tag.innerHTML = `
                ${province}
                <span class="tag-remove" data-province="${province}">
                    <i class="fas fa-times"></i>
                </span>
            `;
                selectedProvincesContainer.appendChild(tag);
            });

            document.querySelectorAll('.tag-remove[data-province]').forEach(button => {
                button.addEventListener('click', function() {
                    const province = this.getAttribute('data-province');
                    selectedProvinces = selectedProvinces.filter(p => p !== province);
                    renderSelectedProvinces();
                    updateHiddenInputs();
                });
            });
        }

        function renderSelectedStations() {
            if (!selectedStationsContainer) return;
            selectedStationsContainer.innerHTML = '';
            selectedStations.forEach(stationId => {
                const station = document.querySelector(`#station-select option[value="${stationId}"]`);
                const stationText = station ? station.textContent : stationId;

                const tag = document.createElement('span');
                tag.className = 'tag';
                tag.innerHTML = `
                ${stationText}
                <span class="tag-remove" data-station="${stationId}">
                    <i class="fas fa-times"></i>
                </span>
            `;
                selectedStationsContainer.appendChild(tag);
            });

            document.querySelectorAll('.tag-remove[data-station]').forEach(button => {
                button.addEventListener('click', function() {
                    const stationId = this.getAttribute('data-station');
                    selectedStations = selectedStations.filter(id => id !== stationId);
                    renderSelectedStations();
                    updateHiddenInputs();
                });
            });
        }

        if (selectStationsFromText) {
            selectStationsFromText.addEventListener('click', function() {
                const text = stationIdsText.value.trim();
                if (!text) return;

                const ids = text.split(',').map(id => id.trim()).filter(id => id);
                ids.forEach(id => {
                    if (!selectedStations.includes(id)) {
                        selectedStations.push(id);
                    }
                });

                renderSelectedStations();
                updateHiddenInputs();
                stationIdsText.value = '';
            });
        }

        const clearFilterBtn = document.getElementById('clearFilter');
        if (clearFilterBtn) {
            clearFilterBtn.addEventListener('click', function() {
                const searchForm = document.getElementById('searchForm');
                if (searchForm) {
                    searchForm.querySelector('input[name="search"]').value = '';
                    searchForm.querySelector('select[name="province"]').value = '';
                    searchForm.submit();
                }
            });
        }

        // Check expired promotions
        const checkExpiredBtn = document.getElementById('checkExpiredPromotionsBtn');
        if (checkExpiredBtn) {
            checkExpiredBtn.addEventListener('click', function() {
                // Get current time in UTC
                const now = new Date();
                const nowUTC = new Date(now.toISOString());
                const expiredRows = [];

                // Debug: Log current time and total rows checked
                console.log('Current time (UTC):', nowUTC.toISOString());
                const rows = document.querySelectorAll('tbody tr[data-end-time]');
                console.log(`Total rows with data-end-time: ${rows.length}`);

                rows.forEach((row, index) => {
                    const endTimeStr = row.getAttribute('data-end-time');
                    const endTime = new Date(endTimeStr);

                    // Debug: Log each row's data
                    console.log(`Row ${index + 1}:`);
                    console.log(`  data-end-time: ${endTimeStr}`);
                    console.log(`  Parsed end time: ${endTime.toISOString()}`);
                    console.log(`  Is valid date: ${!isNaN(endTime.getTime())}`);

                    if (isNaN(endTime.getTime())) {
                        console.error(`Invalid date format for end_time in row ${index + 1}: ${endTimeStr}`);
                        return;
                    }

                    if (endTime < nowUTC) {
                        const promoId = row.getAttribute('data-promo-id');
                        const stationId = row.querySelector('input[name="station_id"]').value;
                        expiredRows.push({
                            stationId: stationId,
                            promoId: promoId,
                            endTime: endTimeStr
                        });
                        console.log(`  Expired: Yes`);
                    } else {
                        console.log(`  Expired: No`);
                    }
                });

                const modal = document.getElementById('expiredPromotionsModal');
                const tableBody = document.getElementById('expiredPromotionsTable');

                console.log(`Found ${expiredRows.length} expired promotions`);

                if (expiredRows.length > 0) {
                    if (tableBody) {
                        tableBody.innerHTML = '';
                        expiredRows.forEach(row => {
                            const tr = document.createElement('tr');
                            tr.innerHTML = `
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${row.stationId}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${row.promoId}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${new Date(row.endTime).toLocaleString()}</td>
                        `;
                            tableBody.appendChild(tr);
                        });

                        // Show modal
                        modal.classList.remove('hidden');
                        setTimeout(() => {
                            modal.querySelector('.transform').classList.remove('scale-95');
                        }, 10);
                    }
                } else {
                    alert('No expired promotions found! Check the console for more details.');
                }
            });
        }

        // Close modal when clicking outside
        const modalOverlay = document.getElementById('modalOverlay');
        if (modalOverlay) {
            modalOverlay.addEventListener('click', function() {
                closeModal();
            });
        }
    });
</script>
</body>
</html>