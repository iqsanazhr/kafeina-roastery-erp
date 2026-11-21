# -*- coding: utf-8 -*-
"""
Script untuk memformat ulang Laporan Pengujian White-Box dan Laporan Pengujian Multi-Tier
sesuai kaidah baku academic-doc-generator (Unsoed Standard)
dengan menyertakan diagram grafis Arsitektur Pengujian Lima Lapis (5-Tier Framework).
"""
import re
import os
import subprocess

def clean_dashes(text):
    text = text.replace('\u2014', ' - ')
    text = text.replace('\u2013', '-')
    return text

def perfect_whitebox_report():
    input_path = r"d:\Kafeina\docs\Laporan_Pengujian_WhiteBox_Kafeina_ERP_v2_Disempurnakan.md"
    with open(input_path, "r", encoding="utf-8") as f:
        content = f.read()

    content = clean_dashes(content)

    frontmatter = """---
title: "LAPORAN PENGUJIAN KOTAK PUTIH (WHITE-BOX TESTING) PADA SISTEM ENTERPRISE RESOURCE PLANNING (ERP) RANTAI PASOK KOPI SPESIALTI"
subtitle: "STUDI KASUS: SISTEM TERPADU KAFEINA SPECIALTY ROASTERY DAN MULTI-BRANCH POINT OF SALE BERBASIS HYBRID STATIC ANALYSIS, ARCHITECTURE COMPLIANCE, BASIS PATH STRUCTURAL TESTING, DAN END-TO-END BROWSER AUTOMATION"
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

BAB I PENDAHULUAN\t1
1.1 Latar Belakang dan Keterbatasan Pengujian Kotak Hitam Konvensional\t1
1.2 Urgensi Pengujian Kotak Putih dan Paradigma Multi-Tier Modern\t2
1.3 Rumusan Masalah\t3
1.4 Tujuan Pengujian Sistem\t4
1.5 Batasan Masalah\t4
BAB II METODOLOGI DAN LANDASAN TEORI\t5
2.1 Konsep Dasar Pengujian Kotak Putih (White-Box Testing)\t5
2.2 Dekonstruksi Piramida Pengujian Menjadi 5-Tier Enterprise Quality Framework\t6
2.3 Basis Path Testing dan Pemodelan Control Flow Graph (CFG)\t7
2.4 Perhitungan Kompleksitas Siklomatis (Cyclomatic Complexity McCabe)\t8
2.5 Analisis Statis Tipe dan Defek Laten (Larastan / PHPStan Level 5)\t9
2.6 Penegakan Aturan Arsitektur dan Larangan Degradasi Desain (Pest Architecture Testing)\t10
2.7 Otomasi Pengujian Antarmuka Pengguna dan Browser Riil (Laravel Dusk Headless Chrome)\t11
2.8 Landasan Teori Domain Bisnis dan Formulasi Matematika Kopi Spesialti\t12
BAB III PERANCANGAN DAN IMPLEMENTASI SKRIP PENGUJIAN\t15
3.1 Arsitektur Test Suite Terintegrasi\t15
3.2 Konfigurasi Larastan Level 5 (phpstan.neon)\t16
3.3 Spesifikasi Aturan Pest Architecture Testing (tests/Feature/ArchitectureTest.php)\t17
3.4 Desain Kasus Uji Struktural Berdasarkan 8 Jalur Kritis (White-Box Basis Path)\t18
3.5 Desain Skenario Uji Browser End-to-End Laravel Dusk\t21
BAB IV HASIL PENGUJIAN, GRAFIK ANALISIS, DAN PEMBUKTIAN EMPIRIS\t23
4.1 Rekapitulasi Hasil Eksekusi Test Suite Terpadu (100% Pass Rate)\t23
4.2 Analisis Diagram Control Flow Graph (CFG) dan Cakupan Cabang (100% Branch Coverage)\t24
4.3 Hasil Analisis Statis Larastan Level 5 dan Resolusi 16 Defek Laten\t25
4.4 Hasil Penegakan Kepatuhan Arsitektur Pest PHP v4\t26
4.5 Hasil Eksekusi Test Suite Inti PHPUnit / Pest Feature\t27
4.6 Hasil Otomasi Pengujian Browser Laravel Dusk dan Dokumentasi Tangkapan Layar\t29
4.7 Pembuktian Keaslian Eksekusi Menggunakan PowerShell Terminal Capture\t31
4.8 Matriks Keterlacakan Kebutuhan (Requirements Traceability Matrix - RTM)\t34
BAB V EVALUASI TEKNIS DAN TEMUAN SISTEMATIS\t36
5.1 Perbandingan Komparatif: Pengujian Kotak Hitam vs White-Box Multi-Tier\t36
5.2 Evaluasi Temuan Laten Kritis: Resolusi Kegagalan Pembayaran QRIS Midtrans\t37
5.3 Evaluasi Ketahanan Sistem Terhadap Pergeseran Arsitektur (Architectural Drift)\t38
5.4 Analisis Kinerja Waktu Eksekusi dan Kepadatan Assertion\t39
BAB VI KESIMPULAN DAN SARAN\t41
6.1 Kesimpulan\t41
6.2 Saran Pengembangan Lanjutan\t42
DAFTAR PUSTAKA\t43
"""

    idx_bab1 = content.find("## BAB I PENDAHULUAN")
    if idx_bab1 == -1:
        idx_bab1 = content.find("# BAB I PENDAHULUAN")
    body = content[idx_bab1:]

    body = re.sub(r'^##\s+(BAB\s+[IVXLCDM]+\s+.*)$', r'# \1', body, flags=re.MULTILINE)
    body = re.sub(r'^##\s+(DAFTAR PUSTAKA.*)$', r'# \1', body, flags=re.MULTILINE)
    body = re.sub(r'^###\s+(\d+\.\d+\s+.*)$', r'## \1', body, flags=re.MULTILINE)
    body = re.sub(r'^####\s+(\d+\.\d+\.\d+\s+.*)$', r'### \1', body, flags=re.MULTILINE)

    # 1. Ganti ASCII pyramid dengan Diagram 5-Tier Testing Architecture (Gambar 1)
    old_pyramid = r'```\s*\[\s*Tier 5: E2E Browser Testing\s*\].*?\[\s*Tier 1: Static Type & Defect Analysis\s*\].*?```'
    new_pyramid = """Arsitektur pengujian lima lapis terintegrasi ini disajikan secara komprehensif pada bagan piramida mutu berikut:

![Arsitektur Pengujian Lima Lapis](assets/diagram_5_tier_testing_architecture.png)
Gambar 1. Arsitektur Pengujian Lima Lapis (5-Tier Enterprise Quality Framework) KAFEINA ERP"""
    body = re.sub(old_pyramid, new_pyramid, body, flags=re.DOTALL)

    # 2. Diagram End to End Rantai Pasok (Gambar 2)
    body = re.sub(
        r'(!\[Arsitektur Rantai Pasok.*?\]\(assets/diagram_end_to_end_supply_chain\.png\)\s*\n\s*Gambar \d+\..*?(\n|$))|'
        r'(#### Gambar 2\.1:.*?\n.*?assets/diagram_end_to_end_supply_chain\.png\))',
        'Diagram aliran menyeluruh dari modul pengadaan biji mentah, evaluasi mutu laboratorium sensori, produksi unit sangrai, logistik gudang konsolidasi, hingga kasir POS cabang disajikan pada gambar berikut:\n\n![Arsitektur Rantai Pasok Kopi Spesialti](assets/diagram_end_to_end_supply_chain.png)\nGambar 2. Arsitektur Rantai Pasok Kopi Spesialti Terintegrasi KAFEINA ERP\n',
        body,
        flags=re.DOTALL
    )

    # 3. Browser Captures (Gambar 3 s.d. 6)
    old_browsers_pattern = r'Dokumentasi bukti visual tangkapan layar peramban.*?assets/05_pos_shift_modal\.png\)\s*\n\s*Gambar \d+\..*?(\n|$)'
    new_browsers = """Dokumentasi bukti visual tangkapan layar peramban sesungguhnya yang dihasilkan oleh otomasi Laravel Dusk disajikan pada gambar-gambar berikut:

![Halaman Login KAFEINA ERP](assets/01_login_page.png)
Gambar 3. Antarmuka Autentikasi Pengguna KAFEINA ERP

![Dasbor Analitik Roastery](assets/02_dashboard_overview.png)
Gambar 4. Tampilan Dasbor Analitik Roastery KAFEINA ERP

![Terminal Kasir POS](assets/04_pos_touch_terminal.png)
Gambar 5. Terminal Kasir Layar Sentuh Point of Sale KAFEINA ERP

![Modal Shift POS](assets/05_pos_shift_modal.png)
Gambar 6. Modal Manajemen dan Rekonsiliasi Shift Kasir
"""
    body = re.sub(old_browsers_pattern, new_browsers, body, flags=re.DOTALL)

    # 4. Terminal Captures (Gambar 7 s.d. 10)
    old_terminals_pattern = r'Tangkapan layar terminal pada Gambar \d+ membuktikan eksekusi perintah `vendor/bin/phpstan.*?!\[Terminal Laravel Dusk\]\(assets/terminal_dusk_browser\.png\)\s*\n\s*Gambar \d+\..*?(\n|$)'
    new_terminals = """Tangkapan layar terminal pada Gambar 7 membuktikan eksekusi perintah `vendor/bin/phpstan analyse -v` yang menghasilkan status **100% Passed (0 Errors)** pada seluruh 65 kelas PHP secara komprehensif tanpa cacat tipe data:

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
"""
    body = re.sub(old_terminals_pattern, new_terminals, body, flags=re.DOTALL)

    final_md = frontmatter + "\n\n" + body.strip() + "\n"

    with open(input_path, "w", encoding="utf-8") as f:
        f.write(final_md)

    print("[SUCCESS] White-box markdown perfected with 5-Tier Diagram!")


def perfect_kafeina_report():
    input_path = r"d:\Kafeina\docs\Laporan_Pengujian_Kafeina_ERP_v2_Disempurnakan.md"
    with open(input_path, "r", encoding="utf-8") as f:
        content = f.read()

    content = clean_dashes(content)

    frontmatter = """---
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

BAB I PENDAHULUAN\t1
1.1 Latar Belakang dan Urgensi Pengujian Multi-Tier\t1
1.2 Rumusan Masalah\t2
1.3 Tujuan Pengujian Sistem\t3
1.4 Batasan Pengujian\t4
BAB II METODOLOGI DAN ARSITEKTUR MULTI-TIER TESTING\t5
2.1 Dekonstruksi Paradigma Piramida Pengujian Enterprise\t5
2.2 Tier 1: Analisis Statis Tipe dan Defek (Larastan / PHPStan Level 5)\t6
2.3 Tier 2: Penegakan Kepatuhan Arsitektur (Pest Architecture Testing)\t7
2.4 Tier 3: Pengujian Struktural Kotak Putih (White-Box Basis Path)\t8
2.5 Tier 4: Pengujian Integrasi Fitur dan Domain Bisnis (Pest Feature Test Suite)\t9
2.6 Tier 5: Pengujian Antarmuka Pengguna dan Browser End-to-End (Laravel Dusk)\t10
2.7 Formulasi Matematika Domain Kopi Spesialti dan Transaksi ACID\t11
BAB III PERANCANGAN DAN IMPLEMENTASI KASUS UJI\t14
3.1 Konfigurasi Lingkungan Pengujian dan Autoloading\t14
3.2 Spesifikasi Aturan Larastan Level 5 (phpstan.neon)\t15
3.3 Spesifikasi Penegakan Arsitektur Pest (ArchitectureTest.php)\t16
3.4 Desain Kasus Uji Struktural 8 Jalur Kritis (White-Box Basis Path)\t17
3.5 Desain Skenario Uji Black-Box dan Otomasi Browser Dusk\t20
BAB IV HASIL PENGUJIAN, METRIK KINERJA, DAN PEMBUKTIAN EMPIRIS\t22
4.1 Rekapitulasi Hasil Eksekusi Pengujian Terpadu\t22
4.2 Hasil Analisis Statis Larastan Level 5 dan Resolusi Defek Laten\t23
4.3 Hasil Penegakan Arsitektur Pest PHP v4\t24
4.4 Hasil Eksekusi Test Suite Inti PHPUnit / Pest Feature\t25
4.5 Hasil Otomasi Pengujian Browser Laravel Dusk dan Dokumentasi Visual\t27
4.6 Pembuktian Keaslian Eksekusi Menggunakan PowerShell Terminal Capture\t29
4.7 Matriks Keterlacakan Kebutuhan (Requirements Traceability Matrix)\t32
BAB V EVALUASI TEKNIS DAN ANALISIS KOMPARATIF\t34
5.1 Evaluasi Efektivitas Multi-Tier Testing vs White-Box Tunggal\t34
5.2 Pencegahan Kebocoran Konfigurasi (env Outside Config)\t35
5.3 Pencegahan Pergeseran Arsitektur (Architectural Drift)\t36
5.4 Analisis Kinerja Waktu Eksekusi dan Keandalan Regresi\t37
BAB VI KESIMPULAN DAN REKOMENDASI\t39
6.1 Kesimpulan\t39
6.2 Rekomendasi\t40
DAFTAR PUSTAKA\t41
"""

    idx_bab1 = content.find("## BAB I PENDAHULUAN")
    if idx_bab1 == -1:
        idx_bab1 = content.find("# BAB I PENDAHULUAN")
    body = content[idx_bab1:]

    body = re.sub(r'^##\s+(BAB\s+[IVXLCDM]+\s+.*)$', r'# \1', body, flags=re.MULTILINE)
    body = re.sub(r'^##\s+(DAFTAR PUSTAKA.*)$', r'# \1', body, flags=re.MULTILINE)
    body = re.sub(r'^###\s+(\d+\.\d+\s+.*)$', r'## \1', body, flags=re.MULTILINE)
    body = re.sub(r'^####\s+(\d+\.\d+\.\d+\s+.*)$', r'### \1', body, flags=re.MULTILINE)

    # 1. Ganti ASCII pyramid dengan Diagram 5-Tier Testing Architecture (Gambar 1)
    old_pyramid = r'```\s*\[\s*Tier 5: E2E Browser Testing\s*\].*?\[\s*Tier 1: Static Type & Defect Analysis\s*\].*?```'
    new_pyramid = """Arsitektur pengujian lima lapis terintegrasi ini disajikan secara komprehensif pada bagan piramida mutu berikut:

![Arsitektur Pengujian Lima Lapis](assets/diagram_5_tier_testing_architecture.png)
Gambar 1. Arsitektur Pengujian Lima Lapis (5-Tier Enterprise Testing Architecture) KAFEINA ERP"""
    body = re.sub(old_pyramid, new_pyramid, body, flags=re.DOTALL)

    # 2. Diagram End to End Rantai Pasok (Gambar 2)
    body = re.sub(
        r'(!\[Arsitektur Rantai Pasok.*?\]\(assets/diagram_end_to_end_supply_chain\.png\)\s*\n\s*Gambar \d+\..*?(\n|$))|'
        r'(#### Gambar 2\.1:.*?\n.*?assets/diagram_end_to_end_supply_chain\.png\))',
        'Diagram alur rantai pasok terpadu dari pengadaan hingga titik kasir POS disajikan pada gambar berikut:\n\n![Arsitektur Rantai Pasok Kopi Spesialti](assets/diagram_end_to_end_supply_chain.png)\nGambar 2. Arsitektur Rantai Pasok Kopi Spesialti Terintegrasi KAFEINA ERP\n',
        body,
        flags=re.DOTALL
    )

    # 3. Browser Captures (Gambar 3 s.d. 6)
    old_browsers_pattern = r'Dokumentasi bukti visual tangkapan layar peramban.*?assets/05_pos_shift_modal\.png\)\s*\n\s*Gambar \d+\..*?(\n|$)'
    new_browsers = """Dokumentasi bukti visual tangkapan layar peramban sesungguhnya yang dihasilkan oleh otomasi Laravel Dusk disajikan pada gambar-gambar berikut:

![Halaman Login KAFEINA ERP](assets/01_login_page.png)
Gambar 3. Antarmuka Autentikasi Pengguna KAFEINA ERP

![Dasbor Analitik Roastery](assets/02_dashboard_overview.png)
Gambar 4. Tampilan Dasbor Analitik Roastery KAFEINA ERP

![Terminal Kasir POS](assets/04_pos_touch_terminal.png)
Gambar 5. Terminal Kasir Layar Sentuh Point of Sale KAFEINA ERP

![Modal Shift POS](assets/05_pos_shift_modal.png)
Gambar 6. Modal Manajemen dan Rekonsiliasi Shift Kasir
"""
    body = re.sub(old_browsers_pattern, new_browsers, body, flags=re.DOTALL)

    # 4. Terminal Captures (Gambar 7 s.d. 10)
    old_terminals_pattern = r'Tangkapan layar terminal pada Gambar \d+ membuktikan eksekusi perintah `vendor/bin/phpstan.*?!\[Terminal Laravel Dusk\]\(assets/terminal_dusk_browser\.png\)\s*\n\s*Gambar \d+\..*?(\n|$)'
    new_terminals = """Tangkapan layar terminal pada Gambar 7 membuktikan eksekusi perintah `vendor/bin/phpstan analyse -v` yang menghasilkan status **100% Passed (0 Errors)** pada seluruh 65 kelas PHP secara komprehensif tanpa cacat tipe data:

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
"""
    body = re.sub(old_terminals_pattern, new_terminals, body, flags=re.DOTALL)

    final_md = frontmatter + "\n\n" + body.strip() + "\n"

    with open(input_path, "w", encoding="utf-8") as f:
        f.write(final_md)

    print("[SUCCESS] Kafeina multi-tier markdown perfected with 5-Tier Diagram!")

perfect_whitebox_report()
perfect_kafeina_report()
