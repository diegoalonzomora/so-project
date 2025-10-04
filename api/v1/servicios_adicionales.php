<?php
include 'db.php';
header('Content-Type: application/json; charset=utf-8');

function response($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

try {
    $idHotel = isset($_GET['idHotel']) ? intval($_GET['idHotel']) : 0;
    
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);
        $stmt = $conn->prepare("
            SELECT sa.*, h.nombreHotel 
            FROM ServiciosAdicionales sa 
            LEFT JOIN Hotel h ON sa.idHotel = h.idHotel 
            WHERE sa.idServicioAdicional = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $data = $res->fetch_assoc();
        response($data ?: ["error" => "Servicio adicional no encontrado"]);
    }

    $sql = "
        SELECT sa.*, h.nombreHotel 
        FROM ServiciosAdicionales sa 
        LEFT JOIN Hotel h ON sa.idHotel = h.idHotel
    ";
    
    if ($idHotel > 0) {
        $sql .= " WHERE sa.idHotel = " . $idHotel;
    }
    
    $sql .= " ORDER BY sa.nombre";
    
    $res = $conn->query($sql);
    if (!$res) response(["error" => $conn->error]);
    response($res->fetch_all(MYSQLI_ASSOC));

} catch (Exception $e) {
    response(["error" => $e->getMessage()]);
}
