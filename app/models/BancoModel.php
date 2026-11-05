<?php
declare(strict_types=1);

namespace App\Models;

// MODELO: BancoModel
// OBJETIVO: Catálogo de bancos (nombre único y borrado lógico)
// NOTA: Toda la lógica vive en ModeloCatalogo; aquí solo se definen tabla, clave y etiqueta
class BancoModel extends ModeloCatalogo
{
    protected string $tabla = 'banco';
    protected string $pk = 'id_banco';
    protected string $etiqueta = 'banco';
}
