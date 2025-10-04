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
            SELECT r.*, c.nombreCliente, h.codigoHabitacion
            FROM Reserva r
            INNER JOIN Cliente c ON r.idCliente = c.idCliente
            INNER JOIN Habitacion h ON r.idHabitacion = h.idHabitacion
            WHERE r.idReserva = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $reserva = $result->fetch_assoc();
        response($reserva ?: ["error" => "Reserva no encontrada"]);
    }

    $sql = "
        SELECT r.*, c.nombreCliente, h.codigoHabitacion
        FROM Reserva r
        INNER JOIN Cliente c ON r.idCliente = c.idCliente
        INNER JOIN Habitacion h ON r.idHabitacion = h.idHabitacion
    ";
    $result = $conn->query($sql);
    if (!$result) response(["error" => $conn->error]);
    response($result->fetch_all(MYSQLI_ASSOC));

} catch (Exception $e) {
    response(["error" => $e->getMessage()]);
}
