-- Bloque "Pre-tratamiento" del boletín de Germinación (fechas, tipo de pre-tratamiento, sustrato y temperatura de cámara)
ALTER TABLE boletines_germinacion
    ADD COLUMN pretratamiento_desde DATE NULL AFTER fecha_segundo_conteo,
    ADD COLUMN pretratamiento_hasta DATE NULL AFTER pretratamiento_desde,
    ADD COLUMN pretratamiento_total_dias SMALLINT UNSIGNED NULL AFTER pretratamiento_hasta,
    ADD COLUMN pretratamiento_aplica ENUM('si', 'no') NULL AFTER pretratamiento_total_dias,
    ADD COLUMN pretratamiento_frio TINYINT(1) NOT NULL DEFAULT 0 AFTER pretratamiento_aplica,
    ADD COLUMN pretratamiento_nitrato_k TINYINT(1) NOT NULL DEFAULT 0 AFTER pretratamiento_frio,
    ADD COLUMN pretratamiento_presecado TINYINT(1) NOT NULL DEFAULT 0 AFTER pretratamiento_nitrato_k,
    ADD COLUMN pretratamiento_prelavado TINYINT(1) NOT NULL DEFAULT 0 AFTER pretratamiento_presecado,
    ADD COLUMN sustrato_arena TINYINT(1) NOT NULL DEFAULT 0 AFTER pretratamiento_prelavado,
    ADD COLUMN sustrato_sobre_papel TINYINT(1) NOT NULL DEFAULT 0 AFTER sustrato_arena,
    ADD COLUMN sustrato_entre_papel TINYINT(1) NOT NULL DEFAULT 0 AFTER sustrato_sobre_papel,
    ADD COLUMN sustrato_otro VARCHAR(255) NULL AFTER sustrato_entre_papel,
    ADD COLUMN temperatura_camara_20 TINYINT(1) NOT NULL DEFAULT 0 AFTER sustrato_otro,
    ADD COLUMN temperatura_camara_25 TINYINT(1) NOT NULL DEFAULT 0 AFTER temperatura_camara_20,
    ADD COLUMN temperatura_camara_20_30 TINYINT(1) NOT NULL DEFAULT 0 AFTER temperatura_camara_25,
    ADD COLUMN temperatura_camara_otra VARCHAR(100) NULL AFTER temperatura_camara_20_30;
