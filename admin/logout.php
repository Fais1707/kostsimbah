<?php
// Hancurkan sesi admin yang sedang aktif lalu redirect ke halaman login
session_start();
session_destroy();
header('Location: /kost_simbah/login.php');
exit;
