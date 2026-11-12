ALTER TABLE especies
    ADD COLUMN primer_conteo SMALLINT UNSIGNED NULL AFTER cientifico_especie,
    ADD COLUMN conteo_final SMALLINT UNSIGNED NULL AFTER primer_conteo;
