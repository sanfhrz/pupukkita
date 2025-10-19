
# 🌱 Pupuk Kita

**Pupuk Kita** adalah website e-commerce sederhana untuk penjualan berbagai jenis pupuk, dilengkapi dengan sistem manajemen produk, promo, keranjang belanja, dan dashboard admin.  
Website ini dikembangkan sebagai tugas akhir mata kuliah **Enterprise Resource Planning** di **Universitas Royal**.

---

## 🚀 Fitur Utama

- 🛒 **Manajemen Produk** — CRUD produk pupuk lengkap dengan gambar & deskripsi
- 🧾 **Sistem Pemesanan** — Checkout, invoice, dan pelacakan pesanan
- 🎟️ **Promo & Diskon** — Kode voucher otomatis dan analitik promo
- 👥 **Sistem Login & Register** — Otentikasi pengguna dan admin
- 📊 **Dashboard Admin** — Statistik pesanan, produk, dan pengguna
- 💬 **Wishlist & Review** — Simpan produk favorit dan beri ulasan
- 📤 **Ekspor Laporan** — Ekspor data order dan promo ke file

---

## 📋 Requirements

- PHP 7.4 atau lebih tinggi
- MySQL 5.7 atau lebih tinggi
- Web Server (XAMPP, Apache/Nginx)
- Browser modern (Chrome, Firefox, Edge)

---

## 🔧 Instalasi

1. **Clone atau download** project:
   ```bash
   git clone https://github.com/sanfhrz/pupukkita.git
   cd pupukkita
   ```

2. **Setup database:**
   - Buka phpMyAdmin
   - Buat database baru: `pupukstore`
   - Import file `pupukstore.sql` dari folder `database/`

3. **Konfigurasi koneksi:**
   - Edit file:
     ```
     includes/config.php
     ```

4. **Jalankan website:**
   ```
   http://localhost/pupukkita
   ```

---

## 🔐 Login Default

**Admin:**
- Email: `...............`
- Password: `........`
  contact me fahrizaihsan06@gmail.com

**User:**
- Bisa ditambah langsung di halaman login

---

## 📁 Struktur Folder

```
pupukkita
│   about.php
│   cart.php
│   checkout.php
│   cody-chat-history-2025-06-26T02-52-03.json
│   contact.php
│   detail_produk.php
│   index.php
│   invoice.php
│   keranjang.php
│   login.php
│   logout.php
│   my_orders.php
│   order_detail.php
│   order_success.php
│   order_tracking.php
│   process_checkout.php
│   produk.php
│   profile.php
│   pupukstore (1).sql
│   register.php
│   search.php
│   test_curl.php
│   txt.txt
│   upload_payment.php
│   wishlist.php
│
├───admin
│   │   bulk_promo_actions.php
│   │   dashboard.php
│   │   export_orders.php
│   │   export_promos.php
│   │   get_dashboard_stats.php
│   │   get_order_count.php
│   │   get_order_details.php
│   │   index.php
│   │   login.php
│   │   logout.php
│   │   orders.php
│   │   print_order.php
│   │   products.php
│   │   promo_analytics.php
│   │   promo_codes.php
│   │   reports.php
│   │   setup_database.php
│   │   users.php
│   │
│   └───includes
│           auth.php
│
├───ajax
│       add_to_cart.php
│       add_to_wishlist.php
│       apply_promo.php
│       cancel_order.php
│       check_order_status.php
│       clear_cart.php
│       clear_wishlist.php
│       confirm_received.php
│       quick_view.php
│       reorder.php
│       submit_review.php
│
├───assets
│   ├───css
│   │       style.css
│   │
│   ├───img
│   │   │   pupukgacor.jpg
│   │   │   
│   │   └───pupuk
│   │           1750994307_685e0d8372bb4.jpg
│   │           1750994334_685e0d9e8052e.png
│   │           1750994411_685e0deb3033d.jpg
│   │           1750994453_685e0e15081f4.jpg
│   │           1750994486_685e0e3687fa2.jpg
│   │           Gambar WhatsApp 2025-06-24 pukul 17.35.11_1533b268.jpg
│   │           Gambar WhatsApp 2025-06-24 pukul 17.35.11_47fc10e1.jpg
│   │           Gambar WhatsApp 2025-06-24 pukul 17.35.11_6cc7d7a0.jpg
│   │           Gambar WhatsApp 2025-06-24 pukul 17.35.12_9ae5eaa1.jpg
│   │           Gambar WhatsApp 2025-06-24 pukul 17.35.12_b92efd53.jpg
│   │           product_1750835790_685ba24e12a3e.jpg
│   │           product_1750835879_685ba2a74c4bd.jpg
│   │
│   └───js
│           script.js
│
├───database
│       pupukstore.sql
│
└───includes
        config.php
        footer.php
        header.php
        navbar.php
```

---


## 📞 Kontak Developer

- 📧 fahrizaihsan06@gmail.com  
- 🎓 Universitas Royal – Mata Kuliah ERP 2025

---

## 🙌 Kredit

Dibuat oleh **Ihsan Fahriza**  
Tugas akhir untuk mata kuliah **Enterprise Resource Planning**
