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
        $stmt = $conn->prepare("SELECT * FROM Servicios WHERE idServicio = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $servicio = $res->fetch_assoc();
        response($servicio ?: ["error" => "Servicio no encontrado"]);
    }

    $res = $conn->query("SELECT * FROM Servicios");
    if (!$res) response(["error" => $conn->error]);
    response($res->fetch_all(MYSQLI_ASSOC));

} catch (Exception $e) {
    response(["error" => $e->getMessage()]);
}
