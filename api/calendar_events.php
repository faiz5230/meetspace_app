<?php
require_once __DIR__ . '/../includes/functions.php'; require_login();
$rows=$pdo->query("SELECT b.*, r.name room_name, u.name user_name FROM bookings b JOIN rooms r ON r.id=b.room_id JOIN users u ON u.id=b.user_id WHERE b.status IN ('approved','pending')")->fetchAll();
$out=[];
foreach($rows as $b){
  $out[]=[
    'title'=>$b['room_name'].' - '.$b['title'].' ('.$b['status'].')',
    'start'=>$b['booking_date'].'T'.$b['start_time'],
    'end'=>$b['booking_date'].'T'.$b['end_time'],
    'color'=>$b['status']==='approved' ? '#198754' : '#f59f00'
  ];
}
header('Content-Type: application/json'); echo json_encode($out);
?>
