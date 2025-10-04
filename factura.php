<?php
include 'db.php';
// Crear Factura
if (isset($_POST['add'])) {
    $monto = $_POST['montoTotal'];
    $fecha = $_POST['fechaPago'];
    $metodo = $_POST['metodoPago'];
    $descuento = $_POST['descuento'];
    $stmt = $conn->prepare("INSERT INTO Factura (montoTotal, fechaPago, metodoPago, descuento) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("dssd", $monto, $fecha, $metodo, $descuento);
    $stmt->execute();
    header("Location: factura.php");
    exit();
}
// Editar Factura
if (isset($_POST['edit'])) {
    $id = $_POST['idFactura'];
    $monto = $_POST['montoTotal'];
    $fecha = $_POST['fechaPago'];
    $metodo = $_POST['metodoPago'];
    $descuento = $_POST['descuento'];
    $stmt = $conn->prepare("UPDATE Factura SET montoTotal=?, fechaPago=?, metodoPago=?, descuento=? WHERE idFactura=?");
    $stmt->bind_param("dssdi", $monto, $fecha, $metodo, $descuento, $id);
    $stmt->execute();
    header("Location: factura.php");
    exit();
}
// Eliminar Factura
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM Factura WHERE idFactura=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: factura.php");
    exit();
}
$facturas = $conn->query("SELECT * FROM Factura");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>CRUD Facturas</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
    <h2 class="mb-4 text-primary">Gestión de Facturas</h2>
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">Agregar Factura</div>
        <div class="card-body">
            <form method="POST">
                <div class="row g-2 mb-2">
                    <div class="col-md-3">
                        <input type="number" step="0.01" name="montoTotal" class="form-control" placeholder="Monto Total" required>
                    </div>
                    <div class="col-md-3">
                        <input type="date" name="fechaPago" class="form-control" placeholder="Fecha Pago">
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="metodoPago" class="form-control" placeholder="Método Pago">
                    </div>
                    <div class="col-md-3">
                        <input type="number" step="0.01" name="descuento" class="form-control" placeholder="Descuento">
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
        <div class="card-header bg-secondary text-white">Lista de Facturas</div>
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Monto</th>
                        <th>Fecha Pago</th>
                        <th>Método</th>
                        <th>Descuento</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                <?php while ($row = $facturas->fetch_assoc()): ?>
                    <tr>
                        <td><?= $row['idFactura'] ?></td>
                        <td><?= htmlspecialchars($row['montoTotal']) ?></td>
                        <td><?= htmlspecialchars($row['fechaPago']) ?></td>
                        <td><?= htmlspecialchars($row['metodoPago']) ?></td>
                        <td><?= htmlspecialchars($row['descuento']) ?></td>
                        <td>
                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal<?= $row['idFactura'] ?>">Editar</button>
                            <a href="?delete=<?= $row['idFactura'] ?>" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar esta factura?')">Eliminar</a>
                        </td>
                    </tr>
                    <div class="modal fade" id="editModal<?= $row['idFactura'] ?>" tabindex="-1">
                        <div class="modal-dialog">
                            <form method="POST" class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Editar Factura</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="idFactura" value="<?= $row['idFactura'] ?>">
                                    <div class="mb-2">
                                        <label>Monto Total</label>
                                        <input type="number" step="0.01" name="montoTotal" class="form-control" value="<?= htmlspecialchars($row['montoTotal']) ?>" required>
                                    </div>
                                    <div class="mb-2">
                                        <label>Fecha Pago</label>
                                        <input type="date" name="fechaPago" class="form-control" value="<?= htmlspecialchars($row['fechaPago']) ?>">
                                    </div>
                                    <div class="mb-2">
                                        <label>Método Pago</label>
                                        <input type="text" name="metodoPago" class="form-control" value="<?= htmlspecialchars($row['metodoPago']) ?>">
                                    </div>
                                    <div class="mb-2">
                                        <label>Descuento</label>
                                        <input type="number" step="0.01" name="descuento" class="form-control" value="<?= htmlspecialchars($row['descuento']) ?>">
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
