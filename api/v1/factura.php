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

// GET ?id (factura + sus reservas y clientes)
if ($_SERVER['REQUEST_METHOD']==='GET'){
  if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("
      SELECT
        f.idFactura, f.montoTotal, f.fechaPago, f.metodoPago, f.descuento,
        r.idReserva, c.idCliente, c.nombres, c.apellidoPaterno, c.apellidoMaterno
      FROM Factura f
      LEFT JOIN Reserva r ON r.idFactura = f.idFactura
      LEFT JOIN Cliente c ON c.idCliente = r.idCliente
      WHERE f.idFactura = ?
    ");
    $stmt->bind_param("i",$id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows===0) response(["error"=>"Factura no encontrada"],404);

    $fact=null; $reservas=[];
    while($row=$res->fetch_assoc()){
      if ($fact===null){
        $fact=[
          "idFactura"=>(int)$row["idFactura"],
          "montoTotal"=>$row["montoTotal"],
          "fechaPago"=>$row["fechaPago"],
          "metodoPago"=>$row["metodoPago"],
          "descuento"=>$row["descuento"],
          "reservas"=>[]
        ];
      }
      if ($row["idReserva"]!==null){
        $reservas[]=[
          "idReserva"=>(int)$row["idReserva"],
          "idCliente"=>$row["idCliente"]!==null?(int)$row["idCliente"]:null,
          "cliente"=>trim(($row["nombres"]??"")." ".($row["apellidoPaterno"]??"")." ".($row["apellidoMaterno"]??""))
        ];
      }
    }
    $fact["reservas"]=$reservas;
    response($fact);
  }

  $sql="
    SELECT
      f.idFactura, f.montoTotal, f.fechaPago, f.metodoPago, f.descuento,
      r.idReserva, c.idCliente, c.nombres, c.apellidoPaterno, c.apellidoMaterno
    FROM Factura f
    LEFT JOIN Reserva r ON r.idFactura = f.idFactura
    LEFT JOIN Cliente c ON c.idCliente = r.idCliente
    ORDER BY f.idFactura
  ";
  $result = $conn->query($sql);
  if (!$result) response(["error"=>$conn->error],500);

  $map=[];
  while($row=$result->fetch_assoc()){
    $fid=(int)$row["idFactura"];
    if (!isset($map[$fid])){
      $map[$fid]=[
        "idFactura"=>$fid,
        "montoTotal"=>$row["montoTotal"],
        "fechaPago"=>$row["fechaPago"],
        "metodoPago"=>$row["metodoPago"],
        "descuento"=>$row["descuento"],
        "reservas"=>[]
      ];
    }
    if ($row["idReserva"]!==null){
      $map[$fid]["reservas"][]=[
        "idReserva"=>(int)$row["idReserva"],
        "idCliente"=>$row["idCliente"]!==null?(int)$row["idCliente"]:null,
        "cliente"=>trim(($row["nombres"]??"")." ".($row["apellidoPaterno"]??"")." ".($row["apellidoMaterno"]??""))
      ];
    }
  }
  response(array_values($map));
}

// POST (crear factura)
if ($_SERVER['REQUEST_METHOD']==='POST'){
  $d = body();
  $req = ['montoTotal','fechaPago','metodoPago']; // descuento opcional
  foreach($req as $k){ if (!isset($d[$k]) || $d[$k]==='') response(["error"=>"Falta campo: $k"],422); }

  $montoTotal = $d['montoTotal'];
  $fechaPago  = $d['fechaPago'];
  $metodoPago = $d['metodoPago'];
  $descuento  = isset($d['descuento']) && $d['descuento']!=='' ? $d['descuento'] : 0;
  $idFactura  = isset($d['idFactura']) && $d['idFactura']!=='' ? intval($d['idFactura']) : null;

  try{
    $conn->begin_transaction();
    if ($idFactura===null){
      $r=$conn->query("SELECT COALESCE(MAX(idFactura)+1,1) AS next_id FROM Factura FOR UPDATE");
      $idFactura=intval($r->fetch_assoc()['next_id']);
    }

    $stmt=$conn->prepare("INSERT INTO Factura (idFactura, montoTotal, fechaPago, metodoPago, descuento) VALUES (?,?,?,?,?)");
    $stmt->bind_param("idssd", $idFactura, $montoTotal, $fechaPago, $metodoPago, $descuento);
    if(!$stmt->execute()){ $conn->rollback(); response(["error"=>$stmt->error],500); }

    $conn->commit();

    $sel=$conn->prepare("SELECT * FROM Factura WHERE idFactura=?");
    $sel->bind_param("i",$idFactura);
    $sel->execute();
    $row=$sel->get_result()->fetch_assoc();
    response(["message"=>"Factura creada","data"=>$row],201);

  }catch(Throwable $e){
    if ($conn->errno) $conn->rollback();
    response(["error"=>$e->getMessage()],500);
  }
}

response(["error"=>"Método no permitido"],405);
