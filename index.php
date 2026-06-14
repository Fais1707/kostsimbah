<?php
session_start();
require_once 'config/db.php';

$pageTitle = 'Kost Simbah - Hunian Nyaman & Strategis';

// Ambil kamar tersedia untuk ditampilkan
$kamar_result = $conn->query("SELECT * FROM kamar ORDER BY status='Tersedia' DESC, harga ASC LIMIT 6");
$kamar_list = $kamar_result->fetch_all(MYSQLI_ASSOC);

// Ambil kamar tersedia untuk dropdown form booking
$kamar_dropdown = $conn->query("SELECT id, nomor_kamar, nama, harga FROM kamar WHERE status='Tersedia' ORDER BY nomor_kamar")->fetch_all(MYSQLI_ASSOC);

// Handle form kontak
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kirim_pesan'])) {
    $nama        = $conn->real_escape_string(trim($_POST['nama']));
    $no_hp       = $conn->real_escape_string(trim($_POST['no_hp']));
    $email       = $conn->real_escape_string(trim($_POST['email'] ?? ''));
    $kamar_id    = !empty($_POST['kamar_id']) ? (int)$_POST['kamar_id'] : 'NULL';
    $tgl_tour    = $conn->real_escape_string($_POST['tanggal_tour'] ?? '');
    $pesan       = $conn->real_escape_string(trim($_POST['pesan'] ?? ''));
    if ($nama && $no_hp) {
        $conn->query("INSERT INTO booking (nama, no_hp, email, kamar_id, tanggal_tour, pesan, status)
                      VALUES ('$nama','$no_hp','$email',$kamar_id," . ($tgl_tour ? "'$tgl_tour'" : 'NULL') . ",'$pesan','Pending')");
        $msg = 'success';
    } else {
        $msg = 'error';
    }
}

include 'config/head.php';
?>

<!-- TopAppBar -->
<header class="fixed top-0 w-full z-50 bg-surface/80 backdrop-blur-md border-b border-outline-variant/30 shadow-sm">
  <div class="max-w-container-max mx-auto px-sm md:px-md flex justify-between items-center h-16">
    <div class="flex items-center gap-xs">
      <img src="assets/img/hijau_nobg.png" alt="Kost Simbah" style="width:50px">
      <span class="text-title-md font-bold text-primary">Kost Simbah</span>
    </div>
    <nav class="hidden md:flex items-center gap-md">
      <a class="text-on-surface-variant hover:text-primary transition-colors text-label-md" href="#home">Home</a>
      <a class="text-on-surface-variant hover:text-primary transition-colors font-label-md text-label-md" href="#tentang">Tentang</a>
      <a class="text-on-surface-variant hover:text-primary transition-colors font-label-md text-label-md" href="#kamar">Kamar</a>
      <a class="text-on-surface-variant hover:text-primary transition-colors font-label-md text-label-md" href="#kontak">Kontak</a>
      <a class="bg-primary text-on-primary px-sm py-xs rounded-lg font-bold hover:opacity-90 transition-all" href="/kost_simbah/login.php">Login Admin</a>
    </nav>
    <button class="md:hidden text-primary" onclick="document.getElementById('mobile-menu').classList.toggle('hidden')">
      <span class="material-symbols-outlined">menu</span>
    </button>
  </div>
  <!-- Mobile Menu -->
  <div id="mobile-menu" class="hidden md:hidden bg-surface border-t border-outline-variant/20 px-sm py-md flex flex-col gap-md">
    <a class="text-primary font-bold" href="#home">Home</a>
    <a class="text-on-surface-variant" href="#tentang">Tentang</a>
    <a class="text-on-surface-variant" href="#kamar">Kamar</a>
    <a class="text-on-surface-variant" href="#kontak">Kontak</a>
    <a class="bg-primary text-on-primary px-sm py-xs rounded-lg font-bold text-center" href="/kost_simbah/login.php">Login Admin</a>
  </div>
</header>

<!-- Hero Section -->
<section class="relative h-screen flex items-center justify-center overflow-hidden" id="home">
  <div class="absolute inset-0 z-0">
    <img class="w-full h-full object-cover hero-zoom" src="assets/img/heroimg.jpg" alt="Kost Simbah Hero"/>
    <div class="absolute inset-0" style="background:linear-gradient(to bottom,rgba(1,45,29,0.4),rgba(1,45,29,0.7))"></div>
  </div>
  <div class="relative z-10 text-center px-sm max-w-4xl">
    <h1 class="font-display-lg text-display-lg text-white mb-sm drop-shadow-lg reveal">Kost Simbah</h1>
    <p class="font-body-lg text-body-lg text-surface-container-low mb-lg max-w-2xl mx-auto reveal">
      Lebih dari sekadar tempat tinggal. Hadir dengan kenyamanan, keamanan, dan lokasi yang memudahkan setiap aktivitas Anda.
    </p>
    <div class="flex flex-col sm:flex-row gap-md justify-center items-center reveal-zoom">
      <a class="bg-primary text-on-primary px-xl py-md rounded-full font-bold text-lg hover:bg-primary-container transition-all flex items-center gap-xs shadow-lg" href="#kamar">
        Lihat Kamar <span class="material-symbols-outlined">arrow_forward</span>
      </a>
      <a class="bg-white/20 backdrop-blur-md border border-white/40 text-white px-xl py-md rounded-full font-bold text-lg hover:bg-white/30 transition-all" href="#kontak">
        Hubungi Kami
      </a>
    </div>
  </div>
</section>

<!-- Tentang Section -->
<section class="py-xl bg-surface" id="tentang">
  <div class="max-w-container-max mx-auto px-sm md:px-md">
    <div class="text-center mb-xl reveal">
      <span class="text-primary font-bold tracking-widest uppercase text-label-sm">KEUNGGULAN KAMI</span>
      <h2 class="font-headline-lg text-headline-lg text-primary mt-xs">Mengapa Memilih Kost Simbah?</h2>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-lg">
      <?php
      $fitur = [
        ['icon'=>'distance','title'=>'Lokasi Strategis','desc'=>'Terletak di pusat area residensial yang tenang namun dekat dengan akses jalan utama.'],
        ['icon'=>'school','title'=>'Dekat Kampus','desc'=>'Hanya 5 menit berkendara atau 10 menit jalan kaki menuju gerbang utama universitas.'],
        ['icon'=>'wifi','title'=>'WiFi Cepat','desc'=>'Akses internet dedicated berkecepatan tinggi di setiap kamar untuk mendukung produktivitas.'],
        ['icon'=>'local_parking','title'=>'Parkiran Luas','desc'=>'Tersedia area parkir motor dan mobil yang luas serta teduh dengan pengawasan CCTV.'],
        ['icon'=>'security','title'=>'Keamanan 24 Jam','desc'=>'Keamanan terjamin dengan penjagaan 24 jam dan akses masuk sistem sidik jari/kartu.'],
        ['icon'=>'eco','title'=>'Lingkungan Nyaman','desc'=>'Suasana asri dengan banyak tanaman hijau untuk kualitas udara dan kenyamanan maksimal.'],
      ];
      foreach($fitur as $f): ?>
      <div class="p-lg bg-surface-container-low rounded-xl border border-outline-variant/20 hover:shadow-md transition-all group reveal">
        <div class="w-12 h-12 bg-primary-fixed rounded-lg flex items-center justify-center mb-md group-hover:scale-110 transition-transform">
          <span class="material-symbols-outlined text-primary text-3xl"><?= $f['icon'] ?></span>
        </div>
        <h3 class="font-title-md text-title-md text-primary mb-xs"><?= $f['title'] ?></h3>
        <p class="text-on-surface-variant font-body-md"><?= $f['desc'] ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Daftar Kamar -->
<section class="py-xl bg-surface-container-low" id="kamar">
  <div class="max-w-container-max mx-auto px-sm md:px-md">
    <div class="flex flex-col md:flex-row justify-between items-end mb-xl gap-md reveal">
      <div>
        <span class="text-primary font-bold tracking-widest uppercase text-label-sm">PILIHAN HUNIAN</span>
        <h2 class="font-headline-lg text-headline-lg text-primary mt-xs">Daftar Kamar Tersedia</h2>
      </div>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-lg">
      <?php foreach($kamar_list as $k):
        $fasilitas = json_decode($k['fasilitas'] ?? '[]', true);
        $status_color = $k['status'] === 'Tersedia' ? 'bg-secondary-container text-on-secondary-container' : 'bg-error-container text-on-error-container';
      ?>
      <div class="bg-surface rounded-xl overflow-hidden shadow-sm hover:shadow-md transition-all border border-outline-variant/20 reveal-zoom">
        <div class="relative h-64 bg-surface-container">
          <?php if($k['foto']): ?>
            <img class="w-full h-full object-cover" src="<?= htmlspecialchars($k['foto']) ?>" alt="<?= htmlspecialchars($k['nama']) ?>"/>
          <?php else: ?>
            <div class="w-full h-full flex items-center justify-center bg-surface-container-low">
              <span class="material-symbols-outlined text-outline text-6xl">bed</span>
            </div>
          <?php endif; ?>
          <div class="absolute bottom-4 left-4 right-4 glass-card py-xs px-sm rounded-lg flex justify-between items-center">
            <span class="text-primary font-bold"><?= formatRupiah($k['harga']) ?> / bln</span>
            <span class="<?= $status_color ?> text-[10px] uppercase font-bold px-xs py-[2px] rounded"><?= $k['status'] ?></span>
          </div>
        </div>
        <div class="p-md">
          <h3 class="font-title-md text-title-md text-primary mb-xs"><?= htmlspecialchars($k['nama']) ?></h3>
          <div class="flex flex-wrap gap-xs mb-md">
            <?php foreach(array_slice($fasilitas, 0, 3) as $fas): ?>
            <span class="text-on-surface-variant text-label-sm flex items-center gap-[4px]">
              <span class="material-symbols-outlined text-[16px]">check</span> <?= htmlspecialchars($fas) ?>
            </span>
            <?php endforeach; ?>
          </div>
          <?php if($k['status'] === 'Tersedia'): ?>
          <a href="/kost_simbah/kamar.php?id=<?= $k['id'] ?>" class="block w-full py-xs bg-primary-fixed text-on-primary-fixed-variant font-bold rounded-lg hover:bg-primary-fixed-dim transition-colors text-center">
            Detail Kamar
          </a>
          <?php else: ?>
          <button class="w-full py-xs border border-outline text-outline font-bold rounded-lg cursor-not-allowed"><?= $k['status'] ?></button>
          <?php endif; ?>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Testimoni -->
<section class="py-xl bg-surface" id = "testimoni">
  <div class="max-w-container-max mx-auto px-sm md:px-md">
    <div class="text-center mb-xl reveal">
      <h2 class="font-headline-lg text-headline-lg text-primary">Apa Kata Mereka?</h2>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-md">
      <?php
      $testimoni = [
        ['nama'=>'Irfan Yudistira','profesi'=>'Mahasiswa Teknik','initial'=>'I','isi'=>'"JOSSS JISSS!! Kamar nyaman, fasilitas lengkap, dan lokasi strategis!"','featured'=>true],
        ['nama'=>'Hilal Ramadhan','profesi'=>'Mahasiswi Kedokteran','initial'=>'H','isi'=>'"Dapet makan Gratis tiap minggu!"','featured'=>true],
        ['nama'=>'Ardian Ginting','profesi'=>'Karyawan Swasta','initial'=>'A','isi'=>'"Wenak polll.. tempatnya nyamann dan juga aman banget buat tempat tinggal!"','featured'=>true],
      ];
      foreach($testimoni as $t): ?>
      <div class="p-md bg-surface-container rounded-xl shadow-sm italic reveal-left cursor-pointer transition-all duration-300 hover:shadow-lg hover:-translate-y-1 hover:bg-surface-container-high <?= ($t['featured']??false) ? 'border-l-4 border-primary hover:border-primary' : 'hover:border-l-4 hover:border-primary-fixed-dim' ?>">
        <p class="text-on-surface-variant mb-md font-body-md"><?= $t['isi'] ?></p>
        <div class="flex items-center gap-xs">
          <div class="w-10 h-10 rounded-full bg-primary-fixed flex items-center justify-center font-bold text-primary"><?= $t['initial'] ?></div>
          <div>
            <p class="font-bold text-primary text-label-md"><?= $t['nama'] ?></p>
            <p class="text-[12px] text-outline"><?= $t['profesi'] ?></p>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<!-- Kontak Section -->
<section class="py-xl bg-primary text-on-primary" id="kontak">
  <div class="max-w-container-max mx-auto px-sm md:px-md">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-xl">
      <div class="reveal-left">
        <h2 class="font-display-lg text-headline-lg mb-md">Tertarik Bergabung Bersama Kami?</h2>
        <p class="font-body-lg text-on-primary/80 mb-lg">Silakan hubungi kami untuk cek ketersediaan kamar atau sekadar bertanya mengenai fasilitas.</p>
        <div class="space-y-md">
          <a href="https://maps.app.goo.gl/72ipoJTEZ32wnCpe7" target="_blank" class="flex items-center gap-md hover:opacity-80 transition-opacity cursor-pointer"><span class="material-symbols-outlined text-primary-fixed-dim">location_on</span>
            <p>Blulukan, Kec. Colomadu, Kabupaten Karanganyar, Jawa Tengah 57174</p></a>
          <a href="https://wa.me/6289653557426" target="_blank" class="flex items-center gap-md hover:opacity-80 transition-opacity cursor-pointer"><span class="material-symbols-outlined text-primary-fixed-dim">call</span>
            <p> +62 896-5355-7426</p></a>
          <a href="mailto:arsipbayu05.com" target="_blank" class="flex items-center gap-md hover:opacity-80 transition-opacity cursor-pointer"><span class="material-symbols-outlined text-primary-fixed-dim">mail</span>
            <p>kostsimbah@gmail.com</p></a>
        </div>
      </div>
      <div class="bg-white/10 p-lg rounded-xl backdrop-blur-md border border-white/20 reveal-right">
        <?php if($msg === 'success'): ?>
        <div class="bg-primary-fixed text-on-primary-fixed p-md rounded-lg mb-md text-center font-bold">
          Pesan terkirim! Kami akan segera menghubungi Anda.
        </div>
        <?php elseif($msg === 'error'): ?>
        <div class="bg-error-container text-on-error-container p-md rounded-lg mb-md text-center font-bold">
          Mohon isi nama dan nomor WhatsApp.
        </div>
        <?php endif; ?>
        <form method="POST" class="space-y-md">
          <div>
            <label class="block text-label-md mb-xs">Nama Lengkap*</label>
            <input name="nama" type="text" required class="w-full bg-white/10 border border-white/20 rounded-lg px-md py-xs focus:ring-2 focus:ring-primary-fixed-dim outline-none text-white placeholder:text-white/50" placeholder="Nama Anda"/>
          </div>
          <div class="grid grid-cols-1 sm:grid-cols-2 gap-md">
            <div>
              <label class="block text-label-md mb-xs">Nomor WhatsApp*</label>
              <input name="no_hp" type="tel" required class="w-full bg-white/10 border border-white/20 rounded-lg px-md py-xs focus:ring-2 focus:ring-primary-fixed-dim outline-none text-white placeholder:text-white/50" placeholder="08xxxxxxxxxx"/>
            </div>
            <div>
              <label class="block text-label-md mb-xs">Email</label>
              <input name="email" type="email" class="w-full bg-white/10 border border-white/20 rounded-lg px-md py-xs focus:ring-2 focus:ring-primary-fixed-dim outline-none text-white placeholder:text-white/50" placeholder="email@contoh.com"/>
            </div>
          </div>
          <div>
            <label class="block text-label-md mb-xs">Kamar yang Diminati</label>
            <select name="kamar_id" class="w-full bg-white/10 border border-white/20 rounded-lg px-md py-xs focus:ring-2 focus:ring-primary-fixed-dim outline-none text-white">
              <option value="" class="text-on-surface">-- Pilih Kamar (opsional) --</option>
              <?php foreach($kamar_dropdown as $k): ?>
              <option value="<?= $k['id'] ?>" class="text-on-surface"><?= htmlspecialchars($k['nomor_kamar'].' - '.$k['nama']) ?> (<?= formatRupiah($k['harga']) ?>/bln)</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="block text-label-md mb-xs">Tanggal Survey</label>
            <input name="tanggal_tour" type="date" min="<?= date('Y-m-d') ?>" class="w-full bg-white/10 border border-white/20 rounded-lg px-md py-xs focus:ring-2 focus:ring-primary-fixed-dim outline-none text-white"/>
          </div>
          <div>
            <label class="block text-label-md mb-xs">Pesan / Pertanyaan</label>
            <textarea name="pesan" rows="3" class="w-full bg-white/10 border border-white/20 rounded-lg px-md py-xs focus:ring-2 focus:ring-primary-fixed-dim outline-none text-white placeholder:text-white/50" placeholder="Tulis pesan Anda..."></textarea>
          </div>
          <button name="kirim_pesan" type="submit" class="w-full bg-primary-fixed text-on-primary-fixed py-md rounded-lg font-bold hover:opacity-90 transition-all">
            Kirim Pesan
          </button>
        </form>
      </div>
    </div>
  </div>
</section>

<!-- Footer -->
<footer class="bg-primary-container text-on-primary-container py-lg md:mb-0 mb-20">
  <div class="max-w-container-max mx-auto px-sm md:px-md flex flex-col md:flex-row justify-between items-center gap-lg">
    <div class="flex items-center gap-xs">
      <img src="assets/img/putih_nobg.png" alt="Kost Simbah" style="width:50px">
      <span class="text-title-md font-bold text-white">Kost Simbah</span>
    </div>
    <div class="flex gap-md font-body-md">
      <a class="hover:text-white transition-colors" href="#tentang">Tentang Kami</a>
      <a class="hover:text-white transition-colors" href="#testimoni">Testimoni</a>
      <a class="hover:text-white transition-colors" href="#kontak">Contact</a>
    </div>
    <p class="text-on-primary-container/60 text-label-sm">© <?= date('Y') ?> Kost Simbah. All rights reserved.</p>
  </div>
</footer>

<!-- Bottom Nav Mobile -->
<nav class="md:hidden fixed bottom-0 left-0 w-full z-50 bg-surface/80 backdrop-blur-md border-t border-outline-variant/20 h-16 flex justify-around items-center px-sm shadow-lg rounded-t-xl">
  <a class="flex flex-col items-center text-on-surface-variant-xl px-4 py-1" href="#home">
    <span class="material-symbols-outlined">home</span><span class="text-[10px] font-bold">Home</span>
  </a>
  <a class="flex flex-col items-center text-on-surface-variant px-4 py-1" href="#kamar">
    <span class="material-symbols-outlined">bed</span><span class="text-[10px] font-bold">Kamar</span>
  </a>
  <a class="flex flex-col items-center text-on-surface-variant px-4 py-1" href="#kontak">
    <span class="material-symbols-outlined">call</span><span class="text-[10px] font-bold">Kontak</span>
  </a>
  <a class="flex flex-col items-center text-on-surface-variant px-4 py-1" href="/kost_simbah/login.php">
    <span class="material-symbols-outlined">person</span><span class="text-[10px] font-bold">Admin</span>
  </a>
</nav>
<script>
  // Tutup menu mobile saat salah satu link di dalam menu diklik
  (function(){
    var mobileMenu = document.getElementById('mobile-menu');
    if (!mobileMenu) return;
    var links = mobileMenu.querySelectorAll('a');
    links.forEach(function(a){
      a.addEventListener('click', function(){
        // sembunyikan menu (menggunakan kelas Tailwind yang sudah ada)
        mobileMenu.classList.add('hidden');
      });
    });
  })();
</script>
</body>
</html>
