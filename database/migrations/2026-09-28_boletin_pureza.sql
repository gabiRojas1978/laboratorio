-- Boletín de Pureza física-botánica (cabecera + componentes clasificados)
CREATE TABLE IF NOT EXISTS boletines_pureza (
    id_boletin_pureza INT AUTO_INCREMENT PRIMARY KEY,
    id_empresa INT NOT NULL,
    id_cliente INT NOT NULL,
    id_usuario INT NULL,
    id_especie INT UNSIGNED NULL,
    analisis_nro VARCHAR(100) NOT NULL,
    fecha_analisis DATE NULL,
    analista VARCHAR(150) NULL,
    peso_inicial DECIMAL(12,4) NOT NULL,
    peso_semilla_pura DECIMAL(12,4) NOT NULL,
    peso_materia_inerte DECIMAL(12,4) NOT NULL DEFAULT 0,
    peso_otras_semillas DECIMAL(12,4) NOT NULL DEFAULT 0,
    peso_total DECIMAL(12,4) NOT NULL DEFAULT 0,
    diferencia_peso DECIMAL(12,4) NOT NULL DEFAULT 0,
    porcentaje_semilla_pura DECIMAL(8,4) NOT NULL DEFAULT 0,
    porcentaje_materia_inerte DECIMAL(8,4) NOT NULL DEFAULT 0,
    porcentaje_otras_semillas DECIMAL(8,4) NOT NULL DEFAULT 0,
    observaciones TEXT NULL,
    created_at_boletin TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_boletin_pureza_empresa_cliente (id_empresa, id_cliente),
    CONSTRAINT fk_boletin_pureza_empresa FOREIGN KEY (id_empresa)
        REFERENCES empresas(id_empresa) ON DELETE CASCADE,
    CONSTRAINT fk_boletin_pureza_cliente FOREIGN KEY (id_cliente)
        REFERENCES clientes(id_cliente) ON DELETE CASCADE,
    CONSTRAINT fk_boletin_pureza_especie FOREIGN KEY (id_especie)
        REFERENCES especies(id_especie) ON DELETE SET NULL,
    CONSTRAINT fk_boletin_pureza_usuario FOREIGN KEY (id_usuario)
        REFERENCES usuarios(id_usuario) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS boletin_pureza_componentes (
    id_componente_pureza INT AUTO_INCREMENT PRIMARY KEY,
    id_boletin_pureza INT NOT NULL,
    tipo_componente ENUM('materia_inerte', 'otras_semillas') NOT NULL,
    nombre_componente VARCHAR(255) NOT NULL,
    peso_gramos DECIMAL(12,4) NOT NULL,
    KEY idx_pureza_componentes_boletin (id_boletin_pureza, tipo_componente),
    CONSTRAINT fk_pureza_componentes_boletin FOREIGN KEY (id_boletin_pureza)
        REFERENCES boletines_pureza(id_boletin_pureza) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
