<?php
// Inisialisasi sesi dan koneksi database
session_start();
require_once '../config/db.php';
requireAdmin();                   // Pastikan hanya admin yang bisa akses
cekPenghuniKeluar($conn);         // Tandai penghuni yang masa sewanya sudah habis
generateTagihanBulanan($conn);    // Auto-generate tagihan bulanan jika belum ada

$pageTitle = 'Dashboard - Kost Simbah Admin';

// ─── Statistik Ringkasan ─────────────────────────────────────────────────────
$total_kamar    = $conn->query("SELECT COUNT(*) as c FROM kamar")->fetch_assoc()['c'];
$kamar_kosong   = $conn->query("SELECT COUNT(*) as c FROM kamar WHERE status='Tersedia'")->fetch_assoc()['c'];
$kamar_terisi   = $conn->query("SELECT COUNT(*) as c FROM kamar WHERE status='Terisi'")->fetch_assoc()['c'];
$penghuni_aktif = $conn->query("SELECT COUNT(*) as c FROM penghuni WHERE status='Aktif'")->fetch_assoc()['c'];

// Ambil 5 penghuni terbaru beserta nomor kamarnya
$penghuni_baru = $conn->query("
    SELECT p.*, k.nomor_kamar
    FROM penghuni p
    LEFT JOIN kamar k ON p.kamar_id = k.id
    ORDER BY p.created_at DESC
    LIMIT 5
")->fetch_all(MYSQLI_ASSOC);

// Ambil 5 log aktivitas terbaru untuk ditampilkan di sidebar/feed
$aktivitas = $conn->query("SELECT * FROM aktivitas ORDER BY created_at DESC LIMIT 5")->fetch_all(MYSQLI_ASSOC);

include '../config/head.php';
?>
<?php include 'sidebar.php'; ?>

<main class="pt-20 lg:pt-6 lg:ml-20 xl:ml-64 p-6">
  <div class="max-w-container-max mx-auto p-sm md:p-xl space-y-lg">

    <!-- Judul halaman dan sapaan nama admin -->
    <div class="space-y-xs">
      <h1 class="font-headline-lg-mobile md:font-headline-lg text-primary">Overview Dashboard</h1>
      <p class="text-on-surface-variant font-body-md">Selamat datang, <?= htmlspecialchars($_SESSION['admin_nama']) ?>. Berikut ringkasan properti Anda hari ini.</p>
    </div>

    <!-- Kartu statistik: Total Kamar, Kamar Kosong, Kamar Terisi, Penghuni Aktif -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-gutter">
      <!-- Kartu total semua kamar -->
      <div class="bg-surface-container-lowest p-md rounded-xl shadow-sm border border-outline-variant/20 hover:scale-[1.02] transition-transform">
        <div class="flex justify-between items-start mb-sm">
          <div class="bg-primary-fixed p-2 rounded-lg"><span class="material-symbols-outlined text-primary">apartment</span></div>
        </div>
        <p class="text-on-surface-variant font-label-md">Total Kamar</p>
        <p class="font-display-lg text-display-lg text-primary mt-xs"><?= $total_kamar ?></p>
      </div>
      <!-- Kartu kamar yang masih kosong/tersedia -->
      <div class="bg-surface-container-lowest p-md rounded-xl shadow-sm border border-outline-variant/20 hover:scale-[1.02] transition-transform">
        <div class="flex justify-between items-start mb-sm">
          <div class="bg-tertiary-fixed p-2 rounded-lg"><span class="material-symbols-outlined text-tertiary">door_open</span></div>
          <div class="w-2 h-2 rounded-full bg-tertiary animate-pulse"></div>
        </div>
        <p class="text-on-surface-variant font-label-md">Kamar Kosong</p>
        <p class="font-display-lg text-display-lg text-tertiary mt-xs"><?= $kamar_kosong ?></p>
      </div>
      <!-- Kartu kamar terisi beserta persentase occupancy -->
      <div class="bg-surface-container-lowest p-md rounded-xl shadow-sm border border-outline-variant/20 hover:scale-[1.02] transition-transform">
        <div class="flex justify-between items-start mb-sm">
          <div class="bg-primary-fixed-dim p-2 rounded-lg"><span class="material-symbols-outlined text-primary-container">meeting_room</span></div>
          <span class="text-xs font-bold text-on-primary-fixed-variant"><?= $total_kamar > 0 ? round($kamar_terisi/$total_kamar*100) : 0 ?>% Occupied</span>
        </div>
        <p class="text-on-surface-variant font-label-md">Kamar Terisi</p>
        <p class="font-display-lg text-display-lg text-primary-container mt-xs"><?= $kamar_terisi ?></p>
      </div>
      <!-- Kartu jumlah penghuni yang berstatus aktif -->
      <div class="bg-surface-container-lowest p-md rounded-xl shadow-sm border border-outline-variant/20 hover:scale-[1.02] transition-transform">
        <div class="flex justify-between items-start mb-sm">
          <div class="bg-secondary-fixed p-2 rounded-lg"><span class="material-symbols-outlined text-secondary">group</span></div>
        </div>
        <p class="text-on-surface-variant font-label-md">Penghuni</p>
        <p class="font-display-lg text-display-lg text-on-secondary-fixed-variant mt-xs"><?= $penghuni_aktif ?></p>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-lg">
      <!-- Tabel 5 penghuni terbaru yang baru ditambahkan -->
      <div class="lg:col-span-3 space-y-md">
        <div class="flex justify-between items-end">
          <h2 class="font-title-md text-title-md text-primary">Penghuni Terbaru</h2>
          <a class="text-primary font-label-md hover:underline" href="/kost_simbah/admin/penghuni.php">Lihat semua</a>
        </div>
        <div class="bg-surface-container-lowest rounded-xl overflow-hidden shadow-sm border border-outline-variant/20">
          <div class="overflow-x-auto">
            <table class="w-full text-left">
              <!-- Header kolom tabel penghuni -->
              <thead class="bg-surface-container-low border-b border-outline-variant/30">
                <tr>
                  <th class="px-md py-4 font-label-md text-on-surface-variant">Nama</th>
                  <th class="px-md py-4 font-label-md text-on-surface-variant">Kamar</th>
                  <th class="px-md py-4 font-label-md text-on-surface-variant">Masuk</th>
                  <th class="px-md py-4 font-label-md text-on-surface-variant">Keluar</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-outline-variant/20">
                <?php if(empty($penghuni_baru)): ?>
                <tr><td colspan="4" class="px-md py-8 text-center text-on-surface-variant">Belum ada data penghuni</td></tr>
                <?php else: ?>
                <?php foreach($penghuni_baru as $p):
                  $inisial = strtoupper(substr($p['nama'],0,1)); // Huruf pertama nama untuk avatar
                  if ($p['tanggal_keluar']) {
                      $sudah_lewat = new DateTime($p['tanggal_keluar']) < new DateTime('today');
                      $selisih = (new DateTime('today'))->diff(new DateTime($p['tanggal_keluar']))->days;
                  }
                ?>
                <tr class="hover:bg-surface-container transition-colors">
                  <!-- Avatar inisial + nama penghuni -->
                  <td class="px-md py-4">
                    <div class="flex items-center gap-xs">
                      <div class="w-8 h-8 rounded-full bg-primary-fixed text-primary flex items-center justify-center font-bold text-xs"><?= $inisial ?></div>
                      <span class="font-medium"><?= htmlspecialchars($p['nama']) ?></span>
                    </div>
                  </td>
                  <td class="px-md py-4 text-on-surface-variant"><?= $p['nomor_kamar'] ? 'Room '.$p['nomor_kamar'] : '-' ?></td>
                  <td class="px-md py-4 text-on-surface-variant"><?= $p['tanggal_masuk'] ? date('d M Y', strtotime($p['tanggal_masuk'])) : '-' ?></td>
                  <!-- Badge status keluar: merah jika sudah lewat, hijau dengan sisa hari jika masih aktif -->
                  <td class="px-md py-4">
                    <?php if (!$p['tanggal_keluar']): ?>
                      <span class="text-on-surface-variant text-sm">-</span>
                    <?php elseif ($sudah_lewat): ?>
                      <span class="px-2 py-1 rounded-full bg-error-container text-on-error-container text-[10px] font-bold uppercase">Sudah Keluar</span>
                    <?php else: ?>
                      <span class="px-2 py-1 rounded-full bg-primary-fixed text-on-primary-fixed-variant text-[10px] font-bold"><?= $selisih ?> hari lagi</span>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</main>
</body>
</html>
