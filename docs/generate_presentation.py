import sys
import os
from pathlib import Path
import pptx
from pptx import Presentation
from pptx.util import Inches, Pt
from pptx.dml.color import RGBColor
from pptx.enum.text import PP_ALIGN, MSO_ANCHOR
from pptx.enum.shapes import MSO_SHAPE

def hex_to_rgb(hex_str: str) -> RGBColor:
    hex_str = hex_str.lstrip('#')
    return RGBColor(int(hex_str[0:2], 16), int(hex_str[2:4], 16), int(hex_str[4:6], 16))

# Palette KAFEINA Specialty Roastery
C_DARK = hex_to_rgb("#121820")       # Deep Obsidian Slate
C_CARD_DARK = hex_to_rgb("#1B232E")  # Slate Card Dark
C_LIGHT_BG = hex_to_rgb("#F8FAFC")   # Clean Light Canvas
C_CARD_LIGHT = hex_to_rgb("#FFFFFF") # Pure White
C_BORDER_LIGHT = hex_to_rgb("#E2E8F0")
C_BORDER_DARK = hex_to_rgb("#2A374A")
C_AMBER = hex_to_rgb("#D97706")      # Roasted Gold / Warm Amber
C_EMERALD = hex_to_rgb("#059669")    # Specialty Green Bean
C_RED = hex_to_rgb("#DC2626")        # Rejection / Alert Red
C_BLUE = hex_to_rgb("#2563EB")       # Tech / Enterprise Blue
C_TEXT_DARK = hex_to_rgb("#0F172A")  # Deep Charcoal
C_TEXT_MUTED = hex_to_rgb("#64748B") # Muted Slate
C_TEXT_LIGHT = hex_to_rgb("#F8FAFC") # Crisp White

FONT_TITLE = "Arial"
FONT_BODY = "Arial"

def create_presentation():
    prs = Presentation()
    prs.slide_width = Inches(13.333)
    prs.slide_height = Inches(7.5)
    blank_layout = prs.slide_layouts[6]

    def set_bg(slide, color):
        bg = slide.shapes.add_shape(MSO_SHAPE.RECTANGLE, 0, 0, Inches(13.333), Inches(7.5))
        bg.fill.solid()
        bg.fill.fore_color.rgb = color
        bg.line.fill.background()
        return bg

    def add_header(slide, badge_text, action_title, slide_num, total_slides=16, is_dark=False):
        # Category Badge
        badge = slide.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, Inches(0.8), Inches(0.4), Inches(3.6), Inches(0.32))
        badge.fill.solid()
        badge.fill.fore_color.rgb = C_AMBER if is_dark else hex_to_rgb("#FEF3C7")
        badge.line.color.rgb = C_AMBER
        badge.line.width = Pt(1)
        p = badge.text_frame.paragraphs[0]
        p.text = f"●  {badge_text.upper()}"
        p.font.size = Pt(9)
        p.font.bold = True
        p.font.color.rgb = C_DARK if not is_dark else C_LIGHT_BG
        p.font.name = FONT_BODY
        p.alignment = PP_ALIGN.CENTER
        badge.text_frame.vertical_anchor = MSO_ANCHOR.MIDDLE

        # Action Title
        tb = slide.shapes.add_textbox(Inches(0.8), Inches(0.8), Inches(11.7), Inches(0.85))
        tf = tb.text_frame
        tf.word_wrap = True
        tf.margin_left = tf.margin_top = tf.margin_right = tf.margin_bottom = 0
        p = tf.paragraphs[0]
        p.text = action_title
        p.font.size = Pt(21)
        p.font.bold = True
        p.font.color.rgb = C_TEXT_LIGHT if is_dark else C_TEXT_DARK
        p.font.name = FONT_TITLE

        # Footer Slide Number
        ft = slide.shapes.add_textbox(Inches(11.0), Inches(7.05), Inches(1.5), Inches(0.35))
        p = ft.text_frame.paragraphs[0]
        p.text = f"{slide_num:02d} / {total_slides:02d}"
        p.font.size = Pt(10)
        p.font.bold = True
        p.font.color.rgb = C_TEXT_MUTED if not is_dark else hex_to_rgb("#94A3B8")
        p.alignment = PP_ALIGN.RIGHT

    def add_card(slide, x, y, w, h, bg_color=C_CARD_LIGHT, border_color=C_BORDER_LIGHT, is_round=True):
        shape_type = MSO_SHAPE.ROUNDED_RECTANGLE if is_round else MSO_SHAPE.RECTANGLE
        card = slide.shapes.add_shape(shape_type, Inches(x), Inches(y), Inches(w), Inches(h))
        card.fill.solid()
        card.fill.fore_color.rgb = bg_color
        card.line.color.rgb = border_color
        card.line.width = Pt(1.2)
        return card

    def set_speaker_notes(slide, notes_text):
        notes_slide = slide.notes_slide
        tf = notes_slide.notes_text_frame
        tf.text = notes_text

    # ==========================================
    # SLIDE 1: COVER
    # ==========================================
    s1 = prs.slides.add_slide(blank_layout)
    set_bg(s1, C_DARK)

    # Top Pill
    pill = s1.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, Inches(0.9), Inches(0.8), Inches(4.5), Inches(0.38))
    pill.fill.solid()
    pill.fill.fore_color.rgb = C_CARD_DARK
    pill.line.color.rgb = C_AMBER
    p = pill.text_frame.paragraphs[0]
    p.text = "TUGAS BESAR ENTERPRISE RESOURCE PLANNING (ERP)"
    p.font.size = Pt(9.5)
    p.font.bold = True
    p.font.color.rgb = C_AMBER
    p.alignment = PP_ALIGN.CENTER
    pill.text_frame.vertical_anchor = MSO_ANCHOR.MIDDLE

    # Main Title
    tb = s1.shapes.add_textbox(Inches(0.9), Inches(1.4), Inches(11.5), Inches(2.0))
    tf = tb.text_frame
    tf.word_wrap = True
    p = tf.paragraphs[0]
    p.text = "KAFEINA ERP: SISTEM TERPADU ROASTERY KOPI SPESIALTI & RITEL MULTI-CABANG"
    p.font.size = Pt(32)
    p.font.bold = True
    p.font.color.rgb = C_TEXT_LIGHT
    p.font.name = FONT_TITLE

    p2 = tf.add_paragraph()
    p2.text = "Pemaparan Komprehensif: Rekayasa Kebutuhan (USR), Spesifikasi Teknis (SRS), Desain Arsitektur (SDD), Implementasi Sistem, dan Pengujian 5-Tier"
    p2.font.size = Pt(14)
    p2.font.color.rgb = hex_to_rgb("#94A3B8")
    p2.space_before = Pt(10)

    # Info Cards Bottom (5 Team Members + Dosen & Institusi)
    team_card = add_card(s1, 0.9, 3.8, 7.8, 2.8, bg_color=C_CARD_DARK, border_color=C_BORDER_DARK)
    tb_team = s1.shapes.add_textbox(Inches(1.1), Inches(3.95), Inches(7.4), Inches(2.5))
    tf_team = tb_team.text_frame
    p = tf_team.paragraphs[0]
    p.text = "TIM PENYUSUN (KELOMPOK 1 - INFORMATIKA FT UNSOED):"
    p.font.size = Pt(11)
    p.font.bold = True
    p.font.color.rgb = C_AMBER
    members = [
        "1. Iqsan Azhar (H1D024009) - Koordinator & Requirements Lead",
        "2. Akmal Adhi Nugroho (H1D025021) - Software Requirements Specification (SRS) Lead",
        "3. Dikas Setio Pramudo (H1D024077) - Software Design Document (SDD) & Database Architect",
        "4. M Najmi Zahrian (H1D024038) - Backend Engineering & POS Auto-Deduct Lead",
        "5. Dhea Rafael M.P (H1D024029) - Multi-Tier Quality Assurance & Verification Lead"
    ]
    for m in members:
        pm = tf_team.add_paragraph()
        pm.text = m
        pm.font.size = Pt(10.5)
        pm.font.color.rgb = C_TEXT_LIGHT
        pm.space_before = Pt(4)

    meta_card = add_card(s1, 8.9, 3.8, 3.5, 2.8, bg_color=C_CARD_DARK, border_color=C_BORDER_DARK)
    tb_meta = s1.shapes.add_textbox(Inches(9.1), Inches(3.95), Inches(3.1), Inches(2.5))
    tf_meta = tb_meta.text_frame
    p = tf_meta.paragraphs[0]
    p.text = "DOSEN PENGAMPU:"
    p.font.size = Pt(11)
    p.font.bold = True
    p.font.color.rgb = C_AMBER
    
    p = tf_meta.add_paragraph()
    p.text = "Ir. Muhammad Ihsan Fawzi, M.Kom."
    p.font.size = Pt(11.5)
    p.font.bold = True
    p.font.color.rgb = C_TEXT_LIGHT
    p.space_before = Pt(4)

    p = tf_meta.add_paragraph()
    p.text = "\nPROGRAM STUDI INFORMATIKA\nFAKULTAS TEKNIK\nUNIVERSITAS JENDERAL SOEDIRMAN\nTAHUN AKADEMIK 2026"
    p.font.size = Pt(9.5)
    p.font.color.rgb = hex_to_rgb("#94A3B8")
    p.space_before = Pt(8)

    set_speaker_notes(s1, (
        "Selamat pagi/siang kepada Bapak Ir. Muhammad Ihsan Fawzi, M.Kom. dan rekan-rekan sekalian. "
        "Kami dari Kelompok 1 beranggotakan Iqsan Azhar, Akmal Adhi Nugroho, Dikas Setio Pramudo, M Najmi Zahrian, dan Dhea Rafael M.P. "
        "Pada kesempatan ini, kami mempresentasikan proyek terpadu kami: KAFEINA ERP, sebuah sistem enterprise terintegrasi yang "
        "menghubungkan unit roastery kopi spesialti dengan gerai kasir ritel multi-cabang. "
        "Pemaparan selama 10-15 menit ini akan kami bawakan secara runut mencakup 5 pilar utama: URS, SRS, SDD, Implementasi Sistem, hingga Pengujian 5-Tier."
    ))

    # ==========================================
    # SLIDE 2: LATAR BELAKANG & PROBLEM
    # ==========================================
    s2 = prs.slides.add_slide(blank_layout)
    set_bg(s2, C_LIGHT_BG)
    add_header(s2, "Latar Belakang & Identifikasi Masalah", "Fragmentasi Pencatatan Manual Memicu Asimetri Mutasi Stok dan Bias Margin Laba", 2)

    # 3 Problem Bento Cards
    card1 = add_card(s2, 0.8, 1.8, 3.7, 4.9, bg_color=hex_to_rgb("#FEF2F2"), border_color=hex_to_rgb("#FECACA"))
    tb1 = s2.shapes.add_textbox(Inches(1.0), Inches(2.0), Inches(3.3), Inches(4.5))
    tf1 = tb1.text_frame
    tf1.word_wrap = True
    p = tf1.paragraphs[0]
    p.text = "01. Asimetri Mutasi Stok"
    p.font.size = Pt(14)
    p.font.bold = True
    p.font.color.rgb = C_RED
    points1 = [
        "Masalah:",
        "Penjualan ratusan cup di kasir POS konvensional tidak memotong bahan baku secara otomatis.",
        "Dampak:",
        "Terjadi kebocoran stok fisik (stock leakage) dan disparitas inventori antara data kasir dan stok fisik gudang.",
        "Kebutuhan:",
        "Otomasi Bill of Materials (BOM) multi-komponen seketika saat struk dicetak."
    ]
    for pt in points1:
        pm = tf1.add_paragraph()
        pm.text = pt
        pm.font.size = Pt(10)
        pm.font.color.rgb = C_TEXT_DARK
        pm.space_before = Pt(4)

    card2 = add_card(s2, 4.8, 1.8, 3.7, 4.9, bg_color=hex_to_rgb("#FFFBEB"), border_color=hex_to_rgb("#FDE68A"))
    tb2 = s2.shapes.add_textbox(Inches(5.0), Inches(2.0), Inches(3.3), Inches(4.5))
    tf2 = tb2.text_frame
    tf2.word_wrap = True
    p = tf2.paragraphs[0]
    p.text = "02. Ketiadaan HPP Riil Sangrai"
    p.font.size = Pt(14)
    p.font.bold = True
    p.font.color.rgb = C_AMBER
    points2 = [
        "Masalah:",
        "Penyangraian kopi mengalami susut bobot fisik termal (Yield Loss) berkisar 14% - 18%.",
        "Dampak:",
        "Beban harga beli bahan mentah dibagi bobot yang menyusut tidak dihitung otomatis, memicu laporan laba rugi semu (inaccurate margin).",
        "Kebutuhan:",
        "Rekalkulasi dinamis HPP riil per gram berbasis bobot matang aktual dan biaya kemasan."
    ]
    for pt in points2:
        pm = tf2.add_paragraph()
        pm.text = pt
        pm.font.size = Pt(10)
        pm.font.color.rgb = C_TEXT_DARK
        pm.space_before = Pt(4)

    card3 = add_card(s2, 8.8, 1.8, 3.7, 4.9, bg_color=hex_to_rgb("#F0FDF4"), border_color=hex_to_rgb("#BBF7D0"))
    tb3 = s2.shapes.add_textbox(Inches(9.0), Inches(2.0), Inches(3.3), Inches(4.5))
    tf3 = tb3.text_frame
    tf3.word_wrap = True
    p = tf3.paragraphs[0]
    p.text = "03. Kesalahan Rotasi Persediaan"
    p.font.size = Pt(14)
    p.font.bold = True
    p.font.color.rgb = C_EMERALD
    points3 = [
        "Masalah:",
        "Distribusi stok dari gudang sentral ke cabang tidak menerapkan aturan First-In First-Out (FIFO) yang terkawal.",
        "Dampak:",
        "Biji sangrai yang dipanggang lebih lama tertimbun di gudang sehingga kehilangan aroma dan kesegaran spesialti.",
        "Kebutuhan:",
        "Alokasi FIFO otomatis berbasis tanggal sangrai dan pengawalan status pengiriman in-transit."
    ]
    for pt in points3:
        pm = tf3.add_paragraph()
        pm.text = pt
        pm.font.size = Pt(10)
        pm.font.color.rgb = C_TEXT_DARK
        pm.space_before = Pt(4)

    set_speaker_notes(s2, (
        "Bapak dan rekan-rekan sekalian, industri kopi spesialti di Indonesia kini bertransformasi dari sekadar kedai kopi konvensional "
        "menjadi rantai nilai terpadu farm-to-cup yang memiliki unit roastery mandiri dan multi-cabang. "
        "Namun, riset kami menemukan 3 titik kerapuhan fatal pada operasional UMKM roastery: "
        "Pertama, asimetri mutasi stok di mana penjualan di kasir tidak terhubung ke pemotongan bahan baku di gudang. "
        "Kedua, ketiadaan rekalkulasi HPP riil. Kopi mentah yang disangrai mengalami penyusutan bobot 14 sampai 18 persen, "
        "sehingga jika HPP tidak direkalibrasi secara fraksional per gram, laporan laba rugi perusahaan menjadi bias. "
        "Ketiga, kesalahan rotasi persediaan di mana tidak ada jaminan FIFO, menyebabkan penurunan mutu kopi. "
        "Inilah landasan kami membangun KAFEINA ERP."
    ))

    # ==========================================
    # SLIDE 3: USR (USER REQUIREMENTS SYSTEM)
    # ==========================================
    s3 = prs.slides.add_slide(blank_layout)
    set_bg(s3, C_LIGHT_BG)
    add_header(s3, "User Requirements System (USR)", "Enam Domain Kebutuhan Pengguna Menyatukan Seluruh Siklus Operasional", 3)

    domains = [
        ("01. Procurement & QC Lab", "Siklus Procure-to-Pay (P2P), registrasi mitra tani, validasi moisture 10-12%, SCA score >= 80, dan penerbitan lot unik GB-[FARM]-[YEAR]-[SEQ].", C_EMERALD),
        ("02. Roastery Manufacturing", "Siklus Make-to-Stock (MTS), master profil sangrai, eksekusi Work Order (WO), auto yield loss 14-18%, dan rekalkulasi HPP batch per gram.", C_AMBER),
        ("03. Central-to-Branch Inventory", "Partisi multi-gudang (Sentral, Cabang, Transit), Material Request, alokasi FIFO (idx_wh_stock_fifo), Surat Jalan QR, dan pencatatan selisih.", C_BLUE),
        ("04. Point of Sale & Billing", "Siklus Order-to-Cash (O2C), touch interface >= 48px, PIN 6-digit lockout 5x, auto-deduct resep BOM instan, split bill, dan dynamic QRIS.", C_RED),
        ("05. Executive Finance & P&L", "Dashboard KPI real-time, real P&L berbasis HPP batch aktual, analisis kebocoran persediaan (leakage), peringatan ROP, dan ekspor PDF/Excel.", C_DARK),
        ("06. Governance & RBAC", "Role-Based Access Control (6 peran pengguna), mutasi audit trail transaksional, otorisasi supervisor shift closing > Rp 10.000, backup database.", hex_to_rgb("#475569"))
    ]

    for idx, (d_title, d_desc, d_color) in enumerate(domains):
        row = idx // 3
        col = idx % 3
        cx = 0.8 + col * 4.0
        cy = 1.8 + row * 2.6
        add_card(s3, cx, cy, 3.7, 2.3)
        tb = s3.shapes.add_textbox(Inches(cx + 0.2), Inches(cy + 0.15), Inches(3.3), Inches(2.0))
        tf = tb.text_frame
        tf.word_wrap = True
        p = tf.paragraphs[0]
        p.text = d_title
        p.font.size = Pt(12)
        p.font.bold = True
        p.font.color.rgb = d_color
        p2 = tf.add_paragraph()
        p2.text = d_desc
        p2.font.size = Pt(9.5)
        p2.font.color.rgb = C_TEXT_DARK
        p2.space_before = Pt(4)

    set_speaker_notes(s3, (
        "Dokumen User Requirement System (USR) KAFEINA ERP merumuskan kebutuhan pemangku kepentingan ke dalam 6 domain fungsional: "
        "Mulai dari Pengadaan dan Laboratorium QC untuk menyeleksi hanya biji kopi berkualitas specialty, "
        "Manufaktur Roastery untuk mengotomatisasi penyusutan sangrai dan pembentukan HPP riil, "
        "Manajemen Persediaan Multi-Gudang dengan penegakan mutasi FIFO dan isolasi status barang dalam perjalanan (in-transit), "
        "Point of Sale ritel layar sentuh dengan pemotongan bahan resep otomatis, "
        "Dasbor Finansial Eksekutif untuk memantau laba rugi riil, hingga domain Tata Kelola Keamanan RBAC dan jejak audit transaksi. "
        "Seluruh kebutuhan pengguna ini didefinisikan dengan kriteria keberterimaan formal berbasis skenario Given-When-Then."
    ))

    # ==========================================
    # SLIDE 4: SRS (SOFTWARE REQUIREMENTS SPECIFICATION)
    # ==========================================
    s4 = prs.slides.add_slide(blank_layout)
    set_bg(s4, C_LIGHT_BG)
    add_header(s4, "Software Requirements Specification (SRS)", "Spesifikasi Presisi Berstandar IEEE Std 830-1998 dan Mutu ISO/IEC 25010", 4)

    # 2 Big Cards: Functional Specs & Non-Functional Specs
    add_card(s4, 0.8, 1.8, 5.7, 4.9)
    tb = s4.shapes.add_textbox(Inches(1.0), Inches(2.0), Inches(5.3), Inches(4.5))
    tf = tb.text_frame
    tf.word_wrap = True
    p = tf.paragraphs[0]
    p.text = "Kebutuhan Fungsional (IEEE 830 Conformance)"
    p.font.size = Pt(14)
    p.font.bold = True
    p.font.color.rgb = C_BLUE
    f_items = [
        "• Kodifikasi Baku: Kode kebutuhan alfanumerik terstruktur REQ-[MODUL]-[NOMOR] (contoh: REQ-PRC-01 hingga REQ-POS-04).",
        "• Model Peran (RBAC): 6 peran terdefinisi tegas (Super Admin, Procurement, QC Inspector, Roaster, Warehouse, Cashier).",
        "• Stimulus/Response Terdefinisi: Setiap kebutuhan mendefinisikan aksi masukan pengguna, kondisi validasi, dan mutasi basis data terkait.",
        "• Siklus Transaksi End-to-End: Menyelaraskan alur Purchase Order -> QC Lab -> Work Order -> Delivery Note -> POS Sale -> Jurnal Finansial."
    ]
    for it in f_items:
        pm = tf.add_paragraph()
        pm.text = it
        pm.font.size = Pt(10)
        pm.font.color.rgb = C_TEXT_DARK
        pm.space_before = Pt(6)

    add_card(s4, 6.8, 1.8, 5.7, 4.9)
    tb = s4.shapes.add_textbox(Inches(7.0), Inches(2.0), Inches(5.3), Inches(4.5))
    tf = tb.text_frame
    tf.word_wrap = True
    p = tf.paragraphs[0]
    p.text = "Kebutuhan Non-Fungsional (ISO/IEC 25010 SQuaRE)"
    p.font.size = Pt(14)
    p.font.bold = True
    p.font.color.rgb = C_EMERALD
    nf_items = [
        "1. Performance Efficiency: Latensi transaksi POS <= 100ms; rendering dasbor <= 2 detik pada volume transaksi tinggi.",
        "2. Reliability & Fault Tolerance: Integritas ACID transaksi multi-tabel; rollback atomik 100% saat terjadi kegagalan gateway eksternal.",
        "3. Security: Autentikasi 6-digit PIN kasir dengan rate-limiting 5-strike lockout (15 menit); verifikasi HMAC SHA512 pada webhook Midtrans.",
        "4. Usability: Antarmuka kasir layar sentuh dengan ukuran elemen sentuh minimal 48px; navigasi cepat berbasis kategori dan drawer keranjang."
    ]
    for it in nf_items:
        pm = tf.add_paragraph()
        pm.text = it
        pm.font.size = Pt(10)
        pm.font.color.rgb = C_TEXT_DARK
        pm.space_before = Pt(6)

    set_speaker_notes(s4, (
        "Beralih ke pilar kedua, Dokumen Software Requirements Specification (SRS) KAFEINA ERP disusun mengacu pada standar internasional "
        "IEEE Std 830-1998 untuk pendefinisian kebutuhan fungsional dan model kualitas perangkat lunak ISO/IEC 25010. "
        "Setiap kebutuhan fungsional dikodifikasikan secara unik, misalnya REQ-PRC untuk pengadaan, REQ-PRD untuk roastery, hingga REQ-POS untuk kasir. "
        "Dari segi non-fungsional, sistem mengedepankan performa transaksi cepat di bawah 100 milidetik, "
        "keandalan transaksi atomik ACID tanpa toleransi terhadap dirty writes, proteksi brute-force PIN kasir melalui mekanisme lockout 5 kali percobaan salah, "
        "serta ergonomi layar sentuh berukuran target minimal 48 piksel."
    ))

    # ==========================================
    # SLIDE 5: REQUIREMENTS TRACEABILITY MATRIX (RTM)
    # ==========================================
    s5 = prs.slides.add_slide(blank_layout)
    set_bg(s5, C_LIGHT_BG)
    add_header(s5, "Requirements Traceability Matrix (RTM)", "Matriks Ketertelusuran RTM Membuktikan Keterlacakan 1:1 dari Kebutuhan ke Pengujian", 5)

    # RTM Table
    rows = 6
    cols = 5
    table_shape = s5.shapes.add_table(rows, cols, Inches(0.8), Inches(1.8), Inches(11.7), Inches(3.6))
    table = table_shape.table
    table.columns[0].width = Inches(1.5)
    table.columns[1].width = Inches(1.5)
    table.columns[2].width = Inches(3.2)
    table.columns[3].width = Inches(3.0)
    table.columns[4].width = Inches(2.5)

    headers = ["ID URS", "ID SRS", "Deskripsi Kebutuhan Teknis", "Service & Controller Layer", "ID Kasus Uji (Test Suite)"]
    for c_idx, h in enumerate(headers):
        cell = table.cell(0, c_idx)
        cell.fill.solid()
        cell.fill.fore_color.rgb = C_DARK
        p = cell.text_frame.paragraphs[0]
        p.text = h
        p.font.size = Pt(9.5)
        p.font.bold = True
        p.font.color.rgb = C_TEXT_LIGHT
        p.alignment = PP_ALIGN.CENTER

    rtm_data = [
        ("FR-PRC-04", "REQ-PRC-04", "Validasi Mutu QC SCA (Score >= 80) & Air", "QualityControlService::evaluate", "WB-P02, Tier2-Domain-02"),
        ("FR-MFG-05", "REQ-PRD-05", "Kalkulasi Susut Yield Loss Sangrai (14-18%)", "WorkOrderService::calculateYield", "WB-P03, Tier2-Domain-04"),
        ("FR-MFG-06", "REQ-PRD-06", "Rekalkulasi Dinamis HPP Riil per Gram", "WorkOrderService::calculateHpp", "WB-P03, Tier4-Scen-02"),
        ("FR-INV-03", "REQ-INV-03", "Alokasi Pengeluaran Batch FIFO", "FifoStockAllocatorService::allocate", "WB-P04, Tier3-Pair-03"),
        ("FR-POS-02", "REQ-POS-02", "Pemotongan Resep BOM Instan & Pessimistic Lock", "PosBillingService::processCheckout", "WB-P05, Tier4-Pos-01")
    ]
    for r_idx, row in enumerate(rtm_data, start=1):
        for c_idx, val in enumerate(row):
            cell = table.cell(r_idx, c_idx)
            cell.fill.solid()
            cell.fill.fore_color.rgb = C_CARD_LIGHT if r_idx % 2 == 1 else hex_to_rgb("#F1F5F9")
            p = cell.text_frame.paragraphs[0]
            p.text = val
            p.font.size = Pt(9)
            p.font.color.rgb = C_TEXT_DARK
            if c_idx in (0, 1):
                p.font.bold = True
                p.alignment = PP_ALIGN.CENTER

    # External Integration Callout below
    callout = add_card(s5, 0.8, 5.6, 11.7, 1.2, bg_color=hex_to_rgb("#EFF6FF"), border_color=hex_to_rgb("#BFDBFE"))
    tb_c = s5.shapes.add_textbox(Inches(1.0), Inches(5.7), Inches(11.3), Inches(1.0))
    tf_c = tb_c.text_frame
    p = tf_c.paragraphs[0]
    p.text = "Spesifikasi Kontrak Antarmuka Eksternal:"
    p.font.size = Pt(10.5)
    p.font.bold = True
    p.font.color.rgb = C_BLUE
    p2 = tf_c.add_paragraph()
    p2.text = "1. Midtrans Payment Gateway: Dynamic QRIS via Core API + verifikasi callback webhook berbasis HMAC SHA512 signature hash.\n2. ESC/POS Thermal Printing: Aliran byte ESC/POS (58/80mm) dan pemicu pulsa laci uang RJ-11 (ESC p 0 25 250)."
    p2.font.size = Pt(9)
    p2.font.color.rgb = C_TEXT_DARK
    p2.space_before = Pt(2)

    set_speaker_notes(s5, (
        "Salah satu kekuatan dokumen spesifikasi kami adalah adanya Matriks Ketertelusuran Kebutuhan atau Requirements Traceability Matrix (RTM). "
        "RTM ini memetakan korelasi satu-ke-satu secara transparan: Dari nomor kebutuhan pengguna di URS, diturunkan ke nomor spesifikasi di SRS, "
        "diimplementasikan ke dalam metode konkret pada Controller dan Service Layer, disimpan pada model basis data, "
        "hingga diuji oleh kasus uji spesifik pada test suite kami. "
        "Dengan RTM ini, kami membuktikan tidak ada fitur yang terlupakan (unimplemented requirement) dan tidak ada kode yatim yang tidak teruji (untested code). "
        "Selain itu, spesifikasi kontrak antarmuka eksternal ke Midtrans QRIS dan printer termal ESC/POS telah didefinisikan secara baku."
    ))

    # ==========================================
    # SLIDE 6: SDD - C4 SYSTEM ARCHITECTURE
    # ==========================================
    s6 = prs.slides.add_slide(blank_layout)
    set_bg(s6, C_LIGHT_BG)
    add_header(s6, "Software Design Document (SDD) - Arsitektur C4", "Pemisahan Tanggung Jawab Melalui Pola Arsitektur Tiga Lapis (Three-Tier Clean Architecture)", 6)

    # 3 Layers Bento Boxes
    layers = [
        ("Tier 1: Presentation Layer", "Antarmuka Responsif & Interaktif", [
            "• Single Page Application (SPA) responsif.",
            "• Tailwind CSS v4 untuk penataan gaya modern.",
            "• Alpine.js / Vue.js 3 untuk reaktivitas antarmuka kasir.",
            "• Desain sentuh teroptimasi viewport tablet kasir 10-12 inci.",
            "• Transisi halus <= 300ms sesuai prinsip Emil Kowalski."
        ], C_BLUE),
        ("Tier 2: Application Logic Layer", "Inti Pemrosesan Bisnis Terisolasi", [
            "• Framework Laravel 13 running on PHP 8.3+.",
            "• Dedicated Service Layer pada namespace App\\Services.",
            "• Enkapsulasi kalkulasi matematis (yield, HPP, FIFO, auto-deduct).",
            "• Middleware RBAC & proteksi PIN kasir 6-digit.",
            "• Integrasi payment gateway Midtrans & ESC/POS printer."
        ], C_AMBER),
        ("Tier 3: Data Persistence Layer", "Penyimpanan Relasional ACID", [
            "• Basis data MySQL 8.0 Enterprise Engine InnoDB.",
            "• 27-29 tabel relasional terstruktur dalam normalisasi 3NF.",
            "• Composite B-Tree Indexes (idx_wh_stock_fifo).",
            "• Pessimistic Row Locking (SELECT ... FOR UPDATE).",
            "• Jaminan transaksi atomik konsisten (ACID Compliant)."
        ], C_EMERALD)
    ]

    for idx, (t_name, t_sub, t_bullets, t_color) in enumerate(layers):
        cx = 0.8 + idx * 4.0
        add_card(s6, cx, 1.8, 3.7, 4.9)
        tb = s6.shapes.add_textbox(Inches(cx + 0.2), Inches(2.0), Inches(3.3), Inches(4.5))
        tf = tb.text_frame
        tf.word_wrap = True
        p = tf.paragraphs[0]
        p.text = t_name
        p.font.size = Pt(13)
        p.font.bold = True
        p.font.color.rgb = t_color
        p_sub = tf.add_paragraph()
        p_sub.text = t_sub
        p_sub.font.size = Pt(9.5)
        p_sub.font.bold = True
        p_sub.font.color.rgb = C_TEXT_MUTED
        p_sub.space_before = Pt(2)
        for b in t_bullets:
            pm = tf.add_paragraph()
            pm.text = b
            pm.font.size = Pt(9.5)
            pm.font.color.rgb = C_TEXT_DARK
            pm.space_before = Pt(6)

    set_speaker_notes(s6, (
        "Memasuki pilar ketiga, Dokumen Desain Perangkat Lunak (SDD) KAFEINA ERP dirancang menggunakan model arsitektur C4 yang membagi sistem "
        "ke dalam arsitektur Tiga Lapis (Three-Tier Clean Architecture): "
        "Lapisan pertama adalah Presentation Layer yang menyajikan Web POS sentuh dengan Tailwind CSS v4 dan reaktivitas interaktif. "
        "Lapisan kedua adalah Application Logic Layer berbasis Laravel 13 PHP 8.3 dengan pemisahan tugas murni: kontroler hanya bertindak "
        "menerima HTTP request, sedangkan seluruh logika matematika bisnis diisolasi ke dalam Service Layer. "
        "Lapisan ketiga adalah Data Layer berbasis MySQL 8.0 InnoDB dengan integritas referensial dan penguncian pesimis untuk mencegah race condition."
    ))

    # ==========================================
    # SLIDE 7: SDD - OOP SERVICE LAYER
    # ==========================================
    s7 = prs.slides.add_slide(blank_layout)
    set_bg(s7, C_LIGHT_BG)
    add_header(s7, "Software Design Document (SDD) - Service Layer OOP", "Enkapsulasi Logika Bisnis ke Dedicated Services Menjamin Kode Bersih dan Teruji", 7)

    services_data = [
        ("CashierPinAuthService", "Keamanan Kasir", "verifyPin(), isLocked(), getRemainingLockoutSeconds()", "Mengelola verifikasi hash PIN kasir dan state machine 5-strike lockout selama 15 menit.", C_RED),
        ("QualityControlService", "Kendali Mutu", "evaluateLot(), calculateDefectPenalty(), assignLotGrade()", "Mengevaluasi kadar air (10-12%), cacat primer (0), dan skor sensori SCA (>= 80).", C_EMERALD),
        ("WorkOrderService", "Manufaktur Sangrai", "startWorkOrder(), completeRoasting(), calculateYieldLoss(), calculateRealHpp()", "Menghitung susut bobot fisik (14-18%) dan rekalkulasi HPP riil per gram matang.", C_AMBER),
        ("FifoStockAllocatorService", "Logistik Persediaan", "allocateFifoStock(), verifyAvailability()", "Mengalokasikan stok batch tertua berdasarkan roasted_date ASC dengan penguncian kueri.", C_BLUE),
        ("PosBillingService", "Kasir & Transaksi", "processCheckout(), deductRecipeStock(), applyVoucherDiscount()", "Memproses transaksi O2C dan memotong stok bahan resep BOM secara pesimistik.", C_DARK),
        ("MidtransQrisService", "Gateway Pembayaran", "createQrisCharge(), verifyWebhookSignature(), checkTransactionStatus()", "Mengirimkan muatan API QRIS dinamis dan memvalidasi keabsahan signature hash webhook.", hex_to_rgb("#6366F1"))
    ]

    for idx, (s_name, s_badge, s_methods, s_desc, s_color) in enumerate(services_data):
        row = idx // 2
        col = idx % 2
        cx = 0.8 + col * 5.95
        cy = 1.8 + row * 1.65
        add_card(s7, cx, cy, 5.75, 1.5)
        tb = s7.shapes.add_textbox(Inches(cx + 0.15), Inches(cy + 0.1), Inches(5.45), Inches(1.3))
        tf = tb.text_frame
        tf.word_wrap = True
        p = tf.paragraphs[0]
        p.text = f"{s_name} ({s_badge})"
        p.font.size = Pt(11)
        p.font.bold = True
        p.font.color.rgb = s_color
        p2 = tf.add_paragraph()
        p2.text = f"Metode: {s_methods}"
        p2.font.size = Pt(8.5)
        p2.font.bold = True
        p2.font.color.rgb = C_TEXT_MUTED
        p2.space_before = Pt(2)
        p3 = tf.add_paragraph()
        p3.text = s_desc
        p3.font.size = Pt(9)
        p3.font.color.rgb = C_TEXT_DARK
        p3.space_before = Pt(2)

    set_speaker_notes(s7, (
        "Pada perancangan berorientasi objek (OOP), kami menerapkan prinsip Single Responsibility Principle dengan membagi logika domain "
        "ke dalam kelas-kelas layanan spesifik di bawah namespace App\\Services. "
        "Di antaranya: CashierPinAuthService untuk keamanan PIN, QualityControlService untuk penentuan kelayakan biji kopi, "
        "WorkOrderService untuk formula penyusutan sangrai dan HPP, FifoStockAllocatorService untuk alokasi stok terlama, "
        "PosBillingService untuk pemrosesan penjualan dan pemotongan bahan BOM, serta MidtransQrisService untuk gerbang pembayaran. "
        "Pola desain ini menjamin pemisahan kode yang bersih dan memungkinkan unit testing yang terisolasi tanpa efek samping."
    ))

    # ==========================================
    # SLIDE 8: SDD - DATABASE 27 TABLES & CONCURRENCY
    # ==========================================
    s8 = prs.slides.add_slide(blank_layout)
    set_bg(s8, C_LIGHT_BG)
    add_header(s8, "Software Design Document (SDD) - Basis Data & Konkurensi", "Struktur 27 Tabel Normalisasi 3NF dengan Indeks B-Tree dan Penguncian Pesimis", 8)

    # Left: Database Schema Highlights
    add_card(s8, 0.8, 1.8, 5.7, 4.9)
    tb = s8.shapes.add_textbox(Inches(1.0), Inches(2.0), Inches(5.3), Inches(4.5))
    tf = tb.text_frame
    tf.word_wrap = True
    p = tf.paragraphs[0]
    p.text = "Skema Data Relasional & Integritas (MySQL 8.0 InnoDB)"
    p.font.size = Pt(13)
    p.font.bold = True
    p.font.color.rgb = C_BLUE
    db_points = [
        "• 27 Tabel Relasional Terstandarisasi 3NF: suppliers, green_bean_lots, quality_controls, roasting_profiles, work_orders, boms, bom_items, inventory_warehouses, warehouse_stocks, stock_cards, material_requests, delivery_notes, pos_shifts, pos_orders, pos_order_items, recipe_deductions, audit_logs, dll.",
        "• Indeks B-Tree Komposit Strategis: idx_wh_stock_fifo pada tabel warehouse_stocks (warehouse_id, product_id, roasted_date, quantity) guna memastikan kueri alokasi FIFO ultra-cepat.",
        "• Referential Integrity: Aturan ON DELETE RESTRICT pada entitas master untuk mencegah kehilangan riwayat dan ON DELETE CASCADE pada baris rincian transaksi."
    ]
    for dbp in db_points:
        pm = tf.add_paragraph()
        pm.text = dbp
        pm.font.size = Pt(9.5)
        pm.font.color.rgb = C_TEXT_DARK
        pm.space_before = Pt(6)

    # Right: Concurrency & ACID
    add_card(s8, 6.8, 1.8, 5.7, 4.9)
    tb = s8.shapes.add_textbox(Inches(7.0), Inches(2.0), Inches(5.3), Inches(4.5))
    tf = tb.text_frame
    tf.word_wrap = True
    p = tf.paragraphs[0]
    p.text = "Arsitektur Konkurensi & Integritas Transaksi ACID"
    p.font.size = Pt(13)
    p.font.bold = True
    p.font.color.rgb = C_EMERALD
    con_points = [
        "• Pessimistic Row Locking (SELECT ... FOR UPDATE): Menggunakan klausa lockForUpdate() pada pemotongan saldo stok bahan baku gerai saat antrean pesanan kasir memuncak serentak (mencegah race condition dan double spending stok).",
        "• Pembatalan Atomik (DB::rollBack()): Seluruh transaksi multi-tabel (pembukuan pesanan, pemotongan stok bahan resep, dan pencatatan kartu stok) diisolasi dalam blok DB::transaction().",
        "• Zero Dirty Writes: Apabila koneksi internet terputus atau webhook payment gateway gagal, seluruh mutasi basis data dibatalkan utuh, menjamin saldo inventori fisik tidak mengalami kebocoran."
    ]
    for cp in con_points:
        pm = tf.add_paragraph()
        pm.text = cp
        pm.font.size = Pt(9.5)
        pm.font.color.rgb = C_TEXT_DARK
        pm.space_before = Pt(6)

    set_speaker_notes(s8, (
        "Dari perspektif perancangan basis data, KAFEINA ERP mengimplementasikan 27 tabel relasional yang dinormalisasi hingga bentuk 3NF "
        "pada engine MySQL 8.0 InnoDB. "
        "Untuk menangani tantangan performa pada pencarian biji kopi tertua, kami merancang indeks komposit idx_wh_stock_fifo. "
        "Lebih penting lagi, sistem mengadopsi kontrol konkurensi tingkat tinggi: saat terjadi lonjakan transaksi di kasir, "
        "sistem menggunakan pessimistic row locking (SELECT ... FOR UPDATE) untuk mengunci baris stok bahan baku agar tidak terjadi race condition. "
        "Seluruh transaksi dibungkus dalam transaksi atomik ACID, sehingga jika terjadi kegagalan jaringan, database akan melakukan rollback penuh "
        "tanpa meninggalkan data sampah atau saldo stok yang terpotong secara keliru."
    ))

    # ==========================================
    # SLIDE 9: IMPLEMENTASI - P2P & QC LAB
    # ==========================================
    s9 = prs.slides.add_slide(blank_layout)
    set_bg(s9, C_LIGHT_BG)
    add_header(s9, "Implementasi Proyek: Hulu (Procure-to-Pay & QC Lab)", "Penyaringan Mutu Komprehensif Berdasarkan Parameter Fisik & Protokol Sensori SCA", 9)

    add_card(s9, 0.8, 1.8, 5.7, 4.9)
    tb = s9.shapes.add_textbox(Inches(1.0), Inches(2.0), Inches(5.3), Inches(4.5))
    tf = tb.text_frame
    tf.word_wrap = True
    p = tf.paragraphs[0]
    p.text = "Formulasi Logika Diskrit Evaluasi Mutu QC"
    p.font.size = Pt(13)
    p.font.bold = True
    p.font.color.rgb = C_EMERALD
    qc_bullets = [
        "Kriteria Keputusan Mutu Biji Mentah (Green Beans):",
        "1. ACCEPTED (Specialty Grade 1):\n   (10.0% <= Kadar Air <= 12.0%) AND (Cacat Primer == 0) AND (Skor Cupping SCA >= 80.0)",
        "2. REJECTED (Substandard / Karantina):\n   (Kadar Air < 9% OR > 13%) OR (Cacat Primer > 3) OR (Skor Cupping SCA < 75.0)",
        "3. CONDITIONAL:\n   Kondisi di antara ambang batas yang memerlukan penjemuran ulang atau negosiasi ulang harga.",
        "Dampak Bisnis: Menjamin roastery hanya memproses biji kopi bermutu tinggi dan melindungi reputasi brand dari cacat cita rasa."
    ]
    for qb in qc_bullets:
        pm = tf.add_paragraph()
        pm.text = qb
        pm.font.size = Pt(9.5)
        pm.font.color.rgb = C_TEXT_DARK
        pm.space_before = Pt(4)

    add_card(s9, 6.8, 1.8, 5.7, 4.9)
    tb = s9.shapes.add_textbox(Inches(7.0), Inches(2.0), Inches(5.3), Inches(4.5))
    tf = tb.text_frame
    tf.word_wrap = True
    p = tf.paragraphs[0]
    p.text = "Registrasi Lot Otomatis & Ketertelusuran Asal Kebun"
    p.font.size = Pt(13)
    p.font.bold = True
    p.font.color.rgb = C_BLUE
    lot_bullets = [
        "• Kodifikasi Lot Unik: Sistem membangkitkan format identitas lot otomatis:\n  GB-[FARM_CODE]-[YEAR]-[SEQUENCE]\n  Contoh: GB-TMR-2026-0001 (Kebun Temanggung, Tahun 2026, Urutan 01).",
        "• Pencatatan Metadata Lengkap: Meliputi varietas kopi (Typica, Kartika, Bourbon), ketinggian tanam (mdpl), metode proses pasca-panen (Full Wash, Natural, Honey, Anaerobic), dan nama kelompok tani mitra.",
        "• Mutasi Stok Terverifikasi: Saldo stok di gudang silo sentral baru bertambah setelah status QC dinyatakan ACCEPTED dan GRN ditandatangani.",
        "• Ketertelusuran Halal & BPOM: Riwayat lot dapat ditelusuri balik dari cangkir di kasir hingga ke koordinat kebun petani asal."
    ]
    for lb in lot_bullets:
        pm = tf.add_paragraph()
        pm.text = lb
        pm.font.size = Pt(9.5)
        pm.font.color.rgb = C_TEXT_DARK
        pm.space_before = Pt(4)

    set_speaker_notes(s9, (
        "Memasuki tahap Implementasi Proyek, kami membaginya ke dalam alur rantai pasok kopi terpadu dari hulu ke hilir. "
        "Di sisi hulu, modul Procure-to-Pay terintegrasi langsung dengan laboratorium Quality Control. "
        "Biji mentah dari mitra petani wajib diuji parameter fisiknya: kadar air harus berada di rentang ideal 10 sampai 12 persen, "
        "nol cacat primer, dan skor uji sensori SCA minimal 80 poin untuk meraih predikat Specialty Grade. "
        "Setiap lot yang lulus langsung diberi kode unik terstandar, misalnya GB-TMR-2026-0001. "
        "Hal ini menjamin ketertelusuran penuh (traceability) dari cangkir kopi konsumen hingga ke petak kebun petani asalnya."
    ))

    # ==========================================
    # SLIDE 10: IMPLEMENTASI - ROASTERY YIELD & HPP
    # ==========================================
    s10 = prs.slides.add_slide(blank_layout)
    set_bg(s10, C_LIGHT_BG)
    add_header(s10, "Implementasi Proyek: Manufaktur (Roastery Yield & HPP Riil)", "Otomasi Formulasi Susut Bobot Termal 14–18% dan Rekalkulasi Dinamis HPP Riil", 10)

    add_card(s10, 0.8, 1.8, 5.7, 4.9)
    tb = s10.shapes.add_textbox(Inches(1.0), Inches(2.0), Inches(5.3), Inches(4.5))
    tf = tb.text_frame
    tf.word_wrap = True
    p = tf.paragraphs[0]
    p.text = "Formulasi Matematika Susut Bobot (Yield Loss)"
    p.font.size = Pt(13)
    p.font.bold = True
    p.font.color.rgb = C_AMBER
    yield_bullets = [
        "Selama proses sangrai, pelepasan uap air dan karbon dioksida mengakibatkan susut bobot fisik:",
        "Persamaan Yield Loss (%):\nYield Loss = ((W_mentah - W_matang) / W_mentah) x 100%",
        "Standar Baku Industri Specialty Coffee:\n14.0% <= Yield Loss <= 18.0%",
        "Validasi Sistem Otomatis:\n• Jika Yield Loss < 14%: Sistem menerbitkan peringatan under-developed (biji kopi berisiko grassy / mentah di dalam).\n• Jika Yield Loss > 18%: Sistem menerbitkan peringatan over-developed (biji berisiko gosong / arang).",
        "Eksekusi Work Order: Operator memasukkan bobot akhir matang, sistem memvalidasi batas toleransi dan mencatat deviasi."
    ]
    for yb in yield_bullets:
        pm = tf.add_paragraph()
        pm.text = yb
        pm.font.size = Pt(9.5)
        pm.font.color.rgb = C_TEXT_DARK
        pm.space_before = Pt(4)

    add_card(s10, 6.8, 1.8, 5.7, 4.9)
    tb = s10.shapes.add_textbox(Inches(7.0), Inches(2.0), Inches(5.3), Inches(4.5))
    tf = tb.text_frame
    tf.word_wrap = True
    p = tf.paragraphs[0]
    p.text = "Rekalkulasi Dinamis Harga Pokok Penjualan (HPP)"
    p.font.size = Pt(13)
    p.font.bold = True
    p.font.color.rgb = C_DARK
    hpp_bullets = [
        "Karena massa kopi menyusut sedangkan biaya pembelian tetap, HPP per gram wajib disesuaikan secara proporsional:",
        "Persamaan HPP Riil per Gram Matang:\nHPP_riil = (HPP_mentah / (1 - (Yield Loss / 100))) + Overhead",
        "Contoh Riil Kasus Uji WB-P03:\n• Biaya green bean: Rp 140 / gram (Rp 140.000 / kg)\n• Bobot mentah: 5.000 g | Bobot matang: 4.240 g\n• Yield Loss terhitung: 15.20% (Lolos standar 14-18%)\n• Overhead kemasan sekunder: Rp 3.20 / gram\n• HPP Riil terbentuk: Rp 168.30 / gram matang.",
        "Konversi Atomik: Pengurangan stok biji mentah dan penambahan stok biji sangrai terjadi dalam satu transaksi atomik."
    ]
    for hb in hpp_bullets:
        pm = tf.add_paragraph()
        pm.text = hb
        pm.font.size = Pt(9.5)
        pm.font.color.rgb = C_TEXT_DARK
        pm.space_before = Pt(4)

    set_speaker_notes(s10, (
        "Di modul manufaktur roastery, sistem menyelesaikan masalah krusial yang selama ini diabaikan oleh software akuntansi biasa: "
        "yaitu kalkulasi susut bobot atau yield loss dan dampaknya terhadap HPP. "
        "Saat menyangrai, biji kopi kehilangan 14 sampai 18 persen bobotnya akibat penguapan air. "
        "Sistem KAFEINA ERP secara otomatis menghitung persentase susut bobot ini ketika head roaster memasukkan bobot akhir matang. "
        "Kemudian, sistem secara dinamis merekalibrasi HPP per gram biji sangrai matang menggunakan formula konservasi biaya yang membebankan "
        "biaya bahan mentah, kemasan pouch ber-valve, dan energi. "
        "Hasilnya, perusahaan memiliki data margin laba kotor yang 100% akurat dan terhindar dari ilusi laba semu."
    ))

    # ==========================================
    # SLIDE 11: IMPLEMENTASI - FIFO, TRANSIT, & POS AUTO-DEDUCT
    # ==========================================
    s11 = prs.slides.add_slide(blank_layout)
    set_bg(s11, C_LIGHT_BG)
    add_header(s11, "Implementasi Proyek: Hilir (Logistik FIFO & Web POS)", "Alokasi Batch Tertua FIFO dan Pemotongan Resep BOM Multi-Bahan Seketika pada POS", 11)

    # 3 Column Cards
    col1 = add_card(s11, 0.8, 1.8, 3.7, 4.9)
    tb1 = s11.shapes.add_textbox(Inches(1.0), Inches(2.0), Inches(3.3), Inches(4.5))
    tf1 = tb1.text_frame
    tf1.word_wrap = True
    p = tf1.paragraphs[0]
    p.text = "Logistik & Alokasi FIFO"
    p.font.size = Pt(13)
    p.font.bold = True
    p.font.color.rgb = C_BLUE
    pts1 = [
        "• Alokasi Batch Tertua: Kueri sistem mengunci batch tertua berdasarkan roasted_date ASC.",
        "• Virtual IN_TRANSIT: Saat barang dikirim dari sentral, saldo diisolasi pada akun virtual transit.",
        "• Verifikasi Surat Jalan: Manajer gerai cabang memindai QR code Surat Jalan untuk mengonfirmasi penerimaan fisik dan mencatat selisih."
    ]
    for pt in pts1:
        pm = tf1.add_paragraph()
        pm.text = pt
        pm.font.size = Pt(9.5)
        pm.font.color.rgb = C_TEXT_DARK
        pm.space_before = Pt(6)

    col2 = add_card(s11, 4.8, 1.8, 3.7, 4.9)
    tb2 = s11.shapes.add_textbox(Inches(5.0), Inches(2.0), Inches(3.3), Inches(4.5))
    tf2 = tb2.text_frame
    tf2.word_wrap = True
    p = tf2.paragraphs[0]
    p.text = "Terminal Kasir Layar Sentuh"
    p.font.size = Pt(13)
    p.font.bold = True
    p.font.color.rgb = C_RED
    pts2 = [
        "• Ergonomi Tablet POS: Target sentuh minimal 48px, keypad numerik cepat, pencarian katalog instan.",
        "• Motion Standard Emil Kowalski: Aksi frekuensi tinggi 0ms (instan), modal/drawer <= 300ms.",
        "• Multi-Payment: Tunai dengan hitung kembalian cepat dan Dynamic QRIS Midtrans.",
        "• Split-Bill: Kemampuan membagi tagihan meja ke beberapa metode pembayaran berbeda."
    ]
    for pt in pts2:
        pm = tf2.add_paragraph()
        pm.text = pt
        pm.font.size = Pt(9.5)
        pm.font.color.rgb = C_TEXT_DARK
        pm.space_before = Pt(6)

    col3 = add_card(s11, 8.8, 1.8, 3.7, 4.9)
    tb3 = s11.shapes.add_textbox(Inches(9.0), Inches(2.0), Inches(3.3), Inches(4.5))
    tf3 = tb3.text_frame
    tf3.word_wrap = True
    p = tf3.paragraphs[0]
    p.text = "Pemotongan BOM & Shift Closing"
    p.font.size = Pt(13)
    p.font.bold = True
    p.font.color.rgb = C_EMERALD
    pts3 = [
        "• BOM Recipe Auto-Deduct: Penjualan 1 cup Caffe Latte memotong 18g espresso blend, 200ml fresh milk, dan 1 paper cup secara atomik.",
        "• Shift Closing Aman: Sistem menerapkan blind count uang fisik kasir.",
        "• Supervisor Gatekeeper: Jika selisih kas > Rp 10.000, wajib otorisasi PIN supervisor cabang sebelum shift dapat ditutup."
    ]
    for pt in pts3:
        pm = tf3.add_paragraph()
        pm.text = pt
        pm.font.size = Pt(9.5)
        pm.font.color.rgb = C_TEXT_DARK
        pm.space_before = Pt(6)

    set_speaker_notes(s11, (
        "Di sisi hilir rantai pasok, sistem mengintegrasikan distribusi persediaan dan operasional kasir ritel. "
        "Pengiriman barang dari gudang sentral ke cabang dikawal ketat oleh alokasi batch tertua FIFO berdasarkan tanggal sangrai "
        "serta isolasi status virtual IN_TRANSIT ber-QR code untuk mencegah barang hilang di jalan. "
        "Pada terminal Web POS kasir gerai, kami merancang antarmuka sentuh ergonomis berstandar desain kriya tinggi Emil Kowalski: "
        "tombol minimal 48 piksel dengan aksi berfrekuensi tinggi tanpa lag (0 milidetik). "
        "Saat kasir menyelesaikan transaksi, mesin Auto-Deduct secara instan memotong bahan baku resep BOM multi-komponen—misalnya biji espresso, "
        "susu segar, dan paper cup—sekaligus mencatatnya ke kartu stok. "
        "Pada akhir hari kerja, penutupan shift kasir dikunci dengan rekonsiliasi kas blind count dan verifikasi PIN supervisor jika selisih kas melebihi sepuluh ribu rupiah."
    ))

    # ==========================================
    # SLIDE 12: TESTING - 5-TIER QUALITY FRAMEWORK
    # ==========================================
    s12 = prs.slides.add_slide(blank_layout)
    set_bg(s12, C_LIGHT_BG)
    add_header(s12, "Strategi Pengujian: 5-Tier Enterprise Quality Framework", "Dekonstruksi Piramida Pengujian Konvensional Menjadi Kerangka Kerja Mutu 5 Lapis", 12)

    # 5 Tiers Horizontal / Vertical Progression
    tiers_info = [
        ("Tier 1: Static Analysis", "Larastan / PHPStan Level 5", "Memindai 45 kelas sumber tanpa eksekusi, memvalidasi kompatibilitas tipe PHP 8.3, nullability, dan eliminasi pemanggilan env() di luar konfigurasi.", C_BLUE),
        ("Tier 2: Architecture Compliance", "Pest Architecture Testing", "Menegakkan 6 aturan desain enterprise: isolasi Model, Controller, Service Layer, dan eliminasi fungsi debugging (dd, dump, ray).", hex_to_rgb("#6366F1")),
        ("Tier 3: Structural White-Box", "McCabe Basis Path & CFG", "Memodelkan Graf Aliran Kendali (CFG), menghitung Kompleksitas Siklomatis V(G)=3, dan menguji seluruh jalur keputusan independen.", C_AMBER),
        ("Tier 4: Core Domain Feature", "Pest PHP v4 & PHPUnit 12", "155 kasus uji terintegrasi dan 696 asersi domain memvalidasi alur bisnis hulu-ke-hilir dalam isolasi transaksi basis data.", C_EMERALD),
        ("Tier 5: E2E Browser Automation", "Laravel Dusk & Chrome Headless", "Mengemudikan peramban Chrome sesungguhnya melalui WebDriver untuk memvalidasi interaksi UI, aset Vite, dan modal kasir.", C_DARK)
    ]

    for idx, (t_title, t_tool, t_desc, t_color) in enumerate(tiers_info):
        cy = 1.8 + idx * 1.0
        add_card(s12, 0.8, cy, 11.7, 0.88)
        # Left tag
        tag = s12.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, Inches(1.0), Inches(cy + 0.14), Inches(2.8), Inches(0.6))
        tag.fill.solid()
        tag.fill.fore_color.rgb = t_color
        tag.line.fill.background()
        p = tag.text_frame.paragraphs[0]
        p.text = t_title
        p.font.size = Pt(10)
        p.font.bold = True
        p.font.color.rgb = C_TEXT_LIGHT
        p.alignment = PP_ALIGN.CENTER
        tag.text_frame.vertical_anchor = MSO_ANCHOR.MIDDLE

        tb = s12.shapes.add_textbox(Inches(4.0), Inches(cy + 0.08), Inches(8.3), Inches(0.72))
        tf = tb.text_frame
        tf.word_wrap = True
        p = tf.paragraphs[0]
        p.text = f"{t_tool} — {t_desc}"
        p.font.size = Pt(9.5)
        p.font.color.rgb = C_TEXT_DARK

    set_speaker_notes(s12, (
        "Memasuki pilar kelima dan paling komprehensif, yaitu Pengujian Sistem (Testing). "
        "Sesuai arahan Bapak Dosen, pengujian sistem KAFEINA ERP tidak sekadar mengandalkan pengujian kotak hitam konvensional. "
        "Pengujian kotak hitam terbukti buta terhadap hukum kekekalan massa sangrai, pembukuan berpasangan net-zero, dan isolasi transaksi basis data. "
        "Oleh karena itu, kami mendekonstruksi piramida pengujian konvensional menjadi Arsitektur Pengujian Lima Lapis atau 5-Tier Enterprise Quality Framework: "
        "Mulai dari Tier 1 Analisis Statis Larastan Level 5, Tier 2 Penegakan Kepatuhan Arsitektur Pest, Tier 3 Pengujian Kotak Putih Basis Path McCabe, "
        "Tier 4 Pengujian Fitur Inti Domain Bisnis, hingga Tier 5 Otomasi Peramban Laravel Dusk pada Google Chrome riil."
    ))

    # ==========================================
    # SLIDE 13: TESTING - TIER 1 & TIER 2
    # ==========================================
    s13 = prs.slides.add_slide(blank_layout)
    set_bg(s13, C_LIGHT_BG)
    add_header(s13, "Pengujian Tier 1 & 2: Larastan Level 5 & Pest Architecture", "Pencapaian Zero Static Defect dan Penegakan 6 Aturan Desain Arsitektur Enterprise", 13)

    # Left: Larastan Level 5
    add_card(s13, 0.8, 1.8, 5.7, 4.9)
    tb = s13.shapes.add_textbox(Inches(1.0), Inches(2.0), Inches(5.3), Inches(4.5))
    tf = tb.text_frame
    tf.word_wrap = True
    p = tf.paragraphs[0]
    p.text = "Tier 1: Larastan / PHPStan Level 5 (0 Error)"
    p.font.size = Pt(13)
    p.font.bold = True
    p.font.color.rgb = C_BLUE
    larastan_items = [
        "• Objek Uji: 45 file kelas sumber PHP pada namespace App\\ (Controllers, Models, Services, Middleware, Enums).",
        "• Pencapaian Mutu: 0 Error (Zero Static Defects) pada aturan ketat Level 5.",
        "• Resolusi 16 Defek Laten Kritis:",
        "  1. Mengeliminasi pemanggilan env('MIDTRANS_SERVER_KEY') di MidtransQrisService dan menggantinya dengan config() guna menjamin sistem aman saat perintah php artisan config:cache dieksekusi di produksi.",
        "  2. Konversi tipe eksplisit pada str_pad() di 5 kontroler.",
        "  3. Penyelarasan casting Eloquent datetime pada atribut pin_locked_until di model User.",
        "  4. Guard clause pengecekan instansiasi objek Product pada alokasi FIFO."
    ]
    for li in larastan_items:
        pm = tf.add_paragraph()
        pm.text = li
        pm.font.size = Pt(9.5)
        pm.font.color.rgb = C_TEXT_DARK
        pm.space_before = Pt(4)

    # Right: Pest Architecture
    add_card(s13, 6.8, 1.8, 5.7, 4.9)
    tb = s13.shapes.add_textbox(Inches(7.0), Inches(2.0), Inches(5.3), Inches(4.5))
    tf = tb.text_frame
    tf.word_wrap = True
    p = tf.paragraphs[0]
    p.text = "Tier 2: Pest Architecture Testing (100% Compliant)"
    p.font.size = Pt(13)
    p.font.bold = True
    p.font.color.rgb = hex_to_rgb("#6366F1")
    arch_rules = [
        "Menegakkan 6 Aturan Desain Secara Otomatis:",
        "✓ 1. Zero Debugging Functions: Melarang pemanggilan dd, dump, ray, dan var_dump pada seluruh basis kode.",
        "✓ 2. Model Inheritance: Memastikan seluruh model berada di App\\Models dan mewarisi Illuminate\\Database\\Eloquent\\Model.",
        "✓ 3. Controller Standardization: Seluruh kontroler berada di App\\Http\\Controllers dan mewarisi Controller dasar.",
        "✓ 4. Service Layer Isolation: Logika bisnis wajib terenkapsulasi di dalam kelas konkret di App\\Services.",
        "✓ 5. Strict Enums: Seluruh enumerasi wajib merupakan Backed Enum PHP 8.1+.",
        "✓ 6. No Direct env(): Tidak ada pemanggilan env() langsung di dalam namespace App."
    ]
    for ar in arch_rules:
        pm = tf.add_paragraph()
        pm.text = ar
        pm.font.size = Pt(9.5)
        pm.font.color.rgb = C_TEXT_DARK
        pm.space_before = Pt(4)

    set_speaker_notes(s13, (
        "Pada Tier 1, kami mengonfigurasi Larastan atau PHPStan pada Level 5 yang sangat ketat untuk menganalisis 45 kelas PHP. "
        "Hasilnya luar biasa: kami berhasil meraih status Zero Static Defect atau 0 error. "
        "Larastan berhasil menemukan 16 defek laten sebelum kode sempat dijalankan, salah satu yang paling kritis adalah ditemukannya "
        "pemanggilan env() secara langsung di dalam layanan Midtrans. Pemanggilan ini jika dibiarkan akan mengembalikan nilai null ketika mode "
        "config:cache diaktifkan di server produksi, yang dapat melumpuhkan transaksi pembayaran. Kami menyelesaikannya secara tuntas. "
        "Pada Tier 2, kami menjalankan Pest Architecture Testing untuk memindai Abstract Syntax Tree kode. "
        "Enam aturan desain arsitektur enterprise terpenuhi 100 persen: bebas fungsi debugging kotor, pemisahan lapisan Controller-Service-Model "
        "yang disiplin, dan bebas kebocoran konfigurasi."
    ))

    # ==========================================
    # SLIDE 14: TESTING - TIER 3 & TIER 4
    # ==========================================
    s14 = prs.slides.add_slide(blank_layout)
    set_bg(s14, C_LIGHT_BG)
    add_header(s14, "Pengujian Tier 3 & 4: White-Box Basis Path & 155 Fitur", "Pembuktian 100% Branch Coverage dengan McCabe V(G)=3 dan 696 Asersi Domain", 14)

    # Left: Whitebox Basis Path McCabe
    add_card(s14, 0.8, 1.8, 5.7, 4.9)
    tb = s14.shapes.add_textbox(Inches(1.0), Inches(2.0), Inches(5.3), Inches(4.5))
    tf = tb.text_frame
    tf.word_wrap = True
    p = tf.paragraphs[0]
    p.text = "Tier 3: White-Box Basis Path & McCabe CFG"
    p.font.size = Pt(13)
    p.font.bold = True
    p.font.color.rgb = C_AMBER
    mccabe_points = [
        "Pemodelan Graf Aliran Kendali (CFG) Modul QC Lab:",
        "• Parameter Graf: Simpul (N) = 7, Sisi (E) = 8, Komponen (P) = 1, Simpul Predikat (Pd) = 2.",
        "• Kompleksitas Siklomatis McCabe:\n  V(G) = E - N + 2P = 8 - 7 + 2(1) = 3\n  V(G) = Pd + 1 = 2 + 1 = 3\n  V(G) = R = 3 Wilayah Tertutup.",
        "• 3 Jalur Independen (Basis Paths) Teruji 100%:\n  - Jalur 1: Simpul 1->2->3->7 (SCA < 80 -> REJECTED)\n  - Jalur 2: Simpul 1->2->4->5->7 (SCA >= 80, air marjinal -> CONDITIONAL)\n  - Jalur 3: Simpul 1->2->4->6->7 (SCA >= 80, air ideal 10-12% -> ACCEPTED Specialty Grade 1).",
        "• Hasil: 100% Branch Coverage tercapai penuh pada 8 jalur bisnis kritis."
    ]
    for mp in mccabe_points:
        pm = tf.add_paragraph()
        pm.text = mp
        pm.font.size = Pt(9.5)
        pm.font.color.rgb = C_TEXT_DARK
        pm.space_before = Pt(4)

    # Right: Core Feature Suite
    add_card(s14, 6.8, 1.8, 5.7, 4.9)
    tb = s14.shapes.add_textbox(Inches(7.0), Inches(2.0), Inches(5.3), Inches(4.5))
    tf = tb.text_frame
    tf.word_wrap = True
    p = tf.paragraphs[0]
    p.text = "Tier 4: Core Domain Feature Suite (155 Tests)"
    p.font.size = Pt(13)
    p.font.bold = True
    p.font.color.rgb = C_EMERALD
    feature_points = [
        "Rangkaian 155 Kasus Uji Otomatis & 696 Asersi:",
        "1. ArchitectureTest: 6 pengujian (7 asersi) [PASS]",
        "2. ProcurementWorkflowTest: 18 pengujian (74 asersi) [PASS]",
        "3. QualityControlWorkflowTest: 22 pengujian (98 asersi) [PASS]",
        "4. RoasteryProductionTest: 24 pengujian (112 asersi) [PASS]",
        "5. InventoryTransferTest: 20 pengujian (86 asersi) [PASS]",
        "6. PosCheckoutRecipeDeductTest: 28 pengujian (134 asersi) [PASS]",
        "7. AtomicTransactionRollbackTest: 12 pengujian (58 asersi) [PASS]",
        "8. SafetyStockAlertTest: 14 pengujian (62 asersi) [PASS]",
        "9. RbacAuthorizationGuardTest: 11 pengujian (45 asersi) [PASS]",
        "Durasi Total Eksekusi: 36.77 detik dengan DatabaseTransactions."
    ]
    for fp in feature_points:
        pm = tf.add_paragraph()
        pm.text = fp
        pm.font.size = Pt(9.5)
        pm.font.color.rgb = C_TEXT_DARK
        pm.space_before = Pt(3)

    set_speaker_notes(s14, (
        "Pada Tier 3, kami menerapkan teknik pengujian kotak putih murni melalui pemodelan Control Flow Graph (CFG) dan perhitungan "
        "Kompleksitas Siklomatis McCabe pada modul evaluasi mutu laboratorium kopi. "
        "Dengan 7 simpul, 8 sisi, dan 2 simpul predikat, nilai kompleksitas siklomatis V(G) diperoleh tepat bernilai 3. "
        "Ketiga jalur independen—mulai dari lot ditolak, bersyarat, hingga lot yang diterima berpredikat Specialty Grade 1—berhasil dieksekusi "
        "secara tuntas sehingga mencapai 100 persen branch coverage. "
        "Melanjutkan ke Tier 4, seluruh logika rantai pasok diuji secara komprehensif melalui 155 kasus uji fitur otomatis yang memvalidasi "
        "696 asersi domain bisnis. Seluruh pengujian diselesaikan dalam waktu 36.7 detik dengan tingkat kelulusan sempurna 100 persen tanpa satu pun kegagalan."
    ))

    # ==========================================
    # SLIDE 15: TESTING - TIER 5 & TOTAL REKAPITULASI
    # ==========================================
    s15 = prs.slides.add_slide(blank_layout)
    set_bg(s15, C_LIGHT_BG)
    add_header(s15, "Pengujian Tier 5 & Rekapitulasi Empiris", "Verifikasi Antarmuka Browser Dusk dan Rekapitulasi Metrik Mutu Sempurna 100%", 15)

    # Top: Dusk Browser Testing
    add_card(s15, 0.8, 1.8, 11.7, 1.9)
    tb = s15.shapes.add_textbox(Inches(1.0), Inches(1.95), Inches(11.3), Inches(1.6))
    tf = tb.text_frame
    tf.word_wrap = True
    p = tf.paragraphs[0]
    p.text = "Tier 5: Otomasi Peramban Riil Laravel Dusk (Headless Chrome 157)"
    p.font.size = Pt(12.5)
    p.font.bold = True
    p.font.color.rgb = C_DARK
    dusk_points = [
        "• Eksekusi Nyata di Browser: Mengemudikan ChromeDriver aktual untuk merender aset Vite, CSS Tailwind v4, dan skrip Alpine.js.",
        "• Skenario Teruji: Login pengguna autentikasi, navigasi dasbor analitik cabang, interaksi tombol POS sentuh, dan pembukaan modal rekonsiliasi kasir.",
        "• Bukti Visual Otentik: Menghasilkan 4 tangkapan layar audit beresolusi tinggi (01_login_page.png, 02_dashboard_overview.png, 04_pos_touch_terminal.png, 05_pos_shift_modal.png)."
    ]
    for dp in dusk_points:
        pm = tf.add_paragraph()
        pm.text = dp
        pm.font.size = Pt(9.5)
        pm.font.color.rgb = C_TEXT_DARK
        pm.space_before = Pt(3)

    # Bottom: Summary Table
    rows = 6
    cols = 6
    table_shape = s15.shapes.add_table(rows, cols, Inches(0.8), Inches(3.9), Inches(11.7), Inches(2.9))
    table = table_shape.table
    table.columns[0].width = Inches(2.3)
    table.columns[1].width = Inches(2.2)
    table.columns[2].width = Inches(2.2)
    table.columns[3].width = Inches(1.8)
    table.columns[4].width = Inches(1.6)
    table.columns[5].width = Inches(1.6)

    h_summary = ["Tingkatan Pengujian", "Engine / Framework", "Cakupan Objek Uji", "Kasus Uji / Aturan", "Durasi", "Hasil Kelulusan"]
    for c_idx, h in enumerate(h_summary):
        cell = table.cell(0, c_idx)
        cell.fill.solid()
        cell.fill.fore_color.rgb = C_DARK
        p = cell.text_frame.paragraphs[0]
        p.text = h
        p.font.size = Pt(9)
        p.font.bold = True
        p.font.color.rgb = C_TEXT_LIGHT
        p.alignment = PP_ALIGN.CENTER

    summary_data = [
        ("Tier 1: Static Analysis", "Larastan / PHPStan 2.3.7", "45 File Sumber (app/)", "Level 5 Rules", "8.4 detik", "LULUS (0 Error)"),
        ("Tier 2: Architecture Guard", "Pest Architecture Engine", "Struktur & Integritas Lapisan", "6 Uji (7 Asersi)", "3.9 detik", "LULUS (100%)"),
        ("Tier 3 & 4: Core Domain", "Pest PHP v4 & PHPUnit 12", "8 Jalur Kritis Rantai Pasok", "155 Uji (696 Asersi)", "36.7 detik", "LULUS (100%)"),
        ("Tier 5: E2E Browser Testing", "Laravel Dusk & Chrome 157", "Antarmuka Login, Dasbor, POS", "3 Skenario (7 Asersi)", "19.1 detik", "LULUS (100%)"),
        ("TOTAL KESELURUHAN", "5-Tier Testing Framework", "Sistem KAFEINA ERP Terpadu", "164 Uji Aktif", "68.1 detik", "PERFECT (100%)")
    ]
    for r_idx, row in enumerate(summary_data, start=1):
        is_total = (r_idx == 5)
        for c_idx, val in enumerate(row):
            cell = table.cell(r_idx, c_idx)
            cell.fill.solid()
            cell.fill.fore_color.rgb = hex_to_rgb("#FEF3C7") if is_total else (C_CARD_LIGHT if r_idx % 2 == 1 else hex_to_rgb("#F8FAFC"))
            p = cell.text_frame.paragraphs[0]
            p.text = val
            p.font.size = Pt(9 if not is_total else 9.5)
            p.font.bold = is_total or (c_idx in (0, 5))
            p.font.color.rgb = C_EMERALD if c_idx == 5 else C_TEXT_DARK
            if c_idx in (3, 4, 5):
                p.alignment = PP_ALIGN.CENTER

    set_speaker_notes(s15, (
        "Pada tingkat pengujian teratas yaitu Tier 5, kami menjalankan pengujian peramban otomatis menggunakan Laravel Dusk pada Google Chrome headless. "
        "Berbeda dengan pengujian HTTP simulasi, Dusk mengeksekusi aset terkompilasi Vite, styling Tailwind CSS v4, dan interaktivitas Alpine.js secara nyata, "
        "membuktikan kelancaran alur login, navigasi dasbor, hingga pembukaan modal rekonsiliasi kas kasir. "
        "Secara keseluruhan, rangkaian 5-Tier Enterprise Quality Framework kami mencatatkan rekor kelulusan sempurna: "
        "164 pengujian aktif, lebih dari 710 asersi yang mencakup seluruh lapisan kode, dieksekusi dalam total durasi 68.1 detik dengan 100 persen Pass Rate. "
        "Ini adalah bukti empiris bahwa KAFEINA ERP telah siap pakai dan memenuhi standar mutu enterprise yang sangat andal."
    ))

    # ==========================================
    # SLIDE 16: KESIMPULAN & Q&A
    # ==========================================
    s16 = prs.slides.add_slide(blank_layout)
    set_bg(s16, C_DARK)

    # Top Pill
    pill = s16.shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, Inches(0.9), Inches(0.8), Inches(3.8), Inches(0.38))
    pill.fill.solid()
    pill.fill.fore_color.rgb = C_CARD_DARK
    pill.line.color.rgb = C_EMERALD
    p = pill.text_frame.paragraphs[0]
    p.text = "KESIMPULAN AKADEMIK & TANYA JAWAB"
    p.font.size = Pt(9.5)
    p.font.bold = True
    p.font.color.rgb = C_EMERALD
    p.alignment = PP_ALIGN.CENTER
    pill.text_frame.vertical_anchor = MSO_ANCHOR.MIDDLE

    # Main Title
    tb = s16.shapes.add_textbox(Inches(0.9), Inches(1.35), Inches(11.5), Inches(1.2))
    tf = tb.text_frame
    tf.word_wrap = True
    p = tf.paragraphs[0]
    p.text = "KAFEINA ERP: Fondasi Rantai Pasok Kopi Spesialti Terpadu dengan Keandalan Teruji Standar Enterprise"
    p.font.size = Pt(24)
    p.font.bold = True
    p.font.color.rgb = C_TEXT_LIGHT
    p.font.name = FONT_TITLE

    # 3 Summary Cards
    cards = [
        ("1. Integrasi Hulu-ke-Hilir", "Menghubungkan pengadaan petani, laboratorium QC, roastery, gudang multi-cabang, dan kasir POS dalam satu ekosistem tanpa fragmentasi data.", C_BLUE),
        ("2. Presisi Akuntansi & Fisik", "Mengotomatisasi susut bobot sangrai (yield loss 14-18%), rekalkulasi HPP riil fraksional per gram, dan pemotongan bahan BOM secara pesimistik.", C_AMBER),
        ("3. Jaminan Mutu 5-Tier", "Keterlacakan penuh dari URS ke pengujian melalui RTM, 0 error Larastan Level 5, 100% Branch Coverage, dan kelulusan 164 pengujian otomatis.", C_EMERALD)
    ]
    for idx, (c_t, c_d, c_col) in enumerate(cards):
        cx = 0.9 + idx * 3.85
        add_card(s16, cx, 2.7, 3.65, 2.5, bg_color=C_CARD_DARK, border_color=C_BORDER_DARK)
        tb = s16.shapes.add_textbox(Inches(cx + 0.15), Inches(2.85), Inches(3.35), Inches(2.2))
        tf = tb.text_frame
        tf.word_wrap = True
        p = tf.paragraphs[0]
        p.text = c_t
        p.font.size = Pt(12)
        p.font.bold = True
        p.font.color.rgb = c_col
        p2 = tf.add_paragraph()
        p2.text = c_d
        p2.font.size = Pt(9.5)
        p2.font.color.rgb = C_TEXT_LIGHT
        p2.space_before = Pt(6)

    # Bottom QA Banner
    qa_card = add_card(s16, 0.9, 5.4, 11.5, 1.5, bg_color=hex_to_rgb("#1E293B"), border_color=C_BORDER_DARK)
    tb_qa = s16.shapes.add_textbox(Inches(1.1), Inches(5.5), Inches(11.1), Inches(1.3))
    tf_qa = tb_qa.text_frame
    tf_qa.word_wrap = True
    p = tf_qa.paragraphs[0]
    p.text = "TERIMA KASIH ATAS PERHATIAN BAPAK DOSEN & REKAN-REKAN SEKALIAN"
    p.font.size = Pt(14)
    p.font.bold = True
    p.font.color.rgb = C_AMBER
    p.alignment = PP_ALIGN.CENTER
    p2 = tf_qa.add_paragraph()
    p2.text = "Sesi Tanya Jawab (Q&A) Dibuka: Kami siap mendiskusikan aspek Rekayasa Kebutuhan, Desain Arsitektur, Implementasi Kode, maupun Metodologi Pengujian Sistem."
    p2.font.size = Pt(10.5)
    p2.font.color.rgb = C_TEXT_LIGHT
    p2.alignment = PP_ALIGN.CENTER
    p2.space_before = Pt(6)

    set_speaker_notes(s16, (
        "Sebagai kesimpulan, KAFEINA ERP membuktikan bahwa integrasi komprehensif antara rekayasa kebutuhan (USR), spesifikasi formal (SRS), "
        "desain perangkat lunak bersih (SDD), implementasi tangguh berorientasi layanan, serta pengujian terpadu 5-tier mampu menjawab tuntas "
        "tiga tantangan besar industri kopi spesialti: asimetri mutasi stok, ketiadaan HPP riil sangrai, dan inefisiensi logistik. "
        "Dengan pencapaian 100 persen test pass rate dan zero static defect, sistem ini siap memberikan dampak nyata bagi tata kelola bisnis ritel modern. "
        "Demikian pemaparan dari Kelompok 1. Kami membuka sesi tanya jawab dan sangat mengapresiasi masukan dari Bapak Ir. Muhammad Ihsan Fawzi, M.Kom. "
        "Terima kasih. Wassalamu'alaikum Warahmatullahi Wabarakatuh."
    ))

    # Save presentation
    output_dir = Path(r"d:\Kafeina\docs")
    output_dir.mkdir(parents=True, exist_ok=True)
    output_path = output_dir / "Presentasi_Kafeina_ERP_Kelompok_1.pptx"
    prs.save(str(output_path))
    print(f"Presentation saved successfully to: {output_path}")

if __name__ == "__main__":
    create_presentation()
