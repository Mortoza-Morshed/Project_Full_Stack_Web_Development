<?php
session_start();
require_once 'includes/functions.php';
require_once 'config/db_connect.php';

// is provider
if (!is_logged_in() || !has_role('provider')) {
    set_message("You must be logged in as a provider to access this page", "error");
    redirect("login.php");
}

$user_id = $_SESSION['user_id'];
$name = $_SESSION['name'];

$provider_info = [];
$stmt = $conn->prepare("
    SELECT p.provider_id, p.company_name, p.services, p.location, p.sustainability_practices, u.email 
    FROM providers p
    JOIN users u ON p.user_id = u.user_id
    WHERE p.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$provider_info = $result->fetch_assoc();
$provider_id = $provider_info['provider_id'];
$stmt->close();

// provider's recommendations
$recommendations = [];
$stmt = $conn->prepare("
    SELECT recommendation_id, title, description, created_at
    FROM recommendations
    WHERE provider_id = ?
    ORDER BY created_at DESC
");
$stmt->bind_param("i", $provider_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $recommendations[] = $row;
}
$stmt->close();

// Stats for dashboard
$stats = [
    'recommendations_count' => count($recommendations),
    'last_activity' => !empty($recommendations) ? $recommendations[0]['created_at'] : 'No activity yet'
];

// Handle delete recommendation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_recommendation'])) {
    $recommendation_id = intval($_POST['recommendation_id']);
    
    // Verify 
    $stmt = $conn->prepare("SELECT provider_id FROM recommendations WHERE recommendation_id = ?");
    $stmt->bind_param("i", $recommendation_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $recommendation = $result->fetch_assoc();
    $stmt->close();
    
    if ($recommendation && $recommendation['provider_id'] == $provider_id) {
        $stmt = $conn->prepare("DELETE FROM recommendations WHERE recommendation_id = ?");
        $stmt->bind_param("i", $recommendation_id);
        
        if ($stmt->execute()) {
            set_message("Recommendation deleted successfully", "success");
            redirect("dashboard_provider.php"); // refresh to update
        } else {
            set_message("Error deleting recommendation: " . $conn->error, "error");
        }
        
        $stmt->close();
    } else {
        set_message("You don't have permission to delete this recommendation", "error");
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <div class="flex flex-wrap items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold mb-2">Provider Dashboard</h1>
            <p class="text-gray-600">Welcome back, <?php echo htmlspecialchars($provider_info['company_name']); ?>!</p>
        </div>
        <a href="add_recommendation.php" class="bg-primary text-white py-2 px-6 rounded-lg hover:bg-primary/90 transition mt-4 md:mt-0">
            <i class="fas fa-plus mr-2"></i> Add Recommendation
        </a>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow-md p-6 flex items-center">
        <div class="rounded-full bg-primary/10 p-4 mr-4">
            <i class="fas fa-lightbulb text-2xl text-primary"></i>
        </div>
        <div>
            <p class="text-sm text-gray-500">Recommendations</p>
            <p class="text-2xl font-bold"><?php echo $stats['recommendations_count']; ?></p>
        </div>
    </div>
    
    <div class="bg-white rounded-lg shadow-md p-6 flex items-center">
        <div class="rounded-full bg-blue-100 p-4 mr-4">
            <i class="fas fa-clock text-2xl text-blue-500"></i>
        </div>
        <div>
            <p class="text-sm text-gray-500">Last Activity</p>
            <p class="text-sm font-medium">
                <?php echo is_string($stats['last_activity']) ? $stats['last_activity'] : date('M j, Y', strtotime($stats['last_activity'])); ?>
            </p>
        </div>
    </div>
    
    <div class="md:col-span-2 bg-green-50 rounded-lg p-6">
        <div class="flex items-start">
            <div class="bg-green-100 rounded-full p-3 mr-4">
                <i class="fas fa-info-circle text-green-600"></i>
            </div>
            <div>
                <h3 class="font-medium text-green-800 mb-1">Provider Tips</h3>
                <p class="text-sm text-green-700">Share sustainable energy tips and recommendations to help homeowners reduce their energy consumption. Your expertise makes a difference!</p>
            </div>
        </div>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <!-- Basic inf -->
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold mb-4">Company Information</h2>
        
        <div class="space-y-3">
            <div>
                <p class="text-gray-500 text-sm">Company Name</p>
                <p class="font-medium"><?php echo htmlspecialchars($provider_info['company_name']); ?></p>
            </div>
            
            <div>
                <p class="text-gray-500 text-sm">Services Offered</p>
                <p class="font-medium"><?php echo htmlspecialchars($provider_info['services']); ?></p>
            </div>
            
            <div>
                <p class="text-gray-500 text-sm">Location</p>
                <p class="font-medium"><?php echo htmlspecialchars($provider_info['location']); ?></p>
            </div>
            
            <div>
                <p class="text-gray-500 text-sm">Contact Email</p>
                <p class="font-medium"><?php echo htmlspecialchars($provider_info['email']); ?></p>
            </div>
            
            <div>
                <p class="text-gray-500 text-sm">Sustainability Practices</p>
                <p class="text-sm"><?php echo htmlspecialchars($provider_info['sustainability_practices']); ?></p>
            </div>
        </div>
        
        <div class="mt-6">
            <a href="edit_profile.php" class="text-primary hover:underline flex items-center">
                <i class="fas fa-edit mr-1"></i> Edit Company Profile
            </a>
        </div>
    </div>
    
    <!-- recommend -->
    <div class="md:col-span-2">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-4">Your Recommendations</h2>
            
            <?php if (!empty($recommendations)): ?>
                <div class="space-y-4">
                    <?php foreach ($recommendations as $recommendation): ?>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <div class="flex justify-between items-start">
                                <h3 class="font-semibold text-lg"><?php echo htmlspecialchars($recommendation['title']); ?></h3>
                                <form method="post" action="dashboard_provider.php" onsubmit="return confirm('Are you sure you want to delete this recommendation?');" class="inline-block">
                                    <input type="hidden" name="recommendation_id" value="<?php echo $recommendation['recommendation_id']; ?>">
                                    <button type="submit" name="delete_recommendation" class="text-red-600 hover:text-red-800">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                            <p class="text-gray-600 mt-2"><?php echo htmlspecialchars($recommendation['description']); ?></p>
                            <p class="text-xs text-gray-500 mt-1">Added on <?php echo date('M j, Y', strtotime($recommendation['created_at'])); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="bg-gray-50 p-8 rounded-lg text-center">
                    <p class="text-gray-600 mb-4">You haven't added any recommendations yet.</p>
                    <a href="add_recommendation.php" class="inline-block bg-primary text-white py-2 px-4 rounded-lg hover:bg-primary/90 transition">
                        <i class="fas fa-plus mr-2"></i> Add Your First Recommendation
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
