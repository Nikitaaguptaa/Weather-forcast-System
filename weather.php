<?php
header('Content-Type: application/json');

$apiKey = "YOUR_API_KEY";  // 🔁 Replace with your actual OpenWeatherMap API key
$city = isset($_GET['city']) ? trim($_GET['city']) : '';

if ($city === '') {
    echo json_encode(["error" => "No city provided"]);
    exit;
}

// Create a simple cache file per city
$cacheFile = "cache_" . md5(strtolower($city)) . ".json";
$cacheTime = 600; // 10 minutes in seconds

if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTime)) {
    // Serve from cache
    echo file_get_contents($cacheFile);
    exit;
}

// Call OpenWeatherMap API
$apiUrl = "https://api.openweathermap.org/data/2.5/weather?q=" . urlencode($city) . "&units=metric&appid=" . $apiKey;

$response = @file_get_contents($apiUrl);
if ($response === FALSE) {
    http_response_code(500);
    echo json_encode(["error" => "Failed to fetch data from weather API."]);
    exit;
}

// Save response to cache
file_put_contents($cacheFile, $response);

// Return API result
echo $response;
?>

