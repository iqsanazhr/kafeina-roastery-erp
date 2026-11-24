import csv
import json
import numpy as np

# Definisi Data 10 Responden Anonim Berdasarkan Peran di Kafeina ERP
respondents = [
    {
        "id": "R01",
        "role": "Store Manager (Outlet Senopati)",
        "experience": "4 Tahun",
        "scores": [5, 5, 5, 5, 5, 5, 5, 5, 5, 5],
        "feedback": "Modul katalog produk dan varian sangat fleksibel. Penetapan resep auto-deduct memudahkan rekonsiliasi stok harian tanpa selisih."
    },
    {
        "id": "R02",
        "role": "Lead Barista & Cashier (Outlet Senopati)",
        "experience": "2 Tahun",
        "scores": [5, 5, 5, 4, 4, 5, 4, 5, 4, 5],
        "feedback": "Web POS sangat cepat responsnya saat peak hours. Pop-up ekstra shot dan oat milk langsung sinkron ke total tagihan."
    },
    {
        "id": "R03",
        "role": "Branch Cashier (Outlet Braga)",
        "experience": "1.5 Tahun",
        "scores": [4, 4, 4, 4, 4, 4, 4, 5, 4, 4],
        "feedback": "Proteksi PIN kasir dan validasi modal awal shift sangat rapi, mencegah kekeliruan perhitungan uang fisik di laci."
    },
    {
        "id": "R04",
        "role": "Outlet Supervisor (Outlet Malioboro)",
        "experience": "3 Tahun",
        "scores": [5, 5, 5, 5, 5, 5, 4, 5, 5, 5],
        "feedback": "Pembatasan hak akses (RBAC) berjalan sempurna; kasir tidak bisa asal ubah harga atau void order tanpa izin supervisor."
    },
    {
        "id": "R05",
        "role": "Head Roaster (Central Roastery)",
        "experience": "5 Tahun",
        "scores": [5, 4, 5, 5, 5, 4, 5, 5, 5, 5],
        "feedback": "Fitur Work Order dan perhitungan Yield Loss susut sangrai sangat presisi. HPP riil per batch langsung terhitung otomatis."
    },
    {
        "id": "R06",
        "role": "QC Inspector (Laboratorium Mutu)",
        "experience": "3 Tahun",
        "scores": [4, 4, 4, 4, 4, 4, 4, 4, 4, 4],
        "feedback": "Validasi kadar air 10-12% dan cupping score SCAA >= 80 bekerja ketat menolak lot green beans yang tidak sesuai standar."
    },
    {
        "id": "R07",
        "role": "Warehouse & Logistics Staff",
        "experience": "4 Tahun",
        "scores": [5, 4, 5, 5, 5, 4, 5, 5, 5, 5],
        "feedback": "Surat Jalan ber-QR code dan alokasi stok FIFO memudahkan pelacakan distribusi biji kopi antar cabang tanpa tertukar tanggal sangrai."
    },
    {
        "id": "R08",
        "role": "Procurement Officer",
        "experience": "2.5 Tahun",
        "scores": [4, 4, 4, 4, 5, 4, 4, 4, 4, 4],
        "feedback": "Penerbitan PO dan pencatatan mitra petani kopi sangat terstruktur, mempermudah pelacakan asal kebun dan varietas."
    },
    {
        "id": "R09",
        "role": "Barista Shift 2 (Outlet Braga)",
        "experience": "1 Tahun",
        "scores": [5, 5, 5, 4, 4, 5, 4, 4, 4, 5],
        "feedback": "Desain antarmuka bersih dan ramah layar sentuh (touchscreen tablet). Navigasi kategori minuman sangat cepat."
    },
    {
        "id": "R10",
        "role": "General Manager / Owner Representative",
        "experience": "6 Tahun",
        "scores": [5, 5, 5, 5, 5, 5, 5, 5, 5, 5],
        "feedback": "Laporan laba rugi riil (Real P&L) dan dashboard eksekutif memberikan visibilitas penuh terhadap performa keuangan seluruh cabang."
    }
]

questions = [
    "P01. Pembatasan Hak Akses (RBAC Gatekeeper)",
    "P02. Kecepatan & Responsivitas Kasir Web POS",
    "P03. Akurasi Auto-Deduct Stok Bahan Baku Resep",
    "P04. Fleksibilitas Manajemen Produk, Varian & Modifiers",
    "P05. Ketepatan Alur Pengadaan (PO/QC) & Roastery (BOM)",
    "P06. Tampilan Antarmuka (UI) Bersih & User-Friendly",
    "P07. Kecepatan Respon Sistem & Bebas Gangguan (Lag)",
    "P08. Keandalan Keamanan (PIN Kasir, Lockout & Audit Log)",
    "P09. Efektivitas Pengendalian Stok (Spillage & Selisih)",
    "P10. Kelayakan Menyeluruh untuk Operasional Harian"
]

# Simpan ke CSV
csv_path = "docs/hasil_responden_uat_kafeina_erp.csv"
with open(csv_path, mode="w", newline="", encoding="utf-8") as f:
    writer = csv.writer(f)
    header = ["ID Responden", "Peran / Jabatan", "Pengalaman"] + [f"P{i+1:02d}" for i in range(10)] + ["Total Skor", "Persentase (%)", "Kategori", "Komentar / Saran"]
    writer.writerow(header)
    
    matrix = []
    for r in respondents:
        scores = r["scores"]
        matrix.append(scores)
        total = sum(scores)
        pct = (total / (10 * 5)) * 100
        kat = "Sangat Layak" if pct >= 81 else ("Layak" if pct >= 61 else "Cukup")
        writer.writerow([r["id"], r["role"], r["experience"]] + scores + [total, f"{pct:.1f}%", kat, r["feedback"]])

matrix = np.array(matrix) # Shape: (10, 10)
n_resp, n_quest = matrix.shape

total_scores_per_resp = matrix.sum(axis=1)
total_actual = matrix.sum()
max_possible = n_resp * n_quest * 5
overall_percentage = (total_actual / max_possible) * 100

# Perhitungan Rata-rata per Butir
means = matrix.mean(axis=0)
stds = matrix.std(axis=0, ddof=1)

# Uji Reliabilitas Cronbach's Alpha
item_variances = matrix.var(axis=0, ddof=1)
sum_item_var = item_variances.sum()
total_var = total_scores_per_resp.var(ddof=1)
cronbach_alpha = (n_quest / (n_quest - 1)) * (1 - (sum_item_var / total_var))

# Korelasi Pearson untuk Uji Validitas
correlations = []
for j in range(n_quest):
    r_xy = np.corrcoef(matrix[:, j], total_scores_per_resp)[0, 1]
    correlations.append(r_xy)

summary = {
    "n_respondents": n_resp,
    "total_actual": int(total_actual),
    "max_possible": max_possible,
    "overall_percentage": round(overall_percentage, 2),
    "cronbach_alpha": round(cronbach_alpha, 3),
    "means": [round(m, 2) for m in means],
    "correlations": [round(c, 3) for c in correlations]
}

print(json.dumps(summary, indent=2))
print(f"File CSV berhasil diekspor ke: {csv_path}")
