// Añade el token CSRF a toda petición no-GET al mismo origen
(function () {
    const meta = document.querySelector('meta[name="csrf-token"]');
    if (!meta) return;
    const fetchOriginal = window.fetch;
    window.fetch = function (recurso, opciones = {}) {
        const metodo = (opciones.method || (recurso instanceof Request ? recurso.method : 'GET')).toUpperCase();
        if (metodo !== 'GET' && metodo !== 'HEAD') {
            const headers = new Headers(opciones.headers || (recurso instanceof Request ? recurso.headers : undefined));
            headers.set('X-CSRF-Token', meta.content);
            opciones = { ...opciones, headers };
        }
        return fetchOriginal.call(this, recurso, opciones);
    };
})();
