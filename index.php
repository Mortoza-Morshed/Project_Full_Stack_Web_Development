<?php
session_start();
require_once 'includes/functions.php';
?>

<?php include 'includes/header.php'; ?>

<!-- MAIN -->
<section class="text-white bg-gray-700 py-32 relative">
    <img src="images/bg.jpg" alt="AI Logo" class="w-full z-10 h-full mb-4 opacity-30 absolute top-0 left-0 object-cover">
    <div class="container mx-auto z-30 px-4 text-center relative">
        <h1 class="text-4xl md:text-5xl font-bold mb-4">Reduce Your Energy Footprint Today!</h1>
        <p class="text-xl md:text-2xl mb-8">Track, analyze, and optimize your home's energy consumption with our easy-to-use platform.</p>
        <div class="flex flex-col sm:flex-row justify-center gap-4">
            <?php if (!is_logged_in()): ?>
                <a href="register.php" class="bg-white text-primary font-semibold py-3 px-6 rounded-lg shadow-md hover:bg-gray-100 transition">Get Started</a>
                <a href="login.php" class="border border-white text-white font-semibold py-3 px-6 rounded-lg hover:bg-white/10 transition">Log In</a>
            <?php else: ?>
                <a href="<?php echo $_SESSION['role'] === 'homeowner' ? 'dashboard_homeowner.php' : ($_SESSION['role'] === 'provider' ? 'dashboard_provider.php' : 'admin_dashboard.php'); ?>" 
                   class="bg-white text-primary font-semibold py-3 px-6 rounded-lg shadow-md hover:bg-gray-100 transition">
                    Go to Dashboard
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Features Section -->
<section class="py-16">
    <div class="container mx-auto px-4">
        <h2 class="text-3xl font-bold text-center mb-12">How It Works</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            <div class="bg-white p-6 rounded-lg shadow-md flex flex-col items-center text-center">
                <div class="bg-primary/10 p-4 rounded-full mb-4">
                    <i class="fas fa-chart-line text-3xl text-primary"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">Track Your Energy Usage</h3>
                <p class="text-gray-600">Log your electricity, gas, and other energy consumption data with our easy-to-use interface.</p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-md flex flex-col items-center text-center">
                <div class="bg-primary/10 p-4 rounded-full mb-4">
                    <i class="fas fa-lightbulb text-3xl text-primary"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">Get Smart Recommendations</h3>
                <p class="text-gray-600">Receive personalized tips and insights to help you reduce your energy consumption and costs.</p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-md flex flex-col items-center text-center">
                <div class="bg-primary/10 p-4 rounded-full mb-4">
                    <i class="fas fa-hands-helping text-3xl text-primary"></i>
                </div>
                <h3 class="text-xl font-semibold mb-2">Connect with Providers</h3>
                <p class="text-gray-600">Find and connect with local energy service providers who can help implement energy-saving solutions.</p>
            </div>
        </div>
    </div>
</section>


<section class="py-16 bg-gradient-to-r from-primary to-secondary text-white">
    <div class="container mx-auto px-4 text-center">
        <h2 class="text-3xl font-bold mb-6">Ready to Start Saving?</h2>
        <p class="text-xl mb-8">Join thousands of homeowners who are reducing their energy footprint and saving money.</p>
        <?php if (!is_logged_in()): ?>
            <div class="flex flex-col sm:flex-row justify-center gap-4">
                <a href="register.php" class="bg-white text-primary font-semibold py-3 px-6 rounded-lg shadow-md hover:bg-gray-100 transition">Create Free Account</a>
                <a href="login.php" class="border border-white text-white font-semibold py-3 px-6 rounded-lg hover:bg-white/10 transition">Log In</a>
            </div>
        <?php else: ?>
            <a href="<?php echo $_SESSION['role'] === 'homeowner' ? 'dashboard_homeowner.php' : ($_SESSION['role'] === 'provider' ? 'dashboard_provider.php' : 'admin_dashboard.php'); ?>" 
               class="bg-white text-primary font-semibold py-3 px-6 rounded-lg shadow-md hover:bg-gray-100 transition">
                Go to Dashboard
            </a>
        <?php endif; ?>
    </div>
</section>

<?php include 'includes/footer.php'; ?>