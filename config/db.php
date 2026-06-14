<?php
$conn = new mysqli(
    getenv('MYSQLHOST'),
    getenv('MYSQLUSER'),
    getenv('MYSQLPASSWORD'),
    getenv('MYSQLDATABASE'),
    getenv('MYSQLPORT')
);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

function requireAdmin() {
    if (!isAdminLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
}

function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

function logAktivitas($conn, $judul, $deskripsi, $tipe = 'Lainnya') {
    $stmt = $conn->prepare("INSERT INTO aktivitas (judul, deskripsi, tipe) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $judul, $deskripsi, $tipe);
    $stmt->execute();
}

function setFlash($msg) {
    $_SESSION['flash'] = $msg;
}

function getFlash() {
    $msg = $_SESSION['flash'] ?? '';
    unset($_SESSION['flash']);
    return $msg;
}

// Otomatis generate tagihan bulanan untuk semua penghuni yang belum punya tagihan bulan ini
function generateTagihanBulanan($conn) {
    // Ambil semua penghuni yang punya kamar
    $penghuni = $conn->query("
        SELECT p.id, p.nama, p.tanggal_masuk, k.id as kamar_id, k.harga
        FROM penghuni p
        JOIN kamar k ON p.kamar_id = k.id
        WHERE p.kamar_id IS NOT NULL
          AND (p.tanggal_keluar IS NULL OR p.tanggal_keluar >= CURDATE())
    ")->fetch_all(MYSQLI_ASSOC);

    foreach ($penghuni as $p) {
        // Generate tagihan dari bulan masuk sampai bulan ini
        $bulan = new DateTime(date('Y-m-01', strtotime($p['tanggal_masuk'])));
        $bulan_ini = new DateTime(date('Y-m-01'));

        while ($bulan <= $bulan_ini) {
            $bulan_str = $bulan->format('Y-m-d');
            // Cek apakah tagihan bulan ini sudah ada
            $sudah_ada = $conn->query("
                SELECT COUNT(*) as c FROM pembayaran
                WHERE penghuni_id = {$p['id']} AND bulan_bayar = '$bulan_str'
            ")->fetch_assoc()['c'];

            if ($sudah_ada == 0) {
                $conn->query("
                    INSERT INTO pembayaran (penghuni_id, kamar_id, bulan_bayar, jumlah, status)
                    VALUES ({$p['id']}, {$p['kamar_id']}, '$bulan_str', {$p['harga']}, 'Pending')
                ");
            }
            $bulan->modify('+1 month');
        }
    }

    // Tandai otomatis jadi Telat jika bulan sudah lewat dan masih Pending
    $conn->query("
        UPDATE pembayaran SET status='Telat'
        WHERE status='Pending'
          AND bulan_bayar < DATE_FORMAT(CURDATE(), '%Y-%m-01')
    ");
}
// Dijalankan setiap kali halaman admin diload
function cekPenghuniKeluar($conn) {
    // Ambil semua penghuni yang tanggal_keluar-nya sudah lewat hari ini
    $selesai = $conn->query("
        SELECT id, nama, kamar_id
        FROM penghuni
        WHERE tanggal_keluar IS NOT NULL
          AND tanggal_keluar < CURDATE()
          AND kamar_id IS NOT NULL
    ")->fetch_all(MYSQLI_ASSOC);

    foreach ($selesai as $p) {
        // Bebaskan kamar jika tidak ada penghuni lain yang masih aktif di kamar yang sama
        $masih_ada = $conn->query("
            SELECT COUNT(*) as c FROM penghuni
            WHERE kamar_id = {$p['kamar_id']}
              AND id != {$p['id']}
              AND (tanggal_keluar IS NULL OR tanggal_keluar >= CURDATE())
        ")->fetch_assoc()['c'];

        if ($masih_ada == 0) {
            $conn->query("UPDATE kamar SET status='Tersedia' WHERE id={$p['kamar_id']}");
        }

        // Lepas kamar dari data penghuni agar tidak memblokir kamar terus
        $conn->query("UPDATE penghuni SET kamar_id=NULL WHERE id={$p['id']}");

        logAktivitas($conn, 'Penghuni Keluar Otomatis', "{$p['nama']} telah selesai masa sewanya, kamar dibebaskan.", 'Lainnya');
    }
}
