<?php
// Inisialisasi sesi dan koneksi database
session_start();
require_once '../config/db.php';
requireAdmin();              // Cek apakah user sudah login sebagai admin
cekPenghuniKeluar($conn);   // Cek penghuni yang masa sewanya sudah habis

$pageTitle = 'Manajemen Booking - Kost Simbah Admin';
$msg = getFlash(); // Ambil pesan flash dari sesi sebelumnya

// ─── Handle POST: Tambah, Ubah Status, Hapus Booking ───────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Tambah booking baru dari form
    if ($action === 'add') {
        $nama        = $conn->real_escape_string(trim($_POST['nama']));
        $no_hp       = $conn->real_escape_string(trim($_POST['no_hp']));
        $email       = $conn->real_escape_string(trim($_POST['email'] ?? ''));
        $kamar_id    = $_POST['kamar_id'] ? (int)$_POST['kamar_id'] : 'NULL'; // NULL jika tidak pilih kamar
        $tgl_tour    = $conn->real_escape_string($_POST['tanggal_tour']);
        $pesan       = $conn->real_escape_string(trim($_POST['pesan'] ?? ''));

        $conn->query("INSERT INTO booking (nama, no_hp, email, kamar_id, tanggal_tour, pesan)
                      VALUES ('$nama','$no_hp','$email',$kamar_id,'$tgl_tour','$pesan')");
        logAktivitas($conn, 'Booking Baru', "Booking dari $nama untuk survey kamar.", 'Booking');
        setFlash('Booking berhasil ditambahkan!');
        header('Location: /kost_simbah/admin/booking.php');
        exit;

    // Ubah status booking (Pending → Konfirmasi → Selesai → Batal)
    } elseif ($action === 'status') {
        $id         = (int)$_POST['id'];
        $new_status = $conn->real_escape_string($_POST['new_status']);
        $conn->query("UPDATE booking SET status='$new_status' WHERE id=$id");

        $bk = $conn->query("SELECT nama FROM booking WHERE id=$id")->fetch_assoc();
        if ($new_status === 'Konfirmasi') {
            logAktivitas($conn, 'Booking Dikonfirmasi', "Booking {$bk['nama']} dikonfirmasi.", 'Booking');
        }
        if ($new_status === 'Batal') {
            logAktivitas($conn, 'Booking Dibatalkan', "Booking {$bk['nama']} dibatalkan.", 'Booking');
        }
        $msg = 'Status booking diperbarui.';
        setFlash('Status booking diperbarui.');
        header('Location: /kost_simbah/admin/booking.php');
        exit;

    // Hapus booking berdasarkan ID
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $conn->query("DELETE FROM booking WHERE id=$id");
        setFlash('Booking berhasil dihapus.');
        header('Location: /kost_simbah/admin/booking.php');
        exit;
    }
}

// ─── Filter & Search ────────────────────────────────────────────────────────
// Ambil parameter pencarian nama/HP dan filter status dari URL
$search        = $conn->real_escape_string(trim($_GET['q'] ?? ''));
$filter_status = $conn->real_escape_string($_GET['status'] ?? '');
$where = "1=1";
if ($search)        $where .= " AND (b.nama LIKE '%$search%' OR b.no_hp LIKE '%$search%')";
if ($filter_status) $where .= " AND b.status='$filter_status'";

// ─── Pagination ─────────────────────────────────────────────────────────────
// Hitung total data dan batasi tampilan 10 data per halaman
$page        = max(1, (int)($_GET['page'] ?? 1));
$per_page    = 10;
$offset      = ($page - 1) * $per_page;
$total       = $conn->query("SELECT COUNT(*) as c FROM booking b WHERE $where")->fetch_assoc()['c'];
$total_pages = ceil($total / $per_page);

// Ambil data booking dengan join ke tabel kamar untuk nama & nomor kamar
$list = $conn->query("
    SELECT b.*, k.nomor_kamar, k.nama as nama_kamar
    FROM booking b
    LEFT JOIN kamar k ON b.kamar_id = k.id
    WHERE $where
    ORDER BY b.created_at DESC
    LIMIT $per_page OFFSET $offset
")->fetch_all(MYSQLI_ASSOC);

// Ambil daftar kamar yang masih tersedia untuk pilihan di form tambah booking
$kamar_list = $conn->query("SELECT id, nomor_kamar, nama FROM kamar WHERE status='Tersedia' ORDER BY nomor_kamar")->fetch_all(MYSQLI_ASSOC);

// ─── Statistik Booking ──────────────────────────────────────────────────────
// Hitung jumlah booking per status untuk ditampilkan di kartu ringkasan
$stats = $conn->query("SELECT status, COUNT(*) as c FROM booking GROUP BY status")->fetch_all(MYSQLI_ASSOC);
$stat_map = array_column($stats, 'c', 'status'); // Format: ['Pending' => 3, 'Konfirmasi' => 1, ...]

include '../config/head.php';
?>
<?php include 'sidebar.php'; ?>

<main class="pt-20 lg:pt-6 lg:ml-20 xl:ml-64 p-6">

  <!-- Pesan flash notifikasi hasil aksi (tambah/hapus/update) -->
  <?php if ($msg): ?>
  <div class="mb-md p-md bg-primary-fixed text-on-primary-fixed rounded-xl font-bold text-center">
    <?= htmlspecialchars($msg) ?>
  </div>
  <?php endif; ?>

  <!-- Header halaman + tombol tambah booking -->
  <section class="mb-lg flex flex-col md:flex-row md:items-center justify-between gap-md">
    <div>
      <h2 class="font-headline-lg text-primary">Manajemen Booking</h2>
      <p class="text-on-surface-variant font-body-md">Kelola permintaan booking dan jadwal survey kamar.</p>
    </div>
    <button onclick="document.getElementById('modal-add').classList.remove('hidden')"
      class="flex items-center justify-center gap-xs px-md py-sm bg-primary text-on-primary rounded-xl font-label-md hover:opacity-90 transition-all shadow-sm">
      <span class="material-symbols-outlined">add</span> Tambah Booking
    </button>
  </section>

  <!-- Kartu ringkasan: jumlah booking per status -->
  <section class="grid grid-cols-3 gap-md mb-lg">
    <div class="p-md rounded-xl bg-surface border border-outline-variant/30 shadow-sm">
      <p class="text-xs text-on-surface-variant">Pending</p>
      <p class="text-headline-lg text-tertiary font-bold"><?= str_pad($stat_map['Pending'] ?? 0, 2, '0', STR_PAD_LEFT) ?></p>
    </div>
    <div class="p-md rounded-xl bg-surface border border-outline-variant/30 shadow-sm">
      <p class="text-xs text-on-surface-variant">Konfirmasi</p>
      <p class="text-headline-lg text-primary font-bold"><?= str_pad($stat_map['Konfirmasi'] ?? 0, 2, '0', STR_PAD_LEFT) ?></p>
    </div>
    <div class="p-md rounded-xl bg-surface border border-outline-variant/30 shadow-sm">
      <p class="text-xs text-on-surface-variant">Batal</p>
      <p class="text-headline-lg text-error font-bold"><?= str_pad($stat_map['Batal'] ?? 0, 2, '0', STR_PAD_LEFT) ?></p>
    </div>
  </section>

  <!-- Form filter: cari berdasarkan nama/HP dan saring berdasarkan status -->
  <section class="mb-md p-sm md:p-md bg-surface-container-low rounded-xl">
    <form method="GET" class="flex flex-col md:flex-row gap-md items-center">
      <div class="relative w-full md:w-96">
        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant">search</span>
        <input name="q" type="text" value="<?= htmlspecialchars($search) ?>"
          class="w-full pl-10 pr-4 py-2.5 bg-surface border border-outline-variant/50 rounded-lg outline-none font-body-md"
          placeholder="Cari nama atau no HP..."/>
      </div>
      <select name="status" class="bg-surface border border-outline-variant/50 rounded-lg px-4 py-2 font-label-md text-on-surface-variant outline-none">
      <option value="Pending" <?= $filter_status === 'Pending' ? 'selected' : '' ?>>
          Pending</option>
      <option value="Konfirmasi" <?= $filter_status === 'Konfirmasi' ? 'selected' : '' ?>>
          Konfirmasi</option>
      <option value="Selesai" <?= $filter_status === 'Selesai' ? 'selected' : '' ?>>
          Selesai</option>
      <option value="Batal" <?= $filter_status === 'Batal' ? 'selected' : '' ?>>
          Batal</option>
      </select>
      <button type="submit" class="px-md py-2 bg-primary text-on-primary rounded-lg font-label-md hover:opacity-90">Filter</button>
      <a href="/kost_simbah/admin/booking.php" class="px-md py-2 text-on-surface-variant hover:text-primary font-label-md">Reset</a>
    </form>
  </section>

  <!-- Tabel daftar booking -->
  <div class="bg-surface-container-lowest rounded-xl overflow-hidden shadow-sm border border-outline-variant/20">
    <!-- Header kolom tabel (hanya tampil di layar medium ke atas) -->
    <div class="hidden md:grid grid-cols-12 gap-md p-md bg-surface-container-high border-b border-outline-variant/30 font-bold font-label-md">
      <div class="col-span-3">Nama & Kontak</div>
      <div class="col-span-2">Kamar</div>
      <div class="col-span-2">Tgl. Survey</div>
      <div class="col-span-3">Pesan</div>
      <div class="col-span-1">Status</div>
      <div class="col-span-1 text-right">Aksi</div>
    </div>

    <div class="divide-y divide-outline-variant/20">
      <?php if (empty($list)): ?>
      <!-- Tampilan kosong jika tidak ada data booking -->
      <div class="p-xl text-center text-on-surface-variant">
        <span class="material-symbols-outlined text-5xl mb-md block">event_busy</span>
        <p>Tidak ada booking ditemukan.</p>
      </div>
      <?php else: ?>
      <?php
      // Mapping status ke kelas CSS badge, tombol aksi berikutnya, dan ikon
      $status_badge = [
          'Pending'    => 'bg-tertiary-fixed text-tertiary',
          'Konfirmasi' => 'bg-primary-fixed text-on-primary-fixed-variant',
          'Selesai'    => 'bg-green-100 text-green-700',
          'Batal'      => 'bg-error-container text-on-error-container',
      ];
      $status_next = [
          'Pending'    => 'Konfirmasi',
          'Konfirmasi' => 'Selesai',
          'Selesai'    => 'Batal',
          'Batal'      => 'Pending'
      ];
      $status_icon = [
          'Pending'    => 'check_circle',
          'Konfirmasi' => 'task_alt',
          'Selesai'    => 'cancel',
          'Batal'      => 'replay'
      ];
      foreach ($list as $b):
      $badge = $status_badge[$b['status']] ?? 'bg-gray-100 text-gray-700';
      $next  = $status_next[$b['status']] ?? 'Pending';
      $icon  = $status_icon[$b['status']] ?? 'help';
      ?>
      <!-- Baris data booking -->
      <div class="grid grid-cols-1 md:grid-cols-12 gap-md p-md hover:bg-surface-container-low transition-colors items-center">
        <!-- Kolom nama dan nomor HP -->
        <div class="col-span-3">
          <p class="font-medium"><?= htmlspecialchars($b['nama']) ?></p>
          <p class="text-xs text-on-surface-variant"><?= htmlspecialchars($b['no_hp']) ?></p>
        </div>
        <!-- Kolom nomor kamar yang dipesan -->
        <div class="col-span-2 text-sm">
          <?= $b['nomor_kamar'] ? 'Room '.$b['nomor_kamar'] : '<span class="text-on-surface-variant">-</span>' ?>
        </div>
        <!-- Kolom tanggal survey yang diajukan -->
        <div class="col-span-2 text-sm text-on-surface-variant">
          <?= $b['tanggal_tour'] ? date('d M Y', strtotime($b['tanggal_tour'])) : '-' ?>
        </div>
        <!-- Kolom pesan/catatan dari pemesan -->
        <div class="col-span-3 text-sm text-on-surface-variant truncate">
          <?= htmlspecialchars($b['pesan'] ?: '-') ?>
        </div>
        <!-- Kolom badge status booking -->
        <div class="col-span-1">
          <span class="px-2 py-1 rounded-full <?= $badge ?> text-[10px] font-bold uppercase whitespace-nowrap"><?= $b['status'] ?></span>
        </div>
        <!-- Kolom tombol aksi: ubah status dan hapus -->
        <div class="col-span-1 flex justify-end gap-xs">
          <!-- Form ubah status ke status berikutnya -->
          <form method="POST" class="inline">
            <input type="hidden" name="action" value="status"/>
            <input type="hidden" name="id" value="<?= $b['id'] ?>"/>
            <input type="hidden" name="new_status" value="<?= $next ?>"/>
            <button type="submit" title="Ubah ke <?= $next ?>"
              class="p-2 rounded-lg bg-surface-container-high text-on-surface-variant hover:bg-primary-container hover:text-on-primary-container transition-all">
              <span class="material-symbols-outlined text-[18px]"><?= $icon ?></span>
            </button>
          </form>
          <!-- Form hapus booking dengan konfirmasi -->
          <form method="POST" onsubmit="return confirm('Hapus booking ini?')" class="inline">
            <input type="hidden" name="action" value="delete"/>
            <input type="hidden" name="id" value="<?= $b['id'] ?>"/>
            <button type="submit" class="p-2 rounded-lg bg-surface-container-high text-error hover:bg-error-container transition-all" title="Hapus">
              <span class="material-symbols-outlined text-[18px]">delete</span>
            </button>
          </form>
        </div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- Navigasi pagination (hanya tampil jika lebih dari 1 halaman) -->
    <?php if ($total_pages > 1): ?>
    <div class="p-md flex items-center justify-between border-t border-outline-variant/30 bg-surface-container-low">
      <p class="text-xs text-on-surface-variant">
        Menampilkan <?= $offset + 1 ?>–<?= min($offset + $per_page, $total) ?> dari <?= $total ?> booking
      </p>
      <div class="flex gap-xs">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <a href="?page=<?= $i ?>&q=<?= urlencode($search) ?>&status=<?= urlencode($filter_status) ?>"
           class="w-8 h-8 flex items-center justify-center rounded-lg text-xs <?= $i === $page ? 'bg-primary text-on-primary font-bold' : 'border border-outline-variant/50 text-on-surface-variant hover:bg-surface-container-high' ?>">
          <?= $i ?>
        </a>
        <?php endfor; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</main>

<!-- Modal form tambah booking baru -->
<div id="modal-add" class="hidden fixed inset-0 z-50 flex items-center justify-center p-sm">
  <!-- Overlay gelap, klik untuk tutup modal -->
  <div onclick="document.getElementById('modal-add').classList.add('hidden')" class="absolute inset-0 bg-black/50"></div>
  <div class="relative bg-surface rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto p-lg">
    <div class="flex justify-between items-center mb-lg">
      <h3 class="font-title-md text-primary">Tambah Booking</h3>
      <button onclick="document.getElementById('modal-add').classList.add('hidden')">
        <span class="material-symbols-outlined text-outline">close</span>
      </button>
    </div>
    <form method="POST" class="space-y-md">
      <input type="hidden" name="action" value="add"/>
      <!-- Input nama pemesan -->
      <div>
        <label class="block text-label-md text-on-surface-variant mb-xs">Nama Lengkap*</label>
        <input name="nama" type="text" required class="w-full border border-outline-variant/50 rounded-lg px-md py-xs outline-none bg-surface-container-low" placeholder="Andi Setiawan"/>
      </div>
      <!-- Input nomor HP dan email -->
      <div class="grid grid-cols-2 gap-md">
        <div>
          <label class="block text-label-md text-on-surface-variant mb-xs">No. HP*</label>
          <input name="no_hp" type="text" required class="w-full border border-outline-variant/50 rounded-lg px-md py-xs outline-none bg-surface-container-low" placeholder="08123456789"/>
        </div>
        <div>
          <label class="block text-label-md text-on-surface-variant mb-xs">Email</label>
          <input name="email" type="email" class="w-full border border-outline-variant/50 rounded-lg px-md py-xs outline-none bg-surface-container-low" placeholder="email@contoh.com"/>
        </div>
      </div>
      <!-- Dropdown pilih kamar yang tersedia -->
      <div>
        <label class="block text-label-md text-on-surface-variant mb-xs">Kamar yang Diminati</label>
        <select name="kamar_id" class="w-full border border-outline-variant/50 rounded-lg px-md py-xs outline-none bg-surface-container-low">
          <option value="">-- Pilih Kamar --</option>
          <?php foreach ($kamar_list as $k): ?>
          <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nomor_kamar'].' - '.$k['nama']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <!-- Input tanggal survey -->
      <div>
        <label class="block text-label-md text-on-surface-variant mb-xs">Tanggal Survey*</label>
        <input name="tanggal_tour" type="date" required class="w-full border border-outline-variant/50 rounded-lg px-md py-xs outline-none bg-surface-container-low"/>
      </div>
      <!-- Textarea pesan atau catatan tambahan -->
      <div>
        <label class="block text-label-md text-on-surface-variant mb-xs">Pesan / Catatan</label>
        <textarea name="pesan" rows="3" class="w-full border border-outline-variant/50 rounded-lg px-md py-xs outline-none bg-surface-container-low" placeholder="Pesan tambahan..."></textarea>
      </div>
      <button type="submit" class="w-full bg-primary text-on-primary py-md rounded-xl font-bold hover:opacity-90 transition-all">
        Simpan Booking
      </button>
    </form>
  </div>
</div>

</body>
</html>
