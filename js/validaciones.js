// validaciones del lado del cliente (Persona 3)
//
// este archivo se carga en todas las paginas (pie.php), pero solo actua sobre
// los formularios con class="validar" y los que tienen data-confirmar.
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
        // 8 digitos, con o sin guion (8888-1234 u 88881234)
        if (!/^[0-9]{4}-?[0-9]{4}$/.test(valor)) {
            return "El teléfono debe tener 8 dígitos (ej. 8888-1234).";
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

    // regla especial del formulario de productos:
    // un producto de venta necesita un precio de venta mayor a 0
    if (formulario.id === "form-producto") {
        var categoria = formulario.querySelector("[name=categoria]");
        var venta = formulario.querySelector("[name=venta]");
        var yaTieneError = venta.nextElementSibling && venta.nextElementSibling.classList.contains("aviso-campo");

        if (categoria.value === "Venta" && !yaTieneError && Number(venta.value) < 1) {
            mostrarError(venta, "Un producto de venta necesita un precio de venta mayor a 0.");
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

    // formularios con validacion
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

    // botones de eliminar: piden confirmacion antes de enviar
    var conConfirmacion = document.querySelectorAll("form[data-confirmar]");

    conConfirmacion.forEach(function (formulario) {
        formulario.addEventListener("submit", function (evento) {
            if (!confirm(formulario.getAttribute("data-confirmar"))) {
                evento.preventDefault();
            }
        });
    });

    ajustarPrecioVenta();
});
