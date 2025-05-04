<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['clear_all_expired'])) {
    date_default_timezone_set('Asia/Phnom_Penh');
    $promotions = json_decode(file_get_contents('./data/promotions.json'), true);
    $current_time = new DateTime('now', new DateTimeZone('Asia/Phnom_Penh'));

    foreach ($promotions['PROMOTIONS'] as &$station) {
        $station['promotions'] = array_filter($station['promotions'], function ($promo) use ($current_time) {
            $end_time = new DateTime($promo['end_time']);
            return $end_time >= $current_time;
        });
    }

    file_put_contents('./data/promotions.json', json_encode($promotions, JSON_PRETTY_PRINT));
    echo 'Expired promotions deleted.';
}
?>