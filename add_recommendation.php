<?php
session_start();
require_once 'includes/functions.php';
require_once 'config/db_connect.php';

// Check if user is logged in and is a provider
if (!is_logged_in() || !has_role('provider')) {
    set_message("You must be logged in as a provider to access this page", "error");
    redirect("login.php");
}

$user_id = $_SESSION['user_id'];
$errors = [];

// provider INfo
$provider_info = [];
$stmt = $conn->prepare("
    SELECT provider_id, company_name 
    FROM providers 
    WHERE user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$provider_info = $result->fetch_assoc();
$provider_id = $provider_info['provider_id'];
$stmt->close();

$categories = [
    'Lighting' => 'Tips about energy-efficient lighting solutions',
    'Heating' => 'Recommendations for heating systems and efficiency',
    'Cooling' => 'Tips for air conditioning and cooling optimization',
    'Appliances' => 'Advice on energy-efficient household appliances',
    'Insulation' => 'Suggestions for improving home insulation',
    'Solar' => 'Information about solar panel installation and benefits',
    'Water' => 'Tips for saving water and water heating efficiency',
    'General' => 'Other energy-saving recommendations'
];


if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $title = sanitize($_POST['title'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $category = sanitize($_POST['category'] ?? '');
    

    if (empty($title)) {
        $errors[] = "Title is required";
    }
    
    if (empty($description)) {
        $errors[] = "Description is required";
    }
    
    if (empty($errors)) {
        // Just formatting of title to make  it better
        $formatted_title = (!empty($category) && $category !== 'none') 
            ? "$category: $title" 
            : $title;
        
        $stmt = $conn->prepare("
            INSERT INTO recommendations (provider_id, title, description) 
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param("iss", $provider_id, $formatted_title, $description);
        
        if ($stmt->execute()) {
            set_message("Recommendation added successfully", "success");
            redirect("dashboard_provider.php");
        } else {
            $errors[] = "Failed to add recommendation: " . $conn->error;
        }
        
        $stmt->close();
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="max-w-7xl mx-auto flex flex-col gap-5 lg:flex-row">
    <div class="bg-white rounded-lg shadow-md p-6 lg:w-3/4">
        <h1 class="text-3xl font-bold mb-6">Add Energy-Saving Recommendation</h1>
        
        <?php if (!empty($errors)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <ul class="list-disc list-inside">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo $error; ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form action="add_recommendation.php" method="post" class="space-y-6">
            <div>
                <label for="category" class="block text-gray-700 font-medium mb-2">Category</label>
                <select id="category" name="category" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="none">-- Select Category (Optional) --</option>
                    <?php foreach ($categories as $cat => $desc): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="text-sm text-gray-500 mt-1">Categorizing helps homeowners find your recommendations more easily</p>
            </div>
            
            <div>
                <label for="title" class="block text-gray-700 font-medium mb-2">Title</label>
                <input type="text" id="title" name="title" value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" required>
                <p class="text-sm text-gray-500 mt-1">Keep it concise and descriptive</p>
            </div>
            
            <div>
                <label for="description" class="block text-gray-700 font-medium mb-2">Description</label>
                <textarea id="description" name="description" rows="5" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                <p class="text-sm text-gray-500 mt-1">Provide detailed information, potential savings, and implementation tips</p>
            </div>
            
            <div id="categoryHelp" class="bg-blue-50 p-4 rounded-lg hidden">
                <h3 class="font-medium text-blue-800 mb-2" id="categoryHelpTitle"></h3>
                <p class="text-sm text-blue-700" id="categoryHelpDescription"></p>
            </div>
            
            <div class="flex justify-between">
                <a href="dashboard_provider.php" class="bg-gray-100 text-gray-700 py-2 px-6 rounded-lg hover:bg-gray-200 transition">Cancel</a>
                <button type="submit" class="bg-primary text-white py-2 px-6 rounded-lg hover:bg-primary/90 transition">Add Recommendation</button>
            </div>
        </form>
    </div>
    
    <div class="bg-white rounded-lg shadow-md  p-6 lg:w-1/4">
        <h2 class="text-xl font-semibold mb-4">Tips for Effective Recommendations</h2>
        
        <div class="space-y-4">
            <div class="flex items-start">
                <div class="bg-green-100 rounded-full p-2 mr-3 mt-1">
                    <i class="fas fa-check text-green-600"></i>
                </div>
                <div>
                    <h3 class="font-medium text-green-800 mb-1">Be Specific</h3>
                    <p class="text-sm text-gray-600">Instead of "Use less electricity," suggest "Replace incandescent bulbs with LED bulbs to save up to 75% on lighting costs."</p>
                </div>
            </div>
            
            <div class="flex items-start">
                <div class="bg-green-100 rounded-full p-2 mr-3 mt-1">
                    <i class="fas fa-check text-green-600"></i>
                </div>
                <div>
                    <h3 class="font-medium text-green-800 mb-1">Quantify Benefits</h3>
                    <p class="text-sm text-gray-600">Include potential energy savings (%, kWh, or $) when possible to help homeowners understand the impact.</p>
                </div>
            </div>
            
            <div class="flex items-start">
                <div class="bg-green-100 rounded-full p-2 mr-3 mt-1">
                    <i class="fas fa-check text-green-600"></i>
                </div>
                <div>
                    <h3 class="font-medium text-green-800 mb-1">Include Implementation Details</h3>
                    <p class="text-sm text-gray-600">Provide practical steps on how to implement your recommendation, including difficulty level and approximate costs.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Rendering category at bottom
    document.getElementById('category').addEventListener('change', function() {
        const categoryHelp = document.getElementById('categoryHelp');
        const categoryHelpTitle = document.getElementById('categoryHelpTitle');
        const categoryHelpDescription = document.getElementById('categoryHelpDescription');
        const selectedCategory = this.value;
        
        if (selectedCategory !== 'none') {
            const categories = <?php echo json_encode($categories); ?>;
            categoryHelpTitle.textContent = selectedCategory + ' Recommendations';
            categoryHelpDescription.textContent = categories[selectedCategory] || 'Provide tips related to this category';
            categoryHelp.classList.remove('hidden');
        } else {
            categoryHelp.classList.add('hidden');
        }
    });
</script>

<?php include 'includes/footer.php'; ?>
