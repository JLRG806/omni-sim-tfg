<?php

// Suprime E_DEPRECATED antes de cargar vendor — evita ruido de PDO::MYSQL_ATTR_SSL_CA
// en vendor/laravel/framework/config/database.php con PHP 8.5 hasta que Laravel lo corrija.
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);

require __DIR__ . '/../vendor/autoload.php';
