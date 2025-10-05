<?php
include 'db.php';
header('Content-Type: application/json; charset=utf-8');

function response($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

try {
    $idHotel = isset($_GET['idHotel']) ? intval($_GET['idHotel']) : 0;
    $fechaEntrada = isset($_GET['fechaEntrada']) ? $_GET['fechaEntrada'] : '';
    $fechaSalida = isset($_GET['fechaSalida']) ? $_GET['fechaSalida'] : '';
    
    // Si viene ?id= devolver una habitación específica
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $stmt = $conn->prepare("
            SELECT h.idHabitacion, h.codigoHabitacion, h.pisoHabitacion, 
                   h.capacidad, h.tipoHabitacion, h.estado, 
                   h.precioNoche, h.descripcion, 
                   h.idHotel, t.nombreHotel, t.ciudad
            FROM Habitacion h
            INNER JOIN Hotel t ON h.idHotel = t.idHotel
            WHERE h.idHabitacion = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $habitacion = $result->fetch_assoc();
        response($habitacion ?: ["error" => "Habitación no encontrada"]);
    }

    // Construir query base
    $sql = "
        SELECT h.idHabitacion, h.codigoHabitacion, h.pisoHabitacion, 
               h.capacidad, h.tipoHabitacion, h.estado, 
               h.precioNoche, h.descripcion, 
               h.idHotel, t.nombreHotel, t.ciudad
        FROM Habitacion h
        INNER JOIN Hotel t ON h.idHotel = t.idHotel
        WHERE h.estado = 'Disponible'
    ";
    
    // Filtrar por hotel si viene
    if ($idHotel > 0) {
        $sql .= " AND h.idHotel = " . $idHotel;
    }
    
    // Filtrar por disponibilidad en fechas
    if ($fechaEntrada && $fechaSalida) {
        $entrada = mysqli_real_escape_string($conn, $fechaEntrada);
        $salida = mysqli_real_escape_string($conn, $fechaSalida);
        
        $sql .= " AND h.idHabitacion NOT IN (
            SELECT r.idHabitacion 
            FROM Reserva r 
            WHERE r.estadoReserva IN ('Confirmada', 'Pendiente', 'pendiente', 'confirmada')
            AND NOT (r.fechaSalida <= '$entrada' OR r.fechaEntrada >= '$salida')
        )";
    }
    
    $sql .= " ORDER BY h.precioNoche";
    
    $result = $conn->query($sql);
    if (!$result) {
        response(["error" => "Error en la consulta: " . $conn->error]);
    }

    $habitaciones = $result->fetch_all(MYSQLI_ASSOC);
    response($habitaciones);

} catch (Exception $e) {
    response(["error" => $e->getMessage()]);
}
