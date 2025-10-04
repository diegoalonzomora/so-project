<?php
include 'db.php';
$paises = $conn->query("SELECT idPais, nombrePais FROM Pais");
// Crear Cliente
if (isset($_POST['add'])) {
    $telefono = $_POST['numeroTelefono'];
    $nombres = $_POST['nombres'];
    $apPaterno = $_POST['apellidoPaterno'];
    $apMaterno = $_POST['apellidoMaterno'];
    $correo = $_POST['correo'];
    $idPais = $_POST['idPais'];
    $ciudad = $_POST['ciudad'];
    $documento = $_POST['documentoIdentidad'];
    $fecha = $_POST['fechaRegistro'];
    $stmt = $conn->prepare("INSERT INTO Cliente (numeroTelefono, nombres, apellidoPaterno, apellidoMaterno, correo, idPais, ciudad, documentoIdentidad, fechaRegistro) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssssssss", $telefono, $nombres, $apPaterno, $apMaterno, $correo, $idPais, $ciudad, $documento, $fecha);
    $stmt->execute();
    header("Location: cliente.php");
    exit();
}
// Editar Cliente
if (isset($_POST['edit'])) {
    $id = $_POST['idCliente'];
    $telefono = $_POST['numeroTelefono'];
    $nombres = $_POST['nombres'];
    $apPaterno = $_POST['apellidoPaterno'];
    $apMaterno = $_POST['apellidoMaterno'];
    $correo = $_POST['correo'];
    $idPais = $_POST['idPais'];
    $ciudad = $_POST['ciudad'];
    $documento = $_POST['documentoIdentidad'];
    $fecha = $_POST['fechaRegistro'];
    $stmt = $conn->prepare("UPDATE Cliente SET numeroTelefono=?, nombres=?, apellidoPaterno=?, apellidoMaterno=?, correo=?, idPais=?, ciudad=?, documentoIdentidad=?, fechaRegistro=? WHERE idCliente=?");
    $stmt->bind_param("sssssssssi", $telefono, $nombres, $apPaterno, $apMaterno, $correo, $idPais, $ciudad, $documento, $fecha, $id);
    $stmt->execute();
    header("Location: cliente.php");
    exit();
}
// Eliminar Cliente
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM Cliente WHERE idCliente=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: cliente.php");
    exit();
}
$clientes = $conn->query("SELECT c.*, p.nombrePais FROM Cliente c LEFT JOIN Pais p ON c.idPais = p.idPais");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>CRUD Clientes</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <h2 class="mb-4 text-primary">Gestión de Clientes</h2>
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">Agregar Cliente</div>
        <div class="card-body">
            <form method="POST">
                <div class="row g-2 mb-2">
                    <div class="col-md-3">
                        <input type="text" name="numeroTelefono" class="form-control" placeholder="Teléfono" required>
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="nombres" class="form-control" placeholder="Nombres" required>
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="apellidoPaterno" class="form-control" placeholder="Apellido Paterno">
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="apellidoMaterno" class="form-control" placeholder="Apellido Materno">
                    </div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-md-3">
                        <input type="email" name="correo" class="form-control" placeholder="Correo">
                    </div>
                    <div class="col-md-3">
                        <select name="idPais" class="form-select" required>
                            <option value="">País</option>
                            <?php $paises->data_seek(0); while ($pais = $paises->fetch_assoc()): ?>
                                <option value="<?= $pais['idPais'] ?>"><?= htmlspecialchars($pais['nombrePais']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="ciudad" class="form-control" placeholder="Ciudad">
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="documentoIdentidad" class="form-control" placeholder="Documento Identidad">
                    </div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-md-3">
                        <input type="date" name="fechaRegistro" class="form-control" placeholder="Fecha Registro">
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
        <div class="card-header bg-secondary text-white">Lista de Clientes</div>
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Apellido Paterno</th>
                        <th>Apellido Materno</th>
                        <th>Teléfono</th>
                        <th>Correo</th>
                        <th>País</th>
                        <th>Ciudad</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = $clientes->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['idCliente'] ?></td>
                        <td><?= htmlspecialchars($row['nombres']) ?></td>
                        <td><?= htmlspecialchars($row['apellidoPaterno']) ?></td>
                        <td><?= htmlspecialchars($row['apellidoMaterno']) ?></td>
                        <td><?= htmlspecialchars($row['numeroTelefono']) ?></td>
                        <td><?= htmlspecialchars($row['correo']) ?></td>
                        <td><?= htmlspecialchars($row['nombrePais']) ?></td>
                        <td><?= htmlspecialchars($row['ciudad']) ?></td>
                        <td>
                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['idCliente'] ?>">Editar</button>
                            <a href="?delete=<?= $row['idCliente'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar este cliente?')">Eliminar</a>
                        </td>
                    </tr>
                    <div class="modal fade" id="editModal<?= $row['idCliente'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <form method="POST" class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Editar Cliente</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="idCliente" value="<?= $row['idCliente'] ?>">
                                    <div class="mb-2">
                                        <label>Teléfono</label>
                                        <input type="text" name="numeroTelefono" class="form-control" value="<?= htmlspecialchars($row['numeroTelefono']) ?>" required>
                                    </div>
                                    <div class="mb-2">
                                        <label>Nombres</label>
                                        <input type="text" name="nombres" class="form-control" value="<?= htmlspecialchars($row['nombres']) ?>" required>
                                    </div>
                                    <div class="mb-2">
                                        <label>Apellido Paterno</label>
                                        <input type="text" name="apellidoPaterno" class="form-control" value="<?= htmlspecialchars($row['apellidoPaterno']) ?>">
                                    </div>
                                    <div class="mb-2">
                                        <label>Apellido Materno</label>
                                        <input type="text" name="apellidoMaterno" class="form-control" value="<?= htmlspecialchars($row['apellidoMaterno']) ?>">
                                    </div>
                                    <div class="mb-2">
                                        <label>Correo</label>
                                        <input type="email" name="correo" class="form-control" value="<?= htmlspecialchars($row['correo']) ?>">
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
                                        <label>Ciudad</label>
                                        <input type="text" name="ciudad" class="form-control" value="<?= htmlspecialchars($row['ciudad']) ?>">
                                    </div>
                                    <div class="mb-2">
                                        <label>Documento Identidad</label>
                                        <input type="text" name="documentoIdentidad" class="form-control" value="<?= htmlspecialchars($row['documentoIdentidad']) ?>">
                                    </div>
                                    <div class="mb-2">
                                        <label>Fecha Registro</label>
                                        <input type="date" name="fechaRegistro" class="form-control" value="<?= htmlspecialchars($row['fechaRegistro']) ?>">
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
