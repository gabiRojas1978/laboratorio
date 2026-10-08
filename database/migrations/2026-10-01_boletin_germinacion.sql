-- Boletín de Germinación: editable (1er conteo temprano + 2do conteo final por repetición)
CREATE TABLE IF NOT EXISTS boletines_germinacion (
    id_boletin_germinacion INT AUTO_INCREMENT PRIMARY KEY,
    id_empresa INT NOT NULL,
    id_cliente INT NOT NULL,
    id_usuario INT NULL,
    id_especie INT UNSIGNED NULL,
    analisis_nro VARCHAR(100) NULL,
    muestra_nro VARCHAR(100) NOT NULL,
    fecha_siembra DATE NULL,
    fecha_primer_conteo DATE NULL,
    fecha_segundo_conteo DATE NULL,
    analista VARCHAR(150) NULL,
    primer_promedio DECIMAL(6,2) NULL,
    resultado_plantulas_normales DECIMAL(6,2) NULL,
    resultado_plantulas_anormales DECIMAL(6,2) NULL,
    resultado_semillas_duras DECIMAL(6,2) NULL,
    resultado_semillas_frescas DECIMAL(6,2) NULL,
    resultado_semillas_muertas DECIMAL(6,2) NULL,
    estado ENUM('primer_conteo', 'finalizado') NOT NULL DEFAULT 'primer_conteo',
    observaciones TEXT NULL,
    created_at_boletin TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at_boletin TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_boletin_germinacion_empresa_cliente (id_empresa, id_cliente),
    CONSTRAINT fk_boletin_germinacion_empresa FOREIGN KEY (id_empresa)
        REFERENCES empresas(id_empresa) ON DELETE CASCADE,
    CONSTRAINT fk_boletin_germinacion_cliente FOREIGN KEY (id_cliente)
        REFERENCES clientes(id_cliente) ON DELETE CASCADE,
    CONSTRAINT fk_boletin_germinacion_especie FOREIGN KEY (id_especie)
        REFERENCES especies(id_especie) ON DELETE SET NULL,
    CONSTRAINT fk_boletin_germinacion_usuario FOREIGN KEY (id_usuario)
        REFERENCES usuarios(id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS boletin_germinacion_repeticiones (
    id_repeticion_germinacion INT AUTO_INCREMENT PRIMARY KEY,
    id_boletin_germinacion INT NOT NULL,
    nro_repeticion TINYINT UNSIGNED NOT NULL,
    semillas_sembradas INT UNSIGNED NOT NULL,
    primer_conteo_normales INT UNSIGNED NULL,
    segundo_conteo_normales INT UNSIGNED NULL,
    segundo_conteo_anormales INT UNSIGNED NULL,
    segundo_conteo_duras INT UNSIGNED NULL,
    segundo_conteo_frescas INT UNSIGNED NULL,
    segundo_conteo_muertas INT UNSIGNED NULL,
    UNIQUE KEY uniq_germinacion_repeticion (id_boletin_germinacion, nro_repeticion),
    CONSTRAINT fk_boletin_germinacion_repeticiones FOREIGN KEY (id_boletin_germinacion)
        REFERENCES boletines_germinacion(id_boletin_germinacion) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
