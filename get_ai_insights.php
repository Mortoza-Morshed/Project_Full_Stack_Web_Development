<?php
include 'db.php';
session_start();

if(!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$data = json_decode(file_get_contents('php://input'), true);

if(!isset($data['message']) || !isset($data['user_id']) || !isset($data['start_date']) || !isset($data['end_date'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters']);
    exit();
}

$user_id = $data['user_id'];
$message = $data['message'];
$start_date = $data['start_date'];
$end_date = $data['end_date'];
$energy_type = $data['energy_type'] ?? 'all';

// Get user info
$sql = "SELECT name, email FROM users WHERE user_id = '$user_id'";
$user_info = $conn->query($sql)->fetch_assoc();

// get energy data
$sql = "SELECT date, energy_type, consumption_value, unit 
        FROM energy_usage 
        WHERE user_id = '$user_id' 
        AND date BETWEEN '$start_date' AND '$end_date'";
if($energy_type != 'all') {
    $sql .= " AND energy_type = '$energy_type'";
}
$sql .= " ORDER BY date ASC";
$energy_data = $conn->query($sql);

// total consumption
$sql = "SELECT 
            energy_type, 
            SUM(consumption_value) as total_consumption,
            unit,
            AVG(consumption_value) as avg_daily
        FROM energy_usage 
        WHERE user_id = '$user_id' 
        AND date BETWEEN '$start_date' AND '$end_date' 
        GROUP BY energy_type, unit";
$consumption_data = $conn->query($sql);

// peak usage
$sql = "SELECT date, MAX(consumption_value) as peak_usage, unit 
        FROM energy_usage 
        WHERE user_id = '$user_id' 
        AND date BETWEEN '$start_date' AND '$end_date'";
if($energy_type != 'all') {
    $sql .= " AND energy_type = '$energy_type'";
}
$peak_usage = $conn->query($sql)->fetch_assoc();

$analysis_data = [
    'user_info' => $user_info,
    'consumption' => [],
    'peak_usage' => $peak_usage,
    'recent_data' => []
];

while($row = $consumption_data->fetch_assoc()) {
    $analysis_data['consumption'][] = [
        'type' => $row['energy_type'],
        'total' => $row['total_consumption'],
        'unit' => $row['unit'],
        'avg_daily' => $row['avg_daily']
    ];
}

// Get recent
$recent_data = [];
while($row = $energy_data->fetch_assoc()) {
    $recent_data[] = [
        'date' => $row['date'],
        'type' => $row['energy_type'],
        'value' => $row['consumption_value'],
        'unit' => $row['unit']
    ];
}
$analysis_data['recent_data'] = array_slice($recent_data, -5);

// API calling
$response = callGeminiAPI($message, $analysis_data);


header('Content-Type: application/json');
echo json_encode(['response' => $response]);

function callGeminiAPI($message, $data) {
    // Key
    $api_key = 'AIzaSyAm50IvIVoVjW44quJ-b_WhlOVYeazHCr4';
    
    // Prompting
    $prompt = "You are an energy efficiency expert specializing in Indian households. Provide concise, practical insights based on this data. Focus only on energy analysis, predictions, and recommendations. Assume typical Indian household patterns and rates.\n\n";
    
    // Add data
    $prompt .= "User: {$data['user_info']['name']} ({$data['user_info']['email']})\n\n";
    $prompt .= "Energy Consumption Summary:\n";
    foreach ($data['consumption'] as $consumption) {
        $prompt .= "- {$consumption['type']}: {$consumption['total']} {$consumption['unit']}\n";
        $prompt .= "  Average daily: {$consumption['avg_daily']} {$consumption['unit']}\n";
    }
    if ($data['peak_usage']) {
        $prompt .= "\nPeak Usage: {$data['peak_usage']['peak_usage']} {$data['peak_usage']['unit']} on {$data['peak_usage']['date']}\n";
    }
    $prompt .= "\nRecent Trends (last 5 entries):\n";
    foreach ($data['recent_data'] as $entry) {
        $prompt .= "- {$entry['date']}: {$entry['value']} {$entry['unit']}\n";
    }
    
    // prompting
    $prompt .= "\nAlways provide a concise response, avoid paragraphs and use bullet points.\n";
    $prompt .= "Keep responses brief and to the point. Focus only on energy-related analysis.\n\n";
    $prompt .= "Keeping all instrcutions in mind, you have to answer the User's question: \"{$message}\"For price,assume indian rates of energy ";
    


    $ch = curl_init();
    
    // Set cURL options
    curl_setopt($ch, CURLOPT_URL, 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . $api_key);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'contents' => [
            [
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ],
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json'
    ]);
    
    $response = curl_exec($ch);
    
    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        return "Error connecting to AI service: " . $error;
    }
    
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // parse
    $response_data = json_decode($response, true);
    
    if ($http_code !== 200) {
        return "Error: API returned status code " . $http_code;
    }
    
    if (isset($response_data['error'])) {
        return "Error: " . $response_data['error']['message'];
    }
    
    if (isset($response_data['candidates'][0]['content']['parts'][0]['text'])) {
        return $response_data['candidates'][0]['content']['parts'][0]['text'];
    } else {
        return "Unable to generate response. Please try again.";
    }
} 