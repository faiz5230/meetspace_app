<?php
require_once __DIR__ . '/../includes/header.php'; require_admin();
if($_SERVER['REQUEST_METHOD']==='POST'){
  $action=$_POST['action']; $id=(int)$_POST['id'];
  $stmt=$pdo->prepare("SELECT b.*, u.email, u.name user_name, r.name room_name FROM bookings b JOIN users u ON u.id=b.user_id JOIN rooms r ON r.id=b.room_id WHERE b.id=?");
  $stmt->execute([$id]); $b=$stmt->fetch();
  if(!$b) redirect('bookings.php');
  if($action==='approve'){
    $pdo->prepare("UPDATE bookings SET status='approved', approved_by=?, approved_at=NOW() WHERE id=?")->execute([$me['id'],$id]);
    notify_user($b['user_id'],'Booking Disetujui','Booking '.$b['title'].' telah disetujui.','success');
    send_email_log($b['email'],'Booking MeetSpace Disetujui','Booking <b>'.e($b['title']).'</b> telah disetujui.');
    audit_log('APPROVE_BOOKING','Approve booking ID '.$id);
  }
  if($action==='reject'){
    $pdo->prepare("UPDATE bookings SET status='rejected', rejection_reason=?, approved_by=?, approved_at=NOW() WHERE id=?")->execute([$_POST['reason'],$me['id'],$id]);
    notify_user($b['user_id'],'Booking Ditolak','Booking '.$b['title'].' ditolak: '.$_POST['reason'],'danger');
    send_email_log($b['email'],'Booking MeetSpace Ditolak','Booking <b>'.e($b['title']).'</b> ditolak. Alasan: '.e($_POST['reason']));
    audit_log('REJECT_BOOKING','Reject booking ID '.$id);
  }
  if($action==='cancel'){
    $pdo->prepare("UPDATE bookings SET status='cancelled', cancel_reason=?, cancelled_by=?, cancelled_at=NOW() WHERE id=?")->execute([$_POST['reason'],$me['id'],$id]);
    notify_user($b['user_id'],'Booking Dibatalkan','Booking '.$b['title'].' dibatalkan: '.$_POST['reason'],'warning');
    send_email_log($b['email'],'Booking MeetSpace Dibatalkan','Booking <b>'.e($b['title']).'</b> dibatalkan. Alasan: '.e($_POST['reason']));
    audit_log('CANCEL_BOOKING','Cancel booking ID '.$id);
  }
  if($action==='reschedule'){
    $room_id=$_POST['room_id']; $date=$_POST['booking_date']; $start=$_POST['start_time']; $end=$_POST['end_time'];
    $conf=conflict_booking($room_id,$date,$start,$end,$id);
    if(!$conf){
      $pdo->prepare("UPDATE bookings SET room_id=?, booking_date=?, start_time=?, end_time=?, attendees=?, rescheduled_by=?, rescheduled_at=NOW(), reschedule_note=?, updated_at=NOW() WHERE id=?")
          ->execute([$room_id,$date,$start,$end,$_POST['attendees'],$me['id'],$_POST['note'],$id]);
      notify_user($b['user_id'],'Booking Dijadwalkan Ulang','Booking '.$b['title'].' telah di-reschedule.','info');
      send_email_log($b['email'],'Booking MeetSpace Reschedule','Booking <b>'.e($b['title']).'</b> telah dijadwalkan ulang.');
      audit_log('RESCHEDULE_BOOKING','Reschedule booking ID '.$id);
    }
  }
  redirect('bookings.php');
}
$rows=$pdo->query("SELECT b.*, u.name user_name, r.name room_name FROM bookings b JOIN users u ON u.id=b.user_id JOIN rooms r ON r.id=b.room_id ORDER BY b.created_at DESC")->fetchAll();
$rooms=$pdo->query("SELECT * FROM rooms WHERE status='active' ORDER BY name")->fetchAll();
?>
<script>const BASE_URL_JS = "<?= BASE_URL ?>";</script>
<h3 class="fw-bold mb-3">Manajemen Booking</h3>
<div class="card card-soft"><div class="card-body table-responsive"><table class="table align-middle">
<thead><tr><th>Pemohon</th><th>Ruangan</th><th>Judul</th><th>Tanggal</th><th>Waktu</th><th>Status</th><th>Aksi</th></tr></thead><tbody>
<?php foreach($rows as $b): ?><tr>
<td><?= e($b['user_name']) ?></td><td><?= e($b['room_name']) ?></td><td><?= e($b['title']) ?></td><td><?= e($b['booking_date']) ?></td><td><?= substr($b['start_time'],0,5) ?>-<?= substr($b['end_time'],0,5) ?></td><td><?= status_badge($b['status']) ?></td>
<td class="text-nowrap">
<?php if($b['status']==='pending'): ?>
<form class="d-inline" method="post"><input type="hidden" name="action" value="approve"><input type="hidden" name="id" value="<?= $b['id'] ?>"><button class="btn btn-sm btn-success">Approve</button></form>
<button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#reasonModal" onclick="setReason('reject',<?= $b['id'] ?>)">Reject</button>
<?php endif; ?>
<button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#resModal" onclick='setRes(<?= json_encode($b, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'>Reschedule</button>
<button class="btn btn-sm btn-secondary" data-bs-toggle="modal" data-bs-target="#reasonModal" onclick="setReason('cancel',<?= $b['id'] ?>)">Cancel</button>
</td></tr><?php endforeach; ?>
</tbody></table></div></div>

<div class="modal fade" id="reasonModal"><div class="modal-dialog"><form class="modal-content" method="post">
<input type="hidden" name="action" id="reason_action"><input type="hidden" name="id" id="reason_id">
<div class="modal-header"><h5>Alasan</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body"><textarea class="form-control" name="reason" required></textarea></div>
<div class="modal-footer"><button class="btn btn-danger">Simpan</button></div>
</form></div></div>

<div class="modal fade" id="resModal"><div class="modal-dialog"><form class="modal-content" method="post">
<input type="hidden" name="action" value="reschedule"><input type="hidden" name="id" id="res_id">
<div class="modal-header"><h5>Reschedule Booking</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
<div class="modal-body row g-2">
<div class="col-12"><label>Ruangan</label><select class="form-select" name="room_id" id="res_room"><?php foreach($rooms as $r): ?><option value="<?= $r['id'] ?>"><?= e($r['name']) ?> - kapasitas <?= $r['capacity'] ?></option><?php endforeach; ?></select></div>
<div class="col-12"><label>Tanggal</label><input class="form-control" type="date" name="booking_date" id="res_date"></div>
<div class="col-6"><label>Mulai</label><input class="form-control" type="time" name="start_time" id="res_start"></div>
<div class="col-6"><label>Selesai</label><input class="form-control" type="time" name="end_time" id="res_end"></div>
<div class="col-12"><label>Peserta</label><input class="form-control" type="number" name="attendees" id="res_attendees"></div>
<div class="col-12"><label>Catatan</label><textarea class="form-control" name="note"></textarea></div>
</div><div class="modal-footer"><button class="btn btn-warning">Reschedule</button></div>
</form></div></div>
<script>
function setReason(a,id){reason_action.value=a;reason_id.value=id;}
function setRes(b){res_id.value=b.id;res_room.value=b.room_id;res_date.value=b.booking_date;res_start.value=b.start_time.substring(0,5);res_end.value=b.end_time.substring(0,5);res_attendees.value=b.attendees;}
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
