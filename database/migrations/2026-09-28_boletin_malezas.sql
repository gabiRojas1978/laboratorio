-- Análisis de malezas: cabecera y especies halladas por número
CREATE TABLE IF NOT EXISTS boletines_malezas (
    id_boletin_malezas INT AUTO_INCREMENT PRIMARY KEY,
    id_empresa INT NOT NULL,
    id_cliente INT NOT NULL,
    id_usuario INT NULL,
    analisis_nro VARCHAR(100) NOT NULL,
    muestra_nro VARCHAR(100) NULL,
    especie_remitente VARCHAR(255) NOT NULL,
    especie_latin VARCHAR(255) NOT NULL,
    lote VARCHAR(100) NULL,
    tipo_analisis ENUM('completo', 'limitado', 'reducido', 'reducido_limitado') NOT NULL,
    peso_analisis DECIMAL(12,4) NULL,
    fecha_analisis DATE NULL,
    analista VARCHAR(150) NULL,
    observaciones TEXT NULL,
    created_at_boletin TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_boletin_malezas_empresa_cliente (id_empresa, id_cliente),
    CONSTRAINT fk_boletin_malezas_empresa FOREIGN KEY (id_empresa)
        REFERENCES empresas(id_empresa) ON DELETE CASCADE,
    CONSTRAINT fk_boletin_malezas_cliente FOREIGN KEY (id_cliente)
        REFERENCES clientes(id_cliente) ON DELETE CASCADE,
    CONSTRAINT fk_boletin_malezas_usuario FOREIGN KEY (id_usuario)
        REFERENCES usuarios(id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS boletin_malezas_especies (
    id_especie_hallada INT AUTO_INCREMENT PRIMARY KEY,
    id_boletin_malezas INT NOT NULL,
    especie_latin VARCHAR(255) NOT NULL,
    numero_semillas INT UNSIGNED NOT NULL,
    KEY idx_malezas_especies_boletin (id_boletin_malezas),
    CONSTRAINT fk_malezas_especies_boletin FOREIGN KEY (id_boletin_malezas)
        REFERENCES boletines_malezas(id_boletin_malezas) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
