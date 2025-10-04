<?php
include 'db.php';
$hoteles = $conn->query("SELECT idHotel, nombreHotel FROM Hotel");
// Crear Servicio Adicional
if (isset($_POST['add'])) {
    $idHotel = $_POST['idHotel'];
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];
    $precio = $_POST['precioAdicional'];
    $stmt = $conn->prepare("INSERT INTO ServiciosAdicionales (idHotel, nombre, descripcion, precioAdicional) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("issd", $idHotel, $nombre, $descripcion, $precio);
    $stmt->execute();
    header("Location: servicios_adicionales.php");
    exit();
}
// Editar Servicio Adicional
if (isset($_POST['edit'])) {
    $id = $_POST['idServicioAdicional'];
    $idHotel = $_POST['idHotel'];
    $nombre = $_POST['nombre'];
    $descripcion = $_POST['descripcion'];
    $precio = $_POST['precioAdicional'];
    $stmt = $conn->prepare("UPDATE ServiciosAdicionales SET idHotel=?, nombre=?, descripcion=?, precioAdicional=? WHERE idServicioAdicional=?");
    $stmt->bind_param("issdi", $idHotel, $nombre, $descripcion, $precio, $id);
    $stmt->execute();
    header("Location: servicios_adicionales.php");
    exit();
}
// Eliminar Servicio Adicional
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM ServiciosAdicionales WHERE idServicioAdicional=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: servicios_adicionales.php");
    exit();
}
$servicios = $conn->query("SELECT s.*, h.nombreHotel FROM ServiciosAdicionales s LEFT JOIN Hotel h ON s.idHotel = h.idHotel");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>CRUD Servicios Adicionales</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <h2 class="mb-4 text-primary">Gestión de Servicios Adicionales</h2>
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">Agregar Servicio Adicional</div>
        <div class="card-body">
            <form method="POST">
                <div class="row g-2 mb-2">
                    <div class="col-md-3">
                        <select name="idHotel" class="form-select" required>
                            <option value="">Hotel</option>
                            <?php $hoteles->data_seek(0); while ($hotel = $hoteles->fetch_assoc()): ?>
                                <option value="<?= $hotel['idHotel'] ?>"><?= htmlspecialchars($hotel['nombreHotel']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="nombre" class="form-control" placeholder="Nombre" required>
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="descripcion" class="form-control" placeholder="Descripción">
                    </div>
                    <div class="col-md-2">
                        <input type="number" step="0.01" name="precioAdicional" class="form-control" placeholder="Precio">
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
        <div class="card-header bg-secondary text-white">Lista de Servicios Adicionales</div>
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Hotel</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Precio</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = $servicios->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['idServicioAdicional'] ?></td>
                        <td><?= htmlspecialchars($row['nombreHotel']) ?></td>
                        <td><?= htmlspecialchars($row['nombre']) ?></td>
                        <td><?= htmlspecialchars($row['descripcion']) ?></td>
                        <td><?= htmlspecialchars($row['precioAdicional']) ?></td>
                        <td>
                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['idServicioAdicional'] ?>">Editar</button>
                            <a href="?delete=<?= $row['idServicioAdicional'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar este servicio adicional?')">Eliminar</a>
                        </td>
                    </tr>
                    <div class="modal fade" id="editModal<?= $row['idServicioAdicional'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <form method="POST" class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Editar Servicio Adicional</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="idServicioAdicional" value="<?= $row['idServicioAdicional'] ?>">
                                    <div class="mb-2">
                                        <label>Hotel</label>
                                        <select name="idHotel" class="form-select" required>
                                            <?php $hoteles->data_seek(0); while ($hotel = $hoteles->fetch_assoc()): ?>
                                                <option value="<?= $hotel['idHotel'] ?>" <?= $row['idHotel'] == $hotel['idHotel'] ? 'selected' : '' ?>><?= htmlspecialchars($hotel['nombreHotel']) ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="mb-2">
                                        <label>Nombre</label>
                                        <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($row['nombre']) ?>" required>
                                    </div>
                                    <div class="mb-2">
                                        <label>Descripción</label>
                                        <input type="text" name="descripcion" class="form-control" value="<?= htmlspecialchars($row['descripcion']) ?>">
                                    </div>
                                    <div class="mb-2">
                                        <label>Precio</label>
                                        <input type="number" step="0.01" name="precioAdicional" class="form-control" value="<?= htmlspecialchars($row['precioAdicional']) ?>">
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
