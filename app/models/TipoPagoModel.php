<?php
declare(strict_types=1);

namespace App\Models;

class TipoPagoModel extends ModeloCatalogo
{
    protected string $tabla = 'tipo_pago';
    protected string $pk = 'id_tipo_pago';
    protected string $etiqueta = 'tipo de pago';
}
