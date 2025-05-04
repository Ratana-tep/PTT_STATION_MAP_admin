<?php
//session_start();
//date_default_timezone_set('Asia/Phnom_Penh');
//
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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Stations | PTT Map</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <!-- DataTables -->
    <script type="text/javascript" src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script type="text/javascript" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBWfYa4jsQg-YtPDdFYPLLDDBDiqRvr3d8"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">


    <style>
        /* Custom animations */
        @keyframes slideInRight {
            from {
                transform: translateX(20px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .animate-slide-in-right {
            animation: slideInRight 0.5s ease-out forwards;
        }

        /* Button hover effect */
        .btn-hover-effect {
            transition: all 0.2s ease;
            transform: translateY(0);
        }

        .btn-hover-effect:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        /* Custom DataTables styling */
        .dataTables_wrapper .dataTables_length select {
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            padding: 0.25rem 1.5rem 0.25rem 0.5rem;
        }

        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            padding: 0.375rem 0.75rem;
            margin-left: 0.5rem;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            border: 1px solid transparent;
            padding: 0.375rem 0.75rem;
            border-radius: 0.375rem;
            margin-left: 0.25rem;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #3b82f6;
            color: white !important;
            border-color: #3b82f6;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button:hover {
            background: #e5e7eb;
            border-color: #d1d5db;
        }

        .dataTables_wrapper .dataTables_info {
            padding-top: 0.75rem;
        }

        /* ... existing styles ... */
        #map-container {
            width: 100%;
            height: 400px;
            position: relative;
        }

    </style>
</head>

<body class="bg-gray-50 font-sans antialiased">
<div class="flex h-screen overflow-hidden">
    <!-- Sidebar -->
    <div class="w-64 bg-gradient-to-b from-blue-900 to-blue-800 text-white flex-shrink-0">
        <div class="flex items-center justify-center py-6 px-4 bg-gray-800">
            <img src="./pictures/logo_Station.png" alt="PTT Logo" class="h-8 mr-3">
        </div>
        <nav class="mt-6">
            <div class="px-4 space-y-1">
                <a href="index.php"
                   class="group flex items-center px-4 py-3 text-sm font-medium rounded-lg text-blue-100 hover:text-white hover:bg-blue-700 transition-colors duration-200">
                    <span class="material-icons mr-3">dashboard</span>
                    <span class="opacity-100 group-hover:opacity-100">Overview</span>
                </a>
                <a href="manage.php"
                   class="group flex items-center px-4 py-3 text-sm font-medium rounded-lg text-blue-100 hover:text-white hover:bg-blue-700 transition-colors duration-200">
                    <span class="material-icons mr-3">campaign</span>
                    <span class="opacity-100 group-hover:opacity-100">Marketing</span>
                </a>
                <a href="station_admin.php"
                   class="group flex items-center px-4 py-3 text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 transition-colors duration-200">
                    <span class="material-icons mr-3">local_gas_station</span>
                    <span class="opacity-100 group-hover:opacity-100">Stations</span>
                </a>
            </div>
        </nav>
    </div>

    <!-- Main content -->
    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Top navigation -->
        <header class="bg-white shadow-sm z-10">
            <div class="flex items-center justify-between px-6 py-4">
                <div class="flex items-center">
                    <h1 class="text-2xl font-semibold text-gray-800 ml-4">Station Management</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <button class="btn-hover-effect bg-gradient-to-r from-red-500 to-red-600 hover:from-red-600 hover:to-red-700 text-white px-4 py-2 rounded-lg shadow-md flex items-center">
                        <i class="fas fa-sign-out-alt mr-2"></i> Logout
                    </button>
                </div>
            </div>
        </header>

        <!-- Main content area -->
        <main class="flex-1 overflow-y-auto p-6 bg-gray-50">
            <!-- Add Station Button -->
            <div class="animate-slide-in-right mb-8">
                <button type="button"
                        class="btn-hover-effect bg-gradient-to-r from-blue-500 to-blue-600 hover:from-blue-600 hover:to-blue-700 text-white px-6 py-3 rounded-lg shadow-md flex items-center"
                        data-bs-toggle="modal" data-bs-target="#addStationModal">
                    <i class="fas fa-plus-circle mr-2"></i> Add New Station
                </button>
            </div>

            <!-- Stations Table -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden">
                <div class="p-6">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">All Stations</h2>
                    <div class="overflow-x-auto">
                        <table id="stations-table" class="min-w-full divide-y divide-gray-200 stripe hover">
                            <thead class="bg-gray-50">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    ID
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Location
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Station
                                </th>
                                <!--                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Products</th>-->
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Products
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Services
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Payment
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Province
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Status
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Image
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                            <!-- Data will be loaded via AJAX -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<!-- Add Station Modal -->
<div class="modal fade fixed inset-0 z-50 hidden w-full h-full outline-none overflow-x-hidden overflow-y-auto"
     id="addStationModal" tabindex="-1" aria-labelledby="addStationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg relative w-auto pointer-events-none my-6 mx-auto max-w-4xl">
        <div class="modal-content border-none shadow-lg relative flex flex-col w-full pointer-events-auto bg-white bg-clip-padding rounded-md outline-none text-current">
            <div class="modal-header flex flex-shrink-0 items-center justify-between p-4 border-b border-gray-200 rounded-t-md bg-gradient-to-r from-blue-500 to-blue-600">
                <h5 class="text-xl font-medium leading-normal text-white" id="addStationModalLabel">Add New Station</h5>
                <button type="button"
                        class="btn-close box-content w-4 h-4 p-1 text-white border-none rounded-none opacity-50 focus:shadow-none focus:outline-none focus:opacity-100 hover:text-white hover:opacity-75 hover:no-underline"
                        data-bs-dismiss="modal" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body relative p-6 overflow-y-auto max-h-[80vh]">
                <form id="station-form" method="POST" action="marker-interface.php" enctype="multipart/form-data"
                      class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="id" class="block text-sm font-medium text-gray-700 mb-1">Station ID</label>
                            <input type="text" id="id" name="id" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 text-sm">
                        </div>
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Station Name</label>
                            <input type="text" id="title" name="title" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 text-sm">
                        </div>
                        <div>
                            <label for="province" class="block text-sm font-medium text-gray-700 mb-1">Province</label>
                            <select id="province" name="province" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 text-sm">
                                <option value="" selected disabled>Select Province</option>
                                <!-- Options will be populated by JavaScript -->
                            </select>
                        </div>
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select id="status" name="status" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 text-sm">
                                <option value="" selected disabled>Select Status</option>
                                <option value="16h">⏰ 16 Hours</option>
                                <option value="24h">⏰ 24 Hours</option>
                                <option value="under construct">🚫 Under Construct</option>
                            </select>
                        </div>
                        <div>
                            <label for="latitude" class="block text-sm font-medium text-gray-700 mb-1">Latitude</label>
                            <input type="text" id="latitude" name="latitude" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 text-sm">
                        </div>
                        <div>
                            <label for="longitude"
                                   class="block text-sm font-medium text-gray-700 mb-1">Longitude</label>
                            <input type="text" id="longitude" name="longitude" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 text-sm">
                        </div>
                    </div>

                    <div class="pt-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Products</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
                            <div class="flex items-center">
                                <input id="ulg95" name="product[]" type="checkbox" value="ULG 95"
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded text-sm">
                                <label for="ulg95" class="ml-2 block text-sm text-gray-700">ULG 95</label>
                            </div>
                            <div class="flex items-center">
                                <input id="ulr91" name="product[]" type="checkbox" value="ULR 91"
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded text-sm">
                                <label for="ulr91" class="ml-2 block text-sm text-gray-700">ULR 91</label>
                            </div>
                            <div class="flex items-center">
                                <input id="hsd" name="product[]" type="checkbox" value="HSD"
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded text-sm">
                                <label for="hsd" class="ml-2 block text-sm text-gray-700">HSD</label>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Other Products</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
                            <div class="flex items-center">
                                <input id="ev" name="other_product[]" type="checkbox" value="EV"
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded text-sm">
                                <label for="ev" class="ml-2 block text-sm text-gray-700">EV</label>
                            </div>
                            <div class="flex items-center">
                                <input id="onion" name="other_product[]" type="checkbox" value="Onion"
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded text-sm">
                                <label for="onion" class="ml-2 block text-sm text-gray-700">Onion</label>
                            </div>

                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Services</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
                            <div class="flex items-center">
                                <input id="amazon" name="description[]" type="checkbox" value="Amazon"
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded text-sm">
                                <label for="amazon" class="ml-2 block text-sm text-gray-700">Amazon</label>
                            </div>
                            <div class="flex items-center">
                                <input id="7eleven" name="description[]" type="checkbox" value="7-Eleven"
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded text-sm">
                                <label for="7eleven" class="ml-2 block text-sm text-gray-700">7-Eleven</label>
                            </div>
                            <div class="flex items-center">
                                <input id="otr" name="description[]" type="checkbox" value="Otr"
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded text-sm">
                                <label for="otr" class="ml-2 block text-sm text-gray-700">Otteri</label>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Payment Methods</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
                            <div class="flex items-center">
                                <input id="fleetcard" name="service[]" type="checkbox" value="Fleet card"
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded text-sm">
                                <label for="fleetcard" class="ml-2 block text-sm text-gray-700">Fleet Card</label>
                            </div>
                            <div class="flex items-center">
                                <input id="aba" name="service[]" type="checkbox" value="KHQR"
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded text-sm">
                                <label for="aba" class="ml-2 block text-sm text-gray-700">KHQR</label>
                            </div>
                            <div class="flex items-center">
                                <input id="test" name="service[]" type="checkbox" value="Cash"
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded text-sm">
                                <label for="test" class="ml-2 block text-sm text-gray-700">Cash</label>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label for="address" class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                        <input type="text" id="address" name="address" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 text-sm">
                    </div>

                    <div>
                        <label for="picture" class="block text-sm font-medium text-gray-700 mb-1">Station Image</label>
                        <div class="mt-1 flex items-center">
                            <input type="file" id="picture" name="picture"
                                   class="block w-full text-sm text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        </div>
                    </div>

                    <div class="modal-footer flex flex-shrink-0 flex-wrap items-center justify-end p-4 border-t border-gray-200 rounded-b-md">
                        <button type="button"
                                class="btn-hover-effect px-4 py-2 bg-gray-200 text-gray-700 text-xs font-medium uppercase rounded shadow-md hover:bg-gray-300 transition duration-150 ease-in-out"
                                data-bs-dismiss="modal">Close
                        </button>
                        <button type="submit"
                                class="btn-hover-effect px-4 py-2 bg-blue-600 text-white text-xs font-medium uppercase rounded shadow-md hover:bg-blue-700 transition duration-150 ease-in-out ml-2">
                            Add Station
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Station Modal -->
<div class="modal fade fixed inset-0 z-50 hidden w-full h-full outline-none overflow-x-hidden overflow-y-auto"
     id="editStationModal" tabindex="-1" aria-labelledby="editStationModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg relative w-auto pointer-events-none my-6 mx-auto max-w-4xl">
        <div class="modal-content border-none shadow-lg relative flex flex-col w-full pointer-events-auto bg-white bg-clip-padding rounded-md outline-none text-current">
            <div class="modal-header flex flex-shrink-0 items-center justify-between p-4 border-b border-gray-200 rounded-t-md bg-gradient-to-r from-blue-500 to-blue-600">
                <h5 class="text-xl font-medium leading-normal text-white" id="editStationModalLabel">Edit Station</h5>
                <button type="button"
                        class="btn-close box-content w-4 h-4 p-1 text-white border-none rounded-none opacity-50 focus:shadow-none focus:outline-none focus:opacity-100 hover:text-white hover:opacity-75 hover:no-underline"
                        data-bs-dismiss="modal" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body relative p-6 overflow-y-auto max-h-[80vh]">
                <form id="edit-station-form" method="POST" action="marker-interface.php" enctype="multipart/form-data"
                      class="space-y-4">
                    <input type="hidden" id="edit-id" name="id">
                    <input type="hidden" id="old-picture" name="old_picture">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="edit-title" class="block text-sm font-medium text-gray-700 mb-1">Station
                                Name</label>
                            <input type="text" id="edit-title" name="title" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 text-sm">
                        </div>
                        <div>
                            <label for="edit-province"
                                   class="block text-sm font-medium text-gray-700 mb-1">Province</label>
                            <select id="edit-province" name="province" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 text-sm">
                                <option value="" selected disabled>Select Province</option>
                                <!-- Options will be populated by JavaScript -->
                            </select>
                        </div>
                        <div>
                            <label for="edit-status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select id="edit-status" name="status" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 text-sm">
                                <option value="" selected disabled>Select Status</option>
                                <option value="16h">⏰ 16 Hours</option>
                                <option value="24h">⏰ 24 Hours</option>
                                <option value="under construct">🚫 Under Construct</option>
                            </select>
                        </div>
                        <div>
                            <label for="edit-latitude"
                                   class="block text-sm font-medium text-gray-700 mb-1">Latitude</label>
                            <input type="text" id="edit-latitude" name="latitude" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 text-sm">
                        </div>
                        <div>
                            <label for="edit-longitude"
                                   class="block text-sm font-medium text-gray-700 mb-1">Longitude</label>
                            <input type="text" id="edit-longitude" name="longitude" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 text-sm">
                        </div>
                    </div>

                    <div class="pt-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Products</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
                            <div class="flex items-center">
                                <input id="edit-ulg95" name="product[]" type="checkbox" value="ULG 95"
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded text-sm">
                                <label for="edit-ulg95" class="ml-2 block text-sm text-gray-700">ULG 95</label>
                            </div>
                            <div class="flex items-center">
                                <input id="edit-ulr91" name="product[]" type="checkbox" value="ULR 91"
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded text-sm">
                                <label for="edit-ulr91" class="ml-2 block text-sm text-gray-700">ULR 91</label>
                            </div>
                            <div class="flex items-center">
                                <input id="edit-hsd" name="product[]" type="checkbox" value="HSD"
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded text-sm">
                                <label for="edit-hsd" class="ml-2 block text-sm text-gray-700">HSD</label>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Other Products</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
                            <div class="flex items-center">
                                <input id="edit-ev" name="other_product[]" type="checkbox" value="EV"
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded text-sm">
                                <label for="edit-ev" class="ml-2 block text-sm text-gray-700">EV</label>
                            </div>
                            <div class="flex items-center">
                                <input id="edit-onion" name="other_product[]" type="checkbox" value="Onion"
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded text-sm">
                                <label for="edit-onion" class="ml-2 block text-sm text-gray-700">Onion</label>
                            </div>

                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Services</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
                            <div class="flex items-center">
                                <input id="edit-amazon" name="description[]" type="checkbox" value="Amazon"
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded text-sm">
                                <label for="edit-amazon" class="ml-2 block text-sm text-gray-700">Amazon</label>
                            </div>
                            <div class="flex items-center">
                                <input id="edit-7eleven" name="description[]" type="checkbox" value="7-Eleven"
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded text-sm">
                                <label for="edit-7eleven" class="ml-2 block text-sm text-gray-700">7-Eleven</label>
                            </div>
                            <div class="flex items-center">
                                <input id="edit-otr" name="description[]" type="checkbox" value="Otr"
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded text-sm">
                                <label for="edit-otr" class="ml-2 block text-sm text-gray-700">Otteri</label>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Payment Methods</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2">
                            <div class="flex items-center">
                                <input id="edit-fleetcard" name="service[]" type="checkbox" value="Fleet card"
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded text-sm">
                                <label for="edit-fleetcard" class="ml-2 block text-sm text-gray-700">Fleet Card</label>
                            </div>
                            <div class="flex items-center">
                                <input id="edit-aba" name="service[]" type="checkbox" value="KHQR"
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded text-sm">
                                <label for="edit-aba" class="ml-2 block text-sm text-gray-700">KHQR</label>
                            </div>
                            <div class="flex items-center">
                                <input id="edit-test" name="service[]" type="checkbox" value="Cash"
                                       class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded text-sm">
                                <label for="edit-test" class="ml-2 block text-sm text-gray-700">Cash</label>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label for="edit-address" class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                        <input type="text" id="edit-address" name="address" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200 text-sm">
                    </div>

                    <div>
                        <label for="edit-picture" class="block text-sm font-medium text-gray-700 mb-1">Station
                            Image</label>
                        <div class="mt-1 flex items-center">
                            <input type="file" id="edit-picture" name="picture"
                                   class="block w-full text-sm text-gray-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                        </div>
                        <div id="current-image" class="mt-2 text-xs text-gray-500"></div>
                    </div>

                    <div class="modal-footer flex flex-shrink-0 flex-wrap items-center justify-end p-4 border-t border-gray-200 rounded-b-md">
                        <button type="button"
                                class="btn-hover-effect px-4 py-2 bg-gray-200 text-gray-700 text-xs font-medium uppercase rounded shadow-md hover:bg-gray-300 transition duration-150 ease-in-out"
                                data-bs-dismiss="modal">Close
                        </button>
                        <button type="submit"
                                class="btn-hover-effect px-4 py-2 bg-blue-600 text-white text-xs font-medium uppercase rounded shadow-md hover:bg-blue-700 transition duration-150 ease-in-out ml-2">
                            Update Station
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Image Preview Modal -->
<div class="modal fade fixed inset-0 z-50 hidden w-full h-full outline-none overflow-x-hidden overflow-y-auto"
     id="imagePreviewModal" tabindex="-1" aria-labelledby="imagePreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg relative w-auto pointer-events-none my-6 mx-auto max-w-4xl">
        <div class="modal-content border-none shadow-lg relative flex flex-col w-full pointer-events-auto bg-white bg-clip-padding rounded-md outline-none text-current">
            <div class="modal-header flex flex-shrink-0 items-center justify-between p-4 border-b border-gray-200 rounded-t-md bg-gradient-to-r from-blue-500 to-blue-600">
                <h5 class="text-xl font-medium leading-normal text-white" id="imagePreviewModalLabel">Station Image</h5>
                <button type="button"
                        class="btn-close box-content w-4 h-4 p-1 text-white border-none rounded-none opacity-50 focus:shadow-none focus:outline-none focus:opacity-100 hover:text-white hover:opacity-75 hover:no-underline"
                        data-bs-dismiss="modal" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body relative p-4 flex justify-center items-center">
                <img id="modal-preview-image" src="" alt="Station Image"
                     class="max-w-full max-h-[70vh] object-contain rounded-lg shadow-md">
            </div>
            <div class="modal-footer flex flex-shrink-0 flex-wrap items-center justify-end p-4 border-t border-gray-200 rounded-b-md">
                <button type="button"
                        class="btn-hover-effect px-4 py-2 bg-gray-200 text-gray-700 text-xs font-medium uppercase rounded shadow-md hover:bg-gray-300 transition duration-150 ease-in-out"
                        data-bs-dismiss="modal">Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Location Preview Modal -->
<div class="modal fade fixed inset-0 z-50 hidden w-full h-full outline-none overflow-x-hidden overflow-y-auto"
     id="locationPreviewModal" tabindex="-1" aria-labelledby="locationPreviewModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg relative w-auto pointer-events-none my-6 mx-auto max-w-4xl">
        <div class="modal-content border-none shadow-lg relative flex flex-col w-full pointer-events-auto bg-white bg-clip-padding rounded-md outline-none text-current">
            <div class="modal-header flex flex-shrink-0 items-center justify-between p-4 border-b border-gray-200 rounded-t-md bg-gradient-to-r from-blue-500 to-blue-600">
                <h5 class="text-xl font-medium leading-normal text-white" id="locationPreviewModalLabel">Station
                    Location</h5>
                <button type="button"
                        class="btn-close box-content w-4 h-4 p-1 text-white border-none rounded-none opacity-50 focus:shadow-none focus:outline-none focus:opacity-100 hover:text-white hover:opacity-75 hover:no-underline"
                        data-bs-dismiss="modal" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body relative p-4">
                <div class="mb-4">
                    <h6 class="text-lg font-semibold text-gray-800">Coordinates</h6>
                    <p id="location-coordinates" class="text-gray-600 text-sm"></p>
                </div>
                <div id="map-container" class="h-96 w-full rounded-lg overflow-hidden relative">
                    <div id="map-error"
                         class="hidden absolute inset-0 bg-gray-100 flex flex-col items-center justify-center p-4 text-center">
                        <i class="fas fa-exclamation-triangle text-red-500 text-4xl mb-3"></i>
                        <h4 class="text-lg font-semibold text-gray-800 mb-1">Map Loading Error</h4>
                        <p id="map-error-message" class="text-gray-600 text-sm"></p>
                        <button id="retry-load-map"
                                class="mt-3 px-4 py-2 bg-blue-100 text-blue-600 rounded-lg text-sm font-medium">
                            <i class="fas fa-sync-alt mr-1"></i> Retry
                        </button>
                    </div>
                    <div id="map-loading" class="absolute inset-0 bg-gray-100 flex items-center justify-center">
                        <div class="text-center">
                            <i class="fas fa-spinner fa-spin text-blue-500 text-3xl mb-2"></i>
                            <p class="text-gray-600">Loading map...</p>
                        </div>
                    </div>
                </div>
                <div class="mt-4 flex justify-between items-center">
                    <div class="text-sm text-gray-500">
                        <i class="fas fa-info-circle mr-1"></i> Click and drag to navigate the map
                    </div>
                    <button id="open-google-maps"
                            class="btn-hover-effect bg-blue-100 text-blue-600 px-3 py-1 rounded-lg text-sm font-medium">
                        <i class="fas fa-external-link-alt mr-1"></i> Open in Google Maps
                    </button>
                </div>
            </div>
            <div class="modal-footer flex flex-shrink-0 flex-wrap items-center justify-end p-4 border-t border-gray-200 rounded-b-md">
                <button type="button"
                        class="btn-hover-effect px-4 py-2 bg-gray-200 text-gray-700 text-xs font-medium uppercase rounded shadow-md hover:bg-gray-300 transition duration-150 ease-in-out"
                        data-bs-dismiss="modal">Close
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Configuration object for easy customization
    const mapConfig = {
        apiKey: 'AIzaSyBWfYa4jsQg-YtPDdFYPLLDDBDiqRvr3d8',
        defaultZoom: 15,
        fallbackMapLink: 'https://www.google.com/maps',
        loadingMessage: 'Loading map...',
        errorMessages: {
            elementNotFound: 'Map components not available',
            apiFailed: 'Failed to load Google Maps',
            invalidCoords: 'Invalid coordinates provided'
        }
    };

    // Global state
    const mapState = {
        initialized: false,
        currentModal: null,
        map: null,
        marker: null
    };

    // Initialize when DOM is ready
    document.addEventListener('DOMContentLoaded', function () {
        setupMapModal();
        loadGoogleMaps();
    });

    // Set up modal with flexible element detection
    function setupMapModal() {
        // Try to get elements with fallbacks
        const modalEl = getElementWithFallback('locationPreviewModal', () => {
            console.warn('Modal element not found, creating fallback');
            return createFallbackModal();
        });

        // Set up modal events if available
        if (modalEl) {
            modalEl.addEventListener('shown.bs.modal', handleModalShown);
            modalEl.addEventListener('hidden.bs.modal', handleModalHidden);
        }

        // Set up buttons with existence checks
        setupButton('retry-load-map', handleRetryClick);
        setupButton('open-google-maps', handleOpenInMapsClick);
    }

    // Flexible element getter with fallback
    function getElementWithFallback(id, fallbackFn) {
        const el = document.getElementById(id);
        if (!el && fallbackFn) {
            return fallbackFn();
        }
        return el;
    }

    // Create a simple fallback modal if needed
    function createFallbackModal() {
        const modal = document.createElement('div');
        modal.className = 'fallback-modal';
        modal.innerHTML = `
    <div class="modal-content">
      <div id="fallback-location-info"></div>
      <div id="fallback-map-container">
        <p>${mapConfig.errorMessages.elementNotFound}</p>
        <a id="fallback-map-link" href="#" target="_blank">View in Google Maps</a>
      </div>
    </div>
  `;
        document.body.appendChild(modal);
        return modal;
    }

    // Handle modal shown event
    function handleModalShown() {
        const coordsEl = getElementWithFallback('location-coordinates');
        if (coordsEl && coordsEl.textContent) {
            const [lat, lng] = parseCoordinates(coordsEl.textContent);
            if (lat && lng) {
                initMap(lat, lng);
            }
        }
    }

    // Handle modal hidden event
    function handleModalHidden() {
        cleanUpMap();
    }

    // Set up button with existence check
    function setupButton(id, handler) {
        const btn = document.getElementById(id);
        if (btn) {
            btn.addEventListener('click', handler);
        }
    }

    // View location - main entry point
    function viewLocation(latitude, longitude) {
        try {
            // Safely handle coordinates
            const lat = parseFloat(latitude);
            const lng = parseFloat(longitude);

            if (isNaN(lat) || isNaN(lng)) {
                throw new Error(mapConfig.errorMessages.invalidCoords);
            }

            // Try standard modal flow first
            if (tryStandardModalFlow(lat, lng)) {
                return;
            }

            // Fallback to simple display
            fallbackLocationDisplay(lat, lng);

        } catch (error) {
            console.error('Location view error:', error);
            showUserMessage(error.message || 'Failed to show location');
        }
    }

    // Try standard modal flow
    function tryStandardModalFlow(lat, lng) {
        const coordsEl = getElementWithFallback('location-coordinates');
        const modalEl = getElementWithFallback('locationPreviewModal');

        if (!coordsEl || !modalEl) {
            return false;
        }

        // Update coordinates
        coordsEl.textContent = `Latitude: ${lat}, Longitude: ${lng}`;

        // Show loading state if elements exist
        const loadingEl = document.getElementById('map-loading');
        const errorEl = document.getElementById('map-error');
        if (loadingEl) loadingEl.classList.remove('hidden');
        if (errorEl) errorEl.classList.add('hidden');

        // Show modal
        const modal = new bootstrap.Modal(modalEl);
        modal.show();

        return true;
    }

    // Fallback display when modal isn't available
    function fallbackLocationDisplay(lat, lng) {
        // Try to find or create fallback elements
        const container = getElementWithFallback('fallback-map-container', () => {
            const div = document.createElement('div');
            div.id = 'fallback-map-container';
            document.body.appendChild(div);
            return div;
        });

        const link = getElementWithFallback('fallback-map-link', () => {
            const a = document.createElement('a');
            a.id = 'fallback-map-link';
            a.target = '_blank';
            container.appendChild(a);
            return a;
        });

        const info = getElementWithFallback('fallback-location-info', () => {
            const div = document.createElement('div');
            div.id = 'fallback-location-info';
            container.prepend(div);
            return div;
        });

        // Update content
        info.textContent = `Location: ${lat}, ${lng}`;
        link.href = `${mapConfig.fallbackMapLink}?q=${lat},${lng}`;
        link.textContent = 'Open in Google Maps';

        // Show to user
        showUserMessage(`Location: ${lat}, ${lng} - Click below to view in Google Maps`, 'info');
    }

    // Load Google Maps API
    function loadGoogleMaps() {
        if (window.google && google.maps) return;

        const script = document.createElement('script');
        script.src = `https://maps.googleapis.com/maps/api/js?key=${mapConfig.apiKey}&callback=handleMapsApiLoaded`;
        script.async = true;
        script.defer = true;
        script.onerror = handleMapsApiError;
        document.head.appendChild(script);
    }

    // Handle successful API load
    function handleMapsApiLoaded() {
        console.log('Google Maps API loaded');
        // Ready for map initialization
    }

    // Handle API load error
    function handleMapsApiError() {
        console.error('Failed to load Google Maps API');
        showUserMessage(mapConfig.errorMessages.apiFailed, 'error');
    }

    // Initialize map with flexible approach
    function initMap(latitude, longitude) {
        try {
            const mapContainer = getElementWithFallback('map-container');
            if (!mapContainer) {
                throw new Error('Map container not available');
            }

            // Wait for container to be visible
            if (mapContainer.offsetWidth === 0 || mapContainer.offsetHeight === 0) {
                setTimeout(() => initMap(latitude, longitude), 100);
                return;
            }

            // Create map
            mapState.map = new google.maps.Map(mapContainer, {
                center: {lat: latitude, lng: longitude},
                zoom: mapConfig.defaultZoom
            });

            // Add marker
            mapState.marker = new google.maps.Marker({
                position: {lat: latitude, lng: longitude},
                map: mapState.map,
                title: 'Station Location'
            });

            // Update UI
            const loadingEl = document.getElementById('map-loading');
            if (loadingEl) loadingEl.classList.add('hidden');

            mapState.initialized = true;

        } catch (error) {
            console.error('Map init error:', error);
            showMapError(error.message || mapConfig.errorMessages.apiFailed);
        }
    }

    // Clean up map resources
    function cleanUpMap() {
        if (mapState.marker) {
            mapState.marker.setMap(null);
            mapState.marker = null;
        }
        mapState.map = null;
        mapState.initialized = false;
    }

    // Show error in map container
    function showMapError(message) {
        const errorEl = getElementWithFallback('map-error');
        const messageEl = getElementWithFallback('map-error-message');
        const loadingEl = getElementWithFallback('map-loading');

        if (errorEl) errorEl.classList.remove('hidden');
        if (messageEl) messageEl.textContent = message;
        if (loadingEl) loadingEl.classList.add('hidden');
    }

    // Show user message (toast/alert)
    function showUserMessage(message, type = 'error') {
        // Implement your preferred user notification method
        console.log(`${type}: ${message}`);
        alert(`${type.toUpperCase()}: ${message}`);
    }

    // Button handlers
    function handleRetryClick() {
        const coordsEl = getElementWithFallback('location-coordinates');
        if (coordsEl && coordsEl.textContent) {
            const [lat, lng] = parseCoordinates(coordsEl.textContent);
            initMap(lat, lng);
        }
    }

    function handleOpenInMapsClick() {
        const coordsEl = getElementWithFallback('location-coordinates');
        if (coordsEl && coordsEl.textContent) {
            const [lat, lng] = parseCoordinates(coordsEl.textContent);
            window.open(`${mapConfig.fallbackMapLink}?q=${lat},${lng}`, '_blank');
        }
    }

    // Parse coordinates from text
    function parseCoordinates(text) {
        try {
            const lat = parseFloat(text.split('Latitude: ')[1].split(',')[0]);
            const lng = parseFloat(text.split('Longitude: ')[1]);
            return [lat, lng];
        } catch (e) {
            console.error('Coordinate parsing error:', e);
            return [null, null];
        }
    }
</script>

<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Initialize DataTable with enhanced features
    $(document).ready(function () {
        $('#stations-table').DataTable({
            "ajax": {
                "url": "marker-interface.php",
                "dataSrc": "STATION"
            },
            "columns": [
                {
                    "data": "id",
                    "className": "px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"
                },
                {
                    "data": null,
                    "className": "px-6 py-4 whitespace-nowrap",
                    "render": function (data, type, row) {
                        return `<button class="btn-hover-effect bg-blue-100 text-blue-800 px-3 py-1 rounded-lg text-sm font-medium" onclick="viewLocation('${row.latitude}', '${row.longitude}')">
                                <i class="fas fa-map-marker-alt mr-1"></i> View
                            </button>`;
                    }
                },
                {
                    "data": "title",
                    "className": "px-6 py-4 whitespace-nowrap text-sm text-gray-500"
                },
                {
                    "data": "other_product",
                    "className": "px-6 py-4 text-sm text-gray-500",
                    "render": function (data) {
                        if (!data || data.length === 0) return 'N/A';

                        // Define color classes for different products
                        const colorMap = {
                            'EV': 'bg-purple-100 text-purple-800',
                            'Onion': 'bg-orange-100 text-orange-800'
                            // Add more mappings as needed
                        };

                        return data.map(item => {
                            const colorClass = colorMap[item] || 'bg-gray-100 text-gray-800';
                            return `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${colorClass} mr-1 mb-1">${item}</span>`;
                        }).join('');
                    }
                },
                {
                    "data": "description",
                    "className": "px-6 py-4 text-sm text-gray-500",
                    "render": function (data) {
                        if (!data || data.length === 0) return 'N/A';

                        // Define color classes for different items in description
                        const colorMap = {
                            'Amazon': 'bg-purple-100 text-purple-800',
                            '7-Eleven': 'bg-orange-100 text-orange-800',
                            'Otr': 'bg-teal-100 text-teal-800'
                            // Add more mappings as needed
                        };

                        return data.map(item => {
                            const colorClass = colorMap[item] || 'bg-gray-100 text-gray-800';
                            return `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${colorClass} mr-1 mb-1">${item}</span>`;
                        }).join('');
                    }
                },
                {
                    "data": "service",
                    "className": "px-6 py-4 text-sm text-gray-500",
                    "render": function (data) {
                        if (!data || data.length === 0) return 'N/A';

                        // Define color classes for different payment methods
                        const colorMap = {
                            'Fleet card': 'bg-indigo-100 text-indigo-800',
                            'KHQR': 'bg-green-100 text-green-800',
                            'Cash': 'bg-yellow-100 text-yellow-800',
                            // Add more mappings as needed
                        };

                        return data.map(item => {
                            const colorClass = colorMap[item] || 'bg-gray-100 text-gray-800';
                            return `<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${colorClass} mr-1 mb-1">${item}</span>`;
                        }).join('');
                    }
                },
                {
                    "data": "province",
                    "className": "px-6 py-4 whitespace-nowrap text-sm text-gray-500"
                },
                {
                    "data": "status",
                    "className": "px-6 py-4 whitespace-nowrap",
                    "render": function (data) {
                        if (data === '16h') return '<span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-medium">16 Hours</span>';
                        if (data === '24h') return '<span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-medium">24 Hours</span>';
                        if (data === 'under construct') return '<span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs font-medium">Under Construct</span>';
                        return data;
                    }
                },
                {
                    "data": "picture",
                    "className": "px-6 py-4 whitespace-nowrap text-sm text-gray-500",
                    "render": function (data) {
                        if (data) {
                            return `<a href="#" class="marker-image-link" data-bs-toggle="modal" data-bs-target="#imagePreviewModal" data-image="pictures/${data}">
                                    <img src="pictures/${data}" alt="Station Image" class="w-10 h-10 rounded-full object-cover border-2 border-blue-200 hover:border-blue-400 transition duration-200">
                                </a>`;
                        } else {
                            return '<span class="text-gray-400">No Image</span>';
                        }
                    }
                },
                {
                    "data": null,
                    "className": "px-6 py-4 whitespace-nowrap text-sm font-medium",
                    "render": function (data, type, row) {
                        return `<div class="flex space-x-2">
                                <button class="btn-hover-effect bg-red-100 text-red-600 px-3 py-1 rounded-lg text-sm font-medium" onclick="deleteStation(${row.id})">
                                    <i class="fas fa-trash-alt mr-1"></i> Delete
                                </button>
                                <button class="btn-hover-effect bg-blue-100 text-blue-600 px-3 py-1 rounded-lg text-sm font-medium" onclick="editStation(${row.id})" data-bs-toggle="modal" data-bs-target="#editStationModal">
                                    <i class="fas fa-edit mr-1"></i> Edit
                                </button>
                            </div>`;
                    }
                }
            ],
            "responsive": true,
            "dom": '<"flex flex-col md:flex-row items-center justify-between"<"mb-4 md:mb-0"l><"flex items-center"f>>rt<"flex flex-col md:flex-row items-center justify-between"<"mb-4 md:mb-0"i><"flex"p>>',
            "language": {
                "search": "",
                "searchPlaceholder": "Search stations...",
                "lengthMenu": "Show _MENU_ entries",
                "info": "Showing _START_ to _END_ of _TOTAL_ stations",
                "infoEmpty": "Showing 0 to 0 of 0 stations",
                "infoFiltered": "(filtered from _MAX_ total stations)",
                "paginate": {
                    "first": "First",
                    "last": "Last",
                    "next": '<i class="fas fa-chevron-right"></i>',
                    "previous": '<i class="fas fa-chevron-left"></i>'
                },
                "emptyTable": "No stations available",
                "zeroRecords": "No matching stations found"
            },
            "lengthMenu": [10, 25, 50, 100],
            "pageLength": 10,
            "order": [[0, "asc"]], // Default sort by ID ascending
            "initComplete": function () {
                // Add custom styling to search box
                $('.dataTables_filter input').addClass('border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200');
                $('.dataTables_filter label').contents().filter(function () {
                    return this.nodeType === 3;
                }).remove();
                $('.dataTables_filter label').prepend('<i class="fas fa-search mr-2 text-gray-400"></i>');

                // Add custom styling to length menu
                $('.dataTables_length select').addClass('border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-200');
            }
        });
    });

    // Delete station
    function deleteStation(id) {
        if (confirm('Are you sure you want to delete this station?')) {
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
                    // Refresh the DataTable
                    $('#stations-table').DataTable().ajax.reload();
                    // Show success message
                    showAlert('Station deleted successfully!', 'success');
                })
                .catch(error => {
                    console.error('Error:', error);
                    showAlert('Failed to delete station.', 'error');
                });
        }
    }

    // Edit station - populate form
    function editStation(id) {
        fetch(`marker-interface.php?id=${id}`)
            .then(response => response.json())
            .then(data => {
                if (data && data.STATION.length > 0) {
                    const station = data.STATION[0];

                    // Populate basic fields
                    document.getElementById('edit-id').value = station.id;
                    document.getElementById('edit-title').value = station.title;
                    document.getElementById('edit-province').value = station.province;
                    document.getElementById('edit-latitude').value = station.latitude;
                    document.getElementById('edit-longitude').value = station.longitude;
                    document.getElementById('edit-address').value = station.address;
                    document.getElementById('edit-status').value = station.status;
                    document.getElementById('old-picture').value = station.picture || '';

                    // Show current image if exists
                    if (station.picture) {
                        document.getElementById('current-image').innerHTML = `
                                <span class="text-green-600">Current image:</span> ${station.picture}
                            `;
                    } else {
                        document.getElementById('current-image').innerHTML = `
                                <span class="text-gray-500">No image uploaded</span>
                            `;
                    }

                    // Reset all checkboxes
                    document.querySelectorAll('#edit-station-form input[type="checkbox"]').forEach(checkbox => {
                        checkbox.checked = false;
                    });

                    // Check product checkboxes
                    if (station.product) {
                        station.product.forEach(product => {
                            const checkbox = document.querySelector(`#edit-station-form input[name="product[]"][value="${product}"]`);
                            if (checkbox) checkbox.checked = true;
                        });
                    }

                    // Check other product checkboxes
                    if (station.other_product) {
                        station.other_product.forEach(product => {
                            const checkbox = document.querySelector(`#edit-station-form input[name="other_product[]"][value="${product}"]`);
                            if (checkbox) checkbox.checked = true;
                        });
                    }

                    // Check service checkboxes
                    if (station.description) {
                        station.description.forEach(service => {
                            const checkbox = document.querySelector(`#edit-station-form input[name="description[]"][value="${service}"]`);
                            if (checkbox) checkbox.checked = true;
                        });
                    }

                    // Check payment method checkboxes
                    if (station.service) {
                        station.service.forEach(payment => {
                            const checkbox = document.querySelector(`#edit-station-form input[name="service[]"][value="${payment}"]`);
                            if (checkbox) checkbox.checked = true;
                        });
                    }

                    // Initialize the modal
                    const editModal = new bootstrap.Modal(document.getElementById('editStationModal'));
                    editModal.show();
                } else {
                    console.error('No data found for the specified ID.');
                    showAlert('Station data not found.', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Failed to load station data.', 'error');
            });
    }

    // Show alert message
    function showAlert(message, type) {
        const alert = document.createElement('div');
        alert.className = `fixed top-4 right-4 z-50 px-6 py-4 rounded-lg shadow-lg text-white font-medium ${type === 'success' ? 'bg-green-500' : 'bg-red-500'}`;
        alert.innerHTML = `
                <div class="flex items-center">
                    <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} mr-2"></i>
                    <span>${message}</span>
                </div>
            `;
        document.body.appendChild(alert);

        // Remove alert after 3 seconds
        setTimeout(() => {
            alert.remove();
        }, 3000);
    }

    // Handle image preview in modal
    $(document).on('click', '.marker-image-link', function (e) {
        e.preventDefault();
        const imageUrl = $(this).data('image');
        $('#modal-preview-image').attr('src', imageUrl);
        const modal = new bootstrap.Modal(document.getElementById('imagePreviewModal'));
        modal.show();
    });

    // Populate province dropdowns
    function populateProvinceDropdowns() {
        const provinces = [
            "Banteay Meanchey", "Battambang", "Kampong Cham", "Kampong Chhnang", "Kampong Speu",
            "Kampong Thom", "Kampot", "Kandal", "Koh Kong", "Kratié", "Mondulkiri", "Oddar Meanchey", "Phnom Penh",
            "Pailin", "Preah Sihanouk", "Preah Vihear", "Pursat", "Ratanakiri", "Siem Reap", "Prey Veng",
            "Stung Treng", "Svay Rieng", "Takéo", "Kep", "Otdar Meanchey", "Pursat"
        ];

        const dropdowns = ['province', 'edit-province'];

        dropdowns.forEach(dropdownId => {
            const dropdown = document.getElementById(dropdownId);
            provinces.forEach(province => {
                const option = document.createElement('option');
                option.value = province;
                option.textContent = province;
                dropdown.appendChild(option);
            });
        });
    }

    // Initialize province dropdowns when page loads
    document.addEventListener('DOMContentLoaded', populateProvinceDropdowns);
</script>
</body>
</html>