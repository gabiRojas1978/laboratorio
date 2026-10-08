-- Vincular cada análisis de malezas con la especie seleccionada en el catálogo.
ALTER TABLE boletines_malezas
    ADD COLUMN id_especie INT UNSIGNED NULL AFTER id_cliente,
    ADD KEY idx_boletin_malezas_especie (id_especie),
    ADD CONSTRAINT fk_boletin_malezas_especie FOREIGN KEY (id_especie)
        REFERENCES especies(id_especie) ON DELETE SET NULL;
