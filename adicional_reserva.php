<?php
include 'db.php';
$servicios = $conn->query("SELECT sa.idServicioAdicional, sa.nombre, h.nombreHotel FROM ServiciosAdicionales sa LEFT JOIN Hotel h ON sa.idHotel = h.idHotel");
$reservas = $conn->query("SELECT r.idReserva, c.nombres FROM Reserva r LEFT JOIN Cliente c ON r.idCliente = c.idCliente");
// Crear AdicionalReserva
if (isset($_POST['add'])) {
    $idServicioAdicional = $_POST['idServicioAdicional'];
    $idReserva = $_POST['idReserva'];
    $stmt = $conn->prepare("INSERT INTO AdicionalReserva (idServicioAdicional, idReserva) VALUES (?, ?)");
    $stmt->bind_param("ii", $idServicioAdicional, $idReserva);
    $stmt->execute();
    header("Location: adicional_reserva.php");
    exit();
}
// Eliminar AdicionalReserva
if (isset($_GET['delete'])) {
    $idServicioAdicional = $_GET['idServicioAdicional'];
    $idReserva = $_GET['idReserva'];
    $stmt = $conn->prepare("DELETE FROM AdicionalReserva WHERE idServicioAdicional=? AND idReserva=?");
    $stmt->bind_param("ii", $idServicioAdicional, $idReserva);
    $stmt->execute();
    header("Location: adicional_reserva.php");
    exit();
}
$adicionales = $conn->query("SELECT ar.*, sa.nombre as servicio, h.nombreHotel, r.idReserva, c.nombres as cliente FROM AdicionalReserva ar LEFT JOIN ServiciosAdicionales sa ON ar.idServicioAdicional = sa.idServicioAdicional LEFT JOIN Hotel h ON sa.idHotel = h.idHotel LEFT JOIN Reserva r ON ar.idReserva = r.idReserva LEFT JOIN Cliente c ON r.idCliente = c.idCliente");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>CRUD Adicional Reserva</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <h2 class="mb-4 text-primary">Gestión de Servicios Adicionales en Reservas</h2>
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">Agregar Servicio Adicional a Reserva</div>
        <div class="card-body">
            <form method="POST">
                <div class="row g-2 mb-2">
                    <div class="col-md-6">
                        <select name="idServicioAdicional" class="form-select" required>
                            <option value="">Servicio Adicional</option>
                            <?php $servicios->data_seek(0); while ($s = $servicios->fetch_assoc()): ?>
                                <option value="<?= $s['idServicioAdicional'] ?>">[<?= htmlspecialchars($s['nombreHotel']) ?>] <?= htmlspecialchars($s['nombre']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <select name="idReserva" class="form-select" required>
                            <option value="">Reserva (Cliente)</option>
                            <?php $reservas->data_seek(0); while ($r = $reservas->fetch_assoc()): ?>
                                <option value="<?= $r['idReserva'] ?>">#<?= $r['idReserva'] ?> - <?= htmlspecialchars($r['nombres']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-2 ms-auto">
                        <button type="submit" name="add" class="btn btn-success w-100">Agregar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="card">
        <div class="card-header bg-secondary text-white">Servicios Adicionales por Reserva</div>
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>Servicio</th>
                        <th>Hotel</th>
                        <th>Reserva</th>
                        <th>Cliente</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = $adicionales->fetch_assoc()): ?>
                    <tr>
                        <td><?= htmlspecialchars($row['servicio']) ?></td>
                        <td><?= htmlspecialchars($row['nombreHotel']) ?></td>
                        <td><?= $row['idReserva'] ?></td>
                        <td><?= htmlspecialchars($row['cliente']) ?></td>
                        <td>
                            <a href="?delete=1&idServicioAdicional=<?= $row['idServicioAdicional'] ?>&idReserva=<?= $row['idReserva'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar este servicio de la reserva?')">Eliminar</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
