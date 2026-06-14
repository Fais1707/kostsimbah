<?php
session_start();
require_once 'config/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$stmt = $conn->prepare("SELECT * FROM kamar WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$kamar = $stmt->get_result()->fetch_assoc();

if (!$kamar) {
    header('Location: /kost_simbah/index.php#kamar');
    exit;
}

$fasilitas = json_decode($kamar['fasilitas'] ?? '[]', true);
$pageTitle = $kamar['nama'] . ' - Kost Simbah';

$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_tour'])) {
    // Re-cek status kamar langsung dari DB saat submit (hindari race condition)
    $cek = $conn->prepare("SELECT status FROM kamar WHERE id = ?");
    $cek->bind_param("i", $id);
    $cek->execute();
    $status_saat_ini = $cek->get_result()->fetch_assoc()['status'] ?? '';

    if ($status_saat_ini !== 'Tersedia') {
        // Kamar sudah terisi/maintenance saat form disubmit, tolak booking
        $msg = 'not_available';
        // Refresh data kamar supaya tampilan ikut update
        $stmt2 = $conn->prepare("SELECT * FROM kamar WHERE id = ?");
        $stmt2->bind_param("i", $id);
        $stmt2->execute();
        $kamar = $stmt2->get_result()->fetch_assoc();
    } else {
        $nama = $conn->real_escape_string(trim($_POST['nama']));
        $no_hp = $conn->real_escape_string(trim($_POST['no_hp']));
        $tgl = $conn->real_escape_string(trim($_POST['tanggal_tour']));
        $pesan = $conn->real_escape_string(trim($_POST['pesan'] ?? ''));
        if ($nama && $no_hp && $tgl) {
            $conn->query("INSERT INTO booking (nama, no_hp, kamar_id, tanggal_tour, pesan, status) VALUES ('$nama','$no_hp',$id,'$tgl','$pesan','Pending')");
            logAktivitas($conn, 'Booking Tour Baru', "$nama ingin tour kamar {$kamar['nomor_kamar']} pada $tgl", 'Booking');
            $msg = 'success';
        } else {
            $msg = 'error';
        }
    }
}

include 'config/head.php';
?>

<!-- Header -->
<header class="fixed top-0 w-full z-50 bg-surface/80 backdrop-blur-md border-b border-outline-variant/30 shadow-sm">
  <div class="max-w-container-max mx-auto px-sm md:px-md flex justify-between items-center h-16">
    <div class="flex items-center gap-xs">
      <img src="assets/img/hijau_nobg.png" alt="Kost Simbah" style="width:50px">
      <span class="text-title-md font-bold text-primary">Kost Simbah</span>
    </div>
    <nav class="hidden md:flex items-center gap-md">
      <a class="text-primary border-b-2 border-primary font-bold font-label-md text-label-md" href="/kost_simbah/index.php#home">Home</a>
      <a class="text-on-surface-variant hover:text-primary transition-colors font-label-md text-label-md" href="/kost_simbah/index.php#tentang">Tentang</a>
      <a class="text-on-surface-variant hover:text-primary transition-colors font-label-md text-label-md" href="/kost_simbah/index.php#kamar">Kamar</a>
      <a class="text-on-surface-variant hover:text-primary transition-colors font-label-md text-label-md" href="/kost_simbah/index.php#kontak">Kontak</a>
      <a href="/kost_simbah/index.php" class="flex items-center gap-xs text-on-surface-variant hover:text-primary">
      <span class="material-symbols-outlined">arrow_back</span>
      <span class="hidden md:inline font-label-md">Kembali</span>
    </a>
    </nav>
    <button class="md:hidden text-primary" onclick="document.getElementById('mobile-menu').classList.toggle('hidden')">
      <span class="material-symbols-outlined">menu</span>
    </button>
  </div>
  <!-- Mobile Menu -->
  <div id="mobile-menu" class="hidden md:hidden bg-surface border-t border-outline-variant/20 px-sm py-md flex flex-col gap-md">
    <a class="text-primary font-bold" href="/kost_simbah/index.php#home">Home</a>
    <a class="text-on-surface-variant" href="/kost_simbah/index.php#tentang">Tentang</a>
    <a class="text-on-surface-variant" href="/kost_simbah/index.php#kamar">Kamar</a>
    <a class="text-on-surface-variant" href="/kost_simbah/index.php#kontak">Kontak</a>
    <a href="/kost_simbah/index.php" class="flex items-center gap-xs text-on-surface-variant hover:text-primary">
    <span class="material-symbols-outlined">arrow_back</span>
    <span class="hidden md:inline font-label-md">Kembali</span>
    </a>
  </div>
</header>

</header>

<main class="pt-16 pb-24 md:pb-12">
  <!-- Gallery -->
  <section class="w-full max-w-container-max mx-auto px-0 md:px-md md:mt-md">
    <div class="w-full aspect-[16/9] overflow-hidden md:rounded-xl rounded-xl bg-surface-container flex items-center justify-center">
      <?php
      $gallerySrc = '';
      if (!empty($kamar['foto'])) {
          $photo_path = $_SERVER['DOCUMENT_ROOT'] . $kamar['foto'];
          if (file_exists($photo_path)) {
              $gallerySrc = $kamar['foto'];
          }
      }
      ?>
      <?php if($gallerySrc): ?>
      <img class="w-full h-full object-cover" src="<?= htmlspecialchars($gallerySrc) ?>" alt="<?= htmlspecialchars($kamar['nama']) ?>"/>
      <?php else: ?>
      <div class="flex flex-col items-center gap-md text-outline">
        <span class="material-symbols-outlined text-8xl">hotel</span>
        <p class="font-title-md">Foto segera hadir</p>
      </div>
      <?php endif; ?>
    </div>
  </section>

  <!-- Content -->
  <section class="max-w-container-max mx-auto px-sm md:px-md mt-lg flex flex-col lg:flex-row gap-lg">
    <!-- Detail -->
    <div class="flex-1">
      <div class="flex flex-col gap-xs mb-md">
        <div class="flex items-center gap-xs">
          <span class="bg-primary/10 text-primary-container px-xs py-1 rounded-full text-label-sm"><?= $kamar['tipe'] ?> Room</span>
          <?php
          $sc = ['Tersedia'=>'bg-on-primary-container/10 text-on-primary-container','Terisi'=>'bg-error-container text-on-error-container','Maintenance'=>'bg-tertiary-fixed text-tertiary'];
          ?>
          <span class="<?= $sc[$kamar['status']] ?? '' ?> px-xs py-1 rounded-full text-label-sm flex items-center gap-1">
            <span class="material-symbols-outlined text-[14px]">circle</span> <?= $kamar['status'] ?>
          </span>
        </div>
        <h1 class="font-headline-lg text-primary"><?= htmlspecialchars($kamar['nama']) ?></h1>
        <div class="flex items-center gap-xs text-on-surface-variant">
          <span class="material-symbols-outlined text-[20px]">location_on</span>
          <span>Jl. Kampus Merdeka No. 45, Indonesia • Lantai <?= $kamar['lantai'] ?></span>
        </div>
      </div>

      <hr class="border-outline-variant/30 my-lg"/>

      <!-- Highlights -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-md mb-lg">
        <div class="bg-surface-container-low p-md rounded-xl flex flex-col items-center gap-xs text-center border border-outline-variant/10">
          <span class="material-symbols-outlined text-primary text-[28px]">square_foot</span>
          <span class="text-label-sm text-on-surface-variant"><?= $kamar['luas'] ?? '—' ?> m²</span>
        </div>
        <div class="bg-surface-container-low p-md rounded-xl flex flex-col items-center gap-xs text-center border border-outline-variant/10">
          <span class="material-symbols-outlined text-primary text-[28px]">king_bed</span>
          <span class="text-label-sm text-on-surface-variant"><?= $kamar['tipe'] ?></span>
        </div>
        <div class="bg-surface-container-low p-md rounded-xl flex flex-col items-center gap-xs text-center border border-outline-variant/10">
          <span class="material-symbols-outlined text-primary text-[28px]">wifi</span>
          <span class="text-label-sm text-on-surface-variant">100 Mbps</span>
        </div>
        <div class="bg-surface-container-low p-md rounded-xl flex flex-col items-center gap-xs text-center border border-outline-variant/10">
          <span class="material-symbols-outlined text-primary text-[28px]">apartment</span>
          <span class="text-label-sm text-on-surface-variant">Lantai <?= $kamar['lantai'] ?></span>
        </div>
      </div>

      <!-- Deskripsi -->
      <div class="mb-lg">
        <h2 class="font-title-md text-title-md text-primary mb-md">Deskripsi</h2>
        <p class="text-body-md text-on-surface-variant leading-relaxed">
          <?= nl2br(htmlspecialchars($kamar['deskripsi'] ?? 'Kamar premium di Kost Simbah dengan fasilitas lengkap dan suasana nyaman.')) ?>
        </p>
      </div>

      <!-- Fasilitas -->
      <?php if(!empty($fasilitas)): ?>
      <div class="mb-lg">
        <h2 class="font-title-md text-title-md text-primary mb-md">Fasilitas Kamar</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-y-sm gap-x-lg">
          <?php foreach($fasilitas as $fas): ?>
          <div class="flex items-center gap-md">
            <span class="material-symbols-outlined text-secondary">check_circle</span>
            <span class="text-body-md text-on-surface-variant"><?= htmlspecialchars($fas) ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <!-- Booking Card -->
    <div class="w-full lg:w-[400px]">
      <div class="glass-card p-lg rounded-2xl shadow-lg border border-outline-variant/20 sticky top-24">
        <div class="mb-lg">
          <span class="text-label-sm text-on-surface-variant block mb-1">Harga per bulan</span>
          <div class="flex items-baseline gap-1">
            <span class="font-headline-lg text-primary"><?= formatRupiah($kamar['harga']) ?></span>
            <span class="text-on-surface-variant text-label-md">/bulan</span>
          </div>
        </div>

        <?php if($kamar['status'] === 'Tersedia'): ?>
        <?php if($msg === 'not_available'): ?>
        <div class="bg-error-container text-on-error-container p-md rounded-xl mb-md text-center font-bold">
          Maaf, kamar ini baru saja dipesan oleh orang lain. Silakan pilih kamar lain.
        </div>
        <?php elseif($msg === 'success'): ?>
        <div class="bg-primary-fixed text-on-primary-fixed p-md rounded-xl mb-md text-center font-bold">
          Booking tour berhasil! Kami akan menghubungi Anda.
        </div>
        <?php else: ?>
        <?php if($msg === 'error'): ?>
        <div class="bg-error-container text-on-error-container p-md rounded-xl mb-md text-center">Mohon lengkapi form booking.</div>
        <?php endif; ?>
        <form method="POST" class="space-y-md">
          <div>
            <label class="block text-label-md text-on-surface-variant mb-xs">Nama Lengkap</label>
            <input name="nama" type="text" required class="w-full border border-outline-variant/50 rounded-lg px-md py-xs focus:ring-2 focus:ring-primary/20 outline-none bg-surface" placeholder="Nama Anda"/>
          </div>
          <div>
            <label class="block text-label-md text-on-surface-variant mb-xs">No. WhatsApp</label>
            <input name="no_hp" type="tel" required class="w-full border border-outline-variant/50 rounded-lg px-md py-xs focus:ring-2 focus:ring-primary/20 outline-none bg-surface" placeholder="08xxxxxxxxxx"/>
          </div>
          <div>
            <label class="block text-label-md text-on-surface-variant mb-xs">Tanggal Tour</label>
            <input name="tanggal_tour" type="date" required min="<?= date('Y-m-d') ?>" class="w-full border border-outline-variant/50 rounded-lg px-md py-xs focus:ring-2 focus:ring-primary/20 outline-none bg-surface"/>
          </div>
          <div>
            <label class="block text-label-md text-on-surface-variant mb-xs">Pesan (opsional)</label>
            <textarea name="pesan" rows="3" class="w-full border border-outline-variant/50 rounded-lg px-md py-xs focus:ring-2 focus:ring-primary/20 outline-none bg-surface" placeholder="Ada pertanyaan?"></textarea>
          </div>
          <button name="book_tour" type="submit" class="w-full bg-primary text-on-primary py-md rounded-xl font-title-md hover:bg-primary-container transition-all flex items-center justify-center gap-md shadow-md">
            <span class="material-symbols-outlined">calendar_month</span> Book Tour Sekarang
          </button>
        </form>
        <?php endif; ?>
        <?php else: ?>
        <div class="bg-error-container text-on-error-container p-md rounded-xl text-center font-bold">
          Kamar sedang <?= $kamar['status'] ?>
        </div>
        <?php endif; ?>

        <p class="text-center text-label-sm text-on-surface-variant mt-md">
          Pertanyaan? <a class="text-primary underline font-bold" href="/kost_simbah/index.php#kontak">Hubungi kami</a>
        </p>
      </div>
    </div>
  </section>
</main>

<!-- Footer -->
<footer class="bg-primary text-on-primary w-full mt-xl">
  <div class="w-full py-lg px-md flex flex-col md:flex-row justify-between items-center max-w-container-max mx-auto">
    <span class="font-title-md text-on-primary">Kost Simbah</span>
    <p class="text-on-primary/60 font-label-sm text-label-sm">© <?= date('Y') ?> Kost Simbah. All rights reserved.</p>
  </div>
</footer>
</body>
</html>
