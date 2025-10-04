<?php
include 'db.php';
$hoteles = $conn->query("SELECT idHotel, nombreHotel FROM Hotel");
// Crear Servicio
if (isset($_POST['add'])) {
    $nombre = $_POST['nombreServicio'];
    $descripcion = $_POST['descripcion'];
    $idHotel = $_POST['idHotel'];
    $stmt = $conn->prepare("INSERT INTO Servicios (nombreServicio, descripcion, idHotel) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $nombre, $descripcion, $idHotel);
    $stmt->execute();
    header("Location: servicios.php");
    exit();
}
// Editar Servicio
if (isset($_POST['edit'])) {
    $id = $_POST['idServicio'];
    $nombre = $_POST['nombreServicio'];
    $descripcion = $_POST['descripcion'];
    $idHotel = $_POST['idHotel'];
    $stmt = $conn->prepare("UPDATE Servicios SET nombreServicio=?, descripcion=?, idHotel=? WHERE idServicio=?");
    $stmt->bind_param("ssii", $nombre, $descripcion, $idHotel, $id);
    $stmt->execute();
    header("Location: servicios.php");
    exit();
}
// Eliminar Servicio
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM Servicios WHERE idServicio=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: servicios.php");
    exit();
}
$servicios = $conn->query("SELECT s.*, h.nombreHotel FROM Servicios s LEFT JOIN Hotel h ON s.idHotel = h.idHotel");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>CRUD Servicios</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <h2 class="mb-4 text-primary">Gestión de Servicios</h2>
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">Agregar Servicio</div>
        <div class="card-body">
            <form method="POST">
                <div class="row g-2 mb-2">
                    <div class="col-md-4">
                        <input type="text" name="nombreServicio" class="form-control" placeholder="Nombre del servicio" required>
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="descripcion" class="form-control" placeholder="Descripción">
                    </div>
                    <div class="col-md-4">
                        <select name="idHotel" class="form-select" required>
                            <option value="">Hotel</option>
                            <?php $hoteles->data_seek(0); while ($hotel = $hoteles->fetch_assoc()): ?>
                                <option value="<?= $hotel['idHotel'] ?>"><?= htmlspecialchars($hotel['nombreHotel']) ?></option>
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
        <div class="card-header bg-secondary text-white">Lista de Servicios</div>
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Hotel</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = $servicios->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['idServicio'] ?></td>
                        <td><?= htmlspecialchars($row['nombreServicio']) ?></td>
                        <td><?= htmlspecialchars($row['descripcion']) ?></td>
                        <td><?= htmlspecialchars($row['nombreHotel']) ?></td>
                        <td>
                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['idServicio'] ?>">Editar</button>
                            <a href="?delete=<?= $row['idServicio'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar este servicio?')">Eliminar</a>
                        </td>
                    </tr>
                    <div class="modal fade" id="editModal<?= $row['idServicio'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <form method="POST" class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Editar Servicio</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="idServicio" value="<?= $row['idServicio'] ?>">
                                    <div class="mb-2">
                                        <label>Nombre</label>
                                        <input type="text" name="nombreServicio" class="form-control" value="<?= htmlspecialchars($row['nombreServicio']) ?>" required>
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
