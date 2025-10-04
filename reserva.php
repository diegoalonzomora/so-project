<?php
include 'db.php';
$habitaciones = $conn->query("SELECT idHabitacion, codigoHabitacion FROM Habitacion");
$clientes = $conn->query("SELECT idCliente, nombres FROM Cliente");
$facturas = $conn->query("SELECT idFactura FROM Factura");
// Crear Reserva
if (isset($_POST['add'])) {
    $idHabitacion = $_POST['idHabitacion'];
    $idCliente = $_POST['idCliente'];
    $fechaEntrada = $_POST['fechaEntrada'];
    $fechaSalida = $_POST['fechaSalida'];
    $estado = $_POST['estadoReserva'];
    $idFactura = $_POST['idFactura'];
    $stmt = $conn->prepare("INSERT INTO Reserva (idHabitacion, idCliente, fechaEntrada, fechaSalida, estadoReserva, idFactura) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisssi", $idHabitacion, $idCliente, $fechaEntrada, $fechaSalida, $estado, $idFactura);
    $stmt->execute();
    header("Location: reserva.php");
    exit();
}
// Editar Reserva
if (isset($_POST['edit'])) {
    $id = $_POST['idReserva'];
    $idHabitacion = $_POST['idHabitacion'];
    $idCliente = $_POST['idCliente'];
    $fechaEntrada = $_POST['fechaEntrada'];
    $fechaSalida = $_POST['fechaSalida'];
    $estado = $_POST['estadoReserva'];
    $idFactura = $_POST['idFactura'];
    $stmt = $conn->prepare("UPDATE Reserva SET idHabitacion=?, idCliente=?, fechaEntrada=?, fechaSalida=?, estadoReserva=?, idFactura=? WHERE idReserva=?");
    $stmt->bind_param("iisssii", $idHabitacion, $idCliente, $fechaEntrada, $fechaSalida, $estado, $idFactura, $id);
    $stmt->execute();
    header("Location: reserva.php");
    exit();
}
// Eliminar Reserva
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM Reserva WHERE idReserva=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: reserva.php");
    exit();
}
$reservas = $conn->query("SELECT r.*, h.codigoHabitacion, c.nombres, f.idFactura FROM Reserva r LEFT JOIN Habitacion h ON r.idHabitacion = h.idHabitacion LEFT JOIN Cliente c ON r.idCliente = c.idCliente LEFT JOIN Factura f ON r.idFactura = f.idFactura");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>CRUD Reservas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <h2 class="mb-4 text-primary">Gestión de Reservas</h2>
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">Agregar Reserva</div>
        <div class="card-body">
            <form method="POST">
                <div class="row g-2 mb-2">
                    <div class="col-md-2">
                        <select name="idHabitacion" class="form-select" required>
                            <option value="">Habitación</option>
                            <?php $habitaciones->data_seek(0); while ($h = $habitaciones->fetch_assoc()): ?>
                                <option value="<?= $h['idHabitacion'] ?>"><?= htmlspecialchars($h['codigoHabitacion']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="idCliente" class="form-select" required>
                            <option value="">Cliente</option>
                            <?php $clientes->data_seek(0); while ($c = $clientes->fetch_assoc()): ?>
                                <option value="<?= $c['idCliente'] ?>"><?= htmlspecialchars($c['nombres']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="date" name="fechaEntrada" class="form-control" placeholder="Entrada">
                    </div>
                    <div class="col-md-2">
                        <input type="date" name="fechaSalida" class="form-control" placeholder="Salida">
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="estadoReserva" class="form-control" placeholder="Estado">
                    </div>
                    <div class="col-md-2">
                        <select name="idFactura" class="form-select">
                            <option value="">Factura</option>
                            <?php $facturas->data_seek(0); while ($f = $facturas->fetch_assoc()): ?>
                                <option value="<?= $f['idFactura'] ?>"><?= $f['idFactura'] ?></option>
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
        <div class="card-header bg-secondary text-white">Lista de Reservas</div>
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Habitación</th>
                        <th>Cliente</th>
                        <th>Entrada</th>
                        <th>Salida</th>
                        <th>Estado</th>
                        <th>Factura</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = $reservas->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['idReserva'] ?></td>
                        <td><?= htmlspecialchars($row['codigoHabitacion']) ?></td>
                        <td><?= htmlspecialchars($row['nombres']) ?></td>
                        <td><?= htmlspecialchars($row['fechaEntrada']) ?></td>
                        <td><?= htmlspecialchars($row['fechaSalida']) ?></td>
                        <td><?= htmlspecialchars($row['estadoReserva']) ?></td>
                        <td><?= htmlspecialchars($row['idFactura']) ?></td>
                        <td>
                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['idReserva'] ?>">Editar</button>
                            <a href="?delete=<?= $row['idReserva'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar esta reserva?')">Eliminar</a>
                        </td>
                    </tr>
                    <div class="modal fade" id="editModal<?= $row['idReserva'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <form method="POST" class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Editar Reserva</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="idReserva" value="<?= $row['idReserva'] ?>">
                                    <div class="mb-2">
                                        <label>Habitación</label>
                                        <select name="idHabitacion" class="form-select" required>
                                            <?php $habitaciones->data_seek(0); while ($h = $habitaciones->fetch_assoc()): ?>
                                                <option value="<?= $h['idHabitacion'] ?>" <?= $row['idHabitacion'] == $h['idHabitacion'] ? 'selected' : '' ?>><?= htmlspecialchars($h['codigoHabitacion']) ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="mb-2">
                                        <label>Cliente</label>
                                        <select name="idCliente" class="form-select" required>
                                            <?php $clientes->data_seek(0); while ($c = $clientes->fetch_assoc()): ?>
                                                <option value="<?= $c['idCliente'] ?>" <?= $row['idCliente'] == $c['idCliente'] ? 'selected' : '' ?>><?= htmlspecialchars($c['nombres']) ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="mb-2">
                                        <label>Fecha Entrada</label>
                                        <input type="date" name="fechaEntrada" class="form-control" value="<?= htmlspecialchars($row['fechaEntrada']) ?>">
                                    </div>
                                    <div class="mb-2">
                                        <label>Fecha Salida</label>
                                        <input type="date" name="fechaSalida" class="form-control" value="<?= htmlspecialchars($row['fechaSalida']) ?>">
                                    </div>
                                    <div class="mb-2">
                                        <label>Estado</label>
                                        <input type="text" name="estadoReserva" class="form-control" value="<?= htmlspecialchars($row['estadoReserva']) ?>">
                                    </div>
                                    <div class="mb-2">
                                        <label>Factura</label>
                                        <select name="idFactura" class="form-select">
                                            <?php $facturas->data_seek(0); while ($f = $facturas->fetch_assoc()): ?>
                                                <option value="<?= $f['idFactura'] ?>" <?= $row['idFactura'] == $f['idFactura'] ? 'selected' : '' ?>><?= $f['idFactura'] ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="submit" name="edit" class="btn btn-primary">Guardar Cambios</button>
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                </div>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
