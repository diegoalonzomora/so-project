<?php
include 'db.php';
header('Content-Type: application/json; charset=utf-8');

function response($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

try {
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $stmt = $conn->prepare("SELECT * FROM Servicios_Adicionales WHERE idServicioAdicional = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $data = $res->fetch_assoc();
        response($data ?: ["error" => "Servicio adicional no encontrado"]);
    }

    $res = $conn->query("SELECT * FROM Servicios_Adicionales");
    if (!$res) response(["error" => $conn->error]);
    response($res->fetch_all(MYSQLI_ASSOC));

} catch (Exception $e) {
    response(["error" => $e->getMessage()]);
}
