<p align="center">
  <img src="public/images/logo.svg" alt="KAFEINA Logo" width="128" height="128" />
</p>

<h1 align="center">KAFEINA</h1>

<p align="center">
  <b>Specialty Roastery &amp; Multi-Branch Retail POS Enterprise Resource Planning (ERP) Platform</b>
</p>

<p align="center">
  <a href="https://laravel.com"><img src="https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel 12" /></a>
  <a href="https://php.net"><img src="https://img.shields.io/badge/PHP-8.3-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.3" /></a>
  <a href="https://tailwindcss.com"><img src="https://img.shields.io/badge/Tailwind_CSS-3.4-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white" alt="Tailwind CSS" /></a>
  <a href="https://alpinejs.dev"><img src="https://img.shields.io/badge/Alpine.js-3.x-8BC0D0?style=for-the-badge&logo=alpine.js&logoColor=white" alt="Alpine.js" /></a>
  <a href="https://mysql.com"><img src="https://img.shields.io/badge/MySQL-8.0-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL 8" /></a>
  <a href="https://vitejs.dev"><img src="https://img.shields.io/badge/Vite-5.x-646CFF?style=for-the-badge&logo=vite&logoColor=white" alt="Vite" /></a>
  <a href="https://apple.com"><img src="https://img.shields.io/badge/UI_Standard-Apple_iOS_27_Dynamic_Island-000000?style=for-the-badge&logo=apple&logoColor=white" alt="Apple iOS 27" /></a>
  <a href="LICENSE"><img src="https://img.shields.io/badge/License-MIT-green?style=for-the-badge" alt="License" /></a>
</p>

<p align="center">
  Platform ERP industri specialty coffee modern berkinerja tinggi yang mengintegrasikan rantai pasok hulu ke hilir: pengadaan biji mentah (<i>green beans</i>) dari mitra petani, laboratorium pengujian mutu sensori (<i>QC Lab SCA Cupping</i>), penjadwalan sangrai mesin industri (<i>Giesen W15A</i>), pengawasan susut sangrai (<i>Yield Loss</i>), mutasi inventori multi-gudang (<i>FIFO</i>), hingga terminal kasir layar sentuh (<i>Touchscreen POS</i>) dengan auto-deduct resep gramatur dan Dynamic QRIS Midtrans.
</p>

---

Didesain dengan standar kriya antarmuka tinggi (*high craft & design engineering*), sistem ini mengadopsi filosofi visual modern: tata letak monokromatik berpresisi piksel, notifikasi mengambang **Apple iPhone iOS 27 Dynamic Island** berfisika pegas (*spring physics*), **Spotlight Command Palette** instan dengan tombol shortcut keyboard (`/`), komponen layar sentuh POS kasir responsif bebas lag, serta kepatuhan mutlak standar toleransi industri sangrai kopi Specialty Coffee Association (SCA).

---

## 🏛️ Arsitektur Aplikasi & Infrastruktur

Sistem **KAFEINA** dibangun dengan arsitektur modular berorientasi domain (*Domain-Driven Decoupled Architecture*) pada fondasi **Laravel 12 (PHP 8.3)** dan antarmuka reaktif **Alpine.js**:

```mermaid
graph TB
    subgraph "Presentation & Interaction Layer (Vercel & Apple Standards)"
        A["Executive Analytics Dashboard"]
        B["Touchscreen POS Cashier Terminal"]
        C["Spotlight Command Palette Engine"]
        D["iOS 27 Dynamic Island Notification Engine"]
        E["Roastery & QC Sensory Workspace"]
    end

    subgraph "Application & Controller Layer (Laravel 12)"
        F["DashboardController (Hero Trend & PO Consolidation)"]
        G["PosController (Shift, Order & QRIS Engine)"]
        H["RoasteryController (Work Orders & Roast Logs)"]
        I["YieldLossController (Moisture & HPP Monitor)"]
        J["QcLabController (SCA 100-Point Cupping Form)"]
        K["InventoryController (Multi-Warehouse & ROP Alerts)"]
        L["ProcurementController (Farmers & Purchase Orders)"]
    end

    subgraph "Domain Core & Business Logic"
        M["FIFO Stock Card & Batch Tracker"]
        N["Recipe Auto-Deduction Engine"]
        O["Safety Stock ROP Trigger & Consolidator"]
        P["Dynamic Midtrans QRIS Simulator"]
        Q["Role-Based Access Control (RBAC Guard)"]
    end

    subgraph "Persistence & Storage Layer (MySQL 8.0)"
        R[("MySQL Relational Database")]
        S[("Database Sessions & Cache")]
    end

    A --> F
    B --> G
    C --> A
    D --> A
    D --> B
    E --> H
    E --> I
    E --> J

    F --> O
    G --> N
    G --> P
    H --> M
    I --> M
    K --> M
    L --> O
    
    M --> R
    N --> R
    O --> R
    P --> R
    Q --> S
```

### ⚙️ Pola Arsitektur Utama
- **Zero-Latency In-Memory Spotlight Search**: Kotak pencarian dasbor memanfaatkan pemindaian terindeks pada koleksi batch, peringatan stok ROP, dan entitas gerai tanpa memicu beban query berulang ke server basis data.
- **Single-Flight PO Emergency Consolidation**: Mekanisme penggabungan seluruh item di bawah ambang batas aman (*Reorder Point*) menjadi satu dokumen Purchase Order bernomor unik (`PO-KONS-YYYYMMDD-XXX`) secara transaksional aman (*ACID compliant*).
- **FIFO Recipe Deduction Matrix**: Setiap transaksi yang diselesaikan pada terminal kasir secara otomatis memotong stok bahan baku (roasted beans, fresh milk, sirup, pouch, paper cup) sesuai gramatur resep presisi pada cabang yang bersangkutan.
- **Apple iOS 27 Dynamic Island Notification Engine**: Seluruh dialog peringatan dan flash message digantikan oleh notifikasi kapsul mengambang berbasis CSS blur, squircle adaptif, tema terang (*Light Glass*), dan penutupan otomatis (*auto-dismiss timer*).
- **Vercel-Style Fast Role Switcher**: Kemudahan pergantian identitas pengguna simulasi (*Super Admin, Head Roaster, Barista, QC Inspector*) untuk mempermudah demonstrasi dan audit lintas departemen.

---

## 🔄 Alur Kerja Ekosistem (End-to-End Pipeline)

Diagram sekuens berikut menggambarkan siklus pergerakan biji kopi dari hulu pengadaan hingga transaksi kasir ritel:

```mermaid
sequenceDiagram
    participant P as Kelompok Tani / Supplier
    participant PO as Procurement & PO Engine
    participant QC as Laboratorium QC (SCA Cupping)
    participant R as Central Roastery (Giesen W15A)
    participant WH as Inventori Gudang (FIFO)
    participant POS as Terminal Kasir POS (Cabang)
    participant D as Dasbor Eksekutif & Owner

    Note over P,PO: 1. Pengadaan Bahan Mentah
    P->>PO: Pengiriman Green Bean (Gayo Honey / Sidikalang)
    PO->>QC: Alokasi Sampel Uji Fisik & Kadar Air
    
    Note over QC: 2. Standar Mutu Sensori (SCA Form)
    QC->>QC: Penilaian Cupping (Fragrance, Flavor, Clean Cup)
    QC-->>PO: Skor Kelayakan Mutu (Score >= 84.0 / Q-Grade)
    
    Note over R: 3. Produksi Sangrai Industri
    PO->>R: Terbitkan Work Order (BOM Green Bean 12.0 kg)
    R->>R: Sangrai Mesin Giesen W15A (First Crack, Drop Temp)
    R->>R: Audit Yield Loss (14% - 18% Toleransi Susut)
    R->>WH: Simpan Roasted Beans ke Gudang Pusat
    
    Note over WH,POS: 4. Distribusi Cabang & ROP Alert
    WH->>POS: Transfer Stok Biji Sangrai ke Gerai (Senopati/Braga)
    POS->>POS: Barista Transaksi POS (Auto-deduct Gramatur)
    POS->>POS: Pelanggan Bayar via Dynamic QRIS Midtrans
    
    Note over POS,D: 5. Analitik & Peringatan Otomatis
    POS-->>D: Real-time Sinkronisasi Omzet & Struk Penjualan
    WH-->>D: Trigger Safety Stock ROP (< 35% Batas Aman)
    D->>PO: 1-Click Terbitkan PO Konsolidasi Darurat
```

---

## ⚡ Fitur Unggulan KAFEINA

### 1. 🔍 Dasbor Eksekutif & Spotlight Command Palette (`/`)
- **Pencarian Cepat Instan**: Menekan tombol shortcut keyboard `/` atau mengklik search bar langsung menampilkan panel pencarian cerdas *Spotlight* bergaya macOS.
- **Filter Fuzzy Terintegrasi**: Secara langsung mencocokkan kode batch sangrai (`WO-202611-001`), stok bahan baku kritis, navigasi modul, serta tombol filter cabang instan.
- **Grafik Tren Interaktif**: Visualisasi omzet 7 hari / 30 hari, volume struk POS, dan jam sibuk (*rush hour*) dengan tooltip analitik dinamis.

### 2. 📱 Apple iOS 27 Dynamic Island Notification Engine (Light Theme)
- **Kapsul Mengambang Elegan**: Menggantikan pesan konfirmasi jadul (`window.alert`) dengan kapsul Dynamic Island berestetika *Light Glass* (`bg-white/95 backdrop-blur-2xl border border-neutral-200/90`).
- **Ikon Squircle & Animasi Pegas**: Dilengkapi ikon status berbasis squircle (emerald untuk PO berhasil, rose untuk error, amber untuk peringatan) dengan animasi masuk berfisika pegas (*spring physics*).

### 3. 🚨 1-Click Buat PO Konsolidasi Darurat (Safety Stock ROP)
- **Deteksi Otomatis Item Kritis**: Memantau 5 bahan baku krusial (Green Beans Aceh Gayo, Cold Cup PET 12oz, Susu Greenfields, Oatly Barista, Degassing Valve Pouch) yang menyentuh batas aman *Reorder Point*.
- **Penerbitan PO Real-Time**: Tombol `+ Buat PO Konsolidasi` terhubung ke API backend riil yang menerbitkan dokumen PO ke vendor secara instan, mengubah status tombol menjadi `✓ PO Berhasil Diterbitkan ✓`.

### 4. ☕ Terminal Kasir Touchscreen POS (Multi-Cabang)
- **Antarmuka Layar Sentuh Modern**: Desain kartu produk grid dengan kategori terindeks (Espresso Based, Manual Brew Reserve, Beans Retail, Artisan Tea, Pastry).
- **Shift Drawer Barista**: Manajemen kas awal (*opening float*), pencatatan setoran kasir, rekonsiliasi tunai akhir hari, dan otorisasi supervisor.
- **Kunci Isolasi Cabang**: Barista gerai (Senopati, Braga, Malioboro) hanya dapat memproses pesanan dan melihat riwayat transaksi cabang masing-masing.

### 5. 💳 Dynamic QRIS Midtrans Payment Simulator
- **Generator QRIS Dinamis**: Menghasilkan kode QR pembayaran unik per transaksi sesuai total tagihan kasir.
- **Simulasi Pembayaran Real-Time**: Dilengkapi tombol simulasi scan pembeli yang langsung memvalidasi pembayaran ke sistem POS tanpa perlu integrasi bank manual saat pengujian.

### 6. 🔥 Roastery & Work Orders (Mesin Giesen W15A)
- **Pelacakan Siklus Sangrai**: Mengontrol status sangrai (*Planned, In-Progress, Roasting, Cooling, Completed*).
- **Bill of Materials (BOM)**: Integrasi lot biji hijau dengan target profil (Light-Medium, Omni-Roast, Dark Espresso).

### 7. ⚖️ Yield Loss Monitor & Audit HPP Riil
- **Formula Otomatis Susut Bobot**: Menghitung susut sangrai dengan rumus baku:
  $$\text{Loss \%} = \frac{\text{Bobot Green} - \text{Bobot Roasted}}{\text{Bobot Green}} \times 100$$
- **Toleransi Industri (14.0% – 18.0%)**: Indikator visual instan penanda profil optimal atau review evaluasi mutu.

### 8. 🧪 QC Lab & SCA Cupping Sensori (100 Poin)
- **Formulir Cupping Standar SCA**: Evaluasi 10 atribut sensori (Fragrance/Aroma, Flavor, Aftertaste, Acidity, Body, Balance, Uniformity, Clean Cup, Sweetness, Overall).
- **Katalog Lot Biji Mentah**: Dokumentasi kadar air (*moisture meter*), kerapatan curah (*bulk density*), proses pasca-panen, dan elevasi kebun.

### 9. 📦 Inventaris Multi-Cabang & Saldo FIFO Gudang
- **Visibilitas Stok Multi-Lokasi**: Pelacakan saldo gudang sangrai pusat (*Central Hub Cilandak*) dan gudang gerai ritel.
- **Surat Jalan & Transfer**: Alokasi perpindahan barang terkontrol dengan dokumen nomor transfer resmi.

### 10. 🏷️ Voucher Promosi & Manajemen Kuota
- **Diskon Nominal & Persentase**: Validasi batas minimal pembelian, tanggal kedaluwarsa, dan kuota pemakaian kupon.

---

## 🛠️ Tech Stack & Ketergantungan

| Layer | Teknologi & Komponen | Deskripsi & Peran |
| :--- | :--- | :--- |
| **Backend Framework** | Laravel 12.x (PHP 8.3) | Arsitektur MVC, Eloquent ORM, Service Layer, dan API endpoints |
| **Database** | MySQL 8.0 | Basis data relasional dengan integritas kunci asing (*foreign keys*) |
| **CSS Framework** | Tailwind CSS 3.4 | Utilitas styling modern, responsif, dan layouting grid/flex |
| **Frontend Reactivity** | Alpine.js 3.x | Manajemen state antarmuka, popover, dan interaktivitas reaktif |
| **Asset Bundler** | Vite 5.x | Kompilasi kilat aset CSS dan JavaScript |
| **Design System** | Apple iOS 27 & Vercel UI | Dynamic Island notification capsule, Glassmorphism, dan Typography Instrument Sans |
| **Testing Suite** | PHPUnit & Playwright | Pengujian fungsional backend dan otomatisasi verifikasi visual |

---

## ⌨️ Referensi Shortcut Keyboard

| Shortcut | Aksi Cepat | Deskripsi |
| :---: | :--- | :--- |
| **`/`** | **Buka Spotlight Search** | Membuka kotak pencarian cepat dan hasil pencarian cerdas di Dasbor. |
| **`Esc`** | **Tutup Spotlight Search** | Menutup panel dropdown hasil pencarian. |
| **`⌘0` / `Alt+0`** | **Dasbor Eksekutif** | Navigasi cepat ke halaman analitik eksekutif. |
| **`⌘1` / `Alt+1`** | **Terminal Kasir POS** | Membuka antarmuka kasir layar sentuh. |
| **`⌘2` / `Alt+2`** | **Riwayat Transaksi** | Membuka arsip struk penjualan dan audit kasir. |
| **`⌘3` / `Alt+3`** | **Voucher & Kuota** | Membuka manajemen kupon promosi. |
| **`⌘4` / `Alt+4`** | **Pengadaan (Procurement)** | Membuka daftar supplier dan purchase orders. |
| **`⌘5` / `Alt+5`** | **QC Lab & Laboratorium** | Membuka pengujian sensori SCA cupping. |
| **`⌘6` / `Alt+6`** | **Roastery & Work Orders** | Membuka penjadwalan mesin sangrai Giesen. |
| **`⌘7` / `Alt+7`** | **Yield Loss Monitor** | Membuka audit susut bobot sangrai. |
| **`⌘8` / `Alt+8`** | **Inventori Multi-Cabang** | Membuka kartu stok dan saldo gudang. |

---

## 🚀 Panduan Memulai Cepat (Quick Start)

### 📋 Kebutuhan Sistem
1. **PHP**: Versi `>= 8.3` dengan ekstensi `pdo_mysql`, `mbstring`, `openssl`, `bcmath`, `curl`.
2. **Composer**: Versi `>= 2.7`.
3. **Node.js**: Versi `>= 20.x` dan NPM.
4. **MySQL Database Server**: Versi `>= 8.0` (Laragon, XAMPP, Docker, atau MySQL native).

---

### 💻 Langkah Instalasi

```powershell
# 1. Kloning repositori GitHub
git clone https://github.com/iqsanazhr/kafeina-roastery-erp.git
cd kafeina-roastery-erp

# 2. Instal dependensi PHP (Composer)
composer install

# 3. Instal dependensi Frontend (NPM)
npm install

# 4. Konfigurasi berkas environment
cp .env.example .env
php artisan key:generate

# 5. Konfigurasi kredensial basis data di berkas .env
# DB_DATABASE=kafeina
# DB_USERNAME=root
# DB_PASSWORD=

# 6. Jalankan migrasi skema tabel & data seeder awal
php artisan migrate:fresh --seed

# 7. Kompilasi aset frontend
npm run build

# 8. Jalankan server lokal
php artisan serve
```

Akses sistem melalui peramban web pada alamat: **`http://127.0.0.1:8000`**.

---

## 🔐 Kredensial Akun Pengujian Default

Semua akun pengujian menggunakan kata sandi seragam: **`password123`** (atau via *Fast Account Switcher*).

| Peran (Role) | Email Masuk | Deskripsi Hak Akses |
| :--- | :--- | :--- |
| **Super Administrator** | `admin@kafeina.id` | Hak akses tanpa batas ke seluruh modul, pengaturan master, dan analitik eksekutif. |
| **Procurement Officer** | `procurement@kafeina.id` | Pengadaan green bean, manajemen vendor tani, dan penerbitan PO konsolidasi. |
| **QC Lab Inspector** | `qc@kafeina.id` | Laboratorium mutu fisik biji hijau, uji kadar air, dan penilaian skor cupping SCA. |
| **Head Roaster** | `roaster@kafeina.id` | Operasional mesin Giesen W15A, log sangrai harian, dan kontrol yield loss. |
| **Warehouse & Logistics** | `warehouse@kafeina.id` | Penerimaan gudang, mutasi stok FIFO, transfer cabang, dan surat jalan. |
| **Lead Barista (Senopati)** | `cashier.senopati@kafeina.id` | Terminal POS touchscreen cabang Flagship Senopati, pembayaran, dan kasir. |
| **Lead Barista (Braga)** | `cashier.braga@kafeina.id` | Terminal POS touchscreen cabang Braga Heritage Bandung. |
| **Lead Barista (Malioboro)** | `cashier.malioboro@kafeina.id` | Terminal POS touchscreen cabang Malioboro Sanctuary Yogyakarta. |
| **Floor Supervisor** | `supervisor.senopati@kafeina.id` | Otorisasi void kasir, buka/tutup shift kasir, dan penyesuaian kuota voucher. |

---

## 👥 Tim Pengembang & Atribusi

- **Arsitek & Lead Full-Stack Developer**:
  - **Iqsan Azhar** ([@iqsanazhr](https://github.com/iqsanazhr)) — Perancangan arsitektur ERP kopi, skema basis data relasional, modul sangrai industri Giesen W15A, terminal kasir layar sentuh, dan sistem notifikasi Apple iOS 27 Dynamic Island.

---

## 📄 Lisensi

Proyek ini didistribusikan di bawah ketentuan lisensi terbuka **MIT License**. Silakan baca berkas [LICENSE](LICENSE) untuk informasi hak cipta selengkapnya.

---

<p align="center">
  <b>KAFEINA</b> &bull; Crafted with precision by <b>Iqsan Azhar</b> &bull; Specialty Coffee Roastery &amp; Enterprise POS Platform
</p>
