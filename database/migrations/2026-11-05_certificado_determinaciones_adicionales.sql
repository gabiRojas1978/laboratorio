ALTER TABLE certificados
    ADD COLUMN peroxidasa_positivas_certificado DECIMAL(6,2) NULL AFTER hipoclorito_max_10_certificado,
    ADD COLUMN peroxidasa_negativas_certificado DECIMAL(6,2) NULL AFTER peroxidasa_positivas_certificado,
    ADD COLUMN contam_hilo_cantidad_semillas_certificado DECIMAL(6,2) NULL AFTER peroxidasa_negativas_certificado,
    ADD COLUMN contam_hilo_color_contaminante_certificado VARCHAR(255) NULL AFTER contam_hilo_cantidad_semillas_certificado,
    ADD COLUMN hipocotilo_variedad_certificado VARCHAR(255) NULL AFTER contam_hilo_color_contaminante_certificado,
    ADD COLUMN hipocotilo_color_contaminante_certificado VARCHAR(255) NULL AFTER hipocotilo_variedad_certificado;
