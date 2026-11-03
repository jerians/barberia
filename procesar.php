<?php
// recibe todos los formularios, hace el cambio y regresa a la pagina

session_start();
require_once "funciones.php";
cargarDatosDePrueba();

// si se abre directo en el navegador, vuelve al inicio
if (!isset($_POST["accion"])) {
    header("Location: index.php");
    exit;
}

$accion = $_POST["accion"];
$volver = "index.php";

try {

    switch ($accion) {

        // CITAS

        // nueva (posicion = -1) o editada
        case "guardar_cita":
            $volver   = "citas.php";
            $posicion = $_POST["posicion"];
            $cliente  = $_POST["cliente"];
            $servicio = $_POST["servicio"];
            $barbero  = $_POST["barbero"];
            $fecha    = $_POST["fecha"];
            $hora     = $_POST["hora"];

            if ($cliente == "" || $servicio == "" || $barbero == "" || $fecha == "" || $hora == "") {
                throw new Exception("Complete todos los campos de la cita.");
            }
            if ($fecha < fechaHoy()) {
                throw new Exception("No se puede agendar en una fecha que ya pasó.");
            }
            if (!barberoLibre($barbero, $fecha, $hora, $posicion)) {
                throw new Exception("Horario ocupado: $barbero ya tiene una cita el $fecha a las $hora.");
            }

            $cita = array("Cliente" => $cliente, "Servicio" => $servicio, "Barbero" => $barbero,
                          "Fecha" => $fecha, "Hora" => $hora, "Estado" => "Pendiente");

            if ($posicion == -1) {
                array_push($_SESSION["citas"], $cita);
                mensaje("exito", "Cita registrada.");
            } else {
                $_SESSION["citas"][$posicion] = $cita;
                mensaje("exito", "Cita modificada.");
            }
            $volver = "citas.php?dia=" . $fecha;
            break;

        case "cancelar_cita":
            $volver   = "citas.php";
            $posicion = $_POST["posicion"];

            if (!isset($_SESSION["citas"][$posicion])) {
                throw new Exception("Seleccione una cita.");
            }
            // no se borra, queda para el historial
            $_SESSION["citas"][$posicion]["Estado"] = "Cancelada";
            mensaje("exito", "Cita cancelada.");
            break;

        // cobrar: ingreso a caja + descontar producto del inventario
        case "cobrar_cita":
            $volver   = "citas.php";
            $posicion = $_POST["posicion"];
            $producto = $_POST["producto"];

            if (!isset($_SESSION["citas"][$posicion])) {
                throw new Exception("Seleccione una cita.");
            }

            // revisar existencias antes de cobrar
            if ($producto != "") {
                $p = buscarProducto($producto);
                if ($p == -1 || $_SESSION["productos"][$p]["Cantidad"] < 1) {
                    throw new Exception("No hay existencias de ese producto.");
                }
            }

            $cita   = $_SESSION["citas"][$posicion];
            $precio = $servicios[$cita["Servicio"]];

            // ingreso del servicio
            $movimiento = array("Fecha" => fechaHoy(), "Tipo" => "Ingreso",
                                "Descripcion" => "Cita: " . $cita["Servicio"] . " - " . $cita["Cliente"],
                                "Monto" => $precio);
            array_push($_SESSION["movimientos"], $movimiento);
            $texto = "Cita cobrada: " . colones($precio) . ".";

            // venta del producto (opcional)
            if ($producto != "") {
                $p = buscarProducto($producto);
                $_SESSION["productos"][$p]["Cantidad"] = $_SESSION["productos"][$p]["Cantidad"] - 1;

                $venta = array("Fecha" => fechaHoy(), "Tipo" => "Ingreso",
                               "Descripcion" => "Venta: " . $producto,
                               "Monto" => $_SESSION["productos"][$p]["Venta"]);
                array_push($_SESSION["movimientos"], $venta);
                $texto = $texto . " Se vendió 1 " . $producto . ".";

                if ($_SESSION["productos"][$p]["Cantidad"] <= $_SESSION["productos"][$p]["Minimo"]) {
                    $texto = $texto . " Atención: " . $producto . " llegó al stock mínimo.";
                }
            }

            $_SESSION["citas"][$posicion]["Estado"] = "Finalizada";
            mensaje("exito", $texto);
            break;

        // CLIENTES

        case "agregar_cliente":
            $volver       = "clientes.php";
            $nombre       = trim($_POST["nombre"]);
            $telefono     = trim($_POST["telefono"]);
            $preferencias = trim($_POST["preferencias"]);

            if (strlen($nombre) < 3) {
                throw new Exception("El nombre debe tener al menos 3 letras.");
            }
            // se quita el guion y se revisa que queden 8 numeros
            $soloNumeros = str_replace("-", "", $telefono);
            if (strlen($soloNumeros) != 8 || !is_numeric($soloNumeros)) {
                throw new Exception("El teléfono debe tener 8 números. Ejemplo: 8888-8888.");
            }
            for ($i = 0; $i < count($_SESSION["clientes"]); $i++) {
                if (str_replace("-", "", $_SESSION["clientes"][$i]["Telefono"]) == $soloNumeros) {
                    throw new Exception("Ya existe un cliente con ese teléfono.");
                }
            }

            $nuevo = array("Nombre" => $nombre, "Telefono" => $telefono,
                           "Registro" => fechaHoy(), "Preferencias" => $preferencias);
            array_push($_SESSION["clientes"], $nuevo);
            mensaje("exito", "Cliente registrado.");
            break;

        // INVENTARIO

        case "agregar_producto":
            $volver    = "inventario.php";
            $nombre    = trim($_POST["nombre"]);
            $categoria = $_POST["categoria"];
            $compra    = $_POST["compra"];
            $venta     = $_POST["venta"];
            $cantidad  = $_POST["cantidad"];
            $minimo    = $_POST["minimo"];

            // uso interno no se vende
            if ($categoria == "Uso interno") {
                $venta = 0;
            }

            if ($nombre == "" || $categoria == "" || $compra == "" || $venta == "" || $cantidad == "" || $minimo == "") {
                throw new Exception("Complete todos los campos del producto.");
            }
            if ($categoria == "Venta" && $venta <= $compra) {
                throw new Exception("El precio de venta debe ser mayor al de compra.");
            }
            if (buscarProducto($nombre) != -1) {
                throw new Exception("Ese producto ya existe.");
            }

            $nuevo = array("Nombre" => $nombre, "Categoria" => $categoria, "Compra" => $compra,
                           "Venta" => $venta, "Cantidad" => $cantidad, "Minimo" => $minimo);
            array_push($_SESSION["productos"], $nuevo);
            mensaje("exito", "Producto registrado.");
            break;

        case "mover_existencias":
            $volver   = "inventario.php";
            $producto = $_POST["producto"];
            $tipo     = $_POST["tipo"];
            $cantidad = $_POST["cantidad"];
            $p = buscarProducto($producto);

            if ($p == -1) {
                throw new Exception("Seleccione un producto.");
            }
            if ($cantidad < 1) {
                throw new Exception("La cantidad debe ser al menos 1.");
            }

            if ($tipo == "Entrada") {
                // la compra se registra como gasto
                $_SESSION["productos"][$p]["Cantidad"] = $_SESSION["productos"][$p]["Cantidad"] + $cantidad;
                $costo = $_SESSION["productos"][$p]["Compra"] * $cantidad;
                $gasto = array("Fecha" => fechaHoy(), "Tipo" => "Gasto",
                               "Descripcion" => "Compra: $producto x$cantidad", "Monto" => $costo);
                array_push($_SESSION["movimientos"], $gasto);
                mensaje("exito", "Entrada registrada. Gasto: " . colones($costo) . ".");
            } else {
                if ($cantidad > $_SESSION["productos"][$p]["Cantidad"]) {
                    throw new Exception("No hay suficientes unidades.");
                }
                $_SESSION["productos"][$p]["Cantidad"] = $_SESSION["productos"][$p]["Cantidad"] - $cantidad;
                mensaje("exito", "Salida registrada.");
            }
            break;

        // FINANZAS

        case "agregar_movimiento":
            $volver      = "finanzas.php";
            $tipo        = $_POST["tipo"];
            $descripcion = trim($_POST["descripcion"]);
            $monto       = $_POST["monto"];
            $fecha       = $_POST["fecha"];

            if ($descripcion == "" || $monto == "" || $fecha == "") {
                throw new Exception("Complete todos los campos.");
            }
            if ($monto <= 0) {
                throw new Exception("El monto debe ser mayor a cero.");
            }
            if ($fecha > fechaHoy()) {
                throw new Exception("No se permiten fechas futuras.");
            }

            $nuevo = array("Fecha" => $fecha, "Tipo" => $tipo, "Descripcion" => $descripcion, "Monto" => $monto);
            array_push($_SESSION["movimientos"], $nuevo);
            mensaje("exito", "$tipo registrado.");
            break;

        case "reiniciar":
            session_destroy();
            header("Location: index.php");
            exit;
    }

} catch (Exception $e) {
    mensaje("error", $e->getMessage());
}

header("Location: " . $volver);
exit;
