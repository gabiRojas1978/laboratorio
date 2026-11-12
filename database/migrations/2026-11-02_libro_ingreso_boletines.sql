CREATE TABLE IF NOT EXISTS libro_ingreso_boletines (
    id_libro_ingreso INT NOT NULL,
    tipo_boletin VARCHAR(60) NOT NULL,
    id_boletin INT NOT NULL,
    PRIMARY KEY (id_libro_ingreso, tipo_boletin),
    UNIQUE KEY uniq_libro_ingreso_boletin (tipo_boletin, id_boletin),
    CONSTRAINT fk_libro_ingreso_boletines_ingreso FOREIGN KEY (id_libro_ingreso)
        REFERENCES libro_ingreso(id_libro_ingreso) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
