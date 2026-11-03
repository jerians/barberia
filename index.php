<?php
// inicio: resumen del dia

session_start();
require_once "funciones.php";
cargarDatosDePrueba();

$citas       = $_SESSION["citas"];
$productos   = $_SESSION["productos"];
$movimientos = $_SESSION["movimientos"];

// citas pendientes de hoy
$pendientes = 0;
for ($i = 0; $i < count($citas); $i++) {
    if ($citas[$i]["Fecha"] == fechaHoy() && $citas[$i]["Estado"] == "Pendiente") {
        $pendientes++;
    }
}

// ingresos y gastos de hoy
$ingresosHoy = 0;
$gastosHoy = 0;
for ($i = 0; $i < count($movimientos); $i++) {
    if ($movimientos[$i]["Fecha"] == fechaHoy()) {
        if ($movimientos[$i]["Tipo"] == "Ingreso") {
            $ingresosHoy = $ingresosHoy + $movimientos[$i]["Monto"];
        } else {
            $gastosHoy = $gastosHoy + $movimientos[$i]["Monto"];
        }
    }
}

require "encabezado.php";
?>

<section>
    <h2>Resumen de hoy (<?php echo fechaHoy(); ?>)</h2>
    <ul>
        <li>Citas pendientes: <strong><?php echo $pendientes; ?></strong></li>
        <li>Clientes registrados: <strong><?php echo count($_SESSION["clientes"]); ?></strong></li>
        <li>Ingresos de hoy: <strong><?php echo colones($ingresosHoy); ?></strong></li>
        <li>Gastos de hoy: <strong><?php echo colones($gastosHoy); ?></strong></li>
    </ul>
</section>

<section>
    <h2>Citas de hoy</h2>
    <table>
        <tr><th>Hora</th><th>Cliente</th><th>Servicio</th><th>Barbero</th><th>Estado</th></tr>
        <?php
        for ($i = 0; $i < count($citas); $i++) {
            if ($citas[$i]["Fecha"] == fechaHoy()) {
                echo "<tr>";
                echo "<td>" . $citas[$i]["Hora"] . "</td>";
                echo "<td>" . htmlspecialchars($citas[$i]["Cliente"]) . "</td>";
                echo "<td>" . $citas[$i]["Servicio"] . "</td>";
                echo "<td>" . $citas[$i]["Barbero"] . "</td>";
                echo "<td>" . $citas[$i]["Estado"] . "</td>";
                echo "</tr>";
            }
        }
        ?>
    </table>
</section>

<section>
    <h2>Productos por reabastecer</h2>
    <?php
    $bajos = 0;
    for ($i = 0; $i < count($productos); $i++) {
        if ($productos[$i]["Cantidad"] <= $productos[$i]["Minimo"]) {
            $bajos++;
            echo "<p class='error'>" . htmlspecialchars($productos[$i]["Nombre"])
                . ": quedan " . $productos[$i]["Cantidad"] . " (mínimo " . $productos[$i]["Minimo"] . ")</p>";
        }
    }
    if ($bajos == 0) {
        echo "<p class='exito'>Todo el inventario está bien.</p>";
    }
    ?>
</section>

<?php require "pie.php"; ?>
