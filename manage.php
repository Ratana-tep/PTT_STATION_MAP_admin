<?php
session_start();
date_default_timezone_set('Asia/Phnom_Penh');

// Ensure the correct content type for handling Khmer characters
header('Content-Type: text/html; charset=utf-8');

// Check if the user is logged in
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
    // Load promotions and markers data
    $promotions = json_decode(file_get_contents('./data/promotions.json'), true, 512, JSON_UNESCAPED_UNICODE);
    $markers = json_decode(file_get_contents('./data/markers.json'), true, 512, JSON_UNESCAPED_UNICODE);
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
        $selected_stations = !empty($_POST['stations']) ? explode(',', $_POST['stations']) : [];

        foreach ($promotions['PROMOTIONS'] as &$station) {
            $station_id = $station['station_id'];
            foreach ($markers['STATION'] as $marker) {
                if ($marker['id'] == $station_id) {
                    // If specific stations are selected, add promotion only to those stations
                    if (!empty($selected_stations) && in_array($station_id, $selected_stations)) {
                        addPromotionToStation($station, $promotion_id, $end_time, $description);
                    }
                    // If no specific stations are selected, add promotion to all stations in selected provinces
                    elseif (empty($selected_stations) && !empty($selected_provinces) && in_array($marker['province'], $selected_provinces)) {
                        addPromotionToStation($station, $promotion_id, $end_time, $description);
                    }
                }
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
        $uploaded_file_type = mime_content_type($uploaded_file);
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

function addPromotionToStation(&$station, $promotion_id, $end_time, $description)
{
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

// Collect unique promotion IDs
$unique_promotions = [];
foreach ($promotions['PROMOTIONS'] as $promotion) {
    foreach ($promotion['promotions'] as $promo) {
        if (!in_array($promo['promotion_id'], $unique_promotions)) {
            $unique_promotions[] = $promo['promotion_id'];
        }
    }
}

// Load markers data
$markers = json_decode(file_get_contents('./data/markers.json'), true, 512, JSON_UNESCAPED_UNICODE);

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
    <title>Manage Promotions</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #000308;
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

        .navbar {
            padding: 10px 15px;
            background-color: #ffffff;
            color: black;
            box-shadow: 0 10px 16px -4px rgba(0, 0, 0, 0.6);
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            color: black;
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
        }

        .card-header {
            background-color: #007bff;
            color: white;
            border-bottom: none;
            border-radius: 10px 10px 0 0;
        }

        .card-body {
            padding: 20px;
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

        .badge {
            display: inline-block;
            padding: 0.5em 0.75em;
            font-size: 0.75em;
            font-weight: 700;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
            border-radius: 0.375rem;
            transition: background-color 0.15s ease-in-out;
        }

        .btn-close {
            padding: 0.25em;
            background: none;
            border: none;
            font-size: 1em;
        }

        .btn-custom {
            background: linear-gradient(45deg, #007bff, #00d4ff);
            border: none;
            color: white;
            transition: background 0.3s ease;
        }

        .btn-custom:hover {
            background: linear-gradient(45deg, #00d4ff, #007bff);
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
            <nav class="navbar navbar-expand-lg navbar-light bg-light">
                <a class="navbar-brand ms-3" href="#">
                    <img src="./pictures/logo_Station.png" width="200" height="auto" alt="Logo">
                </a>
                <div class="ms-auto">
                    <a href="logout.php" class="btn btn-danger"><i class="fas fa-sign-out-alt"></i> Logout</a>
                </div>
            </nav>
            <div class="container-fluid mt-5">
                <style>
                    @keyframes colorChange {

                        0%,
                        100% {
                            color: red;
                            text-shadow: 2px 2px 4px #000000;
                        }

                        20% {
                            color: orange;
                            text-shadow: 2px 2px 4px #000000;
                        }

                        40% {
                            color: yellow;
                            text-shadow: 2px 2px 4px #000000;
                        }

                        60% {
                            color: green;
                            text-shadow: 2px 2px 4px #000000;
                        }

                        80% {
                            color: blue;
                            text-shadow: 2px 2px 4px #000000;
                        }
                    }

                    .animated-heading {
                        display: inline-block;
                        animation: colorChange 5s infinite ease-in-out;
                        font-size: 3em;
                        /* Adjust size as needed */
                        font-weight: bold;
                    }
                </style>
                <h1 class="animated-heading"><i class="fas fa-bullhorn"></i> Promotions Dashboard</h1>


                <?php if (!empty($messages)) : ?>
                    <div class="alert alert-warning" role="alert">
                        <?php echo implode('<br>', $messages); ?>
                    </div>
                <?php endif; ?>

                <!-- Buttons to toggle sections -->
                <div class="mb-4">
                    <button class="btn btn-custom me-2" onclick="showSection(3)"><i class="fas fa-plus"></i> Show Add Promotion</button>
                    <button class="btn btn-custom me-2" onclick="showSection(4)"><i class="fas fa-trash"></i> Show Clear Promotions</button>
                    <button class="btn btn-custom" onclick="showSection(5)"><i class="fas fa-search"></i> Show Search Promotions</button>
                </div>

                <!-- Section 3 -->
                <div id="section3" class="content-section">
                    <form action="manage.php" method="post" enctype="multipart/form-data" class="mb-4 p-3 border rounded shadow-sm bg-light">
                        <input type="hidden" name="action" value="add_to_all">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="promotion_id" class="form-label">Promotion ID:</label>
                                <select class="form-select" name="promotion_id" required>
                                    <?php foreach ($promotion_ids as $promo) : ?>
                                        <option value="<?php echo $promo['promotion_id']; ?>"><?php echo $promo['promotion_id']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="end_time" class="form-label">End Time:</label>
                                <input type="datetime-local" class="form-control" name="end_time" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description:</label>
                            <textarea class="form-control" name="description" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="promotion_image" class="form-label">Promotion Image:</label>
                            <input type="file" class="form-control" name="promotion_image" id="promotion_image" accept="image/*">
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="province-select" class="form-label">Provinces:</label>
                                <select id="province-select" class="form-select">
                                    <option value="">Select a province</option>
                                    <?php
                                    $provinces = array_unique(array_column($markers['STATION'], 'province'));
                                    foreach ($provinces as $province) {
                                        echo "<option value=\"$province\">$province</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="station-select" class="form-label">Stations:</label>
                                <select id="station-select" class="form-select">
                                    <option value="">Select a station</option>
                                    <?php foreach ($markers['STATION'] as $station) : ?>
                                        <option value="<?php echo $station['id']; ?>">
                                            <?php echo $station['id'] . ' - ' . $station['title']; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label>Selected Provinces:</label>
                            <div id="selected-provinces-container" class="border p-2 rounded bg-white">
                                <!-- Selected provinces will be displayed here as tags -->
                            </div>
                        </div>
                        <div class="mb-3">
                            <label>Selected Stations:</label>
                            <div id="selected-stations-container" class="border p-2 rounded bg-white">
                                <!-- Selected stations will be displayed here as tags -->
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="station-ids-text" class="form-label">Paste Station IDs (comma separated):</label>
                            <textarea id="station-ids-text" class="form-control" rows="3"></textarea>
                            <button type="button" class="btn btn-info mt-2" id="selectStationsFromText"><i class="fas fa-check"></i> Select Stations from Text</button>
                        </div>
                        <input type="hidden" name="provinces" id="selected-provinces" value="">
                        <input type="hidden" name="stations" id="selected-stations" value="">
                        <button type="submit" class="btn btn-custom"><i class="fas fa-plus"></i> Add Promotion to Stations</button>
                    </form>
                    <div>
                        <form action="commit_git.php" method="post" class="mb-4 p-3 border rounded shadow-sm bg-light">
                            <input type="hidden" name="commit_changes" value="1">
                            <button type="submit" class="btn btn-success"><i class="fas fa-upload"></i> Add to Server</button>
                        </form>
                    </div>
                </div>

                <!-- Section 4 -->
                <div id="section4" class="content-section">
                    <form id="clearAllPromotionsForm" action="manage.php" method="post" class="mb-4 p-3 border rounded shadow-sm bg-light">
                        <input type="hidden" name="delete_all_promotions" value="1">
                        <div class="mb-3">
                            <label for="selected_promotions" class="form-label">Select Promotions to Clear:</label>
                            <?php foreach ($unique_promotions as $promotion_id) : ?>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="selected_promotions[]" value="<?php echo $promotion_id; ?>" id="promo_<?php echo $promotion_id; ?>">
                                    <label class="form-check-label" for="promo_<?php echo $promotion_id; ?>">
                                        <?php echo $promotion_id; ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="btn btn-danger" onclick="confirmAction('Are you sure you want to clear the selected promotions?', 'clearAllPromotionsForm')"><i class="fas fa-trash"></i> Clear Selected Promotions</button>
                    </form>
                </div>

                <script>
                    function confirmAction(message, formId) {
                        if (confirm(message)) {
                            document.getElementById(formId).submit();
                        }
                    }
                </script>

                <!-- Section 5 -->
                <div id="section5" class="content-section">
                    <div>
                        <button class="btn btn-warning mb-4" id="checkExpiredPromotionsBtn"><i class="fas fa-exclamation-circle"></i> Check Expired Promotions</button>
                    </div>
                    <form class="form-inline mb-4 p-3 border rounded shadow-sm bg-light" id="searchForm" method="get" action="manage.php">
                        <div class="row g-3">
                            <div class="col-md-5">
                                <input class="form-control" type="text" id="search" name="search" placeholder="Search by Station Title or Promotion ID" value="<?php echo htmlspecialchars($search_query); ?>">
                            </div>
                            <div class="col-md-4">
                                <select class="form-select" id="province-filter" name="province">
                                    <option value="">All Provinces</option>
                                    <?php
                                    $provinces = array_unique(array_column($markers['STATION'], 'province'));
                                    foreach ($provinces as $province) {
                                        echo "<option value=\"$province\"" . ($province === $selected_province ? " selected" : "") . ">$province</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-primary me-2"><i class="fas fa-filter"></i> Filter</button>
                                <button type="button" id="clearFilter" class="btn btn-secondary"><i class="fas fa-times"></i> Clear</button>
                            </div>
                        </div>
                    </form>
                </div>

                <div id="results" style="display: <?php echo (!empty($selected_province) || !empty($search_query)) ? 'block' : 'none'; ?>;">
                    <?php if (!empty($filtered_promotions)) : ?>
                        <?php foreach ($current_page_promotions as $promotion) : ?>
                            <div class="card mb-3">
                                <div class="card-header">
                                    <strong><?php echo $promotion['title']; ?> (Station ID:
                                        <?php echo $promotion['station_id']; ?>)</strong>
                                    <br>
                                    <small><?php echo $promotion['address']; ?></small>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($promotion['promotions'])) : ?>
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th>Promotion ID</th>
                                                    <th>End Time</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($promotion['promotions'] as $promo) : ?>
                                                    <tr data-promo-id="<?php echo $promo['promotion_id']; ?>" data-end-time="<?php echo $promo['end_time']; ?>">
                                                        <form action="manage.php" method="post" class="form-inline">
                                                            <input type="hidden" name="station_id" value="<?php echo $promotion['station_id']; ?>">
                                                            <input type="hidden" name="promotion_id" value="<?php echo $promo['promotion_id']; ?>">
                                                            <input type="hidden" name="action" value="edit">
                                                            <td>
                                                                <select class="form-select" name="new_promotion_id" required>
                                                                    <?php foreach ($promotion_ids as $promo_option) : ?>
                                                                        <option value="<?php echo $promo_option['promotion_id']; ?>" <?php echo ($promo_option['promotion_id'] == $promo['promotion_id']) ? 'selected' : ''; ?>>
                                                                            <?php echo $promo_option['promotion_id']; ?>
                                                                        </option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </td>
                                                            <td>
                                                                <input type="datetime-local" class="form-control" name="end_time" value="<?php echo date('Y-m-d\TH:i', strtotime($promo['end_time'])); ?>" required>
                                                            </td>
                                                            <td>
                                                                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update</button>
                                                                <button type="button" class="btn btn-danger ms-2" onclick="deletePromotion('<?php echo $promotion['station_id']; ?>', '<?php echo $promo['promotion_id']; ?>')"><i class="fas fa-trash"></i> Delete</button>
                                                            </td>
                                                        </form>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    <?php else : ?>
                                        <p>No promotions available.</p>
                                    <?php endif; ?>
                                    <form action="manage.php" method="post" class="mt-4">
                                        <input type="hidden" name="station_id" value="<?php echo $promotion['station_id']; ?>">
                                        <input type="hidden" name="action" value="add">
                                        <div class="form-group">
                                            <label for="promotion_id">Promotion ID:</label>
                                            <select class="form-select" name="promotion_id" required>
                                                <?php foreach ($promotion_ids as $promo) : ?>
                                                    <option value="<?php echo $promo['promotion_id']; ?>">
                                                        <?php echo $promo['promotion_id']; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label for="end_time">End Time:</label>
                                            <input type="datetime-local" class="form-control" name="end_time" required>
                                        </div>
                                        <button type="submit" class="btn btn-success"><i class="fas fa-plus"></i> Add Promotion</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <!-- Pagination Controls -->
                        <nav aria-label="Page navigation example">
                            <ul class="pagination">
                                <li class="page-item <?php if ($page <= 1) echo 'disabled'; ?>">
                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search_query); ?>&province=<?php echo urlencode($selected_province); ?>" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                        <span class="sr-only">Previous</span>
                                    </a>
                                </li>
                                <?php for ($i = 1; $i <= $total_pages; $i++) : ?>
                                    <li class="page-item <?php if ($page == $i) echo 'active'; ?>"><a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search_query); ?>&province=<?php echo urlencode($selected_province); ?>"><?php echo $i; ?></a></li>
                                <?php endfor; ?>
                                <li class="page-item <?php if ($page >= $total_pages) echo 'disabled'; ?>">
                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search_query); ?>&province=<?php echo urlencode($selected_province); ?>" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                        <span class="sr-only">Next</span>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    <?php else : ?>
                        <p>No promotions found for the selected criteria.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for Expired Promotions -->
    <div class="modal fade" id="expiredPromotionsModal" tabindex="-1" aria-labelledby="expiredPromotionsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="expiredPromotionsModalLabel">Expired Promotions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Station ID</th>
                                <th>Promotion ID</th>
                                <th>End Time</th>
                            </tr>
                        </thead>
                        <tbody id="expiredPromotionsTable">
                            <!-- Rows will be populated by JavaScript -->
                        </tbody>
                    </table>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" id="clearExpiredPromotionsBtn"><i class="fas fa-trash"></i> Clear All Expired Promotions</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

</body>
<script>
    $(document).ready(function() {
        // Hide all sections initially
        $('.content-section').hide();

        // Show specific section
        window.showSection = function(sectionNumber) {
            $('.content-section').hide();
            $('#section' + sectionNumber).show();
        };

        // Handle search form submission
        $('#searchForm').submit(function(event) {
            var searchQuery = $('#search').val();
            var selectedProvince = $('#province-filter').val();
            var url = 'manage.php?search=' + encodeURIComponent(searchQuery) + '&province=' + encodeURIComponent(selectedProvince);
            window.location.href = url;
            event.preventDefault();
        });

        // Show results based on filter criteria
        $('#province-filter, #search').on('change', function() {
            var selectedProvince = $('#province-filter').val();
            var searchQuery = $('#search').val();

            if (selectedProvince || searchQuery) {
                $('#results').show();
            } else {
                $('#results').hide();
            }
        });

        // Clear filter
        $('#clearFilter').click(function() {
            $('#search').val('');
            $('#province-filter').val('');
            $('#results').hide();
            window.location.href = 'manage.php';
        });

        // Handle province selection
        $('#province-select').on('change', function() {
            var selectedProvince = $(this).val();
            if (selectedProvince) {
                addProvinceTag(selectedProvince);
                $(this).val('');
            }
            updateStationsBasedOnProvinces();
        });

        function addProvinceTag(province) {
            var container = $('#selected-provinces-container');
            var existingProvinces = $('#selected-provinces').val().split(',').filter(Boolean);

            if (!existingProvinces.includes(province)) {
                existingProvinces.push(province);
                var tag = $('<span class="badge badge-light mr-2" style="color: black;">' + province + ' <span class="remove-tag" style="cursor:pointer;">&times;</span></span>');
                tag.find('.remove-tag').on('click', function() {
                    removeProvinceTag(province, tag);
                });
                container.append(tag);
                $('#selected-provinces').val(existingProvinces.join(','));
                updateStationsBasedOnProvinces();
            }
        }

        function removeProvinceTag(province, tag) {
            var existingProvinces = $('#selected-provinces').val().split(',').filter(Boolean);
            existingProvinces = existingProvinces.filter(function(item) {
                return item !== province;
            });
            $('#selected-provinces').val(existingProvinces.join(','));
            tag.remove();
            updateStationsBasedOnProvinces();
        }

        function updateStationsBasedOnProvinces() {
            var selectedProvinces = $('#selected-provinces').val().split(',').filter(Boolean);
            var stationSelect = $('#station-select');
            stationSelect.empty().append('<option value="">Select a station</option>');

            <?php foreach ($markers['STATION'] as $station) : ?>
                var stationProvince = "<?php echo $station['province']; ?>";
                var stationId = "<?php echo $station['id']; ?>";
                var stationTitle = "<?php echo $station['title']; ?>";

                if (selectedProvinces.length === 0 || selectedProvinces.includes(stationProvince)) {
                    stationSelect.append('<option value="' + stationId + '">' + stationId + ' - ' + stationTitle + '</option>');
                }
            <?php endforeach; ?>

            stationSelect.trigger('change');
        }

        // Initialize Select2 for station select with search by ID and name
        $('#station-select').select2({
            placeholder: "Select a station",
            allowClear: true,
            width: '100%',
            matcher: function(params, data) {
                if ($.trim(params.term) === '') {
                    return data;
                }

                if (data.text.toLowerCase().indexOf(params.term.toLowerCase()) > -1) {
                    return data;
                }

                if ($(data.element).attr('value').toLowerCase().indexOf(params.term.toLowerCase()) > -1) {
                    return data;
                }

                return null;
            }
        });

        // Handle adding selected stations
        $('#station-select').on('change', function() {
            var selectedStationId = $(this).val();
            var selectedStationText = $(this).find('option:selected').text();
            if (selectedStationId) {
                addStationTag(selectedStationId, selectedStationText);
                $(this).val('').trigger('change'); // Reset select2 selection
            }
        });

        function addStationTag(stationId, stationText) {
            var container = $('#selected-stations-container');
            var existingStations = $('#selected-stations').val().split(',').filter(Boolean);

            if (!existingStations.includes(stationId)) {
                existingStations.push(stationId);
                var tag = $('<span class="badge badge-light mr-2" style="color: black;">' + stationText + ' <span class="remove-tag" style="cursor:pointer;">&times;</span></span>');
                tag.find('.remove-tag').on('click', function() {
                    removeStationTag(stationId, tag);
                });
                container.append(tag);
                $('#selected-stations').val(existingStations.join(','));
            }
        }

        function removeStationTag(stationId, tag) {
            var existingStations = $('#selected-stations').val().split(',').filter(Boolean);
            existingStations = existingStations.filter(function(item) {
                return item !== stationId;
            });
            $('#selected-stations').val(existingStations.join(','));
            tag.remove();
        }

        $('#search').on('input', function() {
            var searchQuery = $(this).val();
            if (searchQuery.length > 0) {
                $.get('manage.php', {
                    search: searchQuery
                }, function(data) {
                    $('#results').html($(data).find('#results').html());
                    $('#results').show();
                });
            } else {
                $('#results').hide();
            }
        });

        // Function to check expired promotions and display them in the modal
        function checkExpiredPromotions() {
            var expiredPromotions = [];
            var currentTime = new Date().toISOString();

            <?php foreach ($combined_data as $promotion) : ?>
                <?php foreach ($promotion['promotions'] as $promo) : ?>
                    if (new Date('<?php echo $promo['end_time']; ?>').toISOString() < currentTime) {
                        expiredPromotions.push({
                            station_id: '<?php echo $promotion['station_id']; ?>',
                            promotion_id: '<?php echo $promo['promotion_id']; ?>',
                            end_time: '<?php echo $promo['end_time']; ?>'
                        });
                    }
                <?php endforeach; ?>
            <?php endforeach; ?>

            displayExpiredPromotions(expiredPromotions);
            $('#expiredPromotionsModal').modal('show');
        }

        // Function to display expired promotions in the modal
        function displayExpiredPromotions(expiredPromotions) {
            var tableBody = $('#expiredPromotionsTable');
            tableBody.empty();

            expiredPromotions.forEach(function(promo) {
                var row = '<tr>' +
                    '<td>' + promo.station_id + '</td>' +
                    '<td>' + promo.promotion_id + '</td>' +
                    '<td>' + promo.end_time + '</td>' +
                    '</tr>';
                tableBody.append(row);
            });
        }

        // Set up event listener for the "Check Expired Promotions" button
        $('#checkExpiredPromotionsBtn').click(function() {
            checkExpiredPromotions();
        });

        // Set up event listener for clearing expired promotions
        $('#clearExpiredPromotionsBtn').click(function() {
            $.post('manage.php', {
                clear_all_expired: 1
            }, function(response) {
                location.reload();
            });
        });

        // Handle Excel file upload
        $('#processExcel').click(function() {
            var fileInput = $('#uploadExcel')[0];
            if (!fileInput.files.length) {
                alert("Please upload an Excel file.");
                return;
            }

            var file = fileInput.files[0];
            var reader = new FileReader();

            reader.onload = function(event) {
                var data = new Uint8Array(event.target.result);
                var workbook = XLSX.read(data, {
                    type: 'array'
                });
                var firstSheetName = workbook.SheetNames[0];
                var worksheet = workbook.Sheets[firstSheetName];

                var jsonData = XLSX.utils.sheet_to_json(worksheet, {
                    header: 1
                });
                var stationIds = jsonData.map(function(row) {
                    return row[0]; // Assuming the first column contains the station IDs
                });

                selectStationsById(stationIds);
            };

            reader.readAsArrayBuffer(file);
        });

        // Handle pasting station IDs
        $('#selectStationsFromText').click(function() {
            var stationIdsText = $('#station-ids-text').val();
            var stationIds = stationIdsText.split(/[\s,]+/).filter(function(id) {
                return id.trim();
            });

            selectStationsById(stationIds);
        });

        function selectStationsById(stationIds) {
            var stationSelect = $('#station-select');
            var selectedStationIds = [];

            stationIds.forEach(function(stationId) {
                var option = stationSelect.find('option[value="' + stationId + '"]');
                if (option.length) {
                    selectedStationIds.push(stationId);
                    addStationTag(stationId, option.text());
                }
            });

            $('#selected-stations').val(selectedStationIds.join(','));
        }

        // Trigger the update stations function on page load to populate the stations
        updateStationsBasedOnProvinces();
    });

    function deletePromotion(stationId, promotionId) {
        if (confirm('Are you sure you want to delete this promotion?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'manage.php';

            const stationIdInput = document.createElement('input');
            stationIdInput.type = 'hidden';
            stationIdInput.name = 'station_id';
            stationIdInput.value = stationId;
            form.appendChild(stationIdInput);

            const promotionIdInput = document.createElement('input');
            promotionIdInput.type = 'hidden';
            promotionIdInput.name = 'promotion_id';
            promotionIdInput.value = promotionId;
            form.appendChild(promotionIdInput);

            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'delete';
            form.appendChild(actionInput);

            document.body.appendChild(form);
            form.submit();
        }
    }
</script>

</html>