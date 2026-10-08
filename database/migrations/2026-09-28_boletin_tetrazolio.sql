-- Boletín de Tetrazolio: datos generales y cuatro repeticiones
CREATE TABLE IF NOT EXISTS boletines_tetrazolio (
    id_boletin_tetrazolio INT AUTO_INCREMENT PRIMARY KEY,
    id_empresa INT NOT NULL,
    id_cliente INT NOT NULL,
    id_usuario INT NULL,
    id_especie INT UNSIGNED NULL,
    muestra_nro VARCHAR(100) NOT NULL,
    fecha_inicio DATE NULL,
    fecha_finalizacion DATE NULL,
    analista VARCHAR(150) NULL,
    tiempo_humedecimiento VARCHAR(100) NULL,
    tiempo_tincion VARCHAR(100) NULL,
    temperatura_tincion VARCHAR(100) NULL,
    concentracion_tz VARCHAR(100) NULL,
    promedio_viables DECIMAL(10,2) NULL,
    promedio_no_viables DECIMAL(10,2) NULL,
    resultado_porcentaje DECIMAL(6,2) NULL,
    observaciones TEXT NULL,
    created_at_boletin TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_boletin_tetrazolio_empresa_cliente (id_empresa, id_cliente),
    CONSTRAINT fk_boletin_tetrazolio_empresa FOREIGN KEY (id_empresa)
        REFERENCES empresas(id_empresa) ON DELETE CASCADE,
    CONSTRAINT fk_boletin_tetrazolio_cliente FOREIGN KEY (id_cliente)
        REFERENCES clientes(id_cliente) ON DELETE CASCADE,
    CONSTRAINT fk_boletin_tetrazolio_especie FOREIGN KEY (id_especie)
        REFERENCES especies(id_especie) ON DELETE SET NULL,
    CONSTRAINT fk_boletin_tetrazolio_usuario FOREIGN KEY (id_usuario)
        REFERENCES usuarios(id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS boletin_tetrazolio_repeticiones (
    id_repeticion_tetrazolio INT AUTO_INCREMENT PRIMARY KEY,
    id_boletin_tetrazolio INT NOT NULL,
    nro_repeticion TINYINT UNSIGNED NOT NULL,
    semillas_viables INT UNSIGNED NOT NULL,
    semillas_no_viables INT UNSIGNED NOT NULL,
    UNIQUE KEY uniq_tetrazolio_repeticion (id_boletin_tetrazolio, nro_repeticion),
    CONSTRAINT fk_boletin_tetrazolio_repeticiones FOREIGN KEY (id_boletin_tetrazolio)
        REFERENCES boletines_tetrazolio(id_boletin_tetrazolio) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
