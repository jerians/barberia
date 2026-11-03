<?php
// caja y reportes

session_start();
require_once "funciones.php";
cargarDatosDePrueba();

$movimientos = $_SESSION["movimientos"];

// periodo del reporte (por defecto hoy)
$periodo = "dia";
if (isset($_GET["periodo"])) {
    $periodo = $_GET["periodo"];
}

// $desde = primera fecha que entra en el reporte
if ($periodo == "semana") {
    $desde = fechaHace(6);    // hoy y los 6 dias anteriores
} elseif ($periodo == "mes") {
    $desde = substr(fechaHoy(), 0, 7) . "-01";    // dia 1 del mes
} else {
    $desde = fechaHoy();
}

// las fechas "AAAA-MM-DD" se pueden comparar como texto
$ingresos = 0;
$gastos = 0;
for ($i = 0; $i < count($movimientos); $i++) {
    if ($movimientos[$i]["Fecha"] >= $desde) {
        if ($movimientos[$i]["Tipo"] == "Ingreso") {
            $ingresos = $ingresos + $movimientos[$i]["Monto"];
        } else {
            $gastos = $gastos + $movimientos[$i]["Monto"];
        }
    }
}

require "encabezado.php";
?>

<!-- reporte -->
<section>
    <h2>Reporte de caja</h2>

    <form method="get" action="finanzas.php">
        <select name="periodo">
            <option value="dia">Hoy</option>
            <option value="semana" <?php if ($periodo == "semana") { echo "selected"; } ?>>Últimos 7 días</option>
            <option value="mes" <?php if ($periodo == "mes") { echo "selected"; } ?>>Este mes</option>
        </select>
        <button type="submit">Ver</button>
    </form>

    <ul>
        <li>Ingresos: <strong><?php echo colones($ingresos); ?></strong></li>
        <li>Gastos: <strong><?php echo colones($gastos); ?></strong></li>
        <li>Balance: <strong><?php echo colones($ingresos - $gastos); ?></strong></li>
    </ul>

    <table>
        <tr><th>Fecha</th><th>Tipo</th><th>Descripción</th><th>Monto</th></tr>
        <?php
        for ($i = 0; $i < count($movimientos); $i++) {
            if ($movimientos[$i]["Fecha"] >= $desde) {
                echo "<tr>";
                echo "<td>" . $movimientos[$i]["Fecha"] . "</td>";
                echo "<td>" . $movimientos[$i]["Tipo"] . "</td>";
                echo "<td>" . htmlspecialchars($movimientos[$i]["Descripcion"]) . "</td>";
                echo "<td>" . colones($movimientos[$i]["Monto"]) . "</td>";
                echo "</tr>";
            }
        }
        ?>
    </table>
</section>

<!-- nuevo movimiento -->
<section>
    <h2>Registrar ingreso o gasto</h2>
    <form method="post" action="procesar.php" onsubmit="return validarMovimiento()">
        <input type="hidden" name="accion" value="agregar_movimiento">

        <p>
            <input type="radio" id="ingreso" name="tipo" value="Ingreso" checked>
            <label for="ingreso" class="en-linea">Ingreso</label>
            <input type="radio" id="gasto" name="tipo" value="Gasto">
            <label for="gasto" class="en-linea">Gasto</label>
        </p>

        <label for="descripcion">Descripción</label>
        <input type="text" id="descripcion" name="descripcion">

        <label for="monto">Monto</label>
        <input type="number" id="monto" name="monto" min="1">

        <label for="fecha">Fecha</label>
        <input type="date" id="fecha" name="fecha" value="<?php echo fechaHoy(); ?>">

        <p id="error_movimiento" class="error"></p>
        <button type="submit">Registrar</button>
    </form>
</section>

<?php require "pie.php"; ?>
