<?php
// inventario

session_start();
require_once "funciones.php";
cargarDatosDePrueba();

$productos = $_SESSION["productos"];

$nombres = array();
for ($i = 0; $i < count($productos); $i++) {
    array_push($nombres, $productos[$i]["Nombre"]);
}

require "encabezado.php";
?>

<!-- lista de productos -->
<section>
    <h2>Productos</h2>
    <table>
        <tr><th>Producto</th><th>Categoría</th><th>Compra</th><th>Venta</th><th>Cantidad</th><th>Mínimo</th><th>Estado</th></tr>
        <?php
        for ($i = 0; $i < count($productos); $i++) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($productos[$i]["Nombre"]) . "</td>";
            echo "<td>" . $productos[$i]["Categoria"] . "</td>";
            echo "<td>" . colones($productos[$i]["Compra"]) . "</td>";
            echo "<td>" . colones($productos[$i]["Venta"]) . "</td>";
            echo "<td>" . $productos[$i]["Cantidad"] . "</td>";
            echo "<td>" . $productos[$i]["Minimo"] . "</td>";

            if ($productos[$i]["Cantidad"] <= $productos[$i]["Minimo"]) {
                echo "<td class='error'>Stock bajo</td>";
            } else {
                echo "<td>Disponible</td>";
            }
            echo "</tr>";
        }
        ?>
    </table>
</section>

<!-- nuevo producto -->
<section>
    <h2>Nuevo producto</h2>
    <form method="post" action="procesar.php" onsubmit="return validarProducto()">
        <input type="hidden" name="accion" value="agregar_producto">

        <label for="nombre">Nombre</label>
        <input type="text" id="nombre" name="nombre">

        <label for="categoria">Categoría</label>
        <select id="categoria" name="categoria">
            <option value="">Seleccione</option>
            <option value="Venta">Venta</option>
            <option value="Uso interno">Uso interno</option>
        </select>

        <label for="compra">Precio de compra</label>
        <input type="number" id="compra" name="compra" min="0">

        <label for="venta">Precio de venta (0 si es de uso interno)</label>
        <input type="number" id="venta" name="venta" min="0">

        <label for="cantidad">Cantidad</label>
        <input type="number" id="cantidad" name="cantidad" min="0">

        <label for="minimo">Stock mínimo</label>
        <input type="number" id="minimo" name="minimo" min="0">

        <p id="error_producto" class="error"></p>
        <button type="submit">Registrar producto</button>
    </form>
</section>

<!-- entradas y salidas -->
<section>
    <h2>Actualizar existencias</h2>
    <form method="post" action="procesar.php" onsubmit="return validarExistencias()">
        <input type="hidden" name="accion" value="mover_existencias">

        <label for="producto_mov">Producto</label>
        <select id="producto_mov" name="producto">
            <option value="">Seleccione</option>
            <?php opciones($nombres, ""); ?>
        </select>

        <p>
            <input type="radio" id="entrada" name="tipo" value="Entrada" checked>
            <label for="entrada" class="en-linea">Entrada (compra, se registra como gasto)</label>
            <br>
            <input type="radio" id="salida" name="tipo" value="Salida">
            <label for="salida" class="en-linea">Salida (uso o pérdida)</label>
        </p>

        <label for="cantidad_mov">Cantidad</label>
        <input type="number" id="cantidad_mov" name="cantidad" min="1" value="1">

        <p id="error_existencias" class="error"></p>
        <button type="submit">Actualizar</button>
    </form>
</section>

<?php require "pie.php"; ?>
