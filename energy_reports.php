<?php
include 'db.php';
session_start();

// is homeowner
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'homeowner') {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success = false;
$errors = array();


$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01'); // Default to start of current month
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d'); // Default to today
$energy_type = isset($_GET['energy_type']) ? $_GET['energy_type'] : 'all';

// data
$sql = "SELECT date, energy_type, consumption_value, unit 
        FROM energy_usage 
        WHERE user_id = '$user_id' 
        AND date BETWEEN '$start_date' AND '$end_date'";
if($energy_type != 'all') {
    $sql .= " AND energy_type = '$energy_type'";
}
$sql .= " ORDER BY date ASC";
$energy_data = $conn->query($sql);

// consumption by type
$sql = "SELECT energy_type, SUM(consumption_value) as total, unit 
        FROM energy_usage 
        WHERE user_id = '$user_id' 
        AND date BETWEEN '$start_date' AND '$end_date' 
        GROUP BY energy_type, unit";
$consumption_by_type = $conn->query($sql);

// daily average
$sql = "SELECT AVG(consumption_value) as avg_daily, unit 
        FROM energy_usage 
        WHERE user_id = '$user_id' 
        AND date BETWEEN '$start_date' AND '$end_date'";
if($energy_type != 'all') {
    $sql .= " AND energy_type = '$energy_type'";
}
$avg_daily = $conn->query($sql)->fetch_assoc();

// peak use
$sql = "SELECT date, consumption_value as peak_usage, unit 
        FROM energy_usage 
        WHERE user_id = '$user_id' 
        AND date BETWEEN '$start_date' AND '$end_date'
        AND consumption_value = (
            SELECT MAX(consumption_value) 
            FROM energy_usage 
            WHERE user_id = '$user_id'
            AND date BETWEEN '$start_date' AND '$end_date'
        )";
if($energy_type != 'all') {
    $sql .= " AND energy_type = '$energy_type'";
}
$peak_usage = $conn->query($sql)->fetch_assoc();

// community average
$sql = "SELECT AVG(consumption_value) as community_avg, unit 
        FROM energy_usage 
        WHERE date BETWEEN '$start_date' AND '$end_date'";
if($energy_type != 'all') {
    $sql .= " AND energy_type = '$energy_type'";
}
$community_avg = $conn->query($sql)->fetch_assoc();

include 'includes/header.php';
?>

<!-- main content -->
<div class="container mx-auto px-4 py-0">
    <div class="bg-white shadow-md rounded-lg p-6 mb-6">
        <h2 class="text-2xl font-bold mb-6">Energy Reports</h2>

        <form method="get" action="" class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                <input type="date" id="start_date" name="start_date" value="<?php echo $start_date; ?>" 
                       class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
            </div>
            <div>
                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                <input type="date" id="end_date" name="end_date" value="<?php echo $end_date; ?>" 
                       class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
            </div>
            <div>
                <label for="energy_type" class="block text-sm font-medium text-gray-700 mb-1">Energy Type</label>
                <select id="energy_type" name="energy_type" 
                        class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="all" <?php echo $energy_type == 'all' ? 'selected' : ''; ?>>All Types</option>
                    <option value="electricity" <?php echo $energy_type == 'electricity' ? 'selected' : ''; ?>>Electricity</option>
                    <option value="gas" <?php echo $energy_type == 'gas' ? 'selected' : ''; ?>>Gas</option>
                    <option value="water" <?php echo $energy_type == 'water' ? 'selected' : ''; ?>>Water</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="w-full bg-primary text-white px-4 py-2 rounded-lg hover:bg-primary/90">
                    Apply Filters
                </button>
            </div>
        </form>

        <!-- summary -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="text-lg font-semibold mb-2">Total Consumption</h3>
                <div class="text-2xl font-bold">
                    <?php 
                    $total = 0;
                    while($row = $consumption_by_type->fetch_assoc()) {
                        $total += $row['total'];
                    }
                    echo number_format($total, 2) . " units";
                    ?>
                </div>
            </div>

            <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="text-lg font-semibold mb-2">Average Daily</h3>
                <div class="text-2xl font-bold">
                    <?php 
                    $avg_daily_value = $avg_daily['avg_daily'] ?? 0;
                    echo number_format($avg_daily_value, 2) . " units";
                    ?>
                </div>
            </div>

            <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="text-lg font-semibold mb-2">Peak Usage</h3>
                <div class="text-2xl font-bold">
                    <?php 
                    
                    // print_r($peak_usage);
                    
                    $peak_usage_value = $peak_usage['peak_usage'] ?? 0;
                    echo number_format($peak_usage_value, 2) . " " . ($peak_usage['unit'] ?? '');
                    ?>
                </div>
                <div class="text-sm text-gray-600">
                    <?php 
                    if (isset($peak_usage['date'])) {
                        echo "on " . date('M d, Y', strtotime($peak_usage['date']));
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="text-lg font-semibold mb-4">Energy Usage Over Time</h3>
                <div style="height: 300px; ">
                    <canvas id="usageChart"></canvas>
                </div>
            </div>
            <div class="bg-gray-50 p-4 rounded-lg">
                <h3 class="text-lg font-semibold mb-4">Consumption by Type</h3>
                <div style="height: 300px;" class="w-full  flex justify-center items-center">
                    <canvas id="typeChart"></canvas>
                </div>
            </div>
        </div>

        <div class="flex h-96 gap-6">

            <!--comparison -->
            <div class="bg-gray-50 p-4 rounded-lg mb-6 h-full">
                <h3 class="text-lg font-semibold mb-4">Community Comparison</h3>
                <div class="h-full flex justify-center items-center">
                    <canvas id="comparisonChart"></canvas>
                </div>
            </div>

            <!-- AI -->
            <div class="bg-green-50 border border-green-200 h-full rounded-lg p-6 w-full">
                <h3 class="text-xl font-semibold mb-4 text-green-800">AI Energy Assistant</h3>
                <div id="aiChat" class="h-64 mb-2 bg-white border border-green-100 rounded-lg p-4 overflow-y-auto">
                    <div id="emptyChatState" class="h-full flex flex-col items-center justify-center">
                        <img src="images/aiLogo.jpg" alt="AI Assistant" class="w-24 h-24 mb-4 rounded-full opacity-90">
                        <p class="text-gray-500 text-center">Start a conversation with your AI Energy Assistant</p>
                    </div>
                    <!-- Chat ke liye placeholder -->
                </div>
                <div class="flex gap-2">
                    <input type="text text-black" id="aiInput" placeholder="Ask about your energy usage..." 
                    class="flex-1 px-4 py-2 border border-green-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500">
                    <button onclick="sendAIMessage()" 
                    class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition duration-200 font-medium shadow-sm">
                        Send
                    </button>
                </div>
            </div>
        </div>

        <?php include 'includes/footer.php'; ?>
    </div>

    </div>
</div>

<script>
    // ai Chat
    function sendAIMessage() {
        const input = document.getElementById('aiInput');
        const message = input.value.trim();
        if (!message) return;

        document.getElementById('emptyChatState').style.display = 'none';

        const chatDiv = document.getElementById('aiChat');
        
        // new msg
        chatDiv.innerHTML += `
            <div class="mb-4">
                <div class="bg-white p-3 rounded-lg mb-2">
                    <strong>You:</strong> ${message}
                </div>
                <div id="aiResponse" class="bg-green-100 p-3 rounded-lg">
                    <strong>AI:</strong> <div class="inline-block animate-spin rounded-full h-4 w-4 border-b-2 border-green-600"></div>
                </div>
            </div>
        `;
        
        chatDiv.scrollTop = chatDiv.scrollHeight;
        input.value = '';

        fetch('get_ai_insights.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                message: message,
                user_id: <?php echo $user_id; ?>,
                start_date: '<?php echo $start_date; ?>',
                end_date: '<?php echo $end_date; ?>',
                energy_type: '<?php echo $energy_type; ?>'
            })
        })
        .then(response => response.json())
        .then(data => {
            // Format AI response
            let formattedResponse = data.response
                .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>') // Bold text
                .replace(/\n/g, '<br>') // Line breaks
                .replace(/\d+\.\s+(.*?)(?=\d+\.|$)/g, '<div class="ml-4 mb-2">$1</div>') // Numbered lists
                .replace(/\*\s+(.*?)(?=\n|$)/g, '<div class="ml-4 mb-2">• $1</div>'); // Bullet points

            // actual response
            const aiResponse = chatDiv.lastElementChild.lastElementChild;
            aiResponse.innerHTML = `<strong>AI:</strong> ${formattedResponse}`;
            
            chatDiv.scrollTop = chatDiv.scrollHeight;
        })
        .catch(error => {
            console.error('Error:', error);
            const aiResponse = chatDiv.lastElementChild.lastElementChild;
            aiResponse.innerHTML = `<strong>AI:</strong> <span class="text-red-600">Error ho gya!</span>`;
        });
    }

    document.getElementById('aiInput').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            sendAIMessage();
        }
    });

    // after everything is loaded
    document.addEventListener('DOMContentLoaded', function() {

        let usageChart = null;
        const usageCtx = document.getElementById('usageChart').getContext('2d');

        let typeChart = null;
        const typeCtx = document.getElementById('typeChart').getContext('2d');

        let comparisonChart = null;
        const comparisonCtx = document.getElementById('comparisonChart').getContext('2d');

        function updateCharts() {
            
            // remove existing charts
            if (usageChart) {
                usageChart.destroy();
            }
            if (typeChart) {
                typeChart.destroy();
            }
            if (comparisonChart) {
                comparisonChart.destroy();
            }

            usageChart = new Chart(usageCtx, {
                type: 'line',
                data: {
                    labels: <?php 
                        $dates = array();
                        $values = array();
                        $energy_data->data_seek(0); // Reset the pointer
                        while($row = $energy_data->fetch_assoc()) {
                            $dates[] = date('M d', strtotime($row['date']));
                            $values[] = $row['consumption_value'];
                        }
                        echo json_encode($dates);
                    ?>,
                    datasets: [{
                        label: 'Energy Usage',
                        data: <?php echo json_encode($values); ?>,
                        borderColor: 'rgb(75, 192, 192)',
                        tension: 0.1
                    }]
                },
            });

            typeChart = new Chart(typeCtx, {
                type: 'pie',
                data: {
                    labels: <?php 
                        $types = array();
                        $totals = array();
                        $consumption_by_type->data_seek(0); // Reset the pointer
                        while($row = $consumption_by_type->fetch_assoc()) {
                            $types[] = ucfirst($row['energy_type']);
                            $totals[] = $row['total'];
                        }
                        echo json_encode($types);
                    ?>,
                    datasets: [{
                        data: <?php echo json_encode($totals); ?>,
                        backgroundColor: [
                            'rgb(255, 99, 132)',
                            'rgb(54, 162, 235)',
                            'rgb(255, 205, 86)'
                        ]
                    }]
                },
            });

            comparisonChart = new Chart(comparisonCtx, {
                type: 'bar',
                data: {
                    labels: ['Your Usage', 'Community Average'],
                    datasets: [{
                        label: 'Energy Consumption',
                        data: [
                            <?php echo $avg_daily['avg_daily'] ?? 0; ?>,
                            <?php echo $community_avg['community_avg'] ?? 0; ?>
                        ],
                        backgroundColor: [
                            'rgb(75, 192, 192)',
                            'rgb(255, 99, 132)'
                        ]
                    }]
                },
            });
        }

        // initial
        updateCharts();
    });
</script>

</body>
</html>
