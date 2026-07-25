<?php
// session_config.php
// HARUS di-include SEBELUM session_start() dipanggil di halaman manapun

ini_set('session.cookie_httponly', 1);   // cookie tidak bisa dibaca lewat JavaScript
ini_set('session.cookie_secure', 0);     // ganti ke 1 kalau sudah pakai HTTPS
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_only_cookies', 1);
ini_set('session.use_trans_sid', 0);

session_name('ADMIN_SESSID');

?>