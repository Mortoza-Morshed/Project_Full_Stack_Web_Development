<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="icon" href="/Energy/images/weblogo.jpg" type="image/x-icon">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Energy Track</title>
    <!-- <script src="https://cdn.tailwindcss.com"></script> -->
    <link href="/Energy/src/output.css" rel="stylesheet">
    <link href="/Energy/node_modules/@fortawesome/fontawesome-free/css/all.min.css" rel="stylesheet">
    <script src="/Energy/node_modules/chart.js/dist/chart.umd.js"></script>
    <link href="/Energy/public/fonts/poppins.css" rel="stylesheet">
    
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        #mobile-menu {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.5s ease-out;
        }
        #mobile-menu.active {
            max-height: 500px;
        }
    </style>

    <!-- For cdn -->
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
    <nav class="bg-primary text-white shadow-md relative z-30">
        <!-- <div class="absolute bg-gray-900/45 top-0 left-0 w-full -z-10 h-full"></div> -->
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            <a href="index.php" class="text-2xl font-bold flex items-center">
                <img src="/Energy/images/weblogo.jpg" alt="logo" class="w-12 h-12 rounded-full  mr-2">
                <span>EnergyTrack</span>
            </a>
            <div class="hidden xl:flex space-x-4">
                <a href="index.php" class=" hover:text-primary py-2 px-3 hover:bg-gray-50 transition-all duration-300 rounded-md">Home</a>
                <?php if(!isset($_SESSION['user_id'])): ?>
                    <a href="login.php" class="  hover:text-primary py-2 px-3 hover:bg-gray-50 transition-all duration-300 rounded-md">Login</a>
                    <a href="register.php" class=" hover:text-primary py-2 px-3 hover:bg-gray-50 transition-all duration-300 rounded-md">Register</a>
                <?php else: ?>
                    <?php if($_SESSION['role'] == 'homeowner'): ?>
                        <a href="dashboard_homeowner.php" class=" hover:text-primary py-2 px-3 hover:bg-gray-50 transition-all duration-300 rounded-md ">Dashboard</a>
                        <a href="energy_input.php" class="  hover:text-primary py-2 px-3 hover:bg-gray-50 transition-all duration-300 rounded-md">Data Input</a>
                        <a href="energy_reports.php" class="  hover:text-primary py-2 px-3 hover:bg-gray-50 transition-all duration-300 rounded-md">Reports</a>
                        <a href="provider_search.php" class="  hover:text-primary py-2 px-3 hover:bg-gray-50 transition-all duration-300 rounded-md">Find Providers</a>
                        <a href="recommendations.php" class=" hover:text-primary py-2 px-3 hover:bg-gray-50 transition-all duration-300 rounded-md">Recommendations</a>
                    <?php elseif($_SESSION['role'] == 'provider'): ?>
                        <a href="dashboard_provider.php" class="  hover:text-primary py-2 px-3 hover:bg-gray-50 transition-all duration-300 rounded-md">Dashboard</a>
                        <a href="add_recommendation.php" class="  hover:text-primary py-2 px-3 hover:bg-gray-50 transition-all duration-300 rounded-md">Add Recommendation</a>
                    <?php elseif($_SESSION['role'] == 'admin'): ?>
                        <a href="admin_dashboard.php" class="  hover:text-primary py-2 px-3 hover:bg-gray-50 transition-all duration-300 rounded-md">Admin Dashboard</a>
                    <?php endif; ?>
                    <a href="logout.php" class="  hover:text-primary py-2 px-3 hover:bg-gray-50 transition-all duration-300 rounded-md">Logout</a>
                <?php endif; ?>
            </div>
            <button class="xl:hidden text-white cursor-pointer" id="mobile-menu-button">
                <i class="fas fa-bars"></i>
            </button>
        </div>  
        <div class="xl:hidden bg-primary" id="mobile-menu">
            <div class="container mx-auto px-4 py-2 flex flex-col space-y-2">
                <a href="index.php" class=" hover:text-primary py-2 px-3 hover:bg-gray-50 transition-all duration-300 rounded-md">Home</a>
                <?php if(!isset($_SESSION['user_id'])): ?>
                    <a href="login.php" class=" hover:text-primary py-2 px-3 hover:bg-gray-50 transition-all duration-300 rounded-md">Login</a>
                    <a href="register.php" class=" hover:text-primary py-2 px-3 hover:bg-gray-50 transition-all duration-300 rounded-md">Register</a>
                <?php else: ?>
                    <?php if($_SESSION['role'] == 'homeowner'): ?>
                        <a href="dashboard_homeowner.php" class=" hover:text-primary py-2 px-3 hover:bg-gray-50 transition-all duration-300 rounded-md">Dashboard</a>
                        <a href="energy_input.php" class=" hover:text-primary py-2 px-3 hover:bg-gray-50 transition-all duration-300 rounded-md">Data Input</a>
                        <a href="energy_reports.php" class=" hover:text-primary py-2 px-3 hover:bg-gray-50 transition-all duration-300 rounded-md">Reports</a>
                        <a href="provider_search.php" class=" hover:text-primary py-2 px-3 hover:bg-gray-50 transition-all duration-300 rounded-md">Find Providers</a>
                        <a href="recommendations.php" class=" hover:text-primary py-2 px-3 hover:bg-gray-50 transition-all duration-300 rounded-md">Recommendations</a>
                    <?php elseif($_SESSION['role'] == 'provider'): ?>
                        <a href="dashboard_provider.php" class=" hover:text-primary py-2 px-3 hover:bg-gray-50 transition-all duration-300 rounded-md">Dashboard</a>
                        <a href="add_recommendation.php" class=" hover:text-primary py-2 px-3 hover:bg-gray-50 transition-all duration-300 rounded-md">Add Recommendation</a>
                    <?php elseif($_SESSION['role'] == 'admin'): ?>
                        <a href="admin_dashboard.php" class=" hover:text-primary py-2 px-3 hover:bg-gray-50 transition-all duration-300 rounded-md">Admin Dashboard</a>
                    <?php endif; ?>
                    <a href="logout.php" class=" hover:text-primary py-2 px-3 hover:bg-gray-50 transition-all duration-300 rounded-md">Logout</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    <script>
        document.getElementById('mobile-menu-button').addEventListener('click', function() {
            document.getElementById('mobile-menu').classList.toggle('active');
        }); 
    </script>
    
    <main class="container mx-auto px-4 py-8">
        <?php if(isset($_SESSION['message'])): ?>
            <div class="mb-4 p-4 <?php echo $_SESSION['message_type'] == 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?> rounded">
                <?php echo $_SESSION['message']; ?>
                <?php unset($_SESSION['message']); unset($_SESSION['message_type']); ?>
            </div>
        <?php endif; ?> 