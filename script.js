
  document.getElementById("getWeatherBtn").addEventListener("click", async () => {
    const city = document.getElementById("cityInput").value.trim();
    const weatherCard = document.getElementById("weatherCard");
    const errorMsg = document.getElementById("errorMsg");

    // Hide card and clear error at each request
    weatherCard.style.display = "none";
    errorMsg.textContent = "";

    if (!city) {
      errorMsg.textContent = "Please enter a city name.";
      return;
    }

    try {
      const response = await fetch(`weather.php?city=${encodeURIComponent(city)}`);
      
      if (!response.ok) throw new Error("City not found or server error.");

      const data = await response.json();

      // Fill the weather card
      document.getElementById("locationName").textContent = `${data.name}, ${data.sys.country}`;
      document.getElementById("temperature").textContent = data.main.temp;
      document.getElementById("condition").textContent = data.weather[0].description;
      document.getElementById("humidity").textContent = data.main.humidity;

      weatherCard.style.display = "block";

    } catch (error) {
      errorMsg.textContent = "⚠️ Could not fetch weather. Check the city name or try again later.";
      console.error("Weather Fetch Error:", error);
    }
  });

