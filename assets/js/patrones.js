// Archivo: patrones.js
// Única fuente de las expresiones regulares del lado JavaScript
// Nota: el lado PHP tiene su equivalente en app/helpers/Patrones.php; si se cambia una regla, cambiarla en los dos

const PATRONES = Object.freeze({
    // Validación (con test)
    correo: /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/,
    cedula: /^([VE]-[1-9]\d{6,7}|J-\d{8,9})$/i,
    rif: /^[JVEG]-\d{8,9}$/,
    telefono: /^0(412|414|416|422|424|426|251)\d{7}$/,
    codigoProducto: /^[A-Za-z0-9\-\/\.]+$/,
    soloLetras: /^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]+$/,
    soloLetrasOpcional: /^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]*$/,
    unaLetra: /^[a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]$/,
    nombreEmpresa: /^[a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\.,\-&]+$/,
    referenciaPago: /^\d{5}$/,
    claveProhibida: /['"`;\\<>]|--|\/\*|\*\//,
    extensionPdf: /\.pdf$/i,
    fraccion: /^(\d+)\/(\d+)$/,
    fraccionEnTexto: /\d+\/\d+/,

    // Limpieza de lo que se escribe (con replace; llevan la bandera g)
    noLetras: /[^a-zA-ZáéíóúÁÉÍÓÚñÑüÜ\s]/g,
    noDigitos: /[^0-9]/g,
    noNumeroDecimal: /[^0-9.]/g,
    coma: /,/g,
    nombreBancoInvalido: /[^a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\.\-]/g,
    nombreMezclaInvalido: /[^a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\-\/\#\.]/g,
    observacionAjusteInvalida: /[^a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\.\,\-\:\;]/g,
    observacionCompraInvalida: /[^a-zA-Z0-9áéíóúÁÉÍÓÚñÑüÜ\s\.\,\-\:\;\#\/]/g,

    // Separar palabras
    espacios: /\s+/
});
