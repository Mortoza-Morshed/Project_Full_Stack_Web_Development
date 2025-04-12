<?php
session_start();
require_once 'includes/functions.php';
require_once 'config/db_connect.php';

if (is_logged_in()) {
    redirect("index.php");
}

$errors = [];
$role = 'homeowner'; // default

// Handle form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $role = sanitize($_POST['role'] ?? 'homeowner');
    $address = sanitize($_POST['address'] ?? '');
    $company_name = sanitize($_POST['company_name'] ?? '');
    $services = sanitize($_POST['services'] ?? '');
    $location = sanitize($_POST['location'] ?? '');
    $sustainability_practices = sanitize($_POST['sustainability_practices'] ?? '');
    
    // Validatation
    if (empty($name)) {
        $errors[] = "Name is required";
    } elseif (!validate_name($name)) {
        $errors[] = "Invalid name format";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
    } elseif (!validate_email($email)) {
        $errors[] = "Invalid email format";
    } else {
        // if email already exists
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $errors[] = "Email already exists";
        }
        $stmt->close();
    }
    
    if (empty($password)) {
        $errors[] = "Password is required";
    } elseif (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    

    if ($role === 'homeowner') {
        if (empty($address)) {
            $errors[] = "Address is required";
        }
    } elseif ($role === 'provider') {
        if (empty($company_name)) {
            $errors[] = "Company name is required";
        }
        if (empty($services)) {
            $errors[] = "Services are required";
        }
        if (empty($location)) {
            $errors[] = "Location is required";
        }
    }
    
    // register the user
    if (empty($errors)) {
        $hashed_password = hash_password($password);
        
        $conn->begin_transaction();
        
        try {
            // SQL
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, status) VALUES (?, ?, ?, ?, 'pending')");
            $stmt->bind_param("ssss", $name, $email, $hashed_password, $role);
            $stmt->execute();
            $user_id = $conn->insert_id;
            $stmt->close();
            
            // Insert additional details
            if ($role === 'homeowner') {
                $stmt = $conn->prepare("INSERT INTO homeowners (user_id, address) VALUES (?, ?)");
                $stmt->bind_param("is", $user_id, $address);
                $stmt->execute();
                $stmt->close();
            } elseif ($role === 'provider') {
                $stmt = $conn->prepare("INSERT INTO providers (user_id, company_name, services, location, sustainability_practices) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("issss", $user_id, $company_name, $services, $location, $sustainability_practices);
                $stmt->execute();
                $stmt->close();
            }
            

            $conn->commit();
            
            // Set message
            set_message("Registration successful! Please wait for admin approval before you can log in.");
            
            
            redirect("login.php");
        } catch (Exception $e) {
            $conn->rollback();
            $errors[] = "Registration failed: " . $e->getMessage();
        }
    }
}
?>

<?php include 'includes/header.php'; ?>

<div class="max-w-3xl mx-auto bg-white p-8 rounded-lg shadow-md">
    <h1 class="text-3xl font-bold mb-6 text-center">Create an Account</h1>
    
    <?php if (!empty($errors)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
            <ul class="list-disc list-inside">
                <?php foreach ($errors as $error): ?>
                    <li><?php echo $error; ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <form action="register.php" method="post" class="space-y-6">
        <div>
            <label for="role" class="block text-gray-700 font-medium mb-2">I am a:</label>
            <select id="role" name="role" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" onchange="toggleRoleFields(this.value)">
                <option value="homeowner" <?php echo $role === 'homeowner' ? 'selected' : ''; ?>>Homeowner</option>
                <option value="provider" <?php echo $role === 'provider' ? 'selected' : ''; ?>>Energy Service Provider</option>
            </select>
        </div>
        
        <div>
            <label for="name" class="block text-gray-700 font-medium mb-2">Name</label>
            <input type="text" id="name" name="name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" required>
        </div>
        
        <div>
            <label for="email" class="block text-gray-700 font-medium mb-2">Email</label>
            <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" required>
        </div>
        
        <div>
            <label for="password" class="block text-gray-700 font-medium mb-2">Password</label>
            <input type="password" id="password" name="password" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" required>
            <p class="text-sm text-gray-500 mt-1">Must be at least 6 characters</p>
        </div>
        
        <div>
            <label for="confirm_password" class="block text-gray-700 font-medium mb-2">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" required>
        </div>
        
        <!-- homeowner -->
        <div id="homeowner-fields" class="<?php echo $role === 'provider' ? 'hidden' : ''; ?>">
            <div>
                <label for="address" class="block text-gray-700 font-medium mb-2">Address</label>
                <input type="text" id="address" name="address" value="<?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?>" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
            </div>
        </div>
        
        <!-- provider -->
        <div id="provider-fields" class="<?php echo $role === 'homeowner' ? 'hidden' : ''; ?>">
            <div>
                <label for="company_name" class="block text-gray-700 font-medium mb-2">Company Name</label>
                <input type="text" id="company_name" name="company_name" value="<?php echo isset($_POST['company_name']) ? htmlspecialchars($_POST['company_name']) : ''; ?>" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
            </div>
            
            <div>
                <label for="services" class="block text-gray-700 font-medium mb-2">Services Offered</label>
                <textarea id="services" name="services" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" rows="3"><?php echo isset($_POST['services']) ? htmlspecialchars($_POST['services']) : ''; ?></textarea>
            </div>
            
            <div>
                <label for="location" class="block text-gray-700 font-medium mb-2">Location</label>
                <input type="text" id="location" name="location" value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
            </div>
            
            <div>
                <label for="sustainability_practices" class="block text-gray-700 font-medium mb-2">Sustainability Practices</label>
                <textarea id="sustainability_practices" name="sustainability_practices" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" rows="3"><?php echo isset($_POST['sustainability_practices']) ? htmlspecialchars($_POST['sustainability_practices']) : ''; ?></textarea>
            </div>
        </div>
        
        <div>
            <button type="submit" class="w-full bg-primary text-white font-semibold py-3 px-6 rounded-lg hover:bg-primary/90 transition">Register</button>
        </div>
        
        <div class="text-center">
            <p>Already have an account? <a href="login.php" class="text-primary hover:underline">Login</a></p>
        </div>
    </form>
</div>

<script>
    function toggleRoleFields(role) {
        const homeownerFields = document.getElementById('homeowner-fields');
        const providerFields = document.getElementById('provider-fields');
        
        if (role === 'homeowner') {
            homeownerFields.classList.remove('hidden');
            providerFields.classList.add('hidden');
        } else if (role === 'provider') {
            homeownerFields.classList.add('hidden');
            providerFields.classList.remove('hidden');
        }
    }
</script>

<?php include 'includes/footer.php'; ?> 