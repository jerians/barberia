<?php
// acciones de clientes e inventario (Persona 3)
//
// procesar.php (Persona 2) ya tiene agregar_cliente, agregar_producto y
// mover_existencias. aqui estan las acciones que faltaban:
// editar y eliminar clientes, y editar y eliminar productos.
//
// procesar.php las llama asi:
//     require_once "acciones_clientes_inventario.php";
//     procesarClientesInventario($accion);
//
// si la accion es de este archivo, se procesa, se guarda un mensaje y se
// redirige. si es otra, la funcion no hace nada y procesar.php sigue normal.

function procesarClientesInventario($accion)
{
    $misAcciones = array("editar_cliente", "eliminar_cliente", "editar_producto", "eliminar_producto");

    if (!in_array($accion, $misAcciones)) {
        return;
    }

    cargarDatosDePrueba();

    switch ($accion) {

        // ---------- clientes: editar ----------
        case "editar_cliente":
            $posicion     = (int)($_POST["posicion"] ?? -1);
            $nombre       = trim($_POST["nombre"] ?? "");
            $telefono     = trim($_POST["telefono"] ?? "");
            $preferencias = trim($_POST["preferencias"] ?? "");
            $soloNumeros  = str_replace("-", "", $telefono);
            $error        = "";

            // mismas reglas que agregar_cliente en procesar.php y que js/validaciones.js
            if ($posicion < 0 || $posicion >= count($_SESSION["clientes"])) {
                $error = "El cliente que intenta editar no existe.";
            } elseif (mb_strlen($nombre) < 3 || mb_strlen($nombre) > 60) {
                $error = "El nombre debe tener entre 3 y 60 caracteres.";
            } elseif (strlen($soloNumeros) != 8 || !ctype_digit($soloNumeros)) {
                $error = "El teléfono debe tener 8 números. Ejemplo: 8888-8888.";
            } elseif (mb_strlen($preferencias) > 120) {
                $error = "Las preferencias no pueden tener más de 120 caracteres.";
            } else {
                for ($i = 0; $i < count($_SESSION["clientes"]); $i++) {
                    if ($i == $posicion) {
                        continue;
                    }
                    if (str_replace("-", "", $_SESSION["clientes"][$i]["Telefono"]) == $soloNumeros) {
                        $error = "Ya existe un cliente con ese teléfono.";
                    }
                    // las citas guardan el nombre del cliente, por eso no se repite
                    if (mb_strtolower($_SESSION["clientes"][$i]["Nombre"]) == mb_strtolower($nombre)) {
                        $error = "Ya existe un cliente con ese nombre.";
                    }
                }
            }

            if ($error != "") {
                mensaje("error", $error);
                header("Location: clientes.php?editar=" . $posicion);
                exit;
            }

            $nombreAnterior = $_SESSION["clientes"][$posicion]["Nombre"];

            $_SESSION["clientes"][$posicion]["Nombre"]       = $nombre;
            $_SESSION["clientes"][$posicion]["Telefono"]     = substr($soloNumeros, 0, 4) . "-" . substr($soloNumeros, 4, 4);
            $_SESSION["clientes"][$posicion]["Preferencias"] = $preferencias;

            // las citas guardan el nombre del cliente, se actualizan tambien
            for ($i = 0; $i < count($_SESSION["citas"]); $i++) {
                if ($_SESSION["citas"][$i]["Cliente"] == $nombreAnterior) {
                    $_SESSION["citas"][$i]["Cliente"] = $nombre;
                }
            }

            mensaje("exito", "Cliente actualizado correctamente.");
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


        // ---------- inventario: editar producto ----------
        case "editar_producto":
            $posicion  = (int)($_POST["posicion"] ?? -1);
            $nombre    = trim($_POST["nombre"] ?? "");
            $categoria = $_POST["categoria"] ?? "";
            $compra    = trim($_POST["compra"] ?? "");
            $venta     = trim($_POST["venta"] ?? "");
            $cantidad  = trim($_POST["cantidad"] ?? "");
            $minimo    = trim($_POST["minimo"] ?? "");
            $error     = "";

            // los productos de uso interno no se venden
            if ($categoria == "Uso interno") {
                $venta = "0";
            }

            // mismas reglas que agregar_producto en procesar.php y que js/validaciones.js
            if ($posicion < 0 || $posicion >= count($_SESSION["productos"])) {
                $error = "El producto que intenta editar no existe.";
            } elseif (mb_strlen($nombre) < 3 || mb_strlen($nombre) > 60) {
                $error = "El nombre debe tener entre 3 y 60 caracteres.";
            } elseif ($categoria != "Venta" && $categoria != "Uso interno") {
                $error = "La categoría no es válida.";
            } elseif (!ctype_digit($compra) || (int)$compra < 1) {
                $error = "El precio de compra debe ser un número entero mayor a 0.";
            } elseif (!ctype_digit($venta)) {
                $error = "El precio de venta debe ser un número entero.";
            } elseif ($categoria == "Venta" && (int)$venta <= (int)$compra) {
                $error = "El precio de venta debe ser mayor al de compra.";
            } elseif (!ctype_digit($cantidad)) {
                $error = "La cantidad debe ser un número entero (0 o más).";
            } elseif (!ctype_digit($minimo)) {
                $error = "El stock mínimo debe ser un número entero (0 o más).";
            } elseif ((int)$compra > 1000000 || (int)$venta > 1000000 || (int)$cantidad > 100000 || (int)$minimo > 100000) {
                $error = "Alguno de los valores es demasiado grande.";
            } else {
                // no se permiten dos productos con el mismo nombre
                for ($i = 0; $i < count($_SESSION["productos"]); $i++) {
                    if ($i != $posicion && mb_strtolower($_SESSION["productos"][$i]["Nombre"]) == mb_strtolower($nombre)) {
                        $error = "Ese producto ya existe.";
                    }
                }
            }

            if ($error != "") {
                mensaje("error", $error);
                header("Location: inventario.php?editar=" . $posicion);
                exit;
            }

            $_SESSION["productos"][$posicion] = array(
                "Nombre"    => $nombre,
                "Categoria" => $categoria,
                "Compra"    => (int)$compra,
                "Venta"     => (int)$venta,
                "Cantidad"  => (int)$cantidad,
                "Minimo"    => (int)$minimo
            );

            mensaje("exito", "Producto actualizado correctamente.");
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
    }
}
