<?php
require_once '../includes/config.php';
require_once '../includes/database.php';

header('Content-Type: application/json');

if (!isset($_GET['province_id']) || empty($_GET['province_id'])) {
    echo json_encode([]);
    exit;
}

$province_id = (int)$_GET['province_id'];
$db = new Database();

try {
    $cities = $db->getCitiesByProvince($province_id);
    echo json_encode($cities);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Errore nel caricamento delle città']);
}
?>