<?php
// Enable error logging for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in production
ini_set('log_errors', 1);
ini_set('error_log', '/tmp/php_errors.log');

// Session configuration
session_save_path('/var/lib/php/sessions');
ini_set('session.gc_probability', 1);
session_start();

// Dynamic path resolution for connection.php
$possible_paths = [
    __DIR__ . '/../connection.php',
    __DIR__ . '/connection.php',
    __DIR__ . '/../../connection.php',
    '/var/www/myphpapp/connection.php'
];

$connection_loaded = false;
foreach ($possible_paths as $path) {
    if (file_exists($path)) {
        include $path;
        $connection_loaded = true;
        break;
    }
}

if (!$connection_loaded) {
    error_log("Error: connection.php not found. Tried paths: " . implode(', ', $possible_paths));
    die("Database configuration file not found. Please contact administrator.");
}

// Get hotel ID from URL parameter
$hotel_id = isset($_GET['hotel_id']) ? $_GET['hotel_id'] : 'H0001';

// Validate hotel_id format
if (!preg_match('/^H\d{4}$/', $hotel_id)) {
    header("Location: index.php");
    exit();
}

// Function to process booking
function processBooking($hotel_id, $rooms) {
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Please log in to make a booking.'
        ]);
        exit();
    }

    $user_id = $_SESSION['user_id'];
    $room_id = $_POST['room_type'] ?? '';
    $check_in = $_POST['check_in'] ?? '';
    $check_out = $_POST['check_out'] ?? '';
    $payment_method = $_POST['payment_method'] ?? '';
    $special_request = $_POST['special_request'] ?? null;

    // Validate all required inputs
    if (empty($room_id) || empty($check_in) || empty($check_out) || empty($payment_method)) {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'All fields are required.'
        ]);
        exit();
    }

    // Validate dates
    try {
        $check_in_date = new DateTime($check_in);
        $check_out_date = new DateTime($check_out);
        $today = new DateTime('today');
        
        if ($check_in_date < $today) {
            throw new Exception('Check-in date cannot be in the past.');
        }
        
        if ($check_out_date <= $check_in_date) {
            throw new Exception('Check-out date must be after check-in date.');
        }
    } catch (Exception $e) {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
        exit();
    }

    // Calculate nights and find room price
    $nights = $check_in_date->diff($check_out_date)->days;
    $room_price = 0;
    $room_found = false;
    
    foreach ($rooms as $room) {
        if ($room['room_id'] === $room_id) {
            $room_price = floatval($room['price']);
            $room_found = true;
            break;
        }
    }
    
    if (!$room_found || $room_price <= 0) {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Invalid room selected.'
        ]);
        exit();
    }
    
    $total_price = $nights * $room_price;

    // Get database connection
    try {
        $conn = getConnection();
    } catch (Exception $e) {
        error_log("Database connection failed: " . $e->getMessage());
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Database connection failed. Please try again later.'
        ]);
        exit();
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Get next booking_id
        $query_max_booking = "SELECT MAX(CAST(SUBSTRING(booking_id, 2) AS UNSIGNED)) AS max_id FROM booking";
        $result_max_booking = $conn->query($query_max_booking);
        if (!$result_max_booking) {
            throw new Exception("Failed to generate booking ID");
        }
        $row_max_booking = $result_max_booking->fetch_assoc();
        $next_booking_num = ($row_max_booking['max_id'] ?? 0) + 1;
        $booking_id = 'B' . str_pad($next_booking_num, 4, '0', STR_PAD_LEFT);

        // Get next detail_id
        $query_max_detail = "SELECT MAX(CAST(SUBSTRING(detail_id, 2) AS UNSIGNED)) AS max_id FROM booking_detail";
        $result_max_detail = $conn->query($query_max_detail);
        if (!$result_max_detail) {
            throw new Exception("Failed to generate detail ID");
        }
        $row_max_detail = $result_max_detail->fetch_assoc();
        $next_detail_num = ($row_max_detail['max_id'] ?? 0) + 1;
        $detail_id = 'D' . str_pad($next_detail_num, 4, '0', STR_PAD_LEFT);

        // Get next payment_id
        $query_max_payment = "SELECT MAX(CAST(SUBSTRING(payment_id, 2) AS UNSIGNED)) AS max_id FROM payment";
        $result_max_payment = $conn->query($query_max_payment);
        if (!$result_max_payment) {
            throw new Exception("Failed to generate payment ID");
        }
        $row_max_payment = $result_max_payment->fetch_assoc();
        $next_payment_num = ($row_max_payment['max_id'] ?? 0) + 1;
        $payment_id = 'P' . str_pad($next_payment_num, 4, '0', STR_PAD_LEFT);

        // Insert booking
        $stmt = $conn->prepare("INSERT INTO booking (booking_id, user_id, hotel_id, status) VALUES (?, ?, ?, 'confirmed')");
        if (!$stmt) {
            throw new Exception("Failed to prepare booking statement: " . $conn->error);
        }
        $stmt->bind_param("sss", $booking_id, $user_id, $hotel_id);
        if (!$stmt->execute()) {
            throw new Exception("Failed to insert booking: " . $stmt->error);
        }
        $stmt->close();

        // Insert booking detail
        $stmt = $conn->prepare("INSERT INTO booking_detail (detail_id, booking_id, room_id, check_in, check_out, price_per_night, total_price, special_request) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            throw new Exception("Failed to prepare booking detail statement: " . $conn->error);
        }
        $stmt->bind_param("sssssdds", $detail_id, $booking_id, $room_id, $check_in, $check_out, $room_price, $total_price, $special_request);
        if (!$stmt->execute()) {
            throw new Exception("Failed to insert booking detail: " . $stmt->error);
        }
        $stmt->close();

        // Insert payment
        $stmt = $conn->prepare("INSERT INTO payment (payment_id, booking_id, amount, payment_method, status) VALUES (?, ?, ?, ?, 'paid')");
        if (!$stmt) {
            throw new Exception("Failed to prepare payment statement: " . $conn->error);
        }
        $stmt->bind_param("ssds", $payment_id, $booking_id, $total_price, $payment_method);
        if (!$stmt->execute()) {
            throw new Exception("Failed to insert payment: " . $stmt->error);
        }
        $stmt->close();

        // Commit transaction
        $conn->commit();
        $conn->close();
        
        // Return success response
        http_response_code(200);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'booking_id' => $booking_id,
            'payment_id' => $payment_id,
            'total_price' => $total_price,
            'message' => 'Booking confirmed successfully!'
        ]);
        exit();
        
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        $conn->close();
        
        error_log("Booking error: " . $e->getMessage());
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => 'Booking failed: ' . $e->getMessage()
        ]);
        exit();
    }
}

// Handle POST request for booking
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $contentType = isset($_SERVER['CONTENT_TYPE']) ? $_SERVER['CONTENT_TYPE'] : '';
    
    // Handle JSON request
    if (strpos($contentType, 'application/json') !== false) {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if ($data && isset($data['confirm_payment'])) {
            $_POST = $data;
        }
    }
    
    // Process booking if confirmed
    if (isset($_POST['confirm_payment'])) {
        // Get rooms data first
        $conn = getConnection();
        $query_rooms = "SELECT * FROM room WHERE hotel_id = ? AND availability = 1 ORDER BY price";
        $stmt_rooms = $conn->prepare($query_rooms);
        $stmt_rooms->bind_param("s", $hotel_id);
        $stmt_rooms->execute();
        $result_rooms = $stmt_rooms->get_result();
        $rooms = [];
        while ($row = $result_rooms->fetch_assoc()) {
            $rooms[] = $row;
        }
        $conn->close();
        
        processBooking($hotel_id, $rooms);
    }
}

// Fetch hotel details
try {
    $conn = getConnection();
    
    $query = "SELECT * FROM hotel WHERE hotel_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $hotel_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $hotel = $result->fetch_assoc();
    $stmt->close();
    
    // Redirect if hotel not found
    if (!$hotel) {
        $conn->close();
        header("Location: index.php");
        exit();
    }
    
    // Get available rooms for this hotel
    $query_rooms = "SELECT * FROM room WHERE hotel_id = ? AND availability = 1 ORDER BY price";
    $stmt_rooms = $conn->prepare($query_rooms);
    $stmt_rooms->bind_param("s", $hotel_id);
    $stmt_rooms->execute();
    $result_rooms = $stmt_rooms->get_result();
    $rooms = [];
    while ($row = $result_rooms->fetch_assoc()) {
        $rooms[] = $row;
    }
    $stmt_rooms->close();
    
    // Get reviews for this hotel
    $query_reviews = "SELECT r.*, u.name as user_name 
                      FROM review r 
                      JOIN user u ON r.user_id = u.user_id 
                      WHERE r.hotel_id = ? 
                      ORDER BY r.review_date DESC 
                      LIMIT 5";
    $stmt_reviews = $conn->prepare($query_reviews);
    $stmt_reviews->bind_param("s", $hotel_id);
    $stmt_reviews->execute();
    $result_reviews = $stmt_reviews->get_result();
    $reviews = [];
    while ($row = $result_reviews->fetch_assoc()) {
        $reviews[] = $row;
    }
    $stmt_reviews->close();
    
    $conn->close();
    
    // Calculate minimum price
    $min_price = !empty($rooms) ? min(array_column($rooms, 'price')) : 0;
    
} catch (Exception $e) {
    error_log("Error fetching hotel data: " . $e->getMessage());
    die("Error loading hotel information. Please try again later.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($hotel['hotel_name']); ?> - Details</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        .gallery-thumb {
            transition: transform 0.3s ease;
        }
        .gallery-thumb:hover {
            transform: scale(1.05);
        }
        .sticky-top {
            position: sticky;
            top: 20px;
        }
    </style>
</head>
<body>
    <div class="bg-light min-vh-100">
        <?php 
        // Include header if exists
        $header_path = __DIR__ . '/header.php';
        if (file_exists($header_path)) {
            include 'header.php'; 
        }
        ?>
        
        <!-- Image Gallery -->
        <div class="container mt-4">
            <div class="row g-2">
                <div class="col-md-8">
                    <img
                        id="mainImage"
                        src="<?php echo htmlspecialchars($hotel['image_url']); ?>"
                        alt="<?php echo htmlspecialchars($hotel['hotel_name']); ?>"
                        class="img-fluid w-100 rounded"
                        style="height: 500px; object-fit: cover;"
                    />
                </div>
                <div class="col-md-4">
                    <div class="row g-2">
                        <div class="col-6">
                            <img
                                src="https://images.unsplash.com/photo-1655292912612-bb5b1bda9355?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&w=400"
                                alt="Hotel room"
                                class="img-fluid w-100 rounded gallery-thumb"
                                style="height: 120px; object-fit: cover; cursor: pointer;"
                                onclick="changeImage(this.src)"
                            />
                        </div>
                        <div class="col-6">
                            <img
                                src="https://images.unsplash.com/photo-1570214476695-19bd467e6f7a?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&w=400"
                                alt="Hotel pool"
                                class="img-fluid w-100 rounded gallery-thumb"
                                style="height: 120px; object-fit: cover; cursor: pointer;"
                                onclick="changeImage(this.src)"
                            />
                        </div>
                        <div class="col-6">
                            <img
                                src="https://images.unsplash.com/photo-1543539571-2d88da875d21?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&w=400"
                                alt="Hotel restaurant"
                                class="img-fluid w-100 rounded gallery-thumb"
                                style="height: 120px; object-fit: cover; cursor: pointer;"
                                onclick="changeImage(this.src)"
                            />
                        </div>
                        <div class="col-6">
                            <img
                                src="https://images.unsplash.com/photo-1703135387362-4b749023e1e1?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&w=400"
                                alt="Hotel spa"
                                class="img-fluid w-100 rounded gallery-thumb"
                                style="height: 120px; object-fit: cover; cursor: pointer;"
                                onclick="changeImage(this.src)"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="container my-5">
            <div class="row">
                <!-- Left Column - Hotel Information -->
                <div class="col-lg-8">
                    <!-- Hotel Info -->
                    <div class="bg-white p-4 rounded shadow-sm mb-4">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h1 class="mb-2"><?php echo htmlspecialchars($hotel['hotel_name']); ?></h1>
                                <div class="d-flex align-items-center mb-2">
                                    <div class="me-3">
                                        <?php
                                        $rating = floatval($hotel['star_rating']);
                                        $full_stars = floor($rating);
                                        $half_star = ($rating - $full_stars) >= 0.5 ? 1 : 0;
                                        $empty_stars = 5 - $full_stars - $half_star;
                                        
                                        echo str_repeat('<i class="bi bi-star-fill text-warning"></i>', $full_stars);
                                        if ($half_star) echo '<i class="bi bi-star-half text-warning"></i>';
                                        echo str_repeat('<i class="bi bi-star text-warning"></i>', $empty_stars);
                                        ?>
                                    </div>
                                    <span class="badge bg-primary"><?php echo htmlspecialchars($rating); ?> (<?php echo count($reviews); ?> reviews)</span>
                                </div>
                                <div class="d-flex align-items-center text-muted">
                                    <i class="bi bi-geo-alt me-2"></i>
                                    <span><?php echo htmlspecialchars($hotel['address']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Description -->
                    <div class="bg-white p-4 rounded shadow-sm mb-4">
                        <h3 class="mb-3">About This Hotel</h3>
                        <p>
                            Experience luxury and comfort at <?php echo htmlspecialchars($hotel['hotel_name']); ?>. 
                            Located at <?php echo htmlspecialchars($hotel['address']); ?>, our hotel offers 
                            world-class amenities and exceptional service to make your stay unforgettable.
                        </p>
                        <p>
                            Whether you're traveling for business or leisure, our <?php echo intval($hotel['available_room']); ?> 
                            available rooms provide the perfect retreat. Enjoy modern facilities, comfortable accommodations, 
                            and easy access to local attractions.
                        </p>
                    </div>

                    <!-- Amenities -->
                    <div class="bg-white p-4 rounded shadow-sm mb-4">
                        <h3 class="mb-3">Amenities</h3>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-wifi me-2 text-primary fs-5"></i>
                                    <span>Free WiFi</span>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-water me-2 text-primary fs-5"></i>
                                    <span>Swimming Pool</span>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-cup-hot me-2 text-primary fs-5"></i>
                                    <span><?php echo htmlspecialchars($hotel['add_on'] ?? 'Restaurant'); ?></span>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-car-front me-2 text-primary fs-5"></i>
                                    <span>Free Parking</span>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-people me-2 text-primary fs-5"></i>
                                    <span>24/7 Reception</span>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-shield-check me-2 text-primary fs-5"></i>
                                    <span>Security</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Room Types -->
                    <div class="bg-white p-4 rounded shadow-sm mb-4">
                        <h3 class="mb-3">Available Room Types</h3>
                        <?php if (empty($rooms)): ?>
                            <div class="alert alert-warning">
                                <i class="bi bi-exclamation-triangle me-2"></i>
                                No rooms currently available. Please check back later.
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($rooms as $room): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <h5 class="card-title"><?php echo htmlspecialchars($room['room_type']); ?></h5>
                                                <p class="card-text text-muted">
                                                    Comfortable and spacious room with modern amenities
                                                </p>
                                                <ul class="list-unstyled">
                                                    <li><i class="bi bi-check-circle text-success me-2"></i>Air Conditioning</li>
                                                    <li><i class="bi bi-check-circle text-success me-2"></i>Flat Screen TV</li>
                                                    <li><i class="bi bi-check-circle text-success me-2"></i>Private Bathroom</li>
                                                </ul>
                                                <div class="mt-3">
                                                    <span class="h4 text-primary">Rp <?php echo number_format($room['price'], 0, ',', '.'); ?></span>
                                                    <span class="text-muted"> / night</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Reviews -->
                    <?php if (!empty($reviews)): ?>
                    <div class="bg-white p-4 rounded shadow-sm mb-4">
                        <h3 class="mb-4">Guest Reviews</h3>
                        
                        <?php foreach ($reviews as $index => $review): ?>
                        <div class="mb-4 <?php echo $index < count($reviews) - 1 ? 'pb-4 border-bottom' : ''; ?>">
                            <div class="d-flex align-items-start">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3 flex-shrink-0"
                                     style="width: 50px; height: 50px; font-weight: bold;">
                                    <?php 
                                    $name_parts = explode(' ', $review['user_name']);
                                    echo strtoupper(substr($name_parts[0], 0, 1));
                                    if (isset($name_parts[1])) {
                                        echo strtoupper(substr($name_parts[1], 0, 1));
                                    }
                                    ?>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <div>
                                            <h5 class="mb-1"><?php echo htmlspecialchars($review['user_name']); ?></h5>
                                            <small class="text-muted">
                                                <?php echo date('F d, Y', strtotime($review['review_date'])); ?>
                                            </small>
                                        </div>
                                        <div>
                                            <?php
                                            $review_rating = floatval($review['rating']);
                                            $full = floor($review_rating);
                                            $half = ($review_rating - $full) >= 0.5 ? 1 : 0;
                                            $empty = 5 - $full - $half;
                                            
                                            echo str_repeat('<i class="bi bi-star-fill text-warning"></i>', $full);
                                            if ($half) echo '<i class="bi bi-star-half text-warning"></i>';
                                            echo str_repeat('<i class="bi bi-star text-warning"></i>', $empty);
                                            ?>
                                        </div>
                                    </div>
                                    <p class="mb-0"><?php echo htmlspecialchars($review['comment']); ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Right Column - Booking Card -->
                <div class="col-lg-4">
                    <div class="card shadow-sm sticky-top" style="top: 20px;">
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-4">
                                <div>
                                    <h3 class="mb-0 text-primary">Rp <?php echo number_format($min_price, 0, ',', '.'); ?></h3>
                                    <small class="text-muted">per night</small>
                                </div>
                                <span class="badge bg-success">Available</span>
                            </div>

                            <?php if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']): ?>
                                <?php if (!empty($rooms)): ?>
                                <form id="bookingForm" method="POST">
                                    <div class="mb-3">
                                        <label class="form-label d-flex align-items-center">
                                            <i class="bi bi-house me-2"></i>
                                            Room Type
                                        </label>
                                        <select class="form-control" id="roomType" name="room_type" required>
                                            <option value="">Select Room Type</option>
                                            <?php foreach ($rooms as $room): ?>
                                                <option value="<?php echo htmlspecialchars($room['room_id']); ?>" 
                                                        data-price="<?php echo htmlspecialchars($room['price']); ?>">
                                                    <?php echo htmlspecialchars($room['room_type']); ?> - 
                                                    Rp <?php echo number_format($room['price'], 0, ',', '.'); ?>/night
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label d-flex align-items-center">
                                            <i class="bi bi-calendar me-2"></i>
                                            Check-in
                                        </label>
                                        <input
                                            type="date"
                                            class="form-control"
                                            id="checkIn"
                                            name="check_in"
                                            required
                                        />
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label d-flex align-items-center">
                                            <i class="bi bi-calendar me-2"></i>
                                            Check-out
                                        </label>
                                        <input
                                            type="date"
                                            class="form-control"
                                            id="checkOut"
                                            name="check_out"
                                            required
                                        />
                                    </div>

                                    <div class="mb-3">
                                        <label class="form-label d-flex align-items-center">
                                            <i class="bi bi-chat-left-text me-2"></i>
                                            Special Request (Optional)
                                        </label>
                                        <textarea
                                            class="form-control"
                                            id="specialRequest"
                                            name="special_request"
                                            rows="2"
                                            placeholder="e.g., High floor, quiet room"
                                        ></textarea>
                                    </div>

                                    <div id="priceDisplay" class="mb-3" style="display: none;">
                                        <div class="alert alert-info">
                                            <strong>Total Price: Rp <span id="totalPrice">0</span></strong>
                                            <br><small id="nightsInfo"></small>
                                        </div>
                                    </div>

                                    <button type="submit" class="btn btn-primary w-100 mb-3">
                                        <i class="bi bi-credit-card me-2"></i>
                                        Reserve Now
                                    </button>
                                </form>
                                <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    No rooms available at this time.
                                </div>
                                <?php endif; ?>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-2"></i>
                                    Please <a href="login.php" class="alert-link">login</a> to make a booking.
                                </div>
                            <?php endif; ?>

                            <hr />
                            <div class="alert alert-info mt-3 mb-0" role="alert">
                                <small><i class="bi bi-check-circle me-2"></i>Free cancellation within 24 hours</small><br>
                                <small><i class="bi bi-check-circle me-2"></i>No prepayment needed</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Modal -->
        <div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="paymentModalLabel">
                            <i class="bi bi-credit-card me-2"></i>Confirm Payment
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-success">
                            <h6 class="alert-heading">Booking Summary</h6>
                            <hr>
                            <p class="mb-1"><strong>Hotel:</strong> <?php echo htmlspecialchars($hotel['hotel_name']); ?></p>
                            <p class="mb-1"><strong>Room:</strong> <span id="modalRoomType"></span></p>
                            <p class="mb-1"><strong>Check-in:</strong> <span id="modalCheckIn"></span></p>
                            <p class="mb-1"><strong>Check-out:</strong> <span id="modalCheckOut"></span></p>
                            <p class="mb-1"><strong>Nights:</strong> <span id="modalNights"></span></p>
                            <hr>
                            <p class="mb-0 h5"><strong>Total Price:</strong> Rp <span id="modalTotalPrice"></span></p>
                        </div>
                        
                        <form id="paymentForm">
                            <div class="mb-3">
                                <label class="form-label fw-bold">Payment Method</label>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="payment_method" id="credit" value="credit_card" required>
                                    <label class="form-check-label" for="credit">
                                        <i class="bi bi-credit-card me-2"></i>Credit Card
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="payment_method" id="debit" value="debit_card" required>
                                    <label class="form-check-label" for="debit">
                                        <i class="bi bi-credit-card-2-front me-2"></i>Debit Card
                                    </label>
                                </div>
                                <div class="form-check mb-2">
                                    <input class="form-check-input" type="radio" name="payment_method" id="transfer" value="transfer" required>
                                    <label class="form-check-label" for="transfer">
                                        <i class="bi bi-bank me-2"></i>Bank Transfer
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" id="cash" value="cash" required>
                                    <label class="form-check-label" for="cash">
                                        <i class="bi bi-cash me-2"></i>Cash (Pay at Hotel)
                                    </label>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle me-2"></i>Cancel
                        </button>
                        <button type="button" class="btn btn-success" id="confirmPayment">
                            <i class="bi bi-check-circle me-2"></i>Confirm Payment
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <?php 
        // Include footer if exists
        $footer_path = __DIR__ . '/footer.php';
        if (file_exists($footer_path)) {
            include 'footer.php'; 
        }
        ?>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>

    <!-- Custom JavaScript -->
    <script>
        // Change main image when clicking gallery thumbnails
        function changeImage(src) {
            document.getElementById('mainImage').src = src;
        }

        document.addEventListener('DOMContentLoaded', function() {
            const roomTypeSelect = document.getElementById('roomType');
            const checkInInput = document.getElementById('checkIn');
            const checkOutInput = document.getElementById('checkOut');
            const priceDisplay = document.getElementById('priceDisplay');
            const totalPriceSpan = document.getElementById('totalPrice');
            const nightsInfo = document.getElementById('nightsInfo');
            const bookingForm = document.getElementById('bookingForm');
            const paymentModal = new bootstrap.Modal(document.getElementById('paymentModal'));
            const confirmPaymentBtn = document.getElementById('confirmPayment');

            // Set minimum date to today
            const today = new Date();
            const todayStr = today.toISOString().split('T')[0];
            checkInInput.setAttribute('min', todayStr);
            checkOutInput.setAttribute('min', todayStr);

            // Calculate and display price
            function calculatePrice() {
                const selectedOption = roomTypeSelect.options[roomTypeSelect.selectedIndex];
                const price = parseFloat(selectedOption.getAttribute('data-price')) || 0;
                const checkIn = new Date(checkInInput.value);
                const checkOut = new Date(checkOutInput.value);

                if (price > 0 && checkIn && checkOut && checkOut > checkIn) {
                    const timeDiff = checkOut.getTime() - checkIn.getTime();
                    const nights = Math.ceil(timeDiff / (1000 * 3600 * 24));
                    const total = price * nights;

                    totalPriceSpan.textContent = new Intl.NumberFormat('id-ID').format(total);
                    nightsInfo.textContent = `${nights} night(s) × Rp ${new Intl.NumberFormat('id-ID').format(price)}`;
                    priceDisplay.style.display = 'block';
                } else {
                    priceDisplay.style.display = 'none';
                }
            }

            // Update check-out minimum date when check-in changes
            checkInInput.addEventListener('change', function() {
                const checkInDate = new Date(this.value);
                checkInDate.setDate(checkInDate.getDate() + 1);
                const minCheckOut = checkInDate.toISOString().split('T')[0];
                checkOutInput.setAttribute('min', minCheckOut);
                
                // Reset check-out if it's before new minimum
                if (checkOutInput.value && checkOutInput.value <= this.value) {
                    checkOutInput.value = '';
                }
                
                calculatePrice();
            });

            roomTypeSelect.addEventListener('change', calculatePrice);
            checkOutInput.addEventListener('change', calculatePrice);

            // Handle booking form submission
            if (bookingForm) {
                bookingForm.addEventListener('submit', function(e) {
                    e.preventDefault();

                    const selectedOption = roomTypeSelect.options[roomTypeSelect.selectedIndex];
                    const roomTypeText = selectedOption.text.split(' - ')[0];
                    const checkIn = checkInInput.value;
                    const checkOut = checkOutInput.value;
                    const totalPrice = totalPriceSpan.textContent;
                    const checkInDate = new Date(checkIn);
                    const checkOutDate = new Date(checkOut);

                    // Validation
                    if (!roomTypeSelect.value) {
                        alert('Please select a room type.');
                        return;
                    }

                    if (!checkIn || !checkOut) {
                        alert('Please select check-in and check-out dates.');
                        return;
                    }

                    if (checkOutDate <= checkInDate) {
                        alert('Check-out date must be after check-in date.');
                        return;
                    }

                    const nights = Math.ceil((checkOutDate.getTime() - checkInDate.getTime()) / (1000 * 3600 * 24));

                    // Populate modal with booking details
                    document.getElementById('modalRoomType').textContent = roomTypeText;
                    document.getElementById('modalCheckIn').textContent = new Date(checkIn).toLocaleDateString('id-ID');
                    document.getElementById('modalCheckOut').textContent = new Date(checkOut).toLocaleDateString('id-ID');
                    document.getElementById('modalNights').textContent = nights;
                    document.getElementById('modalTotalPrice').textContent = totalPrice;

                    // Show payment modal
                    paymentModal.show();
                });
            }

            // Handle payment confirmation
            if (confirmPaymentBtn) {
                confirmPaymentBtn.addEventListener('click', function() {
                    const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
                    
                    if (!paymentMethod) {
                        alert('Please select a payment method.');
                        return;
                    }

                    // Disable button and show loading state
                    confirmPaymentBtn.disabled = true;
                    const originalText = confirmPaymentBtn.innerHTML;
                    confirmPaymentBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';

                    // Prepare booking data
                    const bookingData = {
                        room_type: roomTypeSelect.value,
                        check_in: checkInInput.value,
                        check_out: checkOutInput.value,
                        payment_method: paymentMethod.value,
                        special_request: document.getElementById('specialRequest')?.value || '',
                        confirm_payment: '1'
                    };

                    // Send booking request
                    fetch(window.location.href, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(bookingData)
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            // Hide modal
                            paymentModal.hide();
                            
                            // Show success message
                            const successMessage = `
                                <div class="alert alert-success alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3" 
                                     style="z-index: 9999; min-width: 400px;" role="alert">
                                    <h5 class="alert-heading"><i class="bi bi-check-circle me-2"></i>Booking Successful!</h5>
                                    <hr>
                                    <p class="mb-1"><strong>Booking ID:</strong> ${data.booking_id}</p>
                                    <p class="mb-1"><strong>Payment ID:</strong> ${data.payment_id}</p>
                                    <p class="mb-0">Your booking has been confirmed. Check your email for details.</p>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            `;
                            document.body.insertAdjacentHTML('afterbegin', successMessage);
                            
                            // Reset form after 3 seconds and reload
                            setTimeout(() => {
                                window.location.reload();
                            }, 3000);
                        } else {
                            throw new Error(data.error || 'Booking failed');
                        }
                    })
                    .catch(error => {
                        console.error('Booking error:', error);
                        
                        // Show error message
                        const errorMessage = `
                            <div class="alert alert-danger alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3" 
                                 style="z-index: 9999; min-width: 400px;" role="alert">
                                <h5 class="alert-heading"><i class="bi bi-exclamation-triangle me-2"></i>Booking Failed</h5>
                                <p class="mb-0">${error.message}</p>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        `;
                        document.body.insertAdjacentHTML('afterbegin', errorMessage);
                        
                        // Re-enable button
                        confirmPaymentBtn.disabled = false;
                        confirmPaymentBtn.innerHTML = originalText;
                    });
                });
            }
        });
    </script>
</body>
</html>