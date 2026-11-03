<?php
// agenda de citas

session_start();
require_once "funciones.php";
cargarDatosDePrueba();

$citas = $_SESSION["citas"];

$nombresClientes = array();
for ($i = 0; $i < count($_SESSION["clientes"]); $i++) {
    array_push($nombresClientes, $_SESSION["clientes"][$i]["Nombre"]);
}

// solo productos de venta con existencias
$productosVenta = array();
for ($i = 0; $i < count($_SESSION["productos"]); $i++) {
    $producto = $_SESSION["productos"][$i];
    if ($producto["Categoria"] == "Venta" && $producto["Cantidad"] > 0) {
        array_push($productosVenta, $producto["Nombre"]);
    }
}

// modo editar: citas.php?editar=2 (-1 = cita nueva)
$posicion = -1;
$cliente  = "";
$servicio = "";
$barbero  = "";
$fecha    = fechaHoy();
$hora     = "";

if (isset($_GET["editar"]) && isset($citas[$_GET["editar"]])) {
    $posicion = $_GET["editar"];
    $cliente  = $citas[$posicion]["Cliente"];
    $servicio = $citas[$posicion]["Servicio"];
    $barbero  = $citas[$posicion]["Barbero"];
    $fecha    = $citas[$posicion]["Fecha"];
    $hora     = $citas[$posicion]["Hora"];
}

// dia que se muestra en la agenda
$diaAgenda = fechaHoy();
if (isset($_GET["dia"]) && $_GET["dia"] != "") {
    $diaAgenda = $_GET["dia"];
}

require "encabezado.php";
?>

<!-- formulario de cita -->
<section>
    <?php
    if ($posicion == -1) {
        echo "<h2>Nueva cita</h2>";
    } else {
        echo "<h2>Editar cita N° $posicion</h2>";
    }
    ?>

    <form method="post" action="procesar.php" onsubmit="return validarCita()">
        <input type="hidden" name="accion" value="guardar_cita">
        <input type="hidden" name="posicion" value="<?php echo $posicion; ?>">

        <label for="cliente">Cliente</label>
        <select id="cliente" name="cliente">
            <option value="">Seleccione</option>
            <?php opciones($nombresClientes, $cliente); ?>
        </select>

        <label for="servicio">Servicio</label>
        <select id="servicio" name="servicio">
            <option value="">Seleccione</option>
            <?php
            foreach ($servicios as $nombre => $precio) {
                if ($nombre == $servicio) {
                    echo "<option value='$nombre' selected>$nombre - " . colones($precio) . "</option>";
                } else {
                    echo "<option value='$nombre'>$nombre - " . colones($precio) . "</option>";
                }
            }
            ?>
        </select>

        <label for="barbero">Barbero</label>
        <select id="barbero" name="barbero">
            <option value="">Seleccione</option>
            <?php opciones($barberos, $barbero); ?>
        </select>

        <label for="fecha">Fecha</label>
        <input type="date" id="fecha" name="fecha" value="<?php echo $fecha; ?>">

        <label for="hora">Hora</label>
        <select id="hora" name="hora">
            <option value="">Seleccione</option>
            <?php opciones($horas, $hora); ?>
        </select>

        <p id="error_cita" class="error"></p>
        <button type="submit">Guardar cita</button>
        <?php
        if ($posicion != -1) {
            echo "<a href='citas.php'>Cancelar edición</a>";
        }
        ?>
    </form>
</section>

<!-- agenda del dia -->
<section>
    <h2>Agenda del <?php echo $diaAgenda; ?></h2>

    <form method="get" action="citas.php">
        <input type="date" name="dia" value="<?php echo $diaAgenda; ?>">
        <button type="submit">Ver día</button>
    </form>

    <table>
        <tr>
            <th>N°</th><th>Hora</th><th>Cliente</th><th>Servicio</th><th>Barbero</th><th>Estado</th><th></th>
        </tr>
        <?php
        $cantidad = 0;
        for ($i = 0; $i < count($citas); $i++) {
            if ($citas[$i]["Fecha"] == $diaAgenda) {
                $cantidad++;
                echo "<tr>";
                echo "<td>$i</td>";
                echo "<td>" . $citas[$i]["Hora"] . "</td>";
                echo "<td>" . htmlspecialchars($citas[$i]["Cliente"]) . "</td>";
                echo "<td>" . $citas[$i]["Servicio"] . "</td>";
                echo "<td>" . $citas[$i]["Barbero"] . "</td>";
                echo "<td>" . $citas[$i]["Estado"] . "</td>";

                if ($citas[$i]["Estado"] == "Pendiente") {
                    echo "<td><a href='citas.php?editar=$i'>Editar</a></td>";
                } else {
                    echo "<td></td>";
                }
                echo "</tr>";
            }
        }
        if ($cantidad == 0) {
            echo "<tr><td colspan='7'>No hay citas este día.</td></tr>";
        }
        ?>
    </table>
</section>

<!-- cobrar o cancelar -->
<section>
    <h2>Cobrar o cancelar una cita</h2>
    <p>Al cobrar, el dinero se registra en Finanzas y el producto vendido se descuenta del Inventario.</p>

    <form method="post" action="procesar.php">
        <label for="posicion_cita">Cita pendiente</label>
        <select id="posicion_cita" name="posicion">
            <option value="">Seleccione</option>
            <?php
            for ($i = 0; $i < count($citas); $i++) {
                if ($citas[$i]["Estado"] == "Pendiente") {
                    echo "<option value='$i'>N° $i - " . htmlspecialchars($citas[$i]["Cliente"])
                        . " - " . $citas[$i]["Fecha"] . " " . $citas[$i]["Hora"] . "</option>";
                }
            }
            ?>
        </select>

        <label for="producto">Producto vendido (opcional)</label>
        <select id="producto" name="producto">
            <option value="">Ninguno</option>
            <?php opciones($productosVenta, ""); ?>
        </select>

        <!-- cada boton envia su propio valor en "accion" -->
        <button type="submit" name="accion" value="cobrar_cita">Cobrar</button>
        <button type="submit" name="accion" value="cancelar_cita">Cancelar cita</button>
    </form>
</section>

<?php require "pie.php"; ?>
