<?php
session_start();
require_once 'includes/functions.php';
require_once 'config/db_connect.php';

// is homeowner
if (!is_logged_in() || !has_role('homeowner')) {
    set_message("You must be logged in as a homeowner to access this page", "error");
    redirect("login.php");
}

$user_id = $_SESSION['user_id'];
$search_term = '';
$category_filter = '';
$recommendations = [];

$categories = [];
$stmt = $conn->prepare("
    SELECT DISTINCT SUBSTRING_INDEX(SUBSTRING_INDEX(title, ':', 1), ' ', -1) as category
    FROM recommendations
    WHERE title LIKE '%:%'
    ORDER BY category
");
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $category = trim($row['category']);
    if (!empty($category)) {
        $categories[] = $category;
    }
}
$stmt->close();

// default
if (empty($categories)) {
    $categories = ['Lighting', 'Heating', 'Cooling', 'Appliances', 'Insulation', 'Solar', 'Water'];
}


if (isset($_GET['search']) || isset($_GET['category'])) {
    $search_term = sanitize($_GET['search'] ?? '');
    $category_filter = sanitize($_GET['category'] ?? '');
    
    // SQL
    $sql = "
        SELECT r.recommendation_id, r.title, r.description, p.company_name
        FROM recommendations r
        LEFT JOIN providers p ON r.provider_id = p.provider_id
        WHERE 1=1
    ";
    
    $params = [];
    $types = "";
    
    if (!empty($search_term)) {
        $sql .= " AND (r.title LIKE ? OR r.description LIKE ?)";
        $search_param = "%$search_term%";
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= "ss";
    }
    
    // Add category filter
    if (!empty($category_filter)) {
        $sql .= " AND r.title LIKE ?";
        $params[] = "$category_filter%";
        $types .= "s";
    }
    
    $sql .= " ORDER BY r.created_at DESC";
    

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $recommendations[] = $row;
    }
    $stmt->close();
} else {
    // default
    $stmt = $conn->prepare("
        SELECT r.recommendation_id, r.title, r.description, p.company_name
        FROM recommendations r
        LEFT JOIN providers p ON r.provider_id = p.provider_id
        ORDER BY r.created_at DESC
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $recommendations[] = $row;
    }
    $stmt->close();
}

// Group recommendations by category
$grouped_recommendations = [];
foreach ($recommendations as $rec) {
    $title_parts = explode(':', $rec['title'], 2);
    $category = trim($title_parts[0]);
    
    if (!isset($grouped_recommendations[$category])) {
        $grouped_recommendations[$category] = [];
    }
    
    $grouped_recommendations[$category][] = $rec;
}
?>

<?php include 'includes/header.php'; ?>

<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <h1 class="text-3xl font-bold mb-6">Energy-Saving Recommendations</h1>
    
    <form action="recommendations.php" method="get" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="md:col-span-2">
                <label for="search" class="block text-gray-700 font-medium mb-2">Search</label>
                <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search_term); ?>" placeholder="Search for recommendations..." class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
            </div>
            
            <div>
                <label for="category" class="block text-gray-700 font-medium mb-2">Category</label>
                <select id="category" name="category" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $cat == $category_filter ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <div class="flex justify-between">
            <button type="submit" class="bg-primary text-white font-semibold py-2 px-6 rounded-lg hover:bg-primary/90 transition">
                <i class="fas fa-search mr-2"></i> Search
            </button>
            
            <?php if (!empty($search_term) || !empty($category_filter)): ?>
                <a href="recommendations.php" class="text-gray-600 hover:text-gray-800 py-2">
                    <i class="fas fa-times mr-1"></i> Clear Filters
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<div class="mb-6">
    <?php if (!empty($recommendations)): ?>
        <div class="space-y-6">
            <?php 
            
            foreach ($grouped_recommendations as $category => $recs): 
            ?>
                <div class="bg-white rounded-lg shadow-md overflow-hidden">
                    <div class="bg-primary/10 px-6 py-3 border-b">
                        <h2 class="text-xl font-semibold text-primary"><?php echo htmlspecialchars($category); ?></h2>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <?php foreach ($recs as $rec): ?>
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <?php 
                                    $title_parts = explode(':', $rec['title'], 2);
                                    $title = isset($title_parts[1]) ? trim($title_parts[1]) : $rec['title'];
                                    ?>
                                    <h3 class="font-semibold text-lg mb-2"><?php echo htmlspecialchars($title); ?></h3>
                                    <p class="text-gray-600 mb-3"><?php echo htmlspecialchars($rec['description']); ?></p>
                                    <?php if (!empty($rec['company_name'])): ?>
                                        <p class="text-xs text-gray-500">Suggested by: <?php echo htmlspecialchars($rec['company_name']); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="bg-gray-50 rounded-lg p-8 text-center">
            <p class="text-gray-600 mb-2">No recommendations found matching your criteria.</p>
            <?php if (!empty($search_term) || !empty($category_filter)): ?>
                <p class="text-gray-500">Try adjusting your search filters or <a href="recommendations.php" class="text-primary hover:underline">view all recommendations</a>.</p>
            <?php else: ?>
                <p class="text-gray-500">Check back later for energy-saving tips and recommendations.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<div class="bg-green-50 rounded-lg p-6 mb-6">
    <h2 class="text-xl font-semibold text-green-800 mb-3">Why Save Energy?</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="flex items-start">
            <div class="bg-green-100 rounded-full p-2 mr-3 mt-1">
                <i class="fas fa-dollar-sign text-green-600"></i>
            </div>
            <div>
                <h3 class="font-medium text-green-800 mb-1">Lower Utility Bills</h3>
                <p class="text-sm text-green-700">Reduce your monthly energy costs by implementing these recommendations</p>
            </div>
        </div>
        <div class="flex items-start">
            <div class="bg-green-100 rounded-full p-2 mr-3 mt-1">
                <i class="fas fa-globe-americas text-green-600"></i>
            </div>
            <div>
                <h3 class="font-medium text-green-800 mb-1">Environmental Impact</h3>
                <p class="text-sm text-green-700">Reduce your carbon footprint and contribute to a healthier planet</p>
            </div>
        </div>
        <div class="flex items-start">
            <div class="bg-green-100 rounded-full p-2 mr-3 mt-1">
                <i class="fas fa-home text-green-600"></i>
            </div>
            <div>
                <h3 class="font-medium text-green-800 mb-1">Increased Home Value</h3>
                <p class="text-sm text-green-700">Energy-efficient homes often have higher market values</p>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
