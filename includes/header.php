<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" href="images/weblogo.jpg" type="image/x-icon">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Energy Track</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href='https://fonts.googleapis.com/css?family=Poppins' rel='stylesheet'>
    <style>
        body{
            font-family: 'Poppins';
        }
</style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#16a34a',
                        secondary: '#0ea5e9',
                        dark: '#0f172a',
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50">
    <nav class="bg-primary text-white shadow-md">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <a href="index.php" class="text-2xl font-bold flex items-center">
                <!-- <i class="fas fa-leaf mr-2"></i> -->
                <img src="images/weblogo.jpg" alt="logo" class="w-12 h-12 rounded-full  mr-2">
                <span>EnergyTrack</span>
            </a>
            <div class="hidden md:flex space-x-4">
                <a href="index.php" class="hover:text-gray-200 py-2 px-3">Home</a>
                <?php if(!isset($_SESSION['user_id'])): ?>
                    <a href="login.php" class=" hover:text-gray-200 py-2 px-3">Login</a>
                    <a href="register.php" class=" hover:text-gray-200 py-2 px-3">Register</a>
                <?php else: ?>
                    <?php if($_SESSION['role'] == 'homeowner'): ?>
                        <a href="dashboard_homeowner.php" class=" hover:text-gray-200 py-2 px-3">Dashboard</a>
                        <a href="energy_input.php" class=" hover:text-gray-200 py-2 px-3">Data Input</a>
                        <a href="energy_reports.php" class=" hover:text-gray-200 py-2 px-3">Reports</a>
                        <a href="provider_search.php" class=" hover:text-gray-200 py-2 px-3">Find Providers</a>
                        <a href="recommendations.php" class=" hover:text-gray-200 py-2 px-3">Recommendations</a>
                    <?php elseif($_SESSION['role'] == 'provider'): ?>
                        <a href="dashboard_provider.php" class=" hover:text-gray-200 py-2 px-3">Dashboard</a>
                        <a href="add_recommendation.php" class=" hover:text-gray-200 py-2 px-3">Add Recommendation</a>
                    <?php elseif($_SESSION['role'] == 'admin'): ?>
                        <a href="admin_dashboard.php" class=" hover:text-gray-200 py-2 px-3">Admin Dashboard</a>
                    <?php endif; ?>
                    <a href="logout.php" class=" hover:text-gray-200 py-2 px-3">Logout</a>
                <?php endif; ?>
            </div>
            <button class="md:hidden text-white" id="mobile-menu-button">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        <div class="hidden md:hidden bg-primary" id="mobile-menu">
            <div class="container mx-auto px-4 py-2 flex flex-col space-y-2">
                <a href="index.php" class="text-white hover:underline py-2">Home</a>
                <?php if(!isset($_SESSION['user_id'])): ?>
                    <a href="login.php" class="text-white hover:underline py-2">Login</a>
                    <a href="register.php" class="text-white hover:underline py-2">Register</a>
                <?php else: ?>
                    <?php if($_SESSION['role'] == 'homeowner'): ?>
                        <a href="dashboard_homeowner.php" class="text-white hover:underline py-2">Dashboard</a>
                        <a href="energy_input.php" class="text-white hover:underline py-2">Data Input</a>
                        <a href="energy_reports.php" class="text-white hover:underline py-2">Reports</a>
                        <a href="provider_search.php" class="text-white hover:underline py-2">Find Providers</a>
                        <a href="recommendations.php" class="text-white hover:underline py-2">Recommendations</a>
                    <?php elseif($_SESSION['role'] == 'provider'): ?>
                        <a href="dashboard_provider.php" class="text-white hover:underline py-2">Dashboard</a>
                        <a href="add_recommendation.php" class="text-white hover:underline py-2">Add Recommendation</a>
                    <?php elseif($_SESSION['role'] == 'admin'): ?>
                        <a href="admin_dashboard.php" class="text-white hover:underline py-2">Admin Dashboard</a>
                    <?php endif; ?>
                    <a href="logout.php" class="text-white hover:underline py-2">Logout</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    
    <main class="container mx-auto px-4 py-8">
        <?php if(isset($_SESSION['message'])): ?>
            <div class="mb-4 p-4 <?php echo $_SESSION['message_type'] == 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?> rounded">
                <?php echo $_SESSION['message']; ?>
                <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
            </div>
        <?php endif; ?> 