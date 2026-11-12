ALTER TABLE boletines_germinacion
    ADD COLUMN tipo_boletin ENUM('germinacion', 'cold_test', 'envejecimiento_acelerado') NOT NULL DEFAULT 'germinacion' AFTER id_empresa,
    ADD KEY idx_boletines_germinacion_tipo_empresa (tipo_boletin, id_empresa);
