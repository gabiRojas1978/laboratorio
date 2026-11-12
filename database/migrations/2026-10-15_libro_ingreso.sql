-- Libro de Ingreso: catálogo de determinaciones propias y registro de muestras recibidas.
CREATE TABLE IF NOT EXISTS determinaciones_libro_ingreso (
    id_determinacion_libro INT AUTO_INCREMENT PRIMARY KEY,
    id_empresa INT NOT NULL,
    nombre_determinacion_libro VARCHAR(255) NOT NULL,
    created_at_determinacion_libro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_determinacion_libro (id_empresa, nombre_determinacion_libro),
    CONSTRAINT fk_determinacion_libro_empresa FOREIGN KEY (id_empresa)
        REFERENCES empresas(id_empresa) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO determinaciones_libro_ingreso (id_empresa, nombre_determinacion_libro)
SELECT e.id_empresa, d.nombre_determinacion_libro
FROM empresas e
CROSS JOIN (
    SELECT 'Energia Germinativa - 1° Conteo' AS nombre_determinacion_libro
    UNION ALL SELECT 'Poder Germinativo'
    UNION ALL SELECT 'Peso de 1000 semillas'
    UNION ALL SELECT 'Poder germinativo con tratamiento'
    UNION ALL SELECT 'Energia Germinativo con tratamiento'
) d;

CREATE TABLE IF NOT EXISTS libro_ingreso (
    id_libro_ingreso INT AUTO_INCREMENT PRIMARY KEY,
    id_empresa INT NOT NULL,
    id_usuario INT NULL,
    anio_libro_ingreso SMALLINT UNSIGNED NOT NULL,
    nro_inase INT UNSIGNED NOT NULL,
    nro_muestra VARCHAR(20) NOT NULL,
    presupuesto ENUM('SI','NO') NOT NULL DEFAULT 'NO',
    facturado ENUM('SI','NO') NOT NULL DEFAULT 'NO',
    fecha_entrada DATE NOT NULL,
    remitente VARCHAR(255) NULL,
    id_cliente INT NOT NULL,
    origen VARCHAR(255) NULL,
    id_especie INT UNSIGNED NULL,
    id_cultivar INT UNSIGNED NULL,
    lote_observaciones VARCHAR(255) NULL,
    ubicacion_camara_archivo VARCHAR(255) NULL,
    created_at_libro_ingreso TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_libro_ingreso_inase (id_empresa, anio_libro_ingreso, nro_inase),
    UNIQUE KEY uniq_libro_ingreso_muestra (id_empresa, nro_muestra),
    KEY idx_libro_ingreso_cliente (id_empresa, id_cliente),
    CONSTRAINT fk_libro_ingreso_empresa FOREIGN KEY (id_empresa)
        REFERENCES empresas(id_empresa) ON DELETE CASCADE,
    CONSTRAINT fk_libro_ingreso_usuario FOREIGN KEY (id_usuario)
        REFERENCES usuarios(id_usuario) ON DELETE SET NULL,
    CONSTRAINT fk_libro_ingreso_cliente FOREIGN KEY (id_cliente)
        REFERENCES clientes(id_cliente) ON DELETE CASCADE,
    CONSTRAINT fk_libro_ingreso_especie FOREIGN KEY (id_especie)
        REFERENCES especies(id_especie) ON DELETE SET NULL,
    CONSTRAINT fk_libro_ingreso_cultivar FOREIGN KEY (id_cultivar)
        REFERENCES cultivares(id_cultivar) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS libro_ingreso_determinaciones (
    id_libro_ingreso INT NOT NULL,
    id_determinacion_libro INT NOT NULL,
    PRIMARY KEY (id_libro_ingreso, id_determinacion_libro),
    KEY idx_libro_ingreso_determinaciones (id_determinacion_libro),
    CONSTRAINT fk_libro_ingreso_determinaciones_libro FOREIGN KEY (id_libro_ingreso)
        REFERENCES libro_ingreso(id_libro_ingreso) ON DELETE CASCADE,
    CONSTRAINT fk_libro_ingreso_determinaciones_catalogo FOREIGN KEY (id_determinacion_libro)
        REFERENCES determinaciones_libro_ingreso(id_determinacion_libro) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
