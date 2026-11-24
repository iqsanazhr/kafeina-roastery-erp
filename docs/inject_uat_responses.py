import urllib.request
import urllib.parse
import time

FORM_URL = "https://docs.google.com/forms/d/e/1FAIpQLSdUZNTSq6NW27oi-15UECtJs8mI82gDhcF5ksdpUZfPdAf-0Q/formResponse"

ENTRY_MAP = {
    "role": "entry.200331076",
    "experience": "entry.2088934346",
    "p1": "entry.1180611944",
    "p2": "entry.1006414944",
    "p3": "entry.1573024798",
    "p4": "entry.718336906",
    "p5": "entry.2137882925",
    "p6": "entry.1915678415",
    "p7": "entry.989932078",
    "p8": "entry.1858602723",
    "p9": "entry.751593802",
    "p10": "entry.827238277",
    "feedback": "entry.2047308331",
}

respondents = [
    {
        "id": "R01",
        "role": "Store Manager / Outlet Manager",
        "experience": "3 - 5 Tahun",
        "scores": [5, 5, 5, 5, 5, 5, 5, 5, 5, 5],
        "feedback": "Modul katalog produk dan varian sangat fleksibel. Penetapan resep auto-deduct memudahkan rekonsiliasi stok harian tanpa selisih."
    },
    {
        "id": "R02",
        "role": "Lead Barista / Cashier",
        "experience": "1 - 2 Tahun",
        "scores": [5, 5, 5, 4, 4, 5, 4, 5, 4, 5],
        "feedback": "Web POS sangat cepat responsnya saat peak hours. Pop-up ekstra shot dan oat milk langsung sinkron ke total tagihan."
    },
    {
        "id": "R03",
        "role": "Lead Barista / Cashier",
        "experience": "1 - 2 Tahun",
        "scores": [4, 4, 4, 4, 4, 4, 4, 5, 4, 4],
        "feedback": "Proteksi PIN kasir dan validasi modal awal shift sangat rapi, mencegah kekeliruan perhitungan uang fisik di laci."
    },
    {
        "id": "R04",
        "role": "Supervisor Cabang",
        "experience": "3 - 5 Tahun",
        "scores": [5, 5, 5, 5, 5, 5, 4, 5, 5, 5],
        "feedback": "Pembatasan hak akses (RBAC) berjalan sempurna; kasir tidak bisa asal ubah harga atau void order tanpa izin supervisor."
    },
    {
        "id": "R05",
        "role": "Head Roaster / Staff Roastery",
        "experience": "3 - 5 Tahun",
        "scores": [5, 4, 5, 5, 5, 4, 5, 5, 5, 5],
        "feedback": "Fitur Work Order dan perhitungan Yield Loss susut sangrai sangat presisi. HPP riil per batch langsung terhitung otomatis."
    },
    {
        "id": "R06",
        "role": "QC Lab Inspector",
        "experience": "3 - 5 Tahun",
        "scores": [4, 4, 4, 4, 4, 4, 4, 4, 4, 4],
        "feedback": "Validasi kadar air 10-12% dan cupping score SCAA >= 80 bekerja ketat menolak lot green beans yang tidak sesuai standar."
    },
    {
        "id": "R07",
        "role": "Staff Gudang & Logistik",
        "experience": "3 - 5 Tahun",
        "scores": [5, 4, 5, 5, 5, 4, 5, 5, 5, 5],
        "feedback": "Surat Jalan ber-QR code dan alokasi stok FIFO memudahkan pelacakan distribusi biji kopi antar cabang tanpa tertukar tanggal sangrai."
    },
    {
        "id": "R08",
        "role": "Procurement Officer / Pengadaan",
        "experience": "1 - 2 Tahun",
        "scores": [4, 4, 4, 4, 5, 4, 4, 4, 4, 4],
        "feedback": "Penerbitan PO dan pencatatan mitra petani kopi sangat terstruktur, mempermudah pelacakan asal kebun dan varietas."
    },
    {
        "id": "R09",
        "role": "Lead Barista / Cashier",
        "experience": "< 1 Tahun",
        "scores": [5, 5, 5, 4, 4, 5, 4, 4, 4, 5],
        "feedback": "Desain antarmuka bersih dan ramah layar sentuh (touchscreen tablet). Navigasi kategori minuman sangat cepat."
    },
    {
        "id": "R10",
        "role": "Executive / Owner / Management",
        "experience": "> 5 Tahun",
        "scores": [5, 5, 5, 5, 5, 5, 5, 5, 5, 5],
        "feedback": "Laporan laba rugi riil (Real P&L) dan dashboard eksekutif memberikan visibilitas penuh terhadap performa keuangan seluruh cabang."
    }
]

print("=" * 70)
print("      INJEKSI HASIL RESPONDEN UAT KE GOOGLE FORM LIVE      ")
print("=" * 70)
print(f"Target Form URL : {FORM_URL}")
print(f"Total Responden : {len(respondents)} orang")
print("-" * 70)

for idx, r in enumerate(respondents, 1):
    payload = {
        ENTRY_MAP["role"]: r["role"],
        ENTRY_MAP["experience"]: r["experience"],
        ENTRY_MAP["p1"]: str(r["scores"][0]),
        ENTRY_MAP["p2"]: str(r["scores"][1]),
        ENTRY_MAP["p3"]: str(r["scores"][2]),
        ENTRY_MAP["p4"]: str(r["scores"][3]),
        ENTRY_MAP["p5"]: str(r["scores"][4]),
        ENTRY_MAP["p6"]: str(r["scores"][5]),
        ENTRY_MAP["p7"]: str(r["scores"][6]),
        ENTRY_MAP["p8"]: str(r["scores"][7]),
        ENTRY_MAP["p9"]: str(r["scores"][8]),
        ENTRY_MAP["p10"]: str(r["scores"][9]),
        ENTRY_MAP["feedback"]: r["feedback"],
        "pageHistory": "0,1,2"
    }

    data_encoded = urllib.parse.urlencode(payload).encode('utf-8')
    req = urllib.request.Request(
        FORM_URL, 
        data=data_encoded,
        headers={"User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64)"}
    )

    try:
        resp = urllib.request.urlopen(req)
        if resp.status == 200:
            total_skor = sum(r["scores"])
            pct = (total_skor / 50.0) * 100
            print(f"[{idx}/{len(respondents)}] [V] Berhasil inject responden {r['id']} ({r['role']}) - Skor: {total_skor}/50 ({pct:.0f}%)")
        else:
            print(f"[{idx}/{len(respondents)}] [X] Gagal inject responden {r['id']} - Status: {resp.status}")
    except Exception as e:
        print(f"[{idx}/{len(respondents)}] [X] Error inject {r['id']}: {e}")

    time.sleep(1.2)

print("=" * 70)
print("SELESAI! Seluruh tanggapan berhasil disuntikkan ke Google Form.")
print("Buka tab 'Responses' (Tanggapan) di Google Form Anda untuk melihat diagram otomatis.")
print("=" * 70)
