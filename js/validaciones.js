// validaciones del lado del cliente (Persona 3)
//
// este archivo se carga en todas las paginas (pie.php). tiene dos partes:
//   1. validacion generica de los formularios con class="validar"
//      (clientes.php e inventario.php)
//   2. validarCita() y validarMovimiento(), que llaman citas.php y finanzas.php
//
// atributos que puede tener cada campo de un formulario "validar":
//   required            -> el campo es obligatorio
//   data-nombre         -> como se llama el campo en el mensaje de error
//   data-tipo           -> "texto" (por defecto), "telefono" o "entero"
//   data-min / data-max -> largo minimo/maximo (texto) o valor minimo/maximo (entero)


// devuelve el mensaje de error de un campo, o "" si esta bien
function textoError(campo) {
    var valor  = campo.value.trim();
    var nombre = campo.getAttribute("data-nombre") || campo.name;
    var tipo   = campo.getAttribute("data-tipo") || "texto";
    var min    = campo.getAttribute("data-min");
    var max    = campo.getAttribute("data-max");

    // campo vacio: solo es error si es obligatorio
    if (valor === "") {
        if (campo.hasAttribute("required")) {
            return nombre + " es obligatorio.";
        }
        return "";
    }

    if (tipo === "telefono") {
        // 8 digitos, con o sin guion (8888-8888 u 88888888)
        if (!/^[0-9]{4}-?[0-9]{4}$/.test(valor)) {
            return "El teléfono debe tener 8 números. Ejemplo: 8888-8888.";
        }

    } else if (tipo === "entero") {
        if (!/^[0-9]+$/.test(valor)) {
            return nombre + " debe ser un número entero, sin decimales ni signo.";
        }
        var numero = Number(valor);
        if (min !== null && numero < Number(min)) {
            return nombre + " no puede ser menor a " + min + ".";
        }
        if (max !== null && numero > Number(max)) {
            return nombre + " no puede ser mayor a " + max + ".";
        }

    } else {
        if (min !== null && valor.length < Number(min)) {
            return nombre + " debe tener al menos " + min + " caracteres.";
        }
        if (max !== null && valor.length > Number(max)) {
            return nombre + ": máximo " + max + " caracteres.";
        }
    }

    return "";
}


// quita el mensaje de error que esta debajo del campo
function quitarError(campo) {
    var siguiente = campo.nextElementSibling;
    if (siguiente && siguiente.classList.contains("aviso-campo")) {
        siguiente.remove();
    }
}


// muestra un mensaje de error debajo del campo (la clase "error" lo pone en rojo)
function mostrarError(campo, texto) {
    quitarError(campo);
    if (texto === "") {
        return;
    }
    var aviso = document.createElement("span");
    aviso.className = "error aviso-campo";
    aviso.style.display = "block";
    aviso.textContent = texto;
    campo.insertAdjacentElement("afterend", aviso);
}


// revisa todos los campos del formulario; devuelve true si no hay errores
function validarFormulario(formulario) {
    var campos = formulario.querySelectorAll("input, select, textarea");
    var primero = null;

    for (var i = 0; i < campos.length; i++) {
        if (campos[i].type === "hidden") {
            continue;
        }
        var error = textoError(campos[i]);
        mostrarError(campos[i], error);
        if (error !== "" && primero === null) {
            primero = campos[i];
        }
    }

    // regla especial del formulario de productos (la misma de procesar.php):
    // en un producto de venta, el precio de venta debe ser mayor al de compra
    if (formulario.id === "form-producto") {
        var categoria = formulario.querySelector("[name=categoria]");
        var compra = formulario.querySelector("[name=compra]");
        var venta = formulario.querySelector("[name=venta]");
        var yaTieneError = venta.nextElementSibling && venta.nextElementSibling.classList.contains("aviso-campo");

        if (categoria.value === "Venta" && !yaTieneError && Number(venta.value) <= Number(compra.value)) {
            mostrarError(venta, "El precio de venta debe ser mayor al de compra.");
            if (primero === null) {
                primero = venta;
            }
        }
    }

    if (primero !== null) {
        primero.focus();
        return false;
    }
    return true;
}


// ---------- formularios de citas.php y finanzas.php ----------
// esas paginas llaman a estas funciones con onsubmit="return validarCita()" y
// onsubmit="return validarMovimiento()". si devuelven false el formulario no se
// envia, y el mensaje se escribe en el <p class="error"> de cada formulario.

// fecha de hoy con el formato AAAA-MM-DD (el mismo de <input type="date">)
function fechaDeHoy() {
    var hoy = new Date();
    var mes = String(hoy.getMonth() + 1).padStart(2, "0");
    var dia = String(hoy.getDate()).padStart(2, "0");
    return hoy.getFullYear() + "-" + mes + "-" + dia;
}

// citas.php: todos los campos son obligatorios y la fecha no puede ser pasada
function validarCita() {
    var mensaje = document.getElementById("error_cita");
    var ids = ["cliente", "servicio", "barbero", "fecha", "hora"];
    var nombres = ["el cliente", "el servicio", "el barbero", "la fecha", "la hora"];

    for (var i = 0; i < ids.length; i++) {
        if (document.getElementById(ids[i]).value === "") {
            mensaje.textContent = "Complete todos los campos de la cita. Falta " + nombres[i] + ".";
            document.getElementById(ids[i]).focus();
            return false;
        }
    }

    if (document.getElementById("fecha").value < fechaDeHoy()) {
        mensaje.textContent = "No se puede agendar en una fecha que ya pasó.";
        document.getElementById("fecha").focus();
        return false;
    }

    mensaje.textContent = "";
    return true;
}

// finanzas.php: descripcion, monto mayor a cero y fecha que no sea futura
function validarMovimiento() {
    var mensaje = document.getElementById("error_movimiento");
    var descripcion = document.getElementById("descripcion");
    var monto = document.getElementById("monto");
    var fecha = document.getElementById("fecha");

    if (descripcion.value.trim() === "") {
        mensaje.textContent = "Falta la descripción.";
        descripcion.focus();
        return false;
    }

    if (monto.value === "" || Number(monto.value) <= 0) {
        mensaje.textContent = "El monto debe ser mayor a cero.";
        monto.focus();
        return false;
    }

    if (fecha.value === "") {
        mensaje.textContent = "Falta la fecha.";
        fecha.focus();
        return false;
    }

    if (fecha.value > fechaDeHoy()) {
        mensaje.textContent = "No se permiten fechas futuras.";
        fecha.focus();
        return false;
    }

    mensaje.textContent = "";
    return true;
}


// los productos de uso interno no se venden: el precio de venta queda en 0
function ajustarPrecioVenta() {
    var categoria = document.querySelector("#form-producto [name=categoria]");
    var venta = document.querySelector("#form-producto [name=venta]");

    if (!categoria || !venta) {
        return;
    }

    function actualizar() {
        if (categoria.value === "Uso interno") {
            venta.value = "0";
            venta.readOnly = true;
        } else {
            venta.readOnly = false;
        }
    }

    categoria.addEventListener("change", actualizar);
    actualizar();
}


document.addEventListener("DOMContentLoaded", function () {

    var formularios = document.querySelectorAll("form.validar");

    formularios.forEach(function (formulario) {
        // se desactiva la validacion del navegador para mostrar nuestros mensajes
        formulario.setAttribute("novalidate", "novalidate");

        formulario.addEventListener("submit", function (evento) {
            if (!validarFormulario(formulario)) {
                evento.preventDefault();
            }
        });

        // al escribir en un campo se quita su mensaje de error
        formulario.addEventListener("input", function (evento) {
            quitarError(evento.target);
        });
        formulario.addEventListener("change", function (evento) {
            quitarError(evento.target);
        });
    });

    ajustarPrecioVenta();
});
