-- Boletín de Peso de 1000 semillas (cabecera + repeticiones)
CREATE TABLE IF NOT EXISTS boletines_peso_1000_semillas (
    id_boletin_peso INT AUTO_INCREMENT PRIMARY KEY,
    id_empresa INT NOT NULL,
    id_usuario INT NULL,
    id_especie INT UNSIGNED NULL,
    muestra_nro VARCHAR(100) NOT NULL,
    fecha_boletin DATE NULL,
    analista VARCHAR(150) NULL,
    suma_pesos DECIMAL(12,4) NULL,
    promedio_peso DECIMAL(12,6) NULL,
    varianza DECIMAL(14,6) NULL,
    desvio_estandar DECIMAL(12,6) NULL,
    coeficiente_variacion DECIMAL(8,3) NULL,
    resultado_peso_mil DECIMAL(12,4) NULL,
    observaciones VARCHAR(500) NULL,
    created_at_boletin TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_boletin_peso_empresa (id_empresa),
    CONSTRAINT fk_boletin_peso_empresa FOREIGN KEY (id_empresa)
        REFERENCES empresas(id_empresa) ON DELETE CASCADE,
    CONSTRAINT fk_boletin_peso_especie FOREIGN KEY (id_especie)
        REFERENCES especies(id_especie) ON DELETE SET NULL,
    CONSTRAINT fk_boletin_peso_usuario FOREIGN KEY (id_usuario)
        REFERENCES usuarios(id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS boletin_peso_repeticiones (
    id_repeticion INT AUTO_INCREMENT PRIMARY KEY,
    id_boletin_peso INT NOT NULL,
    nro_repeticion TINYINT UNSIGNED NOT NULL,
    peso_repeticion DECIMAL(10,4) NOT NULL,
    UNIQUE KEY uniq_boletin_repeticion (id_boletin_peso, nro_repeticion),
    CONSTRAINT fk_boletin_peso_repeticiones FOREIGN KEY (id_boletin_peso)
        REFERENCES boletines_peso_1000_semillas(id_boletin_peso) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
