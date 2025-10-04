<?php
include 'db.php';

// Obtener países para el select
$paises = $conn->query("SELECT idPais, nombrePais FROM Pais");

// Crear Hotel
if (isset($_POST['add'])) {
    $nombre = $_POST['nombreHotel'];
    $calle = $_POST['calle'];
    $ciudad = $_POST['ciudad'];
    $idPais = $_POST['idPais'];
    $codigoPostal = $_POST['codigoPostal'];
    $telefono = $_POST['telefonoContacto'];
    $correo = $_POST['correo'];
    $calificacion = $_POST['calificacion'];
    $numHabitaciones = $_POST['numeroHabitaciones'];
    $descripcion = $_POST['descripcion'];
    $stmt = $conn->prepare("INSERT INTO Hotel (nombreHotel, calle, ciudad, idPais, codigoPostal, telefonoContacto, correo, calificacion, numeroHabitaciones, descripcion) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssssds", $nombre, $calle, $ciudad, $idPais, $codigoPostal, $telefono, $correo, $calificacion, $numHabitaciones, $descripcion);
    $stmt->execute();
    header("Location: hotel.php");
    exit();
}

// Editar Hotel
if (isset($_POST['edit'])) {
    $id = $_POST['idHotel'];
    $nombre = $_POST['nombreHotel'];
    $calle = $_POST['calle'];
    $ciudad = $_POST['ciudad'];
    $idPais = $_POST['idPais'];
    $codigoPostal = $_POST['codigoPostal'];
    $telefono = $_POST['telefonoContacto'];
    $correo = $_POST['correo'];
    $calificacion = $_POST['calificacion'];
    $numHabitaciones = $_POST['numeroHabitaciones'];
    $descripcion = $_POST['descripcion'];
    $stmt = $conn->prepare("UPDATE Hotel SET nombreHotel=?, calle=?, ciudad=?, idPais=?, codigoPostal=?, telefonoContacto=?, correo=?, calificacion=?, numeroHabitaciones=?, descripcion=? WHERE idHotel=?");
    $stmt->bind_param("ssssssssdsi", $nombre, $calle, $ciudad, $idPais, $codigoPostal, $telefono, $correo, $calificacion, $numHabitaciones, $descripcion, $id);
    $stmt->execute();
    header("Location: hotel.php");
    exit();
}

// Eliminar Hotel
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM Hotel WHERE idHotel=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: hotel.php");
    exit();
}

// Obtener hoteles
$hoteles = $conn->query("SELECT h.*, p.nombrePais FROM Hotel h LEFT JOIN Pais p ON h.idPais = p.idPais");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>CRUD Hoteles</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <h2 class="mb-4 text-primary">Gestión de Hoteles</h2>
    <!-- Formulario Agregar Hotel -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">Agregar Hotel</div>
        <div class="card-body">
            <form method="POST">
                <div class="row g-2 mb-2">
                    <div class="col-md-4">
                        <input type="text" name="nombreHotel" class="form-control" placeholder="Nombre del hotel" required>
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="calle" class="form-control" placeholder="Calle">
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="ciudad" class="form-control" placeholder="Ciudad">
                    </div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-md-3">
                        <select name="idPais" class="form-select" required>
                            <option value="">País</option>
                            <?php $paises->data_seek(0); while ($pais = $paises->fetch_assoc()): ?>
                                <option value="<?= $pais['idPais'] ?>"><?= htmlspecialchars($pais['nombrePais']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="codigoPostal" class="form-control" placeholder="Código Postal">
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="telefonoContacto" class="form-control" placeholder="Teléfono">
                    </div>
                    <div class="col-md-3">
                        <input type="email" name="correo" class="form-control" placeholder="Correo">
                    </div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-md-2">
                        <input type="number" step="0.01" name="calificacion" class="form-control" placeholder="Calificación">
                    </div>
                    <div class="col-md-2">
                        <input type="number" name="numeroHabitaciones" class="form-control" placeholder="Habitaciones">
                    </div>
                    <div class="col-md-8">
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
    <!-- Tabla de Hoteles -->
    <div class="card">
        <div class="card-header bg-secondary text-white">Lista de Hoteles</div>
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Ciudad</th>
                        <th>País</th>
                        <th>Teléfono</th>
                        <th>Correo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = $hoteles->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['idHotel'] ?></td>
                        <td><?= htmlspecialchars($row['nombreHotel']) ?></td>
                        <td><?= htmlspecialchars($row['ciudad']) ?></td>
                        <td><?= htmlspecialchars($row['nombrePais']) ?></td>
                        <td><?= htmlspecialchars($row['telefonoContacto']) ?></td>
                        <td><?= htmlspecialchars($row['correo']) ?></td>
                        <td>
                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['idHotel'] ?>">Editar</button>
                            <a href="?delete=<?= $row['idHotel'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar este hotel?')">Eliminar</a>
                        </td>
                    </tr>
                    <!-- Modal Editar -->
                    <div class="modal fade" id="editModal<?= $row['idHotel'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <form method="POST" class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Editar Hotel</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="idHotel" value="<?= $row['idHotel'] ?>">
                                    <div class="mb-2">
                                        <label>Nombre</label>
                                        <input type="text" name="nombreHotel" class="form-control" value="<?= htmlspecialchars($row['nombreHotel']) ?>" required>
                                    </div>
                                    <div class="mb-2">
                                        <label>Calle</label>
                                        <input type="text" name="calle" class="form-control" value="<?= htmlspecialchars($row['calle']) ?>">
                                    </div>
                                    <div class="mb-2">
                                        <label>Ciudad</label>
                                        <input type="text" name="ciudad" class="form-control" value="<?= htmlspecialchars($row['ciudad']) ?>">
                                    </div>
                                    <div class="mb-2">
                                        <label>País</label>
                                        <select name="idPais" class="form-select" required>
                                            <?php $paises->data_seek(0); while ($pais = $paises->fetch_assoc()): ?>
                                                <option value="<?= $pais['idPais'] ?>" <?= $row['idPais'] == $pais['idPais'] ? 'selected' : '' ?>><?= htmlspecialchars($pais['nombrePais']) ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    <div class="mb-2">
                                        <label>Código Postal</label>
                                        <input type="text" name="codigoPostal" class="form-control" value="<?= htmlspecialchars($row['codigoPostal']) ?>">
                                    </div>
                                    <div class="mb-2">
                                        <label>Teléfono</label>
                                        <input type="text" name="telefonoContacto" class="form-control" value="<?= htmlspecialchars($row['telefonoContacto']) ?>">
                                    </div>
                                    <div class="mb-2">
                                        <label>Correo</label>
                                        <input type="email" name="correo" class="form-control" value="<?= htmlspecialchars($row['correo']) ?>">
                                    </div>
                                    <div class="mb-2">
                                        <label>Calificación</label>
                                        <input type="number" step="0.01" name="calificacion" class="form-control" value="<?= htmlspecialchars($row['calificacion']) ?>">
                                    </div>
                                    <div class="mb-2">
                                        <label>Habitaciones</label>
                                        <input type="number" name="numeroHabitaciones" class="form-control" value="<?= htmlspecialchars($row['numeroHabitaciones']) ?>">
                                    </div>
                                    <div class="mb-2">
                                        <label>Descripción</label>
                                        <input type="text" name="descripcion" class="form-control" value="<?= htmlspecialchars($row['descripcion']) ?>">
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
<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
