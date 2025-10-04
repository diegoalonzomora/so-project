<?php
// db.php debe tener la conexión en $conn
include 'db.php';

// Forzar cabecera JSON
header('Content-Type: application/json; charset=utf-8');

// Función para responder en JSON y salir
function response($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

try {
    // Si viene ?id= en la URL → devolver solo una habitación
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $stmt = $conn->prepare("
            SELECT h.idHabitacion, h.codigoHabitacion, h.pisoHabitacion, 
                   h.capacidad, h.tipoHabitacion, h.estado, 
                   h.precioNoche, h.descripcion, 
                   h.idHotel, t.nombreHotel
            FROM Habitacion h
            INNER JOIN Hotel t ON h.idHotel = t.idHotel
            WHERE h.idHabitacion = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $habitacion = $result->fetch_assoc();

        if ($habitacion) {
            response($habitacion);
        } else {
            response(["error" => "Habitación no encontrada"]);
        }
    }

    // Si no hay parámetro → devolver todas las habitaciones
    $sql = "
        SELECT h.idHabitacion, h.codigoHabitacion, h.pisoHabitacion, 
               h.capacidad, h.tipoHabitacion, h.estado, 
               h.precioNoche, h.descripcion, 
               h.idHotel, t.nombreHotel
        FROM Habitacion h
        INNER JOIN Hotel t ON h.idHotel = t.idHotel
    ";
    $result = $conn->query($sql);

    if (!$result) {
        response(["error" => "Error en la consulta: " . $conn->error]);
    }

    $habitaciones = $result->fetch_all(MYSQLI_ASSOC);
    response($habitaciones);

} catch (Exception $e) {
    response(["error" => $e->getMessage()]);
}
