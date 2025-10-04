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
            SELECT f.*, c.nombreCliente, r.idReserva
            FROM Factura f
            INNER JOIN Cliente c ON f.idCliente = c.idCliente
            INNER JOIN Reserva r ON f.idReserva = r.idReserva
            WHERE f.idFactura = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $data = $res->fetch_assoc();
        response($data ?: ["error" => "Factura no encontrada"]);
    }

    $res = $conn->query("
        SELECT f.*, c.nombreCliente, r.idReserva
        FROM Factura f
        INNER JOIN Cliente c ON f.idCliente = c.idCliente
        INNER JOIN Reserva r ON f.idReserva = r.idReserva
    ");
    if (!$res) response(["error" => $conn->error]);
    response($res->fetch_all(MYSQLI_ASSOC));

} catch (Exception $e) {
    response(["error" => $e->getMessage()]);
}
