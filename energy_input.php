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
$errors = [];
$success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // check for file
    if (isset($_FILES['energy_data_file']) && $_FILES['energy_data_file']['error'] === UPLOAD_ERR_OK) {
        // File upload
        $allowed_types = ['text/csv', 'application/vnd.ms-excel'];
        $file_type = $_FILES['energy_data_file']['type'];
        
        if (!in_array($file_type, $allowed_types)) {
            $errors[] = "Only CSV files are allowed";
        } else {
            $file = $_FILES['energy_data_file']['tmp_name'];
            
            // Read CSV file
            if (($handle = fopen($file, "r")) !== FALSE) {
                // Skip header row
                fgetcsv($handle);
                
                $conn->begin_transaction();
                try {
                    $success_count = 0;
                    $error_count = 0;
                    
                    while (($data = fgetcsv($handle)) !== FALSE) {
                        if (count($data) < 4) {
                            $error_count++;
                            continue;
                        }
                        
                        $date = sanitize($data[0]);
                        $energy_type = sanitize($data[1]);
                        $consumption_value = floatval($data[2]);
                        $unit = sanitize($data[3]);
                        
                        if (empty($date) || empty($energy_type) || $consumption_value <= 0 || empty($unit)) {
                            $error_count++;
                            continue;
                        }
                        
                        $stmt = $conn->prepare("INSERT INTO energy_usage (user_id, date, energy_type, consumption_value, unit) VALUES (?, ?, ?, ?, ?)");
                        $stmt->bind_param("issds", $user_id, $date, $energy_type, $consumption_value, $unit);
                        $stmt->execute();
                        $stmt->close();
                        
                        $success_count++;
                    }
                    $conn->commit();
                    
                    if ($success_count > 0) {
                        $success = "Successfully imported {$success_count} energy records";
                        if ($error_count > 0) {
                            $success .= " (skipped {$error_count} invalid entries)";
                        }
                    } else {
                        $errors[] = "No valid data found in the CSV file";
                    }
                } catch (Exception $e) {
                    // for error
                    $conn->rollback();
                    $errors[] = "Import failed: " . $e->getMessage();
                }
                
                fclose($handle);
            } else {
                $errors[] = "Could not open the uploaded file";
            }
        }
    } else {
        // for form
        $date = sanitize($_POST['date'] ?? '');
        $energy_type = sanitize($_POST['energy_type'] ?? '');
        $consumption_value = floatval($_POST['consumption_value'] ?? 0);
        $unit = sanitize($_POST['unit'] ?? '');
        
        if (empty($date)) {
            $errors[] = "Date is required";
        }
        
        if (empty($energy_type)) {
            $errors[] = "Energy type is required";
        }
        
        if ($consumption_value <= 0) {
            $errors[] = "Consumption value must be greater than zero";
        }
        
        if (empty($unit)) {
            $errors[] = "Unit is required";
        }
        
        if (empty($errors)) {
            try {
                $stmt = $conn->prepare("INSERT INTO energy_usage (user_id, date, energy_type, consumption_value, unit) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("issds", $user_id, $date, $energy_type, $consumption_value, $unit);
                $stmt->execute();
                $stmt->close();
                
                $success = "Energy data saved successfully";
            } catch (Exception $e) {
                $errors[] = "Failed to save data: " . $e->getMessage();
            }
        }
    }
}

// last 5 entries
$recent_entries = [];
$stmt = $conn->prepare("
    SELECT date, energy_type, consumption_value, unit
    FROM energy_usage 
    WHERE user_id = ?
    ORDER BY date DESC, created_at DESC
    LIMIT 5
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $recent_entries[] = $row;
}
$stmt->close();
?>

<?php include 'includes/header.php'; ?>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <div class="col-span-1 md:col-span-2">
        <!--  form -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h1 class="text-2xl font-bold mb-4">Add Energy Data</h1>
            
            <?php if (!empty($success)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <ul class="list-disc list-inside">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <!-- manual form -->
            <div class="mb-8">
                <h2 class="text-xl font-semibold mb-4">Manual Entry</h2>
                <form action="energy_input.php" method="post" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="date" class="block text-gray-700 font-medium mb-2">Date</label>
                            <input type="date" id="date" name="date" value="<?php echo date('Y-m-d'); ?>" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" required>
                        </div>
                        
                        <div>
                            <label for="energy_type" class="block text-gray-700 font-medium mb-2">Energy Type</label>
                            <select id="energy_type" name="energy_type" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" required>
                                <option value="">-- Select Type --</option>
                                <option value="electricity">Electricity</option>
                                <option value="gas">Gas</option>
                                <option value="water">Water</option>
                                <option value="solar">Solar Production</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="consumption_value" class="block text-gray-700 font-medium mb-2">Consumption Value</label>
                            <input type="number" id="consumption_value" name="consumption_value" step="0.01" min="0" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" required>
                        </div>
                        
                        <div>
                            <label for="unit" class="block text-gray-700 font-medium mb-2">Unit</label>
                            <select id="unit" name="unit" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary" required>
                                <option value="">-- Select Unit --</option>
                                <option value="kWh">kWh (Electricity)</option>
                                <option value="therms">Therms (Gas)</option>
                                <option value="m3">Cubic Meters (Gas)</option>
                                <option value="gallons">Gallons (Water)</option>
                                <option value="liters">Liters (Water)</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                    </div>
                    
                    <div>
                        <button type="submit" class="bg-primary text-white font-semibold py-2 px-6 rounded-lg hover:bg-primary/90 transition">Save Entry</button>
                    </div>
                </form>
            </div>
            
            <!-- file upload -->
            <div>
                <h2 class="text-xl font-semibold mb-4">Bulk Upload</h2>
                <form action="energy_input.php" method="post" enctype="multipart/form-data" class="space-y-4">
                    <div>
                        <label for="energy_data_file" class="block text-gray-700 font-medium mb-2">Upload CSV File</label>
                        <input type="file" id="energy_data_file" name="energy_data_file" accept=".csv" class="w-full px-4 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                        <p class="text-sm text-gray-500 mt-1">CSV format: date, energy_type, consumption_value, unit</p>
                        <p class="text-sm text-gray-500">Example: 2023-10-01, electricity, 100, kWh</p>
                    </div>
                    
                    <div>
                        <button type="submit" class="bg-secondary text-white font-semibold py-2 px-6 rounded-lg hover:bg-secondary/90 transition">Upload File</button>
                        <a href="#" class="text-secondary hover:underline ml-2 text-sm">Download Template</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div>
        <!-- recents -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-xl font-semibold mb-4">Recent Entries</h2>
            
            <?php if (!empty($recent_entries)): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Value</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Unit</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white">
                            <?php foreach ($recent_entries as $entry): ?>
                                <tr class="border-t">
                                    <td class="px-4 py-3 text-sm"><?php echo date('M j, Y', strtotime($entry['date'])); ?></td>
                                    <td class="px-4 py-3 text-sm capitalize"><?php echo htmlspecialchars($entry['energy_type']); ?></td>
                                    <td class="px-4 py-3 text-sm"><?php echo number_format($entry['consumption_value'], 2); ?></td>
                                    <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($entry['unit']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 text-center">
                    <a href="energy_reports.php" class="text-primary hover:underline">View All History</a>
                </div>
            <?php else: ?>
                <div class="bg-gray-50 rounded-lg p-8 text-center">
                    <p class="text-gray-600">No recent entries found.</p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- tips :) -->
        <div class="bg-blue-50 rounded-lg p-4 mt-6">
            <h3 class="font-semibold text-blue-800 mb-2">Tips for Accuracy</h3>
            <ul class="list-disc list-inside text-sm text-blue-700 space-y-1">
                <li>Record data regularly for better insights</li>
                <li>Check your utility bills for exact usage</li>
                <li>Use consistent units for better comparison</li>
                <li>Make note of weather changes or unusual events</li>
            </ul>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 