ALTER TABLE usuarios
    ADD COLUMN tipo_usuario ENUM('interno', 'cliente') NOT NULL DEFAULT 'interno',
    ADD COLUMN id_cliente_usuario INT NULL,
    ADD CONSTRAINT fk_usuario_cliente FOREIGN KEY (id_cliente_usuario)
        REFERENCES clientes(id_cliente) ON DELETE SET NULL;

CREATE TABLE portal_documentos (
    id_empresa INT NOT NULL,
    tipo_documento VARCHAR(60) NOT NULL,
    id_documento INT NOT NULL,
    fecha_publicacion TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id_empresa, tipo_documento, id_documento),
    CONSTRAINT fk_portal_documentos_empresa FOREIGN KEY (id_empresa)
        REFERENCES empresas(id_empresa) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
