/**
 * ==============================================================================
 * KAFEINA ERP - GOOGLE APPS SCRIPT FORM GENERATOR (1-CLICK UAT BUILDER)
 * ==============================================================================
 * Petunjuk Penggunaan:
 * 1. Buka https://script.google.com/home di browser Anda.
 * 2. Klik tombol "New project" (+ Proyek Baru).
 * 3. Hapus kode default (myFunction), lalu paste seluruh kode di bawah ini.
 * 4. Klik tombol "Run" (Jalankan). Berikan izin otorisasi Google jika diminta.
 * 5. Buka tab Log Eksekusi: Tautan Google Form dan Link Pengisian akan otomatis muncul!
 * ==============================================================================
 */

function createKafeinaUatForm() {
  var formTitle = "UAT (User Acceptance Testing) - KAFEINA ERP Multi-Cabang";
  var formDescription = 
    "Kuesioner Pengujian Penerimaan Pengguna (User Acceptance Testing)\n" +
    "Sistem Terintegrasi Roastery Kopi Spesialti dan Manajemen Multi-Cabang (KAFEINA ERP)\n\n" +
    "Kerahasiaan Data: Identitas responden bersifat ANONIM dan data ini murni digunakan untuk evaluasi akademik/teknis kelayakan operasional sistem.\n\n" +
    "Petunjuk Skala Penilaian:\n" +
    "1 = Sangat Tidak Setuju (STS)\n" +
    "2 = Tidak Setuju (TS)\n" +
    "3 = Ragu-Ragu / Cukup (RR)\n" +
    "4 = Setuju (S)\n" +
    "5 = Sangat Setuju (SS)";

  var form = FormApp.create(formTitle);
  form.setDescription(formDescription);
  form.setAllowResponseEdits(false);
  form.setCollectEmail(false); // Anonim

  // --------------------------------------------------------------------------
  // BAGIAN 1: PROFIL RESPONDEN (ANONIM)
  // --------------------------------------------------------------------------
  form.addSectionHeaderItem()
      .setTitle("Bagian 1: Profil Karakteristik Responden")
      .setHelpText("Informasi posisi peran operasional Anda dalam ekosistem KAFEINA ERP.");

  var roleItem = form.addMultipleChoiceItem();
  roleItem.setTitle("Peran / Jabatan Operasional yang Anda Uji:")
          .setRequired(true)
          .setChoiceValues([
            "Store Manager / Outlet Manager",
            "Supervisor Cabang",
            "Lead Barista / Cashier",
            "Head Roaster / Staff Roastery",
            "Staff Gudang & Logistik",
            "Procurement Officer / Pengadaan",
            "QC Lab Inspector",
            "Executive / Owner / Management"
          ]);

  var expItem = form.addMultipleChoiceItem();
  expItem.setTitle("Pengalaman Bekerja di Industri Kafe / F&B:")
         .setRequired(true)
         .setChoiceValues([
           "< 1 Tahun",
           "1 - 2 Tahun",
           "3 - 5 Tahun",
           "> 5 Tahun"
         ]);

  // --------------------------------------------------------------------------
  // BAGIAN 2: EVALUASI ASPEK FUNGSIONALITAS SISTEM (FUNCTIONALITY & RBAC)
  // --------------------------------------------------------------------------
  form.addPageBreakItem()
      .setTitle("Bagian 2: Evaluasi Aspek Fungsionalitas & Logika Bisnis")
      .setHelpText("Berikan penilaian Anda terhadap keandalan fitur-fitur KAFEINA ERP.");

  var questionsDim1 = [
    {
      title: "1. Pembatasan Hak Akses (RBAC Gatekeeper)",
      help: "Sistem berhasil membatasi hak akses menu dan tindakan sesuai wewenang peran (misal: kasir tidak dapat mengubah harga jual atau resep)."
    },
    {
      title: "2. Kecepatan & Responsivitas Kasir Web POS",
      help: "Tablet Kasir Web POS merespons sentuhan cepat, mendukung pembayaran tunai/QRIS, dan perhitungan kembalian akurat."
    },
    {
      title: "3. Akurasi Auto-Deduct Stok Bahan Baku Resep",
      help: "Sistem secara otomatis memotong stok bahan baku bar (biji kopi sangrai, susu cair, cup/lid) sesuai formula resep saat checkout."
    },
    {
      title: "4. Fleksibilitas Manajemen Produk, Varian & Modifiers",
      help: "Konfigurasi varian ukuran cup (8oz/12oz), kemasan retail, dan add-ons (Oat Milk/Extra Shot) mudah diatur oleh manajer."
    },
    {
      title: "5. Ketepatan Alur Pengadaan (PO/QC) & Roastery (BOM)",
      help: "Penerbitan PO, validasi mutu QC biji mentah, serta kalkulasi susut sangrai (Yield Loss) dan HPP riil batch berjalan presisi."
    }
  ];

  questionsDim1.forEach(function(q) {
    var item = form.addScaleItem();
    item.setTitle(q.title)
        .setHelpText(q.help)
        .setBounds(1, 5)
        .setLabels("Sangat Tidak Setuju", "Sangat Setuju")
        .setRequired(true);
  });

  // --------------------------------------------------------------------------
  // BAGIAN 3: EVALUASI ASPEK KEGUNAAN, ANTARMUKA & KELAYAKAN (USABILITY & RELIABILITY)
  // --------------------------------------------------------------------------
  form.addPageBreakItem()
      .setTitle("Bagian 3: Evaluasi Aspek Kegunaan, Antarmuka & Kelayakan Sistem")
      .setHelpText("Penilaian mengenai pengalaman pengguna, stabilitas sistem, dan kesiapan operasional.");

  var questionsDim2 = [
    {
      title: "6. Desain Antarmuka (UI) Bersih & User-Friendly",
      help: "Tampilan sistem modern, profesional, kontras warna jelas, serta navigasi menu mudah dipelajari oleh staf baru."
    },
    {
      title: "7. Kecepatan Respon Sistem & Bebas Gangguan (Lag)",
      help: "Perpindahan halaman, filter pencarian instan, dan proses simpan data berjalan mulus tanpa hambatan."
    },
    {
      title: "8. Keandalan Keamanan (PIN Kasir, Lockout & Audit Log)",
      help: "Otorisasi PIN 6-digit kasir, proteksi lockout 5-strike, dan pencatatan riwayat transaksi memberi rasa aman pada data."
    },
    {
      title: "9. Efektivitas Pengendalian Stok (Spillage & Selisih)",
      help: "Penerapan sistem KAFEINA ERP efektif mengurangi kesalahan pencatatan manual dan meminimalisir kebocoran bahan baku."
    },
    {
      title: "10. Kelayakan Menyeluruh untuk Operasional Harian",
      help: "Secara keseluruhan, sistem KAFEINA ERP dinilai layak, stabil, dan siap diterapkan untuk operasional harian kafe dan roastery."
    }
  ];

  questionsDim2.forEach(function(q) {
    var item = form.addScaleItem();
    item.setTitle(q.title)
        .setHelpText(q.help)
        .setBounds(1, 5)
        .setLabels("Sangat Tidak Setuju", "Sangat Setuju")
        .setRequired(true);
  });

  // --------------------------------------------------------------------------
  // BAGIAN 4: SARAN & MASUKAN KUALITATIF
  // --------------------------------------------------------------------------
  var feedbackItem = form.addParagraphTextItem();
  feedbackItem.setTitle("Kritik, Saran, atau Masukan untuk Penyempurnaan Sistem KAFEINA ERP:")
              .setHelpText("Tuliskan pandangan atau catatan operasional Anda (opsional).")
              .setRequired(false);

  Logger.log("==================================================================");
  Logger.log("BERHASIL MEMBUAT GOOGLE FORM UAT KAFEINA ERP!");
  Logger.log("Link Edit Form    : " + form.getEditUrl());
  Logger.log("Link Pengisian Form: " + form.getPublishedUrl());
  Logger.log("==================================================================");
}
