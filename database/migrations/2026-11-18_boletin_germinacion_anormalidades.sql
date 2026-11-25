-- Ejecutar una sola vez, después de las migraciones de Germinación y Anormalidades.
CREATE TABLE boletin_germinacion_anormalidades (
    id_boletin_germinacion INT NOT NULL,
    id_anormalidad INT NOT NULL,
    PRIMARY KEY (id_boletin_germinacion, id_anormalidad),
    CONSTRAINT fk_germinacion_anormalidad_boletin FOREIGN KEY (id_boletin_germinacion)
        REFERENCES boletines_germinacion(id_boletin_germinacion) ON DELETE CASCADE,
    CONSTRAINT fk_germinacion_anormalidad_catalogo FOREIGN KEY (id_anormalidad)
        REFERENCES anormalidades(id_anormalidad) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
