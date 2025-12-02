<?php
// admin/student_location_tracker.php
require_once '../db.php';
checkRole(['admin']);

$user = getCurrentUser();

// Get all students with their latest location data
$students_query = "SELECT s.*, d.dept_name, c.class_name, c.section,
                   sl.latitude, sl.longitude, sl.accuracy, sl.timestamp as last_location_time,
                   sl.address, sl.battery_level, sl.is_online
                   FROM students s
                   LEFT JOIN departments d ON s.department_id = d.id
                   LEFT JOIN classes c ON s.class_id = c.id
                   LEFT JOIN student_locations sl ON s.id = sl.student_id
                   WHERE s.is_active = 1
                   ORDER BY sl.timestamp DESC";
$students = $conn->query($students_query);

// Get statistics
$total_students = $students->num_rows;
$online_count = 0;
$offline_count = 0;
$locations_today = 0;

$students->data_seek(0);
while ($row = $students->fetch_assoc()) {
    if ($row['is_online']) $online_count++;
    else $offline_count++;
    if ($row['last_location_time'] && date('Y-m-d', strtotime($row['last_location_time'])) == date('Y-m-d')) {
        $locations_today++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Live Location Tracker - Admin</title>
    <link rel="icon" href="../Nit_logo.png" type="image/svg+xml" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%); 
            min-height: 100vh; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
        }
        
        .navbar { 
            background: rgba(26, 31, 58, 0.95); 
            backdrop-filter: blur(20px); 
            padding: 20px 40px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3); 
            position: sticky; 
            top: 0; 
            z-index: 1000; 
        }
        
        .navbar h1 { color: white; font-size: 24px; font-weight: 700; }
        .user-info { display: flex; align-items: center; gap: 25px; color: white; }
        .btn { 
            padding: 12px 24px; 
            border-radius: 12px; 
            text-decoration: none; 
            font-weight: 600; 
            transition: all 0.3s; 
            display: inline-block; 
            border: none; 
            cursor: pointer; 
        }
        .btn-secondary { background: #6c757d; color: white; }
        .btn-danger { 
            background: linear-gradient(135deg, #ff6b6b, #ee5a5a); 
            color: white; 
            box-shadow: 0 4px 15px rgba(255, 107, 107, 0.4); 
        }
        
        .main-content { padding: 40px; max-width: 1800px; margin: 0 auto; }
        
        .page-header { 
            background: rgba(255, 255, 255, 0.95); 
            backdrop-filter: blur(20px); 
            padding: 30px 40px; 
            border-radius: 20px; 
            margin-bottom: 30px; 
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2); 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
        }
        
        .page-header h2 { 
            font-size: 32px; 
            background: linear-gradient(135deg, #667eea, #764ba2); 
            -webkit-background-clip: text; 
            -webkit-text-fill-color: transparent; 
        }
        
        .stats-grid { 
            display: grid; 
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); 
            gap: 20px; 
            margin-bottom: 30px; 
        }
        
        .stat-card { 
            background: rgba(255, 255, 255, 0.95); 
            backdrop-filter: blur(20px); 
            padding: 25px; 
            border-radius: 20px; 
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15); 
            display: flex; 
            align-items: center; 
            gap: 20px; 
            transition: all 0.3s; 
        }
        
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 15px 50px rgba(0, 0, 0, 0.25); }
        
        .stat-icon { 
            width: 70px; 
            height: 70px; 
            border-radius: 15px; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            font-size: 32px; 
            color: white; 
        }
        
        .stat-info h4 { color: #666; font-size: 14px; margin-bottom: 8px; }
        .stat-value { font-size: 36px; font-weight: 800; color: #2c3e50; }
        
        .map-container { 
            background: rgba(255, 255, 255, 0.95); 
            backdrop-filter: blur(20px); 
            border-radius: 20px; 
            padding: 30px; 
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2); 
            margin-bottom: 30px; 
        }
        
        #map { 
            height: 600px; 
            border-radius: 15px; 
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1); 
        }
        
        .students-list { 
            background: rgba(255, 255, 255, 0.95); 
            backdrop-filter: blur(20px); 
            border-radius: 20px; 
            padding: 30px; 
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2); 
        }
        
        .search-bar { 
            margin-bottom: 20px; 
            position: relative; 
        }
        
        .search-bar input { 
            width: 100%; 
            padding: 15px 20px 15px 50px; 
            border: 2px solid #e0e0e0; 
            border-radius: 15px; 
            font-size: 14px; 
            transition: all 0.3s; 
        }
        
        .search-bar input:focus { 
            outline: none; 
            border-color: #667eea; 
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); 
        }
        
        .search-bar i { 
            position: absolute; 
            left: 18px; 
            top: 50%; 
            transform: translateY(-50%); 
            color: #999; 
            font-size: 18px; 
        }
        
        .student-card { 
            background: white; 
            border: 2px solid #f0f0f0; 
            border-radius: 15px; 
            padding: 20px; 
            margin-bottom: 15px; 
            display: flex; 
            align-items: center; 
            gap: 20px; 
            transition: all 0.3s; 
            cursor: pointer; 
        }
        
        .student-card:hover { 
            border-color: #667eea; 
            box-shadow: 0 5px 20px rgba(102, 126, 234, 0.2); 
            transform: translateX(5px); 
        }
        
        .student-avatar { 
            width: 60px; 
            height: 60px; 
            border-radius: 50%; 
            object-fit: cover; 
            border: 3px solid #667eea; 
        }
        
        .student-avatar-placeholder { 
            width: 60px; 
            height: 60px; 
            border-radius: 50%; 
            background: linear-gradient(135deg, #667eea, #764ba2); 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            color: white; 
            font-size: 24px; 
            font-weight: 700; 
        }
        
        .student-info { flex: 1; }
        .student-name { font-size: 18px; font-weight: 700; color: #2c3e50; margin-bottom: 5px; }
        .student-details { font-size: 13px; color: #666; }
        
        .location-status { display: flex; align-items: center; gap: 10px; }
        .status-badge { 
            padding: 6px 14px; 
            border-radius: 20px; 
            font-size: 12px; 
            font-weight: 600; 
        }
        
        .badge-online { background: #d4edda; color: #155724; }
        .badge-offline { background: #f8d7da; color: #721c24; }
        
        .location-time { font-size: 12px; color: #999; margin-top: 5px; }
        
        .view-location-btn { 
            padding: 10px 20px; 
            background: linear-gradient(135deg, #667eea, #764ba2); 
            color: white; 
            border: none; 
            border-radius: 10px; 
            cursor: pointer; 
            font-weight: 600; 
            transition: all 0.3s; 
        }
        
        .view-location-btn:hover { 
            transform: scale(1.05); 
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4); 
        }
        
        .refresh-btn { 
            position: fixed; 
            bottom: 30px; 
            right: 30px; 
            width: 60px; 
            height: 60px; 
            border-radius: 50%; 
            background: linear-gradient(135deg, #667eea, #764ba2); 
            border: none; 
            color: white; 
            font-size: 24px; 
            cursor: pointer; 
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4); 
            transition: all 0.3s; 
            animation: pulse 2s infinite; 
        }
        
        .refresh-btn:hover { transform: scale(1.1) rotate(180deg); }
        
        @keyframes pulse { 
            0%, 100% { box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4); } 
            50% { box-shadow: 0 10px 40px rgba(102, 126, 234, 0.6), 0 0 0 10px rgba(102, 126, 234, 0.1); } 
        }
        
        .leaflet-popup-content { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .popup-student-name { font-size: 16px; font-weight: 700; color: #2c3e50; margin-bottom: 8px; }
        .popup-details { font-size: 13px; color: #666; line-height: 1.6; }
        
        @media (max-width: 768px) {
            .navbar { padding: 15px 20px; flex-direction: column; gap: 15px; }
            .main-content { padding: 20px; }
            .page-header { flex-direction: column; gap: 15px; text-align: center; }
            .stats-grid { grid-template-columns: 1fr; }
            #map { height: 400px; }
            .student-card { flex-direction: column; text-align: center; }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div>
            <h1>📍 Student Live Location Tracker</h1>
        </div>
        <div class="user-info">
            <a href="index.php" class="btn btn-secondary">← Back to Dashboard</a>
            <span>👨‍💼 <?php echo htmlspecialchars($user['full_name']); ?></span>
            <a href="../logout.php" class="btn btn-danger">🚪 Logout</a>
        </div>
    </nav>

    <div class="main-content">
        <div class="page-header">
            <h2>🗺️ Real-Time Student Location Tracking</h2>
            <div>
                <span style="color: #28a745; font-weight: 600;">🟢 Live Tracking Active</span>
            </div>
        </div>

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #667eea, #764ba2);">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <h4>Total Students</h4>
                    <div class="stat-value"><?php echo $total_students; ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #28a745, #20c997);">
                    <i class="fas fa-circle"></i>
                </div>
                <div class="stat-info">
                    <h4>Online Now</h4>
                    <div class="stat-value"><?php echo $online_count; ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #dc3545, #c82333);">
                    <i class="fas fa-circle"></i>
                </div>
                <div class="stat-info">
                    <h4>Offline</h4>
                    <div class="stat-value"><?php echo $offline_count; ?></div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #ffc107, #ffb300);">
                    <i class="fas fa-map-marker-alt"></i>
                </div>
                <div class="stat-info">
                    <h4>Locations Today</h4>
                    <div class="stat-value"><?php echo $locations_today; ?></div>
                </div>
            </div>
        </div>

        <!-- Map -->
        <div class="map-container">
            <h3 style="margin-bottom: 20px; color: #2c3e50;"><i class="fas fa-map"></i> Live Location Map</h3>
            <div id="map"></div>
        </div>

        <!-- Students List -->
        <div class="students-list">
            <h3 style="margin-bottom: 20px; color: #2c3e50;"><i class="fas fa-list"></i> Students List</h3>
            
            <div class="search-bar">
                <i class="fas fa-search"></i>
                <input type="text" id="searchInput" placeholder="Search by name, roll number, department..." onkeyup="searchStudents()">
            </div>

            <div id="studentsList">
                <?php 
                $students->data_seek(0);
                while ($student = $students->fetch_assoc()): 
                ?>
                <div class="student-card" onclick="focusStudent(<?php echo $student['latitude'] ?? 0; ?>, <?php echo $student['longitude'] ?? 0; ?>, '<?php echo htmlspecialchars($student['full_name']); ?>')">
                    <?php if (!empty($student['photo']) && file_exists("../uploads/students/" . $student['photo'])): ?>
                        <img src="../uploads/students/<?php echo htmlspecialchars($student['photo']); ?>" 
                             alt="Photo" class="student-avatar">
                    <?php else: ?>
                        <div class="student-avatar-placeholder">
                            <?php echo strtoupper(substr($student['full_name'], 0, 1)); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="student-info">
                        <div class="student-name"><?php echo htmlspecialchars($student['full_name']); ?></div>
                        <div class="student-details">
                            <i class="fas fa-id-card"></i> <?php echo htmlspecialchars($student['roll_number']); ?> • 
                            <i class="fas fa-building"></i> <?php echo htmlspecialchars($student['dept_name']); ?> • 
                            <i class="fas fa-book"></i> <?php echo htmlspecialchars($student['section'] ?? $student['class_name']); ?>
                        </div>
                        <?php if ($student['last_location_time']): ?>
                            <div class="location-time">
                                <i class="fas fa-clock"></i> Last seen: <?php echo date('d M Y, h:i A', strtotime($student['last_location_time'])); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="location-status">
                        <span class="status-badge <?php echo $student['is_online'] ? 'badge-online' : 'badge-offline'; ?>">
                            <?php echo $student['is_online'] ? '🟢 Online' : '🔴 Offline'; ?>
                        </span>
                        <?php if ($student['latitude'] && $student['longitude']): ?>
                            <button class="view-location-btn">
                                <i class="fas fa-map-marker-alt"></i> View on Map
                            </button>
                        <?php else: ?>
                            <span style="color: #999; font-size: 13px;">No location data</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <!-- Refresh Button -->
    <button class="refresh-btn" onclick="refreshData()" title="Refresh Locations">
        <i class="fas fa-sync-alt"></i>
    </button>

    <script>
        // Initialize map centered on college (update coordinates as needed)
        const map = L.map('map').setView([21.1458, 79.0882], 13); // Nagpur coordinates
        
        // Add OpenStreetMap tiles
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 19
        }).addTo(map);

        // Custom marker icons
        const onlineIcon = L.icon({
            iconUrl: 'data:image/svg+xml;base64,' + btoa(`
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="40" height="40">
                    <circle cx="12" cy="12" r="10" fill="#28a745"/>
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" fill="white"/>
                </svg>
            `),
            iconSize: [40, 40],
            iconAnchor: [20, 40],
            popupAnchor: [0, -40]
        });

        const offlineIcon = L.icon({
            iconUrl: 'data:image/svg+xml;base64,' + btoa(`
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="40" height="40">
                    <circle cx="12" cy="12" r="10" fill="#dc3545"/>
                    <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z" fill="white"/>
                </svg>
            `),
            iconSize: [40, 40],
            iconAnchor: [20, 40],
            popupAnchor: [0, -40]
        });

        // Add student markers
        const markers = [];
        <?php 
        $students->data_seek(0);
        while ($student = $students->fetch_assoc()): 
            if ($student['latitude'] && $student['longitude']):
        ?>
        {
            const marker = L.marker([<?php echo $student['latitude']; ?>, <?php echo $student['longitude']; ?>], {
                icon: <?php echo $student['is_online'] ? 'onlineIcon' : 'offlineIcon'; ?>
            }).addTo(map);
            
            marker.bindPopup(`
                <div class="popup-student-name">
                    <?php echo htmlspecialchars($student['full_name']); ?>
                </div>
                <div class="popup-details">
                    <strong>Roll No:</strong> <?php echo htmlspecialchars($student['roll_number']); ?><br>
                    <strong>Department:</strong> <?php echo htmlspecialchars($student['dept_name']); ?><br>
                    <strong>Class:</strong> <?php echo htmlspecialchars($student['section'] ?? $student['class_name']); ?><br>
                    <strong>Status:</strong> <span style="color: <?php echo $student['is_online'] ? '#28a745' : '#dc3545'; ?>">
                        <?php echo $student['is_online'] ? '🟢 Online' : '🔴 Offline'; ?>
                    </span><br>
                    <?php if ($student['address']): ?>
                        <strong>Address:</strong> <?php echo htmlspecialchars($student['address']); ?><br>
                    <?php endif; ?>
                    <?php if ($student['battery_level']): ?>
                        <strong>Battery:</strong> <?php echo $student['battery_level']; ?>%<br>
                    <?php endif; ?>
                    <strong>Last Update:</strong> <?php echo date('d M Y, h:i A', strtotime($student['last_location_time'])); ?>
                </div>
            `);
            
            markers.push({
                marker: marker,
                lat: <?php echo $student['latitude']; ?>,
                lng: <?php echo $student['longitude']; ?>,
                name: '<?php echo htmlspecialchars($student['full_name']); ?>'
            });
        }
        <?php 
            endif;
        endwhile; 
        ?>

        // Search function
        function searchStudents() {
            const input = document.getElementById('searchInput').value.toUpperCase();
            const cards = document.querySelectorAll('.student-card');
            
            cards.forEach(card => {
                const text = card.textContent || card.innerText;
                if (text.toUpperCase().indexOf(input) > -1) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        // Focus on student location
        function focusStudent(lat, lng, name) {
            if (lat && lng) {
                map.setView([lat, lng], 16);
                markers.forEach(m => {
                    if (m.lat === lat && m.lng === lng) {
                        m.marker.openPopup();
                    }
                });
            } else {
                alert('No location data available for this student');
            }
        }

        // Refresh data
        function refreshData() {
            location.reload();
        }

        // Auto-refresh every 30 seconds
        setInterval(refreshData, 30000);

        // Fit map to show all markers
        if (markers.length > 0) {
            const group = L.featureGroup(markers.map(m => m.marker));
            map.fitBounds(group.getBounds().pad(0.1));
        }
    </script>
</body>
</html>