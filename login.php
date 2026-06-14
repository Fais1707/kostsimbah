<?php
session_start();
require_once 'config/db.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

if (isAdminLoggedIn() && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    session_unset();
    session_destroy();
    session_start();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $conn->prepare("SELECT * FROM admin WHERE email = ? LIMIT 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $admin = $stmt->get_result()->fetch_assoc();

if ($admin && $password === $admin['password']) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_nama'] = $admin['nama'];
        $_SESSION['admin_role'] = $admin['role'];
        header('Location: /kost_simbah/admin/dashboard.php');
        exit;
    } else {
        $error = 'Email atau password salah.';
    }
}

$pageTitle = 'Login Admin - Kost Simbah';
include 'config/head.php';
?>

<div class="min-h-screen flex items-center justify-center px-sm bg-surface-container-low">
  <div class="w-full max-w-md">
    <div class="text-center mb-lg">
      <div class="inline-flex items-center justify-center w-16 h-16 bg-primary rounded-2xl mb-md">
        <img src="assets/img/putih_nobg.png" alt="Kost Simbah" style="width:80px">
      </div>
      <h1 class="font-headline-lg text-headline-lg text-primary">Kost Simbah</h1>
      <p class="text-on-surface-variant font-body-md mt-xs">Login sebagai admin</p>
    </div>

    <div class="bg-surface p-lg rounded-2xl shadow-sm border border-outline-variant/20">
      <?php if($error): ?>
      <div class="bg-error-container text-on-error-container p-md rounded-lg mb-md text-center font-label-md">
        <?= htmlspecialchars($error) ?>
      </div>
      <?php endif; ?>

      <form method="POST" class="space-y-md">
        <div>
          <label class="block font-label-md text-label-md text-on-surface-variant mb-xs">Email</label>
          <div class="relative">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline">mail</span>
            <input name="email" type="email" required
              class="w-full pl-10 pr-4 py-3 border border-outline-variant/50 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none bg-surface-container-low font-body-md"
              placeholder="admin@kostsimbah.com"
              value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"/>
          </div>
        </div>
        <div>
          <label class="block font-label-md text-label-md text-on-surface-variant mb-xs">Password</label>
          <div class="relative">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline">lock</span>
            <input name="password" type="password" required
              class="w-full pl-10 pr-4 py-3 border border-outline-variant/50 rounded-xl focus:ring-2 focus:ring-primary/20 focus:border-primary outline-none bg-surface-container-low font-body-md"
              placeholder="••••••••"/>
          </div>
        </div>
        <button type="submit" class="w-full bg-primary text-on-primary py-md rounded-xl font-bold hover:opacity-90 transition-all shadow-sm">
          Masuk ke Dashboard
        </button>
      </form>
    </div>

    <div class="text-center mt-md">
      <a href="/kost_simbah/index.php" class="text-primary font-label-md hover:underline flex items-center justify-center gap-xs">
        <span class="material-symbols-outlined text-[18px]">arrow_back</span> Kembali ke Website
      </a>
    </div>
  </div>
</div>
</body>
</html>
