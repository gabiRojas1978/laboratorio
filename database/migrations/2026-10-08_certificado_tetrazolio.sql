-- Campos específicos del certificado de prueba topográfica por tetrazolio.
ALTER TABLE certificados
    ADD COLUMN tetrazolio_detalle_certificado VARCHAR(255) NULL AFTER otras_determinaciones_texto_certificado,
    ADD COLUMN tetrazolio_viable_sin_defectos_certificado DECIMAL(6,2) NULL AFTER tetrazolio_detalle_certificado,
    ADD COLUMN tetrazolio_viable_defectos_leves_certificado DECIMAL(6,2) NULL AFTER tetrazolio_viable_sin_defectos_certificado,
    ADD COLUMN tetrazolio_viable_defectos_moderados_certificado DECIMAL(6,2) NULL AFTER tetrazolio_viable_defectos_leves_certificado,
    ADD COLUMN tetrazolio_viable_defectos_severos_certificado DECIMAL(6,2) NULL AFTER tetrazolio_viable_defectos_moderados_certificado,
    ADD COLUMN tetrazolio_no_viables_certificado DECIMAL(6,2) NULL AFTER tetrazolio_viable_defectos_severos_certificado,
    ADD COLUMN tetrazolio_vigor_alto_certificado DECIMAL(6,2) NULL AFTER tetrazolio_no_viables_certificado,
    ADD COLUMN tetrazolio_vigor_medio_certificado DECIMAL(6,2) NULL AFTER tetrazolio_vigor_alto_certificado,
    ADD COLUMN tetrazolio_vigor_bajo_certificado DECIMAL(6,2) NULL AFTER tetrazolio_vigor_medio_certificado,
    ADD COLUMN tetrazolio_danos_mecanicos_certificado DECIMAL(6,2) NULL AFTER tetrazolio_vigor_bajo_certificado,
    ADD COLUMN tetrazolio_danos_ambientes_certificado DECIMAL(6,2) NULL AFTER tetrazolio_danos_mecanicos_certificado,
    ADD COLUMN tetrazolio_danos_chinches_certificado DECIMAL(6,2) NULL AFTER tetrazolio_danos_ambientes_certificado,
    ADD COLUMN tetrazolio_danos_fracturas_certificado DECIMAL(6,2) NULL AFTER tetrazolio_danos_chinches_certificado,
    ADD COLUMN tetrazolio_danos_otros_certificado DECIMAL(6,2) NULL AFTER tetrazolio_danos_fracturas_certificado,
    ADD COLUMN tetrazolio_duras_certificado DECIMAL(6,2) NULL AFTER tetrazolio_danos_otros_certificado;
