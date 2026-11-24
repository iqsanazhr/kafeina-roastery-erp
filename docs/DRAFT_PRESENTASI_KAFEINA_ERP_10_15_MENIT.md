# DRAFT PANDUAN & NASKAH LENGKAP PRESENTASI KAFEINA ERP
## Durasi: 10 - 15 Menit (Ujian Terstruktur Tugas Besar ERP)

- **Mata Kuliah**: Enterprise Resources Planning (ERP) A / Sistem Informasi Enterprise
- **Dosen Pengampu**: Ir. Muhammad Ihsan Fawzi, M.Kom.
- **Institusi**: Program Studi Informatika, Fakultas Teknik, Universitas Jenderal Soedirman (Unsoed), 2026
- **Tim Penyusun (Kelompok 1)**:
  1. **Iqsan Azhar** (H1D024009) — Koordinator & Requirements Lead *(Slide 01 - 03)*
  2. **Akmal Adhi Nugroho** (H1D025021) — Software Requirements Specification (SRS) Lead *(Slide 04 - 05)*
  3. **Dikas Setio Pramudo** (H1D024077) — Software Design Document (SDD) & Database Architect *(Slide 06 - 08)*
  4. **M Najmi Zahrian** (H1D024038) — Backend Engineering & POS Auto-Deduct Lead *(Slide 09 - 11)*
  5. **Dhea Rafael M.P** (H1D024029) — Multi-Tier Quality Assurance & Verification Lead *(Slide 12 - 16)*
- **File PowerPoint Siap Pakai**: `docs/Presentasi_Kafeina_ERP_Kelompok_1.pptx` *(16 Slide Widescreen 16:9 + Speaker Notes Terintegrasi)*

---

## ⏱️ MATRIKS MANAJEMEN WAKTU & PEMBAGIAN PEMBICARA (10 - 15 MENIT)

| Sesi | Rentang Waktu | Alokasi | Slide | Topik Bahasan Utama | Pembicara Bertugas |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Sesi 1** | 00:00 - 02:45 | 2.5 Menit | 01 - 03 | Pembukaan, Latar Belakang Kopi Spesialti, & User Requirements System (USR) | **Iqsan Azhar** |
| **Sesi 2** | 02:45 - 04:45 | 2.0 Menit | 04 - 05 | Software Requirements Specification (SRS), ISO 25010, & Matriks RTM | **Akmal Adhi Nugroho** |
| **Sesi 3** | 04:45 - 07:45 | 3.0 Menit | 06 - 08 | Software Design Document (SDD): C4 Model, OOP Service Layer, DB 27 Tabel & ACID | **Dikas Setio Pramudo** |
| **Sesi 4** | 07:45 - 10:45 | 3.0 Menit | 09 - 11 | Implementasi Proyek: QC Lab P2P, Roastery Yield & HPP, FIFO Logistics & Web POS | **M Najmi Zahrian** |
| **Sesi 5** | 10:45 - 13:45 | 3.0 Menit | 12 - 15 | Pengujian 5-Tier: Larastan Level 5, Pest Arch, McCabe CFG, 155 Core Tests & Dusk | **Dhea Rafael M.P** |
| **Sesi 6** | 13:45 - 15:00 | 1.5 Menit | 16 | Kesimpulan Eksekutif, Nilai Tambah Sistem, & Pembukaan Tanya Jawab (Q&A) | **Iqsan Azhar & Tim** |

---

## 📑 STRUKTUR SLIDE LENGKAP & NASKAH PEMBICARA (SPEAKER NOTES)

```
================================================================================
SLIDE 01: SAMPUL / COVER PRESENTASI
================================================================================
```
- **Kategori**: `TUGAS BESAR ENTERPRISE RESOURCE PLANNING (ERP)`
- **Judul Slide**: KAFEINA ERP: SISTEM TERPADU ROASTERY KOPI SPESIALTI & RITEL MULTI-CABANG
- **Subjudul**: Pemaparan Komprehensif: Rekayasa Kebutuhan (USR), Spesifikasi Teknis (SRS), Desain Arsitektur (SDD), Implementasi Sistem, dan Pengujian 5-Tier
- **Layout Visual**:
  - Tema *Dark Obsidian* (`#121820`) berkesan elegan dan formal.
  - Kartu Kiri: Daftar Anggota Tim Kelompok 1 beserta NIM dan peran teknisnya.
  - Kartu Kanan: Informasi Dosen Pengampu (Ir. Muhammad Ihsan Fawzi, M.Kom.) dan identitas akademik Informatika FT Unsoed.
- **Durasi Rekomendasi**: 45 Detik (00:00 - 00:45)
- **Pembicara**: **Iqsan Azhar**

> 🎙️ **NASKAH PEMBICARA (SPEAKER NOTES)**:
> *"Selamat pagi/siang kami ucapkan kepada Bapak Ir. Muhammad Ihsan Fawzi, M.Kom. selaku dosen pengampu mata kuliah Enterprise Resource Planning, serta rekan-rekan mahasiswa sekalian.*
> 
> *Kami dari Kelompok 1 yang beranggotakan saya sendiri Iqsan Azhar, rekan saya Akmal Adhi Nugroho, Dikas Setio Pramudo, M Najmi Zahrian, dan Dhea Rafael M.P. Pada kesempatan yang sangat berharga ini, kami akan mempresentasikan hasil perancangan, pengembangan, dan pengujian komprehensif sistem enterprise kami yang bertajuk: **KAFEINA ERP**, yakni Sistem Terintegrasi Roastery Kopi Spesialti dan Manajemen Ritel Multi-Cabang.*
> 
> *Pemaparan selama 10 hingga 15 menit ke depan akan kami sajikan secara runut dan terstruktur mencakup lima pilar utama rekayasa perangkat lunak: dimulai dari User Requirements System (USR), Software Requirements Specification (SRS), Software Design Document (SDD), Implementasi Nyata Rantai Pasok Hulu-ke-Hilir, hingga Kerangka Kerja Pengujian Multi-Tier 5 Lapis. Mari kita mulai dari latar belakang permasalahan bisnis."*

---

```
================================================================================
SLIDE 02: LATAR BELAKANG & IDENTIFIKASI MASALAH
================================================================================
```
- **Kategori**: `LATAR BELAKANG & IDENTIFIKASI MASALAH`
- **Judul Aksi**: Fragmentasi Pencatatan Manual Memicu Asimetri Mutasi Stok dan Bias Margin Laba
- **Layout Visual**: Bento Grid 3 Kolom Komparatif dengan pewarnaan aksen (Merah, Amber, Hijau).
- **Konten Teks Slide**:
  - **01. Asimetri Mutasi Bahan Baku**: Penjualan ratusan cangkir kopi di kasir ritel tidak memotong persediaan biji kopi, susu, dan sirup di gudang secara *real-time*, memicu kebocoran stok fisik (*stock leakage*).
  - **02. Ketiadaan Rekalkulasi HPP Riil Sangrai**: Proses sangrai (*roasting*) mengalami penyusutan bobot fisik (*yield loss*) sebesar 14% - 18%. Tanpa rekalkulasi dinamis, biaya pembelian bahan mentah tidak teralokasi proporsional ke bobot matang yang menyusut, menghasilkan laporan laba kotor yang semu (*biased margin*).
  - **03. Kesalahan Rotasi Persediaan**: Distribusi stok dari gudang sentral ke cabang belum menerapkan aturan *First-In First-Out* (FIFO) yang terkawal sistem, berisiko meloloskan biji kopi yang telah melewati fase puncak kesegaran (*past crop / stale beans*).
- **Durasi Rekomendasi**: 60 Detik (00:45 - 01:45)
- **Pembicara**: **Iqsan Azhar**

> 🎙️ **NASKAH PEMBICARA (SPEAKER NOTES)**:
> *"Bapak Dosen dan rekan-rekan sekalian, industri kopi spesialti (specialty coffee) di Indonesia saat ini telah bertransformasi pesat dari sekadar kedai kopi konvensional menjadi ekosistem terpadu hulu-ke-hilir (farm-to-cup). Pelaku usaha kini mengoperasikan fasilitas pemanggangan mandiri (micro-roastery), laboratorium kendali mutu sensori, dan jaringan distribusi ke banyak gerai cabang.*
> 
> *Namun, hasil studi kami menemukan bahwa sebagian besar operasional UMKM roastery masih terfragmentasi: pencatatan gudang menggunakan spreadsheet manual, sementara kasir menggunakan aplikasi POS terpisah yang berdiri sendiri. Kondisi ini memicu tiga kerapuhan fatal:*
> *Pertama, asimetri mutasi stok, di mana kasir menjual ratusan cangkir namun bahan baku di gudang tidak terpotong otomatis sehingga memicu selisih stok fisik.*
> *Kedua, ketiadaan rekalkulasi HPP riil. Kopi mentah yang disangrai mengalami penyusutan massa fisik termal 14 sampai 18 persen. Jika kenaikan biaya per gram matang ini tidak dihitung otomatis, laporan laba rugi perusahaan menjadi tidak akurat.*
> *Ketiga, kesalahan rotasi persediaan di mana tidak ada jaminan FIFO, membahayakan konsistensi cita rasa kopi spesialti. Inilah permasalahan mendasar yang dijawab oleh KAFEINA ERP."*

---

```
================================================================================
SLIDE 03: USER REQUIREMENTS SYSTEM (USR)
================================================================================
```
- **Kategori**: `USER REQUIREMENTS SYSTEM (USR)`
- **Judul Aksi**: Enam Domain Kebutuhan Pengguna Menyatukan Seluruh Siklus Operasional
- **Layout Visual**: Bento Grid 6 Kartu Interaktif (2 Baris x 3 Kolom) dengan kode domain formal.
- **Konten Teks Slide**:
  - **Domain 01: Procurement & Green Beans (P2P)**: Registrasi kemitraan petani, Purchase Order (PO), validasi laboratorium QC (moisture 10-12%, SCA score $\ge 80$), dan kode lot unik `GB-[FARM]-[YEAR]-[SEQ]`.
  - **Domain 02: Roastery Manufacturing (MTS)**: Master profil kurva sangrai, eksekusi Work Order (WO), otomasi susut bobot (toleransi 14-18%), dan kalkulasi HPP batch per gram.
  - **Domain 03: Central-to-Branch Inventory**: Partisi multi-gudang (Sentral, Cabang, Transit), pengajuan Material Request, alokasi FIFO (`idx_wh_stock_fifo`), dan Surat Jalan QR.
  - **Domain 04: Point of Sale (POS) & Billing (O2C)**: Terminal sentuh tablet ($\ge 48$px), PIN 6-digit lockout 5x, pemotongan otomatis bahan resep BOM instan, split-bill, dan QRIS dinamis.
  - **Domain 05: Executive Finance & P&L**: Dasbor KPI omzet harian *real-time*, laba rugi riil berbasis HPP batch, analitik kebocoran stok, dan peringatan *Reorder Point* (ROP).
  - **Domain 06: Tata Kelola & RBAC Security**: Otorisasi berbasis peran (6 roles), pencatatan jejak audit mutasi (*audit logs*), dan persetujuan supervisor *shift closing* (> Rp 10.000).
- **Durasi Rekomendasi**: 60 Detik (01:45 - 02:45)
- **Pembicara**: **Iqsan Azhar**

> 🎙️ **NASKAH PEMBICARA (SPEAKER NOTES)**:
> *"Untuk menjawab permasalahan tersebut, kami menyusun Dokumen User Requirements System (USR) yang memetakan kebutuhan seluruh pemangku kepentingan ke dalam enam domain fungsional terintegrasi.*
> 
> *Mulai dari pengadaan dan kendali mutu laboratorium untuk memastikan hanya biji kopi berstandar Specialty Grade 1 yang masuk ke gudang; modul manufaktur roastery untuk mengotomatisasi penyusutan sangrai dan pembentukan HPP; modul logistik multi-gudang dengan penegakan mutasi FIFO dan isolasi status barang dalam perjalanan; antarmuka kasir layar sentuh ergonomis dengan pemotongan otomatis bahan resep; dasbor finansial eksekutif; hingga tata kelola sistem berbasis RBAC.*
> 
> *Seluruh kebutuhan pengguna ini kami lengkapi dengan kriteria keberterimaan formal (Acceptance Criteria) berbasis skenario Given-When-Then, yang kemudian kami turunkan ke dokumen spesifikasi teknis perangkat lunak, yang akan dipaparkan oleh rekan saya, Akmal."*

---

```
================================================================================
SLIDE 04: SOFTWARE REQUIREMENTS SPECIFICATION (SRS)
================================================================================
```
- **Kategori**: `SOFTWARE REQUIREMENTS SPECIFICATION (SRS)`
- **Judul Aksi**: Spesifikasi Kebutuhan Presisi dengan Standarisasi IEEE 830 dan Kualitas ISO/IEC 25010
- **Layout Visual**: 2 Kolom Kontras Besar (Spesifikasi Fungsional vs Non-Fungsional).
- **Konten Teks Slide**:
  - **Kebutuhan Fungsional (IEEE Std 830-1998 Conformance)**:
    - Kodifikasi baku alfanumerik `REQ-[MODUL]-[NOMOR]` (contoh: `REQ-PRC-01` s/d `REQ-POS-04`).
    - Matriks 6 Peran Pengguna (Super Admin, Procurement, QC Inspector, Head Roaster, Warehouse Staff, Cashier).
    - Definisi stimulus-respons presisi: memetakan setiap aksi pengguna, kondisi validasi bisnis, dan mutasi basis data.
  - **Kebutuhan Non-Fungsional (ISO/IEC 25010 SQuaRE)**:
    - *Performance Efficiency*: Waktu pemrosesan transaksi kasir $\le 100$ milidetik; rendering dasbor $\le 2$ detik.
    - *Reliability & Fault Tolerance*: Transaksi basis data atomik berprinsip ACID; pembatalan penuh (*rollback* 100%) tanpa *dirty writes*.
    - *Security*: Autentikasi PIN 6-digit kasir dengan penguncian akun 15 menit pasca 5 kali kesalahan (*5-strike lockout*); verifikasi *signature hash* HMAC SHA512 pada webhook Midtrans.
    - *Usability*: Elemen sentuh terminal POS minimal 48 piksel dengan durasi transisi $\le 300$ milidetik (Emil Kowalski standard).
- **Durasi Rekomendasi**: 60 Detik (02:45 - 03:45)
- **Pembicara**: **Akmal Adhi Nugroho**

> 🎙️ **NASKAH PEMBICARA (SPEAKER NOTES)**:
> *"Terima kasih rekan Iqsan. Bapak Dosen dan rekan-rekan, memasuki pilar kedua yaitu Software Requirements Specification (SRS).*
> 
> *Dokumen SRS KAFEINA ERP kami susun berlandaskan standar internasional IEEE Std 830-1998 untuk spesifikasi fungsional dan ISO/IEC 25010 untuk pemenuhan atribut kualitas perangkat lunak. Setiap kebutuhan fungsional dikodifikasikan secara presisi menggunakan format alfanumerik unik, seperti REQ-PRC untuk pengadaan, REQ-PRD untuk produksi sangrai, REQ-INV untuk inventori, hingga REQ-POS untuk transaksi kasir.*
> 
> *Pada sisi non-fungsional, kami menetapkan batas kuantitatif yang ketat: performa transaksi kasir wajib selesai di bawah 100 milidetik agar tidak menimbulkan antrean fisik di gerai; transaksi basis data harus mematuhi prinsip keandalan ACID; keamanan kasir dilindungi oleh algoritma 5-strike lockout pada PIN 6-digit; serta antarmuka layar sentuh yang dirancang ergonomis dengan ukuran tombol minimal 48 piksel. "Mari kita lihat bagaimana kebutuhan ini terlacak secara utuh ke dalam arsitektur sistem."*

---

```
================================================================================
SLIDE 05: REQUIREMENTS TRACEABILITY MATRIX (RTM)
================================================================================
```
- **Kategori**: `REQUIREMENTS TRACEABILITY MATRIX (RTM)`
- **Judul Aksi**: Matriks Ketertelusuran RTM Menjamin 100% Keterlacakan dari Kebutuhan ke Pengujian
- **Layout Visual**: Tabel Matriks RTM 5 Kolom + Callout Integrasi Eksternal di bagian bawah.
- **Konten Teks Slide**:
  - **Tabel Pemetaan RTM**:
    | ID URS | ID SRS | Deskripsi Kebutuhan Teknis | Service & Controller Layer | ID Kasus Uji |
    | :--- | :--- | :--- | :--- | :--- |
    | `FR-PRC-04` | `REQ-PRC-04` | Validasi Mutu QC SCA ($\ge 80$) & Air | `QualityControlService::evaluate` | `WB-P02, Tier2-02` |
    | `FR-MFG-05` | `REQ-PRD-05` | Kalkulasi Susut Yield Loss (14-18%) | `WorkOrderService::calculateYield` | `WB-P03, Tier2-04` |
    | `FR-MFG-06` | `REQ-PRD-06` | Rekalkulasi Dinamis HPP Riil per Gram | `WorkOrderService::calculateHpp` | `WB-P03, Tier4-02` |
    | `FR-INV-03` | `REQ-INV-03` | Alokasi Pengeluaran Batch FIFO | `FifoStockAllocatorService::allocate` | `WB-P04, Tier3-03` |
    | `FR-POS-02` | `REQ-POS-02` | Pemotongan Resep BOM & Pessimistic Lock | `PosBillingService::processCheckout` | `WB-P05, Tier4-01` |
  - **Spesifikasi Kontrak Eksternal**:
    - Midtrans QRIS API: Muatan JSON Core API + Verifikasi Webhook HMAC SHA512.
    - ESC/POS Thermal Printing: Aliran byte printer 58/80mm & sinyal pulsa laci uang RJ-11 (`ESC p 0 25 250`).
- **Durasi Rekomendasi**: 60 Detik (03:45 - 04:45)
- **Pembicara**: **Akmal Adhi Nugroho**

> 🎙️ **NASKAH PEMBICARA (SPEAKER NOTES)**:
> *"Salah satu keunggulan utama dalam metodologi perancangan kami adalah tersedianya Requirements Traceability Matrix atau RTM yang komprehensif.*
> 
> *RTM ini membuktikan keterlacakan dua arah secara sempurna: setiap butir kebutuhan pengguna di URS memiliki padanan langsung pada SRS, diimplementasikan ke dalam metode konkret pada Controller dan Service Layer, disimpan ke model basis data yang terisolasi, dan diverifikasi oleh kasus uji otomatis pada test suite kami.*
> 
> *Sebagai contoh, kebutuhan FR-MFG-05 mengenai kalkulasi susut sangrai diturunkan ke REQ-PRD-05, diimplementasikan pada WorkOrderService::calculateYield, dan diuji pada kasus uji WB-P03 dan Tier 2. Tidak ada kebutuhan yang tertinggal, dan tidak ada baris kode yang tidak memiliki tujuan bisnis yang jelas.*
> 
> *Selain itu, kontrak antarmuka eksternal ke gateway pembayaran Midtrans dan printer kasir ESC/POS telah didefinisikan secara baku. Selanjutnya, arsitektur teknis sistem akan dijelaskan oleh rekan saya, Dikas."*

---

```
================================================================================
SLIDE 06: SDD - ARSITEKTUR C4 MODEL
================================================================================
```
- **Kategori**: `SOFTWARE DESIGN DOCUMENT (SDD) - ARSITEKTUR C4`
- **Judul Aksi**: Pemisahan Tanggung Jawab Melalui Pola Arsitektur Tiga Lapis (Three-Tier Clean Architecture)
- **Layout Visual**: 3 Kartu Tingkatan Arsitektur Komparatif (Presentation, Logic, Data).
- **Konten Teks Slide**:
  - **Tier 1: Presentation Layer (Antarmuka Klien)**:
    - Single Page Application responsif berbasis Tailwind CSS v4.
    - Reaktivitas sisi klien menggunakan Alpine.js / Vue.js 3.
    - Viewport kasir teroptimasi untuk tablet 10–12 inci dengan transisi halus $\le 300$ms.
  - **Tier 2: Application Logic Layer (Backend Services)**:
    - Kerangka kerja enterprise Laravel 13 running on PHP 8.3+.
    - Pola *Dedicated Service Layer* pada namespace `App\Services`.
    - Isolasi formula bisnis: yield loss, HPP riil, alokasi FIFO, dan pemotongan resep BOM.
    - Middleware keamanan: otorisasi RBAC dan proteksi PIN kasir.
  - **Tier 3: Data Persistence Layer (Basis Data Relasional)**:
    - MySQL 8.0 Enterprise Engine InnoDB.
    - 27-29 tabel relasional berstandar normalisasi 3NF.
    - Indeks komposit B-Tree (`idx_wh_stock_fifo`) dan integritas kunci asing (*foreign keys*).
- **Durasi Rekomendasi**: 60 Detik (04:45 - 05:45)
- **Pembicara**: **Dikas Setio Pramudo**

> 🎙️ **NASKAH PEMBICARA (SPEAKER NOTES)**:
> *"Terima kasih rekan Akmal. Selamat pagi/siang Bapak Dosen, saya Dikas Setio Pramudo akan memaparkan Software Design Document (SDD) KAFEINA ERP.*
> 
> *Sistem KAFEINA ERP dirancang mengadopsi model arsitektur C4 yang membagi sistem ke dalam pola Arsitektur Tiga Lapis (Three-Tier Clean Architecture) dengan pemisahan tanggung jawab (Separation of Concerns) yang sangat disiplin.*
> 
> *Pada Presentation Layer, antarmuka dibangun responsif menggunakan Tailwind CSS v4 dan Alpine.js/Vue 3, menghadirkan terminal kasir interaktif yang cepat dan ringan.*
> *Pada Application Logic Layer, kami menggunakan framework Laravel 13 di atas PHP 8.3. Prinsip arsitektur kami menegaskan bahwa HTTP Controller hanya bertugas memvalidasi input HTTP dan mengembalikan respons, sementara seluruh perhitungan kompleks seperti yield loss, HPP riil, dan alokasi stok dienkapsulasi ke dalam Service Layer terisolasi.*
> *Pada Data Layer, basis data MySQL 8.0 InnoDB mengelola relasi 27 tabel secara ACID. Mari kita bedah struktur kelas layanannya pada slide berikutnya."*

---

```
================================================================================
SLIDE 07: SDD - OBJECT-ORIENTED SERVICE LAYER
================================================================================
```
- **Kategori**: `SOFTWARE DESIGN DOCUMENT (SDD) - SERVICE LAYER OOP`
- **Judul Aksi**: Enkapsulasi Logika Bisnis ke Dedicated Services Menjamin Kode Bersih dan Teruji
- **Layout Visual**: Bento Grid 6 Kartu Layanan Domain (2 Kolom x 3 Baris) dengan rincian method publik.
- **Konten Teks Slide**:
  - **`CashierPinAuthService`**: `verifyPin()`, `isLocked()`, `getRemainingLockoutSeconds()` $\rightarrow$ Mengelola verifikasi hash PIN kasir dan *state machine 5-strike lockout* selama 15 menit.
  - **`QualityControlService`**: `evaluateLot()`, `calculateDefectPenalty()`, `assignLotGrade()` $\rightarrow$ Menentukan kelayakan biji kopi berdasarkan kadar air, cacat fisik, dan skor cupping SCA.
  - **`WorkOrderService`**: `startWorkOrder()`, `completeRoasting()`, `calculateYieldLoss()`, `calculateRealHpp()` $\rightarrow$ Mengotomatisasi susut sangrai (14-18%) dan rekalkulasi dinamis HPP per gram matang.
  - **`FifoStockAllocatorService`**: `allocateFifoStock()`, `verifyAvailability()` $\rightarrow$ Mengalokasikan stok batch tertua berdasarkan `roasted_date ASC` dengan penguncian kueri.
  - **`PosBillingService`**: `processCheckout()`, `deductRecipeStock()`, `applyVoucherDiscount()` $\rightarrow$ Memproses transaksi kasir dan memotong bahan baku resep BOM secara pesimistik.
  - **`MidtransQrisService`**: `createQrisCharge()`, `verifyWebhookSignature()`, `checkTransactionStatus()` $\rightarrow$ Mengirim payload pembayaran QRIS dinamis dan memvalidasi keabsahan HMAC SHA512 webhook.
- **Durasi Rekomendasi**: 60 Detik (05:45 - 06:45)
- **Pembicara**: **Dikas Setio Pramudo**

> 🎙️ **NASKAH PEMBICARA (SPEAKER NOTES)**:
> *"Pada perancangan berorientasi objek (OOP), kami menegakkan prinsip Single Responsibility Principle. Seluruh logika domain bisnis utama kami pecah ke dalam dedicated service classes di dalam namespace App\Services.*
> 
> *Di antaranya: CashierPinAuthService yang mengontrol state machine keamanan PIN kasir; QualityControlService yang mengevaluasi parameter fisik dan sensori biji kopi; WorkOrderService yang mengeksekusi perhitungan susut sangrai dan HPP riil; FifoStockAllocatorService yang mengamankan perputaran stok kopi tertua; PosBillingService yang memproses transaksi dan pemotongan bahan resep secara pesimis; serta MidtransQrisService yang memvalidasi integritas transaksi pembayaran nontunai.*
> 
> *Desain ini menghasilkan loose coupling dan high cohesion, sehingga setiap modul layanan dapat diuji secara mandiri (unit testable) tanpa bergantung pada siklus hidup HTTP request."*

---

```
================================================================================
SLIDE 08: SDD - BASIS DATA & KONKURENSI ACID
================================================================================
```
- **Kategori**: `SOFTWARE DESIGN DOCUMENT (SDD) - BASIS DATA & KONKURENSI`
- **Judul Aksi**: Skema 27 Tabel Normalisasi 3NF dengan Indeks B-Tree dan Penguncian Pesimis
- **Layout Visual**: 2 Kartu Kontras Besar (Struktur Relasional 3NF vs Arsitektur Konkurensi ACID).
- **Konten Teks Slide**:
  - **Skema Relasional 27 Tabel (MySQL 8.0 InnoDB)**:
    - Entitas Utama: `suppliers`, `green_bean_lots`, `quality_controls`, `roasting_profiles`, `work_orders`, `boms`, `bom_items`, `warehouse_stocks`, `stock_cards`, `material_requests`, `delivery_notes`, `pos_shifts`, `pos_orders`, `recipe_deductions`, dll.
    - Indeks Komposit Kritis: `idx_wh_stock_fifo` pada `warehouse_stocks (warehouse_id, product_id, roasted_date, quantity)` untuk kueri alokasi batch tertua ultra-cepat.
    - Integritas Referensial: `ON DELETE RESTRICT` pada master dan `ON DELETE CASCADE` pada baris transaksi dependen.
  - **Arsitektur Konkurensi & Integritas ACID**:
    - *Pessimistic Row Locking* (`SELECT ... FOR UPDATE`): Mencegah *race condition* dan *double spending* stok saat transaksi kasir memuncak serentak.
    - *Atomic Transaction Rollback* (`DB::rollBack()`): Isolasi transaksi multi-tabel dalam `DB::transaction()`.
    - *Zero Dirty Writes*: Jika koneksi terputus atau webhook gagal, seluruh mutasi dibatalkan 100%, menjaga saldo fisik inventori tetap utuh.
- **Durasi Rekomendasi**: 60 Detik (06:45 - 07:45)
- **Pembicara**: **Dikas Setio Pramudo**

> 🎙️ **NASKAH PEMBICARA (SPEAKER NOTES)**:
> *"Dari sisi perancangan basis data, KAFEINA ERP mengimplementasikan 27 tabel relasional yang dinormalisasi hingga bentuk normal ketiga (3NF) pada engine InnoDB.*
> 
> *Untuk menjamin performa pencarian stok tertua tetap optimal pada tabel persediaan yang besar, kami merancang indeks komposit strategis idx_wh_stock_fifo.*
> 
> *Lebih penting lagi, sistem kami dirancang tahan terhadap lonjakan transaksi bersamaan (high concurrency). Pada antrean kasir yang padat, dua kasir berpotensi menjual menu dengan bahan baku yang sama secara serentak. Sistem kami mengatasinya menggunakan penguncian baris pesimis (Pessimistic Row Locking melalui SELECT ... FOR UPDATE). Seluruh mutasi pemotongan stok dan pembukuan dibungkus dalam transaksi atomik ACID, sehingga jika terjadi kegagalan jaringan di tengah jalan, basis data melakukan rollback penuh sehingga tidak terjadi dirty writes maupun saldo stok negatif. "Implementasi nyata dari alur rantai pasok ini akan dijelaskan oleh rekan saya, Najmi."*

---

```
================================================================================
SLIDE 09: IMPLEMENTASI - PROCURE-TO-PAY & QC LAB
================================================================================
```
- **Kategori**: `IMPLEMENTASI PROYEK: HULU (PROCURE-TO-PAY & QC LAB)`
- **Judul Aksi**: Penyaringan Mutu Komprehensif Berdasarkan Parameter Fisik & Protokol Sensori SCA
- **Layout Visual**: 2 Kolom Kontras (Formula Evaluasi Mutu QC vs Registrasi Lot Otomatis).
- **Konten Teks Slide**:
  - **Formulasi Logika Diskrit Evaluasi Mutu QC Lab**:
    - `ACCEPTED` (Specialty Grade 1): $(10.0\% \le M \le 12.0\%) \land (D_{\text{primer}} = 0) \land (S_{\text{cupping}} \ge 80.0)$.
    - `REJECTED` (Substandard / Karantina): $(M < 9.0\% \lor M > 13.0\%) \lor (D_{\text{primer}} > 3) \lor (S_{\text{cupping}} < 75.0)$.
    - `CONDITIONAL`: Memerlukan tindakan penjemuran ulang (*re-drying*) atau negosiasi ulang harga komoditas.
    - Dampak: Menjamin roastery hanya memproses biji bermutu tinggi dan menjaga reputasi cita rasa produk.
  - **Registrasi Lot Otomatis & Ketertelusuran Asal Kebun**:
    - Kodifikasi Lot Unik: Pembangkitan otomatis kode `GB-[FARM]-[YEAR]-[SEQ]` (contoh: `GB-TMR-2026-0001`).
    - Metadata Lengkap: Varietas kopi, elevasi kebun (mdpl), metode olah pasca-panen (*Full Wash, Natural, Anaerobic*), dan kelompok tani mitra.
    - Mutasi Bertahap: Saldo gudang sentral baru bertambah setelah status QC `ACCEPTED` dan dokumen GRN diverifikasi.
    - Kepatuhan Halal & BPOM: Garis silsilah lot (*lineage*) dapat ditelusuri balik dari cangkir kasir ke kebun petani asal.
- **Durasi Rekomendasi**: 60 Detik (07:45 - 08:45)
- **Pembicara**: **M Najmi Zahrian**

> 🎙️ **NASKAH PEMBICARA (SPEAKER NOTES)**:
> *"Terima kasih rekan Dikas. Selamat pagi/siang Bapak Dosen dan rekan-rekan, saya M Najmi Zahrian akan memaparkan implementasi nyata dari sistem KAFEINA ERP.*
> 
> *Di sisi hulu rantai pasok, modul Procure-to-Pay kami integrasikan secara ketat dengan pengujian laboratorium Quality Control. Biji kopi mentah (green beans) yang dikirim oleh petani mitra tidak serta merta masuk ke gudang, melainkan harus lolos uji fisik dan sensori berstandar Specialty Coffee Association (SCA).*
> 
> *Kriteria penerimaan kami formulasikan secara matematis: kadar air harus berada pada rentang ideal 10 sampai 12 persen, nol cacat primer, dan skor uji cita rasa cupping minimal 80 poin. Setiap lot yang lolos otomatis diterbitkan kode registrasi unik, misalnya GB-TMR-2026-0001, lengkap dengan data ketinggian kebun dan metode proses pasca-panen. Ini memastikan transparansi penuh dan ketertelusuran dari hulu ke hilir."*

---

```
================================================================================
SLIDE 10: IMPLEMENTASI - ROASTERY YIELD & HPP RIIL
================================================================================
```
- **Kategori**: `IMPLEMENTASI PROYEK: MANUFAKTUR (ROASTERY YIELD & HPP RIIL)`
- **Judul Aksi**: Otomasi Formulasi Susut Bobot Termal 14–18% dan Rekalkulasi Dinamis HPP Riil
- **Layout Visual**: 2 Kolom Komparatif dengan persamaan matematika fraksional dan pembuktian numerik kasus uji.
- **Konten Teks Slide**:
  - **Formulasi Matematika Susut Bobot (Yield Loss)**:
    - Persamaan: $\text{Yield Loss (\%)} = \left(\frac{W_{\text{mentah}} - W_{\text{matang}}}{W_{\text{mentah}}}\right) \times 100\%$.
    - Standar Baku Industri: $14.0\% \le \text{Yield Loss} \le 18.0\%$.
    - Kontrol Kualitas Otomatis:
      - $< 14\%$: Peringatan *under-developed* (biji berisiko mentah/rasa kacang).
      - $> 18\%$: Peringatan *over-developed* (biji berisiko gosong/arang).
  - **Rekalkulasi Dinamis HPP Riil per Gram Matang**:
    - Persamaan Konservasi Biaya: $\text{HPP}_{\text{riil}} = \left(\frac{\text{HPP}_{\text{mentah}}}{1 - (\text{Yield Loss} / 100)}\right) + \text{Overhead Kemasan}$.
    - Pembuktian Kasus Uji `WB-P03`:
      - Biaya green bean: Rp 140 / gram (Rp 140.000 / kg).
      - Bobot mentah: 5.000 g | Bobot matang aktual: 4.240 g.
      - Yield Loss terhitung: 15.20% (Lolos toleransi 14–18%).
      - Overhead kemasan pouch & valve: Rp 3.20 / gram.
      - **HPP Riil Terbentuk**: **Rp 168.30 / gram matang**.
    - Konversi Atomik: Pengurangan stok biji hijau dan penambahan stok biji sangrai matang terjadi secara atomik dalam database.
- **Durasi Rekomendasi**: 60 Detik (08:45 - 09:45)
- **Pembicara**: **M Najmi Zahrian**

> 🎙️ **NASKAH PEMBICARA (SPEAKER NOTES)**:
> *"Pada modul manufaktur roastery, sistem KAFEINA ERP menyelesaikan tantangan kalkulasi termodinamika penyangraian kopi yang selama ini menjadi titik buta aplikasi ERP biasa.*
> 
> *Saat disangrai, biji kopi melepaskan air dan gas sehingga terjadi penyusutan bobot fisik antara 14 hingga 18 persen. Pada sistem kami, saat head roaster memasukkan bobot akhir sangrai pada lembar Work Order, sistem langsung menghitung persentase yield loss dan memvalidasi apakah proses sangrai berada pada profil yang tepat.*
> 
> *Lebih dari itu, sistem secara dinamis merekalibrasi Harga Pokok Penjualan (HPP) riil per gram biji sangrai matang. Pada contoh kasus uji nyata kami di slide, biaya biji mentah Rp 140 per gram dengan susut 15.2% dan kemasan sekunder menghasilkan HPP riil baru sebesar Rp 168.30 per gram matang. Rekalkulasi instan ini menjamin laporan finansial dan margin keuntungan gerai disajikan secara riil dan presisi."*

---

```
================================================================================
SLIDE 11: IMPLEMENTASI - LOGISTIK FIFO & WEB POS
================================================================================
```
- **Kategori**: `IMPLEMENTASI PROYEK: HILIR (LOGISTIK FIFO & WEB POS)`
- **Judul Aksi**: Alokasi Batch Tertua FIFO dan Pemotongan Resep BOM Multi-Bahan Seketika pada POS
- **Layout Visual**: Bento Grid 3 Kolom Alur Hilir (Logistik FIFO, Touch Web POS, Shift Closing).
- **Konten Teks Slide**:
  - **Logistik Distribusi & Alokasi FIFO**:
    - Kueri sistem mengunci batch tertua berbasis tanggal sangrai (`roasted_date ASC`).
    - Isolasi stok pada akun virtual `IN_TRANSIT` saat barang dikirim dari sentral.
    - Manajer gerai cabang memverifikasi penerimaan fisik melalui pemindaian QR code Surat Jalan untuk mencatat selisih (*discrepancy*).
  - **Terminal Web POS Layar Sentuh**:
    - Desain ergonomis tablet dengan ukuran target sentuh $\ge 48$ piksel dan keypad numerik cepat.
    - Standar animasi Emil Kowalski: aksi frekuensi tinggi 0ms (instan), modal/drawer $\le 300$ms.
    - Pembayaran multi-channel: Tunai dengan kalkulator kembalian cepat dan Dynamic QRIS Midtrans.
  - **BOM Auto-Deduct & Shift Closing**:
    - Pemotongan Resep Otomatis: 1 cup Caffe Latte langsung memotong 18g espresso beans, 200ml fresh milk, dan 1 paper cup secara atomik.
    - Rekonsiliasi Kas Aman (*Blind Count*): Kasir menghitung uang fisik tanpa melihat estimasi sistem.
    - Otorisasi Supervisor: Jika selisih kas $> \text{Rp } 10.000$, wajib verifikasi PIN supervisor sebelum shift tertutup.
- **Durasi Rekomendasi**: 60 Detik (09:45 - 10:45)
- **Pembicara**: **M Najmi Zahrian**

> 🎙️ **NASKAH PEMBICARA (SPEAKER NOTES)**:
> *"Di sisi hilir rantai pasok, sistem menyinkronkan alur distribusi pergudangan dengan titik penjualan ritel.*
> 
> *Pengiriman dari gudang sentral ke gerai cabang dipandu oleh alokasi FIFO otomatis berdasarkan tanggal sangrai dan diisolasi dalam status virtual IN_TRANSIT untuk mencegah kebocoran barang di perjalanan.*
> 
> *Pada titik penjualan, terminal Web POS dirancang khusus untuk kenyamanan barista dengan tombol sentuh berukuran minimal 48 piksel dan animasi instan tanpa jeda. Setiap kali pesanan diselesaikan, mesin Auto-Deduct langsung memotong stok bahan baku resep BOM—seperti biji kopi espresso, susu segar, dan paper cup—secara atomik dan mencatatnya ke kartu stok.*
> 
> *Pada akhir hari kerja, proses penutupan shift kasir diamankan dengan metode blind count, di mana jika terdeteksi selisih kas lebih dari sepuluh ribu rupiah, sistem mewajibkan otorisasi PIN supervisor. "Selanjutnya, pembuktian keandalan sistem melalui pengujian multi-tier akan dipaparkan oleh rekan saya, Dhea."*

---

```
================================================================================
SLIDE 12: TESTING - STRATEGI 5-TIER ENTERPRISE
================================================================================
```
- **Kategori**: `STRATEGI PENGUJIAN: 5-TIER ENTERPRISE QUALITY FRAMEWORK`
- **Judul Aksi**: Dekonstruksi Piramida Pengujian Konvensional Menjadi Kerangka Kerja Mutu 5 Lapis
- **Layout Visual**: 5 Baris Kartu Bertingkat / Piramida Mutu Modern dari Tier 1 (Statis) hingga Tier 5 (Browser E2E).
- **Konten Teks Slide**:
  - **Tier 1: Static Analysis (Larastan Level 5)**: Memindai 45 kelas sumber tanpa eksekusi kode, memvalidasi kompatibilitas tipe PHP 8.3, nullability, dan eliminasi `env()` di luar konfigurasi.
  - **Tier 2: Architecture Compliance (Pest Architecture Testing)**: Menegakkan 6 aturan desain arsitektur enterprise: pemisahan Model, Controller, Service Layer, dan eliminasi fungsi debugging kotor (`dd`, `dump`, `ray`).
  - **Tier 3: Structural White-Box (McCabe Basis Path & CFG)**: Memodelkan Graf Aliran Kendali (CFG), menghitung Kompleksitas Siklomatis $V(G) = 3$, dan menguji seluruh jalur independen.
  - **Tier 4: Core Domain Feature (Pest PHP v4 & PHPUnit 12)**: Rangkaian 155 kasus uji terintegrasi dan 696 asersi domain menguji alur rantai pasok dalam isolasi transaksi basis data.
  - **Tier 5: E2E Browser Automation (Laravel Dusk & Chrome Headless)**: Mengemudikan peramban Chrome sesungguhnya melalui WebDriver untuk menguji interaktivitas UI, aset Vite, dan modal kasir.
- **Durasi Rekomendasi**: 45 Detik (10:45 - 11:30)
- **Pembicara**: **Dhea Rafael M.P**

> 🎙️ **NASKAH PEMBICARA (SPEAKER NOTES)**:
> *"Terima kasih rekan Najmi. Selamat pagi/siang Bapak Dosen dan rekan-rekan, saya Dhea Rafael M.P akan memaparkan pilar kelima: Pengujian Sistem (Testing).*
> 
> *Sesuai dengan arahan Bapak Dosen, pengujian sistem KAFEINA ERP tidak sekadar mengandalkan pengujian kotak hitam konvensional. Pengujian kotak hitam terbukti memiliki titik buta fatal: tidak dapat menguji konservasi massa sangrai, pembukuan berpasangan net-zero, dan isolasi transaksi atomik basis data.*
> 
> *Oleh karena itu, kami mendekonstruksi piramida pengujian konvensional menjadi Arsitektur Pengujian Lima Lapis atau 5-Tier Enterprise Quality Framework: mulai dari Tier 1 Analisis Statis Larastan Level 5, Tier 2 Kepatuhan Arsitektur Pest, Tier 3 Pengujian Kotak Putih Basis Path McCabe, Tier 4 Pengujian Fitur Inti Domain Bisnis, hingga Tier 5 Otomasi Peramban Laravel Dusk pada Google Chrome asli. Mari kita telaah hasil pembuktian tiap tingkatan."*

---

```
================================================================================
SLIDE 13: TESTING - TIER 1 & TIER 2
================================================================================
```
- **Kategori**: `PENGUJIAN TIER 1 & 2: LARASTAN LEVEL 5 & PEST ARCHITECTURE`
- **Judul Aksi**: Larastan Level 5 Mencapai Zero Static Defect dan Pest Menegakkan 6 Aturan Desain
- **Layout Visual**: 2 Kolom Kontras Besar (Larastan Static Analysis vs Pest Architecture Rules).
- **Konten Teks Slide**:
  - **Tier 1: Larastan / PHPStan Level 5 (Hasil: 0 Error / PASS)**:
    - Objek Uji: 45 file kelas sumber PHP pada namespace `App\` (Controllers, Models, Services, Middleware, Enums).
    - Resolusi 16 Defek Laten Kritis:
      1. Menghapus pemanggilan `env('MIDTRANS_SERVER_KEY')` di `MidtransQrisService` dan menggantinya dengan `config()`, menjamin pembayaran aman saat perintah `php artisan config:cache` aktif di server produksi.
      2. Penyelarasan casting Eloquent datetime pada `pin_locked_until` di model `User`.
      3. Pengetikan ketat parameter `str_pad()` pada 5 kontroler.
      4. Guard clause pengecekan tipe objek `Product` pada alokasi stok FIFO.
  - **Tier 2: Pest Architecture Testing (Hasil: 6 Passed, 7 Assertions)**:
    - ✓ 1. *Zero Debugging*: Bebas fungsi `dd`, `dump`, `ray`, dan `var_dump`.
    - ✓ 2. *Model Inheritance*: Seluruh model mewarisi `Illuminate\Database\Eloquent\Model`.
    - ✓ 3. *Controller Standardization*: Seluruh kontroler mewarisi basis `Controller`.
    - ✓ 4. *Service Layer Isolation*: Logika bisnis terisolasi di namespace `App\Services`.
    - ✓ 5. *Strict Enums*: Seluruh enumerasi merupakan PHP 8.1 Backed Enums.
    - ✓ 6. *No Direct env()*: Larangan pemanggilan `env()` langsung di luar `config/`.
- **Durasi Rekomendasi**: 45 Detik (11:30 - 12:15)
- **Pembicara**: **Dhea Rafael M.P**

> 🎙️ **NASKAH PEMBICARA (SPEAKER NOTES)**:
> *"Pada Tier 1, kami mengonfigurasi analisis statis Larastan atau PHPStan pada Level 5 yang sangat ketat untuk memindai 45 kelas PHP. Hasilnya sempurna: kami meraih status Zero Static Defect atau 0 error.*
> 
> *Larastan berhasil menemukan 16 defek pengetikan laten sebelum sistem sempat dijalankan. Salah satu temuan paling krusial adalah ditemukannya pemanggilan env() secara langsung di dalam layanan Midtrans. Pemanggilan ini jika dibiarkan akan mengembalikan nilai null ketika mode config:cache diaktifkan di server produksi, yang berpotensi melumpuhkan sistem pembayaran kasir. Kami menyelesaikannya secara tuntas.*
> 
> *Pada Tier 2, kami menjalankan Pest Architecture Testing. Enam aturan arsitektur enterprise terpenuhi 100 persen: basis kode bersih dari fungsi debugging kotor, struktur pewarisan kelas terstandarisasi, dan lapisan logika bisnis terisolasi secara disiplin."*

---

```
================================================================================
SLIDE 14: TESTING - TIER 3 & TIER 4
================================================================================
```
- **Kategori**: `PENGUJIAN TIER 3 & 4: WHITE-BOX BASIS PATH & 155 FITUR`
- **Judul Aksi**: 100% Branch Coverage dengan McCabe V(G)=3 dan Kelulusan Penuh 155 Uji Fitur Domain
- **Layout Visual**: 2 Kolom Komparatif (Pemodelan CFG McCabe vs Rekapitulasi Feature Test Suite).
- **Konten Teks Slide**:
  - **Tier 3: White-Box Basis Path & Control Flow Graph (CFG)**:
    - Pemodelan CFG Modul Keputusan QC Lab:
      - Simpul Program ($N$): 7 simpul.
      - Sisi Program ($E$): 8 sisi.
      - Simpul Keputusan ($P_d$): 2 simpul predikat.
    - Perhitungan Kompleksitas Siklomatis McCabe:
      - $V(G) = E - N + 2P = 8 - 7 + 2(1) = 3$.
      - $V(G) = P_d + 1 = 2 + 1 = 3$ (3 Wilayah Bidang).
    - Eksekusi 3 Jalur Basis Independen:
      - Jalur 1: Simpul $1 \rightarrow 2 \rightarrow 3 \rightarrow 7$ (Skor SCA $< 80 \rightarrow$ `REJECTED`).
      - Jalur 2: Simpul $1 \rightarrow 2 \rightarrow 4 \rightarrow 5 \rightarrow 7$ (SCA $\ge 80$, air marjinal $\rightarrow$ `CONDITIONAL`).
      - Jalur 3: Simpul $1 \rightarrow 2 \rightarrow 4 \rightarrow 6 \rightarrow 7$ (SCA $\ge 80$, air ideal 10-12% $\rightarrow$ `ACCEPTED`).
    - **Hasil: 100% Branch Coverage** tercapai pada 8 alur bisnis kritis.
  - **Tier 4: Core Domain Feature Test Suite (Pest & PHPUnit)**:
    - 155 Kasus Uji Otomatis & 696 Asersi Domain Bisnis Lulus Penuh:
      - `ArchitectureTest`: 6 tests (7 assertions) [PASS]
      - `ProcurementWorkflowTest`: 18 tests (74 assertions) [PASS]
      - `QualityControlWorkflowTest`: 22 tests (98 assertions) [PASS]
      - `RoasteryProductionTest`: 24 tests (112 assertions) [PASS]
      - `InventoryTransferTest`: 20 tests (86 assertions) [PASS]
      - `PosCheckoutRecipeDeductTest`: 28 tests (134 assertions) [PASS]
      - `AtomicTransactionRollbackTest`: 12 tests (58 assertions) [PASS]
      - `SafetyStockAlertTest`: 14 tests (62 assertions) [PASS]
      - `RbacAuthorizationGuardTest`: 11 tests (45 assertions) [PASS]
    - Durasi Eksekusi: 36.77 detik dengan isolasi `DatabaseTransactions`.
- **Durasi Rekomendasi**: 45 Detik (12:15 - 13:00)
- **Pembicara**: **Dhea Rafael M.P**

> 🎙️ **NASKAH PEMBICARA (SPEAKER NOTES)**:
> *"Pada Tier 3, kami menerapkan teknik pengujian kotak putih murni melalui pemodelan Control Flow Graph (CFG) dan perhitungan Kompleksitas Siklomatis McCabe pada modul evaluasi mutu laboratorium kopi.*
> 
> *Dengan 7 simpul, 8 sisi, dan 2 simpul keputusan, nilai kompleksitas siklomatis V(G) diperoleh tepat bernilai 3. Ketiga jalur independen—mulai dari lot yang ditolak, lot bersyarat, hingga lot yang diterima berpredikat Specialty Grade 1—berhasil dieksekusi secara tuntas sehingga membuktikan pencapaian 100 persen branch coverage.*
> 
> *Melanjutkan ke Tier 4, seluruh alur bisnis rantai pasok diuji secara komprehensif melalui 155 kasus uji fitur otomatis yang memvalidasi 696 asersi domain bisnis. Seluruh pengujian diselesaikan dalam waktu 36.7 detik dengan tingkat kelulusan sempurna 100 persen tanpa satu pun kegagalan."*

---

```
================================================================================
SLIDE 15: TESTING - TIER 5 & TOTAL REKAPITULASI
================================================================================
```
- **Kategori**: `PENGUJIAN TIER 5 & REKAPITULASI EMPIRIS`
- **Judul Aksi**: Verifikasi Antarmuka Browser Dusk dan Rekapitulasi Metrik Mutu Sempurna 100%
- **Layout Visual**: Bagian Atas: Ringkasan Dusk Browser; Bagian Bawah: Tabel Rekapitulasi Metrik 5-Tier.
- **Konten Teks Slide**:
  - **Tier 5: Otomasi Peramban Riil Laravel Dusk (Headless Chrome 157)**:
    - Menjalankan ChromeDriver sesungguhnya untuk merender aset terkompilasi Vite, CSS Tailwind v4, dan skrip Alpine.js.
    - Menguji skenario login autentikasi, navigasi dasbor analitik cabang, interaksi layar sentuh kasir, dan pembukaan modal rekonsiliasi kasir.
    - Menghasilkan bukti visual tangkapan layar otentik (`01_login_page.png`, `02_dashboard_overview.png`, `04_pos_touch_terminal.png`, `05_pos_shift_modal.png`).
  - **Tabel Rekapitulasi Metrik Pengujian 5-Tier**:
    | Tingkatan Pengujian | Framework / Engine | Cakupan Objek Uji | Jumlah Kasus / Aturan | Durasi | Hasil Kelulusan |
    | :--- | :--- | :--- | :--- | :--- | :--- |
    | **Tier 1: Static Analysis** | Larastan / PHPStan 2.3.7 | 45 File Kelas (`app/`) | Aturan Level 5 | 8.4 detik | **LULUS (0 Error)** |
    | **Tier 2: Architecture Guard** | Pest Architecture Engine | Integritas Lapisan Kode | 6 Uji (7 Asersi) | 3.9 detik | **LULUS (100%)** |
    | **Tier 3 & 4: Core Domain** | Pest PHP v4 & PHPUnit 12 | 8 Jalur Kritis Rantai Pasok | 155 Uji (696 Asersi) | 36.7 detik | **LULUS (100%)** |
    | **Tier 5: E2E Browser Testing**| Laravel Dusk & Chrome 157| Antarmuka Login, Dasbor, POS| 3 Skenario (7 Asersi)| 19.1 detik | **LULUS (100%)** |
    | **TOTAL KESELURUHAN** | **5-Tier Testing Suite** | **Sistem KAFEINA ERP** | **164 Uji Aktif** | **68.1 detik**| **PERFECT (100%)** |
- **Durasi Rekomendasi**: 45 Detik (13:00 - 13:45)
- **Pembicara**: **Dhea Rafael M.P**

> 🎙️ **NASKAH PEMBICARA (SPEAKER NOTES)**:
> *"Pada tingkatan tertinggi yaitu Tier 5, kami membuktikan fungsionalitas antarmuka melalui pengujian peramban otomatis Laravel Dusk pada Google Chrome headless.*
> 
> *Dusk mengeksekusi aset terkompilasi Vite, styling Tailwind CSS v4, dan interaktivitas Alpine.js secara nyata, membuktikan kelancaran alur login, pemuatan dasbor, hingga modal rekonsiliasi kas kasir tanpa cacat visual.*
> 
> *Bapak Dosen dan rekan-rekan, Tabel Rekapitulasi pada slide merangkum pencapaian empiris pengujian sistem KAFEINA ERP: total 164 pengujian aktif dengan lebih dari 710 asersi yang mencakup seluruh lapisan kode, dieksekusi dalam total durasi 68.1 detik dengan tingkat kelulusan sempurna 100 persen (Perfect Pass Rate). Ini membuktikan bahwa sistem kami tidak hanya memenuhi spesifikasi fungsional, namun juga memiliki integritas arsitektur tingkat enterprise yang sangat andal."*

---

```
================================================================================
SLIDE 16: KESIMPULAN EKSEKUTIF & SESI TANYA JAWAB (Q&A)
================================================================================
```
- **Kategori**: `KESIMPULAN AKADEMIK & TANYA JAWAB`
- **Judul Aksi**: KAFEINA ERP: Fondasi Rantai Pasok Kopi Spesialti Terpadu dengan Keandalan Teruji Standar Enterprise
- **Layout Visual**: 3 Kartu Nilai Tambah Sistem + Banner Sesi Diskusi / Tanya Jawab di bagian bawah.
- **Konten Teks Slide**:
  - **1. Integrasi Hulu-ke-Hilir**: Menghubungkan pengadaan petani, laboratorium QC, roastery, gudang multi-cabang, dan kasir POS dalam satu ekosistem tanpa fragmentasi data.
  - **2. Presisi Akuntansi & Fisik**: Mengotomatisasi susut bobot sangrai (yield loss 14-18%), rekalkulasi HPP riil fraksional per gram, alokasi FIFO, dan pemotongan bahan resep BOM secara pesimistik.
  - **3. Jaminan Mutu 5-Tier**: Keterlacakan penuh dari URS ke pengujian melalui RTM, 0 error Larastan Level 5, 100% Branch Coverage, dan kelulusan 164 pengujian otomatis.
  - **Sesi Tanya Jawab Dibuka**: Kami siap mendiskusikan aspek Rekayasa Kebutuhan, Desain Arsitektur, Implementasi Kode, maupun Metodologi Pengujian Sistem.
- **Durasi Rekomendasi**: 75 Detik (13:45 - 15:00)
- **Pembicara**: **Iqsan Azhar & Seluruh Anggota Tim**

> 🎙️ **NASKAH PEMBICARA (SPEAKER NOTES)**:
> *"Sebagai kesimpulan penutup, KAFEINA ERP membuktikan bahwa integrasi komprehensif antara rekayasa kebutuhan (USR), spesifikasi teknis formal (SRS), desain arsitektur bersih (SDD), implementasi berorientasi layanan, serta pengujian terpadu 5-tier berhasil menjawab tuntas tiga tantangan besar industri kopi spesialti: asimetri mutasi stok, ketiadaan rekalkulasi HPP riil sangrai, dan inefisiensi logistik multi-cabang.*
> 
> *Sistem ini siap memberikan dampak nyata bagi tata kelola bisnis ritel modern dengan efisiensi dan transparansi yang tinggi.*
> 
> *Demikian pemaparan presentasi dari Kelompok 1. Kami membuka sesi tanya jawab dan sangat mengapresiasi masukan, tanggapan, serta pertanyaan dari Bapak Ir. Muhammad Ihsan Fawzi, M.Kom. beserta rekan-rekan sekalian. Terima kasih atas perhatiannya. Wassalamu'alaikum Warahmatullahi Wabarakatuh."*

---

## 🎯 PANDUAN STRATEGIS MENGHADAPI PERTANYAAN DOSEN (Q&A DEFENSE)

Berdasarkan karakter pengujian tugas besar ERP dan Rekayasa Perangkat Lunak, berikut adalah 4 pertanyaan kritis yang sering diajukan dosen dan strategi jawaban yang disiapkan:

### Pertanyaan 1: "Mengapa Anda menggunakan Pessimistic Locking (SELECT ... FOR UPDATE) pada kasir POS, bukan Optimistic Locking?"
- **Penjawab yang Direkomendasikan**: **Dikas Setio Pramudo** (Database Architect)
- **Jawaban Kunci**:
  > *"Terima kasih atas pertanyaannya, Pak. Pada operasional kedai kopi di jam sibuk (rush hour), beberapa kasir melayani transaksi secara simultan dari stok fisik gerai yang sama. Jika menggunakan Optimistic Locking, sistem akan membiarkan transaksi berjalan dan baru membatalkannya di akhir (via throw exception concurrency). Hal ini berakibat fatal pada antrean kasir ritel karena pelanggan yang sudah memesan dan siap membayar akan mengalami transaksi gagal dan kasir harus menginput ulang.*
  > 
  > *Dengan Pessimistic Locking (`lockForUpdate()`), baris stok bahan baku dikunci secara atomik selama hitungan milidetik saat transaksi dieksekusi. Kasir kedua akan menunggu antrean kueri sesaat tanpa memicu transaksi gagal, sehingga menjamin konsistensi stok tanpa race condition dan pengalaman antrean tetap mulus."*

### Pertanyaan 2: "Mengapa perhitungan HPP harus dihitung ulang setelah proses sangrai? Apa rumusnya?"
- **Penjawab yang Direkomendasikan**: **M Najmi Zahrian** (Backend Lead)
- **Jawaban Kunci**:
  > *"Terima kasih, Pak. Biji kopi mentah (green beans) yang disangrai mengalami penguapan air dan gas termal sehingga massanya menyusut antara 14% hingga 18% (Yield Loss). Misalnya kita membeli 5.000 gram green bean seharga Rp 140/gram (total Rp 700.000). Setelah disangrai, bobot matangnya menjadi 4.240 gram (susut 15.2%).*
  > 
  > *Jika HPP tetap dihitung Rp 140/gram, maka total nilai stok matang hanya dicatat Rp 593.600, yang berarti perusahaan 'kehilangan' nilai aset Rp 106.400 di laporan keuangan. Oleh karena itu, sistem KAFEINA ERP merekalibrasi HPP riil per gram matang dengan membagi total biaya bahan baku dan kemasan dengan bobot matang aktual:*
  > $$\text{HPP}_{\text{riil}} = \frac{\text{HPP}_{\text{mentah}}}{1 - (\text{YieldLoss}/100)} + \text{Overhead} = \frac{140}{1 - 0.152} + 3.20 = \text{Rp } 168.30\text{ per gram}$$
  > *Sehingga laba kotor penjualan per cangkir di kasir dihitung secara akurat berdasarkan HPP riil ini."*

### Pertanyaan 3: "Mengapa Anda tidak cukup menggunakan Black-Box Testing saja? Mengapa butuh sampai 5-Tier Testing?"
- **Penjawab yang Direkomendasikan**: **Dhea Rafael M.P** (QA Lead)
- **Jawaban Kunci**:
  > *"Terima kasih atas pertanyaannya, Pak. Pengujian kotak hitam (Black-Box) hanya menguji permukaan antarmuka input-output, namun memiliki keterbatasan fatal pada sistem ERP. Black-box tidak dapat membuktikan apakah mutasi kartu stok menerapkan prinsip konservasi massa (net-zero difference), tidak dapat mendeteksi kegagalan rollback atomik basis data saat terjadi error jaringan, dan buta terhadap kebocoran pemanggilan konfigurasi runtime.*
  > 
  > *Dengan 5-Tier Testing: Tier 1 Larastan Level 5 membuktikan zero static defects pada 45 kelas PHP dan menangkap kebocoran fungsi env(); Tier 2 Pest Architecture menegakkan disiplin arsitektur; Tier 3 White-Box McCabe menjamin 100% branch coverage; Tier 4 menguji 155 kasus fitur bisnis; dan Tier 5 Dusk membuktikan otomasi browser sesungguhnya. Pendekatan ini memberikan jaminan keandalan tingkat enterprise yang terbukti secara empiris."*

### Pertanyaan 4: "Bagaimana cara Anda membuktikan bahwa semua kebutuhan di URS dan SRS benar-benar sudah diimplementasikan dan diuji?"
- **Penjawab yang Direkomendasikan**: **Akmal Adhi Nugroho** (SRS Lead) & **Iqsan Azhar** (Koordinator)
- **Jawaban Kunci**:
  > *"Terima kasih, Pak. Kami membuktikannya melalui Requirements Traceability Matrix (RTM) yang terdokumentasi di dokumen SRS Bab 2.3 dan SDD. Di dalam RTM, setiap butir kebutuhan pengguna di URS (misalnya FR-PRC-04 untuk validasi mutu QC) dipetakan secara 1-ke-1 ke kode spesifikasi SRS (REQ-PRC-04), ke metode di Service Layer (QualityControlService::evaluate), ke tabel database (quality_controls), hingga ke ID kasus uji di test suite kami (WB-P02 dan Tier2-Domain-02).*
  > 
  > *Matriks ini membuktikan bahwa tidak ada kebutuhan pengguna yang terlewat (zero unimplemented requirement) dan seluruh baris kode diverifikasi oleh test suite kami dengan 100% pass rate."*

---
*Dokumen ini disusun untuk menyertai berkas presentasi PowerPoint `Presentasi_Kafeina_ERP_Kelompok_1.pptx` yang telah digenerate di direktori `docs/`.*
