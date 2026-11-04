<?php
// pagina de inventario (Persona 3)
session_start();
require "funciones.php";
cargarDatosDePrueba();

$categorias = array("Venta", "Uso interno");
$filtros    = array("Todas", "Venta", "Uso interno");
$tipos      = array("Entrada", "Salida");

// filtros de la tabla (vienen por GET)
$buscar = "";
if (isset($_GET["buscar"])) {
    $buscar = trim($_GET["buscar"]);
}

$filtro = "Todas";
if (isset($_GET["categoria"]) && in_array($_GET["categoria"], $filtros)) {
    $filtro = $_GET["categoria"];
}

// resumen del inventario (con todos los productos, sin filtro)
$valorInventario = 0;
$nombres = array();
$bajos = array();

for ($i = 0; $i < count($_SESSION["productos"]); $i++) {
    $p = $_SESSION["productos"][$i];

    $valorInventario += $p["Compra"] * $p["Cantidad"];
    array_push($nombres, $p["Nombre"]);

    // stock bajo = la cantidad llego al minimo o esta por debajo
    if ($p["Cantidad"] <= $p["Minimo"]) {
        array_push($bajos, $p["Nombre"]);
    }
}

include "encabezado.php";
?>

<h2>Inventario</h2>

<!-- resumen y alerta de stock bajo -->
<section>
    <h3>Resumen</h3>
    <p>Productos registrados: <strong><?php echo count($_SESSION["productos"]); ?></strong></p>
    <p>Valor del inventario (a precio de compra): <strong><?php echo colones($valorInventario); ?></strong></p>

    <?php if (count($bajos) > 0) { ?>
        <p class="error">
            Atención: <?php echo count($bajos); ?> producto(s) con stock bajo:
            <?php echo htmlspecialchars(implode(", ", $bajos)); ?>.
        </p>
    <?php } else { ?>
        <p class="exito">Todos los productos tienen stock suficiente.</p>
    <?php } ?>
</section>

<!-- formulario para agregar un producto (lo procesa procesar.php: agregar_producto) -->
<section>
    <h3>Nuevo producto</h3>

    <form method="post" action="procesar.php" class="validar" id="form-producto">
        <input type="hidden" name="accion" value="agregar_producto">

        <label for="nombre">Nombre del producto</label>
        <input type="text" id="nombre" name="nombre" required
               data-nombre="El nombre" data-min="3" data-max="60">

        <label for="categoria">Categoría</label>
        <select id="categoria" name="categoria">
            <?php opciones($categorias, "Venta"); ?>
        </select>

        <label for="compra">Precio de compra (₡)</label>
        <input type="number" id="compra" name="compra" required min="1" step="1"
               data-nombre="El precio de compra" data-tipo="entero" data-min="1" data-max="1000000">

        <label for="venta">Precio de venta (₡) - los productos de uso interno quedan en 0</label>
        <input type="number" id="venta" name="venta" required min="0" step="1"
               data-nombre="El precio de venta" data-tipo="entero" data-min="0" data-max="1000000">

        <label for="cantidad">Cantidad en stock</label>
        <input type="number" id="cantidad" name="cantidad" required min="0" step="1"
               data-nombre="La cantidad" data-tipo="entero" data-min="0" data-max="100000">

        <label for="minimo">Stock mínimo (avisa cuando se llega a esta cantidad)</label>
        <input type="number" id="minimo" name="minimo" required min="0" step="1"
               data-nombre="El stock mínimo" data-tipo="entero" data-min="0" data-max="100000">

        <button type="submit">Agregar producto</button>
    </form>
</section>

<!-- entradas y salidas de stock (lo procesa procesar.php: mover_existencias) -->
<section>
    <h3>Registrar movimiento de stock</h3>

    <form method="post" action="procesar.php" class="validar" id="form-movimiento">
        <input type="hidden" name="accion" value="mover_existencias">

        <label for="producto">Producto</label>
        <select id="producto" name="producto">
            <?php opciones($nombres, ""); ?>
        </select>

        <label for="tipo">Tipo de movimiento</label>
        <select id="tipo" name="tipo">
            <?php opciones($tipos, "Entrada"); ?>
        </select>

        <label for="cantidad-mov">Cantidad</label>
        <input type="number" id="cantidad-mov" name="cantidad" required min="1" step="1"
               data-nombre="La cantidad" data-tipo="entero" data-min="1" data-max="100000">

        <p>
            <strong>Entrada</strong>: compra de mercadería (suma stock y registra un gasto en Finanzas).<br>
            <strong>Salida</strong>: uso interno o merma (solo resta stock). Las ventas de productos se registran al cobrar una cita.
        </p>

        <button type="submit">Registrar movimiento</button>
    </form>
</section>

<!-- tabla de productos con filtros -->
<section>
    <h3>Lista de productos</h3>

    <form method="get" action="inventario.php">
        <input type="text" name="buscar" placeholder="Buscar por nombre"
               value="<?php echo htmlspecialchars($buscar); ?>">
        <select name="categoria">
            <?php opciones($filtros, $filtro); ?>
        </select>
        <button type="submit">Filtrar</button>
        <a href="inventario.php">Limpiar</a>
    </form>

    <table>
        <tr>
            <th>Producto</th>
            <th>Categoría</th>
            <th>Compra</th>
            <th>Venta</th>
            <th>Cantidad</th>
            <th>Mínimo</th>
            <th>Estado</th>
        </tr>
        <?php
        $mostrados = 0;

        for ($i = 0; $i < count($_SESSION["productos"]); $i++) {
            $producto = $_SESSION["productos"][$i];

            // filtros: categoria y texto del nombre
            if ($filtro != "Todas" && $producto["Categoria"] != $filtro) {
                continue;
            }
            if ($buscar != "" && mb_stripos($producto["Nombre"], $buscar) === false) {
                continue;
            }

            $mostrados++;
            $stockBajo = ($producto["Cantidad"] <= $producto["Minimo"]);
        ?>
        <tr>
            <td><?php echo htmlspecialchars($producto["Nombre"]); ?></td>
            <td><?php echo htmlspecialchars($producto["Categoria"]); ?></td>
            <td><?php echo colones($producto["Compra"]); ?></td>
            <td><?php echo ($producto["Categoria"] == "Venta") ? colones($producto["Venta"]) : "-"; ?></td>
            <td><?php echo $producto["Cantidad"]; ?></td>
            <td><?php echo $producto["Minimo"]; ?></td>
            <td>
                <?php if ($stockBajo) { ?>
                    <span class="error">Stock bajo</span>
                <?php } else { ?>
                    <span class="exito">Disponible</span>
                <?php } ?>
            </td>
        </tr>
        <?php } ?>
    </table>

    <?php if ($mostrados == 0) { ?>
        <p>No se encontraron productos.</p>
    <?php } ?>
</section>

<?php include "pie.php"; ?>
