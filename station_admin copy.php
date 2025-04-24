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

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Promotions</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
    <script src="https://code.jquery.com/jquery-3.2.1.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.11.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
    <script type="text/javascript" src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
    <!-- DataTables CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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

        .countdown-timer {
            font-size: 1.2em;
            font-weight: bold;
            color: #28a745;
        }

        .promotion-id {
            font-size: 1.2em;
            color: white;
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
            <div>
                <form action="commit_git.php" method="post" class="mb-6 mt-12 p-3 border rounded shadow-sm bg-light">
                    <input type="hidden" name="commit_changes" value="1">
                    <button type="submit" class="btn btn-success">Commit Changes to GitHub</button>
                </form>
            </div>
            <!-- Modal -->
            <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Add Marker</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="marker-form" method="POST" action="marker-interface.php" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label for="id" class="form-label">Station ID:</label>
                                    <input type="text" class="form-control" id="id" name="id" required>
                                </div>
                                <div class="mb-3">
                                    <label for="title" class="form-label">Station Name :</label>
                                    <input type="text" class="form-control" id="title" name="title" required>
                                </div>
                                <div class="mb-3">
                                    <label for="province" class="form-label">Province Name :</label>
                                    <select id="edit1-province" class="form-select" name="province" required>
                                        <option value="" selected disabled>Select Province</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="latitude" class="form-label">Latitude:</label>
                                    <input type="text" class="form-control" id="latitude" name="latitude" required>
                                </div>
                                <div class="mb-3">
                                    <label for="longitude" class="form-label">Longitude:</label>
                                    <input type="text" class="form-control" id="longitude" name="longitude" required>
                                </div>
                                <!-- product -->

                                <div class="mb-3">
                                    <label for="status" class="form-label">Status:</label>
                                    <select id="status" class="form-select" name="status" required>
                                        <option value="" selected disabled>Select Status</option>
                                        <option value="16h">⏰ 16 Hours</option>
                                        <option value="24h">⏰ 24 Hours</option>
                                        <option value="under construct">🚫 Under Construct</option>
                                    </select>

                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Product:</label><br>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="ulg95" name="product[]" value="ULG 95">
                                        <label class="form-check-label" for="ulg95">ULG 95</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="ulr91" name="product[]" value="ULR 91">
                                        <label class="form-check-label" for="7eleven">ULR 91</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="hsd" name="product[]" value="HSD">
                                        <label class="form-check-label" for="ev">HSD</label>
                                    </div>
                                    <!-- Add more checkboxes as needed -->
                                </div>
                                <!-- other_product -->
                                <div class="mb-3">
                                    <label class="form-label">Other Product:</label><br>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="ev" name="other_product[]" value="EV">
                                        <label class="form-check-label" for="ev">EV</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="onion" name="other_product[]" value="Onion">
                                        <label class="form-check-label" for="onion">Onion</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="otr" name="other_product[]" value="Otr">
                                        <label class="form-check-label" for="otr">Otteri</label>
                                    </div>
                                    <!-- Add more checkboxes as needed -->
                                </div>
                                <!-- <div class="mb-3">
                            <label class="form-label">Promotions:</label><br>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="promotion1" name="promotion[]"
                                    value="promotion1">
                                <label class="form-check-label" for="promotion1">Promotion 1</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="promotion2" name="promotion[]"
                                    value="promotion2">
                                <label class="form-check-label" for="promotion2">Promotion 2</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="promotion3" name="promotion[]"
                                    value="promotion3">
                                <label class="form-check-label" for="promotion3">Promotion 3</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="promotion-opening1"
                                    name="promotion[]" value="promotion-opening1">
                                <label class="form-check-label" for="promotion-opening1">Promotion Opening 1</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="promotion-opening2"
                                    name="promotion[]" value="promotion-opening2">
                                <label class="form-check-label" for="promotion-opening2">Promotion Opening 2</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="promotion-opening3"
                                    name="promotion[]" value="promotion-opening3">
                                <label class="form-check-label" for="promotion-opening3">Promotion Opening 3</label>
                            </div>
                        </div> -->

                                <div class="mb-3">
                                    <label class="form-label">Service:</label><br>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="amazon" name="description[]" value="Amazon">
                                        <label class="form-check-label" for="amazon">Amazon</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="7eleven" name="description[]" value="7-Eleven">
                                        <label class="form-check-label" for="7eleven">7-Eleven</label>
                                    </div>

                                    <!-- Add more checkboxes as needed -->
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Payment:</label><br>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="fleetcard" name="service[]" value="Fleet card">
                                        <label class="form-check-label" for="fleetcard">Fleet Card</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="aba" name="service[]" value="KHQR">
                                        <label class="form-check-label" for="aba">KHQR</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="test" name="service[]" value="Cash">
                                        <label class="form-check-label" for="test">Cash</label>
                                    </div>
                                    <!-- Add more checkboxes as needed -->
                                </div>
                                <div class="mb-3">
                                    <label for="address" class="form-label">
                                        Address:
                                    </label>
                                    <input type="text" class="form-control" id="address" name="address" required>
                                </div>
                                <div class="mb-3">
                                    <label for="picture" class="form-label">Picture:</label>
                                    <input type="file" class="form-control" id="picture" name="picture">
                                </div>
                                <button type="submit" class="btn btn-primary">Add Marker</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Add a new modal form for editing markers -->
            <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editModalLabel">Edit Marker</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="edit-marker-form" method="POST" action="marker-interface.php" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label for="edit-id" class="form-label">Station ID:</label>
                                    <input type="text" class="form-control" id="edit-id" name="id" readonly>
                                </div>
                                <div class="mb-3">
                                    <label for="edit-title" class="form-label">Station Name:</label>
                                    <input type="text" class="form-control" id="edit-title" name="title" required>
                                </div>
                                <div class="mb-3">
                                    <label for="edit-picture" class="form-label">Province:</label>
                                    <select id="edit-province" class="form-select" name="province" required>
                                        <option value="" selected disabled>Select Province</option>
                                        <option value="Banteay Meanchey">Banteay Meanchey</option>
                                        <option value="Battambang">Battambang</option>
                                        <option value="Kampong Cham">Kampong Cham</option>
                                        <option value="Kampong Chhnang">Kampong Chhnang</option>
                                        <option value="Kampong Speu">Kampong Speu</option>
                                        <option value="Kampong Thom">Kampong Thom</option>
                                        <option value="Kampot">Kampot</option>
                                        <option value="Kandal">Kandal</option>
                                        <option value="Koh Kong">Koh Kong</option>
                                        <option value="Kratié">Kratié</option>
                                        <option value="Mondulkiri">Mondulkiri</option>
                                        <option value="Oddar Meanchey">Oddar Meanchey</option>
                                        <option value="Phnom Penh">Phnom Penh</option>
                                        <option value="Pailin">Pailin</option>
                                        <option value="Preah Sihanouk">Preah Sihanouk</option>
                                        <option value="Preah Vihear">Preah Vihear</option>
                                        <option value="Pursat">Pursat</option>
                                        <option value="Ratanakiri">Ratanakiri</option>
                                        <option value="Siem Reap">Siem Reap</option>
                                        <option value="Prey Veng">Prey Veng</option>
                                        <option value="Stung Treng">Stung Treng</option>
                                        <option value="Svay Rieng">Svay Rieng</option>
                                        <option value="Takéo">Takéo</option>
                                        <option value="Kep">Kep</option>
                                        <option value="Otdar Meanchey">Otdar Meanchey</option>
                                        <option value="Pursat">Pursat</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="edit-latitude" class="form-label">Latitude:</label>
                                    <input type="text" class="form-control" id="edit-latitude" name="latitude" required>
                                </div>
                                <div class="mb-3">
                                    <label for="edit-longitude" class="form-label">Longitude:</label>
                                    <input type="text" class="form-control" id="edit-longitude" name="longitude" required>
                                </div>
                                <div class="mb-3">
                                    <label for="edit-status" class="form-label">Status:</label>
                                    <select id="edit-status" class="form-select" name="status" required>
                                        <option value="" selected disabled>Select Status</option>
                                        <option value="16h">⏰ 16 Hours</option>
                                        <option value="24h">⏰ 24 Hours</option>
                                        <option value="under construct">🚫 Under Construct</option>
                                    </select>

                                </div>
                                <!-- product  -->
                                <div class="mb-3">
                                    <label class="form-label">Product:</label><br>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="edit-ulg95" name="product[]" value="ULG 95">
                                        <label class="form-check-label" for="edit-ulg95">ULG 95</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="edit-ulr91" name="product[]" value="ULR 91">
                                        <label class="form-check-label" for="edit-ulr91">ULR 91</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="edit-hsd" name="product[]" value="HSD">
                                        <label class="form-check-label" for="edit-hsd">HSD</label>
                                    </div>
                                    <!-- Add more checkboxes as needed -->
                                </div>

                                <!-- other product  -->
                                <div class="mb-3">
                                    <label class="form-label">Other Product:</label><br>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="edit-ev" name="other_product[]" value="EV">
                                        <label class="form-check-label" for="edit-ev">EV</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="edit-onion" name="other_product[]" value="Onion">
                                        <label class="form-check-label" for="edit-onion">Onion</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="edit_otr" name="other_product[]" value="Otr">
                                        <label class="form-check-label" for="edit_otr">Otteri</label>
                                    </div>
                                    <!-- Add more checkboxes as needed -->
                                </div>
                                <!-- <div class="mb-3">
                            <label class="form-label">Promotions:</label><br>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit-promotion1" name="promotion[]"
                                    value="promotion1">
                                <label class="form-check-label" for="edit-promotion1">Promotion 1</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit-promotion2" name="promotion[]"
                                    value="promotion2">
                                <label class="form-check-label" for="edit-promotion2">Promotion 2</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit-promotion3" name="promotion[]"
                                    value="promotion3">
                                <label class="form-check-label" for="edit-promotion3">Promotion 3</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit-promotion-opening1"
                                    name="promotion[]" value="promotion-opening1">
                                <label class="form-check-label" for="edit-promotion-opening1">Promotion Opening
                                    1</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit-promotion-opening2"
                                    name="promotion[]" value="promotion-opening2">
                                <label class="form-check-label" for="edit-promotion-opening2">Promotion Opening
                                    2</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit-promotion-opening3"
                                    name="promotion[]" value="promotion-opening3">
                                <label class="form-check-label" for="edit-promotion-opening3">Promotion Opening
                                    3</label>
                            </div>
                        </div> -->

                                <!-- service  -->
                                <div class="mb-3">
                                    <label class="form-label">Service:</label><br>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="edit-amazon" name="description[]" value="Amazon">
                                        <label class="form-check-label" for="edit-amazon">Amazon</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="edit-7eleven" name="description[]" value="7-Eleven">
                                        <label class="form-check-label" for="edit-7eleven">7-Eleven</label>
                                    </div>
                                    <!-- Add more checkboxes as needed -->
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Payment:</label><br>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="edit-fleetcard" name="service[]" value="Fleet card">
                                        <label class="form-check-label" for="edit-fleetcard">Fleet Card</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="edit-aba" name="service[]" value="KHQR">
                                        <label class="form-check-label" for="edit-aba">KHQR</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="edit-test" name="service[]" value="Cash">
                                        <label class="form-check-label" for="edit-test">Cash</label>
                                    </div>
                                    <!-- Add more checkboxes as needed -->
                                </div>
                                <div class="mb-3">
                                    <label for="address" class="form-label">
                                        Address:
                                    </label>
                                    <input type="text" class="form-control" id="edit-address" name="address" required>
                                </div>
                                <div class="mb-3">
                                    <label for="edit-picture" class="form-label">New Picture:</label>
                                    <input type="file" class="form-control" id="edit-picture" name="picture">
                                </div>
                                <input type="hidden" id="old-picture" name="old_picture">
                                <!-- Display selected file name -->
                                <div id="edit-file-name"></div>
                                <button type="submit" class="btn btn-primary">Update Marker</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="imageModalLabel">Marker Image</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <img id="modalImage" src="" alt="Marker Image" style="max-width: 100%; height: auto;">
                        </div>
                    </div>
                </div>
            </div>
            <div class="container-fluid mt-5">
                <div class="card">
                    <div class="card-body">
                        <!-- Marker Data Table -->
                        <h2 class="card-title mt-5">Marker Data</h2>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#exampleModal">
                            Add Marker
                        </button>
                        <div class="table-responsive mt-3">
                            <table id="marker-table" class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Lat and Long </th>
                                        <th>Station</th>
                                        <th>Product</th>
                                        <th>Other Product</th>
                                        <th>Service</th>
                                        <th>Payment</th>
                                        <th>Province</th>
                                        <th>Address</th>
                                        <th>Image</th>
                                        <th>Status</th>
                                        <!-- <th>promotion</th> -->
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="marker-table-body"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>


        </div>
    </div>


</body>
<script>
                // Function to update the selected file name in the form
                document.getElementById('edit-picture').addEventListener('change', function() {
                    var fileName = this.value.split('\\').pop(); // Get the file name without path
                    document.getElementById('edit-file-name').textContent = 'Selected File: ' + fileName;
                });
            </script>
            <script>
                // Function to populate the province dropdown
                function populateProvinceDropdown(elementId) {
                    // Select the dropdown element
                    const dropdown = document.getElementById(elementId);

                    // Array of provinces
                    const provinces = [
                        "Banteay Meanchey", "Battambang", "Kampong Cham", "Kampong Chhnang", "Kampong Speu",
                        "Kampong Thom", "Kampot", "Kandal", "Koh Kong", "Kratié", "Mondulkiri", "Oddar Meanchey", "Phnom Penh",
                        "Pailin", "Preah Sihanouk", "Preah Vihear", "Pursat", "Ratanakiri", "Siem Reap", "Prey Veng",
                        "Stung Treng", "Svay Rieng", "Takéo", "Kep", "Otdar Meanchey", "Pursat"
                    ];

                    // Loop through provinces array and add each province as an option to the dropdown
                    provinces.forEach(province => {
                        const option = document.createElement("option");
                        option.value = province;
                        option.textContent = province;
                        dropdown.appendChild(option);
                    });
                }

                // Call the function to populate the dropdown for the add marker modal
                populateProvinceDropdown("edit1-province");

                // Call the function to populate the dropdown for the edit marker modal
                populateProvinceDropdown("edit1-province");
            </script>
            <!-- Bootstrap JS and Popper.js -->
            <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
            <!-- jQuery and DataTables JS -->
            <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
            <script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
            <script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
            <script>
                $(document).ready(function() {
                    $('#marker-table').DataTable({
                        "order": [], // Disables initial sorting
                        "processing": true, // Show processing indicator
                        "serverSide": false, // Enable server-side processing
                        "ajax": {
                            "url": "marker-interface.php",
                            "dataSrc": "STATION" // Specify the property name containing the data
                        },
                        "columns": [{
                                "data": "id"
                            },
                            {
                                "data": null,
                                "render": function(data, type, row) {
                                    return `<button class="btn btn-info btn-sm" onclick="viewLocation('${row.latitude}', '${row.longitude}')">View Location</button>`;
                                }
                            },
                            {
                                "data": "title"
                            },
                            {
                                "data": "product"
                            },
                            {
                                "data": "other_product"
                            },
                            {
                                "data": "description"
                            },
                            {
                                "data": "service"
                            },
                            {
                                "data": "province"
                            },
                            {
                                "data": null,
                                "render": function(data, type, row) {
                                    return `<button class="btn btn-primary btn-sm" onclick="viewAddress('${row.address}')">View</button>`;
                                }
                            },
                            {
                                "data": "picture",
                                "render": function(data) {
                                    if (data) {
                                        return `<a href="#" class="marker-image-link" data-bs-toggle="modal" data-bs-target="#imageModal" data-image="pictures/${data}"><img src="pictures/${data}" alt="Marker Image" style="max-width:40px; border-radius: 50%;"></a>`;
                                    } else {
                                        return 'No Image';
                                    }
                                }
                            },


                            {
                                "data": "status"
                            },
                            // { "data": "promotion" },
                            {
                                "data": null,
                                "render": function(data, type, row) {
                                    return `<button class="btn btn-danger btn-sm" onclick="deleteMarker(${data.id})"><i class="fas fa-trash-alt"></i></button>
                                    <button class="btn btn-primary btn-sm" onclick="editMarker(${data.id})" data-bs-toggle="modal" data-bs-target="#editModal"><i class="fas fa-edit"></i></button>`;
                                }
                            }
                        ]
                    });
                });

                function viewAddress(address) {
                    alert(address); // Display the address in an alert, you can modify this to open a modal or any other preferred method
                }

                function viewLocation(latitude, longitude) {
                    // You can handle viewing the location here, such as opening a map with the coordinates
                    alert(`${latitude},${longitude}`);
                }

                // Function to delete marker
                function deleteMarker(id) {
                    if (confirm('Are you sure you want to delete this marker?')) {
                        // Perform delete operation
                        fetch(`marker-interface.php?id=${id}`, {
                                method: 'DELETE'
                            })
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error('Network response was not ok');
                                }
                                return response.json();
                            })
                            .then(data => {
                                console.log(data);
                                // Refresh page to update marker list
                                window.location.reload();
                            })
                            .catch(error => console.error('Error:', error));
                    }
                }

                // Function to populate edit form and show edit modal
                function editMarker(id) {
                    // Fetch marker data corresponding to the ID
                    fetch(`marker-interface.php?id=${id}`)
                        .then(response => response.json())
                        .then(data => {
                            // Check if data is not empty and has the expected structure
                            if (data && data.STATION.length > 0) {
                                // Populate the modal form fields with the marker data
                                const marker = data.STATION[0];
                                document.getElementById('edit-id').value = marker.id;
                                document.getElementById('edit-latitude').value = marker.latitude;
                                document.getElementById('edit-longitude').value = marker.longitude;
                                document.getElementById('edit-title').value = marker.title;
                                document.getElementById('edit-province').value = marker.province;
                                document.getElementById('edit-address').value = marker.address;
                                document.getElementById('edit-status').value = marker.status;
                                // Populate checkbox groups if the arrays are not null
                                if (marker.product !== null) {
                                    marker.product.forEach(prod => {
                                        // Check each checkbox based on its value
                                        document.querySelectorAll(`input[name="product[]"]`).forEach(checkbox => {
                                            if (checkbox.value === prod) {
                                                checkbox.checked = true;
                                            }
                                        });
                                    });
                                }
                                if (marker.other_product !== null) {
                                    marker.other_product.forEach(oprod => {
                                        document.querySelectorAll(`input[name="other_product[]"]`).forEach(checkbox => {
                                            if (checkbox.value === oprod) {
                                                checkbox.checked = true;
                                            }
                                        });
                                    });
                                }
                                if (marker.description !== null) {
                                    marker.description.forEach(desc => {
                                        document.querySelectorAll(`input[name="description[]"]`).forEach(checkbox => {
                                            if (checkbox.value === desc) {
                                                checkbox.checked = true;
                                            }
                                        });
                                    });
                                }
                                if (marker.service !== null) {
                                    marker.service.forEach(serv => {
                                        document.querySelectorAll(`input[name="service[]"]`).forEach(checkbox => {
                                            if (checkbox.value === serv) {
                                                checkbox.checked = true;
                                            }
                                        });
                                    });
                                }
                                // if (marker.promotion !== null) {
                                //     marker.promotion.forEach(promo => {
                                //         document.querySelectorAll(`input[name="promotion[]"]`).forEach(checkbox => {
                                //             if (checkbox.value === promo) {
                                //                 checkbox.checked = true;
                                //             }
                                //         });
                                //     });
                                // }
                                // Set the old picture filename or an empty string if no picture exists
                                document.getElementById('old-picture').value = marker.picture || '';

                                // Display the filename of the existing picture, if available
                                if (marker.picture) {
                                    const fileName = marker.picture.split('/').pop(); // Extract filename from the path
                                    document.getElementById('edit-file-name').textContent = 'Selected File: ' + fileName;
                                } else {
                                    document.getElementById('edit-file-name').textContent = 'No Picture Selected';
                                }
                            } else {
                                console.error('No data found for the specified ID.');
                            }
                        })
                        .catch(error => console.error('Error:', error));
                }
            </script>
            <script>
                $(document).on('click', '.marker-image-link', function(e) {
                    e.preventDefault(); // Prevent the default behavior of the link
                    var imageUrl = $(this).data('image');
                    $('#modalImage').attr('src', imageUrl);
                    $('#imageModal').modal('show');
                });
            </script>
</html>