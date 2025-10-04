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

if ($_SERVER['REQUEST_METHOD']==='GET'){
  if (isset($_GET['id']) || isset($_GET['idReserva'])) {
    $id = isset($_GET['id']) ? intval($_GET['id']) : intval($_GET['idReserva']);
    $stmt=$conn->prepare("
      SELECT r.idReserva, r.idHabitacion, r.idCliente, r.fechaEntrada, r.fechaSalida, r.estadoReserva, r.idFactura,
             h.codigoHabitacion, h.tipoHabitacion, h.precioNoche,
             hot.nombreHotel, hot.ciudad, hot.idHotel,
             p.nombrePais,
             c.nombres, c.apellidoPaterno, c.apellidoMaterno, c.correo, c.documentoIdentidad, c.numeroTelefono,
             f.montoTotal, f.metodoPago, f.fechaPago, f.descuento
      FROM Reserva r
      INNER JOIN Habitacion h ON r.idHabitacion=h.idHabitacion
      INNER JOIN Hotel hot ON h.idHotel=hot.idHotel
      INNER JOIN Pais p ON hot.idPais=p.idPais
      INNER JOIN Cliente c ON r.idCliente=c.idCliente
      LEFT JOIN Factura f ON r.idFactura=f.idFactura
      WHERE r.idReserva=?
    ");
    $stmt->bind_param("i",$id);
    $stmt->execute();
    $row=$stmt->get_result()->fetch_assoc();
    
    if ($row) {
      $stmtServ = $conn->prepare("
        SELECT sa.idServicioAdicional, sa.nombre, sa.descripcion, sa.precioAdicional
        FROM AdicionalReserva ar
        INNER JOIN ServiciosAdicionales sa ON ar.idServicioAdicional = sa.idServicioAdicional
        WHERE ar.idReserva = ?
      ");
      $stmtServ->bind_param("i", $id);
      $stmtServ->execute();
      $servicios = $stmtServ->get_result()->fetch_all(MYSQLI_ASSOC);
      $row['serviciosAdicionales'] = $servicios;
    }
    
    response($row?:["error"=>"Reserva no encontrada"], $row?200:404);
  }

  if (isset($_GET['idCliente'])) {
    $id = intval($_GET['idCliente']);
    $sql="
      SELECT r.idReserva, r.idHabitacion, r.idCliente, r.fechaEntrada, r.fechaSalida, r.estadoReserva, r.idFactura,
            h.codigoHabitacion, h.tipoHabitacion,
            c.nombres, c.apellidoPaterno, c.apellidoMaterno,
            f.montoTotal
      FROM Reserva r
      INNER JOIN Habitacion h ON r.idHabitacion=h.idHabitacion
      INNER JOIN Cliente c ON r.idCliente=c.idCliente
      INNER JOIN Factura f ON r.idFactura=f.idFactura
      WHERE r.idCliente=?
    ";

    $stmt=$conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $rs=$stmt->get_result();
    if(!$rs) response(["error"=>$conn->error],500);
    response($rs->fetch_all(MYSQLI_ASSOC));
  }
}

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

    $ov=$conn->prepare("
      SELECT 1 FROM Reserva
      WHERE idHabitacion = ?
        AND NOT (fechaSalida <= ? OR fechaEntrada >= ?)
      LIMIT 1
    ");
    $ov->bind_param("iss", $idHabitacion, $fechaEntrada, $fechaSalida);
    $ov->execute();
    if ($ov->get_result()->num_rows>0){ $conn->rollback(); response(["error"=>"La habitación ya está reservada en ese rango"],409); }

    if ($idReserva===null){
      $r=$conn->query("SELECT COALESCE(MAX(idReserva)+1,1) AS next_id FROM Reserva FOR UPDATE");
      $idReserva=intval($r->fetch_assoc()['next_id']);
    }

    $stmt=$conn->prepare("
      INSERT INTO Reserva (idReserva, idHabitacion, idCliente, fechaEntrada, fechaSalida, estadoReserva, idFactura)
      VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    if ($idFactura===null){
      $null = null;
      $stmt->bind_param("iiisssi", $idReserva, $idHabitacion, $idCliente, $fechaEntrada, $fechaSalida, $estado, $null);
    } else {
      $stmt->bind_param("iiisssi", $idReserva, $idHabitacion, $idCliente, $fechaEntrada, $fechaSalida, $estado, $idFactura);
    }

    if(!$stmt->execute()){ $conn->rollback(); response(["error"=>$stmt->error],500); }

    $conn->commit();

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

if ($_SERVER['REQUEST_METHOD']==='PUT'){
  $d = body();
  
  if (!isset($d['idReserva']) || $d['idReserva']==='') {
    response(["error"=>"Falta campo: idReserva"],422);
  }
  
  $idReserva = intval($d['idReserva']);
  
  try {
    $conn->begin_transaction();
    
    // Verificar que la reserva existe
    $chk = $conn->prepare("SELECT estadoReserva FROM Reserva WHERE idReserva=?");
    $chk->bind_param("i", $idReserva);
    $chk->execute();
    $result = $chk->get_result();
    
    if ($result->num_rows === 0) {
      $conn->rollback();
      response(["error"=>"Reserva no encontrada"],404);
    }
    
    $reserva = $result->fetch_assoc();
    
    if (isset($d['cancelar']) && $d['cancelar'] === true) {
      if ($reserva['estadoReserva'] === 'Cancelada') {
        $conn->rollback();
        response(["error"=>"La reserva ya está cancelada"],400);
      }
      
      $stmt = $conn->prepare("UPDATE Reserva SET estadoReserva='Cancelada' WHERE idReserva=?");
      $stmt->bind_param("i", $idReserva);
      
      if (!$stmt->execute()) {
        $conn->rollback();
        response(["error"=>$stmt->error],500);
      }
      
      $conn->commit();
      response(["message"=>"Reserva cancelada exitosamente"]);
    }
    
    if (isset($d['serviciosAdicionales']) && is_array($d['serviciosAdicionales'])) {
      $del = $conn->prepare("DELETE FROM AdicionalReserva WHERE idReserva=?");
      $del->bind_param("i", $idReserva);
      $del->execute();
      
      if (count($d['serviciosAdicionales']) > 0) {
        $stmt = $conn->prepare("INSERT INTO AdicionalReserva (idReserva, idServicioAdicional) VALUES (?, ?)");
        
        foreach ($d['serviciosAdicionales'] as $idServicio) {
          $idServicio = intval($idServicio);
          
          $chkServ = $conn->prepare("SELECT 1 FROM ServiciosAdicionales WHERE idServicioAdicional=?");
          $chkServ->bind_param("i", $idServicio);
          $chkServ->execute();
          
          if ($chkServ->get_result()->num_rows === 0) {
            $conn->rollback();
            response(["error"=>"Servicio $idServicio no existe"],422);
          }
          
          $stmt->bind_param("ii", $idReserva, $idServicio);
          if (!$stmt->execute()) {
            $conn->rollback();
            response(["error"=>$stmt->error],500);
          }
        }
      }
      
      $factura = $conn->prepare("
        SELECT f.idFactura, h.precioNoche, r.fechaEntrada, r.fechaSalida
        FROM Reserva r
        INNER JOIN Factura f ON r.idFactura = f.idFactura
        INNER JOIN Habitacion h ON r.idHabitacion = h.idHabitacion
        WHERE r.idReserva = ?
      ");
      $factura->bind_param("i", $idReserva);
      $factura->execute();
      $facturaData = $factura->get_result()->fetch_assoc();
      
      if ($facturaData) {
        $dias = (strtotime($facturaData['fechaSalida']) - strtotime($facturaData['fechaEntrada'])) / 86400;
        $precioHabitacion = floatval($facturaData['precioNoche']) * $dias;
        
        $serviciosTotal = 0;
        if (count($d['serviciosAdicionales']) > 0) {
          $placeholders = implode(',', array_fill(0, count($d['serviciosAdicionales']), '?'));
          $queryServ = "SELECT SUM(precioAdicional) as total FROM ServiciosAdicionales WHERE idServicioAdicional IN ($placeholders)";
          $stmtServ = $conn->prepare($queryServ);
          $types = str_repeat('i', count($d['serviciosAdicionales']));
          $stmtServ->bind_param($types, ...$d['serviciosAdicionales']);
          $stmtServ->execute();
          $resultServ = $stmtServ->get_result()->fetch_assoc();
          $serviciosTotal = floatval($resultServ['total'] ?? 0);
        }
        
        $montoTotal = $precioHabitacion + $serviciosTotal;
        
        $updateFactura = $conn->prepare("UPDATE Factura SET montoTotal=? WHERE idFactura=?");
        $updateFactura->bind_param("di", $montoTotal, $facturaData['idFactura']);
        $updateFactura->execute();
      }
      
      $conn->commit();
      response(["message"=>"Servicios actualizados exitosamente"]);
    }
    
    $conn->rollback();
    response(["error"=>"No se especificó ninguna acción válida (cancelar o serviciosAdicionales)"],400);
    
  } catch(Throwable $e) {
    if ($conn->errno) $conn->rollback();
    response(["error"=>$e->getMessage()],500);
  }
}

response(["error"=>"Método no permitido"],405);
