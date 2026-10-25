<?php
declare(strict_types=1);

namespace App\Models;

class BancoModel extends ModeloCatalogo
{
    protected string $tabla = 'banco';
    protected string $pk = 'id_banco';
    protected string $etiqueta = 'banco';
}
