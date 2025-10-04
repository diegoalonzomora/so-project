<?php
include 'db.php';
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Content-Type, Accept');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

function response($data, $code = 200) { http_response_code($code); echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT); exit; }
function body() {
  $ct = $_SERVER['CONTENT_TYPE'] ?? '';
  if (stripos($ct,'application/json') !== false) {
    $raw = file_get_contents('php://input');
    $j = json_decode($raw, true);
    return is_array($j) ? $j : [];
  }
  return $_POST;
}

// GET ?id=...
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM Cliente WHERE idCliente = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
    response($r ?: ["error" => "Cliente no encontrado"], $r ? 200 : 404);
  }
  $res = $conn->query("SELECT * FROM Cliente");
  if (!$res) response(["error" => $conn->error], 500);
  response($res->fetch_all(MYSQLI_ASSOC));
}

// POST (crear cliente)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $d = body();

  // Validación mínima
  $req = ['nombres','apellidoPaterno','apellidoMaterno','correo','idPais','ciudad','documentoIdentidad','fechaRegistro'];
  foreach ($req as $k) { if (!isset($d[$k]) || $d[$k] === '') response(["error"=>"Falta campo: $k"], 422); }

  $numeroTelefono   = $d['numeroTelefono'] ?? '';
  $nombres          = $d['nombres'];
  $apP              = $d['apellidoPaterno'];
  $apM              = $d['apellidoMaterno'];
  $correo           = $d['correo'];
  $idPais           = intval($d['idPais']);
  $ciudad           = $d['ciudad'];
  $documento        = $d['documentoIdentidad'];
  $fechaRegistro    = $d['fechaRegistro']; // YYYY-MM-DD
  $idCliente        = isset($d['idCliente']) && $d['idCliente'] !== '' ? intval($d['idCliente']) : null;

  try {
    $conn->begin_transaction();

    // Si no envían id, generamos MAX+1 (mejor usar AUTO_INCREMENT en DB)
    if ($idCliente === null) {
      $r = $conn->query("SELECT COALESCE(MAX(idCliente)+1,1) AS next_id FROM Cliente FOR UPDATE");
      $idCliente = intval($r->fetch_assoc()['next_id']);
    }

    $stmt = $conn->prepare("
      INSERT INTO Cliente (idCliente, numeroTelefono, nombres, apellidoPaterno, apellidoMaterno, correo, idPais, ciudad, documentoIdentidad, fechaRegistro)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("issssssiss", $idCliente, $numeroTelefono, $nombres, $apP, $apM, $correo, $idPais, $ciudad, $documento, $fechaRegistro);

    if (!$stmt->execute()) { $conn->rollback(); response(["error"=>$stmt->error], 500); }

    $conn->commit();
    // devolver el registro
    $sel = $conn->prepare("SELECT * FROM Cliente WHERE idCliente = ?");
    $sel->bind_param("i", $idCliente);
    $sel->execute();
    $row = $sel->get_result()->fetch_assoc();
    response(["message"=>"Cliente creado","data"=>$row], 201);

  } catch (Throwable $e) {
    if ($conn->errno) $conn->rollback();
    response(["error"=>$e->getMessage()], 500);
  }
}

response(["error"=>"Método no permitido"], 405);
