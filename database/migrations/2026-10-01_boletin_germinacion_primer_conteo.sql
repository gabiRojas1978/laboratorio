-- El 1er conteo también registra las 5 categorías (igual que el 2do conteo), no solo normales
ALTER TABLE boletin_germinacion_repeticiones
    ADD COLUMN primer_conteo_anormales INT UNSIGNED NULL AFTER primer_conteo_normales,
    ADD COLUMN primer_conteo_duras INT UNSIGNED NULL AFTER primer_conteo_anormales,
    ADD COLUMN primer_conteo_frescas INT UNSIGNED NULL AFTER primer_conteo_duras,
    ADD COLUMN primer_conteo_muertas INT UNSIGNED NULL AFTER primer_conteo_frescas;
