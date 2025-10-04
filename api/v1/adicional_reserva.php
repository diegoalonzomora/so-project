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
        $stmt = $conn->prepare("
            SELECT ar.*, r.idReserva, sa.nombreServicio
            FROM Adicional_Reserva ar
            INNER JOIN Reserva r ON ar.idReserva = r.idReserva
            INNER JOIN Servicios_Adicionales sa ON ar.idServicioAdicional = sa.idServicioAdicional
            WHERE ar.idAdicionalReserva = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $data = $res->fetch_assoc();
        response($data ?: ["error" => "Adicional de reserva no encontrado"]);
    }

    $res = $conn->query("
        SELECT ar.*, r.idReserva, sa.nombreServicio
        FROM Adicional_Reserva ar
        INNER JOIN Reserva r ON ar.idReserva = r.idReserva
        INNER JOIN Servicios_Adicionales sa ON ar.idServicioAdicional = sa.idServicioAdicional
    ");
    if (!$res) response(["error" => $conn->error]);
    response($res->fetch_all(MYSQLI_ASSOC));

} catch (Exception $e) {
    response(["error" => $e->getMessage()]);
}
