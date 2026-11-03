<?php
// pagina de inventario (Persona 3)
session_start();
require "funciones.php";
cargarDatosDePrueba();

$categorias = array("Venta", "Uso interno");
$filtros    = array("Todas", "Venta", "Uso interno");
$tipos      = array("Entrada", "Venta", "Consumo");

// filtros de la tabla (vienen por GET)
$buscar = "";
if (isset($_GET["buscar"])) {
    $buscar = trim($_GET["buscar"]);
}

$filtro = "Todas";
if (isset($_GET["categoria"]) && in_array($_GET["categoria"], $filtros)) {
    $filtro = $_GET["categoria"];
}

// posicion del producto que se edita (-1 = se esta agregando uno nuevo)
$editar = -1;
if (isset($_GET["editar"])) {
    $editar = (int)$_GET["editar"];
}
if ($editar < 0 || $editar >= count($_SESSION["productos"])) {
    $editar = -1;
}

// valores que se muestran en el formulario
if ($editar >= 0) {
    $formulario = $_SESSION["productos"][$editar];
} else {
    $formulario = array("Nombre" => "", "Categoria" => "Venta", "Compra" => "", "Venta" => "", "Cantidad" => "", "Minimo" => "");
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

<!-- formulario para agregar o editar un producto -->
<section>
    <h3><?php echo ($editar >= 0) ? "Editar producto" : "Nuevo producto"; ?></h3>

    <form method="post" action="procesar.php" class="validar" id="form-producto">
        <input type="hidden" name="accion" value="<?php echo ($editar >= 0) ? "editar_producto" : "agregar_producto"; ?>">
        <?php if ($editar >= 0) { ?>
            <input type="hidden" name="posicion" value="<?php echo $editar; ?>">
        <?php } ?>

        <label for="nombre">Nombre del producto</label>
        <input type="text" id="nombre" name="nombre" required
               data-nombre="El nombre" data-min="3" data-max="60"
               value="<?php echo htmlspecialchars($formulario["Nombre"]); ?>">

        <label for="categoria">Categoría</label>
        <select id="categoria" name="categoria">
            <?php opciones($categorias, $formulario["Categoria"]); ?>
        </select>

        <label for="compra">Precio de compra (₡)</label>
        <input type="number" id="compra" name="compra" required min="1" step="1"
               data-nombre="El precio de compra" data-tipo="entero" data-min="1" data-max="1000000"
               value="<?php echo htmlspecialchars($formulario["Compra"]); ?>">

        <label for="venta">Precio de venta (₡) - los productos de uso interno quedan en 0</label>
        <input type="number" id="venta" name="venta" required min="0" step="1"
               data-nombre="El precio de venta" data-tipo="entero" data-min="0" data-max="1000000"
               value="<?php echo htmlspecialchars($formulario["Venta"]); ?>">

        <label for="cantidad">Cantidad en stock</label>
        <input type="number" id="cantidad" name="cantidad" required min="0" step="1"
               data-nombre="La cantidad" data-tipo="entero" data-min="0" data-max="100000"
               value="<?php echo htmlspecialchars($formulario["Cantidad"]); ?>">

        <label for="minimo">Stock mínimo (avisa cuando se llega a esta cantidad)</label>
        <input type="number" id="minimo" name="minimo" required min="0" step="1"
               data-nombre="El stock mínimo" data-tipo="entero" data-min="0" data-max="100000"
               value="<?php echo htmlspecialchars($formulario["Minimo"]); ?>">

        <button type="submit"><?php echo ($editar >= 0) ? "Guardar cambios" : "Agregar producto"; ?></button>
        <?php if ($editar >= 0) { ?>
            <a href="inventario.php">Cancelar</a>
        <?php } ?>
    </form>
</section>

<!-- entradas, ventas y consumo de stock -->
<section>
    <h3>Registrar movimiento de stock</h3>

    <form method="post" action="procesar.php" class="validar" id="form-movimiento">
        <input type="hidden" name="accion" value="movimiento_stock">

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
            <strong>Entrada</strong>: compra de mercadería (suma stock y registra un gasto).<br>
            <strong>Venta</strong>: producto vendido (resta stock y registra un ingreso).<br>
            <strong>Consumo</strong>: uso interno (solo resta stock).
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
            <th>Acciones</th>
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
            <td>
                <a href="inventario.php?editar=<?php echo $i; ?>">Editar</a>

                <form method="post" action="procesar.php" style="display:inline"
                      data-confirmar="<?php echo htmlspecialchars("¿Eliminar el producto " . $producto["Nombre"] . "?"); ?>">
                    <input type="hidden" name="accion" value="eliminar_producto">
                    <input type="hidden" name="posicion" value="<?php echo $i; ?>">
                    <button type="submit">Eliminar</button>
                </form>
            </td>
        </tr>
        <?php } ?>
    </table>

    <?php if ($mostrados == 0) { ?>
        <p>No se encontraron productos.</p>
    <?php } ?>
</section>

<?php include "pie.php"; ?>
