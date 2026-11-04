<?php
// acciones de clientes e inventario (Persona 3)
//
// procesar.php (Persona 2) solo tiene que hacer esto despues de leer $accion:
//
//     require "acciones_clientes_inventario.php";
//     procesarClientesInventario($accion);
//
// si la accion es de clientes o inventario, se procesa aqui, se guarda un
// mensaje y se redirige a la pagina. si es otra accion, la funcion no hace
// nada y procesar.php sigue normal.

function procesarClientesInventario($accion)
{
    $misAcciones = array("agregar_cliente", "editar_cliente", "eliminar_cliente",
                         "agregar_producto", "editar_producto", "eliminar_producto",
                         "movimiento_stock");

    if (!in_array($accion, $misAcciones)) {
        return;
    }

    cargarDatosDePrueba();

    switch ($accion) {

        // ---------- clientes: agregar y editar ----------
        case "agregar_cliente":
        case "editar_cliente":
            $nombre       = trim($_POST["nombre"] ?? "");
            $telefono     = trim($_POST["telefono"] ?? "");
            $preferencias = trim($_POST["preferencias"] ?? "");
            $editando     = ($accion == "editar_cliente");
            $posicion     = $editando ? (int)($_POST["posicion"] ?? -1) : -1;
            $error        = "";

            // mismas reglas que js/validaciones.js
            if (mb_strlen($nombre) < 3 || mb_strlen($nombre) > 60) {
                $error = "El nombre debe tener entre 3 y 60 caracteres.";
            } elseif (!preg_match('/^[0-9]{4}-?[0-9]{4}$/', $telefono)) {
                $error = "El teléfono debe tener 8 dígitos (ej. 8888-1234).";
            } elseif (mb_strlen($preferencias) > 120) {
                $error = "Las preferencias no pueden tener más de 120 caracteres.";
            } elseif ($editando && ($posicion < 0 || $posicion >= count($_SESSION["clientes"]))) {
                $error = "El cliente que intenta editar no existe.";
            } else {
                // no se permiten dos clientes con el mismo nombre
                for ($i = 0; $i < count($_SESSION["clientes"]); $i++) {
                    if ($i != $posicion && mb_strtolower($_SESSION["clientes"][$i]["Nombre"]) == mb_strtolower($nombre)) {
                        $error = "Ya existe un cliente con ese nombre.";
                    }
                }
            }

            if ($error != "") {
                mensaje("error", $error);
                $destino = "clientes.php";
                if ($editando) {
                    $destino .= "?editar=" . $posicion;
                }
                header("Location: " . $destino);
                exit;
            }

            // el telefono se guarda siempre como 8888-1234
            $telefono = str_replace("-", "", $telefono);
            $telefono = substr($telefono, 0, 4) . "-" . substr($telefono, 4, 4);

            if ($editando) {
                $nombreAnterior = $_SESSION["clientes"][$posicion]["Nombre"];

                $_SESSION["clientes"][$posicion]["Nombre"]       = $nombre;
                $_SESSION["clientes"][$posicion]["Telefono"]     = $telefono;
                $_SESSION["clientes"][$posicion]["Preferencias"] = $preferencias;

                // las citas guardan el nombre del cliente, se actualizan tambien
                for ($i = 0; $i < count($_SESSION["citas"]); $i++) {
                    if ($_SESSION["citas"][$i]["Cliente"] == $nombreAnterior) {
                        $_SESSION["citas"][$i]["Cliente"] = $nombre;
                    }
                }

                mensaje("exito", "Cliente actualizado correctamente.");
            } else {
                array_push($_SESSION["clientes"], array(
                    "Nombre"       => $nombre,
                    "Telefono"     => $telefono,
                    "Registro"     => fechaHoy(),
                    "Preferencias" => $preferencias
                ));
                mensaje("exito", "Cliente agregado correctamente.");
            }

            header("Location: clientes.php");
            exit;


        // ---------- clientes: eliminar ----------
        case "eliminar_cliente":
            $posicion = (int)($_POST["posicion"] ?? -1);

            if ($posicion < 0 || $posicion >= count($_SESSION["clientes"])) {
                mensaje("error", "El cliente que intenta eliminar no existe.");
            } else {
                $nombre = $_SESSION["clientes"][$posicion]["Nombre"];

                // no se elimina un cliente que todavia tiene citas pendientes
                $pendientes = 0;
                for ($i = 0; $i < count($_SESSION["citas"]); $i++) {
                    if ($_SESSION["citas"][$i]["Cliente"] == $nombre && $_SESSION["citas"][$i]["Estado"] == "Pendiente") {
                        $pendientes++;
                    }
                }

                if ($pendientes > 0) {
                    mensaje("error", "No se puede eliminar a " . $nombre . " porque tiene " . $pendientes . " cita(s) pendiente(s).");
                } else {
                    array_splice($_SESSION["clientes"], $posicion, 1);
                    mensaje("exito", "Cliente eliminado correctamente.");
                }
            }

            header("Location: clientes.php");
            exit;


        // ---------- inventario: agregar y editar producto ----------
        case "agregar_producto":
        case "editar_producto":
            $nombre    = trim($_POST["nombre"] ?? "");
            $categoria = $_POST["categoria"] ?? "";
            $compra    = trim($_POST["compra"] ?? "");
            $venta     = trim($_POST["venta"] ?? "");
            $cantidad  = trim($_POST["cantidad"] ?? "");
            $minimo    = trim($_POST["minimo"] ?? "");
            $editando  = ($accion == "editar_producto");
            $posicion  = $editando ? (int)($_POST["posicion"] ?? -1) : -1;
            $error     = "";

            // los productos de uso interno no se venden
            if ($categoria == "Uso interno") {
                $venta = "0";
            }

            // mismas reglas que js/validaciones.js
            if (mb_strlen($nombre) < 3 || mb_strlen($nombre) > 60) {
                $error = "El nombre debe tener entre 3 y 60 caracteres.";
            } elseif ($categoria != "Venta" && $categoria != "Uso interno") {
                $error = "La categoría no es válida.";
            } elseif (!ctype_digit($compra) || (int)$compra < 1) {
                $error = "El precio de compra debe ser un número entero mayor a 0.";
            } elseif (!ctype_digit($venta)) {
                $error = "El precio de venta debe ser un número entero.";
            } elseif ($categoria == "Venta" && (int)$venta < 1) {
                $error = "Un producto de venta necesita un precio de venta mayor a 0.";
            } elseif (!ctype_digit($cantidad)) {
                $error = "La cantidad debe ser un número entero (0 o más).";
            } elseif (!ctype_digit($minimo)) {
                $error = "El stock mínimo debe ser un número entero (0 o más).";
            } elseif ((int)$compra > 1000000 || (int)$venta > 1000000 || (int)$cantidad > 100000 || (int)$minimo > 100000) {
                $error = "Alguno de los valores es demasiado grande.";
            } elseif ($editando && ($posicion < 0 || $posicion >= count($_SESSION["productos"]))) {
                $error = "El producto que intenta editar no existe.";
            } else {
                // no se permiten dos productos con el mismo nombre
                for ($i = 0; $i < count($_SESSION["productos"]); $i++) {
                    if ($i != $posicion && mb_strtolower($_SESSION["productos"][$i]["Nombre"]) == mb_strtolower($nombre)) {
                        $error = "Ya existe un producto con ese nombre.";
                    }
                }
            }

            if ($error != "") {
                mensaje("error", $error);
                $destino = "inventario.php";
                if ($editando) {
                    $destino .= "?editar=" . $posicion;
                }
                header("Location: " . $destino);
                exit;
            }

            $producto = array(
                "Nombre"    => $nombre,
                "Categoria" => $categoria,
                "Compra"    => (int)$compra,
                "Venta"     => (int)$venta,
                "Cantidad"  => (int)$cantidad,
                "Minimo"    => (int)$minimo
            );

            if ($editando) {
                $_SESSION["productos"][$posicion] = $producto;
                mensaje("exito", "Producto actualizado correctamente.");
            } else {
                array_push($_SESSION["productos"], $producto);
                mensaje("exito", "Producto agregado correctamente.");
            }

            header("Location: inventario.php");
            exit;


        // ---------- inventario: eliminar producto ----------
        case "eliminar_producto":
            $posicion = (int)($_POST["posicion"] ?? -1);

            if ($posicion < 0 || $posicion >= count($_SESSION["productos"])) {
                mensaje("error", "El producto que intenta eliminar no existe.");
            } else {
                array_splice($_SESSION["productos"], $posicion, 1);
                mensaje("exito", "Producto eliminado correctamente.");
            }

            header("Location: inventario.php");
            exit;


        // ---------- inventario: entradas, ventas y consumo de stock ----------
        case "movimiento_stock":
            $nombre   = $_POST["producto"] ?? "";
            $tipo     = $_POST["tipo"] ?? "";
            $cantidad = trim($_POST["cantidad"] ?? "");
            $error    = "";

            // buscarProducto() esta en funciones.php: devuelve la posicion o -1
            $posicion = buscarProducto($nombre);

            if ($posicion == -1) {
                $error = "El producto no existe.";
            } elseif ($tipo != "Entrada" && $tipo != "Venta" && $tipo != "Consumo") {
                $error = "El tipo de movimiento no es válido.";
            } elseif (!ctype_digit($cantidad) || (int)$cantidad < 1 || (int)$cantidad > 100000) {
                $error = "La cantidad debe ser un número entero mayor a 0.";
            } else {
                $producto = $_SESSION["productos"][$posicion];
                $cantidad = (int)$cantidad;

                if ($tipo == "Venta" && $producto["Categoria"] != "Venta") {
                    $error = $nombre . " es de uso interno, no se puede vender.";
                } elseif ($tipo == "Consumo" && $producto["Categoria"] != "Uso interno") {
                    $error = $nombre . " es un producto de venta, registre una venta en lugar de consumo.";
                } elseif ($tipo != "Entrada" && $cantidad > $producto["Cantidad"]) {
                    $error = "Stock insuficiente: solo quedan " . $producto["Cantidad"] . " unidades de " . $nombre . ".";
                }
            }

            if ($error != "") {
                mensaje("error", $error);
                header("Location: inventario.php");
                exit;
            }

            if ($tipo == "Entrada") {
                // compra de mercaderia: sube el stock y se registra un gasto
                $_SESSION["productos"][$posicion]["Cantidad"] += $cantidad;
                array_push($_SESSION["movimientos"], array(
                    "Fecha"       => fechaHoy(),
                    "Tipo"        => "Gasto",
                    "Descripcion" => "Compra de " . $nombre . " (" . $cantidad . " unidades)",
                    "Monto"       => $producto["Compra"] * $cantidad
                ));
            } elseif ($tipo == "Venta") {
                // venta: baja el stock y se registra un ingreso
                $_SESSION["productos"][$posicion]["Cantidad"] -= $cantidad;
                array_push($_SESSION["movimientos"], array(
                    "Fecha"       => fechaHoy(),
                    "Tipo"        => "Ingreso",
                    "Descripcion" => "Venta de " . $nombre . " (" . $cantidad . " unidades)",
                    "Monto"       => $producto["Venta"] * $cantidad
                ));
            } else {
                // consumo interno: solo baja el stock, no mueve dinero
                $_SESSION["productos"][$posicion]["Cantidad"] -= $cantidad;
            }

            mensaje("exito", "Movimiento registrado. " . $nombre . " ahora tiene " . $_SESSION["productos"][$posicion]["Cantidad"] . " unidades.");
            header("Location: inventario.php");
            exit;
    }
}
