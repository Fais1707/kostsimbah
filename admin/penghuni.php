<?php
// Inisialisasi sesi dan koneksi database
session_start();
require_once '../config/db.php';
requireAdmin();           // Pastikan hanya admin yang bisa akses
cekPenghuniKeluar($conn); // Cek penghuni yang masa sewanya sudah habis

$pageTitle = 'Manajemen Penghuni - Kost Simbah Admin';
$msg = getFlash(); // Ambil pesan flash dari sesi sebelumnya

// ─── Handle POST: Konversi Booking, Tambah/Edit, Hapus Penghuni ──────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Konversi booking yang sudah Konfirmasi menjadi penghuni baru
    if ($action === 'from_booking') {
        $booking_id = (int)$_POST['booking_id'];
        $nama       = $conn->real_escape_string(trim($_POST['nama']));
        $email      = $conn->real_escape_string(trim($_POST['email'] ?? ''));
        $no_hp      = $conn->real_escape_string(trim($_POST['no_hp'] ?? ''));
        $no_ktp     = $conn->real_escape_string(trim($_POST['no_ktp'] ?? ''));
        $kamar_id   = $_POST['kamar_id'] ? (int)$_POST['kamar_id'] : 'NULL';
        $tgl_masuk  = $conn->real_escape_string($_POST['tanggal_masuk']);
        $tgl_keluar = $_POST['tanggal_keluar'] ? "'".$conn->real_escape_string($_POST['tanggal_keluar'])."'" : 'NULL';

        $conn->query("INSERT INTO penghuni (nama, email, no_hp, no_ktp, kamar_id, tanggal_masuk, tanggal_keluar, status)
                      VALUES ('$nama','$email','$no_hp','$no_ktp',$kamar_id,'$tgl_masuk',$tgl_keluar,'Aktif')");

        // Tandai kamar sebagai Terisi setelah penghuni masuk
        if ($kamar_id !== 'NULL') {
            $conn->query("UPDATE kamar SET status='Terisi' WHERE id=$kamar_id");
        }

        // Tandai booking sebagai Selesai supaya tidak muncul lagi di list konversi
        $conn->query("UPDATE booking SET status='Selesai' WHERE id=$booking_id");

        logAktivitas($conn, 'Penghuni Baru dari Booking', "$nama dikonversi dari booking menjadi penghuni.", 'Lainnya');
        setFlash("$nama berhasil ditambahkan sebagai penghuni!");
        header('Location: /kost_simbah/admin/penghuni.php');
        exit;

    // Tambah penghuni baru secara manual atau edit data penghuni yang sudah ada
    } elseif ($action === 'add' || $action === 'edit') {
        $nama       = $conn->real_escape_string(trim($_POST['nama']));
        $email      = $conn->real_escape_string(trim($_POST['email'] ?? ''));
        $no_hp      = $conn->real_escape_string(trim($_POST['no_hp'] ?? ''));
        $no_ktp     = $conn->real_escape_string(trim($_POST['no_ktp'] ?? ''));
        $kamar_id   = $_POST['kamar_id'] ? (int)$_POST['kamar_id'] : 'NULL';
        $tgl_masuk  = $conn->real_escape_string($_POST['tanggal_masuk']);
        $tgl_keluar = $_POST['tanggal_keluar'] ? "'".$conn->real_escape_string($_POST['tanggal_keluar'])."'" : 'NULL';
        $status     = 'Aktif';

        if ($action === 'add') {
            // Validasi: pastikan kamar yang dipilih masih Tersedia saat submit
            $kamar_ok = true;
            if ($kamar_id !== 'NULL') {
                $cek_kamar = $conn->query("SELECT status FROM kamar WHERE id=$kamar_id")->fetch_assoc();
                if ($cek_kamar && $cek_kamar['status'] !== 'Tersedia') {
                    $msg      = 'Gagal: Kamar yang dipilih sudah tidak tersedia. Silakan pilih kamar lain.';
                    $kamar_ok = false;
                }
            }
            if ($kamar_ok) {
                $conn->query("INSERT INTO penghuni (nama, email, no_hp, no_ktp, kamar_id, tanggal_masuk, tanggal_keluar, status)
                              VALUES ('$nama','$email','$no_hp','$no_ktp',$kamar_id,'$tgl_masuk',$tgl_keluar,'$status')");
                // Tandai kamar Terisi setelah penghuni baru masuk
                if ($kamar_id !== 'NULL') {
                    $conn->query("UPDATE kamar SET status='Terisi' WHERE id=$kamar_id");
                }
                logAktivitas($conn, 'Penghuni Baru', "$nama telah ditambahkan sebagai penghuni.", 'Lainnya');
                setFlash('Penghuni berhasil ditambahkan!');
                header('Location: /kost_simbah/admin/penghuni.php');
                exit;
            }
        } else {
            $id = (int)$_POST['id'];
            // Ambil kamar lama sebelum diupdate untuk keperluan perbandingan
            $lama       = $conn->query("SELECT kamar_id FROM penghuni WHERE id=$id")->fetch_assoc();
            $kamar_lama = $lama['kamar_id'] ?? null;

            $conn->query("UPDATE penghuni SET nama='$nama', email='$email', no_hp='$no_hp', no_ktp='$no_ktp',
                          kamar_id=$kamar_id, tanggal_masuk='$tgl_masuk', tanggal_keluar=$tgl_keluar
                          WHERE id=$id");

            // Hapus tagihan Pending/Telat lama agar di-regenerate ulang dari tanggal masuk baru
            $conn->query("DELETE FROM pembayaran WHERE penghuni_id=$id AND status IN ('Pending','Telat')");

            // Jika kamar berubah: bebaskan kamar lama dan kunci kamar baru
            if ($kamar_lama && $kamar_lama != (int)$_POST['kamar_id']) {
                // Cek dulu apakah ada penghuni lain di kamar lama sebelum membebaskannya
                $masih_ada = $conn->query("SELECT COUNT(*) as c FROM penghuni WHERE kamar_id=$kamar_lama AND id!=$id")->fetch_assoc()['c'];
                if ($masih_ada == 0) {
                    $conn->query("UPDATE kamar SET status='Tersedia' WHERE id=$kamar_lama");
                }
            }
            if ($kamar_id !== 'NULL') {
                $conn->query("UPDATE kamar SET status='Terisi' WHERE id=$kamar_id");
            }
            logAktivitas($conn, 'Data Penghuni Diperbarui', "Data $nama telah diperbarui.", 'Lainnya');
            setFlash('Data penghuni berhasil diperbarui!');
            header('Location: /kost_simbah/admin/penghuni.php');
            exit;
        }

    // Hapus penghuni dan bebaskan kamarnya
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        // Ambil kamar yang dihuni sebelum hapus
        $ph = $conn->query("SELECT kamar_id FROM penghuni WHERE id=$id")->fetch_assoc();
        if ($ph && $ph['kamar_id']) {
            // Hanya bebaskan kamar jika tidak ada penghuni lain yang tinggal di kamar yang sama
            $masih_ada = $conn->query("SELECT COUNT(*) as c FROM penghuni WHERE kamar_id={$ph['kamar_id']} AND id!=$id")->fetch_assoc()['c'];
            if ($masih_ada == 0) {
                $conn->query("UPDATE kamar SET status='Tersedia' WHERE id={$ph['kamar_id']}");
            }
        }
        $conn->query("DELETE FROM penghuni WHERE id=$id");
        setFlash('Penghuni berhasil dihapus.');
        header('Location: /kost_simbah/admin/penghuni.php');
        exit;
    }
}

// ─── Filter & Search ─────────────────────────────────────────────────────────
$search = $conn->real_escape_string(trim($_GET['q'] ?? ''));
$where  = "1=1";
if ($search) $where .= " AND (p.nama LIKE '%$search%' OR p.no_hp LIKE '%$search%')";

// ─── Pagination ──────────────────────────────────────────────────────────────
$page        = max(1, (int)($_GET['page'] ?? 1));
$per_page    = 10;
$offset      = ($page - 1) * $per_page;
$total       = $conn->query("SELECT COUNT(*) as c FROM penghuni p WHERE $where")->fetch_assoc()['c'];
$total_pages = ceil($total / $per_page);

// Ambil data penghuni dengan JOIN ke kamar untuk nomor dan nama kamar
$list = $conn->query("
    SELECT p.*, k.nomor_kamar, k.nama as nama_kamar
    FROM penghuni p
    LEFT JOIN kamar k ON p.kamar_id = k.id
    WHERE $where
    ORDER BY p.created_at DESC
    LIMIT $per_page OFFSET $offset
")->fetch_all(MYSQLI_ASSOC);

// Kamar yang bisa dipilih saat tambah penghuni baru: hanya yang masih Tersedia
$kamar_tersedia = $conn->query("SELECT id, nomor_kamar, nama FROM kamar WHERE status='Tersedia' ORDER BY nomor_kamar")->fetch_all(MYSQLI_ASSOC);
$kamar_list     = $kamar_tersedia; // Default, akan di-override saat form edit dibuka

// Booking yang sudah Konfirmasi dan siap dikonversi menjadi penghuni
$booking_konfirmasi = $conn->query("
    SELECT b.*, k.nomor_kamar, k.id as k_id
    FROM booking b
    LEFT JOIN kamar k ON b.kamar_id = k.id
    WHERE b.status = 'Konfirmasi'
    ORDER BY b.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

// Kamar untuk dropdown di modal konversi booking:
// Gabungkan kamar Tersedia + kamar dari booking Konfirmasi (meski statusnya Terisi)
// agar kamar yang sudah dipilih di booking tetap bisa tampil di dropdown
$kamar_ids_booking  = array_filter(array_column($booking_konfirmasi, 'k_id'));
$extra_ids          = !empty($kamar_ids_booking) ? implode(',', array_map('intval', $kamar_ids_booking)) : '0';
$kamar_untuk_booking = $conn->query("
    SELECT id, nomor_kamar, nama FROM kamar
    WHERE status='Tersedia' OR id IN ($extra_ids)
    ORDER BY nomor_kamar
")->fetch_all(MYSQLI_ASSOC);

// ─── Data Edit Penghuni ──────────────────────────────────────────────────────
// Jika ada parameter ?edit= di URL, ambil data penghuni untuk form edit
$edit = null;
if (isset($_GET['edit'])) {
    $eid  = (int)$_GET['edit'];
    $edit = $conn->query("SELECT * FROM penghuni WHERE id=$eid")->fetch_assoc();
    if ($edit && $edit['kamar_id']) {
        // Sertakan kamar yang sedang dihuni (meski Terisi) supaya tetap terpilih di dropdown edit
        $kamar_list = $conn->query("SELECT id, nomor_kamar, nama FROM kamar WHERE status='Tersedia' OR id={$edit['kamar_id']} ORDER BY nomor_kamar")->fetch_all(MYSQLI_ASSOC);
    }
}

include '../config/head.php';
?>
<?php include 'sidebar.php'; ?>
<main class="pt-20 lg:pt-6 lg:ml-20 xl:ml-64 p-6">

  <!-- Pesan flash notifikasi hasil aksi -->
  <?php if ($msg): ?>
  <div class="mb-md p-md bg-primary-fixed text-on-primary-fixed rounded-xl font-bold text-center">
    <?= htmlspecialchars($msg) ?>
  </div>
  <?php endif; ?>

  <!-- Header halaman + tombol aksi (Dari Booking & Tambah Manual) -->
  <section class="mb-lg flex flex-col md:flex-row md:items-center justify-between gap-md">
    <div>
      <h2 class="font-headline-lg text-primary">Manajemen Penghuni</h2>
      <p class="text-on-surface-variant font-body-md">Kelola data penghuni dan informasi sewa.</p>
    </div>
    <div class="flex gap-xs flex-wrap justify-end">
      <!-- Tombol "Dari Booking" hanya muncul jika ada booking yang sudah Konfirmasi -->
      <?php if (!empty($booking_konfirmasi)): ?>
      <button onclick="document.getElementById('modal-booking').classList.remove('hidden')"
        class="flex items-center gap-xs px-md py-sm bg-surface border border-primary text-primary rounded-xl font-label-md hover:bg-primary-fixed transition-all shadow-sm">
        <span class="material-symbols-outlined">event_available</span>
        Dari Booking
        <!-- Badge jumlah booking yang menunggu konversi -->
        <span class="bg-primary text-on-primary text-[10px] font-bold px-1.5 py-0.5 rounded-full"><?= count($booking_konfirmasi) ?></span>
      </button>
      <?php endif; ?>
      <!-- Tombol tambah penghuni manual -->
      <button onclick="document.getElementById('modal-add').classList.remove('hidden')"
        class="flex items-center gap-xs px-md py-sm bg-primary text-on-primary rounded-xl font-label-md hover:opacity-90 transition-all shadow-sm">
        <span class="material-symbols-outlined">person_add</span> Tambah Manual
      </button>
    </div>
  </section>

  <!-- Kartu ringkasan: total penghuni dan penghuni baru bulan ini -->
  <section class="grid grid-cols-2 gap-md mb-lg">
    <div class="p-md rounded-xl bg-surface border border-outline-variant/30 shadow-sm">
      <p class="text-xs text-on-surface-variant">Total Penghuni</p>
      <p class="text-headline-lg text-primary font-bold"><?= $total ?></p>
    </div>
    <div class="p-md rounded-xl bg-surface border border-outline-variant/30 shadow-sm">
      <p class="text-xs text-on-surface-variant">Penghuni Bulan Ini</p>
      <p class="text-headline-lg text-primary-fixed-dim font-bold"><?= str_pad($conn->query("SELECT COUNT(*) as c FROM penghuni WHERE MONTH(created_at)=MONTH(NOW()) AND YEAR(created_at)=YEAR(NOW())")->fetch_assoc()['c'], 2, '0', STR_PAD_LEFT) ?></p>
    </div>
  </section>

  <!-- Form pencarian penghuni berdasarkan nama atau nomor HP -->
  <section class="mb-md p-sm md:p-md bg-surface-container-low rounded-xl">
    <form method="GET" class="flex flex-col md:flex-row gap-md items-center">
      <div class="relative w-full md:w-96">
        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant">search</span>
        <input name="q" type="text" value="<?= htmlspecialchars($search) ?>"
          class="w-full pl-10 pr-4 py-2.5 bg-surface border border-outline-variant/50 rounded-lg outline-none font-body-md"
          placeholder="Cari nama atau no HP..."/>
      </div>
      <button type="submit" class="px-md py-2 bg-primary text-on-primary rounded-lg font-label-md hover:opacity-90">Cari</button>
      <a href="/kost_simbah/admin/penghuni.php" class="px-md py-2 text-on-surface-variant hover:text-primary font-label-md">Reset</a>
    </form>
  </section>

  <!-- Tabel daftar penghuni -->
  <div class="bg-surface-container-lowest rounded-xl overflow-hidden shadow-sm border border-outline-variant/20">
    <!-- Header kolom tabel -->
    <div class="hidden md:grid grid-cols-12 gap-md p-md bg-surface-container-high border-b border-outline-variant/30 font-bold font-label-md">
      <div class="col-span-3">Nama</div>
      <div class="col-span-2">No. HP</div>
      <div class="col-span-2">Kamar</div>
      <div class="col-span-2">Masuk</div>
      <div class="col-span-2">Keluar</div>
      <div class="col-span-1 text-right">Aksi</div>
    </div>

    <div class="divide-y divide-outline-variant/20">
      <?php if (empty($list)): ?>
      <!-- Tampilan kosong jika tidak ada penghuni ditemukan -->
      <div class="p-xl text-center text-on-surface-variant">
        <span class="material-symbols-outlined text-5xl mb-md block">person_off</span>
        <p>Tidak ada penghuni ditemukan.</p>
      </div>
      <?php else: ?>
      <?php foreach ($list as $p):
        $inisial = strtoupper(substr($p['nama'], 0, 1)); // Huruf pertama nama untuk avatar
        // Hitung sisa hari dan cek apakah tanggal keluar sudah lewat
        if ($p['tanggal_keluar']) {
            $selisih     = (new DateTime())->diff(new DateTime($p['tanggal_keluar']))->days;
            $sudah_lewat = new DateTime($p['tanggal_keluar']) < new DateTime('today');
        }
      ?>
      <!-- Baris data penghuni -->
      <div class="grid grid-cols-1 md:grid-cols-12 gap-md p-md hover:bg-surface-container-low transition-colors items-center">
        <!-- Kolom avatar inisial + nama dan email -->
        <div class="col-span-3 flex items-center gap-xs">
          <div class="w-9 h-9 rounded-full bg-primary-fixed text-primary flex items-center justify-center font-bold shrink-0">
            <?= $inisial ?>
          </div>
          <div>
            <p class="font-medium"><?= htmlspecialchars($p['nama']) ?></p>
            <p class="text-xs text-on-surface-variant"><?= htmlspecialchars($p['email'] ?? '-') ?></p>
          </div>
        </div>
        <!-- Kolom nomor HP -->
        <div class="col-span-2 text-on-surface-variant text-sm"><?= htmlspecialchars($p['no_hp'] ?? '-') ?></div>
        <!-- Kolom nomor kamar -->
        <div class="col-span-2 text-sm">
          <?php
            $masih_aktif = !$p['tanggal_keluar'] || new DateTime($p['tanggal_keluar']) >= new DateTime('today');
            if ($p['nomor_kamar']):
          ?>
            Room <?= $p['nomor_kamar'] ?>
          <?php elseif ($masih_aktif): ?>
            <span class="px-2 py-1 rounded-full bg-yellow-100 text-yellow-800 text-[10px] font-bold">Belum Ada Kamar</span>
          <?php else: ?>
            <span class="text-on-surface-variant">-</span>
          <?php endif; ?>
        </div>
        <!-- Kolom tanggal masuk -->
        <div class="col-span-2 text-sm text-on-surface-variant">
          <?= $p['tanggal_masuk'] ? date('d M Y', strtotime($p['tanggal_masuk'])) : '-' ?>
        </div>
        <!-- Badge keluar: merah jika sudah lewat, biru dengan sisa hari jika masih aktif -->
        <div class="col-span-2 text-sm">
          <?php if (!$p['tanggal_keluar']): ?>
            <span class="text-on-surface-variant">-</span>
          <?php elseif ($sudah_lewat): ?>
            <span class="px-2 py-1 rounded-full bg-error-container text-on-error-container text-[10px] font-bold uppercase">Sudah Keluar</span>
          <?php else: ?>
            <span class="px-2 py-1 rounded-full bg-primary-fixed text-on-primary-fixed-variant text-[10px] font-bold"><?= $selisih ?> hari lagi</span>
          <?php endif; ?>
        </div>
        <!-- Kolom tombol aksi: edit dan hapus -->
        <div class="col-span-1 flex justify-end gap-xs">
          <!-- Tombol edit: redirect ke ?edit=ID untuk membuka modal edit -->
          <a href="?edit=<?= $p['id'] ?>" class="p-2 rounded-lg bg-surface-container-high text-on-surface-variant hover:bg-secondary-container transition-all" title="Edit">
            <span class="material-symbols-outlined">edit</span>
          </a>
          <!-- Form hapus penghuni dengan konfirmasi -->
          <form method="POST" onsubmit="return confirm('Hapus penghuni ini?')" class="inline">
            <input type="hidden" name="action" value="delete"/>
            <input type="hidden" name="id" value="<?= $p['id'] ?>"/>
            <button type="submit" class="p-2 rounded-lg bg-surface-container-high text-error hover:bg-error-container transition-all" title="Hapus">
              <span class="material-symbols-outlined">delete</span>
            </button>
          </form>
        </div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- Navigasi pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="p-md flex items-center justify-between border-t border-outline-variant/30 bg-surface-container-low">
      <p class="text-xs text-on-surface-variant">
        Menampilkan <?= $offset + 1 ?>–<?= min($offset + $per_page, $total) ?> dari <?= $total ?> penghuni
      </p>
      <div class="flex gap-xs">
        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
        <a href="?page=<?= $i ?>&q=<?= urlencode($search) ?>"
           class="w-8 h-8 flex items-center justify-center rounded-lg text-xs <?= $i === $page ? 'bg-primary text-on-primary font-bold' : 'border border-outline-variant/50 text-on-surface-variant hover:bg-surface-container-high' ?>">
          <?= $i ?>
        </a>
        <?php endfor; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</main>

<!-- Modal: Daftar Booking Konfirmasi yang siap dikonversi ke penghuni -->
<div id="modal-booking" class="hidden fixed inset-0 z-50 flex items-center justify-center p-sm">
  <!-- Overlay gelap, klik untuk tutup modal -->
  <div onclick="document.getElementById('modal-booking').classList.add('hidden')" class="absolute inset-0 bg-black/50"></div>
  <div class="relative bg-surface rounded-2xl shadow-xl w-full max-w-xl max-h-[90vh] overflow-y-auto p-lg">
    <div class="flex justify-between items-center mb-md">
      <div>
        <h3 class="font-title-md text-primary">Booking Terkonfirmasi</h3>
        <p class="text-xs text-on-surface-variant">Pilih salah satu untuk dijadikan penghuni.</p>
      </div>
      <button onclick="document.getElementById('modal-booking').classList.add('hidden')">
        <span class="material-symbols-outlined text-outline">close</span>
      </button>
    </div>

    <?php if (empty($booking_konfirmasi)): ?>
    <div class="text-center py-lg text-on-surface-variant">
      <span class="material-symbols-outlined text-4xl block mb-xs">event_busy</span>
      Tidak ada booking yang terkonfirmasi.
    </div>
    <?php else: ?>
    <!-- List booking yang bisa dipilih untuk dikonversi -->
    <div class="space-y-xs">
      <?php foreach ($booking_konfirmasi as $b): ?>
      <div class="flex items-center justify-between p-md rounded-xl border border-outline-variant/30 bg-surface-container-low hover:bg-primary-fixed/30 transition-colors">
        <div>
          <p class="font-medium"><?= htmlspecialchars($b['nama']) ?></p>
          <p class="text-xs text-on-surface-variant">
            <?= htmlspecialchars($b['no_hp']) ?>
            <?= $b['nomor_kamar'] ? ' · Room '.$b['nomor_kamar'] : '' ?>
            · Survey <?= $b['tanggal_tour'] ? date('d M Y', strtotime($b['tanggal_tour'])) : '-' ?>
          </p>
        </div>
        <!-- Klik Pilih → auto-fill form konversi via JavaScript lalu buka modal form -->
        <button type="button"
          onclick="pilihBooking(<?= htmlspecialchars(json_encode($b)) ?>)"
          class="px-md py-xs bg-primary text-on-primary rounded-lg font-label-md hover:opacity-90 text-sm shrink-0">
          Pilih
        </button>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Modal: Form lengkap untuk konversi booking ke penghuni (auto-filled dari data booking) -->
<div id="modal-from-booking" class="hidden fixed inset-0 z-50 flex items-center justify-center p-sm">
  <div onclick="document.getElementById('modal-from-booking').classList.add('hidden')" class="absolute inset-0 bg-black/50"></div>
  <div class="relative bg-surface rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto p-lg">
    <div class="flex justify-between items-center mb-lg">
      <div>
        <h3 class="font-title-md text-primary">Jadikan Penghuni</h3>
        <p class="text-xs text-on-surface-variant">Data dari booking, lengkapi yang masih kosong.</p>
      </div>
      <button onclick="document.getElementById('modal-from-booking').classList.add('hidden')">
        <span class="material-symbols-outlined text-outline">close</span>
      </button>
    </div>
    <form method="POST" class="space-y-md">
      <input type="hidden" name="action" value="from_booking"/>
      <!-- ID booking yang dipilih, diisi oleh JavaScript -->
      <input type="hidden" name="booking_id" id="fb-booking-id"/>
      <!-- Input nama penghuni, pre-filled dari data booking -->
      <div>
        <label class="block text-label-md text-on-surface-variant mb-xs">Nama Lengkap*</label>
        <input id="fb-nama" name="nama" type="text" required class="w-full border border-outline-variant/50 rounded-lg px-md py-xs outline-none bg-surface-container-low"/>
      </div>
      <!-- Input nomor HP dan email dari data booking -->
      <div class="grid grid-cols-2 gap-md">
        <div>
          <label class="block text-label-md text-on-surface-variant mb-xs">No. HP*</label>
          <input id="fb-no-hp" name="no_hp" type="text" class="w-full border border-outline-variant/50 rounded-lg px-md py-xs outline-none bg-surface-container-low"/>
        </div>
        <div>
          <label class="block text-label-md text-on-surface-variant mb-xs">Email</label>
          <input id="fb-email" name="email" type="email" class="w-full border border-outline-variant/50 rounded-lg px-md py-xs outline-none bg-surface-container-low"/>
        </div>
      </div>
      <!-- Input nomor KTP (tidak ada di booking, harus diisi manual) -->
      <div>
        <label class="block text-label-md text-on-surface-variant mb-xs">No. KTP</label>
        <input name="no_ktp" type="text" class="w-full border border-outline-variant/50 rounded-lg px-md py-xs outline-none bg-surface-container-low" placeholder="3271..."/>
      </div>
      <!-- Dropdown kamar: pre-selected dari booking, bisa diganti manual -->
      <div>
        <label class="block text-label-md text-on-surface-variant mb-xs">Kamar</label>
        <select id="fb-kamar" name="kamar_id" class="w-full border border-outline-variant/50 rounded-lg px-md py-xs outline-none bg-surface-container-low">
          <option value="">-- Pilih Kamar --</option>
          <?php foreach ($kamar_untuk_booking as $k): ?>
          <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nomor_kamar'].' - '.$k['nama']) ?></option>
          <?php endforeach; ?>
        </select>
        <!-- Hint bahwa kamar sudah diisi otomatis dari data booking -->
        <p id="fb-kamar-hint" class="hidden text-xs text-primary mt-1">★ Kamar sudah terisi otomatis dari data booking. Bisa diganti jika perlu.</p>
      </div>
      <!-- Input tanggal masuk dan keluar -->
      <div class="grid grid-cols-2 gap-md">
        <div>
          <label class="block text-label-md text-on-surface-variant mb-xs">Tanggal Masuk*</label>
          <input id="fb-tgl-masuk" name="tanggal_masuk" type="date" required class="w-full border border-outline-variant/50 rounded-lg px-md py-xs outline-none bg-surface-container-low"/>
        </div>
        <div>
          <label class="block text-label-md text-on-surface-variant mb-xs">Tanggal Keluar</label>
          <input name="tanggal_keluar" type="date" class="w-full border border-outline-variant/50 rounded-lg px-md py-xs outline-none bg-surface-container-low"/>
        </div>
      </div>
      <button type="submit" class="w-full bg-primary text-on-primary py-md rounded-xl font-bold hover:opacity-90 transition-all">
        Simpan sebagai Penghuni
      </button>
    </form>
  </div>
</div>

<!-- Modal: Tambah Penghuni Manual (tanpa data booking) -->
<div id="modal-add" class="hidden fixed inset-0 z-50 flex items-center justify-center p-sm">
  <div onclick="document.getElementById('modal-add').classList.add('hidden')" class="absolute inset-0 bg-black/50"></div>
  <div class="relative bg-surface rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto p-lg">
    <div class="flex justify-between items-center mb-lg">
      <h3 class="font-title-md text-primary">Tambah Penghuni</h3>
      <button onclick="document.getElementById('modal-add').classList.add('hidden')">
        <span class="material-symbols-outlined text-outline">close</span>
      </button>
    </div>
    <form method="POST" class="space-y-md">
      <input type="hidden" name="action" value="add"/>
      <div>
        <label class="block text-label-md text-on-surface-variant mb-xs">Nama Lengkap*</label>
        <input name="nama" type="text" required class="w-full border border-outline-variant/50 rounded-lg px-md py-xs outline-none bg-surface-container-low" placeholder="Budi Pratama"/>
      </div>
      <div class="grid grid-cols-2 gap-md">
        <div>
          <label class="block text-label-md text-on-surface-variant mb-xs">Email</label>
          <input name="email" type="email" class="w-full border border-outline-variant/50 rounded-lg px-md py-xs outline-none bg-surface-container-low" placeholder="email@contoh.com"/>
        </div>
        <div>
          <label class="block text-label-md text-on-surface-variant mb-xs">No. HP</label>
          <input name="no_hp" type="text" class="w-full border border-outline-variant/50 rounded-lg px-md py-xs outline-none bg-surface-container-low" placeholder="08123456789"/>
        </div>
      </div>
      <div>
        <label class="block text-label-md text-on-surface-variant mb-xs">No. KTP</label>
        <input name="no_ktp" type="text" class="w-full border border-outline-variant/50 rounded-lg px-md py-xs outline-none bg-surface-container-low" placeholder="3271..."/>
      </div>
      <!-- Dropdown kamar: hanya tampilkan yang berstatus Tersedia -->
      <div>
        <label class="block text-label-md text-on-surface-variant mb-xs">Kamar</label>
        <select name="kamar_id" class="w-full border border-outline-variant/50 rounded-lg px-md py-xs outline-none bg-surface-container-low">
          <option value="">-- Belum Ada --</option>
          <?php foreach ($kamar_tersedia as $k): ?>
          <option value="<?= $k['id'] ?>"><?= htmlspecialchars($k['nomor_kamar'].' - '.$k['nama']) ?></option>
          <?php endforeach; ?>
          <?php if (empty($kamar_tersedia)): ?>
          <option disabled>Tidak ada kamar tersedia</option>
          <?php endif; ?>
        </select>
      </div>
      <div class="grid grid-cols-2 gap-md">
        <div>
          <label class="block text-label-md text-on-surface-variant mb-xs">Tanggal Masuk*</label>
          <input name="tanggal_masuk" type="date" required class="w-full border border-outline-variant/50 rounded-lg px-md py-xs outline-none bg-surface-container-low"/>
        </div>
        <div>
          <label class="block text-label-md text-on-surface-variant mb-xs">Tanggal Keluar</label>
          <input name="tanggal_keluar" type="date" class="w-full border border-outline-variant/50 rounded-lg px-md py-xs outline-none bg-surface-container-low"/>
        </div>
      </div>
      <button type="submit" class="w-full bg-primary text-on-primary py-md rounded-xl font-bold hover:opacity-90 transition-all">
        Simpan Penghuni
      </button>
    </form>
  </div>
</div>

<!-- Modal: Edit Penghuni — hanya dirender jika ada parameter ?edit= di URL -->
<?php if ($edit): ?>
<div id="modal-edit" class="fixed inset-0 z-50 flex items-center justify-center p-sm">
  <div class="absolute inset-0 bg-black/50"></div>
  <div class="relative bg-surface rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto p-lg">
    <div class="flex justify-between items-center mb-lg">
      <h3 class="font-title-md text-primary">Edit Penghuni</h3>
      <!-- Tutup dengan redirect ke URL tanpa ?edit= -->
      <a href="/kost_simbah/admin/penghuni.php"><span class="material-symbols-outlined text-outline">close</span></a>
    </div>
    <form method="POST" class="space-y-md">
      <input type="hidden" name="action" value="edit"/>
      <input type="hidden" name="id" value="<?= $edit['id'] ?>"/>
      <div>
        <label class="block text-label-md text-on-surface-variant mb-xs">Nama Lengkap*</label>
        <input name="nama" type="text" required value="<?= htmlspecialchars($edit['nama']) ?>" class="w-full border border-outline-variant/50 rounded-lg px-md py-xs outline-none bg-surface-container-low"/>
      </div>
      <div class="grid grid-cols-2 gap-md">
        <div>
          <label class="block text-label-md text-on-surface-variant mb-xs">Email</label>
          <input name="email" type="email" value="<?= htmlspecialchars($edit['email'] ?? '') ?>" class="w-full border border-outline-variant/50 rounded-lg px-md py-xs outline-none bg-surface-container-low"/>
        </div>
        <div>
          <label class="block text-label-md text-on-surface-variant mb-xs">No. HP</label>
          <input name="no_hp" type="text" value="<?= htmlspecialchars($edit['no_hp'] ?? '') ?>" class="w-full border border-outline-variant/50 rounded-lg px-md py-xs outline-none bg-surface-container-low"/>
        </div>
      </div>
      <div>
        <label class="block text-label-md text-on-surface-variant mb-xs">No. KTP</label>
        <input name="no_ktp" type="text" value="<?= htmlspecialchars($edit['no_ktp'] ?? '') ?>" class="w-full border border-outline-variant/50 rounded-lg px-md py-xs outline-none bg-surface-container-low"/>
      </div>
      <!-- Dropdown kamar: Tersedia + kamar yang sedang dihuni penghuni ini -->
      <div>
        <label class="block text-label-md text-on-surface-variant mb-xs">Kamar</label>
        <select name="kamar_id" class="w-full border border-outline-variant/50 rounded-lg px-md py-xs outline-none bg-surface-container-low">
          <option value="">-- Belum Ada --</option>
          <?php foreach ($kamar_list as $k): ?>
          <option value="<?= $k['id'] ?>" <?= $edit['kamar_id'] == $k['id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($k['nomor_kamar'].' - '.$k['nama']) ?>
          </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="grid grid-cols-2 gap-md">
        <div>
          <label class="block text-label-md text-on-surface-variant mb-xs">Tanggal Masuk*</label>
          <input name="tanggal_masuk" type="date" required value="<?= $edit['tanggal_masuk'] ?>" class="w-full border border-outline-variant/50 rounded-lg px-md py-xs outline-none bg-surface-container-low"/>
        </div>
        <div>
          <label class="block text-label-md text-on-surface-variant mb-xs">Tanggal Keluar</label>
          <input name="tanggal_keluar" type="date" value="<?= $edit['tanggal_keluar'] ?? '' ?>" class="w-full border border-outline-variant/50 rounded-lg px-md py-xs outline-none bg-surface-container-low"/>
        </div>
      </div>
      <button type="submit" class="w-full bg-primary text-on-primary py-md rounded-xl font-bold hover:opacity-90 transition-all">
        Simpan Perubahan
      </button>
    </form>
  </div>
</div>
<?php endif; ?>

<script>
// Fungsi dipanggil saat tombol "Pilih" diklik pada list booking
// Tugasnya: tutup modal list → isi form konversi → buka modal form konversi
function pilihBooking(b) {
  document.getElementById('modal-booking').classList.add('hidden');

  // Isi field form dari data booking yang dipilih
  document.getElementById('fb-booking-id').value = b.id;
  document.getElementById('fb-nama').value        = b.nama;
  document.getElementById('fb-no-hp').value       = b.no_hp;
  document.getElementById('fb-email').value       = b.email ?? '';
  // Default tanggal masuk ke hari ini
  document.getElementById('fb-tgl-masuk').value   = new Date().toISOString().split('T')[0];

  // Pilih kamar dari data booking jika ada, lalu tampilkan hint info
  const sel  = document.getElementById('fb-kamar');
  const hint = document.getElementById('fb-kamar-hint');
  sel.value  = ''; // Reset pilihan dulu
  hint.classList.add('hidden');

  if (b.k_id) {
    for (let opt of sel.options) {
      if (opt.value == b.k_id) {
        opt.selected = true;
        hint.classList.remove('hidden'); // Tampilkan info bahwa kamar diambil dari booking
        break;
      }
    }
  }

  // Sembunyikan hint kembali jika user mengganti kamar secara manual
  sel.onchange = () => hint.classList.add('hidden');

  document.getElementById('modal-from-booking').classList.remove('hidden');
}
</script>

</body>
</html>
