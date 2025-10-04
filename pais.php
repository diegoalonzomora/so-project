<?php
include 'db.php';

// Crear País
if (isset($_POST['add'])) {
    $nombre = $_POST['nombrePais'];
    $codigo = $_POST['codigoPais'];
    $stmt = $conn->prepare("INSERT INTO Pais (nombrePais, codigoPais) VALUES (?, ?)");
    $stmt->bind_param("ss", $nombre, $codigo);
    $stmt->execute();
    header("Location: pais.php");
    exit();
}

// Editar País
if (isset($_POST['edit'])) {
    $id = $_POST['idPais'];
    $nombre = $_POST['nombrePais'];
    $codigo = $_POST['codigoPais'];
    $stmt = $conn->prepare("UPDATE Pais SET nombrePais=?, codigoPais=? WHERE idPais=?");
    $stmt->bind_param("ssi", $nombre, $codigo, $id);
    $stmt->execute();
    header("Location: pais.php");
    exit();
}

// Eliminar País
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM Pais WHERE idPais=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: pais.php");
    exit();
}

// Obtener países
$paises = $conn->query("SELECT * FROM Pais");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>CRUD Países</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <h2 class="mb-4 text-primary">Gestión de Países</h2>
    <!-- Formulario Agregar País -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">Agregar País</div>
        <div class="card-body">
            <form method="POST">
                <div class="row g-2">
                    <div class="col-md-6">
                        <input type="text" name="nombrePais" class="form-control" placeholder="Nombre del país" required>
                    </div>
                    <div class="col-md-4">
                        <input type="text" name="codigoPais" class="form-control" placeholder="Código" required>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" name="add" class="btn btn-success w-100">Agregar</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <!-- Tabla de Países -->
    <div class="card">
        <div class="card-header bg-secondary text-white">Lista de Países</div>
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Código</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = $paises->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['idPais'] ?></td>
                        <td><?= htmlspecialchars($row['nombrePais']) ?></td>
                        <td><?= htmlspecialchars($row['codigoPais']) ?></td>
                        <td>
                            <!-- Botón Editar (abre modal) -->
                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['idPais'] ?>">Editar</button>
                            <a href="?delete=<?= $row['idPais'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar este país?')">Eliminar</a>
                        </td>
                    </tr>
                    <!-- Modal Editar -->
                    <div class="modal fade" id="editModal<?= $row['idPais'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <form method="POST" class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Editar País</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="idPais" value="<?= $row['idPais'] ?>">
                                    <div class="mb-3">
                                        <label>Nombre</label>
                                        <input type="text" name="nombrePais" class="form-control" value="<?= htmlspecialchars($row['nombrePais']) ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label>Código</label>
                                        <input type="text" name="codigoPais" class="form-control" value="<?= htmlspecialchars($row['codigoPais']) ?>" required>
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