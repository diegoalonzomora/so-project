<?php
include 'db.php';
// Obtener hoteles para el select
$hoteles = $conn->query("SELECT idHotel, nombreHotel FROM Hotel");
// Crear Habitacion
if (isset($_POST['add'])) {
    $codigo = $_POST['codigoHabitacion'];
    $piso = $_POST['pisoHabitacion'];
    $capacidad = $_POST['capacidad'];
    $tipo = $_POST['tipoHabitacion'];
    $estado = $_POST['estado'];
    $precio = $_POST['precioNoche'];
    $descripcion = $_POST['descripcion'];
    $idHotel = $_POST['idHotel'];
    $stmt = $conn->prepare("INSERT INTO Habitacion (codigoHabitacion, pisoHabitacion, capacidad, tipoHabitacion, estado, precioNoche, descripcion, idHotel) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("siissdsi", $codigo, $piso, $capacidad, $tipo, $estado, $precio, $descripcion, $idHotel);
    $stmt->execute();
    header("Location: habitacion.php");
    exit();
}
// Editar Habitacion
if (isset($_POST['edit'])) {
    $id = $_POST['idHabitacion'];
    $codigo = $_POST['codigoHabitacion'];
    $piso = $_POST['pisoHabitacion'];
    $capacidad = $_POST['capacidad'];
    $tipo = $_POST['tipoHabitacion'];
    $estado = $_POST['estado'];
    $precio = $_POST['precioNoche'];
    $descripcion = $_POST['descripcion'];
    $idHotel = $_POST['idHotel'];
    $stmt = $conn->prepare("UPDATE Habitacion SET codigoHabitacion=?, pisoHabitacion=?, capacidad=?, tipoHabitacion=?, estado=?, precioNoche=?, descripcion=?, idHotel=? WHERE idHabitacion=?");
    $stmt->bind_param("siissdsii", $codigo, $piso, $capacidad, $tipo, $estado, $precio, $descripcion, $idHotel, $id);
    $stmt->execute();
    header("Location: habitacion.php");
    exit();
}
// Eliminar Habitacion
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM Habitacion WHERE idHabitacion=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: habitacion.php");
    exit();
}
// Obtener habitaciones
$habitaciones = $conn->query("SELECT h.*, ho.nombreHotel FROM Habitacion h LEFT JOIN Hotel ho ON h.idHotel = ho.idHotel");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>CRUD Habitaciones</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <h2 class="mb-4 text-primary">Gestión de Habitaciones</h2>
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">Agregar Habitación</div>
        <div class="card-body">
            <form method="POST">
                <div class="row g-2 mb-2">
                    <div class="col-md-3">
                        <input type="text" name="codigoHabitacion" class="form-control" placeholder="Código" required>
                    </div>
                    <div class="col-md-2">
                        <input type="number" name="pisoHabitacion" class="form-control" placeholder="Piso">
                    </div>
                    <div class="col-md-2">
                        <input type="number" name="capacidad" class="form-control" placeholder="Capacidad">
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="tipoHabitacion" class="form-control" placeholder="Tipo">
                    </div>
                    <div class="col-md-3">
                        <select name="idHotel" class="form-select" required>
                            <option value="">Hotel</option>
                            <?php $hoteles->data_seek(0); while ($hotel = $hoteles->fetch_assoc()): ?>
                                <option value="<?= $hotel['idHotel'] ?>"><?= htmlspecialchars($hotel['nombreHotel']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-md-3">
                        <input type="text" name="estado" class="form-control" placeholder="Estado">
                    </div>
                    <div class="col-md-3">
                        <input type="number" step="0.01" name="precioNoche" class="form-control" placeholder="Precio/Noche">
                    </div>
                    <div class="col-md-6">
                        <input type="text" name="descripcion" class="form-control" placeholder="Descripción">
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
        <div class="card-header bg-secondary text-white">Lista de Habitaciones</div>
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Código</th>
                        <th>Piso</th>
                        <th>Capacidad</th>
                        <th>Tipo</th>
                        <th>Estado</th>
                        <th>Precio</th>
                        <th>Hotel</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = $habitaciones->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['idHabitacion'] ?></td>
                        <td><?= htmlspecialchars($row['codigoHabitacion']) ?></td>
                        <td><?= htmlspecialchars($row['pisoHabitacion']) ?></td>
                        <td><?= htmlspecialchars($row['capacidad']) ?></td>
                        <td><?= htmlspecialchars($row['tipoHabitacion']) ?></td>
                        <td><?= htmlspecialchars($row['estado']) ?></td>
                        <td><?= htmlspecialchars($row['precioNoche']) ?></td>
                        <td><?= htmlspecialchars($row['nombreHotel']) ?></td>
                        <td>
                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['idHabitacion'] ?>">Editar</button>
                            <a href="?delete=<?= $row['idHabitacion'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar esta habitación?')">Eliminar</a>
                        </td>
                    </tr>
                    <div class="modal fade" id="editModal<?= $row['idHabitacion'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <form method="POST" class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Editar Habitación</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="idHabitacion" value="<?= $row['idHabitacion'] ?>">
                                    <div class="mb-2">
                                        <label>Código</label>
                                        <input type="text" name="codigoHabitacion" class="form-control" value="<?= htmlspecialchars($row['codigoHabitacion']) ?>" required>
                                    </div>
                                    <div class="mb-2">
                                        <label>Piso</label>
                                        <input type="number" name="pisoHabitacion" class="form-control" value="<?= htmlspecialchars($row['pisoHabitacion']) ?>">
                                    </div>
                                    <div class="mb-2">
                                        <label>Capacidad</label>
                                        <input type="number" name="capacidad" class="form-control" value="<?= htmlspecialchars($row['capacidad']) ?>">
                                    </div>
                                    <div class="mb-2">
                                        <label>Tipo</label>
                                        <input type="text" name="tipoHabitacion" class="form-control" value="<?= htmlspecialchars($row['tipoHabitacion']) ?>">
                                    </div>
                                    <div class="mb-2">
                                        <label>Estado</label>
                                        <input type="text" name="estado" class="form-control" value="<?= htmlspecialchars($row['estado']) ?>">
                                    </div>
                                    <div class="mb-2">
                                        <label>Precio/Noche</label>
                                        <input type="number" step="0.01" name="precioNoche" class="form-control" value="<?= htmlspecialchars($row['precioNoche']) ?>">
                                    </div>
                                    <div class="mb-2">
                                        <label>Descripción</label>
                                        <input type="text" name="descripcion" class="form-control" value="<?= htmlspecialchars($row['descripcion']) ?>">
                                    </div>
                                    <div class="mb-2">
                                        <label>Hotel</label>
                                        <select name="idHotel" class="form-select" required>
                                            <?php $hoteles->data_seek(0); while ($hotel = $hoteles->fetch_assoc()): ?>
                                                <option value="<?= $hotel['idHotel'] ?>" <?= $row['idHotel'] == $hotel['idHotel'] ? 'selected' : '' ?>><?= htmlspecialchars($hotel['nombreHotel']) ?></option>
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
