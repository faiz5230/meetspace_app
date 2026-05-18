<?php
require_once __DIR__ . '/../includes/functions.php'; require_login();
$uid=current_user()['id'];
$stmt=$pdo->prepare("SELECT * FROM notifications WHERE user_id=? AND is_read=0 ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$uid]); $items=$stmt->fetchAll();
if($items){
  $ids=array_column($items,'id');
  $pdo->query("UPDATE notifications SET is_read=1 WHERE id IN (".implode(',', array_map('intval',$ids)).")");
}
header('Content-Type: application/json'); echo json_encode(['items'=>$items]);
?>
