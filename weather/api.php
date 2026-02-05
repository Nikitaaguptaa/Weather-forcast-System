<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Content-Type');

$servername = "localhost";
$username = "root";
$password = "12345678";
$dbname = "weather_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle different API requests
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'get_weather':
        getWeather($conn);
        break;
    case 'save_weather':
        saveWeather($conn);
        break;
    default:
        echo json_encode(['error' => 'Invalid action']);
        break;
}

function getWeather($conn) {
    $city = $_GET['city'] ?? '';
    
    if (empty($city)) {
        echo json_encode(['error' => 'City parameter is required']);
        return;
    }
    
    // Check if we have recent data in database (within last hour)
    $stmt = $conn->prepare("SELECT * FROM weather_data WHERE city = ? AND created_at >= NOW() - INTERVAL 1 HOUR ORDER BY created_at DESC LIMIT 1");
    $stmt->bind_param("s", $city);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        echo json_encode($row);
    } else {
        // If no recent data in DB, fetch from external API
        fetchFromExternalAPI($city, $conn);
    }
}

function fetchFromExternalAPI($city, $conn) {
    // Using OpenWeatherMap API (you need to get your own API key)
    $apiKey = "your_openweathermap_api_key";
    $url = "http://api.openweathermap.org/data/2.5/weather?q=" . urlencode($city) . "&appid=" . $apiKey . "&units=metric";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($response, true);
    
    if ($data['cod'] == 200) {
        // Save to database
        $stmt = $conn->prepare("INSERT INTO weather_data (city, country_code, temperature, humidity, wind_speed, description, icon, forecast_date) VALUES (?, ?, ?, ?, ?, ?, ?, CURDATE())");
        $stmt->bind_param("ssddsss", 
            $data['name'], 
            $data['sys']['country'],
            $data['main']['temp'],
            $data['main']['humidity'],
            $data['wind']['speed'],
            $data['weather'][0]['description'],
            $data['weather'][0]['icon']
        );
        $stmt->execute();
        
        // Return the data
        echo json_encode([
            'city' => $data['name'],
            'country_code' => $data['sys']['country'],
            'temperature' => $data['main']['temp'],
            'humidity' => $data['main']['humidity'],
            'wind_speed' => $data['wind']['speed'],
            'description' => $data['weather'][0]['description'],
            'icon' => $data['weather'][0]['icon']
        ]);
    } else {
        echo json_encode(['error' => 'City not found']);
    }
}

function saveWeather($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $stmt = $conn->prepare("INSERT INTO weather_data (city, country_code, temperature, humidity, wind_speed, description, icon, forecast_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssddssss", 
        $data['city'], 
        $data['country_code'],
        $data['temperature'],
        $data['humidity'],
        $data['wind_speed'],
        $data['description'],
        $data['icon'],
        $data['forecast_date']
    );
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Failed to save data']);
    }
}

$conn->close();
?>