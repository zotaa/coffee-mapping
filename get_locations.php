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
        if (count($params) > 0) {
            $types = str_repeat('s', count($params));
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        $locations = [];
        while($row = $result->fetch_assoc()) {
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
