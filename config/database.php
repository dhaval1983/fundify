<?php 



// =====================================================
// 1. CONFIG/DATABASE.PHP - Database Configuration
// =====================================================
?>

<?php
// config/database.php
return [
    'host' => 'localhost',
    'dbname' => 'isowebt1_funidfy',
    'username' => 'isowebt1_fundifyusr',
    'password' => '-xnwa}[I041Gxp,W',
    'charset' => 'utf8mb4',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
];
?>
