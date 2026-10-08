ALTER TABLE boletines_malezas
    ADD COLUMN otras_determinaciones_texto VARCHAR(255) NULL AFTER observaciones;

CREATE TABLE IF NOT EXISTS boletin_malezas_determinaciones (
    id_boletin_malezas INT NOT NULL,
    id_determinacion INT NOT NULL,
    PRIMARY KEY (id_boletin_malezas, id_determinacion),
    KEY idx_malezas_determinaciones_determinacion (id_determinacion),
    CONSTRAINT fk_malezas_determinaciones_boletin FOREIGN KEY (id_boletin_malezas)
        REFERENCES boletines_malezas(id_boletin_malezas) ON DELETE CASCADE,
    CONSTRAINT fk_malezas_determinaciones_determinacion FOREIGN KEY (id_determinacion)
        REFERENCES otras_determinaciones(id_determinacion) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;