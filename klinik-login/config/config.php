<?php
// config/config.php

// Amankan definisi alamat utama proyek di browser
if (!defined('BASE_URL')) {
    define('BASE_URL', 'http://localhost/klinik-login/'); 
}

// Amankan definisi path direktori Windows server
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__) . '/'); 
}
?>