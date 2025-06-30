// Custom JavaScript untuk Pupuk Store
document.addEventListener('DOMContentLoaded', function() {
    
    // Auto hide alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            alert.style.opacity = '0';
            setTimeout(function() {
                alert.remove();
            }, 300);
        }, 5000);
    });

    // Konfirmasi delete
    const deleteButtons = document.querySelectorAll('.btn-delete');
    deleteButtons.forEach(function(button) {
        button.addEventListener('click', function(e) {
            if (!confirm('Apakah Anda yakin ingin menghapus item ini?')) {
                e.preventDefault();
            }
        });
    });

    // Format input harga
    const priceInputs = document.querySelectorAll('.price-input');
    priceInputs.forEach(function(input) {
        input.addEventListener('input', function() {
            let value = this.value.replace(/[^0-9]/g, '');
            this.value = formatRupiah(value);
        });
    });

    // Quantity controls
    const quantityControls = document.querySelectorAll('.quantity-control');
    quantityControls.forEach(function(control) {
        const minusBtn = control.querySelector('.btn-minus');
        const plusBtn = control.querySelector('.btn-plus');
        const input = control.querySelector('.quantity-input');

        minusBtn.addEventListener('click', function() {
            let value = parseInt(input.value) || 1;
            if (value > 1) {
                input.value = value - 1;
                updateCartItem(input);
            }
        });

        plusBtn.addEventListener('click', function() {
            let value = parseInt(input.value) || 1;
            let max = parseInt(input.getAttribute('max')) || 999;
            if (value < max) {
                input.value = value + 1;
                updateCartItem(input);
            }
        });
    });

    // Image preview for file uploads
    const imageInputs = document.querySelectorAll('.image-input');
    imageInputs.forEach(function(input) {
        input.addEventListener('change', function() {
            const file = this.files[0];
            const preview = document.querySelector(this.getAttribute('data-preview'));
            
            if (file && preview) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            }
        });
    });
});

// Helper Functions
function formatRupiah(angka) {
    return angka.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
}

function updateCartItem(input) {
    // AJAX update cart quantity
    const productId = input.getAttribute('data-product-id');
    const quantity = input.value;
    
    fetch('ajax/update_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `product_id=${productId}&quantity=${quantity}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
}

// WhatsApp Contact
function contactWhatsApp(message = '') {
    const phone = '6282363025220'; // Ganti dengan nomor WA
    const text = encodeURIComponent(message || 'Halo, saya tertarik dengan produk pupuk Anda');
    window.open(`https://wa.me/${phone}?text=${text}`, '_blank');
}