<?php
session_start();
include '../connection.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../client_side/login.php');
    exit();
}

if (!isset($conn) || $conn === null) {
    die("Database connection failed. Please check connection.php");
}


function getUsers($conn) {
    $query = "SELECT user_id, name, email, role FROM user LIMIT 100";
    $result = $conn->query($query);
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    return $users;
}

// Get Hotels
function getHotels($conn) {
    $query = "SELECT hotel_id, hotel_name, address, phone_no, email, star_rating, available_room, add_on FROM hotel LIMIT 100";
    $result = $conn->query($query);
    $hotels = [];
    while ($row = $result->fetch_assoc()) {
        $hotels[] = $row;
    }
    return $hotels;
}

// Get Rooms
function getRooms($conn) {
    $query = "SELECT r.room_id, h.hotel_name, r.room_type, r.price, r.availability, r.hotel_id
              FROM room r 
              JOIN hotel h ON r.hotel_id = h.hotel_id 
              LIMIT 100";
    $result = $conn->query($query);
    $rooms = [];
    while ($row = $result->fetch_assoc()) {
        $rooms[] = $row;
    }
    return $rooms;
}

// Get Bookings
function getBookings($conn) {
    $query = "SELECT b.booking_id, u.name, h.hotel_name, bd.room_id, bd.check_in, bd.check_out, b.status, bd.special_request
              FROM booking b 
              JOIN user u ON b.user_id = u.user_id 
              JOIN hotel h ON b.hotel_id = h.hotel_id 
              LEFT JOIN booking_detail bd ON b.booking_id = bd.booking_id 
              LIMIT 100";
    $result = $conn->query($query);
    $bookings = [];
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
    return $bookings;
}

// Get Payments
function getPayments($conn) {
    $query = "SELECT payment_id, booking_id, amount, payment_method, payment_date, status 
              FROM payment 
              ORDER BY payment_date DESC 
              LIMIT 100";
    $result = $conn->query($query);
    $payments = [];
    while ($row = $result->fetch_assoc()) {
        $payments[] = $row;
    }
    return $payments;
}

// Get Dashboard Stats
function getDashboardStats($conn) {
    $stats = [];
    
    $result = $conn->query("SELECT COUNT(*) as count FROM user WHERE role='customer'");
    $stats['total_users'] = $result->fetch_assoc()['count'];
    
    $result = $conn->query("SELECT COUNT(*) as count FROM hotel");
    $stats['total_hotels'] = $result->fetch_assoc()['count'];
    
    $result = $conn->query("SELECT COUNT(*) as count FROM room");
    $stats['total_rooms'] = $result->fetch_assoc()['count'];
    
    $result = $conn->query("SELECT COUNT(*) as count FROM booking WHERE status='confirmed'");
    $stats['active_bookings'] = $result->fetch_assoc()['count'];
    
    $result = $conn->query("SELECT SUM(amount) as total FROM payment WHERE status='paid'");
    $stats['total_revenue'] = $result->fetch_assoc()['total'] ?? 0;
    
    return $stats;
}

// Update User
function updateUser($conn, $user_id, $name, $email, $role) {
    $stmt = $conn->prepare("UPDATE user SET name=?, email=?, role=? WHERE user_id=?");
    $stmt->bind_param("ssss", $name, $email, $role, $user_id);
    return $stmt->execute();
}

// Delete User
function deleteUser($conn, $user_id) {
    $stmt = $conn->prepare("DELETE FROM user WHERE user_id=?");
    $stmt->bind_param("s", $user_id);
    return $stmt->execute();
}

// Delete Hotel
function deleteHotel($conn, $hotel_id) {
    $stmt = $conn->prepare("DELETE FROM hotel WHERE hotel_id=?");
    $stmt->bind_param("s", $hotel_id);
    return $stmt->execute();
}

// Delete Room
function deleteRoom($conn, $room_id) {
    $stmt = $conn->prepare("DELETE FROM room WHERE room_id=?");
    $stmt->bind_param("s", $room_id);
    return $stmt->execute();
}

// Cancel Booking
function cancelBooking($conn, $booking_id) {
    $stmt = $conn->prepare("UPDATE booking SET status='cancelled' WHERE booking_id=?");
    $stmt->bind_param("s", $booking_id);
    return $stmt->execute();
}

// Update Payment Status
function updatePaymentStatus($conn, $payment_id, $status) {
    $stmt = $conn->prepare("UPDATE payment SET status=? WHERE payment_id=?");
    $stmt->bind_param("ss", $status, $payment_id);
    return $stmt->execute();
}

// ===== HANDLE ACTIONS =====
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    if ($action == 'delete_user') {
        if (deleteUser($conn, $_POST['user_id'])) {
            $message = 'User berhasil dihapus';
            $message_type = 'success';
        } else {
            $message = 'Gagal menghapus user';
            $message_type = 'error';
        }
    } elseif ($action == 'update_user') {
        if (updateUser($conn, $_POST['user_id'], $_POST['name'], $_POST['email'], $_POST['role'])) {
            $message = 'User berhasil diupdate';
            $message_type = 'success';
        } else {
            $message = 'Gagal update user';
            $message_type = 'error';
        }
    } elseif ($action == 'delete_hotel') {
        if (deleteHotel($conn, $_POST['hotel_id'])) {
            $message = 'Hotel berhasil dihapus';
            $message_type = 'success';
        } else {
            $message = 'Gagal menghapus hotel';
            $message_type = 'error';
        }
    } elseif ($action == 'delete_room') {
        if (deleteRoom($conn, $_POST['room_id'])) {
            $message = 'Kamar berhasil dihapus';
            $message_type = 'success';
        } else {
            $message = 'Gagal menghapus kamar';
            $message_type = 'error';
        }
    } elseif ($action == 'cancel_booking') {
        if (cancelBooking($conn, $_POST['booking_id'])) {
            $message = 'Booking berhasil dibatalkan';
            $message_type = 'success';
        } else {
            $message = 'Gagal membatalkan booking';
            $message_type = 'error';
        }
    } elseif ($action == 'update_payment_status') {
        if (updatePaymentStatus($conn, $_POST['payment_id'], $_POST['status'])) {
            $message = 'Status pembayaran berhasil diupdate';
            $message_type = 'success';
        } else {
            $message = 'Gagal update status pembayaran';
            $message_type = 'error';
        }
    } elseif ($action == 'logout') {
        session_destroy();
        header('Location: login.php');
        exit();
    }
}

// ===== SEARCH FUNCTIONS =====
function searchUsers($conn, $keyword) {
    $keyword = '%' . $conn->real_escape_string($keyword) . '%';
    $query = "SELECT user_id, name, email, role FROM user 
              WHERE name LIKE ? OR email LIKE ? OR user_id LIKE ?
              LIMIT 100";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sss", $keyword, $keyword, $keyword);
    $stmt->execute();
    $result = $stmt->get_result();
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    return $users;
}

function searchHotels($conn, $keyword) {
    $keyword = '%' . $conn->real_escape_string($keyword) . '%';
    $query = "SELECT hotel_id, hotel_name, address, phone_no, email, star_rating, available_room, add_on FROM hotel 
              WHERE hotel_name LIKE ? OR address LIKE ? OR email LIKE ? OR hotel_id LIKE ?
              LIMIT 100";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssss", $keyword, $keyword, $keyword, $keyword);
    $stmt->execute();
    $result = $stmt->get_result();
    $hotels = [];
    while ($row = $result->fetch_assoc()) {
        $hotels[] = $row;
    }
    return $hotels;
}

function searchRooms($conn, $keyword) {
    $keyword = '%' . $conn->real_escape_string($keyword) . '%';
    $query = "SELECT r.room_id, h.hotel_name, r.room_type, r.price, r.availability, r.hotel_id
              FROM room r 
              JOIN hotel h ON r.hotel_id = h.hotel_id 
              WHERE r.room_id LIKE ? OR r.room_type LIKE ? OR h.hotel_name LIKE ?
              LIMIT 100";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sss", $keyword, $keyword, $keyword);
    $stmt->execute();
    $result = $stmt->get_result();
    $rooms = [];
    while ($row = $result->fetch_assoc()) {
        $rooms[] = $row;
    }
    return $rooms;
}

function searchBookings($conn, $keyword) {
    $keyword = '%' . $conn->real_escape_string($keyword) . '%';
    $query = "SELECT b.booking_id, u.name, h.hotel_name, bd.room_id, bd.check_in, bd.check_out, b.status, bd.special_request
              FROM booking b 
              JOIN user u ON b.user_id = u.user_id 
              JOIN hotel h ON b.hotel_id = h.hotel_id 
              LEFT JOIN booking_detail bd ON b.booking_id = bd.booking_id 
              WHERE b.booking_id LIKE ? OR u.name LIKE ? OR h.hotel_name LIKE ?
              LIMIT 100";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sss", $keyword, $keyword, $keyword);
    $stmt->execute();
    $result = $stmt->get_result();
    $bookings = [];
    while ($row = $result->fetch_assoc()) {
        $bookings[] = $row;
    }
    return $bookings;
}

function searchPayments($conn, $keyword) {
    $keyword = '%' . $conn->real_escape_string($keyword) . '%';
    $query = "SELECT payment_id, booking_id, amount, payment_method, payment_date, status 
              FROM payment 
              WHERE payment_id LIKE ? OR booking_id LIKE ?
              ORDER BY payment_date DESC 
              LIMIT 100";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $keyword, $keyword);
    $stmt->execute();
    $result = $stmt->get_result();
    $payments = [];
    while ($row = $result->fetch_assoc()) {
        $payments[] = $row;
    }
    return $payments;
}

// Get data untuk ditampilkan
$search_keyword = isset($_GET['search']) ? $_GET['search'] : '';
$current_page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

if ($search_keyword) {
    // Jika ada search keyword, cari sesuai halaman yang sedang aktif
    switch($current_page) {
        case 'users':
            $users = searchUsers($conn, $search_keyword);
            break;
        case 'hotels':
            $hotels = searchHotels($conn, $search_keyword);
            break;
        case 'rooms':
            $rooms = searchRooms($conn, $search_keyword);
            break;
        case 'bookings':
            $bookings = searchBookings($conn, $search_keyword);
            break;
        case 'payments':
            $payments = searchPayments($conn, $search_keyword);
            break;
        default:
            $users = getUsers($conn);
            $hotels = getHotels($conn);
            $rooms = getRooms($conn);
            $bookings = getBookings($conn);
            $payments = getPayments($conn);
    }
} else {
    // Tampilkan semua data
    $users = getUsers($conn);
    $hotels = getHotels($conn);
    $rooms = getRooms($conn);
    $bookings = getBookings($conn);
    $payments = getPayments($conn);
}

$stats = getDashboardStats($conn);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Hotel Booking</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <aside class="sidebar" id="sidebar">
            <div class="logo">
                <h2>🏨 <span class="logo-text">HotelAdmin</span></h2>
            </div>
            <button class="toggle-btn" id="toggleBtn" onclick="toggleSidebar()">
                ☰
            </button>
            <nav class="nav-menu">
                <a href="?page=dashboard" class="nav-item <?php echo (!isset($_GET['page']) || $_GET['page'] == 'dashboard') ? 'active' : ''; ?>">
                    <span>Dashboard</span>
                </a>
                <a href="?page=users<?php echo $search_keyword ? '&search=' . urlencode($search_keyword) : ''; ?>" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] == 'users') ? 'active' : ''; ?>">
                    <span>Pengguna</span>
                </a>
                <a href="?page=hotels<?php echo $search_keyword ? '&search=' . urlencode($search_keyword) : ''; ?>" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] == 'hotels') ? 'active' : ''; ?>">
                    <span>Hotel</span>
                </a>
                <a href="?page=rooms<?php echo $search_keyword ? '&search=' . urlencode($search_keyword) : ''; ?>" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] == 'rooms') ? 'active' : ''; ?>">
                    <span>Kamar</span>
                </a>
                <a href="?page=bookings<?php echo $search_keyword ? '&search=' . urlencode($search_keyword) : ''; ?>" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] == 'bookings') ? 'active' : ''; ?>">
                    <span>Pemesanan</span>
                </a>
                <a href="?page=payments<?php echo $search_keyword ? '&search=' . urlencode($search_keyword) : ''; ?>" class="nav-item <?php echo (isset($_GET['page']) && $_GET['page'] == 'payments') ? 'active' : ''; ?>">
                    <span>Pembayaran</span>
                </a>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="action" value="logout">
                    <button type="submit" class="nav-item logout" style="background: none; border: none; color: inherit; cursor: pointer; width: 100%; text-align: left; padding: 12px 15px;">
                        <span>Logout</span>
                    </button>
                </form>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Header -->
            <header class="header">
                <div class="header-left">
                </div>
                <div class="header-right">
                    <form method="GET" class="search-form">
                        <input type="hidden" name="page" value="<?php echo $current_page; ?>">
                        <div class="search-box">
                            <input type="text" name="search" placeholder="Cari..." value="<?php echo htmlspecialchars($search_keyword); ?>">
                            <button type="submit">🔍</button>
                        </div>
                    </form>
                    <div class="user-profile">
                        <img src="assets/profile.jpg" alt="Admin">
                        <span><?php echo $_SESSION['name']; ?></span>
                    </div>
                </div>
            </header>

            <!-- Notification Message -->
            <?php if ($message): ?>
            <div class="notification <?php echo $message_type; ?>">
                <?php echo $message; ?>
            </div>
            <?php endif; ?>

            <!-- Search Result Info -->
            <?php if ($search_keyword): ?>
            <div class="notification search-info">
                🔍 Hasil pencarian untuk: <strong>"<?php echo htmlspecialchars($search_keyword); ?>"</strong>
                <a href="?page=<?php echo $current_page; ?>" style="float: right; color: inherit; text-decoration: underline;">Bersihkan</a>
            </div>
            <?php endif; ?>

            <!-- Dashboard Section -->
            <?php if (!isset($_GET['page']) || $_GET['page'] == 'dashboard'): ?>
            <section class="content-section active">
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #3498db;">👥</div>
                        <h3>Total Pengguna</h3>
                        <p class="stat-number"><?php echo $stats['total_users']; ?></p>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #2ecc71;">🏢</div>
                        <h3>Total Hotel</h3>
                        <p class="stat-number"><?php echo $stats['total_hotels']; ?></p>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #e74c3c;">🚪</div>
                        <h3>Total Kamar</h3>
                        <p class="stat-number"><?php echo $stats['total_rooms']; ?></p>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon" style="background: #f39c12;">📅</div>
                        <h3>Pemesanan Aktif</h3>
                        <p class="stat-number"><?php echo $stats['active_bookings']; ?></p>
                    </div>
                </div>
            </section>
            <?php endif; ?>

            <!-- Users Section -->
            <?php if (isset($_GET['page']) && $_GET['page'] == 'users'): ?>
            <section class="content-section active">
                <div class="section-header">
                    <h2>Kelola Pengguna</h2>
                    <button class="btn btn-primary" onclick="alert('Form tambah user bisa ditambahkan')">+ Tambah Pengguna</button>
                </div>
                <div class="card">
                    <table>
                        <thead>
                            <tr>
                                <th>ID User</th>
                                <th>Nama</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['user_id']; ?></td>
                                <td><?php echo $user['name']; ?></td>
                                <td><?php echo $user['email']; ?></td>
                                <td><?php echo $user['role']; ?></td>
                                <td class="action-buttons">
                                    <button class="btn-small btn-edit" onclick="editUser('<?php echo $user['user_id']; ?>', '<?php echo htmlspecialchars($user['name']); ?>', '<?php echo $user['email']; ?>', '<?php echo $user['role']; ?>')">Edit</button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus user ini?');">
                                        <input type="hidden" name="action" value="delete_user">
                                        <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                        <button type="submit" class="btn-small btn-delete">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
            <?php endif; ?>

            <!-- Hotels Section -->
            <?php if (isset($_GET['page']) && $_GET['page'] == 'hotels'): ?>
            <section class="content-section active">
                <div class="section-header">
                    <h2>Kelola Hotel</h2>
                    <button class="btn btn-primary" onclick="alert('Form tambah hotel bisa ditambahkan')">+ Tambah Hotel</button>
                </div>
                <div class="card">
                    <table>
                        <thead>
                            <tr>
                                <th>ID Hotel</th>
                                <th>Nama Hotel</th>
                                <th>Alamat</th>
                                <th>Telepon</th>
                                <th>Email</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($hotels as $hotel): ?>
                            <tr>
                                <td><?php echo $hotel['hotel_id']; ?></td>
                                <td><?php echo $hotel['hotel_name']; ?></td>
                                <td><?php echo $hotel['address']; ?></td>
                                <td><?php echo $hotel['phone_no']; ?></td>
                                <td><?php echo $hotel['email']; ?></td>
                                <td class="action-buttons">
                                    <button class="btn-small btn-edit">Edit</button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus hotel ini?');">
                                        <input type="hidden" name="action" value="delete_hotel">
                                        <input type="hidden" name="hotel_id" value="<?php echo $hotel['hotel_id']; ?>">
                                        <button type="submit" class="btn-small btn-delete">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
            <?php endif; ?>

            <!-- Rooms Section -->
            <?php if (isset($_GET['page']) && $_GET['page'] == 'rooms'): ?>
            <section class="content-section active">
                <div class="section-header">
                    <h2>Kelola Kamar</h2>
                    <button class="btn btn-primary" onclick="alert('Form tambah kamar bisa ditambahkan')">+ Tambah Kamar</button>
                </div>
                <div class="card">
                    <table>
                        <thead>
                            <tr>
                                <th>ID Kamar</th>
                                <th>Hotel</th>
                                <th>Tipe Kamar</th>
                                <th>Harga</th>
                                <th>Ketersediaan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rooms as $room): ?>
                            <tr>
                                <td><?php echo $room['room_id']; ?></td>
                                <td><?php echo $room['hotel_name']; ?></td>
                                <td><?php echo $room['room_type']; ?></td>
                                <td>Rp <?php echo number_format($room['price'], 0, ',', '.'); ?></td>
                                <td>
                                    <?php if ($room['availability']): ?>
                                        <span class="badge available">Tersedia</span>
                                    <?php else: ?>
                                        <span class="badge booked">Terpesan</span>
                                    <?php endif; ?>
                                </td>
                                <td class="action-buttons">
                                    <button class="btn-small btn-edit">Edit</button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus kamar ini?');">
                                        <input type="hidden" name="action" value="delete_room">
                                        <input type="hidden" name="room_id" value="<?php echo $room['room_id']; ?>">
                                        <button type="submit" class="btn-small btn-delete">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
            <?php endif; ?>

            <!-- Bookings Section -->
            <?php if (isset($_GET['page']) && $_GET['page'] == 'bookings'): ?>
            <section class="content-section active">
                <div class="section-header">
                    <h2>Kelola Pemesanan</h2>
                </div>
                <div class="card">
                    <table>
                        <thead>
                            <tr>
                                <th>ID Booking</th>
                                <th>Pengguna</th>
                                <th>Hotel</th>
                                <th>Kamar</th>
                                <th>Check-in</th>
                                <th>Check-out</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td><?php echo $booking['booking_id']; ?></td>
                                <td><?php echo $booking['name']; ?></td>
                                <td><?php echo $booking['hotel_name']; ?></td>
                                <td><?php echo $booking['room_id']; ?></td>
                                <td><?php echo $booking['check_in']; ?></td>
                                <td><?php echo $booking['check_out']; ?></td>
                                <td>
                                    <?php 
                                    if ($booking['status'] == 'confirmed') {
                                        echo '<span class="badge confirmed">Confirmed</span>';
                                    } elseif ($booking['status'] == 'pending') {
                                        echo '<span class="badge pending">Pending</span>';
                                    } else {
                                        echo '<span class="badge delete">Cancelled</span>';
                                    }
                                    ?>
                                </td>
                                <td class="action-buttons">
                                    <button class="btn-small btn-edit" onclick="detailBooking('<?php echo $booking['booking_id']; ?>', '<?php echo htmlspecialchars($booking['name']); ?>', '<?php echo htmlspecialchars($booking['hotel_name']); ?>', '<?php echo $booking['room_id']; ?>', '<?php echo $booking['check_in']; ?>', '<?php echo $booking['check_out']; ?>', '<?php echo $booking['status']; ?>', '<?php echo htmlspecialchars($booking['special_request'] ?? ''); ?>')">Detail</button>
                                    <form method="POST" style="display:inline;" onsubmit="return confirm('Batalkan booking ini?');">
                                        <input type="hidden" name="action" value="cancel_booking">
                                        <input type="hidden" name="booking_id" value="<?php echo $booking['booking_id']; ?>">
                                        <button type="submit" class="btn-small btn-delete">Batalkan</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
            <?php endif; ?>

            <!-- Payments Section -->
            <?php if (isset($_GET['page']) && $_GET['page'] == 'payments'): ?>
            <section class="content-section active">
                <div class="section-header">
                    <h2>Kelola Pembayaran</h2>
                </div>
                <div class="card">
                    <table>
                        <thead>
                            <tr>
                                <th>ID Pembayaran</th>
                                <th>ID Booking</th>
                                <th>Jumlah</th>
                                <th>Metode</th>
                                <th>Tanggal</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $payment): ?>
                            <tr>
                                <td><?php echo $payment['payment_id']; ?></td>
                                <td><?php echo $payment['booking_id']; ?></td>
                                <td>Rp <?php echo number_format($payment['amount'], 0, ',', '.'); ?></td>
                                <td>
                                    <?php 
                                    $methods = [
                                        'credit_card' => 'Kartu Kredit',
                                        'debit_card' => 'Kartu Debit',
                                        'cash' => 'Tunai',
                                        'transfer' => 'Transfer'
                                    ];
                                    echo $methods[$payment['payment_method']] ?? $payment['payment_method'];
                                    ?>
                                </td>
                                <td><?php echo $payment['payment_date']; ?></td>
                                <td>
                                    <?php 
                                    if ($payment['status'] == 'paid') {
                                        echo '<span class="badge confirmed">Sudah Bayar</span>';
                                    } elseif ($payment['status'] == 'unpaid') {
                                        echo '<span class="badge pending">Belum Bayar</span>';
                                    } else {
                                        echo '<span class="badge delete">Refund</span>';
                                    }
                                    ?>
                                </td>
                                <td class="action-buttons">
                                    <button class="btn-small btn-edit" onclick="detailPayment('<?php echo $payment['payment_id']; ?>', '<?php echo $payment['booking_id']; ?>', '<?php echo $payment['amount']; ?>', '<?php echo $payment['payment_method']; ?>', '<?php echo $payment['payment_date']; ?>', '<?php echo $payment['status']; ?>')">Detail</button>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="action" value="update_payment_status">
                                        <input type="hidden" name="payment_id" value="<?php echo $payment['payment_id']; ?>">
                                        <select name="status" onchange="this.form.submit()" class="status-select">
                                            <option value="<?php echo $payment['status']; ?>" selected>Ubah Status</option>
                                            <option value="paid">Bayar</option>
                                            <option value="unpaid">Belum Bayar</option>
                                            <option value="refunded">Refund</option>
                                        </select>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>
            <?php endif; ?>
        </main>
    </div>

    </div>

    <!-- Modal untuk Edit User -->
    <div id="editUserModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('editUserModal')">&times;</span>
            <h2>Edit Pengguna</h2>
            <form id="editUserForm" method="POST">
                <input type="hidden" name="action" value="update_user">
                <input type="hidden" id="edit_user_id" name="user_id">
                
                <div class="form-group">
                    <label>ID User</label>
                    <input type="text" id="edit_user_id_display" disabled>
                </div>
                
                <div class="form-group">
                    <label>Nama</label>
                    <input type="text" id="edit_user_name" name="name" required>
                </div>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" id="edit_user_email" name="email" required>
                </div>
                
                <div class="form-group">
                    <label>Role</label>
                    <select id="edit_user_role" name="role" required>
                        <option value="customer">Customer</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                
                <div class="modal-buttons">
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    <button type="button" class="btn btn-secondary" onclick="closeModal('editUserModal')">Batal</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal untuk Detail Booking -->
    <div id="detailBookingModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('detailBookingModal')">&times;</span>
            <h2>Detail Pemesanan</h2>
            
            <div class="modal-info">
                <div class="info-row">
                    <label>ID Booking:</label>
                    <span id="detail_booking_id"></span>
                </div>
                <div class="info-row">
                    <label>Nama Pengguna:</label>
                    <span id="detail_booking_name"></span>
                </div>
                <div class="info-row">
                    <label>Hotel:</label>
                    <span id="detail_booking_hotel"></span>
                </div>
                <div class="info-row">
                    <label>Kamar:</label>
                    <span id="detail_booking_room"></span>
                </div>
                <div class="info-row">
                    <label>Check-in:</label>
                    <span id="detail_booking_checkin"></span>
                </div>
                <div class="info-row">
                    <label>Check-out:</label>
                    <span id="detail_booking_checkout"></span>
                </div>
                <div class="info-row">
                    <label>Status:</label>
                    <span id="detail_booking_status"></span>
                </div>
                <div class="info-row">
                    <label>Keterangan Khusus:</label>
                    <span id="detail_booking_notes"></span>
                </div>
            </div>
            
            <div class="modal-buttons">
                <button type="button" class="btn btn-secondary" onclick="closeModal('detailBookingModal')">Tutup</button>
            </div>
        </div>
    </div>

    <!-- Modal untuk Detail Payment -->
    <div id="detailPaymentModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('detailPaymentModal')">&times;</span>
            <h2>Detail Pembayaran</h2>
            
            <div class="modal-info">
                <div class="info-row">
                    <label>ID Pembayaran:</label>
                    <span id="detail_payment_id"></span>
                </div>
                <div class="info-row">
                    <label>ID Booking:</label>
                    <span id="detail_payment_booking_id"></span>
                </div>
                <div class="info-row">
                    <label>Jumlah:</label>
                    <span id="detail_payment_amount"></span>
                </div>
                <div class="info-row">
                    <label>Metode Pembayaran:</label>
                    <span id="detail_payment_method"></span>
                </div>
                <div class="info-row">
                    <label>Tanggal Pembayaran:</label>
                    <span id="detail_payment_date"></span>
                </div>
                <div class="info-row">
                    <label>Status:</label>
                    <span id="detail_payment_status"></span>
                </div>
            </div>
            
            <div class="modal-buttons">
                <button type="button" class="btn btn-secondary" onclick="closeModal('detailPaymentModal')">Tutup</button>
            </div>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>