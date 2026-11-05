// validaciones de los formularios antes de enviarlos

function mostrarError(idElemento, texto) {
    document.getElementById(idElemento).innerHTML = texto;
}

// citas (citas.php)
function validarCita() {
    let cliente = document.getElementById("cliente").value;
    let servicio = document.getElementById("servicio").value;
    let barbero = document.getElementById("barbero").value;
    let fecha = document.getElementById("fecha").value;
    let hora = document.getElementById("hora").value;

    if (cliente == "" || servicio == "" || barbero == "" || fecha == "" || hora == "") {
        mostrarError("error_cita", "Complete todos los campos antes de guardar la cita.");
        return false;
    }

    return true;
}

// clientes (clientes.php)
function validarCliente() {
    let nombre = document.getElementById("nombre").value.trim();
    let telefono = document.getElementById("telefono").value.trim();

    if (nombre.length < 3) {
        mostrarError("error_cliente", "El nombre debe tener al menos 3 letras.");
        return false;
    }

    let soloNumeros = telefono.replace("-", "");
    if (soloNumeros.length != 8 || isNaN(soloNumeros)) {
        mostrarError("error_cliente", "El teléfono debe tener 8 dígitos. Ejemplo: 8888-8888.");
        return false;
    }

    return true;
}

// productos (inventario.php)
function validarProducto() {
    let nombre = document.getElementById("nombre").value.trim();
    let categoria = document.getElementById("categoria").value;
    let compra = document.getElementById("compra").value;
    let venta = document.getElementById("venta").value;
    let cantidad = document.getElementById("cantidad").value;
    let minimo = document.getElementById("minimo").value;

    if (nombre == "" || categoria == "" || compra == "" || cantidad == "" || minimo == "") {
        mostrarError("error_producto", "Complete todos los campos del producto.");
        return false;
    }

    if (categoria == "Venta") {
        if (venta == "") {
            mostrarError("error_producto", "Indique el precio de venta.");
            return false;
        }
        if (Number(venta) <= Number(compra)) {
            mostrarError("error_producto", "El precio de venta debe ser mayor al de compra.");
            return false;
        }
    }

    return true;
}

// existencias (inventario.php)
function validarExistencias() {
    let producto = document.getElementById("producto_mov").value;
    let cantidad = Number(document.getElementById("cantidad_mov").value);

    if (producto == "") {
        mostrarError("error_existencias", "Seleccione un producto.");
        return false;
    }
    if (cantidad < 1) {
        mostrarError("error_existencias", "La cantidad debe ser al menos 1.");
        return false;
    }

    return true;
}

// movimientos (finanzas.php)
function validarMovimiento() {
    let descripcion = document.getElementById("descripcion").value.trim();
    let monto = Number(document.getElementById("monto").value);
    let fecha = document.getElementById("fecha").value;

    if (descripcion == "" || fecha == "") {
        mostrarError("error_movimiento", "Complete todos los campos.");
        return false;
    }
    if (monto <= 0) {
        mostrarError("error_movimiento", "El monto debe ser mayor a cero.");
        return false;
    }

    return true;
}
