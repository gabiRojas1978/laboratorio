# Laboratorio Leucaena

Proyecto base en PHP 8 con Bootstrap para un laboratorio de semillas.

## Incluye

- Login simple con sesión
- Menú lateral con:
  - Administración
    - Clientes
    - Especies
    - Determinaciones
    - Cultivar
    - Otras Determinaciones
  - Libro de Ingreso
    - Registro de muestras recibidas con N° INASE correlativo y N° de muestra automático
    - Botón **BOL** por ingreso para elegir un tipo de boletín y precargar sus datos sin guardarlo automáticamente
  - Certificados
    - Certificado general
    - Certificado INASE
    - Certificado de tetrazolio
- Paleta verde y negro inspirada en el logo

## Usuario de prueba

- Usuario: `admin`
- Contraseña: `admin123`

## Portal de clientes

Aplicar una sola vez
[2026-11-25_portal_clientes.sql](database/migrations/2026-11-25_portal_clientes.sql)
antes de desplegar el código del portal. Los usuarios existentes quedan como
**Internos**, sin cambiar sus permisos actuales.

En **Usuarios**, elegir **Cliente** y asociar la cuenta a un cliente del
laboratorio. Se pueden crear varias cuentas para el mismo cliente. Ingresan por
el mismo login y solo acceden a **Mis boletines y certificados**, con consulta y
descarga de PDF, sin operaciones administrativas. Desactivar la cuenta, eliminarla
o quitar su cliente impide el acceso también en sesiones ya iniciadas.

En **Administración → Documentos del portal**, filtrar por tipo y cliente,
revisar el PDF y pulsar **Habilitar para cliente**. Ningún documento se publica
automáticamente. **Retirar del portal** quita su disponibilidad sin borrar el
original. Se incluyen los ocho tipos de boletines y los tres certificados.
Las publicaciones usan el documento actual: una edición posterior se refleja
en el PDF; retirar del portal antes de editar si requiere una nueva revisión.
Si cambia el cliente del documento, su visibilidad sigue al cliente actual.
Las copias ya descargadas no se pueden retirar.

El servidor debe impedir el acceso HTTP directo a `storage/` (la regla Apache
ya está en `storage/.htaccess`). En Nginx u otro servidor, configurar la regla
equivalente; el servidor PHP integrado no aplica `.htaccess` y no debe exponerse
como portal público. Los PDF del portal se entregan mediante rutas autenticadas,
comprobando laboratorio, cliente y publicación en cada solicitud.
`php tests/portal_clientes_test.php` verifica cuentas, publicación, descargas y
restricciones del portal. Requiere la base migrada y permiso para crear bases de
prueba aisladas, que se eliminan al finalizar; no modifica los datos del laboratorio.

## Certificado general

En los listados de certificados general, INASE y tetrazolio se puede eliminar un
certificado con confirmación. También se elimina su PDF guardado en el servidor;
los ingresos y boletines de origen no se modifican. Las copias ya descargadas o
enviadas por correo no se eliminan.

En **Configuración** se puede cargar una imagen PNG o JPG (hasta 5 MB) de la
firma del director técnico. Se guarda por laboratorio y se imprime en el
recuadro de firma de los certificados, encima del texto configurado. La imagen
puede reemplazarse o quitarse sin modificar ese texto.
La prueba `php tests/certificado_tetrazolio_pdf_test.php` verifica su impresión.

## Presupuestos del libro de ingreso

En el listado de ingresos, marcar las muestras de un mismo cliente y elegir
**Revisar y enviar presupuesto** para revisar y enviar un PDF conjunto. El listado
puede filtrarse por cliente y por rango de fechas de entrada antes de seleccionar.
Se muestra cada
muestra con sus determinaciones, subtotal y el total general. Cada muestra debe
tener determinaciones; se pueden seleccionar una o varias muestras. El presupuesto incluye
los precios vigentes al generar o enviar el PDF,
en pesos argentinos y sin agregar impuestos. El envío no guarda una copia histórica;
si cambian los precios o el ingreso, un presupuesto posterior reflejará esos cambios.
Los costos se administran en **Determinaciones del Libro de Ingreso**; un costo
vacío se guarda como cero.

En **Configuración** cargar el nombre y logo del laboratorio, además de dirección,
teléfono, correo y CUIT para la cabecera. Para enviar, configurar SMTP y registrar
los correos del cliente; se pueden seleccionar los destinatarios antes del envío.
El nombre y el logo son necesarios para generar el PDF.
`php tests/presupuesto_test.php` verifica el presupuesto y simula el correo sin
enviarlo. Requiere permiso para crear una base de prueba aislada, que se elimina
al finalizar.

El Certificado general incluye **Hipoclorito max 10%**, **Peroxidasa** (positivas
y negativas), **Contam. x hilo (250gr)** (cantidad de semillas y color contaminante)
y **Hipocotilo** (variedad y color contaminante). Los valores se guardan con el
certificado y solo se imprimen en el PDF cuando se completan.

Antes de usar estos campos, ejecutar primero
[2026-11-04_certificado_hipoclorito.sql](database/migrations/2026-11-04_certificado_hipoclorito.sql)
y luego, una sola vez,
[2026-11-05_certificado_determinaciones_adicionales.sql](database/migrations/2026-11-05_certificado_determinaciones_adicionales.sql).
`php tests/certificado_campos_opcionales_pdf_test.php` verifica que los campos
vacíos se omitan y que se impriman correctamente los valores completados.

## Ejecución local

Desde la carpeta del proyecto:

```bash
php -S localhost:8000
```

Luego abrir:

```text
http://localhost:8000
```

Si usas Laragon, también puedes apuntar el virtual host a esta carpeta.

## Anormalidades

En **Configuración → Administrar anormalidades** se pueden dar de alta y editar
el código, la etiqueta completa, el grupo o tipo, la categoría principal y la
subcategoría. Cada laboratorio administra su catálogo por separado y el código
no puede repetirse dentro de una misma empresa.

Ejecutar una sola vez
[2026-11-18_anormalidades.sql](database/migrations/2026-11-18_anormalidades.sql)
antes de usar la pantalla o dar de alta nuevas empresas. La migración crea el
catálogo y su plantilla, y carga los 71 registros proporcionados en cada empresa
existente. Las empresas nuevas reciben una copia de la plantilla original; los
cambios de un laboratorio no afectan a otros ni a la plantilla.

`php tests/anormalidades_test.php` verifica la migración en tablas temporales,
el alta, la edición, las validaciones y el aislamiento entre empresas.

## Edición de boletines

Peso de 1000 semillas, Tetrazolio, Tetrazolio (Soja), Pureza y Malezas permiten
abrir **Editar** desde su listado o mediante `?page=boletin_…&editar=ID`.
Se precargan la cabecera y todas sus filas; **Actualizar** conserva el ID y
reemplaza las repeticiones, componentes y determinaciones en una transacción.
Solo se pueden consultar o modificar boletines de la empresa activa y se
mantienen las restricciones de especie y tipo de análisis. **Cancelar edición**
regresa al formulario de alta.

`php tests/boletin_edicion_test.php` verifica altas, edición sin duplicados,
carga de hijos, aislamiento, validaciones y rollback. Requiere la base configurada
con las tablas de estos boletines y dos empresas; elimina sus registros de prueba.

## Determinaciones y costos

**Administración → Determinaciones** permite dar de alta y modificar el nombre y
el costo de las determinaciones de la empresa activa. Usa exclusivamente
`determinaciones_libro_ingreso`, el mismo catálogo que el campo Determinaciones del
Libro de Ingreso. La migración elimina la tabla independiente `ensayos`, descartada.
No modifica las otras determinaciones de los certificados.

El nombre es obligatorio, admite hasta 255 caracteres y no se puede repetir dentro
de una empresa. El costo se guarda como `DECIMAL(12,2)`, sin importes negativos ni
más de dos decimales; admite coma o punto decimal, sin separadores de miles.
El valor inicial y el campo vacío se guardan como **0,00**. Se deja disponible para
futuros presupuestos; todavía no se implementa su envío.

Ejecutar **una sola vez**
[2026-10-27_determinaciones_libro_ingreso_costos.sql](database/migrations/2026-10-27_determinaciones_libro_ingreso_costos.sql),
después de la migración del Libro de Ingreso. Agrega el costo, reemplaza el catálogo
de las empresas 1 y 4 con los 16 nombres de la imagen y conserva las asociaciones
de los ingresos mediante equivalencias de nombres (incluida Energía con tratamiento).
Antes de aplicarla en otra base, verificar que las determinaciones vinculadas
correspondan al catálogo original de cinco nombres; respaldar la base.
No se vacían los catálogos de otras empresas.

`php tests/determinaciones_libro_ingreso_test.php` verifica alta, modificación,
costos, validación, duplicados, pantalla e aislamiento, y revierte sus registros.
`php tests/libro_ingreso_boletin_test.php` comprueba también las correspondencias
de los nuevos nombres con los boletines existentes.

## Fecha de inicio de análisis

El libro de ingreso permite registrar una **Fecha de inicio de análisis** opcional,
que también se muestra en el listado de ingresos. Si se deja vacía, se guarda como
`NULL`; los ingresos existentes conservan sus datos.

Antes de usar este campo, ejecutar en la base de datos configurada la migración
[2026-10-21_libro_ingreso_fecha_inicio_analisis.sql](database/migrations/2026-10-21_libro_ingreso_fecha_inicio_analisis.sql).
La tabla `libro_ingreso` debe existir previamente.

En **Ingresos registrados** se muestran únicamente Muestra, Pres., Fact, Entrada,
Inicio, Cliente, Especie y Acciones. El icono del **ojo** abre el ingreso completo
para consultar y editar presupuesto, facturación, fechas, remitente, cliente,
origen, especie, cultivar, lote/observaciones, ubicación y determinaciones.
El N° INASE y el N° de muestra son de solo lectura y se conservan al guardar,
igual que el año y los identificadores automáticos. Los botones BOL y CERT
siguen disponibles. Cambiar la fecha o la especie recalcula los conteos pendientes
de la agenda; no modifica boletines ni borra conteos ya marcados como realizados.

Las pruebas `php tests/libro_ingreso_fecha_test.php` y
`php tests/libro_ingreso_edicion_test.php` verifican la actualización de la fecha,
la edición completa, la validación, el aislamiento por empresa y el listado.

## Conteos por especie

El alta, la edición y el listado de especies incluyen **Primer conteo (días)** y
**Conteo final (días)**. Son campos opcionales de días enteros positivos; un campo
vacío se guarda como `NULL`.

Ejecutar primero [2026-10-21_especies_conteos.sql](database/migrations/2026-10-21_especies_conteos.sql)
y luego [2026-10-21_especies_conteos_datos.sql](database/migrations/2026-10-21_especies_conteos_datos.sql).
La segunda migración carga las 24 especies de la tabla proporcionada en las empresas
1 y 4. Si una especie ya existe en esa empresa, solo actualiza sus conteos, sin
cambiar su ID ni su nombre científico; no duplica la Soja existente. Los nombres
científicos no informados en la tabla quedan en `NULL`.

La prueba `php tests/especies_conteos_test.php` verifica el guardado, la validación,
la edición y el aislamiento por empresa; revierte los registros de prueba.

## Agenda semanal de conteos

El Inicio muestra los conteos pendientes de hoy y los próximos seis días, y una
sección separada para los vencidos (fecha anterior a hoy). Se programa cada conteo
de todos los ingresos de la empresa, independientemente de sus determinaciones:
**fecha de inicio de análisis + días del conteo de la especie**, en días corridos
(el inicio es el día cero). Cambiar los días de la especie recalcula los pendientes.

El botón **Marcar realizado** registra el cumplimiento de ese conteo en el ingreso
y lo retira de los pendientes y vencidos. Primer conteo y conteo final se confirman
por separado. Es un registro manual, también disponible si el conteo se realiza
anticipadamente; no guarda resultados ni modifica boletines.

Los ingresos sin fecha de inicio, especie o días de conteo generan un aviso de
datos faltantes, no fechas estimadas. La agenda usa la fecha y zona horaria del
servidor PHP, igual que el resto de la aplicación.

Antes de usarla, aplicar
[2026-10-21_libro_ingreso_conteos_realizados.sql](database/migrations/2026-10-21_libro_ingreso_conteos_realizados.sql),
después de las migraciones de fecha de inicio y conteos de especies.

La prueba `php tests/agenda_conteos_test.php` verifica fechas (incluidos cambios de
mes y año), límites semanales, vencidos, cumplimiento independiente, aislamiento
por empresa y renderizado; revierte los registros de prueba.

## Boletines desde el libro de ingreso

En **Ingresos registrados**, pulsa **BOL**, elige el tipo de boletín y selecciona
**Abrir / cargar**. Si ya existe, se abre en modo edición; si no, se precargan
los datos del ingreso. Se completan cliente y especie, N° de muestra si el formulario
lo incluye, N° INASE como número de análisis si corresponde, y el texto de
lote/observaciones en observaciones. Las fechas de ensayo y los resultados quedan
sin completar; la fecha de entrada no se utiliza como fecha de ensayo.

El selector muestra solamente los boletines correspondientes a las determinaciones
guardadas en ese ingreso. Energía y poder germinativo, con o sin tratamiento,
habilitan Germinación (una sola opción); **Cold Test-Vigor** y **Vigor por
Envejecimiento Acelerado** habilitan sus boletines; Peso de 1000 semillas habilita
el suyo. Pureza y Otras semillas en número usan únicamente las determinaciones
indicadas en la tabla de correspondencias. Las determinaciones sin relación
definida no habilitan un boletín.

En **Ingresos registrados**, **CERT** abre la selección habitual de tipo de
certificado. Si el ingreso incluye **Test de Lavandina- Evaluacion de daño
Mecanico**, abre directamente el certificado general no INASE, con los datos del
ingreso precargados para completar **Hipoclorito max 10%**.

Revisa los datos, completa el ensayo y guarda desde el formulario del boletín.
Los boletines cargados desde **BOL** quedan vinculados al ingreso. Al volver a
pulsar **BOL**, los boletines ya cargados se abren en edición y los demás tipos
disponibles pueden agregarse sin duplicar los existentes. La migración
[2026-11-02_libro_ingreso_boletines.sql](database/migrations/2026-11-02_libro_ingreso_boletines.sql)
crea la relación entre ambos registros; ejecútala después de la migración del
Libro de Ingreso. La prueba `php tests/boletin_edicion_test.php` verifica que los
cinco formularios con edición carguen y reemplacen sus datos asociados.

**Tetrazolio (Soja)** tiene un boletín separado para soja, con planilla interna y
resumen de resultados. Viabilidad por tetrazolio-Soja y Vigor por tetrazolio Soja
lo habilitan únicamente si la especie del ingreso es soja. El Tetrazolio existente
se habilita para Vigor por tetrazolio (otras semillas y forrajeras), y solo con
especies distintas de soja. Se registran cuatro repeticiones de 200 semillas; los cinco niveles de viabilidad
deben sumar 200 en cada repetición. Se calculan medias, porcentajes sobre 200,
viabilidad total, vigor acumulado (A+B+C) y diferencia entre la repetición mayor
y menor. Los daños se guardan por nivel y se acumulan en el resumen. La tolerancia
de tabla queda como dato manual.

Antes de usar este boletín, ejecutar
[2026-11-02_boletin_tetrazolio_soja.sql](database/migrations/2026-11-02_boletin_tetrazolio_soja.sql).
Las pruebas `php tests/boletin_tetrazolio_soja_test.php` y
`php tests/boletin_tetrazolio_soja_pdf_test.php` verifican correspondencias,
restricción de especie, cálculos y generación de las dos hojas del PDF.

Cold Test y Envejecimiento acelerado usan los mismos campos, validaciones,
cálculos y formato PDF que Germinación, pero sus registros se mantienen separados.
Antes de usarlos, ejecutar
[2026-10-26_boletin_germinacion_tipos.sql](database/migrations/2026-10-26_boletin_germinacion_tipos.sql)
después de las migraciones del boletín de Germinación.

En el boletín de Germinación se pueden seleccionar varias anormalidades del
catálogo del laboratorio, debajo de las repeticiones. La selección se guarda con
el boletín, puede modificarse al editarlo y aparece en su PDF. El PDF usa las
etiquetas actuales del catálogo y puede ocupar más de una página si es necesario.
Para habilitarlo, ejecutar una sola vez
[2026-11-18_boletin_germinacion_anormalidades.sql](database/migrations/2026-11-18_boletin_germinacion_anormalidades.sql)
después de las migraciones de Germinación y Anormalidades.
Las pruebas `php tests/boletin_germinacion_anormalidades_test.php`,
`php tests/boletin_germinacion_anormalidades_guardado_test.php` y
`php tests/boletin_germinacion_pdf_test.php` verifican la selección, su guardado y el PDF.

## Certificados desde el libro de ingreso

Pulsa **CERT** en el ingreso, elige certificado general, INASE o tetrazolio y pulsa
**Ver boletines disponibles**. Selecciona explícitamente los ensayos a importar y
pulsa **Precargar certificado**; también puedes continuar sin boletines y completar
los resultados manualmente. No se guarda ni emite un certificado automáticamente.

- Se precargan cliente, especie, cultivar cuando corresponde, lote/observaciones,
  N° INASE como N° de análisis, fecha de entrada como recepción y N° de muestra en referencias.
- Germinación, peso, otras especies y tetrazolio deben coincidir en empresa,
  cliente, especie y N° de muestra. Germinación solo ofrece ensayos finalizados;
  PG y PG curado se eligen por separado y no pueden usar el mismo boletín.
- Pureza no guarda N° de muestra: se ofrecen los análisis del mismo cliente y
  especie con su número y fecha, para confirmar manualmente la asociación.
- Se importan porcentajes finales de germinación y días entre siembra y conteo
  final; porcentajes y componentes de pureza; peso de 1000 semillas
  como otra determinación; y especies halladas con sus cantidades.
  El primer conteo de germinación no se convierte automáticamente en un ensayo de vigor.
- En tetrazolio se importa no viables y se muestra el total viable como referencia:
  no se inventa el desglose de categorías viables, vigor o daños.
- La fecha final de ensayos es la última fecha disponible de los boletines elegidos.
  Revisa fechas, resultados y campos pendientes antes de guardar. El ingreso y los
  boletines originales no se modifican.
- Si un detalle de materia inerte u otras semillas supera los 100 caracteres que
  admite el certificado, se muestra completo en un aviso para resumirlo manualmente,
  sin truncarlo ni intentar guardarlo en un campo demasiado corto.
