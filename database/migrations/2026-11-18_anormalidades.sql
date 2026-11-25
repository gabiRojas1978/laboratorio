-- Ejecutar una sola vez. Cada empresa recibe su propia copia del catálogo inicial.
CREATE TABLE anormalidades_modelo (
    codigo VARCHAR(30) NOT NULL PRIMARY KEY,
    etiqueta VARCHAR(255) NOT NULL,
    grupo_tipo VARCHAR(255) NOT NULL,
    categoria_principal VARCHAR(255) NOT NULL,
    subcategoria VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE anormalidades (
    id_anormalidad INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
    id_empresa INT NOT NULL,
    codigo VARCHAR(30) NOT NULL,
    etiqueta VARCHAR(255) NOT NULL,
    grupo_tipo VARCHAR(255) NOT NULL,
    categoria_principal VARCHAR(255) NOT NULL,
    subcategoria VARCHAR(255) NOT NULL,
    UNIQUE KEY uniq_anormalidad_empresa_codigo (id_empresa, codigo),
    CONSTRAINT fk_anormalidad_empresa FOREIGN KEY (id_empresa)
        REFERENCES empresas(id_empresa) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TEMPORARY TABLE anormalidades_importacion (
    codigo VARCHAR(30) NOT NULL PRIMARY KEY,
    descripcion VARCHAR(255) NOT NULL
) DEFAULT CHARSET=utf8mb4;

INSERT INTO anormalidades_importacion (codigo, descripcion) VALUES
('00/01', 'Plántula deforme'),
('00/02', 'Plántula fracturada'),
('00/03', 'Cotiledones emergen antes que la raíz primaria'),
('00/04', 'Plántulas fusionadas'),
('00/06', 'Plántula amarilla o blanca'),
('00/07', 'Plántula ahilada'),
('00/08', 'Plántula vítrea'),
('00/09', 'Plántula podrida como consecuencia de infección primaria'),
('00/10', 'Plántula con síntomas de fitotoxicidad'),
('00/11', 'Plántula desbalanceada'),
('11/01', 'Raíz primaria atrofiada'),
('11/02', 'Raíz primaria mazuda'),
('11/03', 'Raíz primaria retrasada'),
('11/04', 'Raíz primaria ausente'),
('11/05', 'Raíz primaria rota'),
('11/06', 'Raíz primaria hendida desde el extremo'),
('11/07', 'Raíz primaria atrapada en la cubierta seminal'),
('11/08', 'Raíz primaria con geotropismo negativo'),
('11/09', 'Raíz primaria con constricciones'),
('11/10', 'Raíz primaria ahilada'),
('11/11', 'Raíz primaria vítrea'),
('11/12', 'Raíz primaria podrida como consecuencia de infección primaria'),
('21/01', 'Hipocótilo y/o epicótilo corto y grueso'),
('21/03', 'Hipocótilo y/o epicótilo profundamente quebrado o roto'),
('21/04', 'Hipocótilo y/o epicótilo hendido longitudinalmente'),
('21/05', 'Hipocótilo y/o epicótilo ausente'),
('21/06', 'Hipocótilo y/o epicótilo curvado o formando lazo'),
('21/07', 'Hipocótilo y/o epicótilo forma un espiral'),
('21/08', 'Hipocótilo y/o epicótilo estrechamente retorcido'),
('21/09', 'Hipocótilo y/o epicótilo con constricciones'),
('21/10', 'Hipocótilo y/o epicótilo ahilado'),
('21/11', 'Hipocótilo y/o epicótilo vítreo'),
('21/12', 'Hipocótilo y/o epicótilo podrida como resultado de una infección primaria'),
('21/13', 'Hipocótilo y/o epicótilo fototropismo negativo'),
('22/01', 'Yema terminal y tejidos circundantes deformada'),
('22/02', 'Yema terminal y tejidos circundantes dañada'),
('22/03', 'Yema terminal y tejidos circundantes ausente'),
('22/04', 'Yema terminal y tejidos circundantes necrótica'),
('22/05', 'Yema terminal y tejidos circundantes podrida como consecuencia de infección primaria'),
('31/01', 'Cotiledones hinchados u ondulados'),
('31/02', 'Cotiledones deformados'),
('31/03', 'Cotiledones rotos o dañados'),
('31/04', 'Cotiledones separados o ausentes'),
('31/05', 'Cotiledones decolorados o necróticos'),
('31/06', 'Cotiledones vítreos'),
('31/07', 'Cotiledones podridos como resultado de una infección primaria'),
('31/08', 'Cotiledones fusionados por ambos lados'),
('33/01', 'Hojas primarias deformadas'),
('33/02', 'Hojas primarias dañadas'),
('33/03', 'Hojas primarias ausentes'),
('33/04', 'Hojas primarias decoloradas'),
('33/05', 'Hojas primarias necróticas'),
('33/07', 'Hojas primarias podridas como resultado de una infección primaria'),
('41/01', 'Coleoptilo mazudo o deformado'),
('41/02', 'Coleoptilo roto'),
('41/03', 'Coleoptilo ausente'),
('41/04', 'Coleoptilo ápice faltante o dañado'),
('41/05', 'Coleoptilo fuertemente curvado o formando lazo'),
('41/06', 'Coleoptilo formando un espiral'),
('41/07', 'Coleoptilo fuertemente retorcido'),
('41/08', 'Coleoptilo hendido en más de un 1/3 de longitud desde el ápice'),
('41/09', 'Coleoptilo ahilado'),
('41/10', 'Coleoptilo podrido como consecuencia de infección primaria'),
('41/11', 'Coleoptilo hendido en otro lugar diferente del ápice'),
('41/12', 'Coleoptilo atrapado por las cubiertas'),
('42/01', 'Hoja primaria longitud menor a la mitad del coleoptilo'),
('42/02', 'Hoja primaria ausente'),
('42/03', 'Hoja primaria deshilachada o deformada'),
('42/04', 'Hoja primaria emerge desde la base del coleoptilo'),
('42/05', 'Hoja primaria amarilla o blanca'),
('42/06', 'Hoja primaria podrida como resultado de una infección primaria');

INSERT INTO anormalidades_modelo (codigo, etiqueta, grupo_tipo, categoria_principal, subcategoria)
SELECT codigo, CONCAT(codigo, ' ', descripcion),
    CASE
        WHEN LEFT(codigo, 2) IN ('00', '11', '21', '22') THEN 'Dicotiledóneas / General'
        WHEN LEFT(codigo, 2) IN ('31', '33') THEN 'Dicotiledóneas'
        ELSE 'Monocotiledóneas'
    END,
    CASE
        WHEN LEFT(codigo, 2) = '00' THEN '0. Anormalidades generales'
        WHEN LEFT(codigo, 2) = '11' THEN '1. Anormalidades del sistema radicular'
        WHEN LEFT(codigo, 2) IN ('21', '22') THEN '2. Anormalidades del sistema de tallo'
        WHEN LEFT(codigo, 2) IN ('31', '33') THEN '3. Anormalidades de Cotiledones y/o hojas primarias'
        ELSE '4. Anormalidades de Coleoptilo y Hoja Primaria'
    END,
    CASE LEFT(codigo, 2)
        WHEN '00' THEN '00 Plántula'
        WHEN '11' THEN '11 Raíz primaria'
        WHEN '21' THEN '21 Hipocótilo y/o epicótilo'
        WHEN '22' THEN '22 Yema terminal y tejidos circundantes'
        WHEN '31' THEN '31 Cotiledones (aplica la regla del 50 %)'
        WHEN '33' THEN '33 Hojas Primarias (aplica la regla del 50 %)'
        WHEN '41' THEN '41 Coleoptilo'
        WHEN '42' THEN '42 Hoja primaria'
    END
FROM anormalidades_importacion;

INSERT INTO anormalidades (id_empresa, codigo, etiqueta, grupo_tipo, categoria_principal, subcategoria)
SELECT e.id_empresa, a.codigo, a.etiqueta, a.grupo_tipo, a.categoria_principal, a.subcategoria
FROM empresas e CROSS JOIN anormalidades_modelo a;

DROP TEMPORARY TABLE anormalidades_importacion;
