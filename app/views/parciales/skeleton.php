<?php
// Esqueleto de carga: se muestra mientras la vista termina de cargar sus datos
?>
<div class="skeleton-page" aria-hidden="true">
    <div class="sk sk-titulo"></div>
    <div class="sk-tarjeta">
        <div class="sk-barra"><div class="sk sk-input"></div><div class="sk sk-boton"></div></div>
        <?php for ($i = 0; $i < 7; $i++): ?>
        <div class="sk-fila"><div class="sk sk-c1"></div><div class="sk sk-c2"></div><div class="sk sk-c3"></div><div class="sk sk-c4"></div></div>
        <?php endfor; ?>
    </div>
</div>
