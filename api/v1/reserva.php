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
            SELECT r.idReserva, r.fechaEntrada, r.fechaSalida, r.estadoReserva,
                   h.codigoHabitacion,
                   c.nombres, c.apellidoPaterno, c.apellidoMaterno,
                   f.montoTotal, f.fechaPago, f.metodoPago
            FROM Reserva r
            INNER JOIN Habitacion h ON r.idHabitacion = h.idHabitacion
            INNER JOIN Cliente c ON r.idCliente = c.idCliente
            LEFT JOIN Factura f ON r.idFactura = f.idFactura
            WHERE r.idReserva = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $data = $res->fetch_assoc();
        response($data ?: ["error" => "Reserva no encontrada"]);
    }

    $sql = "
        SELECT r.idReserva, r.fechaEntrada, r.fechaSalida, r.estadoReserva,
               h.codigoHabitacion,
               c.nombres, c.apellidoPaterno, c.apellidoMaterno,
               f.montoTotal, f.fechaPago, f.metodoPago
        FROM Reserva r
        INNER JOIN Habitacion h ON r.idHabitacion = h.idHabitacion
        INNER JOIN Cliente c ON r.idCliente = c.idCliente
        LEFT JOIN Factura f ON r.idFactura = f.idFactura
    ";
    $result = $conn->query($sql);
    if (!$result) response(["error" => $conn->error]);
    response($result->fetch_all(MYSQLI_ASSOC));

} catch (Exception $e) {
    response(["error" => $e->getMessage()]);
}
