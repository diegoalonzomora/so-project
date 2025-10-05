<?php
include 'db.php';
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Accept');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

function response($d,$c=200){ http_response_code($c); echo json_encode($d, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT); exit; }
function body(){
  $ct = $_SERVER['CONTENT_TYPE'] ?? '';
  if (stripos($ct,'application/json')!==false) { $j=json_decode(file_get_contents('php://input'),true); return is_array($j)?$j:[]; }
  return $_POST;
}

// GET (uno o lista con joins útiles)
if ($_SERVER['REQUEST_METHOD']==='GET'){
  if (isset($_GET['idReserva']) && isset($_GET['idServicioAdicional'])) {
    $idR=intval($_GET['idReserva']);
    $idS=intval($_GET['idServicioAdicional']);
    $stmt=$conn->prepare("
      SELECT ar.idReserva, ar.idServicioAdicional, sa.nombre, sa.precioAdicional
      FROM AdicionalReserva ar
      INNER JOIN ServiciosAdicionales sa ON sa.idServicioAdicional = ar.idServicioAdicional
      WHERE ar.idReserva=? AND ar.idServicioAdicional=?
    ");
    $stmt->bind_param("ii",$idR,$idS);
    $stmt->execute();
    $row=$stmt->get_result()->fetch_assoc();
    response($row?:["error"=>"No encontrado"], $row?200:404);
  }

  $sql="
    SELECT ar.idReserva, ar.idServicioAdicional, sa.nombre, sa.precioAdicional
    FROM AdicionalReserva ar
    INNER JOIN ServiciosAdicionales sa ON sa.idServicioAdicional = ar.idServicioAdicional
  ";
  $rs=$conn->query($sql);
  if(!$rs) response(["error"=>$conn->error],500);
  response($rs->fetch_all(MYSQLI_ASSOC));
}

// POST (agregar adicional a reserva)
if ($_SERVER['REQUEST_METHOD']==='POST'){
  $d = body();
  $req=['idReserva','idServicioAdicional'];
  foreach($req as $k){ if(!isset($d[$k]) || $d[$k]==='') response(["error"=>"Falta campo: $k"],422); }

  $idReserva=intval($d['idReserva']);
  $idServicioAdicional=intval($d['idServicioAdicional']);

  try{
    $conn->begin_transaction();

    // Existen?
    $chk=$conn->prepare("SELECT 1 FROM Reserva WHERE idReserva=?");
    $chk->bind_param("i",$idReserva); $chk->execute();
    if($chk->get_result()->num_rows===0){ $conn->rollback(); response(["error"=>"Reserva no existe"],422); }

    $chk=$conn->prepare("SELECT 1 FROM ServiciosAdicionales WHERE idServicioAdicional=?");
    $chk->bind_param("i",$idServicioAdicional); $chk->execute();
    if($chk->get_result()->num_rows===0){ $conn->rollback(); response(["error"=>"Servicio adicional no existe"],422); }

    // Evitar duplicados (PK compuesta)
    $dup=$conn->prepare("SELECT 1 FROM AdicionalReserva WHERE idReserva=? AND idServicioAdicional=?");
    $dup->bind_param("ii",$idReserva,$idServicioAdicional);
    $dup->execute();
    if($dup->get_result()->num_rows>0){ $conn->rollback(); response(["error"=>"Ya agregado"],409); }

    // Insert
    $stmt=$conn->prepare("INSERT INTO AdicionalReserva (idServicioAdicional, idReserva) VALUES (?, ?)");
    $stmt->bind_param("ii", $idServicioAdicional, $idReserva);
    if(!$stmt->execute()){ $conn->rollback(); response(["error"=>$stmt->error],500); }

    $conn->commit();

    // Devolver fila unida
    $sel=$conn->prepare("
      SELECT ar.idReserva, ar.idServicioAdicional, sa.nombre, sa.precioAdicional
      FROM AdicionalReserva ar
      INNER JOIN ServiciosAdicionales sa ON sa.idServicioAdicional = ar.idServicioAdicional
      WHERE ar.idReserva=? AND ar.idServicioAdicional=?
    ");
    $sel->bind_param("ii",$idReserva,$idServicioAdicional);
    $sel->execute();
    $row=$sel->get_result()->fetch_assoc();
    response(["message"=>"Adicional agregado","data"=>$row],201);

  }catch(Throwable $e){
    if ($conn->errno) $conn->rollback();
    response(["error"=>$e->getMessage()],500);
  }
}

response(["error"=>"Método no permitido"],405);
