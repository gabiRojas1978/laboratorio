-- Vincular boletín de peso de 1000 semillas con el cliente (para poder filtrar)
ALTER TABLE boletines_peso_1000_semillas
    ADD COLUMN id_cliente INT NOT NULL AFTER id_especie,
    ADD KEY idx_boletin_peso_cliente (id_empresa, id_cliente),
    ADD CONSTRAINT fk_boletin_peso_cliente FOREIGN KEY (id_cliente)
        REFERENCES clientes(id_cliente) ON DELETE CASCADE;
