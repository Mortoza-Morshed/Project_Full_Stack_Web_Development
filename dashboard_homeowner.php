<?php
session_start();
require_once 'includes/functions.php';
require_once 'config/db_connect.php';

if (!is_logged_in() || !has_role('homeowner')) {
    set_message("You must be logged in as a homeowner to access this page", "error");
    redirect("login.php");
}

$user_id = $_SESSION['user_id'];
$name = $_SESSION['name'];

$total_usage = [
    'electricity' => 0,
    'gas' => 0,
    'other' => 0
];

$current_month = date('Y-m');
$stmt = $conn->prepare("
    SELECT energy_type, SUM(consumption_value) as total 
    FROM energy_usage 
    WHERE user_id = ? AND DATE_FORMAT(date, '%Y-%m') = ?
    GROUP BY energy_type
");
$stmt->bind_param("is", $user_id, $current_month);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    if (isset($total_usage[$row['energy_type']])) {
        $total_usage[$row['energy_type']] = $row['total'];
    } else {
        $total_usage['other'] += $row['total'];
    }
}
$stmt->close();

$recommendations = [];
$stmt = $conn->prepare("
    SELECT recommendation_id, title, description, p.company_name 
    FROM recommendations r
    LEFT JOIN providers p ON r.provider_id = p.provider_id
    ORDER BY r.created_at DESC
    LIMIT 3
");
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $recommendations[] = $row;
}
$stmt->close();

$has_energy_data = false;
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM energy_usage WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$has_energy_data = ($row['count'] > 0);
$stmt->close();
?>

<?php include 'includes/header.php'; ?>

<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <h1 class="text-3xl font-bold mb-4">Welcome, <?php echo htmlspecialchars($name); ?>!</h1>
    <p class="text-gray-600 mb-1">Here's an overview of your energy consumption and recommendations.</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <div class="col-span-1 md:col-span-2">
        <div class="bg-white rounded-lg shadow-md p-6 h-full">
            <h2 class="text-2xl font-semibold mb-4">This Month's Energy Usage</h2>
            
            <?php if ($has_energy_data): ?>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="bg-blue-50 rounded-lg p-4 text-center">
                        <p class="text-gray-500 mb-1">Electricity</p>
                        <p class="text-3xl font-bold text-blue-600"><?php echo number_format($total_usage['electricity'], 1); ?> <span class="text-sm">kWh</span></p>
                    </div>
                    <div class="bg-orange-50 rounded-lg p-4 text-center">
                        <p class="text-gray-500 mb-1">Gas</p>
                        <p class="text-3xl font-bold text-orange-600"><?php echo number_format($total_usage['gas'], 1); ?> <span class="text-sm">therms</span></p>
                    </div>
                    <div class="bg-green-50 rounded-lg p-4 text-center">
                        <p class="text-gray-500 mb-1">Other</p>
                        <p class="text-3xl font-bold text-green-600"><?php echo number_format($total_usage['other'], 1); ?> <span class="text-sm">units</span></p>
                    </div>
                </div>
                <div class="mt-6 text-center">
                    <a href="energy_reports.php" class="inline-block bg-secondary text-white py-2 px-4 rounded-lg hover:bg-secondary/90 transition">View Detailed Reports</a>
                </div>
            <?php else: ?>
                <div class="bg-gray-50 rounded-lg p-8 text-center">
                    <p class="text-gray-600 mb-4">You haven't added any energy data yet.</p>
                    <a href="energy_input.php" class="inline-block bg-primary text-white py-2 px-4 rounded-lg hover:bg-primary/90 transition">
                        <i class="fas fa-plus mr-2"></i> Add Energy Data
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-2xl font-semibold mb-4">Quick Actions</h2>
        <div class="space-y-3">
            <a href="energy_input.php" class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                <div class="bg-primary/10 p-2 rounded-full mr-3">
                    <i class="fas fa-plus text-primary"></i>
                </div>
                <div>
                    <p class="font-medium">Add Energy Data</p>
                    <p class="text-sm text-gray-500">Track your latest consumption</p>
                </div>
            </a>
            
            <a href="energy_reports.php" class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                <div class="bg-secondary/10 p-2 rounded-full mr-3">
                    <i class="fas fa-chart-line text-secondary"></i>
                </div>
                <div>
                    <p class="font-medium">View Reports</p>
                    <p class="text-sm text-gray-500">Analyze your usage trends</p>
                </div>
            </a>
            
            <a href="provider_search.php" class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                <div class="bg-yellow-500/10 p-2 rounded-full mr-3">
                    <i class="fas fa-search text-yellow-500"></i>
                </div>
                <div>
                    <p class="font-medium">Find Providers</p>
                    <p class="text-sm text-gray-500">Connect with energy services</p>
                </div>
            </a>
            
            <a href="recommendations.php" class="flex items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                <div class="bg-green-600/10 p-2 rounded-full mr-3">
                    <i class="fas fa-lightbulb text-green-600"></i>
                </div>
                <div>
                    <p class="font-medium">Get Recommendations</p>
                    <p class="text-sm text-gray-500">Tips to reduce consumption</p>
                </div>
            </a>
        </div>
    </div>
</div>

<div class="bg-white rounded-lg shadow-md p-6 mt-6">
    <h2 class="text-2xl font-semibold mb-4">Recent Recommendations</h2>
    
    <?php if (!empty($recommendations)): ?>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <?php foreach ($recommendations as $recommendation): ?>
                <div class="bg-gray-50 p-4 rounded-lg">
                    <h3 class="font-semibold text-lg mb-2"><?php echo htmlspecialchars($recommendation['title']); ?></h3>
                    <p class="text-gray-600 mb-3 text-sm"><?php echo htmlspecialchars($recommendation['description']); ?></p>
                    <?php if (!empty($recommendation['company_name'])): ?>
                        <p class="text-xs text-gray-500">Suggested by: <?php echo htmlspecialchars($recommendation['company_name']); ?></p>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="mt-4 text-center">
            <a href="recommendations.php" class="text-primary hover:underline">View All Recommendations</a>
        </div>
    <?php else: ?>
        <p class="text-gray-600">No recommendations available yet.</p>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?> 