// =============================================
// script.js - Admin Panel JavaScript
// =============================================

// ===== MODAL FUNCTIONS =====
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('show');
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('show');
    }
}

// Close modal saat klik di luar modal
window.addEventListener('click', function(e) {
    if (e.target.classList.contains('modal')) {
        e.target.classList.remove('show');
    }
});

// ===== EDIT USER =====
function editUser(userId, name, email, role) {
    document.getElementById('edit_user_id').value = userId;
    document.getElementById('edit_user_id_display').value = userId;
    document.getElementById('edit_user_name').value = name;
    document.getElementById('edit_user_email').value = email;
    document.getElementById('edit_user_role').value = role;
    
    openModal('editUserModal');
}

// ===== DETAIL BOOKING =====
function detailBooking(bookingId, name, hotel, roomId, checkIn, checkOut, status, notes) {
    document.getElementById('detail_booking_id').textContent = bookingId;
    document.getElementById('detail_booking_name').textContent = name;
    document.getElementById('detail_booking_hotel').textContent = hotel;
    document.getElementById('detail_booking_room').textContent = roomId;
    document.getElementById('detail_booking_checkin').textContent = checkIn;
    document.getElementById('detail_booking_checkout').textContent = checkOut;
    
    // Format status badge
    let statusBadge = '';
    if (status === 'confirmed') {
        statusBadge = '<span class="badge confirmed">Confirmed</span>';
    } else if (status === 'pending') {
        statusBadge = '<span class="badge pending">Pending</span>';
    } else {
        statusBadge = '<span class="badge delete">Cancelled</span>';
    }
    document.getElementById('detail_booking_status').innerHTML = statusBadge;
    document.getElementById('detail_booking_notes').textContent = notes || '-';
    
    openModal('detailBookingModal');
}

// ===== DETAIL PAYMENT =====
function detailPayment(paymentId, bookingId, amount, method, date, status) {
    document.getElementById('detail_payment_id').textContent = paymentId;
    document.getElementById('detail_payment_booking_id').textContent = bookingId;
    document.getElementById('detail_payment_amount').textContent = 'Rp ' + parseInt(amount).toLocaleString('id-ID');
    
    // Format payment method
    const methods = {
        'credit_card': 'Kartu Kredit',
        'debit_card': 'Kartu Debit',
        'cash': 'Tunai',
        'transfer': 'Transfer'
    };
    document.getElementById('detail_payment_method').textContent = methods[method] || method;
    document.getElementById('detail_payment_date').textContent = date;
    
    // Format status badge
    let statusBadge = '';
    if (status === 'paid') {
        statusBadge = '<span class="badge confirmed">Sudah Bayar</span>';
    } else if (status === 'unpaid') {
        statusBadge = '<span class="badge pending">Belum Bayar</span>';
    } else {
        statusBadge = '<span class="badge delete">Refund</span>';
    }
    document.getElementById('detail_payment_status').innerHTML = statusBadge;
    
    openModal('detailPaymentModal');
}
function confirmLogout(e) {
    e.preventDefault();
    
    if (confirm('Apakah Anda yakin ingin logout?\n\nAnda akan kembali ke halaman login.')) {
        window.location.href = '?action=logout';
    }
}
function toggleSidebar() {
    const sidebar = document.getElementById('sidebar');
    const container = document.querySelector('.container');
    
    sidebar.classList.toggle('collapsed');
    container.classList.toggle('sidebar-collapsed');
    
    // Simpan state ke localStorage (opsional)
    const isCollapsed = sidebar.classList.contains('collapsed');
    localStorage.setItem('sidebarCollapsed', isCollapsed);
}

// ===== LOAD SIDEBAR STATE =====
document.addEventListener('DOMContentLoaded', function() {
    // Check if sidebar was collapsed sebelumnya
    const wasCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    
    if (wasCollapsed) {
        const sidebar = document.getElementById('sidebar');
        const container = document.querySelector('.container');
        sidebar.classList.add('collapsed');
        container.classList.add('sidebar-collapsed');
    }
    
    console.log('✅ Admin Panel initialized');
});

// ===== SEARCH FORM SUBMISSION =====
document.addEventListener('DOMContentLoaded', function() {
    const searchForm = document.querySelector('.search-form');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            const searchInput = this.querySelector('input[name="search"]');
            const keyword = searchInput.value.trim();
            
            // Validasi: minimal 1 karakter
            if (keyword.length === 0) {
                e.preventDefault();
                alert('Masukkan keyword pencarian!');
                return false;
            }
        });
    }
});

// ===== DELETE CONFIRMATION =====
function confirmDelete(type) {
    return confirm(`Apakah Anda yakin ingin menghapus ${type} ini?`);
}

// ===== CANCEL BOOKING CONFIRMATION =====
function confirmCancel(bookingId) {
    return confirm(`Batalkan booking ${bookingId}? Tindakan ini tidak dapat dibatalkan.`);
}

// ===== UPDATE PAYMENT STATUS =====
function confirmPaymentUpdate() {
    return confirm('Update status pembayaran?');
}

// ===== FORMAT RUPIAH =====
function formatRupiah(amount) {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(amount);
}

// ===== NOTIFICATION AUTO HIDE =====
document.addEventListener('DOMContentLoaded', function() {
    const notifications = document.querySelectorAll('.notification');
    
    notifications.forEach(notification => {
        // Auto hide notification setelah 5 detik
        setTimeout(function() {
            notification.style.transition = 'opacity 0.3s ease';
            notification.style.opacity = '0';
            
            // Remove dari DOM setelah animasi selesai
            setTimeout(function() {
                notification.remove();
            }, 300);
        }, 5000);
    });
});

// ===== TABLE FILTER (UNTUK FUTURE USE) =====
function filterTable(tableId, keyword) {
    const table = document.getElementById(tableId);
    const rows = table.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
    
    for (let i = 0; i < rows.length; i++) {
        const cells = rows[i].getElementsByTagName('td');
        let match = false;
        
        for (let j = 0; j < cells.length; j++) {
            if (cells[j].textContent.toLowerCase().includes(keyword.toLowerCase())) {
                match = true;
                break;
            }
        }
        
        rows[i].style.display = match ? '' : 'none';
    }
}

// ===== EXPORT TABLE TO CSV (FUTURE USE) =====
function exportTableToCSV(filename) {
    const csv = [];
    const tables = document.querySelectorAll('table');
    
    tables.forEach(table => {
        const rows = table.querySelectorAll('tr');
        
        rows.forEach(row => {
            const cells = row.querySelectorAll('td, th');
            const rowData = [];
            
            cells.forEach(cell => {
                rowData.push('"' + cell.textContent.trim() + '"');
            });
            
            csv.push(rowData.join(','));
        });
    });
    
    // Create blob dan download
    const csvContent = 'data:text/csv;charset=utf-8,' + encodeURIComponent(csv.join('\n'));
    const link = document.createElement('a');
    link.setAttribute('href', csvContent);
    link.setAttribute('download', filename || 'export.csv');
    link.click();
}

// ===== PRINT PAGE =====
function printPage() {
    window.print();
}

// ===== CLEAR SEARCH =====
function clearSearch() {
    const currentPage = new URLSearchParams(window.location.search).get('page') || 'dashboard';
    window.location.href = `?page=${currentPage}`;
}

// ===== KEYBOARD SHORTCUTS =====
document.addEventListener('keydown', function(e) {
    // Alt + S: Focus search input
    if (e.altKey && e.key === 's') {
        e.preventDefault();
        const searchInput = document.querySelector('input[name="search"]');
        if (searchInput) {
            searchInput.focus();
            searchInput.select();
        }
    }
    
    // Alt + T: Toggle sidebar
    if (e.altKey && e.key === 't') {
        e.preventDefault();
        toggleSidebar();
    }
});

// ===== PAGE READY INDICATOR =====
window.addEventListener('load', function() {
    console.log('✅ All resources loaded');
    document.body.style.opacity = '1';
});