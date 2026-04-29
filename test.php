<?php
include 'config.php';

function getLocationsByThemeOrSearch($theme = null, $searchTerm = null) {
    global $conn;
    $query = "SELECT id, nama_cafe, alamat, latitude, longitude, tema 
              FROM cafe_map
              WHERE 1=1";
    
    $params = [];
    
    if ($theme) {
        $query .= " AND tema = ?";
        $params[] = $theme; // Filter berdasarkan tema
    }
    
    if ($searchTerm) {
        $query .= " AND nama_cafe LIKE ?";
        $params[] = "%" . $searchTerm . "%"; // Filter pencarian nama cafe
    }

    $stmt = $conn->prepare($query);
    
    if ($stmt) {
        // Bind parameter jika ada
        if (count($params) > 0) {
            $types = str_repeat('s', count($params));
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $locations = [];
        while ($row = $result->fetch_assoc()) {
            $locations[] = [
                'id' => $row['id'],
                'name' => $row['nama_cafe'],
                'address' => $row['alamat'],
                'latitude' => str_replace(',', '.', $row['latitude']),
                'longitude' => str_replace(',', '.', $row['longitude']),
                'theme' => $row['tema']
            ];
        }
        return $locations;
    } else {
        return [];
    }
}

if (isset($_GET['theme']) || isset($_GET['search'])) {
    $theme = $_GET['theme'] ?? null;
    $searchTerm = $_GET['search'] ?? null;
    $locations = getLocationsByThemeOrSearch($theme, $searchTerm);
    echo json_encode($locations);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TUBES PEMETAAN</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    
    <!-- jQuery -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
/* Header Styling */
.header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 50px;
            background-color: #fff;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .header .logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: #333;
            display: flex;
            align-items: center;
        }

        .header .logo i {
            margin-left: 5px;
            font-size: 1.2rem;
        }

        .header .navbar a {
            text-decoration: none;
            color: #333;
            margin: 0 15px;
            font-size: 1rem;
            font-weight: 500;
        }

        .header .navbar a:hover {
            color: #5B99C2;
        }

        .header .btn {
            padding: 10px 20px;
            border: 2px solid #333;
            border-radius: 5px;
            text-decoration: none;
            color: #333;
            font-weight: 500;
        }

        .header .btn:hover {
            background-color: #333;
            color: #fff;
        }

        /* Welcome Section */
        .hero-section {
            text-align: center;
            color: white;
            padding: 100px 0;
            background: url('welcome-to-ginger-where.jpg') center/cover no-repeat;
        }

        .hero-section h5 {
            font-size: 18px;
            font-weight: bold;
        }

        .hero-section h1 {
            font-size: 3rem;
            font-weight: bold;
        }

        /* Map Container */
        #map {
            height: 500px;
            margin-top: 20px;
        }

        /* Filter Styling */
        #filter-container {
            margin: 20px auto;
            text-align: center;
        }

        #no-data-alert {
            display: none;
            color: red;
            font-weight: bold;
        }

        #coordinates {
            margin-top: 10px;
            text-align: center;
        }
        body {
            font-family: Arial, sans-serif;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background: #f3f4f6;
            color: #333;
        }

        h1 {
            text-align: center;
            padding: 20px;
            background: #4a90e2;
            color: white;
            font-size: 1.5em;
            margin-bottom: 0;
        }

        #filter-container {
            padding: 10px;
            text-align: center;
            background: #f3f4f6;
        }

        #categoryFilter, #searchInput {
            padding: 10px;
            font-size: 1em;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #fff;
            color: #4a90e2;
            width: 200px;
            margin: 10px;
            cursor: pointer;
        }

        #map {
            flex-grow: 1;
            height: calc(100vh - 140px); /* Adjust height of map */
            width: 100%;
        }

        #coordinates {
            background: rgba(255, 255, 255, 0.8);
            position: fixed;
            bottom: 20px;
            left: 20px;
            padding: 10px;
            border-radius: 8px;
            font-size: 14px;
            color: #333;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.2);
            z-index: 1000; /* Ensure it is above map elements */
        }

        .leaflet-control-zoom {
            left: 20px !important; /* Move to left */
            bottom: 60px !important; /* Position above the coordinates div */
        }

        #gmap-link {
            display: block;
            margin-top: 10px;
            font-size: 14px;
            color: blue;
            text-decoration: underline;
            cursor: pointer;
        }

        #no-data-alert {
            color: red;
            display: none;
            margin: 10px;
        }

        /* Additional Styling for Buttons */
        #searchButton, #cancelButton {
            padding: 10px 20px;
            font-size: 1em;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s, color 0.3s, transform 0.2s;
        }

        /* Search Button Styling */
        #searchButton {
            background-color: #4CAF50;
            color: white;
        }

        #searchButton:hover {
            background-color: #45a049;
            transform: scale(1.05);
        }

        /* Cancel Button Styling */
        #cancelButton {
            background-color: #f44336;
            color: white;
            margin-left: 5px;
        }

        #cancelButton:hover {
            background-color: #e53935;
            transform: scale(1.05);
        }
    </style>
</head>
<body>
    <!-- HEADER -->
    <header class="header">
        <a href="#" class="logo">Coffee <i class="fas fa-mug-hot"></i></a>
        <nav class="navbar">
            <a href="index.html#home">Home</a>
            <a href="index.html#about">About</a>
            <a href="index.html#menu">Menu</a>
            <a href="index.html#review">Review</a>
            <a href="index.html#book">Book</a>
        </nav>
        <a href="index.html#book" class="btn">Book A Table</a>
    </header>

    <!-- Welcome Section -->
    <header class="hero-section text-white text-center py-5" style="background: url('welcome-to-ginger-where.jpg') center/cover;">
        <div class="container">
            <h5 class="text-uppercase">Welcome</h5>
            <h1 class="display-4 font-weight-bold">Taste the Difference, Feel the Comfort.</h1>
        </div>
    </header>

    <div class="container">
        <div id="filter-container">
            <label for="themeFilter">Pilih Tema:</label>
            <select id="themeFilter">
                <option value="">--Pilih Tema--</option>
                <option value="MODERN">MODERN</option>
                <option value="NATURE">NATURE</option>
                <option value="INDUSTRIAL">INDUSTRIAL</option>
                <option value="BOHEMIAN">BOHEMIAN</option>
                <option value="MINIMALIS">MINIMALIS</option>
            </select>
            <input type="text" id="searchInput" placeholder="Cari nama cafe...">
            <button id="searchButton" class="btn btn-primary">Search</button>
            <button id="cancelButton" class="btn btn-danger">Cancel</button>
            <div id="no-data-alert">Data tidak ditemukan</div>
        </div>
        <div id="map"></div>
        <div id="coordinates">Lat: - | Lon: -</div>
    </div>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script>
        var map = L.map('map').setView([-6.9175, 107.6191], 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors'
        }).addTo(map);

        var markers = [];

        function clearMarkers() {
            markers.forEach(marker => map.removeLayer(marker));
            markers = [];
        }

        function fetchLocations(theme = "", search = "") {
            clearMarkers();
            $("#no-data-alert").hide();
            fetch(map.php?theme=${encodeURIComponent(theme)}&search=${encodeURIComponent(search)})
                .then(response => response.json())
                .then(locations => {
                    if (locations.length === 0) {
                        $("#no-data-alert").show();
                    } else {
                        locations.forEach(location => {
                            var lat = parseFloat(location.latitude);
                            var lon = parseFloat(location.longitude);

                            if (!isNaN(lat) && !isNaN(lon)) {
                                var marker = L.marker([lat, lon])
                                    .addTo(map)
                                    .bindPopup(
                                        "<b>" + location.name + "</b><br>" +
                                        location.address + "<br>" +
                                        "Tema: " + location.theme + "<br>" +
                                        "<a href='https://www.google.com/maps/dir/?api=1&destination=" + lat + "," + lon + "' target='_blank'>Get Directions</a>"
                                    );

                                marker.on('click', function() {
                                    document.getElementById('coordinates').innerHTML = "Lat: " + lat.toFixed(6) + " Lon: " + lon.toFixed(6);
                                });

                                markers.push(marker);
                            }
                        });
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        document.getElementById('themeFilter').addEventListener('change', function() {
            fetchLocations(this.value);
        });

        document.getElementById('searchButton').addEventListener('click', function() {
            var searchInput = document.getElementById('searchInput').value.trim();
            var theme = document.getElementById('themeFilter').value;
            fetchLocations(theme, searchInput);
        });

        document.getElementById('cancelButton').addEventListener('click', function() {
            clearMarkers();
            $("#searchInput").val('');
            $("#themeFilter").val('');
            $("#no-data-alert").hide();
        });

        // Display Latitude and Longitude when the user clicks on the map
        map.on('click', function(e) {
            var lat = e.latlng.lat.toFixed(6);
            var lon = e.latlng.lng.toFixed(6);
            document.getElementById('coordinates').innerHTML = "Lat: " + lat + " Lon: " + lon;
        });

        // Geolocation to show current position
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function(position) {
                var lat = position.coords.latitude;
                var lon = position.coords.longitude;

                // Add marker for current location
                var currentLocationMarker = L.marker([lat, lon]).addTo(map)
                    .bindPopup("Lokasi Saat Ini").openPopup();

                // Center map to current location
                map.setView([lat, lon], 15);
            }, function(error) {
                console.error("Error getting location: ", error);
            });
        } else {
            console.error("Geolocation is not supported by this browser.");
        }

    </script>
</body>
</html>