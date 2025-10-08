<?php
header('Content-Type: application/json');

if (!isset($_GET['kod']) || !isset($_GET['tarih'])) {
    http_response_code(400);
    echo json_encode(['hata' => 'Kod ve tarih parametreleri gereklidir.']);
    exit;
}

$kod = $_GET['kod'];
$tarih = $_GET['tarih'];

function array_search_multidim($array, $column, $key){
    $column_data = is_array($array) ? array_column($array, $column) : [];
    return array_search($key, $column_data);
}

$veri = @file_get_contents("https://vd.mackolik.com/livedata?date=" . urlencode($tarih) . "&_=" . time());

if ($veri === false) {
    http_response_code(500);
    echo json_encode(['hata' => 'Veri kaynağına ulaşılamadı.']);
    exit;
}

$dizi = json_decode($veri, true);

if (!isset($dizi['m']) || !is_array($dizi['m'])) {
    http_response_code(404);
    echo json_encode(['hata' => 'Maç verisi bulunamadı.']);
    exit;
}

$mac_index = array_search_multidim($dizi['m'], 14, $kod);

if ($mac_index === false) {
    http_response_code(404);
    echo json_encode(['hata' => 'Belirtilen maça ait veri bulunamadı.']);
    exit;
}
    
$mac_detay = $dizi['m'][$mac_index];

echo json_encode($mac_detay);
?>