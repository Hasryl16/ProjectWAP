function viewBookingDetails(bookingId) {
    alert('View details for booking: ' + bookingId);
}
function cancelBooking(bookingId) {
    document.getElementById('cancelBookingId').value = bookingId;


    const modal = new bootstrap.Modal(document.getElementById('cancelBookingModal'));
    modal.show();
}
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);

    if (urlParams.has('message')) {
        const message = urlParams.get('message');
        if (message === 'cancelled') {
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

function showAlert(message, type) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type === 'success' ? 'success' : 'danger'} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;

    document.body.appendChild(alertDiv);
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}
