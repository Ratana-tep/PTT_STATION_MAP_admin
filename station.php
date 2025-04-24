<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Interface</title>
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
</head>
</head>

<body>
    <!-- Modal -->
    <div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Add Marker</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="marker-form" method="POST" action="test.php" enctype="multipart/form-data">
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
                                <input class="form-check-input" type="checkbox" id="ulg95" name="product[]"
                                    value="ULG 95">
                                <label class="form-check-label" for="ulg95">ULG 95</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="ulr91" name="product[]"
                                    value="ULR 91">
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
                                <input class="form-check-input" type="checkbox" id="ev" name="other_product[]"
                                    value="EV">
                                <label class="form-check-label" for="ev">EV</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="onion" name="other_product[]"
                                    value="Onion">
                                <label class="form-check-label" for="onion">Onion</label>
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
                                <input class="form-check-input" type="checkbox" id="amazon" name="description[]"
                                    value="Amazon">
                                <label class="form-check-label" for="amazon">Amazon</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="7eleven" name="description[]"
                                    value="7-Eleven">
                                <label class="form-check-label" for="7eleven">7-Eleven</label>
                            </div>

                            <!-- Add more checkboxes as needed -->
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Payment:</label><br>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="fleetcard" name="service[]"
                                    value="Fleet card">
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
                    <form id="edit-marker-form" method="POST" action="test.php"
                        enctype="multipart/form-data">
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
                                <input class="form-check-input" type="checkbox" id="edit-ulg95" name="product[]"
                                    value="ULG 95">
                                <label class="form-check-label" for="edit-ulg95">ULG 95</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit-ulr91" name="product[]"
                                    value="ULR 91">
                                <label class="form-check-label" for="edit-ulr91">ULR 91</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit-hsd" name="product[]"
                                    value="HSD">
                                <label class="form-check-label" for="edit-hsd">HSD</label>
                            </div>
                            <!-- Add more checkboxes as needed -->
                        </div>

                        <!-- other product  -->
                        <div class="mb-3">
                            <label class="form-label">Other Product:</label><br>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit-ev" name="other_product[]"
                                    value="EV">
                                <label class="form-check-label" for="edit-ev">EV</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit-onion" name="other_product[]"
                                    value="Onion">
                                <label class="form-check-label" for="edit-onion">Onion</label>
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
                                <input class="form-check-input" type="checkbox" id="edit-amazon" name="description[]"
                                    value="Amazon">
                                <label class="form-check-label" for="edit-amazon">Amazon</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit-7eleven" name="description[]"
                                    value="7-Eleven">
                                <label class="form-check-label" for="edit-7eleven">7-Eleven</label>
                            </div>
                            <!-- Add more checkboxes as needed -->
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Payment:</label><br>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit-fleetcard" name="service[]"
                                    value="Fleet card">
                                <label class="form-check-label" for="edit-fleetcard">Fleet Card</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit-aba" name="service[]"
                                    value="KHQR">
                                <label class="form-check-label" for="edit-aba">KHQR</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="edit-test" name="service[]"
                                    value="Cash">
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

    <script>
        // Function to update the selected file name in the form
        document.getElementById('edit-picture').addEventListener('change', function () {
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
        $(document).ready(function () {
            $('#marker-table').DataTable({
                "order": [], // Disables initial sorting
                "processing": true, // Show processing indicator
                "serverSide": false, // Enable server-side processing
                "ajax": {
                    "url": "test.php",
                    "dataSrc": "STATION" // Specify the property name containing the data
                },
                "columns": [
                    { "data": "id" },
                    {
                        "data": null,
                        "render": function (data, type, row) {
                            return `<button class="btn btn-info btn-sm" onclick="viewLocation('${row.latitude}', '${row.longitude}')">View Location</button>`;
                        }
                    },
                    { "data": "title" },
                    { "data": "product" },
                    { "data": "other_product" },
                    { "data": "description" },
                    { "data": "service" },
                    { "data": "province" },
                    {
                        "data": null,
                        "render": function (data, type, row) {
                            return `<button class="btn btn-primary btn-sm" onclick="viewAddress('${row.address}')">View</button>`;
                        }
                    },
                    {
                        "data": "picture",
                        "render": function (data) {
                            if (data) {
                                return `<a href="#" class="marker-image-link" data-bs-toggle="modal" data-bs-target="#imageModal" data-image="pictures/${data}"><img src="pictures/${data}" alt="Marker Image" style="max-width:40px; border-radius: 50%;"></a>`;
                            } else {
                                return 'No Image';
                            }
                        }
                    },


                    { "data": "status" },
                    // { "data": "promotion" },
                    {
                        "data": null,
                        "render": function (data, type, row) {
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
                fetch(`test.php?id=${id}`, {
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
            fetch(`test.php?id=${id}`)
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
    <script>
        $(document).on('click', '.marker-image-link', function (e) {
            e.preventDefault(); // Prevent the default behavior of the link
            var imageUrl = $(this).data('image');
            $('#modalImage').attr('src', imageUrl);
            $('#imageModal').modal('show');
        });
    </script>




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


</body>

</html>