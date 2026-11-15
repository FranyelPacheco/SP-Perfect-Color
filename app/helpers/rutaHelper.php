<?php
// ARCHIVO: rutaHelper.php
// OBJETIVO: Construir las URLs de la app a partir de la carpeta donde está instalada, sin escribir su nombre a mano

// FUNCIÓN: url
// OBJETIVO: Devolver la ruta pública de la app (ej: url('cliente') => /mi%20carpeta/cliente); sin argumento devuelve la base con "/" final
// NOTA: La base sale de comparar la carpeta del proyecto con DOCUMENT_ROOT, así que renombrar o mover la carpeta no obliga a tocar nada
function url(string $ruta = ''): string
{
    static $base = null;
    if ($base === null) {
        $raiz = str_replace('\\', '/', (string)realpath(__DIR__ . '/../..'));
        $documentos = str_replace('\\', '/', (string)realpath($_SERVER['DOCUMENT_ROOT'] ?? ''));
        $carpeta = ($documentos !== '' && stripos($raiz, $documentos) === 0)
            ? substr($raiz, strlen($documentos))
            : dirname($_SERVER['SCRIPT_NAME'] ?? '/');
        $carpeta = trim(str_replace('\\', '/', $carpeta), '/');
        $base = $carpeta === '' ? '/' : '/' . implode('/', array_map('rawurlencode', explode('/', $carpeta))) . '/';
    }
    return $base . ltrim($ruta, '/');
}

// FUNCIÓN: urlAbsoluta
// OBJETIVO: Igual que url() pero con protocolo y dominio, para enlaces fuera del navegador (correos, canonical)
function urlAbsoluta(string $ruta = ''): string
{
    $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
    return $protocolo . ($_SERVER['HTTP_HOST'] ?? 'localhost') . url($ruta);
}
