-- Ejecutar una sola vez. Requiere la migracion del Libro de Ingreso.
ALTER TABLE determinaciones_libro_ingreso
    ADD COLUMN costo DECIMAL(12,2) NOT NULL DEFAULT 0.00;

START TRANSACTION;

-- Guardar las asociaciones antes de reemplazar el catalogo de las empresas 1 y 4.
CREATE TEMPORARY TABLE determinaciones_vinculos_reemplazo AS
SELECT l.id_libro_ingreso, d.id_empresa,
    CASE d.nombre_determinacion_libro
        WHEN 'Energia Germinativo con tratamiento' THEN 'Energia Germinativo- 1ª Conteo Con tratamiento'
        ELSE d.nombre_determinacion_libro
    END AS nombre_determinacion_libro
FROM libro_ingreso_determinaciones l
INNER JOIN determinaciones_libro_ingreso d
    ON d.id_determinacion_libro = l.id_determinacion_libro
WHERE d.id_empresa IN (1, 4);

DELETE FROM determinaciones_libro_ingreso WHERE id_empresa IN (1, 4);

INSERT INTO determinaciones_libro_ingreso (id_empresa, nombre_determinacion_libro, costo)
SELECT e.id_empresa, datos.nombre, 0.00
FROM empresas e
CROSS JOIN (
    SELECT 'Energia Germinativa - 1° Conteo' AS nombre
    UNION ALL SELECT 'Poder Germinativo'
    UNION ALL SELECT 'Energia Germinativo- 1ª Conteo Con tratamiento'
    UNION ALL SELECT 'Poder germinativo Con tratamiento'
    UNION ALL SELECT 'Pureza (soja-trigo-maiz-sorgo-girasol)'
    UNION ALL SELECT 'Pureza (otras semillas y forrajeras)'
    UNION ALL SELECT 'Otras semillas en numero'
    UNION ALL SELECT 'Viabilidad por tetrazolio-Soja'
    UNION ALL SELECT 'Vigor por tetrazolio'
    UNION ALL SELECT 'Vigor por tetrazolio (otras semillas y forrajeras)'
    UNION ALL SELECT 'Peso de 1000 semillas'
    UNION ALL SELECT 'Test de Lavandina- Evaluacion de daño Mecanico'
    UNION ALL SELECT 'Cold Test-Vigor'
    UNION ALL SELECT 'Vigor por Envejecimiento Acelerado'
    UNION ALL SELECT 'Pureza Genetica (hilo-peroxidasa-hipocotilo)'
    UNION ALL SELECT 'Peroxidasa en semilla de soja'
) datos
WHERE e.id_empresa IN (1, 4);

-- LEFT JOIN fuerza un error por ID nulo si falta alguna equivalencia, en vez de perderla.
INSERT INTO libro_ingreso_determinaciones (id_libro_ingreso, id_determinacion_libro)
SELECT v.id_libro_ingreso, d.id_determinacion_libro
FROM determinaciones_vinculos_reemplazo v
LEFT JOIN determinaciones_libro_ingreso d
    ON d.id_empresa = v.id_empresa
    AND d.nombre_determinacion_libro = v.nombre_determinacion_libro;

DROP TEMPORARY TABLE determinaciones_vinculos_reemplazo;

COMMIT;

-- Se descarta el catalogo independiente creado por error.
DROP TABLE IF EXISTS ensayos;
