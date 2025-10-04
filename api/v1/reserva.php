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

// GET ?id  (con datos útiles)
if ($_SERVER['REQUEST_METHOD']==='GET'){
  if (isset($_GET['id'])) {
    $id=intval($_GET['id']);
    $stmt=$conn->prepare("
      SELECT r.idReserva, r.idHabitacion, r.idCliente, r.fechaEntrada, r.fechaSalida, r.estadoReserva, r.idFactura,
             h.codigoHabitacion, h.tipoHabitacion,
             c.nombres, c.apellidoPaterno, c.apellidoMaterno
      FROM Reserva r
      INNER JOIN Habitacion h ON r.idHabitacion=h.idHabitacion
      INNER JOIN Cliente c ON r.idCliente=c.idCliente
      WHERE r.idReserva=?
    ");
    $stmt->bind_param("i",$id);
    $stmt->execute();
    $row=$stmt->get_result()->fetch_assoc();
    response($row?:["error"=>"Reserva no encontrada"], $row?200:404);
  }

  $sql="
    SELECT r.idReserva, r.idHabitacion, r.idCliente, r.fechaEntrada, r.fechaSalida, r.estadoReserva, r.idFactura,
           h.codigoHabitacion, h.tipoHabitacion,
           c.nombres, c.apellidoPaterno, c.apellidoMaterno
    FROM Reserva r
    INNER JOIN Habitacion h ON r.idHabitacion=h.idHabitacion
    INNER JOIN Cliente c ON r.idCliente=c.idCliente
  ";
  $rs=$conn->query($sql);
  if(!$rs) response(["error"=>$conn->error],500);
  response($rs->fetch_all(MYSQLI_ASSOC));
}

// POST (crear reserva)
if ($_SERVER['REQUEST_METHOD']==='POST'){
  $d = body();
  $req=['idHabitacion','idCliente','fechaEntrada','fechaSalida'];
  foreach($req as $k){ if(!isset($d[$k]) || $d[$k]==='') response(["error"=>"Falta campo: $k"],422); }

  $idHabitacion = intval($d['idHabitacion']);
  $idCliente    = intval($d['idCliente']);
  $fechaEntrada = $d['fechaEntrada'];
  $fechaSalida  = $d['fechaSalida'];
  $estado       = $d['estadoReserva'] ?? 'pendiente';
  $idFactura    = isset($d['idFactura']) && $d['idFactura']!=='' ? intval($d['idFactura']) : null;
  $idReserva    = isset($d['idReserva']) && $d['idReserva']!=='' ? intval($d['idReserva']) : null;

  // Validación fechas
  if (!preg_match('/^\d{4}-\d{2}-\d{2}$/',$fechaEntrada) || !preg_match('/^\d{4}-\d{2}-\d{2}$/',$fechaSalida))
    response(["error"=>"Formato de fecha inválido (YYYY-MM-DD)"],422);
  if (strtotime($fechaEntrada) >= strtotime($fechaSalida))
    response(["error"=>"fechaEntrada debe ser anterior a fechaSalida"],422);

  try{
    $conn->begin_transaction();

    // Existen habitacion/cliente?
    $chk=$conn->prepare("SELECT 1 FROM Habitacion WHERE idHabitacion=?");
    $chk->bind_param("i",$idHabitacion); $chk->execute();
    if($chk->get_result()->num_rows===0){ $conn->rollback(); response(["error"=>"Habitación no existe"],422); }

    $chk=$conn->prepare("SELECT 1 FROM Cliente WHERE idCliente=?");
    $chk->bind_param("i",$idCliente); $chk->execute();
    if($chk->get_result()->num_rows===0){ $conn->rollback(); response(["error"=>"Cliente no existe"],422); }

    if ($idFactura !== null) {
      $chk=$conn->prepare("SELECT 1 FROM Factura WHERE idFactura=?");
      $chk->bind_param("i",$idFactura); $chk->execute();
      if($chk->get_result()->num_rows===0){ $conn->rollback(); response(["error"=>"Factura no existe"],422); }
    }

    // Solapamientos en la misma habitación
    $ov=$conn->prepare("
      SELECT 1 FROM Reserva
      WHERE idHabitacion = ?
        AND NOT (fechaSalida <= ? OR fechaEntrada >= ?)
      LIMIT 1
    ");
    $ov->bind_param("iss", $idHabitacion, $fechaEntrada, $fechaSalida);
    $ov->execute();
    if ($ov->get_result()->num_rows>0){ $conn->rollback(); response(["error"=>"La habitación ya está reservada en ese rango"],409); }

    // Generar idReserva si no viene
    if ($idReserva===null){
      $r=$conn->query("SELECT COALESCE(MAX(idReserva)+1,1) AS next_id FROM Reserva FOR UPDATE");
      $idReserva=intval($r->fetch_assoc()['next_id']);
    }

    $stmt=$conn->prepare("
      INSERT INTO Reserva (idReserva, idHabitacion, idCliente, fechaEntrada, fechaSalida, estadoReserva, idFactura)
      VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    // idFactura puede ser null
    if ($idFactura===null){
      $null = null;
      $stmt->bind_param("iiisssi", $idReserva, $idHabitacion, $idCliente, $fechaEntrada, $fechaSalida, $estado, $null);
    } else {
      $stmt->bind_param("iiisssi", $idReserva, $idHabitacion, $idCliente, $fechaEntrada, $fechaSalida, $estado, $idFactura);
    }

    if(!$stmt->execute()){ $conn->rollback(); response(["error"=>$stmt->error],500); }

    $conn->commit();

    // Devolver reserva creada (con extras)
    $sel=$conn->prepare("
      SELECT r.idReserva, r.idHabitacion, r.idCliente, r.fechaEntrada, r.fechaSalida, r.estadoReserva, r.idFactura,
             h.codigoHabitacion, h.tipoHabitacion,
             c.nombres, c.apellidoPaterno, c.apellidoMaterno
      FROM Reserva r
      INNER JOIN Habitacion h ON r.idHabitacion=h.idHabitacion
      INNER JOIN Cliente c ON r.idCliente=c.idCliente
      WHERE r.idReserva=?
    ");
    $sel->bind_param("i",$idReserva);
    $sel->execute();
    $row=$sel->get_result()->fetch_assoc();
    response(["message"=>"Reserva creada","data"=>$row],201);

  }catch(Throwable $e){
    if ($conn->errno) $conn->rollback();
    response(["error"=>$e->getMessage()],500);
  }
}

response(["error"=>"Método no permitido"],405);
