-- Datos de la tabla proporcionada; carga autorizada para las empresas 1 y 4.
CREATE TEMPORARY TABLE especies_conteos_importacion (
    nombre_especie VARCHAR(150) NOT NULL,
    cientifico_especie VARCHAR(255) NULL,
    primer_conteo SMALLINT UNSIGNED NOT NULL,
    conteo_final SMALLINT UNSIGNED NOT NULL
) DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO especies_conteos_importacion
    (nombre_especie, cientifico_especie, primer_conteo, conteo_final)
VALUES
    ('Alfalfa', 'Medicago sativa', 4, 10),
    ('Algodón', 'Gossypium', 4, 12),
    ('Amaranto', 'Amaranthus', 4, 14),
    ('Avena', 'Avena sativa', 5, 10),
    ('Calabaza', 'Cucurbita', 4, 8),
    ('Cártamo', 'Carthamus tinctorius', 4, 14),
    ('Cebada cervecera', 'Hordeum distichum', 4, 7),
    ('Cebada forrajera', 'Hordeum vulgare', 4, 7),
    ('Centeno', 'Secale cereale', 4, 7),
    ('Chía', 'Salvia Hispánica', 4, 21),
    ('Colza', 'Brassica napus L.', 5, 7),
    ('Garbanzo', 'Cicer arietinum', 5, 8),
    ('Girasol', 'Helianthus annuus', 4, 10),
    ('Girasol confitero', NULL, 4, 10),
    ('Maíz', 'Zea mays', 4, 7),
    ('Maní', 'Arachys hypogaea', 5, 10),
    ('Poroto blanco alubia', NULL, 5, 9),
    ('Poroto mung', NULL, 5, 9),
    ('Poroto negro', 'Phaseolus spp.', 5, 9),
    ('Soja', 'Glycine max.', 5, 8),
    ('Sorgo blanco', 'Sorghum bicolor L.', 4, 10),
    ('Sorgo granífero', 'Sorghum vulgare L.', 4, 10),
    ('Trigo candeal', 'Triticum durum', 4, 8),
    ('Trigo pan', 'Triticum aestivum', 4, 8);

START TRANSACTION;

UPDATE especies e
INNER JOIN especies_conteos_importacion i ON i.nombre_especie = e.nombre_especie
SET e.primer_conteo = i.primer_conteo, e.conteo_final = i.conteo_final
WHERE e.id_empresa IN (1, 4);

INSERT INTO especies (id_empresa, nombre_especie, cientifico_especie, primer_conteo, conteo_final)
SELECT em.id_empresa, i.nombre_especie, i.cientifico_especie, i.primer_conteo, i.conteo_final
FROM empresas em
CROSS JOIN especies_conteos_importacion i
WHERE em.id_empresa IN (1, 4)
  AND NOT EXISTS (
      SELECT 1 FROM especies e
      WHERE e.id_empresa = em.id_empresa AND e.nombre_especie = i.nombre_especie
  );

COMMIT;

DROP TEMPORARY TABLE especies_conteos_importacion;
