<?php
// Inisialisasi sesi dan koneksi database
session_start();
require_once '../config/db.php';
requireAdmin();           // Pastikan hanya admin yang bisa akses
cekPenghuniKeluar($conn); // Cek penghuni yang masa sewanya sudah habis

$pageTitle = 'Manajemen Kamar - Kost Simbah Admin';
$msg = getFlash(); // Ambil pesan flash dari sesi sebelumnya

// ─── Handle POST: Tambah, Edit, Hapus, Ubah Status Kamar ────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Proses tambah kamar baru atau edit kamar yang sudah ada
    if ($action === 'add' || $action === 'edit') {
        $nomor     = $conn->real_escape_string(trim($_POST['nomor_kamar']));
        $nama      = $conn->real_escape_string(trim($_POST['nama']));
        $tipe      = $conn->real_escape_string($_POST['tipe']);
        $lantai    = (int)$_POST['lantai'];
        $luas      = (int)($_POST['luas'] ?? 0);
        $harga     = (int)preg_replace('/[^0-9]/', '', $_POST['harga']); // Bersihkan karakter non-angka dari input harga
        $status    = $conn->real_escape_string($_POST['status']);
        $deskripsi = $conn->real_escape_string(trim($_POST['deskripsi'] ?? ''));
        $fas_raw   = array_filter(array_map('trim', explode(',', $_POST['fasilitas'] ?? '')));
        $fasilitas = json_encode(array_values($fas_raw)); // Simpan fasilitas sebagai JSON array

        // Proses upload foto jika ada file yang dikirim
        $foto_sql = '';
        $foto_url = null;
        if (!empty($_FILES['foto']['name'])) {
            $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            // Hanya izinkan ekstensi gambar yang valid
            if (in_array($ext, ['jpg','jpeg','png','webp'])) {
                $filename   = 'kamar_' . time() . '_' . rand(100,999) . '.' . $ext;
                $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/kost_simbah/uploads/kamar/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                $upload_path = $upload_dir . $filename;
                if (move_uploaded_file($_FILES['foto']['tmp_name'], $upload_path)) {
                    $foto_url = '/kost_simbah/uploads/kamar/' . $filename;
                    $foto_sql = ", foto='" . $conn->real_escape_string($foto_url) . "'";
                    // Hapus foto lama dari server saat edit agar tidak menumpuk
                    if ($action === 'edit') {
                        $old = $conn->query("SELECT foto FROM kamar WHERE id=" . (int)$_POST['id'])->fetch_assoc();
                        if (!empty($old['foto']) && file_exists($_SERVER['DOCUMENT_ROOT'] . $old['foto'])) {
                            @unlink($_SERVER['DOCUMENT_ROOT'] . $old['foto']);
                        }
                    }
                }
            }
        }

        if ($action === 'add') {
            $columns = "nomor_kamar, nama, tipe, lantai, luas, harga, status, deskripsi, fasilitas";
            $values  = "'$nomor','$nama','$tipe',$lantai,$luas,$harga,'$status','$deskripsi','$fasilitas'";
            if ($foto_url) {
                $columns .= ", foto";
                $values  .= ", '" . $conn->real_escape_string($foto_url) . "'";
            }
            $conn->query("INSERT INTO kamar ($columns) VALUES ($values)");
            logAktivitas($conn, 'Kamar Baru Ditambah', "Kamar $nama ($nomor) berhasil ditambahkan.", 'Lainnya');
            setFlash('Kamar berhasil ditambahkan!');
            header('Location: /kost_simbah/admin/kamar.php');
            exit;
        } else {
            $id = (int)$_POST['id'];
            // Cek apakah ada penghuni yang menempati kamar ini
            // Jika ada, status tidak boleh diubah — paksa tetap 'Terisi'
            $ada_penghuni = $conn->query("SELECT COUNT(*) as c FROM penghuni WHERE kamar_id=$id")->fetch_assoc()['c'];
            if ($ada_penghuni > 0) $status = 'Terisi';
            // $foto_sql otomatis kosong jika tidak ada file baru, sehingga foto lama tidak tertimpa
            $conn->query("UPDATE kamar SET nomor_kamar='$nomor', nama='$nama', tipe='$tipe', lantai=$lantai, luas=$luas, harga=$harga, status='$status', deskripsi='$deskripsi', fasilitas='$fasilitas'$foto_sql WHERE id=$id");
            setFlash('Kamar berhasil diperbarui!');
            header('Location: /kost_simbah/admin/kamar.php');
            exit;
        }

    // Hapus kamar berdasarkan ID
    } elseif ($action === 'delete') {
        $id = (int)$_POST['id'];
        $conn->query("DELETE FROM kamar WHERE id=$id");
        setFlash('Kamar berhasil dihapus.');
        header('Location: /kost_simbah/admin/kamar.php');
        exit;

    // Ubah status kamar secara langsung (Tersedia / Terisi / Maintenance)
    } elseif ($action === 'status') {
        $id         = (int)$_POST['id'];
        $new_status = $conn->real_escape_string($_POST['new_status']);
        $conn->query("UPDATE kamar SET status='$new_status' WHERE id=$id");
        $msg = 'Status kamar diperbarui.';
    }
}

// ─── Filter & Search ─────────────────────────────────────────────────────────
$search        = $conn->real_escape_string(trim($_GET['q'] ?? ''));
$filter_status = $conn->real_escape_string($_GET['status'] ?? '');
$where = "1=1";
if ($search)        $where .= " AND (nama LIKE '%$search%' OR nomor_kamar LIKE '%$search%')";
if ($filter_status) $where .= " AND status='$filter_status'";

// ─── Pagination ──────────────────────────────────────────────────────────────
$page        = max(1, (int)($_GET['page'] ?? 1));
$per_page    = 10;
$offset      = ($page - 1) * $per_page;
$total       = $conn->query("SELECT COUNT(*) as c FROM kamar WHERE $where")->fetch_assoc()['c'];
$total_pages = ceil($total / $per_page);

$kamar_list = $conn->query("SELECT * FROM kamar WHERE $where ORDER BY nomor_kamar ASC LIMIT $per_page OFFSET $offset")->fetch_all(MYSQLI_ASSOC);

// ─── Statistik Kamar ─────────────────────────────────────────────────────────
// Hitung jumlah kamar per status untuk kartu ringkasan
$stats    = $conn->query("SELECT status, COUNT(*) as c FROM kamar GROUP BY status")->fetch_all(MYSQLI_ASSOC);
$stat_map = array_column($stats, 'c', 'status');

// ─── Data Edit Kamar ─────────────────────────────────────────────────────────
// Jika ada parameter ?edit= di URL, ambil data kamar untuk form edit
$edit_kamar = null;
if (isset($_GET['edit'])) {
    $eid        = (int)$_GET['edit'];
    $edit_kamar = $conn->query("SELECT * FROM kamar WHERE id=$eid")->fetch_assoc();
    // Cek apakah ada penghuni aktif agar dropdown status bisa dikunci
    if ($edit_kamar) {
        $edit_kamar['ada_penghuni'] = $conn->query("SELECT COUNT(*) as c FROM penghuni WHERE kamar_id=$eid")->fetch_assoc()['c'];
    }
}

include '../config/head.php';
?>
<?php include 'sidebar.php'; ?>

<main class="pt-20 lg:pt-6 lg:ml-20 xl:ml-64 p-6">

  <!-- Pesan flash notifikasi hasil aksi -->
  <?php if($msg): ?>
  <div class="mb-md p-md bg-primary-fixed text-on-primary-fixed rounded-xl font-bold text-center">
    <?= htmlspecialchars($msg) ?>
  </div>
  <?php endif; ?>

  <!-- Header halaman + tombol tambah kamar -->
  <section class="mb-lg flex flex-col md:flex-row md:items-center justify-between gap-md">
    <div>
      <h2 class="font-headline-lg text-primary">Room Management</h2>
      <p class="text-on-surface-variant font-body-md">Kelola unit kamar dan status ketersediaan.</p>
    </div>
    <button onclick="document.getElementById('modal-add').classList.remove('hidden')"
      class="flex items-center justify-center gap-xs px-md py-sm bg-primary text-on-primary rounded-xl font-label-md hover:opacity-90 transition-all shadow-sm">
      <span class="material-symbols-outlined">add</span> Tambah Kamar
    </button>
  </section>

  <!-- Kartu ringkasan: jumlah kamar per status -->
  <section class="grid grid-cols-2 md:grid-cols-4 gap-md mb-lg">
    <div class="p-md rounded-xl bg-surface border border-outline-variant/30 shadow-sm">
      <p class="text-xs text-on-surface-variant font-label-sm">Total Kamar</p>
      <p class="text-headline-lg text-primary font-bold"><?= $total ?></p>
    </div>
    <div class="p-md rounded-xl bg-surface border border-outline-variant/30 shadow-sm">
      <p class="text-xs text-on-surface-variant font-label-sm">Tersedia</p>
      <p class="text-headline-lg text-primary-fixed-dim font-bold"><?= str_pad($stat_map['Tersedia'] ?? 0, 2, '0', STR_PAD_LEFT) ?></p>
    </div>
    <div class="p-md rounded-xl bg-surface border border-outline-variant/30 shadow-sm">
      <p class="text-xs text-on-surface-variant font-label-sm">Terisi</p>
      <p class="text-headline-lg text-secondary font-bold"><?= str_pad($stat_map['Terisi'] ?? 0, 2, '0', STR_PAD_LEFT) ?></p>
    </div>
    <div class="p-md rounded-xl bg-surface border border-outline-variant/30 shadow-sm">
      <p class="text-xs text-on-surface-variant font-label-sm">Maintenance</p>
      <p class="text-headline-lg text-error font-bold"><?= str_pad($stat_map['Maintenance'] ?? 0, 2, '0', STR_PAD_LEFT) ?></p>
    </div>
  </section>

  <!-- Form filter: cari nama/nomor kamar dan saring berdasarkan status -->
  <section class="mb-md p-sm md:p-md bg-surface-container-low rounded-xl flex flex-col md:flex-row gap-md items-center">
    <form method="GET" class="flex flex-col md:flex-row gap-md w-full items-center">
      <div class="relative w-full md:w-96">
        <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-on-surface-variant">search</span>
        <input name="q" type="text" value="<?= htmlspecialchars($search) ?>" class="w-full pl-10 pr-4 py-2.5 bg-surface border border-outline-variant/50 rounded-lg outline-none font-body-md" placeholder="Cari nama atau nomor kamar..."/>
      </div>
      <select name="status" class="bg-surface border border-outline-variant/50 rounded-lg px-4 py-2 font-label-md text-on-surface-variant outline-none">
        <option value="">Semua Status</option>
        <option value="Tersedia"    <?= $filter_status==='Tersedia'    ? 'selected' : '' ?>>Tersedia</option>
        <option value="Terisi"      <?= $filter_status==='Terisi'      ? 'selected' : '' ?>>Terisi</option>
        <option value="Maintenance" <?= $filter_status==='Maintenance' ? 'selected' : '' ?>>Maintenance</option>
      </select>
      <button type="submit" class="px-md py-2 bg-primary text-on-primary rounded-lg font-label-md hover:opacity-90">Filter</button>
      <a href="/kost_simbah/admin/kamar.php" class="px-md py-2 text-on-surface-variant hover:text-primary font-label-md">Reset</a>
    </form>
  </section>

  <!-- Tabel daftar kamar -->
  <div class="bg-surface-container-lowest rounded-xl overflow-hidden shadow-sm border border-outline-variant/20">
    <!-- Header kolom tabel -->
    <div class="hidden md:grid grid-cols-12 gap-md p-md bg-surface-container-high border-b border-outline-variant/30 font-bold font-label-md">
      <div class="col-span-4">Info Kamar</div>
      <div class="col-span-2 text-center">Status</div>
      <div class="col-span-2">Harga</div>
      <div class="col-span-4 text-right">Aksi</div>
    </div>

    <div class="divide-y divide-outline-variant/20">
      <?php if(empty($kamar_list)): ?>
      <!-- Tampilan kosong jika tidak ada kamar yang ditemukan -->
      <div class="p-xl text-center text-on-surface-variant">
        <span class="material-symbols-outlined text-5xl mb-md block">search_off</span>
        <p>Tidak ada kamar ditemukan.</p>
      </div>
      <?php else: ?>
      <?php foreach($kamar_list as $k):
        // Mapping status kamar ke kelas CSS badge
        $status_badge = [
          'Tersedia'    => 'bg-primary-fixed text-on-primary-fixed-variant',
          'Terisi'      => 'bg-secondary-fixed-dim text-on-secondary-fixed-variant',
          'Maintenance' => 'bg-error-container text-on-error-container',
        ][$k['status']] ?? '';
      ?>
      <!-- Baris data kamar -->
      <div class="grid grid-cols-1 md:grid-cols-12 gap-md p-md hover:bg-surface-container-low transition-colors items-center">
        <!-- Kolom foto + nama kamar, nomor, luas, lantai -->
        <div class="col-span-1 md:col-span-4 flex items-center gap-md">
          <div class="w-20 h-20 rounded-lg overflow-hidden bg-surface-variant shrink-0 flex items-center justify-center">
            <?php if($k['foto']): ?>
            <img alt="<?= htmlspecialchars($k['nama']) ?>" class="w-full h-full object-cover" src="<?= htmlspecialchars($k['foto']) ?>"/>
            <?php else: ?>
            <!-- Placeholder jika belum ada foto kamar -->
            <span class="material-symbols-outlined text-outline text-4xl">bed</span>
            <?php endif; ?>
          </div>
          <div>
            <h4 class="font-title-md text-on-surface"><?= htmlspecialchars($k['nama']) ?></h4>
            <p class="text-xs text-on-surface-variant flex items-center gap-1">
              <span class="material-symbols-outlined text-[14px]">square_foot</span>
              <?= $k['luas'] ?? '-' ?> m² • Lantai <?= $k['lantai'] ?> • No. <?= $k['nomor_kamar'] ?>
            </p>
          </div>
        </div>
        <!-- Kolom badge status kamar -->
        <div class="col-span-1 md:col-span-2 flex justify-start md:justify-center">
          <span class="px-3 py-1 rounded-full <?= $status_badge ?> font-label-sm text-xs"><?= $k['status'] ?></span>
        </div>
        <!-- Kolom harga sewa per bulan -->
        <div class="col-span-1 md:col-span-2">
          <p class="font-bold text-primary"><?= formatRupiah($k['harga']) ?></p>
          <p class="text-[10px] text-on-surface-variant">per bulan</p>
        </div>
        <!-- Kolom tombol aksi: edit dan hapus -->
        <div class="col-span-1 md:col-span-4 flex flex-wrap justify-end gap-xs">
          <!-- Tombol edit: redirect ke ?edit=ID untuk buka modal edit -->
          <a href="?edit=<?= $k['id'] ?>" class="p-2 rounded-lg bg-surface-container-high text-on-surface-variant hover:bg-secondary-container transition-all" title="Edit">
            <span class="material-symbols-outlined">edit</span>
          </a>
          <!-- Form hapus kamar dengan konfirmasi -->
          <form method="POST" onsubmit="return confirm('Hapus kamar ini?')" class="inline">
            <input type="hidden" name="action" value="delete"/>
            <input type="hidden" name="id" value="<?= $k['id'] ?>"/>
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
    <?php if($total_pages > 1): ?>
    <div class="p-md flex items-center justify-between border-t border-outline-variant/30 bg-surface-container-low">
      <p class="text-xs text-on-surface-variant">Menampilkan <?= $offset+1 ?>-<?= min($offset+$per_page,$total) ?> dari <?= $total ?> kamar</p>
      <div class="flex items-center gap-xs">
        <?php for($i=1;$i<=$total_pages;$i++): ?>
        <a href="?page=<?= $i ?>&q=<?= urlencode($search) ?>&status=<?= urlencode($filter_status) ?>"
           class="w-8 h-8 flex items-center justify-center rounded-lg <?= $i==$page ? 'bg-primary text-on-primary font-bold' : 'border border-outline-variant/50 text-on-surface-variant hover:bg-surface-container-high' ?> text-xs">
          <?= $i ?>
        </a>
        <?php endfor; ?>
      </div>
    </div>
    <?php endif; ?>
  </div>
</main>

<!-- Modal Tambah Kamar Baru -->
<div id="modal-add" class="hidden fixed inset-0 z-50 flex items-center justify-center p-sm">
  <!-- Overlay gelap, klik untuk tutup modal -->
  <div onclick="document.getElementById('modal-add').classList.add('hidden')" class="absolute inset-0 bg-black/50"></div>
  <div class="relative bg-surface rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto p-lg">
    <div class="flex justify-between items-center mb-lg">
      <h3 class="font-title-md text-title-md text-primary">Tambah Kamar Baru</h3>
      <button onclick="document.getElementById('modal-add').classList.add('hidden')" class="text-outline hover:text-on-surface">
        <span class="material-symbols-outlined">close</span>
      </button>
    </div>
    <!-- enctype multipart/form-data wajib agar file foto bisa dikirim -->
    <form method="POST" enctype="multipart/form-data" class="space-y-md">
      <input type="hidden" name="action" value="add"/>
      <!-- Input nomor kamar dan lantai -->
      <div class="grid grid-cols-2 gap-md">
        <div>
          <label class="block text-label-md text-on-surface-variant mb-xs">No. Kamar*</label>
          <input name="nomor_kamar" type="text" required class="w-full border border-outline-variant/50 rounded-lg px-md py-xs outline-none bg-surface-container-low" placeholder="A1"/>
        </div>
        <div>
          <label class="block text-label-md text-on-surface-variant mb-xs">Lantai*</label>
          <input name="lantai" type="number" min="1" required class="w-full border border-outline-variant/50 rounded-lg px-md py-xs outline-none bg-surface-container-low" placeholder="1"/>
        </div>
      </div>
      <!-- Input nama kamar -->
      <div>
        <label class="block text-label-md text-on-surface-variant mb-xs">Nama Kamar*</label>
        <input name="nama" type="text" required class="w-full border border-outline-variant/50 rounded-lg px-md py-xs outline-none bg-surface-container-low" placeholder="Standard Room A1"/>
      </div>
      <!-- Dropdown tipe kamar dan input luas -->
      <div class="grid grid-cols-2 gap-md">
        <div>
          <label class="block text-label-md text-on-surface-variant mb-xs">Tipe*</label>
          <select name="tipe" class="w-full border border-outline-variant/50 rounded-lg px-md py-xs outline-none bg-surface-container-low">
            <option>Standard</option><option>Executive</option>
          </select>
        </div>
        <div>
          <label class="block text-label-md text-on-surface-variant mb-xs">Luas (m²)</label>
          <input name="luas" type="number" min="1" class="w-full border border-outline-variant/50 rounded-lg px-md py-xs outline-none bg-surface-container-low" placeholder="24"/>
        </div>
      </div>
      <!-- Input harga sewa dan status awal kamar -->
      <div class="grid grid-cols-2 gap-md">
        <div>
          <label class="block text-label-md text-on-surface-variant mb-xs">Harga/bln (Rp)*</label>
          <input name="harga" type="number" min="0" required class="w-full border border-outline-variant/50 rounded-lg px-md py-xs outline-none bg-surface-container-low" placeholder="2500000"/>
        </div>
        <div>
          <label class="block text-label-md text-on-surface-variant mb-xs">Status*</label>
          <select name="status" class="w-full border border-outline-variant/50 rounded-lg px-md py-xs outline-none bg-surface-container-low">
            <option>Tersedia</option><option>Terisi</option><option>Maintenance</option>
          </select>
        </div>
      </div>
      <!-- Input fasilitas dipisah koma, disimpan sebagai JSON -->
      <div>
        <label class="block text-label-md text-on-surface-variant mb-xs">Fasilitas (pisahkan koma)</label>
        <input name="fasilitas" type="text" class="w-full border border-outline-variant/50 rounded-lg px-md py-xs outline-none bg-surface-container-low" placeholder="AC, WiFi, KM Dalam, Meja & Kursi"/>
      </div>
      <!-- Textarea deskripsi singkat kamar -->
      <div>
        <label class="block text-label-md text-on-surface-variant mb-xs">Deskripsi</label>
        <textarea name="deskripsi" rows="3" class="w-full border border-outline-variant/50 rounded-lg px-md py-xs outline-none bg-surface-container-low" placeholder="Deskripsi singkat kamar..."></textarea>
      </div>
      <!-- Input upload foto kamar -->
      <div>
        <label class="block text-label-md text-on-surface-variant mb-xs">Foto Kamar</label>
        <input name="foto" type="file" accept="image/*"
          class="w-full border border-outline-variant/50 rounded-lg px-md py-xs bg-surface-container-low text-sm"/>
      </div>
      <button type="submit" class="w-full bg-primary text-on-primary py-md rounded-xl font-bold hover:opacity-90 transition-all">
        Simpan Kamar
      </button>
    </form>
  </div>
</div>

<!-- Modal Edit Kamar — hanya dirender jika ada parameter ?edit= di URL -->
<?php if($edit_kamar): ?>
<div id="modal-edit" class="fixed inset-0 z-50 flex items-center justify-center p-sm">
  <div class="absolute inset-0 bg-black/50"></div>
  <div class="relative bg-surface rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto p-lg">
    <div class="flex justify-between items-center mb-lg">
      <h3 class="font-title-md text-title-md text-primary">Edit Kamar</h3>
      <!-- Tutup modal edit dengan kembali ke URL tanpa ?edit= -->
      <a href="/kost_simbah/admin/kamar.php" class="text-outline hover:text-on-surface"><span class="material-symbols-outlined">close</span></a>
    </div>
    <!-- enctype multipart/form-data wajib agar file foto bisa dikirim -->
    <form method="POST" enctype="multipart/form-data" class="space-y-md">
      <input type="hidden" name="action" value="edit"/>
      <input type="hidden" name="id" value="<?= $edit_kamar['id'] ?>"/>
      <!-- Input nomor kamar dan lantai (pre-filled dengan data lama) -->
      <div class="grid grid-cols-2 gap-md">
        <div>
          <label class="block text-label-md text-on-surface-variant mb-xs">No. Kamar*</label>
          <input name="nomor_kamar" type="text" required value="<?= htmlspecialchars($edit_kamar['nomor_kamar']) ?>" class="w-full border border-outline-variant/50 rounded-lg px-md py-xs outline-none bg-surface-container-low"/>
        </div>
        <div>
          <label class="block text-label-md text-on-surface-variant mb-xs">Lantai*</label>
          <input name="lantai" type="number" min="1" required value="<?= $edit_kamar['lantai'] ?>" class="w-full border border-outline-variant/50 rounded-lg px-md py-xs outline-none bg-surface-container-low"/>
        </div>
      </div>
      <div>
        <label class="block text-label-md text-on-surface-variant mb-xs">Nama Kamar*</label>
        <input name="nama" type="text" required value="<?= htmlspecialchars($edit_kamar['nama']) ?>" class="w-full border border-outline-variant/50 rounded-lg px-md py-xs outline-none bg-surface-container-low"/>
      </div>
      <div class="grid grid-cols-2 gap-md">
        <div>
          <label class="block text-label-md text-on-surface-variant mb-xs">Tipe*</label>
          <select name="tipe" class="w-full border border-outline-variant/50 rounded-lg px-md py-xs outline-none bg-surface-container-low">
            <?php foreach(['Standard','Executive'] as $t): ?>
            <option <?= $edit_kamar['tipe']===$t?'selected':'' ?>><?= $t ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div>
          <label class="block text-label-md text-on-surface-variant mb-xs">Luas (m²)</label>
          <input name="luas" type="number" min="1" value="<?= $edit_kamar['luas'] ?>" class="w-full border border-outline-variant/50 rounded-lg px-md py-xs outline-none bg-surface-container-low"/>
        </div>
      </div>
      <div class="grid grid-cols-2 gap-md">
        <div>
          <label class="block text-label-md text-on-surface-variant mb-xs">Harga/bln (Rp)*</label>
          <input name="harga" type="number" min="0" required value="<?= $edit_kamar['harga'] ?>" class="w-full border border-outline-variant/50 rounded-lg px-md py-xs outline-none bg-surface-container-low"/>
        </div>
        <div>
          <label class="block text-label-md text-on-surface-variant mb-xs">Status*</label>
          <?php if ($edit_kamar['ada_penghuni'] > 0): ?>
          <!-- Status dikunci karena ada penghuni aktif di kamar ini -->
          <input type="hidden" name="status" value="Terisi"/>
          <div class="w-full border border-outline-variant/30 rounded-lg px-md py-xs bg-surface-container text-on-surface-variant flex items-center gap-xs">
            <span class="material-symbols-outlined text-[16px] text-error">lock</span>
            <span class="text-sm">Terisi</span>
            <span class="text-xs text-on-surface-variant ml-auto">Ada penghuni aktif</span>
          </div>
          <?php else: ?>
          <!-- Status bisa diubah bebas jika tidak ada penghuni -->
          <select name="status" class="w-full border border-outline-variant/50 rounded-lg px-md py-xs outline-none bg-surface-container-low">
            <?php foreach(['Tersedia','Terisi','Maintenance'] as $s): ?>
            <option <?= $edit_kamar['status']===$s?'selected':'' ?>><?= $s ?></option>
            <?php endforeach; ?>
          </select>
          <?php endif; ?>
        </div>
      </div>
      <!-- Input fasilitas: decode JSON dari DB lalu join kembali dengan koma untuk ditampilkan di input -->
      <div>
        <label class="block text-label-md text-on-surface-variant mb-xs">Fasilitas (pisahkan koma)</label>
        <input name="fasilitas" type="text" value="<?= htmlspecialchars(implode(', ', json_decode($edit_kamar['fasilitas'] ?? '[]', true))) ?>" class="w-full border border-outline-variant/50 rounded-lg px-md py-xs outline-none bg-surface-container-low"/>
      </div>
      <div>
        <label class="block text-label-md text-on-surface-variant mb-xs">Deskripsi</label>
        <textarea name="deskripsi" rows="3" class="w-full border border-outline-variant/50 rounded-lg px-md py-xs outline-none bg-surface-container-low"><?= htmlspecialchars($edit_kamar['deskripsi'] ?? '') ?></textarea>
      </div>
      <!-- Preview foto lama + input upload foto baru. Kosongkan jika tidak ingin ganti -->
      <div>
        <label class="block text-label-md text-on-surface-variant mb-xs">Foto Kamar</label>
        <?php if($edit_kamar['foto']): ?>
        <img src="<?= htmlspecialchars($edit_kamar['foto']) ?>" class="w-full h-40 object-cover rounded-lg mb-xs"/>
        <?php endif; ?>
        <input name="foto" type="file" accept="image/*"
          class="w-full border border-outline-variant/50 rounded-lg px-md py-xs bg-surface-container-low text-sm"/>
        <p class="text-xs text-on-surface-variant mt-1">Kosongkan jika tidak ingin mengubah foto.</p>
      </div>
      <button type="submit" class="w-full bg-primary text-on-primary py-md rounded-xl font-bold hover:opacity-90 transition-all">Simpan Perubahan</button>
    </form>
  </div>
</div>
<?php endif; ?>

</body>
</html>
