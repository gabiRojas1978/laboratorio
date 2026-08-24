-- Tabla para multiples emails por cliente (multi-tenant)
CREATE TABLE IF NOT EXISTS cliente_emails (
    id_email_cliente INT AUTO_INCREMENT PRIMARY KEY,
    id_empresa INT NOT NULL,
    id_cliente INT NOT NULL,
    email_cliente VARCHAR(150) NOT NULL,
    principal_email TINYINT(1) NOT NULL DEFAULT 0,
    activo_email TINYINT(1) NOT NULL DEFAULT 1,
    created_at_email TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_cliente_email (id_empresa, id_cliente, email_cliente),
    KEY idx_cliente_emails_cliente (id_empresa, id_cliente, principal_email),
    CONSTRAINT fk_cliente_emails_empresa FOREIGN KEY (id_empresa)
        REFERENCES empresas(id_empresa) ON DELETE CASCADE,
    CONSTRAINT fk_cliente_emails_cliente FOREIGN KEY (id_cliente)
        REFERENCES clientes(id_cliente) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Migrar datos existentes desde clientes.mail_cliente (si tiene valor)
INSERT INTO cliente_emails (id_empresa, id_cliente, email_cliente, principal_email, activo_email)
SELECT c.id_empresa, c.id_cliente, TRIM(c.mail_cliente), 1, 1
FROM clientes c
WHERE c.mail_cliente IS NOT NULL
  AND TRIM(c.mail_cliente) <> ''
  AND NOT EXISTS (
      SELECT 1
      FROM cliente_emails ce
      WHERE ce.id_empresa = c.id_empresa
        AND ce.id_cliente = c.id_cliente
        AND ce.email_cliente = TRIM(c.mail_cliente)
  );

-- Opcional (ejecutar cuando confirmes que ya no usas la columna antigua):
-- ALTER TABLE clientes DROP COLUMN mail_cliente;
