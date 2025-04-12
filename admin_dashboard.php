<?php
session_start();
require_once 'includes/functions.php';
require_once 'config/db_connect.php';

// Check if user is logged in and is an admin
if (!is_logged_in() || !has_role('admin')) {
    set_message("You must be logged in as an administrator to access this page", "error");
    redirect("login.php");
}

// Process approval/rejection
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && isset($_POST['user_id'])) {
    $user_id = intval($_POST['user_id']);
    $action = sanitize($_POST['action']);
    
    if ($action === 'approve' || $action === 'reject') {
        $status = ($action === 'approve') ? 'approved' : 'rejected';
        
        // Update user status
        $stmt = $conn->prepare("UPDATE users SET status = ? WHERE user_id = ?");
        $stmt->bind_param("si", $status, $user_id);
        
        if ($stmt->execute()) {
            set_message("User " . ($status === 'approved' ? 'approved' : 'rejected') . " successfully", "success");
        } else {
            set_message("Error processing request: " . $conn->error, "error");
        }
        
        $stmt->close();
    }
}

// Get pending registrations
$pending_registrations = [];
$stmt = $conn->prepare("
    SELECT u.user_id, u.name, u.email, u.role, u.created_at, 
           h.address, 
           p.company_name, p.services, p.location, p.sustainability_practices
    FROM users u
    LEFT JOIN homeowners h ON u.user_id = h.user_id
    LEFT JOIN providers p ON u.user_id = p.user_id
    WHERE u.status = 'pending'
    ORDER BY u.created_at DESC
");
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $pending_registrations[] = $row;
}
$stmt->close();

// Get platform statistics
$stats = [
    'total_users' => 0,
    'homeowners' => 0,
    'providers' => 0,
    'energy_entries' => 0,
    'recommendations' => 0
];

// Total approved users
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE status = 'approved'");
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stats['total_users'] = $row['count'];
$stmt->close();

// Homeowners count
$stmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM users 
    WHERE role = 'homeowner' AND status = 'approved'
");
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stats['homeowners'] = $row['count'];
$stmt->close();

// Providers count
$stmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM users 
    WHERE role = 'provider' AND status = 'approved'
");
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stats['providers'] = $row['count'];
$stmt->close();

// entries count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM energy_usage");
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stats['energy_entries'] = $row['count'];
$stmt->close();

// recommend count
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM recommendations");
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stats['recommendations'] = $row['count'];
$stmt->close();

// last 10 actions
$recent_activity = [];
$stmt = $conn->prepare("
    (SELECT 'Energy Entry' as type, eu.date as date, u.name as user_name, eu.energy_type, eu.consumption_value, eu.unit, NULL as title
     FROM energy_usage eu
     JOIN users u ON eu.user_id = u.user_id
     ORDER BY eu.created_at DESC
     LIMIT 5)
    UNION
    (SELECT 'Recommendation' as type, r.created_at as date, p.company_name as user_name, NULL as energy_type, NULL as consumption_value, NULL as unit, r.title
     FROM recommendations r
     LEFT JOIN providers p ON r.provider_id = p.provider_id
     ORDER BY r.created_at DESC
     LIMIT 5)
    ORDER BY date DESC
    LIMIT 10
");
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $recent_activity[] = $row;
}
$stmt->close();
?>

<?php include 'includes/header.php'; ?>

<div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <h1 class="text-3xl font-bold mb-4">Admin Dashboard</h1>
    <p class="text-gray-600">Manage user registrations and monitor platform activity.</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-6">
    <div class="bg-white rounded-lg shadow-md p-6 text-center">
        <p class="text-4xl font-bold text-primary"><?php echo $stats['total_users']; ?></p>
        <p class="text-gray-600">Total Users</p>
    </div>
    <div class="bg-white rounded-lg shadow-md p-6 text-center">
        <p class="text-4xl font-bold text-blue-500"><?php echo $stats['homeowners']; ?></p>
        <p class="text-gray-600">Homeowners</p>
    </div>
    <div class="bg-white rounded-lg shadow-md p-6 text-center">
        <p class="text-4xl font-bold text-green-500"><?php echo $stats['providers']; ?></p>
        <p class="text-gray-600">Providers</p>
    </div>
    <div class="bg-white rounded-lg shadow-md p-6 text-center">
        <p class="text-4xl font-bold text-orange-500"><?php echo $stats['energy_entries']; ?></p>
        <p class="text-gray-600">Energy Entries</p>
    </div>
    <div class="bg-white rounded-lg shadow-md p-6 text-center">
        <p class="text-4xl font-bold text-yellow-500"><?php echo $stats['recommendations']; ?></p>
        <p class="text-gray-600">Recommendations</p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- pending approvlas -->
    <div class="lg:col-span-2">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-semibold mb-4">Pending Registrations</h2>
            
            <?php if (!empty($pending_registrations)): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Registered</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white">
                            <?php foreach ($pending_registrations as $reg): ?>
                                <tr class="border-t">
                                    <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($reg['name']); ?></td>
                                    <td class="px-4 py-3 text-sm"><?php echo htmlspecialchars($reg['email']); ?></td>
                                    <td class="px-4 py-3 text-sm capitalize"><?php echo htmlspecialchars($reg['role']); ?></td>
                                    <td class="px-4 py-3 text-sm"><?php echo date('M j, Y', strtotime($reg['created_at'])); ?></td>
                                    <td class="px-4 py-3 text-sm">
                                        <button type="button" class="text-blue-600 hover:text-blue-800" onclick="viewDetails(<?php echo $reg['user_id']; ?>)">
                                            <i class="fas fa-info-circle mr-1"></i> Details
                                        </button>
                                        <form method="post" action="admin_dashboard.php" class="inline ml-2" onsubmit="return confirm('Are you sure you want to approve this user?');">
                                            <input type="hidden" name="user_id" value="<?php echo $reg['user_id']; ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="text-green-600 hover:text-green-800">
                                                <i class="fas fa-check mr-1"></i> Approve
                                            </button>
                                        </form>
                                        <form method="post" action="admin_dashboard.php" class="inline ml-2" onsubmit="return confirm('Are you sure you want to reject this user?');">
                                            <input type="hidden" name="user_id" value="<?php echo $reg['user_id']; ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="text-red-600 hover:text-red-800">
                                                <i class="fas fa-times mr-1"></i> Reject
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <!-- more details -->
                                <tr id="details-<?php echo $reg['user_id']; ?>" class="hidden bg-gray-50">
                                    <td colspan="5" class="px-4 py-3 text-sm">
                                        <?php if ($reg['role'] === 'homeowner'): ?>
                                            <p><strong>Address:</strong> <?php echo htmlspecialchars($reg['address']); ?></p>
                                        <?php elseif ($reg['role'] === 'provider'): ?>
                                            <p><strong>Company:</strong> <?php echo htmlspecialchars($reg['company_name']); ?></p>
                                            <p><strong>Services:</strong> <?php echo htmlspecialchars($reg['services']); ?></p>
                                            <p><strong>Location:</strong> <?php echo htmlspecialchars($reg['location']); ?></p>
                                            <p><strong>Sustainability Practices:</strong> <?php echo htmlspecialchars($reg['sustainability_practices']); ?></p>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="bg-gray-50 rounded-lg p-8 text-center">
                    <p class="text-gray-600">No pending registrations.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- recents -->
    <div>
        <div class="bg-white rounded-lg shadow-md p-6">
            <h2 class="text-2xl font-semibold mb-4">Recent Activity</h2>
            
            <?php if (!empty($recent_activity)): ?>
                <div class="space-y-4">
                    <?php foreach ($recent_activity as $activity): ?>
                        <div class="border-l-4 
                            <?php echo $activity['type'] === 'Energy Entry' ? 'border-blue-500' : 'border-green-500'; ?> 
                            pl-4 py-1">
                            <p class="font-medium">
                                <?php echo $activity['type'] === 'Energy Entry' 
                                    ? "Energy Entry: " . htmlspecialchars($activity['energy_type']) . " (" . $activity['consumption_value'] . " " . $activity['unit'] . ")"
                                    : "Recommendation: " . htmlspecialchars($activity['title']); ?>
                            </p>
                            <p class="text-sm text-gray-500">
                                By <?php echo htmlspecialchars($activity['user_name'] ?? 'Unknown'); ?> on 
                                <?php echo date('M j, Y', strtotime($activity['date'])); ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-gray-600">No recent activity.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    function viewDetails(userId) {
        const detailsRow = document.getElementById('details-' + userId);
        if (detailsRow) {
            detailsRow.classList.toggle('hidden');
        }
    }
</script>

<?php include 'includes/footer.php'; ?>
