<?php
// factura.php
include 'db.php';
header('Content-Type: application/json; charset=utf-8');

function response($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

try {
    // Una factura por id
    if (isset($_GET['id'])) {
        $id = intval($_GET['id']);

        $stmt = $conn->prepare("
            SELECT
                f.idFactura,
                f.montoTotal,
                f.fechaPago,
                f.metodoPago,
                f.descuento,
                r.idReserva,
                c.idCliente,
                c.nombres,
                c.apellidoPaterno,
                c.apellidoMaterno
            FROM Factura f
            LEFT JOIN Reserva r      ON r.idFactura = f.idFactura
            LEFT JOIN Cliente c      ON c.idCliente = r.idCliente
            WHERE f.idFactura = ?
        ");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows === 0) {
            response(["error" => "Factura no encontrada"]);
        }

        $factura = null;
        $reservas = [];

        while ($row = $res->fetch_assoc()) {
            if ($factura === null) {
                $factura = [
                    "idFactura"   => (int)$row["idFactura"],
                    "montoTotal"  => $row["montoTotal"],
                    "fechaPago"   => $row["fechaPago"],
                    "metodoPago"  => $row["metodoPago"],
                    "descuento"   => $row["descuento"],
                    "reservas"    => []
                ];
            }

            if ($row["idReserva"] !== null) {
                $reservas[] = [
                    "idReserva" => (int)$row["idReserva"],
                    "idCliente" => $row["idCliente"] !== null ? (int)$row["idCliente"] : null,
                    "cliente"   => trim(($row["nombres"] ?? "") . " " . ($row["apellidoPaterno"] ?? "") . " " . ($row["apellidoMaterno"] ?? ""))
                ];
            }
        }

        $factura["reservas"] = $reservas;
        response($factura);
    }

    // Todas las facturas (con reservas y clientes asociados)
    $sql = "
        SELECT
            f.idFactura,
            f.montoTotal,
            f.fechaPago,
            f.metodoPago,
            f.descuento,
            r.idReserva,
            c.idCliente,
            c.nombres,
            c.apellidoPaterno,
            c.apellidoMaterno
        FROM Factura f
        LEFT JOIN Reserva r ON r.idFactura = f.idFactura
        LEFT JOIN Cliente c ON c.idCliente = r.idCliente
        ORDER BY f.idFactura
    ";
    $result = $conn->query($sql);
    if (!$result) {
        response(["error" => "Error en la consulta: " . $conn->error]);
    }

    $map = []; // agrupamos por idFactura
    while ($row = $result->fetch_assoc()) {
        $fid = (int)$row["idFactura"];
        if (!isset($map[$fid])) {
            $map[$fid] = [
                "idFactura"  => $fid,
                "montoTotal" => $row["montoTotal"],
                "fechaPago"  => $row["fechaPago"],
                "metodoPago" => $row["metodoPago"],
                "descuento"  => $row["descuento"],
                "reservas"   => []
            ];
        }

        if ($row["idReserva"] !== null) {
            $map[$fid]["reservas"][] = [
                "idReserva" => (int)$row["idReserva"],
                "idCliente" => $row["idCliente"] !== null ? (int)$row["idCliente"] : null,
                "cliente"   => trim(($row["nombres"] ?? "") . " " . ($row["apellidoPaterno"] ?? "") . " " . ($row["apellidoMaterno"] ?? ""))
            ];
        }
    }

    response(array_values($map));

} catch (Exception $e) {
    response(["error" => $e->getMessage()]);
}
