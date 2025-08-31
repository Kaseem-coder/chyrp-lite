<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    require 'includes/common.php';
    echo "Chyrp loaded successfully.<br>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
