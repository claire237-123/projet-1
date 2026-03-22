<?php
// Configuration des chemins principaux
define('K_PATH_MAIN', 'D:/tcpdf/'); // Chemin vers TCPDF installé sur D:
define('K_PATH_FONTS', K_PATH_MAIN . 'fonts/');
define('K_PATH_IMAGES', ' c:\Users\User\Desktop\PROJET SOUTENANCE 2024-2025 CLAIRE\logo');
define('K_PATH_CACHE', K_PATH_MAIN . 'cache/');

// On NE redéfinit PAS les constantes déjà gérées par TCPDF

// Inclure la classe TCPDF (chargera aussi la configuration par défaut)
require_once K_PATH_MAIN . 'tcpdf.php';