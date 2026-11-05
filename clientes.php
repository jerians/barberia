<?php
// registro y ficha de clientes

session_start();
require_once "funciones.php";
cargarDatosDePrueba();

$clientes = $_SESSION["clientes"];
$citas    = $_SESSION["citas"];

require "encabezado.php";
?>

<!-- formulario -->
<section>
    <h2>Nuevo cliente</h2>
    <form method="post" action="procesar.php" onsubmit="return validarCliente()">
        <input type="hidden" name="accion" value="agregar_cliente">

        <label for="nombre">Nombre completo</label>
        <input type="text" id="nombre" name="nombre">

        <label for="telefono">Teléfono (8888-8888)</label>
        <input type="text" id="telefono" name="telefono">

        <label for="preferencias">Preferencias</label>
        <textarea id="preferencias" name="preferencias" rows="3"></textarea>

        <p id="error_cliente" class="error"></p>
        <button type="submit">Registrar cliente</button>
    </form>
</section>

<!-- lista de clientes -->
<section>
    <h2>Clientes registrados</h2>
    <table>
        <tr><th>Nombre</th><th>Teléfono</th><th>Desde</th><th></th></tr>
        <?php
        for ($i = 0; $i < count($clientes); $i++) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($clientes[$i]["Nombre"]) . "</td>";
            echo "<td>" . htmlspecialchars($clientes[$i]["Telefono"]) . "</td>";
            echo "<td>" . $clientes[$i]["Registro"] . "</td>";
            echo "<td><a href='clientes.php?ver=$i'>Ver ficha</a></td>";
            echo "</tr>";
        }
        ?>
    </table>
</section>

<!-- ficha e historial -->
<?php
// solo se muestra si viene clientes.php?ver=numero
if (isset($_GET["ver"]) && isset($clientes[$_GET["ver"]])) {
    $cliente = $clientes[$_GET["ver"]];

    echo "<section>";
    echo "<h2>Ficha de " . htmlspecialchars($cliente["Nombre"]) . "</h2>";
    echo "<p>Teléfono: " . htmlspecialchars($cliente["Telefono"]) . "</p>";
    echo "<p>Preferencias: " . htmlspecialchars($cliente["Preferencias"]) . "</p>";

    echo "<table>";
    echo "<tr><th>Fecha</th><th>Servicio</th><th>Barbero</th><th>Estado</th></tr>";

    $visitas = 0;
    $total = 0;
    for ($i = 0; $i < count($citas); $i++) {
        if ($citas[$i]["Cliente"] == $cliente["Nombre"]) {
            echo "<tr>";
            echo "<td>" . $citas[$i]["Fecha"] . "</td>";
            echo "<td>" . $citas[$i]["Servicio"] . "</td>";
            echo "<td>" . $citas[$i]["Barbero"] . "</td>";
            echo "<td>" . $citas[$i]["Estado"] . "</td>";
            echo "</tr>";

            // solo las finalizadas se pagaron
            if ($citas[$i]["Estado"] == "Finalizada") {
                $visitas++;
                $total = $total + $servicios[$citas[$i]["Servicio"]];
            }
        }
    }
    echo "</table>";

    echo "<p>Visitas: $visitas | Total pagado: " . colones($total) . "</p>";
    echo "<a href='clientes.php'>Cerrar ficha</a>";
    echo "</section>";
}
?>

<?php require "pie.php"; ?>
