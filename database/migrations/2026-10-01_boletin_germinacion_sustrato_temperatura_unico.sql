-- Sustrato y Temperatura de cámara son un único valor por boletín (no múltiples checkboxes)
ALTER TABLE boletines_germinacion
    DROP COLUMN sustrato_arena,
    DROP COLUMN sustrato_sobre_papel,
    DROP COLUMN sustrato_entre_papel,
    DROP COLUMN temperatura_camara_20,
    DROP COLUMN temperatura_camara_25,
    DROP COLUMN temperatura_camara_20_30,
    ADD COLUMN sustrato_tipo ENUM('arena', 'sobre_papel', 'entre_papel', 'otro') NULL AFTER pretratamiento_prelavado,
    ADD COLUMN temperatura_camara_tipo ENUM('20', '25', '20_30', 'otra') NULL AFTER sustrato_otro;
