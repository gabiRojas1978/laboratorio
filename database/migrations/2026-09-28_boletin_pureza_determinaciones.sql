-- Determinaciones elegidas desde el catálogo y una determinación de texto libre por boletín.
ALTER TABLE boletines_pureza
    ADD COLUMN otras_determinaciones_texto VARCHAR(255) NULL AFTER observaciones;

CREATE TABLE IF NOT EXISTS boletin_pureza_determinaciones (
    id_boletin_pureza INT NOT NULL,
    id_determinacion INT NOT NULL,
    PRIMARY KEY (id_boletin_pureza, id_determinacion),
    KEY idx_boletin_pureza_determinaciones (id_determinacion),
    CONSTRAINT fk_pureza_determinaciones_boletin FOREIGN KEY (id_boletin_pureza)
        REFERENCES boletines_pureza(id_boletin_pureza) ON DELETE CASCADE,
    CONSTRAINT fk_pureza_determinaciones_catalogo FOREIGN KEY (id_determinacion)
        REFERENCES otras_determinaciones(id_determinacion) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
