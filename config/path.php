<?php
// path.php
define('BASE_PATH', realpath(__DIR__ . '/..')); // naik satu folder dari /config
define('INCLUDES_PATH', BASE_PATH . '/includes');
define('DB_PATH', BASE_PATH . '/koneksi.php');
define('HELPER_PATH', BASE_PATH . '/helpers.php');
?>
