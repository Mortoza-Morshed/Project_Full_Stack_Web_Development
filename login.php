<?php
session_start();
require_once 'includes/functions.php';
require_once 'config/db_connect.php';


if (is_logged_in()) {
    $redirect_page = '';
    if ($_SESSION['role'] === 'homeowner') {
        $redirect_page = 'dashboard_homeowner.php';
    } elseif ($_SESSION['role'] === 'provider') {
        $redirect_page = 'dashboard_provider.php';
    } elseif ($_SESSION['role'] === 'admin') {
        $redirect_page = 'admin_dashboard.php';
    }
    redirect($redirect_page);
}

$error = '';

// Handle form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validation
    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password";
    } else {
        // Get user from db
        $stmt = $conn->prepare("SELECT user_id, name, email, password, role, status FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // status check
            if ($user['status'] === 'pending') {
                $error = "Your account is pending approval by an administrator";
            } elseif ($user['status'] === 'rejected') {
                $error = "Your registration has been rejected";
            } else {
                // Verify password
                if (verify_password($password, $user['password'])) {
                    // session data set karo
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['name'] = $user['name'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    
                    // Set success message
                    set_message("Login successful! Welcome back, {$user['name']}.");
                    
                    // Redirect based on role
                    if ($user['role'] === 'homeowner') {
                        redirect("dashboard_homeowner.php");
                    } elseif ($user['role'] === 'provider') {
                        redirect("dashboard_provider.php");
                    } elseif ($user['role'] === 'admin') {
                        redirect("admin_dashboard.php");
                    } else {
                        redirect("index.php");
                    }
                } else {
                    $error = "Invalid email or password";
                }
            }
        } else {
            $error = "Invalid email or password";
        }
        
        $stmt->close();
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="max-w-md mx-auto bg-white p-8 rounded-lg shadow-md">
    <h1 class="text-3xl font-bold mb-6 text-center">Login</h1>
    
    <?php if (!empty($error)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <form action="login.php" method="post" class="space-y-6">
        <div>
            <label for="email" class="block text-gray-700 font-medium mb-2">Email</label>
            <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" required>
        </div>
        
        <div>
            <label for="password" class="block text-gray-700 font-medium mb-2">Password</label>
            <input type="password" id="password" name="password" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" required>
        </div>
        
        <div>
            <button type="submit" class="w-full bg-primary text-white font-semibold py-3 px-6 rounded-lg hover:bg-primary/90 transition">Login</button>
        </div>
        
        <div class="text-center">
            <p>Don't have an account? <a href="register.php" class="text-primary hover:underline">Register</a></p>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?> 