<?php
// pagina de clientes (Persona 3)
session_start();
require "funciones.php";
cargarDatosDePrueba();

// texto del buscador (viene por GET)
$buscar = "";
if (isset($_GET["buscar"])) {
    $buscar = trim($_GET["buscar"]);
}

// posicion del cliente del que se muestra el historial (-1 = ninguno)
$ver = -1;
if (isset($_GET["ver"])) {
    $ver = (int)$_GET["ver"];
}
if ($ver < 0 || $ver >= count($_SESSION["clientes"])) {
    $ver = -1;
}

include "encabezado.php";
?>

<h2>Clientes</h2>

<!-- formulario para agregar un cliente (lo procesa procesar.php: agregar_cliente) -->
<section>
    <h3>Nuevo cliente</h3>

    <form method="post" action="procesar.php" class="validar">
        <input type="hidden" name="accion" value="agregar_cliente">

        <label for="nombre">Nombre completo</label>
        <input type="text" id="nombre" name="nombre" required
               data-nombre="El nombre" data-min="3" data-max="60">

        <label for="telefono">Teléfono</label>
        <input type="text" id="telefono" name="telefono" required
               data-nombre="El teléfono" data-tipo="telefono" placeholder="8888-8888">

        <label for="preferencias">Preferencias (opcional)</label>
        <textarea id="preferencias" name="preferencias" rows="3"
                  data-nombre="Las preferencias" data-max="120"></textarea>

        <button type="submit">Agregar cliente</button>
    </form>
</section>

<!-- historial de citas del cliente seleccionado -->
<?php
if ($ver >= 0) {
    $clienteVer = $_SESSION["clientes"][$ver];
    $totalGastado = 0;
    $hayCitas = false;
?>
<section>
    <h3>Historial de <?php echo htmlspecialchars($clienteVer["Nombre"]); ?></h3>

    <table>
        <tr>
            <th>Fecha</th>
            <th>Hora</th>
            <th>Servicio</th>
            <th>Barbero</th>
            <th>Estado</th>
            <th>Precio</th>
        </tr>
        <?php
        for ($i = 0; $i < count($_SESSION["citas"]); $i++) {
            $cita = $_SESSION["citas"][$i];

            if ($cita["Cliente"] != $clienteVer["Nombre"]) {
                continue;
            }

            $hayCitas = true;
            $precio = isset($servicios[$cita["Servicio"]]) ? $servicios[$cita["Servicio"]] : 0;

            // solo las citas finalizadas cuentan como dinero gastado
            if ($cita["Estado"] == "Finalizada") {
                $totalGastado += $precio;
            }
        ?>
        <tr>
            <td><?php echo htmlspecialchars($cita["Fecha"]); ?></td>
            <td><?php echo htmlspecialchars($cita["Hora"]); ?></td>
            <td><?php echo htmlspecialchars($cita["Servicio"]); ?></td>
            <td><?php echo htmlspecialchars($cita["Barbero"]); ?></td>
            <td><?php echo htmlspecialchars($cita["Estado"]); ?></td>
            <td><?php echo colones($precio); ?></td>
        </tr>
        <?php } ?>
    </table>

    <?php if (!$hayCitas) { ?>
        <p>Este cliente todavía no tiene citas.</p>
    <?php } ?>

    <p>Total gastado en citas finalizadas: <strong><?php echo colones($totalGastado); ?></strong></p>
    <a href="clientes.php">Cerrar historial</a>
</section>
<?php } ?>

<!-- lista de clientes con buscador -->
<section>
    <h3>Lista de clientes (<?php echo count($_SESSION["clientes"]); ?>)</h3>

    <form method="get" action="clientes.php">
        <input type="text" name="buscar" placeholder="Buscar por nombre o teléfono"
               value="<?php echo htmlspecialchars($buscar); ?>">
        <button type="submit">Buscar</button>
        <a href="clientes.php">Limpiar</a>
    </form>

    <table>
        <tr>
            <th>Nombre</th>
            <th>Teléfono</th>
            <th>Registro</th>
            <th>Citas</th>
            <th>Preferencias</th>
            <th>Historial</th>
        </tr>
        <?php
        $mostrados = 0;

        for ($i = 0; $i < count($_SESSION["clientes"]); $i++) {
            $cliente = $_SESSION["clientes"][$i];

            // si hay busqueda, se salta los clientes que no coinciden
            if ($buscar != "" && mb_stripos($cliente["Nombre"] . " " . $cliente["Telefono"], $buscar) === false) {
                continue;
            }

            $mostrados++;

            // cuantas citas tiene (sin contar las canceladas)
            $totalCitas = 0;
            for ($j = 0; $j < count($_SESSION["citas"]); $j++) {
                if ($_SESSION["citas"][$j]["Cliente"] == $cliente["Nombre"] && $_SESSION["citas"][$j]["Estado"] != "Cancelada") {
                    $totalCitas++;
                }
            }
        ?>
        <tr>
            <td><?php echo htmlspecialchars($cliente["Nombre"]); ?></td>
            <td><?php echo htmlspecialchars($cliente["Telefono"]); ?></td>
            <td><?php echo htmlspecialchars($cliente["Registro"]); ?></td>
            <td><?php echo $totalCitas; ?></td>
            <td><?php echo htmlspecialchars($cliente["Preferencias"]); ?></td>
            <td><a href="clientes.php?ver=<?php echo $i; ?>">Ver historial</a></td>
        </tr>
        <?php } ?>
    </table>

    <?php if ($mostrados == 0) { ?>
        <p>No se encontraron clientes.</p>
    <?php } ?>
</section>

<?php include "pie.php"; ?>
