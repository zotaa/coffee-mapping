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
        $params[] = $theme;
    }
    
    if ($searchTerm) {
        $query .= " AND nama_cafe LIKE ?";
        $params[] = "%" . $searchTerm . "%";
    }

    $stmt = $conn->prepare($query);
    
    if ($stmt) {
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
    <title>Pemetaan Cafe</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <style>
    body {
            font-family: Arial, sans-serif;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
            background: #f3f4f6;
            color: #333;
        }

    /* Styling Umum */
    body {
        font-family: Arial, sans-serif;
        display: flex;
        flex-direction: column;
        min-height: 100vh;
        background: #f3f4f6;
        color: #333;
        margin: 0;
        padding: 0;
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
       /* Targetkan h1 atau elemen tertentu */
       .hero-section h1 {
        color: white; /* Ubah warna teks menjadi putih */
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5); /* Tambahkan shadow agar lebih terbaca */
    }
    

    .header .logo {
        font-size: 1.5rem;
        font-weight: bold;
        color: #333;
        text-decoration: none;
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
        transition: all 0.3s ease;
    }

    .header .btn:hover {
        background-color: #333;
        color: #fff;
    }

    /* Hero Section - Hilangkan Warna Biru */
    h1 {
        text-align: center;
        padding: 20px;
        font-size: 2rem;
        color: #333;
        margin-bottom: 0;
        background: none; /* Hilangkan warna biru */
    }

    /* Filter Styling */
    #filter-container {
        padding: 10px;
        text-align: center;
        background: #f3f4f6;
    }

        #filter-container {
            padding: 10px;
            text-align: center;
            background: #f3f4f6;
        }

        #themeFilter {
            padding: 12px 16px;
            font-size: 1rem;
            border: 1px solid #ced4da;
            border-radius: 25px;
            background-color: #fff;
            color: #495057;
            cursor: pointer;
            outline: none;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        #themeFilter:hover, #themeFilter:focus {
            border-color: #4a90e2;
            box-shadow: 0 0 8px rgba(74, 144, 226, 0.5);
            background-color: #f8f9fa;
        }

        #searchButton, #cancelButton {
            padding: 10px 20px;
            font-size: 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        #searchButton {
            background-color: #4CAF50;
            color: white;
        }

        #searchButton:hover {
            background-color: #45a049;
            transform: scale(1.05);
        }

        #cancelButton {
            background-color: #f44336;
            color: white;
        }

        #cancelButton:hover {
            background-color: #e53935;
            transform: scale(1.05);
        }
        #map {
            height: calc(100vh - 140px);
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
            z-index: 1000;
        }
    </style>
</head>
<body>
<header class="header">
        <a href="#" class="logo">Coffee <i class="fas fa-mug-hot"></i></a>
        <nav class="navbar">
            <a href="index.html#home">Home</a>
            <a href="index.html#about">About</a>
            <a href="index.html#menu">Statistic</a>
            <a hrefz="index.html#review">Review</a>
            <a href="index.html#book">Feedback</a>
        </nav>
        <a href="index.html#book" class="btn">Go Back</a>
    </header>

    <!-- Welcome Section -->
    <header class="hero-section text-white text-center py-5" style="background: url('welcome-to-ginger-where.jpg') center/cover;">
        <div class="container">
            <h5 class="text-uppercase">Welcome</h5>
            <h1 class="display-4 font-weight-bold">Taste the Difference, Feel the Comfort.</h1>
        </div>
    </header>
    <main>
        <div id="filter-container">
            <label for="themeFilter">Pilih Tema:</label>
            <select id="themeFilter">
                <option value="">--Pilih Tema--</option>
                <option value="MODERN">Modern</option>
                <option value="NATURE">Nature</option>
                <option value="INDUSTRIAL">Industrial</option>
                <option value="BOHEMIAN">Bohemian</option>
                <option value="MINIMALIS">Minimalis</option>
                <option value="VINTAGE">Vintage</option>
            </select>
            <input type="text" id="searchInput" placeholder="Cari nama cafe...">
            <button id="searchButton">Search</button>
            <button id="cancelButton">Cancel</button>
        </div>
        <div id="map"></div>
        <div id="coordinates">Lat: - | Lon: -</div>
    </main>

    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
    <script>
    const map = L.map('map').setView([-6.9175, 107.6191], 12);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    let markers = [];

    // Objek untuk ikon berdasarkan tema
    const themeIcons = {
        'MODERN': L.icon({
            iconUrl: '../coba/image/1.png',
            iconSize: [30, 30],
            iconAnchor: [15, 30],
            popupAnchor: [0, -30]
        }),
        'NATURE': L.icon({
            iconUrl: '../coba/image/2.png',
            iconSize: [30, 30],
            iconAnchor: [15, 30],
            popupAnchor: [0, -30]
        }),
        'INDUSTRIAL': L.icon({
            iconUrl: '../coba/image/3.png',
            iconSize: [30, 30],
            iconAnchor: [15, 30],
            popupAnchor: [0, -30]
        }),
        'BOHEMIAN': L.icon({
            iconUrl: '../coba/image/4.png',
            iconSize: [30, 30],
            iconAnchor: [15, 30],
            popupAnchor: [0, -30]
        }),
        'MINIMALIS': L.icon({
            iconUrl: '../coba/image/5.png',
            iconSize: [30, 30],
            iconAnchor: [15, 30],
            popupAnchor: [0, -30]
        }),
        'VINTAGE': L.icon({
            iconUrl: '../coba/image/6.png',
            iconSize: [30, 30],
            iconAnchor: [15, 30],
            popupAnchor: [0, -30]
        }),
        'DEFAULT': L.icon({
            iconUrl: '../coba/image/7.png',
            iconSize: [30, 30],
            iconAnchor: [15, 30],
            popupAnchor: [0, -30]
        })
    };

    // Fungsi untuk menormalkan tema
    function normalizeTheme(theme) {
        if (theme === 'VINATAGE') return 'VINTAGE';

        return theme;
    }

    function clearMarkers() {
        markers.forEach(marker => map.removeLayer(marker));
        markers = [];
    }

    function fetchLocations(theme = "", search = "") {
        clearMarkers();
        fetch(`map.php?theme=${encodeURIComponent(theme)}&search=${encodeURIComponent(search)}`)
            .then(response => response.json())
            .then(locations => {
                locations.forEach(location => {
                    const lat = parseFloat(location.latitude);
                    const lon = parseFloat(location.longitude);

                    // Normalisasi tema
                    const normalizedTheme = normalizeTheme(location.theme);

                    // Gunakan ikon default jika tema tidak dipilih atau tidak cocok
                    const icon = themeIcons[normalizedTheme] || themeIcons['DEFAULT'];

                    if (!isNaN(lat) && !isNaN(lon)) {
                        const marker = L.marker([lat, lon], { icon })
                            .addTo(map)
                            .bindPopup(`<b>${location.name}</b><br>${location.address}<br>Tema: ${normalizedTheme}`);
                        markers.push(marker);
                    }
                });
            })
            .catch(console.error);
        }

        document.getElementById('themeFilter').addEventListener('change', function () {
            fetchLocations(this.value, document.getElementById('searchInput').value.trim());
        });

        document.getElementById('searchButton').addEventListener('click', function () {
            fetchLocations(document.getElementById('themeFilter').value, document.getElementById('searchInput').value.trim());
        });

        document.getElementById('cancelButton').addEventListener('click', function () {
            clearMarkers();
            document.getElementById('themeFilter').value = '';
            document.getElementById('searchInput').value = '';
        });

        map.on('click', function(e) {
            const lat = e.latlng.lat.toFixed(6);
            const lon = e.latlng.lng.toFixed(6);
            document.getElementById('coordinates').innerHTML = `Lat: ${lat} | Lon: ${lon}`;
        });

        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(position => {
                const { latitude, longitude } = position.coords;
                L.marker([latitude, longitude]).addTo(map).bindPopup('Lokasi Anda').openPopup();
                map.setView([latitude, longitude], 15);
            });
        }
    </script>

</body>
</html>
