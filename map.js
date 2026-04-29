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

    fetch(`get_locations.php?theme=${encodeURIComponent(theme)}&search=${encodeURIComponent(search)}`)
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
                                location.theme + "<br>" +
                                "<a href='https://www.google.com/maps/dir/?api=1&destination=" + lat + "," + lon + "' target='_blank'>Get Directions</a><br>" +
                                "<a href='https://www.google.com/maps?layer=c&cbll=" + lat + "," + lon + "' target='_blank'>View Street View</a>"
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

// Event listeners for filters
document.getElementById('themeFilter').addEventListener('change', function() {
    fetchLocations(this.value);
});
document.getElementById('searchButton').addEventListener('click', performSearch);
document.getElementById('cancelButton').addEventListener('click', function() {
    clearMarkers();
    $("#searchInput").val('');
    $("#themeFilter").val('');
    $("#no-data-alert").hide();
});
