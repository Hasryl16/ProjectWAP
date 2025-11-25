<?php
session_start();
include_once '../connection.php';

// Check if user is logged in
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user's bookings with related data
$conn = getConnection();
$query = "
    SELECT 
        b.booking_id,
        b.booking_date,
        b.status as booking_status,
        bd.check_in,
        bd.check_out,
        bd.price_per_night,
        bd.total_price,
        bd.special_request,
        h.hotel_name,
        h.address,
        h.image_url,
        r.room_type,
        p.amount as payment_amount,
        p.payment_method,
        p.status as payment_status
    FROM booking b
    JOIN booking_detail bd ON b.booking_id = bd.booking_id
    JOIN hotel h ON b.hotel_id = h.hotel_id
    JOIN room r ON bd.room_id = r.room_id
    LEFT JOIN payment p ON b.booking_id = p.booking_id
    WHERE b.user_id = ?
    ORDER BY b.booking_date DESC
";

$stmt = $conn->prepare($query);
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$bookings = [];
while ($row = $result->fetch_assoc()) {
    $bookings[] = $row;
}

$stmt->close();
$conn->close();

// Helper function to format dates
function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

// Helper function to check if booking is upcoming
function isUpcoming($check_in) {
    return strtotime($check_in) > time();
}

// Helper function to check if booking is past
function isPast($check_out) {
    return strtotime($check_out) < time();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking History - Sunrise Hotel</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="../style.css">
</head>
<body>
        <?php include 'header.php'; ?>

        <!-- Main Content -->
        <div class="container my-5">
            <!-- Summary Cards -->
            <?php
            $upcoming_count = 0;
            $past_count = 0;
            $total_spent = 0;

            foreach ($bookings as $booking) {
                if (isUpcoming($booking['check_in'])) {
                    $upcoming_count++;
                } elseif (isPast($booking['check_out'])) {
                    $past_count++;
                    $total_spent += $booking['total_price'];
                }
            }
            ?>
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="card text-center shadow-sm">
                        <div class="card-body">
                            <i class="bi bi-calendar-event text-primary" style="font-size: 2rem;"></i>
                            <h3 class="mt-3 mb-0"><?php echo $upcoming_count; ?></h3>
                            <p class="text-muted mb-0">Upcoming Bookings</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card text-center shadow-sm">
                        <div class="card-body">
                            <i class="bi bi-check-circle text-success" style="font-size: 2rem;"></i>
                            <h3 class="mt-3 mb-0"><?php echo $past_count; ?></h3>
                            <p class="text-muted mb-0">Completed Stays</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-3">
                    <div class="card text-center shadow-sm">
                        <div class="card-body">
                            <i class="bi bi-cash text-warning" style="font-size: 2rem;"></i>
                            <h3 class="mt-3 mb-0">Rp <?php echo number_format($total_spent, 0, ',', '.'); ?></h3>
                            <p class="text-muted mb-0">Total Spent</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bookings List -->
            <div id="bookingsList">
                <?php if (empty($bookings)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox text-muted" style="font-size: 4rem;"></i>
                        <h3 class="mt-3">No bookings found</h3>
                        <p class="text-muted">You haven't made any bookings yet.</p>
                        <a href="../index.php" class="btn btn-primary mt-3">
                            <i class="bi bi-search me-2"></i>Browse Hotels
                        </a>
                    </div>
                <?php else: ?>
                    <?php foreach ($bookings as $booking): ?>
                        <div class="booking-card <?php echo isUpcoming($booking['check_in']) ? 'upcoming-booking' : 'past-booking'; ?>" data-status="<?php echo isUpcoming($booking['check_in']) ? 'upcoming' : 'past'; ?>">
                            <div class="card shadow-sm mb-4 <?php echo isPast($booking['check_out']) ? 'opacity-75' : ''; ?>">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <img src="<?php echo htmlspecialchars($booking['image_url'] ?: 'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?w=500&h=300&fit=crop'); ?>" 
                                                 alt="Hotel" 
                                                 class="img-fluid rounded"
                                                 style="height: 150px; width: 100%; object-fit: cover;">
                                        </div>
                                        <div class="col-md-6">
                                            <div class="d-flex align-items-start justify-content-between mb-2">
                                                <div>
                                                    <h4 class="mb-1"><?php echo htmlspecialchars($booking['hotel_name']); ?></h4>
                                                    <p class="text-muted mb-2">
                                                        <i class="bi bi-geo-alt me-1"></i>
                                                        <?php echo htmlspecialchars($booking['address']); ?>
                                                    </p>
                                                </div>
                                                <span class="badge <?php 
                                                    if ($booking['booking_status'] === 'confirmed') echo 'bg-success';
                                                    elseif ($booking['booking_status'] === 'pending') echo 'bg-warning';
                                                    elseif ($booking['booking_status'] === 'cancelled') echo 'bg-danger';
                                                    else echo 'bg-secondary';
                                                ?>">
                                                    <?php echo ucfirst($booking['booking_status']); ?>
                                                </span>
                                            </div>
                                            <div class="row g-3">
                                                <div class="col-6">
                                                    <small class="text-muted d-block">Check-in</small>
                                                    <strong><?php echo formatDate($booking['check_in']); ?></strong>
                                                    <small class="d-block text-muted">2:00 PM</small>
                                                </div>
                                                <div class="col-6">
                                                    <small class="text-muted d-block">Check-out</small>
                                                    <strong><?php echo formatDate($booking['check_out']); ?></strong>
                                                    <small class="d-block text-muted">12:00 PM</small>
                                                </div>
                                                <div class="col-6">
                                                    <small class="text-muted d-block">Room Type</small>
                                                    <strong><?php echo htmlspecialchars($booking['room_type']); ?></strong>
                                                </div>
                                                <div class="col-6">
                                                    <small class="text-muted d-block">Price per Night</small>
                                                    <strong>Rp <?php echo number_format($booking['price_per_night'], 0, ',', '.'); ?></strong>
                                                </div>
                                            </div>
                                            <div class="mt-3">
                                                <span class="text-muted">Booking ID: </span>
                                                <strong><?php echo htmlspecialchars($booking['booking_id']); ?></strong>
                                                <?php if ($booking['special_request']): ?>
                                                    <br><small class="text-muted">Special Request: <?php echo htmlspecialchars($booking['special_request']); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="text-end">
                                                <h4 class="text-primary mb-3">Rp <?php echo number_format($booking['total_price'], 0, ',', '.'); ?></h4>
                                                <div class="d-grid gap-2">
                                                    <button class="btn btn-primary btn-sm" onclick="viewBookingDetails('<?php echo $booking['booking_id']; ?>')">
                                                        <i class="bi bi-eye me-1"></i> View Details
                                                    </button>
                                                    <?php if (isUpcoming($booking['check_in']) && $booking['booking_status'] !== 'cancelled'): ?>
                                                        <button class="btn btn-outline-danger btn-sm" onclick="cancelBooking('<?php echo $booking['booking_id']; ?>')">
                                                            <i class="bi bi-x-circle me-1"></i> Cancel
                                                        </button>
                                                    <?php endif; ?>
                                                    <button class="btn btn-outline-secondary btn-sm">
                                                        <i class="bi bi-download me-1"></i> Receipt
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Empty State (hidden by default) -->
            <div id="emptyState" class="text-center py-5" style="display: none;">
                <i class="bi bi-inbox text-muted" style="font-size: 4rem;"></i>
                <h3 class="mt-3">No bookings found</h3>
                <p class="text-muted">You don't have any bookings matching this filter.</p>
                <a href="index.html" class="btn btn-primary mt-3">
                    <i class="bi bi-search me-2"></i>Browse Hotels
                </a>
            </div>
        </div>

        <?php include 'footer.php'; ?>

    <!-- Booking Details Modal -->
    <div class="modal fade" id="bookingDetailsModal" tabindex="-1" aria-labelledby="bookingDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bookingDetailsModalLabel">Booking Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modalContent">
                    <!-- Content will be dynamically loaded -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary">
                        <i class="bi bi-printer me-1"></i> Print
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Cancel Booking Confirmation Modal -->
    <div class="modal fade" id="cancelBookingModal" tabindex="-1" aria-labelledby="cancelBookingModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cancelBookingModalLabel">Cancel Booking</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to cancel this booking?</p>
                    <p class="text-muted">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, Keep Booking</button>
                    <form method="POST" action="cancel_booking.php" style="display: inline;">
                        <input type="hidden" name="booking_id" id="cancelBookingId">
                        <button type="submit" class="btn btn-danger">Yes, Cancel Booking</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
    
    <!-- Custom JavaScript -->
    <script>
    // Function to view booking details (placeholder)
    function viewBookingDetails(bookingId) {
        // This would typically load booking details via AJAX
        alert('View details for booking: ' + bookingId);
    }

    // Function to cancel booking - shows confirmation modal
    function cancelBooking(bookingId) {
        // Set the booking ID in the hidden input
        document.getElementById('cancelBookingId').value = bookingId;

        // Show the modal
        const modal = new bootstrap.Modal(document.getElementById('cancelBookingModal'));
        modal.show();
    }

    // Handle URL parameters for success/error messages
    document.addEventListener('DOMContentLoaded', function() {
        const urlParams = new URLSearchParams(window.location.search);

        if (urlParams.has('message')) {
            const message = urlParams.get('message');
            if (message === 'cancelled') {
                // Show success message
                showAlert('Booking cancelled successfully!', 'success');
            }
        }

        if (urlParams.has('error')) {
            const error = urlParams.get('error');
            if (error === 'cancel_failed') {
                showAlert('Failed to cancel booking. Please try again.', 'error');
            } else if (error === 'unauthorized') {
                showAlert('You are not authorized to cancel this booking.', 'error');
            } else if (error === 'not_found') {
                showAlert('Booking not found.', 'error');
            }
        }
    });

    // Function to show alerts
    function showAlert(message, type) {
        // Create alert element
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed`;
        alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;

        // Add to body
        document.body.appendChild(alertDiv);

        // Auto remove after 5 seconds
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }
    </script>
</body>
</html>
