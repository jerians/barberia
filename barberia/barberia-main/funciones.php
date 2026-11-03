<?php
// datos y funciones que usan todas las paginas

$barberos = array("Carlos Mora", "Andrés Solís", "Diego Vargas");

// arreglo asociativo: servicio => precio
$servicios = array(
    "Corte clásico"    => 6000,
    "Corte + barba"    => 9000,
    "Arreglo de barba" => 4000,
    "Corte infantil"   => 5000
);

$horas = array("08:00", "09:00", "10:00", "11:00", "12:00", "13:00",
               "14:00", "15:00", "16:00", "17:00", "18:00");

function fechaHoy()
{
    return date("Y-m-d");
}

// strtotime("-3 days") = hace 3 dias
function fechaHace($dias)
{
    return date("Y-m-d", strtotime("-" . $dias . " days"));
}

// datos de prueba en la sesion (mientras no hay base de datos)
function cargarDatosDePrueba()
{
    if (isset($_SESSION["citas"])) {
        return;
    }

    $_SESSION["clientes"] = array(
        array("Nombre" => "José Ramírez",   "Telefono" => "8888-1234", "Registro" => fechaHace(40), "Preferencias" => "Degradado bajo."),
        array("Nombre" => "Luis Fernández", "Telefono" => "8777-4567", "Registro" => fechaHace(25), "Preferencias" => "Siempre pide a Carlos."),
        array("Nombre" => "Marco Jiménez",  "Telefono" => "8666-7890", "Registro" => fechaHace(10), "Preferencias" => "Piel sensible."),
        array("Nombre" => "Daniel Castro",  "Telefono" => "8555-2468", "Registro" => fechaHace(3),  "Preferencias" => "")
    );

    $_SESSION["productos"] = array(
        array("Nombre" => "Cera para cabello",   "Categoria" => "Venta",       "Compra" => 2500, "Venta" => 4500, "Cantidad" => 12, "Minimo" => 5),
        array("Nombre" => "Aceite para barba",   "Categoria" => "Venta",       "Compra" => 3000, "Venta" => 5500, "Cantidad" => 4,  "Minimo" => 5),
        array("Nombre" => "Shampoo",             "Categoria" => "Venta",       "Compra" => 2000, "Venta" => 3800, "Cantidad" => 8,  "Minimo" => 3),
        array("Nombre" => "Navajas desechables", "Categoria" => "Uso interno", "Compra" => 150,  "Venta" => 0,    "Cantidad" => 40, "Minimo" => 20),
        array("Nombre" => "After shave",         "Categoria" => "Uso interno", "Compra" => 3500, "Venta" => 0,    "Cantidad" => 2,  "Minimo" => 2)
    );

    $_SESSION["citas"] = array(
        array("Cliente" => "José Ramírez",   "Servicio" => "Corte clásico",    "Barbero" => "Carlos Mora",  "Fecha" => fechaHoy(),   "Hora" => "09:00", "Estado" => "Finalizada"),
        array("Cliente" => "Luis Fernández", "Servicio" => "Corte + barba",    "Barbero" => "Carlos Mora",  "Fecha" => fechaHoy(),   "Hora" => "10:00", "Estado" => "Pendiente"),
        array("Cliente" => "Marco Jiménez",  "Servicio" => "Arreglo de barba", "Barbero" => "Andrés Solís", "Fecha" => fechaHoy(),   "Hora" => "10:00", "Estado" => "Pendiente"),
        array("Cliente" => "Daniel Castro",  "Servicio" => "Corte clásico",    "Barbero" => "Diego Vargas", "Fecha" => fechaHoy(),   "Hora" => "15:00", "Estado" => "Pendiente"),
        array("Cliente" => "José Ramírez",   "Servicio" => "Corte + barba",    "Barbero" => "Andrés Solís", "Fecha" => fechaHace(7), "Hora" => "11:00", "Estado" => "Finalizada"),
        array("Cliente" => "Luis Fernández", "Servicio" => "Corte clásico",    "Barbero" => "Carlos Mora",  "Fecha" => fechaHace(3), "Hora" => "16:00", "Estado" => "Finalizada")
    );

    $_SESSION["movimientos"] = array(
        array("Fecha" => fechaHoy(),    "Tipo" => "Ingreso", "Descripcion" => "Cita: Corte clásico - José Ramírez",   "Monto" => 6000),
        array("Fecha" => fechaHace(1),  "Tipo" => "Gasto",   "Descripcion" => "Recibo de luz",                        "Monto" => 18000),
        array("Fecha" => fechaHace(3),  "Tipo" => "Ingreso", "Descripcion" => "Cita: Corte clásico - Luis Fernández", "Monto" => 6000),
        array("Fecha" => fechaHace(7),  "Tipo" => "Ingreso", "Descripcion" => "Cita: Corte + barba - José Ramírez",   "Monto" => 9000),
        array("Fecha" => fechaHace(12), "Tipo" => "Gasto",   "Descripcion" => "Compra de navajas",                    "Monto" => 6000)
    );
}

// guarda un mensaje para la siguiente pagina ("exito" o "error")
function mensaje($tipo, $texto)
{
    $_SESSION["mensaje"] = array("Tipo" => $tipo, "Texto" => $texto);
}

// muestra el mensaje y lo borra para que no se repita
function mostrarMensaje()
{
    if (isset($_SESSION["mensaje"])) {
        echo "<p class='" . $_SESSION["mensaje"]["Tipo"] . "'>";
        echo htmlspecialchars($_SESSION["mensaje"]["Texto"]);
        echo "</p>";
        unset($_SESSION["mensaje"]);
    }
}

function colones($monto)
{
    return "₡" . $monto;
}

// devuelve la posicion del producto o -1 si no existe
function buscarProducto($nombre)
{
    for ($i = 0; $i < count($_SESSION["productos"]); $i++) {
        if ($_SESSION["productos"][$i]["Nombre"] == $nombre) {
            return $i;
        }
    }
    return -1;
}

// revisa que el barbero no tenga otra cita a esa hora
// $posicionEditada = -1 si la cita es nueva
function barberoLibre($barbero, $fecha, $hora, $posicionEditada)
{
    for ($i = 0; $i < count($_SESSION["citas"]); $i++) {
        $cita = $_SESSION["citas"][$i];
        if ($i != $posicionEditada && $cita["Estado"] != "Cancelada"
            && $cita["Barbero"] == $barbero && $cita["Fecha"] == $fecha && $cita["Hora"] == $hora) {
            return false;
        }
    }
    return true;
}

// llena las opciones de un select
function opciones($lista, $elegida)
{
    for ($i = 0; $i < count($lista); $i++) {
        $texto = htmlspecialchars($lista[$i]);
        if ($lista[$i] == $elegida) {
            echo "<option selected>$texto</option>";
        } else {
            echo "<option>$texto</option>";
        }
    }
}
