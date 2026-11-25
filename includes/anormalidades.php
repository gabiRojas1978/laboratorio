<?php

declare(strict_types=1);

function anormalidades_inicializar_empresa(PDO $conexion, int $empresaId): void
{
    $statement = $conexion->prepare(
        'INSERT INTO anormalidades (id_empresa, codigo, etiqueta, grupo_tipo, categoria_principal, subcategoria)
         SELECT :id_empresa, codigo, etiqueta, grupo_tipo, categoria_principal, subcategoria FROM anormalidades_modelo'
    );
    $statement->execute([':id_empresa' => $empresaId]);
    if ($statement->rowCount() === 0) {
        throw new RuntimeException('La plantilla de anormalidades está vacía.');
    }
}
