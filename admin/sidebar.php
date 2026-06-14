<?php
// Deteksi halaman aktif berdasarkan nama file yang sedang dibuka
$current_page = basename($_SERVER['PHP_SELF']);

// Daftar item navigasi sidebar: link, ikon Material Symbols, label, dan nama file untuk deteksi aktif
$nav_items = [
    ['href'=>'dashboard.php',  'icon'=>'dashboard',        'label'=>'Dashboard', 'file'=>'dashboard.php'],
    ['href'=>'kamar.php',      'icon'=>'bed',              'label'=>'Rooms',     'file'=>'kamar.php'],
    ['href'=>'penghuni.php',   'icon'=>'group',            'label'=>'Tenants',   'file'=>'penghuni.php'],
    ['href'=>'booking.php',    'icon'=>'event_available',  'label'=>'Booking',   'file'=>'booking.php'],
];
?>

<!-- ═══════════════════════════════════════
     Sidebar Desktop (tampil di layar md ke atas)
     ═══════════════════════════════════════ -->
<aside class="hidden md:flex flex-col h-screen w-64 fixed left-0 top-0 bg-surface border-r border-outline-variant/20 p-md gap-xs z-40">

  <!-- Logo dan nama aplikasi -->
  <div class="flex items-center gap-2 mb-lg">
    <img src="../assets/img/hijau_nobg.png" alt="Kost Simbah" class="w-20 h-auto object-contain">
    <span class="font-headline-lg text-headline-lg text-primary font-bold">Kost Simbah</span>
  </div>

  <!-- Daftar menu navigasi utama -->
  <div class="flex flex-col gap-xs flex-grow">
    <?php foreach($nav_items as $item):
      $active = ($current_page === $item['file']); // Tandai menu aktif sesuai halaman saat ini
    ?>
    <a href="/kost_simbah/admin/<?= $item['href'] ?>"
       class="flex items-center gap-md px-md py-3 rounded-lg transition-colors
       <?= $active
           ? 'bg-primary-container text-on-primary-container font-bold' // Gaya aktif
           : 'text-on-surface-variant hover:bg-surface-variant'         // Gaya hover
       ?>">
      <span class="material-symbols-outlined"><?= $item['icon'] ?></span>
      <span class="font-label-md text-label-md"><?= $item['label'] ?></span>
    </a>
    <?php endforeach; ?>
  </div>

  <!-- Info user yang sedang login + tombol logout -->
  <div class="pt-md border-t border-outline-variant/30">
    <div class="flex items-center gap-md p-2 mb-md">
      <!-- Avatar inisial nama admin -->
      <div class="w-10 h-10 rounded-full bg-primary-fixed flex items-center justify-center font-bold text-primary">
        <?= strtoupper(substr($_SESSION['admin_nama'] ?? 'A', 0, 1)) ?>
      </div>
      <div class="overflow-hidden">
        <p class="font-label-md font-bold truncate"><?= htmlspecialchars($_SESSION['admin_nama'] ?? 'Admin') ?></p>
        <!-- Tampilkan role admin dengan format lebih rapi (underscore → spasi, kapital depan) -->
        <p class="text-xs text-on-surface-variant"><?= ucfirst(str_replace('_',' ',$_SESSION['admin_role'] ?? 'admin')) ?></p>
      </div>
    </div>
    <!-- Tombol logout -->
    <a href="/kost_simbah/admin/logout.php"
       class="flex items-center gap-md px-md py-3 text-error hover:bg-error-container/20 rounded-lg transition-colors">
      <span class="material-symbols-outlined">logout</span>
      <span class="font-label-md text-label-md">Logout</span>
    </a>
  </div>
</aside>

<!-- ═══════════════════════════════════════
     Header Mobile (tampil di layar < md)
     ═══════════════════════════════════════ -->
<header class="md:hidden fixed top-0 left-0 right-0 z-50 bg-surface border-b border-outline-variant/20 h-16 flex items-center justify-between px-4 shadow-sm">
  <!-- Logo mobile -->
  <div class="flex items-center gap-2">
    <img src="../assets/img/hijau_nobg.png" alt="Kost Simbah" class="w-10 h-10 object-contain">
    <span class="font-bold text-primary">Kost Simbah</span>
  </div>
  <!-- Tombol hamburger untuk membuka drawer navigasi mobile -->
  <button onclick="document.getElementById('mob-nav').classList.remove('hidden')" class="p-2">
    <span class="material-symbols-outlined">menu</span>
  </button>
</header>

<!-- ═══════════════════════════════════════
     Drawer Navigasi Mobile
     ═══════════════════════════════════════ -->
<div id="mob-nav" class="hidden md:hidden fixed inset-0 z-50">

  <!-- Overlay gelap, klik untuk tutup drawer -->
  <div onclick="document.getElementById('mob-nav').classList.add('hidden')" class="absolute inset-0 bg-black/50"></div>

  <!-- Panel drawer dari kiri -->
  <div class="absolute left-0 top-0 h-full w-72 bg-surface shadow-xl flex flex-col">

    <!-- Logo di dalam drawer -->
    <div class="flex items-center gap-3 p-4 border-b">
      <img src="../assets/img/hijau_nobg.png" alt="Kost Simbah" class="w-10 h-10 object-contain">
      <h2 class="font-bold text-primary">Kost Simbah</h2>
    </div>

    <!-- Daftar menu navigasi di dalam drawer -->
    <div class="flex-grow p-3 space-y-2">
      <?php foreach($nav_items as $item):
        $active = ($current_page === $item['file']);
      ?>
      <a href="/kost_simbah/admin/<?= $item['href'] ?>"
         class="flex items-center gap-3 px-4 py-3 rounded-xl
         <?= $active
             ? 'bg-primary-container text-on-primary-container font-bold'
             : 'hover:bg-surface-variant text-on-surface-variant'
         ?>">
        <span class="material-symbols-outlined"><?= $item['icon'] ?></span>
        <span><?= $item['label'] ?></span>
      </a>
      <?php endforeach; ?>
    </div>

    <!-- Info user di dalam drawer -->
    <div class="p-4 border-b">
      <div class="flex items-center gap-3">
        <div class="w-10 h-10 rounded-full bg-primary-fixed flex items-center justify-center font-bold text-primary">
          <?= strtoupper(substr($_SESSION['admin_nama'] ?? 'A', 0, 1)) ?>
        </div>
        <div>
          <p class="font-semibold"><?= htmlspecialchars($_SESSION['admin_nama'] ?? 'Admin') ?></p>
          <p class="text-xs text-on-surface-variant"><?= ucfirst(str_replace('_',' ',$_SESSION['admin_role'] ?? 'admin')) ?></p>
        </div>
      </div>
    </div>

    <!-- Tombol logout di dalam drawer -->
    <div class="p-3 border-t">
      <a href="/kost_simbah/admin/logout.php"
         class="flex items-center gap-3 px-4 py-3 rounded-xl text-error hover:bg-error-container/20">
        <span class="material-symbols-outlined">logout</span>
        <span>Logout</span>
      </a>
    </div>
  </div>
</div>
