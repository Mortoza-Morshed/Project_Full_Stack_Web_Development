<?php
session_start();
require_once 'includes/functions.php';
require_once 'config/db_connect.php';

if (!is_logged_in() || !has_role('homeowner')) {
    set_message("You must be logged in as a homeowner to access this page", "error");
    redirect("login.php");
}

$providers = [];
$search_performed = false;
$location_filter = '';
$service_filter = '';
$sustainability_filter = 0;

// search
if (isset($_GET['search']) || isset($_GET['location']) || isset($_GET['service']) || isset($_GET['sustainability'])) {
    $search_performed = true;
    
    $sql = "
        SELECT p.provider_id, p.company_name, p.services, p.location, p.sustainability_practices, u.email
        FROM providers p
        JOIN users u ON p.user_id = u.user_id
        WHERE u.status = 'approved'
    ";
    
    $params = [];
    $types = "";
    
    // location filter
    if (!empty($_GET['location'])) {
        $location_filter = sanitize($_GET['location']);
        $sql .= " AND p.location LIKE ?";
        $params[] = "%$location_filter%";
        $types .= "s";
    }
    
    // service filter
    if (!empty($_GET['service'])) {
        $service_filter = sanitize($_GET['service']);
        $sql .= " AND p.services LIKE ?";
        $params[] = "%$service_filter%";
        $types .= "s";
    }
    
    // sustainability filter , although this is farzi
    if (!empty($_GET['sustainability']) && intval($_GET['sustainability']) > 0) {
        $sustainability_filter = intval($_GET['sustainability']);
        // ye just for test purpose hai
        $min_length = $sustainability_filter * 20;
        $sql .= " AND LENGTH(p.sustainability_practices) > ?";
        $params[] = $min_length;
        $types .= "i";
    }
    
    $sql .= " ORDER BY p.company_name";
    
    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $providers[] = $row;
    }
    $stmt->close();
}

// service types
$service_types = [];
$stmt = $conn->prepare("
    SELECT DISTINCT SUBSTRING_INDEX(SUBSTRING_INDEX(services, ',', numbers.n), ',', -1) as service
    FROM providers, (
        SELECT 1 as n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5
    ) as numbers
    WHERE CHAR_LENGTH(services) - CHAR_LENGTH(REPLACE(services, ',', '')) >= numbers.n - 1
    ORDER BY service
");
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $service = trim($row['service']);
    if (!empty($service)) {
        $service_types[] = $service;
    }
}
$stmt->close();

// locations for dropdown
$locations = [];
$stmt = $conn->prepare("SELECT DISTINCT location FROM providers ORDER BY location");
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $locations[] = $row['location'];
}
$stmt->close();
?>

<?php include 'includes/header.php'; ?>

<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <h1 class="text-3xl font-bold mb-6">Find Energy Service Providers</h1>
    
    <form action="provider_search.php" method="get" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label for="location" class="block text-gray-700 font-medium mb-2">Location</label>
                <select id="location" name="location" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">All Locations</option>
                    <?php foreach ($locations as $loc): ?>
                        <option value="<?php echo htmlspecialchars($loc); ?>" <?php echo $loc == $location_filter ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($loc); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label for="service" class="block text-gray-700 font-medium mb-2">Service</label>
                <select id="service" name="service" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">All Services</option>
                    <?php foreach ($service_types as $service): ?>
                        <option value="<?php echo htmlspecialchars($service); ?>" <?php echo $service == $service_filter ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($service); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label for="sustainability" class="block text-gray-700 font-medium mb-2">Sustainability Rating</label>
                <select id="sustainability" name="sustainability" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="0" <?php echo $sustainability_filter == 0 ? 'selected' : ''; ?>>Any</option>
                    <option value="1" <?php echo $sustainability_filter == 1 ? 'selected' : ''; ?>>★</option>
                    <option value="2" <?php echo $sustainability_filter == 2 ? 'selected' : ''; ?>>★★</option>
                    <option value="3" <?php echo $sustainability_filter == 3 ? 'selected' : ''; ?>>★★★</option>
                    <option value="4" <?php echo $sustainability_filter == 4 ? 'selected' : ''; ?>>★★★★</option>
                    <option value="5" <?php echo $sustainability_filter == 5 ? 'selected' : ''; ?>>★★★★★</option>
                </select>
            </div>
            
            <div class="flex items-end">
                <button type="submit" class="bg-primary text-white font-semibold py-2 px-6 rounded-lg hover:bg-primary/90 transition w-full">Search</button>
            </div>
        </div>
    </form>
</div>

<div class="mb-6">
    <?php if ($search_performed): ?>
        <h2 class="text-xl font-semibold mb-4"><?php echo count($providers); ?> Provider<?php echo count($providers) != 1 ? 's' : ''; ?> Found</h2>
        
        <?php if (!empty($providers)): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($providers as $provider): ?>
                    <div class="bg-white rounded-lg shadow-md overflow-hidden">
                        <div class="bg-green-50 px-4 py-3 border-b">
                            <h3 class="text-lg font-semibold"><?php echo htmlspecialchars($provider['company_name']); ?></h3>
                        </div>
                        <div class="p-4 space-y-2">
                            <div class="flex">
                                <i class="fas fa-map-marker-alt text-gray-500 w-6"></i>
                                <p><?php echo htmlspecialchars($provider['location']); ?></p>
                            </div>
                            <div class="flex">
                                <i class="fas fa-tools text-gray-500 w-6"></i>
                                <p class="flex-1"><?php echo htmlspecialchars($provider['services']); ?></p>
                            </div>
                            <div class="flex">
                                <i class="fas fa-leaf text-gray-500 w-6"></i>
                                <p class="flex-1 text-sm"><?php echo htmlspecialchars($provider['sustainability_practices']); ?></p>
                            </div>
                            <div class="pt-3 border-t mt-2">
                                <a href="mailto:<?php echo htmlspecialchars($provider['email']); ?>" class="inline-flex items-center text-primary hover:underline">
                                    <i class="fas fa-envelope mr-1"></i> Contact Provider
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="bg-gray-50 rounded-lg p-8 text-center">
                <p class="text-gray-600 mb-2">No providers found matching your criteria.</p>
                <p class="text-gray-500">Try adjusting your search filters.</p>
            </div>
        <?php endif; ?>
    <?php else: ?>
        <div class="bg-blue-50 rounded-lg p-6 text-center">
            <h2 class="text-xl font-semibold text-blue-800 mb-2">Find Sustainable Energy Service Providers</h2>
            <p class="text-blue-700 mb-4">Use the search filters above to find providers in your area that offer the services you need.</p>
            <p class="text-blue-600 text-sm">All providers are verified and committed to sustainable energy practices.</p>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
