<?php
declare(strict_types=1);

namespace App\Models;

// MODELO: TipoPagoModel
// OBJETIVO: Catálogo de tipos de pago (nombre único y borrado lógico)
// NOTA: Toda la lógica vive en ModeloCatalogo; aquí solo se definen tabla, clave y etiqueta
class TipoPagoModel extends ModeloCatalogo
{
    protected string $tabla = 'tipo_pago';
    protected string $pk = 'id_tipo_pago';
    protected string $etiqueta = 'tipo de pago';
}
