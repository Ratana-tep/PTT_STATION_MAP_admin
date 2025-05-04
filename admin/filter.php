<?php
// Configuration
$githubUrl = 'https://raw.githubusercontent.com/Ratana-tep/PTT_STATION_MAP/master/data/markers.json';
$cacheFile = 'data/markers_cache.json';
$cacheTime = 3600; // 1 hour cache

// Function to fetch data with caching
function fetchDataWithCache($url, $cacheFile, $cacheTime) {
    // Use cache if it's fresh and valid
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTime)) {
        $cachedData = file_get_contents($cacheFile);
        try {
            json_decode($cachedData, true, 512, JSON_THROW_ON_ERROR);
            return $cachedData;
        } catch (JsonException $e) {
            error_log("Invalid JSON in cache: " . $e->getMessage());
            // Cache is invalid, proceed to fetch fresh data
        }
    }

    // Try to get fresh data with cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'PHP');
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HEADER, false);
    $data = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    // Check for cURL errors or non-200 status
    if ($data === false || $httpCode !== 200) {
        if ($data === false) {
            error_log("cURL error: $error");
        } else {
            error_log("HTTP $httpCode received from $url");
        }
        // Fallback to cache if available
        if (file_exists($cacheFile)) {
            error_log("Using stale cache due to fetch failure");
            return file_get_contents($cacheFile);
        }
        die("Failed to fetch data from GitHub (HTTP $httpCode) and no cache available");
    }

    // Clean data (remove BOM, ensure UTF-8)
    $data = preg_replace('/^\xEF\xBB\xBF/', '', $data); // Remove BOM
    $data = mb_convert_encoding($data, 'UTF-8', 'UTF-8'); // Ensure UTF-8

    // Verify JSON validity before caching
    try {
        json_decode($data, true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException $e) {
        error_log("Invalid JSON fetched from $url: " . $e->getMessage());
        // Fallback to cache if available
        if (file_exists($cacheFile)) {
            error_log("Using stale cache due to invalid JSON");
            return file_get_contents($cacheFile);
        }
        die("Invalid JSON from GitHub: " . $e->getMessage());
    }

    // Save to cache
    if (!file_exists(dirname($cacheFile))) {
        mkdir(dirname($cacheFile), 0755, true);
    }
    file_put_contents($cacheFile, $data);
    return $data;
}

// Load the JSON data
$data = fetchDataWithCache($githubUrl, $cacheFile, $cacheTime);
try {
    $jsonData = json_decode($data, true, 512, JSON_THROW_ON_ERROR);
} catch (JsonException $e) {
    error_log("JSON decode error: " . $e->getMessage());
    die("JSON error: " . $e->getMessage());
}

// Validate JSON data
if (!isset($jsonData['STATION'])) {
    die("Invalid JSON data: Missing 'STATION' key");
}

// Fetch stations
$filteredStations = $jsonData['STATION'];

// Extract unique filterable fields safely
$statuses = array_unique(array_filter(array_column($jsonData['STATION'], 'status')));
$provinces = array_unique(array_filter(array_column($jsonData['STATION'], 'province')));
$services = array_unique(array_merge(...array_map(function($s) { return $s['service'] ?? []; }, $jsonData['STATION'])));
$descriptions = array_unique(array_merge(...array_map(function($s) { return $s['description'] ?? []; }, $jsonData['STATION'])));
$otherProducts = array_unique(array_merge(...array_map(function($s) { return $s['other_product'] ?? []; }, $jsonData['STATION'])));

// Apply filters if selected
$activeFilters = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $statusFilter = $_POST['status'] ?? '';
    $provinceFilter = $_POST['province'] ?? '';
    $serviceFilter = $_POST['service'] ?? '';
    $descriptionFilter = $_POST['description'] ?? '';
    $otherProductFilter = $_POST['other_product'] ?? '';

    if (!empty($statusFilter)) $activeFilters['status'] = $statusFilter;
    if (!empty($provinceFilter)) $activeFilters['province'] = $provinceFilter;
    if (!empty($serviceFilter)) $activeFilters['service'] = $serviceFilter;
    if (!empty($descriptionFilter)) $activeFilters['description'] = $descriptionFilter;
    if (!empty($otherProductFilter)) $activeFilters['other_product'] = $otherProductFilter;

    $filteredStations = array_filter($jsonData['STATION'], function($station) use ($statusFilter, $provinceFilter, $serviceFilter, $descriptionFilter, $otherProductFilter) {
        $statusMatch = empty($statusFilter) || (isset($station['status']) && $station['status'] === $statusFilter);
        $provinceMatch = empty($provinceFilter) || (isset($station['province']) && $station['province'] === $provinceFilter);
        $serviceMatch = empty($serviceFilter) || (!empty($station['service']) && in_array($serviceFilter, $station['service']));
        $descriptionMatch = empty($descriptionFilter) || (!empty($station['description']) && in_array($descriptionFilter, $station['description']));
        $otherProductMatch = empty($otherProductFilter) || (!empty($station['other_product']) && in_array($otherProductFilter, $station['other_product']));
        return $statusMatch && $provinceMatch && $serviceMatch && $descriptionMatch && $otherProductMatch;
    });
}

// Build export URL with current filters
$exportUrl = '?export_csv=1';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST as $key => $value) {
        if (!empty($value)) {
            $exportUrl .= "&$key=" . urlencode($value);
        }
    }
}

$grandTotal = count($filteredStations);

// Handle CSV export
if (isset($_GET['export_csv']) && $_GET['export_csv'] == '1') {
    // Apply the same filters as the current view
    $exportStations = $jsonData['STATION'];

    if (!empty($_GET['status']) || !empty($_GET['province']) || !empty($_GET['service']) ||
        !empty($_GET['description']) || !empty($_GET['other_product'])) {
        $exportStations = array_filter($jsonData['STATION'], function($station) {
            $statusMatch = empty($_GET['status']) || (isset($station['status']) && $station['status'] === $_GET['status']);
            $provinceMatch = empty($_GET['province']) || (isset($station['province']) && $station['province'] === $_GET['province']);
            $serviceMatch = empty($_GET['service']) || (!empty($station['service']) && in_array($_GET['service'], $station['service']));
            $descriptionMatch = empty($_GET['description']) || (!empty($station['description']) && in_array($_GET['description'], $station['description']));
            $otherProductMatch = empty($_GET['other_product']) || (!empty($station['other_product']) && in_array($_GET['other_product'], $station['other_product']));
            return $statusMatch && $provinceMatch && $serviceMatch && $descriptionMatch && $otherProductMatch;
        });
    }

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="stations.csv"');
    $output = fopen('php://output', 'w');

    // Write the column headers
    fputcsv($output, ['ID', 'Title', 'Status', 'Province', 'Address']);

    // Write the data
    foreach ($exportStations as $station) {
        fputcsv($output, [
            $station['id'],
            $station['title'],
            $station['status'],
            $station['province'],
            $station['address']
        ]);
    }

    fclose($output);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Station Statistics</title>
	<script src="https://cdn.tailwindcss.com"></script>
	<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-50">
<div class="container mx-auto px-4 py-8">
	<div class="bg-white rounded-xl shadow-md overflow-hidden">
		<!-- Header Section -->
		<div class="bg-blue-600 px-6 py-4">
			<h1 class="text-2xl font-bold text-white">Station Statistics</h1>
			<div class="flex items-center mt-2">
				<span class="text-white mr-2">Total Stations:</span>
				<span class="bg-white text-blue-600 font-bold px-3 py-1 rounded-full"><?php echo $grandTotal; ?></span>
			</div>
		</div>

		<!-- Filters Section -->
		<div class="p-6 border-b">
			<form method="POST" class="space-y-4">
				<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
					<div>
						<label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
						<select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
							<option value="">All Statuses</option>
                            <?php foreach ($statuses as $status): ?>
								<option value="<?php echo $status; ?>" <?php echo isset($activeFilters['status']) && $activeFilters['status'] === $status ? 'selected' : ''; ?>>
                                    <?php echo $status; ?>
								</option>
                            <?php endforeach; ?>
						</select>
					</div>

					<div>
						<label class="block text-sm font-medium text-gray-700 mb-1">Province</label>
						<select name="province" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
							<option value="">All Provinces</option>
                            <?php foreach ($provinces as $province): ?>
								<option value="<?php echo $province; ?>" <?php echo isset($activeFilters['province']) && $activeFilters['province'] === $province ? 'selected' : ''; ?>>
                                    <?php echo $province; ?>
								</option>
                            <?php endforeach; ?>
						</select>
					</div>

					<div>
						<label class="block text-sm font-medium text-gray-700 mb-1">Service</label>
						<select name="service" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
							<option value="">All Services</option>
                            <?php foreach ($services as $service): ?>
								<option value="<?php echo $service; ?>" <?php echo isset($activeFilters['service']) && $activeFilters['service'] === $service ? 'selected' : ''; ?>>
                                    <?php echo $service; ?>
								</option>
                            <?php endforeach; ?>
						</select>
					</div>

					<div>
						<label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
						<select name="description" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
							<option value="">All Descriptions</option>
                            <?php foreach ($descriptions as $description): ?>
								<option value="<?php echo $description; ?>" <?php echo isset($activeFilters['description']) && $activeFilters['description'] === $description ? 'selected' : ''; ?>>
                                    <?php echo $description; ?>
								</option>
                            <?php endforeach; ?>
						</select>
					</div>

					<div>
						<label class="block text-sm font-medium text-gray-700 mb-1">Other Products</label>
						<select name="other_product" class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
							<option value="">All Products</option>
                            <?php foreach ($otherProducts as $otherProduct): ?>
								<option value="<?php echo $otherProduct; ?>" <?php echo isset($activeFilters['other_product']) && $activeFilters['other_product'] === $otherProduct ? 'selected' : ''; ?>>
                                    <?php echo $otherProduct; ?>
								</option>
                            <?php endforeach; ?>
						</select>
					</div>
				</div>

				<div class="flex justify-between items-center">
					<div>
                        <?php if (!empty($activeFilters)): ?>
							<span class="text-sm text-gray-600">
                                    Active filters:
                                    <?php foreach ($activeFilters as $key => $value): ?>
										<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 mr-1">
                                            <?php echo "$key: $value"; ?>
                                            <form method="POST" class="inline ml-1">
                                                <input type="hidden" name="<?php echo $key; ?>" value="">
                                                <button type="submit" class="text-blue-500 hover:text-blue-700">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                        </span>
                                    <?php endforeach; ?>
                                </span>
                        <?php endif; ?>
					</div>

					<div class="flex space-x-2">
						<button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
							<i class="fas fa-filter mr-2"></i> Apply Filters
						</button>

						<a href="<?php echo $exportUrl; ?>" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
							<i class="fas fa-file-export mr-2"></i> Export CSV
						</a>
					</div>
				</div>
			</form>
		</div>

		<!-- Results Table -->
		<div class="overflow-x-auto">
			<table class="min-w-full divide-y divide-gray-200">
				<thead class="bg-gray-50">
				<tr>
					<th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
					<th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
					<th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
					<th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Province</th>
					<th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Address</th>
					<th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Details</th>
				</tr>
				</thead>
				<tbody class="bg-white divide-y divide-gray-200">
                <?php foreach ($filteredStations as $station): ?>
					<tr class="hover:bg-gray-50">
						<td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo $station['id']; ?></td>
						<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $station['title']; ?></td>
						<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                    <?php echo $station['status'] === '24h' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                    <?php echo $station['status']; ?>
                                </span>
						</td>
						<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo $station['province']; ?></td>
						<td class="px-6 py-4 text-sm text-gray-500"><?php echo $station['address']; ?></td>
						<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
							<button class="text-blue-600 hover:text-blue-900" onclick="showDetails(<?php echo htmlspecialchars(json_encode($station), ENT_QUOTES, 'UTF-8'); ?>)">
								<i class="fas fa-eye"></i> View
							</button>
						</td>
					</tr>
                <?php endforeach; ?>
				</tbody>
			</table>
		</div>
	</div>
</div>

<!-- Station Details Modal -->
<div id="detailsModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
	<div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
		<div class="fixed inset-0 transition-opacity" aria-hidden="true">
			<div class="absolute inset-0 bg-gray-500 opacity-75"></div>
		</div>

		<span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

		<div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
			<div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
				<div class="sm:flex sm:items-start">
					<div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
						<h3 id="modalTitle" class="text-lg leading-6 font-medium text-gray-900"></h3>
						<div class="mt-2 grid grid-cols-1 gap-y-2 gap-x-4 sm:grid-cols-2">
							<div>
								<p class="text-sm text-gray-500">ID:</p>
								<p id="modalId" class="text-sm font-medium text-gray-900"></p>
							</div>
							<div>
								<p class="text-sm text-gray-500">Status:</p>
								<p id="modalStatus" class="text-sm font-medium text-gray-900"></p>
							</div>
							<div>
								<p class="text-sm text-gray-500">Province:</p>
								<p id="modalProvince" class="text-sm font-medium text-gray-900"></p>
							</div>
							<div>
								<p class="text-sm text-gray-500">Address:</p>
								<p id="modalAddress" class="text-sm font-medium text-gray-900"></p>
							</div>
							<div class="sm:col-span-2">
								<p class="text-sm text-gray-500">Services:</p>
								<p id="modalServices" class="text-sm font-medium text-gray-900"></p>
							</div>
							<div class="sm:col-span-2">
								<p class="text-sm text-gray-500">Products:</p>
								<p id="modalProducts" class="text-sm font-medium text-gray-900"></p>
							</div>
							<div class="sm:col-span-2">
								<p class="text-sm text-gray-500">Other Products:</p>
								<p id="modalOtherProducts" class="text-sm font-medium text-gray-900"></p>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
				<button type="button" onclick="closeModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
					Close
				</button>
			</div>
		</div>
	</div>
</div>

<script>
    function showDetails(station) {
        document.getElementById('modalTitle').textContent = station.title;
        document.getElementById('modalId').textContent = station.id;
        document.getElementById('modalStatus').textContent = station.status;
        document.getElementById('modalProvince').textContent = station.province;
        document.getElementById('modalAddress').textContent = station.address;
        document.getElementById('modalServices').textContent = station.service ? station.service.join(', ') : 'N/A';
        document.getElementById('modalProducts').textContent = station.product ? station.product.join(', ') : 'N/A';
        document.getElementById('modalOtherProducts').textContent = station.other_product ? station.other_product.join(', ') : 'N/A';

        document.getElementById('detailsModal').classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('detailsModal').classList.add('hidden');
    }

    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('detailsModal');
        if (event.target === modal) {
            closeModal();
        }
    }
</script>
</body>
</html>