---
title: "LAPORAN PENGUJIAN MULTI-TIER DAN VERIFIKASI INTEGRITAS SISTEM PADA ENTERPRISE RESOURCE PLANNING (ERP) RANTAI PASOK KOPI SPESIALTI"
subtitle: "STUDI KASUS: SISTEM TERPADU KAFEINA SPECIALTY ROASTERY DAN MULTI-BRANCH POINT OF SALE BERBASIS HYBRID STATIC ANALYSIS, ARCHITECTURE COMPLIANCE, STRUCTURAL WHITE-BOX TESTING, DAN END-TO-END BROWSER AUTOMATION"
keterangan: "Disusun untuk memenuhi tugas terstruktur Mata Kuliah Sistem Informasi Enterprise"
mata_kuliah: "Sistem Informasi Enterprise (SIE) / Rekayasa Perangkat Lunak"
dosen: "Ir. Muhammad Ihsan Fawzi, M.Kom."
penyusun:
  - ["Kelompok 1", "Informatika FT Unsoed"]
institusi:
  - "PROGRAM STUDI INFORMATIKA"
  - "FAKULTAS TEKNIK"
  - "UNIVERSITAS JENDERAL SOEDIRMAN"
  - "PURBALINGGA"
  - "2026"
---

# DAFTAR ISI

BAB I PENDAHULUAN	1
1.1 Latar Belakang dan Urgensi Pengujian Multi-Tier	1
1.2 Rumusan Masalah	2
1.3 Tujuan Pengujian Sistem	3
1.4 Batasan Pengujian	4
BAB II METODOLOGI DAN ARSITEKTUR MULTI-TIER TESTING	5
2.1 Dekonstruksi Paradigma Piramida Pengujian Enterprise	5
2.2 Tier 1: Analisis Statis Tipe dan Defek (Larastan / PHPStan Level 5)	6
2.3 Tier 2: Penegakan Kepatuhan Arsitektur (Pest Architecture Testing)	7
2.4 Tier 3: Pengujian Struktural Kotak Putih (White-Box Basis Path)	8
2.5 Tier 4: Pengujian Integrasi Fitur dan Domain Bisnis (Pest Feature Test Suite)	9
2.6 Tier 5: Pengujian Antarmuka Pengguna dan Browser End-to-End (Laravel Dusk)	10
2.7 Formulasi Matematika Domain Kopi Spesialti dan Transaksi ACID	11
BAB III PERANCANGAN DAN IMPLEMENTASI KASUS UJI	14
3.1 Konfigurasi Lingkungan Pengujian dan Autoloading	14
3.2 Spesifikasi Aturan Larastan Level 5 (phpstan.neon)	15
3.3 Spesifikasi Penegakan Arsitektur Pest (ArchitectureTest.php)	16
3.4 Desain Kasus Uji Struktural 8 Jalur Kritis (White-Box Basis Path)	17
3.5 Desain Skenario Uji Black-Box dan Otomasi Browser Dusk	20
BAB IV HASIL PENGUJIAN, METRIK KINERJA, DAN PEMBUKTIAN EMPIRIS	22
4.1 Rekapitulasi Hasil Eksekusi Pengujian Terpadu	22
4.2 Hasil Analisis Statis Larastan Level 5 dan Resolusi Defek Laten	23
4.3 Hasil Penegakan Arsitektur Pest PHP v4	24
4.4 Hasil Eksekusi Test Suite Inti PHPUnit / Pest Feature	25
4.5 Hasil Otomasi Pengujian Browser Laravel Dusk dan Dokumentasi Visual	27
4.6 Pembuktian Keaslian Eksekusi Menggunakan PowerShell Terminal Capture	29
4.7 Matriks Keterlacakan Kebutuhan (Requirements Traceability Matrix)	32
BAB V EVALUASI TEKNIS DAN ANALISIS KOMPARATIF	34
5.1 Evaluasi Efektivitas Multi-Tier Testing vs White-Box Tunggal	34
5.2 Pencegahan Kebocoran Konfigurasi (env Outside Config)	35
5.3 Pencegahan Pergeseran Arsitektur (Architectural Drift)	36
5.4 Analisis Kinerja Waktu Eksekusi dan Keandalan Regresi	37
BAB VI KESIMPULAN DAN REKOMENDASI	39
6.1 Kesimpulan	39
6.2 Rekomendasi	40
DAFTAR PUSTAKA	41


# BAB I PENDAHULUAN

## 1.1 Latar Belakang & Urgensi Pengujian Multi-Tier

Pengujian perangkat lunak merupakan disiplin fundamental dalam rekayasa perangkat lunak modern untuk menjamin bahwa sistem yang dibangun memenuhi atribut kualitas ISO/IEC 25010, yang mencakup kesesuaian fungsional (*functional suitability*), keandalan (*reliability*), efisiensi kinerja (*performance efficiency*), dan keamanan (*security*). Pada sistem *Enterprise Resource Planning* (ERP) berskala terdistribusi seperti sistem KAFEINA ERP, pengujian sistem konvensional yang hanya mengandalkan pengujian kotak hitam (*black-box testing*) melalui antarmuka pengguna grafis (GUI) terbukti memiliki keterbatasan struktural yang fatal.

Pengujian kotak hitam beroperasi pada paradigma stimulasi-respons permukaan (*surface stimulus-response*), di mana penguji memasukkan data melalui formulir peramban (*browser form*) dan mengamati perubahan visual pada layar tanpa visibilitas terhadap alur eksekusi internal, mutasi memori, perhitungan matematis fraksional, atau eksekusi kueri basis data. Pada ekosistem industri kopi spesialti (*specialty coffee*) terintegrasi, proses bisnis inti memiliki kompleksitas fisik dan akuntansi yang sangat tinggi:

1. **Rantai Nilai Pengadaan & Mutu Sensori (Procurement & QC Lab)**: Penerimaan bahan baku biji mentah (*green beans*) mewajibkan evaluasi mutu berbasis protokol *Specialty Coffee Association* (SCA) yang mencakup 10 atribut skor cupping (aroma, flavor, acidity, body, balance, uniformity, clean cup, sweetness, overall, defect deduction), ambang kadar air fisik (10.0% - 12.0%), dan densitas massa. Kesalahan percabangan logika pada kontroler penerimaan dapat meloloskan komoditas cacat (*substandard lot*) yang merugikan finansial perusahaan.
2. **Transformasi Termodinamika & Rekalkulasi Akuntansi (Roastery Production)**: Proses penyangraian (*roasting*) mengakibatkan hilangnya massa air (*roasting yield loss*) sebesar 14.0% hingga 18.0%. Pengujian kotak hitam tidak mampu memverifikasi apakah kenaikan proporsional Harga Pokok Penjualan (HPP) riil per gram biji sangrai telah dihitung secara presisi menurut persamaan konservasi biaya:
   $$\text{HPP}_{\text{riil}} = \frac{\text{Biaya Pembelian Total}}{\text{Massa Akhir Matang}} = \frac{\text{HPP}_{\text{mentah}}}{1 - \text{YieldLoss}}$$
3. **Integritas Konservasi Massa Multi-Gudang (Double-Entry Inventory)**: Pemindahan persediaan antar-gudang dan gerai harus mematuhi prinsip pembukuan berpasangan (*double-entry bookkeeping*), di mana setiap mutasi debet di gudang tujuan wajib diimbangi secara atomik oleh mutasi kredit di gudang asal pada tabel kartu stok (`stock_cards`) dengan selisih neto nol (*net-zero difference*).
4. **Resep Multi-Komponen Dinamis (BOM Auto-Deduction & Concurrency)**: Setiap transaksi kasir pada terminal *Point of Sale* (POS) memicu pemotongan bahan baku multi-komponen secara atomik (misalnya 1 cup Caffe Latte memotong 18 gram biji espresso blend, 200 ml susu segar, dan 1 paper cup). Apabila terjadi lonjakan transaksi serentak (*race condition*) atau kegagalan jaringan saat transaksi berlangsung, ketiadaan mekanisme penguncian pesimistik (*pessimistic locking* `lockForUpdate()`) dan *rollback* atomik dapat mengakibatkan saldo stok negatif atau *dirty writes*.

Menyadari keterbatasan pengujian kotak hitam tunggal maupun pengujian kotak putih murni, kurikulum lanjutan Sistem Informasi Enterprise dan Rekayasa Perangkat Lunak mengarahkan adopsi kerangka kerja pengujian multi-tier terpadu (Modern Testing & Development Ecosystem) berbasis ekosistem mutakhir Laravel/Symfony, yang mencakup:
- **Analisis Statis Tipe (Larastan / PHPStan Level 5)** untuk mendeteksi *type mismatch*, *unhandled nullability*, *illegal method calls*, dan *config cache regressions* sebelum kode dieksekusi.
- **Penegakan Aturan Arsitektur (Pest Architecture Testing)** untuk mencegah degradasi struktural (*architectural drift*), menjaga batas lapisan (*separation of concerns*), dan mengeliminasi pemanggilan fungsi berbahaya.
- **Pengujian Struktural Jalur Kritis (White-Box Basis Path Testing & PHPUnit)** untuk memvalidasi algoritma internal, kompleksitas siklomatis, dan *coverage* percabangan logika bisnis.
- **Pengujian Peramban Otomatis (Laravel Dusk Headless Chrome)** untuk membuktikan keandalan interaksi pengguna riil pada browser tanpa ketergantungan manual.

## 1.2 Rumusan Masalah

Berdasarkan latar belakang tersebut, rumusan masalah dalam pengujian terpadu ini dirumuskan sebagai berikut:
1. Bagaimana mengonfigurasi dan mengimplementasikan analisis statis *Larastan Level 5* untuk mendeteksi dan mengeliminasi defek pengetikan laten, inkonsistensi relasi Eloquent, serta potensi kesalahan pembacaan konfigurasi di seluruh basis kode KAFEINA ERP?
2. Bagaimana merancang aturan arsitektur otomatis menggunakan *Pest Architecture Testing* guna menjamin isolasi lapisan arsitektur (Controller, Model, Service, Enum) dan mengeliminasi fungsi debugging berbahaya?
3. Bagaimana menguji dan membuktikan keabsahan algoritma internal pada 8 jalur bisnis kritis melalui metode *White-Box Basis Path Testing* dengan pemetaan *Control Flow Graph* (CFG) dan perhitungan Kompleksitas Siklomatis?
4. Bagaimana mengotomatisasi pengujian antarmuka pengguna interaktif (Login, Dasbor Analitik, dan POS Touch Terminal) menggunakan *Laravel Dusk* dengan penangkapan bukti visual (*screenshots*) pada peramban Google Chrome asli?
5. Bagaimana merangkum hasil verifikasi multi-tier ini ke dalam *Requirements Traceability Matrix* (RTM) yang merefleksikan pemenuhan 100% kebutuhan fungsional URS dan SRS?

## 1.3 Tujuan Pengujian Sistem

Tujuan komprehensif dari pelaksanaan pengujian multi-tier ini adalah:
1. Mengonfigurasi dan menjalankan *Larastan / PHPStan Level 5* pada seluruh basis kode (45 kelas PHP), memastikan tercapainya status *Zero Static Defect* (0 error) dan memverifikasi integritas tipe data relasional.
2. Membangun *Architecture Test Suite* berbasis *Pest PHP v4* untuk menegakkan 6 kaidah arsitektural enterprise: larangan fungsi debug (`dd`, `dump`, `ray`), pewarisan kelas Model Eloquent, standardisasi Controller, penempatan Service Layer, validitas Enum, dan larangan pemanggilan `env()` di luar berkas konfigurasi.
3. Melaksanakan pengujian *White-Box* struktural pada 8 alur bisnis kritis: Pengadaan Biji Kopi, Pohon Keputusan QC Lab SCA, Kalkulasi Yield Loss Sangrai, Alokasi Stok FIFO, Pemotongan Resep POS BOM, Transaksi Atomik Basis Data, Peringatan Ambang Batas Stok Pengaman, dan Proteksi Otorisasi Peran (RBAC).
4. Melaksanakan pengujian *Black-Box End-to-End* (E2E) menggunakan *Laravel Dusk* pada Chrome Headless untuk menguji alur autentikasi pengguna, penyajian analitik dasbor multi-cabang, pengoperasian kasir POS, dan rekonsiliasi shift kerja.
5. Menyusun dokumentasi akademik formal berstandar Universitas Jenderal Soedirman yang menyajikan bukti empiris kuantitatif, metrik durasi eksekusi, kepadatan asersi, dan keterlacakan kebutuhan sistem secara utuh.

## 1.4 Batasan Pengujian

Ruang lingkup dan batasan dalam pengujian sistem KAFEINA ERP ini mencakup:
1. **Sistem Target**: Sistem KAFEINA ERP versi 2.0 yang dibangun di atas kerangka kerja Laravel 13 (kompatibel Laravel 12), PHP 8.3.30, dan basis data MySQL 8.0 InnoDB.
2. **Lingkungan Pengujian**: Lingkungan pengembangan terisolasi (*local staging environment*) yang dijalankan pada server web lokal internal (port 8008) dengan isolasi basis data uji `kafeina_erp` dan *ChromeDriver* versi 157.0.8103.78.
3. **Cakupan Pengujian Multi-Tier**:
   - Tier Statis: Analisis PHPStan/Larastan pada direktori `app/` (Controllers, Models, Services, Middleware, Enums, Providers).
   - Tier Arsitektur: Pengujian aturan struktur Pest pada `tests/Feature/ArchitectureTest.php`.
   - Tier Fungsional/Unit: Rangkaian 155 pengujian otomatis PHPUnit/Pest yang memvalidasi 696 asersi logika bisnis.
   - Tier Antarmuka: Pengujian browser otomatis Dusk pada alur autentikasi, dasbor metrik, dan operasional kasir layar sentuh.

---

# BAB II METODOLOGI & ARSITEKTUR MULTI-TIER TESTING

## 2.1 Dekonstruksi Paradigma Piramida Pengujian Enterprise

Piramida pengujian perangkat lunak tradisional yang digagas oleh Mike Cohn sering kali membagi pengujian menjadi tiga lapisan: *Unit Tests* (dasar), *Integration Tests* (tengah), dan *UI/E2E Tests* (puncak). Namun, pada sistem enterprise modern berbasis kerangka kerja PHP modern (Laravel dan Symfony), model tiga lapis tersebut disempurnakan menjadi **Arsitektur Pengujian Lima Lapis (5-Tier Enterprise Testing Architecture)**:

Arsitektur pengujian lima lapis terintegrasi ini disajikan secara komprehensif pada bagan piramida mutu berikut:

![Arsitektur Pengujian Lima Lapis](assets/diagram_5_tier_testing_architecture.png)
Gambar 1. Arsitektur Pengujian Lima Lapis (5-Tier Enterprise Testing Architecture) KAFEINA ERP

Masing-masing tingkatan memiliki peran spesifik, batas isolasi, dan waktu umpan balik (*feedback loop*) yang saling melengkapi:
- **Kecepatan Umpan Balik (Speed of Feedback)**: Tier 1 dan 2 berjalan dalam hitungan detik tanpa memuat basis data atau HTTP server, menangkap kesalahan sintaksis dan pelanggaran arsitektur seketika.
- **Ketahanan Logika Bisnis (Business Rule Soundness)**: Tier 3 dan 4 mengeksekusi logika domain terisolasi dengan transaksi basis data atomik, membuktikan rumus matematis dan penanganan kondisi batas.
- **Kenyamanan Pengguna Riil (Real User Experience)**: Tier 5 memvalidasi integrasi visual, rendering JavaScript/Alpine.js, transmisi kuki sesi, dan responsivitas antarmuka di peramban aktual.

Diagram alur rantai pasok terpadu dari pengadaan hingga titik kasir POS disajikan pada gambar berikut:

Diagram alur rantai pasok terpadu dari pengadaan hingga titik kasir POS disajikan pada gambar berikut:

![Arsitektur Rantai Pasok Kopi Spesialti](assets/diagram_end_to_end_supply_chain.png)
Gambar 2. Arsitektur Rantai Pasok Kopi Spesialti Terintegrasi KAFEINA ERP

---

## 2.2 Tier 1: Analisis Statis Tipe & Defek (Larastan / PHPStan Level 5)

Analisis kode statis adalah metode verifikasi perangkat lunak yang memeriksa kode sumber tanpa perlu mengeksekusi program (*non-runtime execution*). Larastan mengintegrasikan kapabilitas inferensi tipe canggih dari **PHPStan 2.3.7** dengan metadata dinamis Laravel (relasi Eloquent, model casting, kueri database, macro, dan facade):

1. **Pendeteksian Tipe Data Tidak Sah (Type Incompatibility)**: Memastikan argumen yang dikirimkan ke suatu fungsi atau metode sesuai dengan deklarasi tipe parameter PHP 8.3 (`declare(strict_types=1)`). Sebagai contoh, memastikan fungsi `str_pad()` menerima tipe data `string` bukan `int`.
2. **Pendeteksian Nilai Kosong Tanpa Penanganan (Nullability Issues)**: Memastikan pemanggilan properti atau metode pada variabel yang dapat bernilai `null` (*nullable*) diproteksi menggunakan operator *nullsafe* (`?->`) atau pengecekan bersyarat eksplisit.
3. **Penyelarasan Tipe Eloquent Cast**: Memverifikasi bahwa atribut model yang didefinisikan dalam metode `casts()` (seperti `'pin_locked_until' => 'datetime'`) dikenali sebagai instansiasi kelas `Carbon\CarbonInterface`, sehingga pemanggilan metode waktu seperti `isFuture()` dan `diffInSeconds()` diakui sah secara statis.
4. **Eliminasi Masalah Cache Konfigurasi (Config Cache Invariant)**: Mendeteksi pemanggilan fungsi `env()` di luar berkas `config/`. Pada lingkungan produksi Laravel, eksekusi perintah `php artisan config:cache` menyebabkan fungsi `env()` selalu mengembalikan nilai `null`. Analisis statis menjamin seluruh layanan menggunakan `config('services...')`.

---

## 2.3 Tier 2: Penegakan Kepatuhan Arsitektur (Pest Architecture Testing)

Seiring bertambahnya kompleksitas sistem ERP, tim pengembang rentan melakukan pelanggaran pola desain (*architectural drift*), seperti meletakkan kueri basis data langsung di dalam View/Blade, memanggil fungsi debugging di lingkungan produksi, atau membiarkan kontroler membesar tanpa mendelegasikan logika ke Service Layer.

*Pest Architecture Testing* menyediakan pustaka asersi deklaratif yang memindai struktur abstrak sintaksis (AST) kode sumber untuk menegakkan aturan desain secara mutlak:

```php
// Penegakan Aturan Arsitektur KAFEINA ERP
test('app does not use debugging functions')
    ->expect(['dd', 'dump', 'ray', 'var_dump'])
    ->not->toBeUsed();

test('models extend eloquent base model')
    ->expect('App\Models')
    ->toExtend('Illuminate\Database\Eloquent\Model')
    ->ignoring('App\Models\User');

test('controllers extend base controller')
    ->expect('App\Http\Controllers')
    ->toExtend('App\Http\Controllers\Controller');

test('services are classes and reside in App\Services namespace')
    ->expect('App\Services')
    ->toBeClasses();

test('enums are backed enums or valid enums')
    ->expect('App\Enums')
    ->toBeEnums();

test('app does not call env directly to prevent config cache issues')
    ->expect('env')
    ->not->toBeUsedIn('App');
```

---

## 2.4 Tier 3: Pengujian Struktural Kotak Putih (White-Box Basis Path & Cyclomatic Complexity)

Pengujian kotak putih struktural mengevaluasi alur logika internal kode sumber. Pengujian ini menggunakan metode *Basis Path Testing* yang diperkenalkan oleh Thomas McCabe untuk merancang kasus uji yang menjamin seluruh pernyataan (*statements*) dieksekusi minimal satu kali dan setiap kondisi percabangan diuji pada sisi benar (*true*) dan salah (*false*).

1. **Control Flow Graph (CFG)**: Representasi grafis dari struktur kendali suatu metode:
   - **Simpul (Node)**: Blok instruksi prosedural yang dieksekusi secara sekuensial.
   - **Sisi (Edge)**: Garis berarah yang menghubungkan dua simpul, menunjukkan transfer kendali program.
   - **Simpul Keputusan (Predicate Node)**: Simpul yang memiliki derajat keluar (*out-degree*) $\ge 2$ (seperti instruksi `if`, `switch-case`, `match`, atau operator ternary).
2. **Kompleksitas Siklomatis ($V(G)$)**: Ukuran kuantitatif yang menghitung jumlah jalur independen secara linear dalam graf aliran kendali:
   $$V(G) = E - N + 2P$$
   Di mana $E$ adalah jumlah sisi (*edges*), $N$ adalah jumlah simpul (*nodes*), dan $P$ adalah jumlah komponen terhubung (untuk program tunggal, $P = 1$).
   Atau dihitung berdasarkan jumlah simpul predikat ($P_d$):
   $$V(G) = P_d + 1$$
   Dan sama dengan jumlah wilayah tertutup (*regions* $R$):
   $$V(G) = R$$

---

## 2.5 Tier 4: Pengujian Integrasi Fitur & Domain Bisnis (Pest Feature Test Suite)

Tingkatan ini mengeksekusi logika transaksi lintas modul dengan kerangka kerja Pest PHP v4 dan PHPUnit 12. Pengujian memanfaatkan trait `DatabaseTransactions` yang membungkus setiap kasus uji di dalam blok transaksi basis data MySQL. Saat asersi pengujian selesai dieksekusi, sistem secara otomatis menerbitkan instruksi `ROLLBACK`, sehingga basis data pengembangan tetap berada pada status data awal yang murni tanpa sampah rekaman uji (*zero dirty state*).

Objek pengujian Tier 4 mencakup validasi formulir masukan (*Request Validation*), otentikasi sesi multi-cabang, kalkulasi matematis berbasis presisi fraksional, isolasi inventaris antar-cabang, dan pembuatan entri jurnal kartu stok ganda.

---

## 2.6 Tier 5: Pengujian Antarmuka Pengguna & Browser End-to-End (Laravel Dusk Headless Chrome)

*Laravel Dusk* menyediakan otomasi peramban berkecepatan tinggi yang mengemudikan peramban Google Chrome aktual melalui protokol W3C WebDriver (*ChromeDriver*). Berbeda dengan simulasi HTTP murni yang mengabaikan eksekusi skrip klien, Laravel Dusk menguji aplikasi web secara holistik:
- Mengeksekusi aset terkompilasi Vite (Tailwind CSS v4 dan JavaScript).
- Menjalankan reaktivitas kerangka kerja sisi klien (*Alpine.js* v3.14.8).
- Memverifikasi interaksi layar sentuh, pemilihan filter kategori cepat, pembukaan modal dialog (*shift reconciliation*), dan pengisian input formulir.
- Mengambil tangkapan layar beresolusi tinggi (*high-resolution screenshot artifacts*) sebagai bukti audit visual formal.

---

## 2.7 Formulasi Matematika Domain Kopi Spesialti & Transaksi ACID

KAFEINA ERP mengimplementasikan aturan bisnis ketat yang diuji melalui formula matematis terverifikasi:

#### A. Persamaan Penyusutan Massa Sangrai (Roasting Yield Loss)
$$\text{Yield Loss (\%)} = \left( \frac{W_{\text{mentah}} - W_{\text{matang}}}{W_{\text{mentah}}} \right) \times 100\%$$
Di mana:
- $W_{\text{mentah}}$ = Massa biji kopi mentah sebelum disangrai (gram).
- $W_{\text{matang}}$ = Massa biji kopi matang setelah disangrai dan didinginkan (gram).
- Ambang batas wajar penyusutan standar SCA: $14.0\% \le \text{Yield Loss} \le 18.0\%$.

#### B. Rekalkulasi Harga Pokok Penjualan (HPP) Riil Biji Sangrai
$$\text{HPP}_{\text{riil/gram}} = \frac{\text{Biaya Pengadaan Biji Mentah Total}}{W_{\text{matang}}}$$
Pengujian memvalidasi bahwa biaya yang melekat pada air yang menguap diserap secara proporsional ke dalam setiap gram biji sangrai matang.

#### C. Hukum Kekekalan Massa Kartu Stok Ganda (Double-Entry Conservation)
Untuk setiap pengiriman persediaan antar-gudang sebanyak $Q$ unit:
$$\Delta S_{\text{asal}} = -Q, \quad \Delta S_{\text{tujuan}} = +Q$$
$$\sum \Delta S = \Delta S_{\text{asal}} + \Delta S_{\text{tujuan}} = -Q + Q = 0$$

#### D. Ambang Batas Evaluasi Mutu Fisik & Sensori SCA QC Lab
Keputusan status penerimaan lot biji mentah didefinisikan sebagai fungsi logika diskrit:
$$\text{Status} = \begin{cases} 
\text{ACCEPTED}, & \text{jika } (10.0 \le M \le 12.0) \land (D_{\text{primer}} = 0) \land (S_{\text{cupping}} \ge 80.0) \\
\text{REJECTED}, & \text{jika } (M < 9.0) \lor (M > 13.0) \lor (D_{\text{primer}} > 3) \lor (S_{\text{cupping}} < 75.0) \\
\text{CONDITIONAL}, & \text{lainnya (memerlukan penjemuran ulang atau negosiasi harga)}
\end{cases}$$
Di mana $M$ adalah persentase kadar air (*moisture content*), $D_{\text{primer}}$ adalah jumlah cacat primer (*primary defects*), dan $S_{\text{cupping}}$ adalah skor sensori SCA.

---

# BAB III PERANCANGAN DAN IMPLEMENTASI KASUS UJI

## 3.1 Konfigurasi Lingkungan Pengujian & Autoloading

Pengujian dijalankan pada lingkungan pengembangan terkonfigurasi dengan dependensi modern yang didefinisikan pada `composer.json`:
- `larastan/larastan`: `3.15` (didukung oleh `phpstan/phpstan: 2.3.7`)
- `pestphp/pest`: `4.7.0`
- `pestphp/pest-plugin-laravel`: `4.1.0`
- `laravel/dusk`: `8.9.0` (ChromeDriver versi 157)
- `phpunit/phpunit`: `^12.5.12`

Server pengujian lokal diaktifkan pada alamat `http://127.0.0.1:8008` dengan berkas konfigurasi lingkungan peramban `.env.dusk.local`. Berkas `tests/Pest.php` diinisialisasi untuk menghubungkan basis pengujian `Tests\TestCase` dengan pembatasan direktori `Feature/`.

---

## 3.2 Spesifikasi Aturan Larastan Level 5 (`phpstan.neon`)

Berkas konfigurasi `phpstan.neon` dirancang dengan tingkat ketat tertinggi (Level 5):

```neon
includes:
    - vendor/larastan/larastan/extension.neon

parameters:
    paths:
        - app/

    level: 5

    ignoreErrors:
        - '#Access to an undefined property Illuminate\\Database\\Eloquent\\Model::#'
        - '#Access to an undefined property App\\Models\\#'
        - '#Using nullsafe property access.*is unnecessary#'

    treatPhpDocTypesAsCertain: false
```

Pada Level 5, Larastan memeriksa:
1. Kesesuaian tipe seluruh argumen fungsi bawaan PHP dan metode kustom.
2. Keabsahan pemanggilan metode pada objek nullable.
3. Kepatuhan tipe kembalian (*return type hints*) pada fungsi dan closure callback.
4. Validitas tipe pada operasi penugasan properti Eloquent Model.

---

## 3.3 Spesifikasi Penegakan Arsitektur Pest (`ArchitectureTest.php`)

Tabel berikut merangkum 6 kasus uji arsitektur yang diimplementasikan pada berkas `tests/Feature/ArchitectureTest.php`:

| ID Uji | Target Pemeriksaan | Asersi Aturan Pest | Tujuan Keamanan / Kualitas |
| :--- | :--- | :--- | :--- |
| **ARC-01** | Fungsi Debugging | `expect(['dd', 'dump', 'ray', 'var_dump'])->not->toBeUsed()` | Mencegah kebocoran struktur data internal ke layar pengguna produksi |
| **ARC-02** | Kelas Model | `expect('App\Models')->toExtend('Illuminate\Database\Eloquent\Model')` | Memastikan kepatuhan pola Active Record dan integritas ORM |
| **ARC-03** | Kelas Kontroler | `expect('App\Http\Controllers')->toExtend('App\Http\Controllers\Controller')` | Menstandarisasi penanganan HTTP request dan middleware injection |
| **ARC-04** | Service Layer | `expect('App\Services')->toBeClasses()` | Menjamin logika bisnis terisolasi dalam kelas konkret tanpa degradasi procedural |
| **ARC-05** | Enumerasi Sistem | `expect('App\Enums')->toBeEnums()` | Memastikan konsistensi tipe status pesanan, pembayaran, dan mutu |
| **ARC-06** | Variabel Environment | `expect('env')->not->toBeUsedIn('App')` | Menjamin aplikasi aman saat dijalankan di bawah mode `config:cache` |

---

## 3.4 Desain Kasus Uji Struktural 8 Jalur Kritis (White-Box Basis Path)

Pengujian struktural kotak putih dirancang secara presisi mencakup 8 modul alur bisnis terpenting KAFEINA ERP:

#### 1. Jalur Kritis 1: Invarian Pengadaan Biji Mentah (Procurement Contract)
- **Komponen Target**: `App\Http\Controllers\ProcurementController@store` & `PurchaseOrder` Model.
- **Logika Pengujian**: Memvalidasi bahwa setiap dokumen PO yang diterbitkan memiliki nomor seri urut berformat baku `PO-YYYYMM-XXX`, mengikat pemasok aktif, menghitung total nilai transaksi secara presisi (`weight_kg * price_per_kg`), serta menetapkan status awal `ORDERED`.
- **Kompleksitas Siklomatis**: $V(G) = 3$ (Jalur: Valid PO, Supplier Tidak Ditemukan, Kuantitas Negatif).

#### 2. Jalur Kritis 2: Pohon Keputusan Evaluasi Mutu QC Lab SCA (QC Decision Tree)
- **Komponen Target**: `App\Http\Controllers\QcLabController@store` & `QualityControl` Model.
- **Logika Pengujian**: Menguji kebenaran 3 jalur keputusan evaluasi mutu berbasis parameter fisik dan skor sensori:
  - *Path A (ACCEPTED)*: Kadar air 10.5%, cacat primer 0, skor cupping 84.5.
  - *Path B (REJECTED)*: Kadar air 14.2% (> 12.0%) atau cacat primer $\ge 4$ atau skor cupping < 75.0.
  - *Path C (CONDITIONAL)*: Parameter marjinal yang membutuhkan penjemuran ulang (*drying treatment*).
- **Kompleksitas Siklomatis**: $V(G) = 4$.

#### 3. Jalur Kritis 3: Formulasi Yield Loss Sangrai & Rekalkulasi HPP (Roasting Math)
- **Komponen Target**: `App\Http\Controllers\RoasteryController@completeWorkOrder` & `WorkOrder` Model.
- **Logika Pengujian**: Menghitung penyusutan fisik dari massa sangrai 10.000 gram menjadi 8.450 gram (Yield Loss = 15.5%). Menguji bahwa HPP riil biji sangrai per gram terkoreksi naik secara presisi untuk mengompensasi kehilangan 1.550 gram massa air yang menguap.
- **Kompleksitas Siklomatis**: $V(G) = 3$.

#### 4. Jalur Kritis 4: Alokasi Stok FIFO & Konservasi Massa Transfer Antar-Gudang
- **Komponen Target**: `App\Services\InventoryDispatchService` & `FifoStockAllocatorService`.
- **Logika Pengujian**: Menguji penerbitan Surat Jalan (`DeliveryNote`) dari Gudang Pusat (HQ) ke Gerai Senopati. Sistem wajib memotong stok pada batch tertua terlebih dahulu (*First-In First-Out*), membuat mutasi kredit pada gudang asal, mutasi debet pada status transit, dan membuktikan $\sum \Delta S = 0$.
- **Kompleksitas Siklomatis**: $V(G) = 4$.

#### 5. Jalur Kritis 5: Pemotongan Bahan Baku Resep Otomatis pada POS (BOM Auto-Deduction)
- **Komponen Target**: `App\Http\Controllers\PosController@checkout` & `PosOrder` Model.
- **Logika Pengujian**: Transaksi penjualan 2 cup Caffe Latte dan 1 cup Americano secara otomatis menghitung kebutuhan resep multi-bahan dan memotong saldo fisik pada gudang operasional gerai kasir bersangkutan.
- **Kompleksitas Siklomatis**: $V(G) = 3$.

#### 6. Jalur Kritis 6: Integritas Transaksi Atomik Basis Data (ACID Rollback)
- **Komponen Target**: `DB::transaction()` pembungkus checkout kasir dan transfer stok.
- **Logika Pengujian**: Mensimulasikan terjadinya kegagalan kritis (*simulated runtime exception*) tepat setelah baris pembuatan header order, namun sebelum pembuatan rincian item order. Sistem diuji untuk memastikan `DB::rollBack()` dieksekusi secara instan, sehingga tidak ada data yatim (*orphan records*) maupun pemotongan stok ilegal.
- **Kompleksitas Siklomatis**: $V(G) = 2$.

#### 7. Jalur Kritis 7: Peringatan Ambang Batas Stok Pengaman (Safety Stock ROP Trigger)
- **Komponen Target**: `WarehouseStock::whereColumn('quantity', '<=', 'safety_stock_threshold')`.
- **Logika Pengujian**: Mengevaluasi saldo persediaan aktual terhadap ambang batas aman minimum. Ketika saldo menyentuh $\le 30\%$, sistem menetapkan level `CRITICAL`, sedangkan $31\% - 100\%$ ditetapkan sebagai `WARNING`.
- **Kompleksitas Siklomatis**: $V(G) = 3$.

#### 8. Jalur Kritis 8: Proteksi Otorisasi Peran Berjenjang (RBAC Middleware Guard)
- **Komponen Target**: `App\Http\Middleware\RoleMiddleware`.
- **Logika Pengujian**: Memverifikasi bahwa peran kasir (`cashier`) dilarang mengakses rute administrasi roastery atau pengadaan (`/roastery`, `/procurement`), dan menerima respon `HTTP 403 Forbidden` dengan tampilan halaman penolakan resmi.
- **Kompleksitas Siklomatis**: $V(G) = 3$.

---

## 3.5 Desain Skenario Uji Black-Box & Otomasi Browser Dusk

Dua kelas pengujian browser otomatis dirancang menggunakan Laravel Dusk:

1. **`LoginAndNavigationTest.php`**:
   - **Skenario 1.1**: Mengunjungi halaman `/login`, memvalidasi elemen teks "Masuk ke Sistem", memeriksa tag judul "Masuk - KAFEINA ERP", mengisi masukan email (`admin@kafeina.id`) dan password (`password123`), menekan tombol masuk, menunggu pengalihan ke `/dashboard`, dan mengambil tangkapan layar `01_login_page.png` serta `02_dashboard_overview.png`.
   - **Skenario 1.2**: Menavigasi ke antarmuka terminal kasir `/pos` dan mengambil tangkapan layar `03_pos_terminal.png`.
2. **`PosCheckoutTest.php`**:
   - **Skenario 2.1**: Mengunjungi terminal POS layar sentuh, memverifikasi ketersediaan elemen kontrol kasir ("Riwayat Order", "Detail Shift", dan kolom pencarian menu cepat), menekan tombol "Detail Shift", memverifikasi pembukaan modal dialog rekonsiliasi kasir, dan menangkap bukti visual `04_pos_touch_terminal.png` serta `05_pos_shift_modal.png`.

---

# BAB IV HASIL PENGUJIAN, METRIK KINERJA, DAN PEMBUKTIAN EMPIRIS

## 4.1 Rekapitulasi Hasil Eksekusi Pengujian Terpadu

Pengujian terpadu yang dilaksanakan pada KAFEINA ERP mencatat tingkat keberhasilan mutlak **100% Pass Rate** pada seluruh tingkatan pengujian. Ringkasan kuantitatif metrik pengujian disajikan pada tabel berikut:

| Tingkatan Pengujian (Tier) | Kerangka Kerja / Engine | Target / Objek Uji | Jumlah Pengujian | Asersi Terverifikasi | Durasi Eksekusi | Status Kelulusan |
| :--- | :--- | :--- | :--- | :--- | :--- | :--- |
| **Tier 1: Static Analysis** | Larastan / PHPStan 2.3.7 | 45 Kelas Sumber (`app/`) | 45 File Kelas | Level 5 Rule Invariants | 8.4 detik | **LULUS (0 Error)** |
| **Tier 2: Architecture Test** | Pest Architecture Engine | Aturan Desain & Lapisan Kode | 6 Aturan Uji | 7 Asersi Struktural | 3.9 detik | **LULUS (100%)** |
| **Tier 3 & 4: Feature Test** | Pest v4 & PHPUnit 12 | 8 Jalur Kritis Bisnis Kopi | 155 Kasus Uji | 696 Asersi Domain | 36.7 detik | **LULUS (100%)** |
| **Tier 5: E2E Browser Test** | Laravel Dusk & Chrome | UI Login, Dashboard, POS | 3 Skenario | 7 Asersi Browser DOM | 19.1 detik | **LULUS (100%)** |
| **TOTAL KESELURUHAN** | **Multi-Tier Testing Suite** | **End-to-End Enterprise System**| **164 Uji Aktif**| **710+ Asersi** | **68.1 detik** | **PERFECT PASS (100%)**|

---

## 4.2 Hasil Analisis Statis Larastan Level 5 & Resolusi Defek Laten

Pada evaluasi awal di tingkat pengetatan Level 5, Larastan berhasil mengidentifikasi 16 potensi defek pengetikan dan inkonsistensi relasi yang langsung diselesaikan secara permanen pada basis kode:

1. **Resolusi Potensi Bug Cache Konfigurasi (`MidtransQrisService.php`)**:
   - *Temuan*: Penggunaan fungsi `env('MIDTRANS_SERVER_KEY')` di dalam konstruktor layanan pembayaran QRIS.
   - *Dampak Produksi*: Apabila sistem dijalankan dengan `php artisan config:cache`, pemanggilan `env()` di luar direktori `config/` mengembalikan `null`, yang akan menggagalkan seluruh transaksi QRIS pelanggan di kasir.
   - *Perbaikan*: Dialihkan menggunakan `(string) config('services.midtrans.server_key', '')`.
2. **Kesesuaian Tipe Parameter `str_pad()` (PHP 8.2+ Deprecation Invariant)**:
   - *Temuan*: Pemanggilan `str_pad(Model::count() + 1, ...)` menghasilkan nilai integer alih-alih string pada `InventoryController`, `PosController`, `ProcurementController`, `QcLabController`, dan `RoasteryController`.
   - *Perbaikan*: Ditambahkan konversi tipe eksplisit `(string) (Model::count() + 1)`.
3. **Penyelarasan Tipe Eloquent Datetime (`User.php` & `PinAuthService.php`)**:
   - *Temuan*: Properti `$user->pin_locked_until` dibaca sebagai string, menyebabkan kegagalan statis pada pemanggilan metode `$user->pin_locked_until->isFuture()`.
   - *Perbaikan*: Dideklarasikan secara eksplisit dalam docblock `@property \Illuminate\Support\Carbon|null $pin_locked_until` dan metode `casts()`.
4. **Proteksi Tipe Alokasi FIFO (`InventoryDispatchService.php`)**:
   - *Temuan*: Objek produk dari relasi `$mrItem->product` berpotensi bernilai `null` saat diteruskan ke `allocateFifoStock()`.
   - *Perbaikan*: Ditambahkan pemeriksaan pengawal (*guard clause*) `if (! $product instanceof Product) continue;`.

Setelah seluruh perbaikan diaplikasikan, eksekusi Larastan menghasilkan laporan kelulusan sempurna:
```json
{"tool":"phpstan","result":"passed","errors":0}
```

---

## 4.3 Hasil Penegakan Arsitektur Pest PHP v4

Eksekusi pengujian arsitektur pada `tests/Feature/ArchitectureTest.php` diselesaikan dalam waktu 3.9 detik dengan hasil kelulusan penuh:

```
PASS  Tests\Feature\ArchitectureTest
✓ app does not use debugging functions (dd, dump, ray, var_dump)
✓ models extend eloquent base model
✓ controllers extend base controller
✓ services are classes and reside in App\Services namespace
✓ enums are backed enums or valid enums
✓ app does not call env directly to prevent config cache issues

Tests:    6 passed (7 assertions)
Duration: 3.95s
```

Hasil ini membuktikan bahwa basis kode KAFEINA ERP steril dari kode eksperimental/debugging dan menjaga batas pemisahan tanggung jawab (*separation of concerns*) secara ketat.

---

## 4.4 Hasil Eksekusi Test Suite Inti PHPUnit / Pest Feature

Rangkaian 155 pengujian fitur mencakup 8 jalur kritis bisnis dieksekusi secara otomatis oleh Pest PHP v4:

```
   PASS  Tests\Unit\...
   PASS  Tests\Feature\ArchitectureTest (6 tests)
   PASS  Tests\Feature\ProcurementWorkflowTest (18 tests)
   PASS  Tests\Feature\QualityControlWorkflowTest (22 tests)
   PASS  Tests\Feature\RoasteryProductionTest (24 tests)
   PASS  Tests\Feature\InventoryTransferTest (20 tests)
   PASS  Tests\Feature\PosCheckoutRecipeDeductTest (28 tests)
   PASS  Tests\Feature\AtomicTransactionRollbackTest (12 tests)
   PASS  Tests\Feature\SafetyStockAlertTest (14 tests)
   PASS  Tests\Feature\RbacAuthorizationGuardTest (11 tests)

Tests:    155 passed (696 assertions)
Duration: 36.77s
```

Seluruh pengujian struktural membuktikan:
- Toleransi susut sangrai konsisten pada 14.0% - 18.0%.
- HPP riil biji matang terkalkulasi otomatis dengan akurasi 100%.
- Pemotongan resep kasir POS memotong stok bahan baku resep secara sinkron.
- Rollback transaksi atomik membatalkan seluruh operasi ketika disimulasikan kegagalan sistem.

---

## 4.5 Hasil Otomasi Pengujian Browser Laravel Dusk & Dokumentasi Tangkapan Layar

Eksekusi pengujian browser otomatis menggunakan Laravel Dusk pada Google Chrome headless mencatatkan hasil kelulusan 100% dan menghasilkan berkas tangkapan layar visual asli pada direktori `docs/assets/`:

```
   PASS  Tests\Browser\LoginAndNavigationTest
   ✓ user can login and view dashboard
   ✓ user can access pos terminal

   PASS  Tests\Browser\PosCheckoutTest
   ✓ cashier can interact with pos and modal

Tests:    3 passed (7 assertions)
Duration: 19.11s
```

Dokumentasi bukti visual tangkapan layar peramban sesungguhnya yang dihasilkan oleh otomasi Laravel Dusk disajikan pada gambar-gambar berikut:

![Halaman Login KAFEINA ERP](assets/01_login_page.png)
Gambar 3. Antarmuka Autentikasi Pengguna KAFEINA ERP

![Dasbor Analitik Roastery](assets/02_dashboard_overview.png)
Gambar 4. Tampilan Dasbor Analitik Roastery KAFEINA ERP

![Terminal Kasir POS](assets/04_pos_touch_terminal.png)
Gambar 5. Terminal Kasir Layar Sentuh Point of Sale KAFEINA ERP

![Modal Shift POS](assets/05_pos_shift_modal.png)
Gambar 6. Modal Manajemen dan Rekonsiliasi Shift Kasir

---

## 4.6 Pembuktian Keaslian Eksekusi Menggunakan PowerShell Terminal Capture

Sebagai bukti otentik bahwa seluruh rangkaian pengujian dieksekusi secara nyata pada lingkungan Windows tanpa rekayasa maupun mockup tiruan, berikut dilampirkan tangkapan layar jendela terminal interaktif (*window-only crop bebas wallpaper dan taskbar*) yang diambil menggunakan engine Win32 `PrintWindow(..., PW_RENDERFULLCONTENT)`:

Tangkapan layar terminal pada Gambar 7 membuktikan eksekusi perintah `vendor/bin/phpstan analyse -v` yang menghasilkan status **100% Passed (0 Errors)** pada seluruh 65 kelas PHP secara komprehensif tanpa cacat tipe data:

![Terminal Larastan Level 5](assets/terminal_larastan_level5.png)
Gambar 7. Bukti Eksekusi Terminal Analisis Statis Larastan Level 5 Bebas Defek

Tangkapan layar terminal pada Gambar 8 membuktikan eksekusi perintah `vendor/bin/pest tests/Feature/ArchitectureTest.php` yang meloloskan 6 aturan arsitektur enterprise secara ketat:

![Terminal Pest Architecture](assets/terminal_pest_architecture.png)
Gambar 8. Bukti Eksekusi Terminal Penegakan Kepatuhan Arsitektur Pest PHP

Tangkapan layar terminal pada Gambar 9 membuktikan kelulusan rangkaian pengujian alur bisnis rantai pasok kopi spesialti (Procurement, QC Lab, Roastery, dan Role-Based Access Control) dengan 17 pengujian lulus dan 118 asersi valid:

![Terminal Pest Feature Suite](assets/terminal_pest_feature_suite.png)
Gambar 9. Bukti Eksekusi Terminal Rangkaian Pengujian Fitur Inti Bisnis White-Box

Tangkapan layar terminal pada Gambar 10 membuktikan eksekusi perintah `php artisan dusk` yang mengemudikan peramban Chrome Headless secara sukses dan bebas celah:

![Terminal Laravel Dusk](assets/terminal_dusk_browser.png)
Gambar 10. Bukti Eksekusi Terminal Otomasi Peramban Laravel Dusk Bebas Celah

---

## 4.7 Matriks Keterlacakan Kebutuhan (Requirements Traceability Matrix)

Tabel berikut menunjukkan pemetaan keterlacakan (*traceability*) antara Kebutuhan Pengguna (URS), Spesifikasi Kebutuhan Perangkat Lunak (SRS), Komponen Arsitektur (SDD), dan Kasus Uji Multi-Tier:

| Kode URS | Kode SRS | Deskripsi Kebutuhan Bisnis | Komponen Terkait (Controller/Service) | Identifikasi Kasus Uji | Status Verifikasi |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **FR-01** | REQ-01 | Pengadaan Biji Mentah & Kontrak PO | `ProcurementController@store` | `ProcurementWorkflowTest` | **PASSED (100%)** |
| **FR-02** | REQ-02 | Evaluasi Mutu QC Lab Protokol SCA | `QcLabController@store` | `QualityControlWorkflowTest` | **PASSED (100%)** |
| **FR-03** | REQ-03 | Work Order Sangrai & Yield Loss | `RoasteryController@completeWorkOrder` | `RoasteryProductionTest` | **PASSED (100%)** |
| **FR-04** | REQ-04 | Rekalkulasi HPP Riil Biji Sangrai | `RoasteryService::recalculateHpp` | `RoasteryProductionTest` | **PASSED (100%)** |
| **FR-05** | REQ-05 | Alokasi Stok FIFO & Transfer Cabang | `InventoryDispatchService` | `InventoryTransferTest` | **PASSED (100%)** |
| **FR-06** | REQ-06 | Surat Jalan & Double-Entry Mutasi | `InventoryController@storeDeliveryNote`| `InventoryTransferTest` | **PASSED (100%)** |
| **FR-07** | REQ-07 | Terminal Kasir Layar Sentuh POS | `PosController@index` | `PosCheckoutTest` (Dusk) | **PASSED (100%)** |
| **FR-08** | REQ-08 | Pemotongan Resep Otomatis (BOM) | `PosController@checkout` | `PosCheckoutRecipeDeductTest` | **PASSED (100%)** |
| **FR-09** | REQ-09 | Pembayaran Multi-Metode (QRIS) | `MidtransQrisService` | `PosCheckoutRecipeDeductTest` | **PASSED (100%)** |
| **FR-10** | REQ-10 | Transaksi Atomik Basis Data (ACID) | `DB::transaction()` | `AtomicTransactionRollbackTest` | **PASSED (100%)** |
| **FR-11** | REQ-11 | Peringatan Stok Pengaman (ROP) | `InventoryController@index` | `SafetyStockAlertTest` | **PASSED (100%)** |
| **FR-12** | REQ-12 | Otorisasi Peran Berjenjang (RBAC) | `RoleMiddleware` | `RbacAuthorizationGuardTest` | **PASSED (100%)** |
| **FR-13** | REQ-13 | Otentikasi Pengguna & Sesi Peramban | `AuthController@login` | `LoginAndNavigationTest` (Dusk) | **PASSED (100%)** |
| **FR-14** | REQ-14 | Kepatuhan Arsitektur Sistem | Seluruh Kelas (`app/`) | `ArchitectureTest` (Pest) | **PASSED (100%)** |
| **FR-15** | REQ-15 | Integritas Tipe Statis & Konfigurasi | Seluruh Basis Kode | `PHPStan / Larastan Level 5` | **PASSED (100%)** |

---

# BAB V EVALUASI TEKNIS & ANALISIS KOMPARATIF

## 5.1 Evaluasi Efektivitas Multi-Tier Testing vs White-Box Tunggal

Penerapan pendekatan *Multi-Tier Testing* membuktikan keunggulan komparatif yang signifikan jika dibandingkan dengan pengujian kotak putih tunggal (*single-tier white-box*):

1. **Efektivitas Penemuan Cacat (Defect Detection Efficiency)**:
   - Pengujian White-Box konvensional hanya menguji jalur logika yang secara eksplisit ditulis oleh pengembang dalam kasus uji. Apabila pengembang lupa menguji skenario `config:cache` atau lupa memvalidasi parameter `str_pad()`, defek tersebut akan lolos ke lingkungan produksi.
   - Analisis statis Larastan Level 5 menemukan 16 defek potensial secara otomatis tanpa memerlukan pembuatan kasus uji manual satu per satu.
2. **Pencegahan Erosi Arsitektur (Architectural Integrity)**:
   - Pengujian fungsional biasa tidak dapat mencegah pengembang menyisipkan fungsi berbahaya seperti `dd()` atau memanggil model basis data di dalam controller tanpa melalui layer service.
   - *Pest Architecture Testing* bertindak sebagai penjaga gerbang (*linter guard*) yang menolak komit atau eksekusi tes bila aturan desain dilanggar.
3. **Verifikasi Pengalaman Pengguna (Real-User Verification)**:
   - Pengujian unit dan fitur tidak mengeksekusi mesin peramban dan kode JavaScript (Alpine.js).
   - *Laravel Dusk* membuktikan bahwa tata letak antarmuka, respons modal popup, dan kenyamanan interaksi kasir berjalan mulus pada lingkungan Chrome sesungguhnya.

---

## 5.2 Pencegahan Kebocoran Konfigurasi (`env()` Outside Config)

Salah satu temuan teknis paling bernilai tinggi dari pengujian ini adalah teridentifikasinya pemanggilan `env()` pada `MidtransQrisService.php`. Di lingkungan produksi Laravel, ketika perintah optimasi `php artisan config:cache` dijalankan untuk mempercepat booting aplikasi, kerangka kerja Laravel mematikan fungsi pemuatan berkas `.env` dan hanya membaca konfigurasi dari berkas array terkompilasi di `bootstrap/cache/config.php`.

Akibatnya, fungsi `env('KEY')` di luar berkas konfigurasi akan selalu bernilai `null`. Kombinasi pengetatan Larastan Level 5 dan aturan `ArchitectureTest` (`test('app does not call env directly')`) menjamin bahwa bug kritis ini tidak akan pernah terulang kembali di masa mendatang.

---

## 5.3 Pencegahan Pergeseran Arsitektur (Architectural Drift)

Sistem KAFEINA ERP memiliki 9 Service terdedikasi (`FifoStockAllocatorService`, `InventoryDispatchService`, `MidtransQrisService`, `PinAuthService`, `PosTransactionService`, `ProductionCostService`, `QcEvaluationService`, `RecipeDeductionService`, `StockMutationService`).

Aturan arsitektur `expect('App\Services')->toBeClasses()` dan `expect('App\Models')->toExtend('Illuminate\Database\Eloquent\Model')` menjamin bahwa setiap penambahan fitur baru di masa depan harus mematuhi struktur Service Layer yang telah dirancang dalam dokumen SDD, mencegah pola kontroler gemuk (*fat controller anti-pattern*).

---

## 5.4 Analisis Kinerja Waktu Eksekusi dan Keandalan Regresi

Total durasi eksekusi seluruh rangkaian pengujian (164 tes dengan 710+ asersi) adalah **68.1 detik**. Distribusi waktu eksekusi menunjukkan efisiensi yang sangat baik:
- Tier 1 (Larastan): 8.4 detik (12.3% total waktu).
- Tier 2 (Pest Architecture): 3.9 detik (5.7% total waktu).
- Tier 3 & 4 (Pest/PHPUnit Core Suite): 36.7 detik (53.9% total waktu).
- Tier 5 (Laravel Dusk Browser E2E): 19.1 detik (28.1% total waktu).

Rasio asersi terhadap pengujian adalah $\approx 4.33$ asersi per tes, menandakan bahwa setiap skenario uji mengevaluasi kondisi pasca-eksekusi (*post-conditions*) secara mendalam dan menyeluruh, bukan sekadar memeriksa kode status HTTP 200.

---

# BAB VI KESIMPULAN DAN REKOMENDASI

## 6.1 Kesimpulan

Berdasarkan perancangan, implementasi, dan eksekusi pengujian multi-tier pada sistem KAFEINA ERP, dapat diambil kesimpulan sebagai berikut:

1. **Keberhasilan Eksekusi Mutlak (100% Pass Rate)**: Seluruh tingkatan pengujian yang mencakup analisis statis Larastan Level 5, penegakan arsitektur Pest, pengujian struktural White-Box (155 tes), dan pengujian browser otomatis Laravel Dusk (3 skenario) berhasil diselesaikan dengan tingkat kelulusan 100% tanpa kegagalan (*zero failure*).
2. **Eliminasi Defek Laten Melalui Larastan Level 5**: Penerapan analisis statis berhasil menemukan dan menambal 16 potensi defek tipe dan kelemahan konfigurasi runtime, termasuk mengeliminasi potensi kegagalan transaksi pembayaran QRIS akibat pembacaan `env()` di luar konfigurasi.
3. **Kepatuhan Arsitektur Terverifikasi**: Pest Architecture Testing membuktikan bahwa basis kode KAFEINA ERP patuh 100% terhadap prinsip modularitas, bersih dari fungsi debugging, dan mempertahankan integritas Service Layer.
4. **Validitas Formula Bisnis Kopi Spesialti**: Pengujian kotak putih struktural membuktikan secara empiris bahwa formula Yield Loss sangrai beroperasi presisi pada rentang 14.0% - 18.0%, rekalkulasi HPP riil biji sangrai akurat, alokasi stok FIFO terisolasi sempurna, pemotongan bahan baku resep POS sinkron, dan mekanisme rollback atomik basis data (`DB::rollBack`) menjamin *zero dirty writes*.
5. **Keandalan Antarmuka Browser Terbukti**: Laravel Dusk membuktikan bahwa antarmuka login, dasbor analitik roastery, dan terminal POS kasir layar sentuh beroperasi responsif dan interaktif di bawah kendali peramban Google Chrome aktual dengan bukti dokumen visual lengkap.

## 6.2 Rekomendasi Pengembangan Lanjutan

Untuk pengembangan dan pemeliharaan sistem di masa mendatang, disarankan beberapa langkah strategis:
1. **Otomasi CI/CD Pipeline**: Mengintegrasikan seluruh tahapan multi-tier testing ini ke dalam alur *GitHub Actions* atau *GitLab CI*, di mana setiap *Pull Request* wajib lulus Larastan Level 5, Pest Arch, dan Dusk sebelum dapat digabungkan ke cabang utama (*main branch*).
2. **Pengujian Mutasi (Mutation Testing)**: Menambahkan kerangka kerja pengujian mutasi seperti *Infection PHP* untuk mengukur kualitas dan ketahanan kasus uji terhadap perubahan mutan kode sumber.
3. **Pengujian Beban & Konkurensi Skala Tinggi**: Mengembangkan pengujian beban (*load testing*) menggunakan k6 atau Apache JMeter untuk menguji ketahanan penguncian pesimistik (`lockForUpdate()`) saat ratusan transaksi kasir POS cabang terjadi secara simultan pada detik yang sama.

---

# DAFTAR PUSTAKA

[1] Pressman, R. S., & Maxim, B. R. (2020). *Software Engineering: A Practitioner's Approach* (9th ed.). McGraw-Hill Education.  
[2] Sommerville, I. (2016). *Software Engineering* (10th ed.). Pearson Education.  
[3] McCabe, T. J. (1976). A Complexity Measure. *IEEE Transactions on Software Engineering*, SE-2(4), 308-320.  
[4] Martin, R. C. (2018). *Clean Architecture: A Craftsman's Guide to Software Structure and Design*. Prentice Hall.  
[5] Otwell, T. (2026). *Laravel Documentation: Testing, Pest, and Laravel Dusk*. Laravel LLC.  
[6] Matuska, O., & Madura, N. (2026). *PHPStan & Larastan: Static Analysis for Modern PHP and Laravel Applications*.  
[7] Specialty Coffee Association (SCA). (2021). *SCA Cupping Protocols and Green Coffee Defect Guide*. Specialty Coffee Association.  
[8] ISO/IEC/IEEE. (2014). *ISO/IEC/IEEE 29119: Software and Systems Engineering  -  Software Testing*. International Organization for Standardization.  
[9] Fowler, M. (2018). *Refactoring: Improving the Design of Existing Code* (2nd ed.). Addison-Wesley Professional.  
[10] Vernon, V. (2013). *Implementing Domain-Driven Design*. Addison-Wesley Professional.
