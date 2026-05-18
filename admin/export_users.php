<?php
require_once __DIR__ . '/../includes/functions.php'; require_admin();
$type=$_GET['type'] ?? 'excel';
$q=trim($_GET['q'] ?? ''); $status=$_GET['status'] ?? ''; $dept=$_GET['department_id'] ?? '';
$where=["1=1"]; $params=[];
if($q){$where[]="(u.name LIKE ? OR u.email LIKE ?)";$params[]="%$q%";$params[]="%$q%";}
if($status){$where[]="u.status=?";$params[]=$status;}
if($dept){$where[]="u.department_id=?";$params[]=$dept;}
$stmt=$pdo->prepare("SELECT u.name,u.email,u.role,u.status,d.name department FROM users u LEFT JOIN departments d ON d.id=u.department_id WHERE ".implode(" AND ",$where)." ORDER BY u.name");
$stmt->execute($params); $rows=$stmt->fetchAll();
audit_log('EXPORT_USERS', 'Export user '.$type);

if($type==='pdf'){
    header('Content-Type: text/html');
    echo "<script>window.print()</script><h2>Export Data User</h2><table border='1' cellpadding='8' cellspacing='0'><tr><th>Nama</th><th>Email</th><th>Departemen</th><th>Role</th><th>Status</th></tr>";
    foreach($rows as $r) echo "<tr><td>".e($r['name'])."</td><td>".e($r['email'])."</td><td>".e($r['department'])."</td><td>".e($r['role'])."</td><td>".e($r['status'])."</td></tr>";
    echo "</table>";
    exit;
}
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="users_export.csv"');
$out=fopen('php://output','w');
fputcsv($out,['Nama','Email','Departemen','Role','Status']);
foreach($rows as $r) fputcsv($out,[$r['name'],$r['email'],$r['department'],$r['role'],$r['status']]);
fclose($out);
exit;
?>
