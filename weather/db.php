<?php
// config.php - Database connection
$host = 'local';
$dbname = 'weather_db';
$username = 'root';
$password = '12345678';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// get_weather.php - API endpoint
header('Content-Type: application/json');

if (!isset($_GET['city'])) {
    http_response_code(400);
    echo json_encode(['error' => 'City parameter is required']);
    exit;
}

$city = $_GET['city'];

try {
    // Check if city exists in database
    $stmt = $pdo->prepare("SELECT * FROM cities WHERE name = :city");
    $stmt->execute([':city' => $city]);
    $cityData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$cityData) {
        http_response_code(404);
        echo json_encode(['error' => 'City not found']);
        exit;
    }
    
    // Get current weather
    $stmt = $pdo->prepare("SELECT * FROM weather_data WHERE city_id = :city_id ORDER BY timestamp DESC LIMIT 1");
    $stmt->execute([':city_id' => $cityData['id']]);
    $currentWeather = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get forecast
    $stmt = $pdo->prepare("SELECT * FROM forecasts WHERE city_id = :city_id AND date >= CURDATE() ORDER BY date LIMIT 5");
    $stmt->execute([':city_id' => $cityData['id']]);
    $forecast = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Prepare response
    $response = [
        'city' => $cityData['name'],
        'current' => [
            'temperature' => $currentWeather['temperature'],
            'condition' => $currentWeather['condition'],
            'humidity' => $currentWeather['humidity'],
            'windSpeed' => $currentWeather['wind_speed'],
            'pressure' => $currentWeather['pressure'],
            'visibility' => $currentWeather['visibility']
        ],
        'forecast' => []
    ];
    
    foreach ($forecast as $day) {
        $response['forecast'][] = [
            'date' => date('D, M j', strtotime($day['date'])),
            'high' => $day['high_temp'],
            'low' => $day['low_temp'],
            'condition' => $day['condition']
        ];
    }
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}