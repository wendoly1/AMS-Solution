# Conexión del sitio público con el panel AMS

El sitio usa PDO MySQL y la tabla existente `site_settings`. No hay que reconstruir páginas, cambiar CSS ni crear otra base. El contenido actual de cada página sigue como respaldo cuando no existe una clave o no está disponible la base. Los cambios del panel se leen en la siguiente petición, sin publicar archivos PHP nuevamente.

## Ubicación en Hostinger

```text
public_html/
  index.php, servicios.php, equipo.php, sobre_la_firma.php, contacto.php
  css/, img/, includes/
  config/database.local.php
  config/.htaccess
  uploads/branding/, uploads/team/, uploads/.htaccess
  admin/                 ← archivos internos del panel y su CSS
```

La carpeta `ams-panel-administrativo/` es una copia de trabajo independiente: no se modificó en esta integración. Para instalar ese panel, copiar **el contenido de su carpeta `admin/`** a `public_html/admin/`. Sus archivos `bootstrap.php` ya requieren `../includes/database.php` y `../includes/content.php`: en esa ubicación usan los mismos archivos del sitio. No sobrescribir los `includes/` públicos con las copias antiguas del proyecto del panel.

El cargador de imágenes revisado en ese panel guarda en `dirname(__DIR__)/uploads`: instalado en `public_html/admin`, guarda correctamente en `public_html/uploads`. Si ya existen imágenes en la carpeta de trabajo del panel, copiar también su contenido de `uploads/` al directorio público de uploads, conservando nombres y subcarpetas, sin borrar archivos anteriores.

## Credenciales y tablas

`includes/database.php` es el único conector. Lee `config/database.local.php`, que está excluido de Git. Se conservaron en ese archivo privado las credenciales que ya tenía este proyecto. Para una instalación nueva, copiar `config/database.example.php` como `config/database.local.php` y completar `local` y `production`. Subir explícitamente el archivo privado al hosting: Git no lo incluye. No copiar el archivo de ejemplo sobre una configuración ya completada.

El entorno se elige con `AMS_ENV=local` o `AMS_ENV=production`; sin esa variable, localhost usa local y un dominio usa producción. También se admiten `AMS_DB_HOST`, `AMS_DB_PORT`, `AMS_DB_NAME`, `AMS_DB_USER`, `AMS_DB_PASS`. Ambos procesos PHP deben usar la misma configuración. Requiere PHP 8.0 o superior y `pdo_mysql`.

Usar la misma base y estas tablas existentes:

- `site_settings`: `setting_key VARCHAR(120)` como clave primaria, `setting_value LONGTEXT`, `updated_at`.
- `admin_users`: usuarios del panel; el sitio público no los consulta.
- `contactos`: solicitudes del formulario público.

`database.sql` contiene `CREATE TABLE IF NOT EXISTS` y no borra datos. En Hostinger, seleccionar primero la base asignada en phpMyAdmin y ejecutar **solo los bloques CREATE TABLE**: no ejecutar allí `CREATE DATABASE ams_legal` ni `USE ams_legal`. No importar ni reemplazar datos de una base existente para conectar el panel.

## Contrato que debe guardar el panel

Guardar mediante INSERT/UPDATE de `site_settings`. Las claves son literales con puntos. Los valores de texto son texto plano UTF-8, no HTML ni texto escapado; el sitio los escapa al mostrarlos. Una clave ausente usa el texto original; una cadena vacía se muestra vacía. No borrar claves ajenas al guardar una sección.

| Contenido | Claves |
| --- | --- |
| Identidad | `site.name`, `site.location`, `site.logo`, `site.favicon`, `site.office_image`, `site.footer_description` |
| Dominio canónico | `site.url`, por ejemplo `https://amslegalsolutions.com` |
| Contacto | `contact.address`, `contact.phone`, `contact.email`, `contact.hours`, `contact.heading`, `contact.intro` |
| Portada | `home.hero_title`, `home.hero_description`, `home.services_title`, `home.services_text`, `home.about_title`, `home.about_p1`, `home.about_p2`, `home.team_title` |
| Estadísticas | `stats.years`, `stats.cases`, `stats.lawyers`, `stats.satisfaction` |
| Firma | `firm.hero_title`, `firm.hero_text`, `firm.history_title`, `firm.history_p1`, `firm.history_p2`, `firm.quote`, `firm.quote_author`, `firm.values_title` |
| Valores de firma | `firm.value_1_name` y `firm.value_1_description`, igual para 2, 3 y 4 |
| Introducción de servicios | `services.intro_title`, `services.intro_text` |
| Introducción del equipo | `team.intro_title`, `team.intro_text` |
| SEO por página | `seo.home_title`, `seo.home_description`; igual para `services`, `firm`, `team`, `contact` |
| Listas | `content.services` y `content.team`, como JSON de arrays |

El panel de `public_html/admin` ya incluye controles para favicon, dominio y pie, WhatsApp, Maps, redes sociales, botones del encabezado y botones de portada. La copia independiente `ams-panel-administrativo/` no incluye necesariamente estas mejoras; para desplegar el panel actualizado, usar la carpeta `admin/` de este proyecto.

Ejemplo del valor de `content.services`:

```json
[
  {
    "slug": "corporativo",
    "name": "Derecho Corporativo",
    "summary": "Resumen para la portada",
    "description": "Descripción completa",
    "services": ["Constitución de sociedades", "Contratos"],
    "steps": ["Diagnóstico", "Acompañamiento"]
  }
]
```

Ejemplo del valor de `content.team`:

```json
[
  {
    "slug": "abogada-ejemplo",
    "name": "Nombre de la abogada",
    "role": "Socia",
    "area": "Derecho Corporativo",
    "bio": "Biografía",
    "image": "uploads/team/foto.webp"
  }
]
```

Los slugs deben ser únicos dentro de cada lista, en minúsculas, con letras a-z, números y guiones entre palabras. El orden del array es el orden público. `[]` vacía una lista; JSON inválido o filas sin nombre/slug válido recuperan los valores originales y escriben el motivo en el log de PHP. No se utiliza una tabla independiente de servicios o abogados: si el panel cambia a ese modelo, hará falta un adaptador.

Portada, página de servicios, pie y opciones de los formularios comparten `content.services`. El pie muestra hasta cuatro servicios guardados. Portada y equipo comparten `content.team`. El título original de portada conserva sus tres líneas, dorado y cursiva; un título diferente muestra el texto del panel con sus saltos de línea.

## Archivos de imagen

Guardar rutas públicas como `uploads/branding/logo.png`, `uploads/branding/favicon.png` o `uploads/team/foto.webp`; también se admiten URL absolutas HTTP/HTTPS. No guardar rutas de Windows, `../uploads`, rutas temporales de subida ni `public_html/...`. Las rutas relativas parten del sitio público, aunque se editen desde `/admin`. Mantener `/uploads` con permisos de escritura para PHP y conservar su `.htaccess`.

`site.logo` controla el encabezado; el pie mantiene el nombre de firma como texto, según el diseño existente. `site.office_image` se comparte entre inicio y firma. `site.favicon` controla el icono del navegador y el enlace de Apple; se recomienda PNG. Las imágenes no se almacenan como binarios en MySQL: allí se guarda su ruta.

## Verificación

Ejecutar `C:\xampp\php\php.exe tests/content-integration.php` para comprobar las cinco páginas con contenido original, contenido del panel, JSON inválido y listas vacías. Usa una base SQLite temporal en memoria para probar lectura y renderizado sin alterar datos del proyecto; no verifica conectividad real con MySQL.

Para cerrar la prueba real, con MySQL activo y ambas partes desplegadas: guardar desde el panel un texto, teléfono, servicio, abogado, logo y favicon; abrir inicio, firma, servicios, equipo y contacto y comprobar esos mismos valores. Verificar también título/meta descripción/canonical en el HTML. Las solicitudes del formulario se guardan en `contactos`; la entrega de correo sigue requiriendo que `mail()` esté configurado en el servidor.

## Mejoras para la entrega al cliente

- **Vista previa:** cada formulario incorpora un botón junto a Guardar. Abre otra pestaña con los valores actuales y las imágenes seleccionadas, sin escribirlos en la base pública ni subir las imágenes. Permite recorrer las cinco páginas. La vista previa exige sesión administrativa, dura 20 minutos y bloquea el envío de formularios; solo se conserva el último borrador de esa sesión. La publicación ocurre al guardar desde el panel original.
- **Cambios pendientes:** se muestra el estado del formulario y un aviso al intentar salir sin guardar, incluso al agregar o quitar áreas y abogados. La vista previa no quita el aviso. No es un guardado automático: se debe guardar antes de cerrar.
- **Contacto y botones:** `contact.whatsapp`, `contact.map_url`, `social.facebook`, `social.instagram`, `social.linkedin`; los campos vacíos ocultan el enlace. Los textos y destinos del encabezado son `header.cta_label`, `header.cta_url`; los de Inicio son `home.primary_label`, `home.primary_url`, `home.secondary_label`, `home.secondary_url`. Los destinos aceptan las páginas públicas existentes, anclas o URL HTTP/HTTPS. Se rechazan enlaces ejecutables y rutas privadas.
- **Imágenes:** se muestran localmente al seleccionarlas, con sus dimensiones y aviso de que aún no están guardadas. Hay recomendaciones de tamaño, límite de 5 MB por archivo y verificación del formato en cliente y servidor. La vista previa admite hasta 15 MB de imágenes en total.
- **Consultas recibidas:** lee `contactos` con paginación y permite marcar como leída o pendiente. No envía respuestas ni elimina solicitudes. Si el formulario guarda la solicitud pero falla `mail()`, confirma la recepción porque la consulta ya está disponible en la bandeja.
- **Historial:** cada guardado que cambie contenido crea una revisión de la sección con fecha y usuario. Se puede restaurar esa versión o la anterior; la restauración también se registra, dentro de la misma transacción que los cambios. Los archivos originales se conservan para poder recuperar las imágenes. El historial comienza con el primer guardado posterior a esta actualización: no recupera cambios previos a ella.
- **Ayudas:** los grupos y ejemplos señalan dónde aparece cada campo en el sitio, qué enlaces se aceptan y qué imagen se recomienda.

Al instalar esta versión, subir también `database-editor.sql`. El panel crea sus tablas adicionales `site_revisions` y `contacto_estado` automáticamente al abrir bandeja/historial o guardar; si el usuario de MySQL no tiene permisos de creación, ejecutar ese SQL previamente desde phpMyAdmin en la misma base del sitio. No sobrescribir datos existentes. Para la instalación completa, usar los archivos actuales de `admin/`, `includes/`, las páginas públicas y `database-editor.sql` de este proyecto.

Prueba funcional: `C:\xampp\php\php.exe tests/editor-integration.php`. Requiere MySQL local, `pdo_mysql`, `curl` y permisos para crear una base temporal. Levanta un servidor PHP de prueba en el puerto 18081, crea y elimina únicamente su base `ams_editor_test_*` y limpia las imágenes que generó. Verifica autenticación, CSRF, publicación, vista previa sin publicar, imágenes, áreas, abogados, restauraciones y bandeja. No usa consultas ni credenciales de administradores del cliente.

## Contraseña y recuperación del acceso

**Registrar otro administrador:** abrir **Registrar usuario** en el menú o desde Mi cuenta. Se completa el usuario, su correo de recuperación repetido, una contraseña nueva repetida y la contraseña del administrador que autoriza el alta. Requiere sesión activa y CSRF. Los nombres y correos no pueden repetirse; la clave se guarda como hash. La cuenta se activa de inmediato y no depende del envío de correo. Todos los usuarios del modelo actual tienen acceso administrativo completo, incluida la creación de cuentas. No se ofrece registro público en el login ni se reabre la configuración inicial. El alta conserva la sesión del administrador actual.

1. Los usuarios existentes ingresan con su usuario y contraseña habituales. En **Mi cuenta y contraseña** registran un correo de recuperación, lo repiten y confirman su contraseña actual. El panel muestra un recordatorio mientras no lo hayan registrado. No se asigna automáticamente el correo público de contacto a ningún administrador.
2. Para cambiar la clave desde el panel, en **Mi cuenta** se escribe la contraseña actual y dos veces la nueva. Se requieren al menos 12 caracteres; el servidor rechaza entradas que excedan los 72 bytes admitidos por el hash utilizado. No se envían contraseñas por correo.
3. Desde **¿Olvidaste tu contraseña?**, se solicita el enlace con el correo registrado. La respuesta es genérica para correos existentes y desconocidos. Los enlaces vencen en 20 minutos, son de un solo uso, se guardan como hash y se invalidan al pedir otro enlace o cambiar la clave o el correo.
4. Al restablecer la contraseña se cierran todas las sesiones anteriores y se vuelve al acceso normal. Cambiar la clave o el correo desde Mi cuenta cierra las otras sesiones y mantiene la sesión que confirmó su contraseña actual. También invalida los enlaces de recuperación pendientes. No se modifican las claves existentes al instalar esta mejora; las sesiones anteriores a la actualización deberán volver a ingresar una vez.
5. El primer administrador sigue creándose mediante `AMS_SETUP_TOKEN`, y ahora registra también su correo y su confirmación. Si un usuario anterior olvidó la clave antes de registrar un correo, necesitará la intervención del responsable del hosting para verificar su identidad y recuperar el acceso; el formulario público no permite apropiarse de una cuenta asignándole un correo nuevo.

**Configuración del correo:** copiar `config/security.example.php` como `config/security.local.php` y completar el `admin_url` y `mail_from` de producción. La URL debe apuntar exactamente a la carpeta `/admin` del dominio instalado y usar HTTPS; solo se permite HTTP para localhost en el entorno local. El remitente debe ser una dirección del dominio autorizada por el proveedor. Se admiten las variables `AMS_ADMIN_URL` y `AMS_MAIL_FROM`. El enlace no se construye con el encabezado Host de la petición ni con campos públicos editables del sitio.

La entrega usa `mail()` de PHP y depende del correo saliente del hosting. XAMPP no incluye entrega SMTP configurada. Antes de entregar al cliente, comprobar la recepción real de un enlace con su cuenta, incluyendo spam. Si el envío falla, no se muestra el enlace en pantalla ni se registra en los logs, y se retira el token generado. Cambiar la clave o el correo funciona desde Mi cuenta aunque la entrega de correo no esté disponible; la recuperación por enlace requiere poder recibir el mensaje.

Subir `admin/auth.php`, `admin/account.php`, `admin/forgot-password.php`, `admin/reset-password.php`, los archivos actualizados de acceso/configuración/panel/CSS y `database-auth.sql`. El inicio del panel agrega a `admin_users` las columnas `email` (única y opcional para usuarios anteriores) y `auth_version` y crea `admin_password_resets` y `admin_auth_limits` sin borrar usuarios. El usuario de MySQL debe poder crear tablas y agregar esas columnas e índice durante la actualización. Las tablas de autenticación no forman parte del historial de contenido ni se pueden restaurar desde él.

Se aplican límites persistentes por cuenta y dirección IP, CSRF, confirmación de contraseña y reautenticación para editar datos de acceso. Los límites de recuperación son 3 solicitudes por correo y 10 por IP cada 15 minutos. La página de restablecimiento retira el token de la URL tras validarlo y usa `Referrer-Policy: no-referrer`. Referencia del diseño: [OWASP Forgot Password Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Forgot_Password_Cheat_Sheet.html).

Pruebas: `C:\xampp\php\php.exe tests/auth-integration.php` crea y elimina una base temporal `ams_auth_test_*`, usa un servidor local en el puerto 18084 y un transporte de correo simulado para capturar los enlaces sin entrega externa. Comprueba migración de usuarios anteriores, cambios con contraseña actual, CSRF, neutralidad del mensaje, tokens cifrados por hash, vencimiento, reutilización, invalidación de sesiones, límites persistentes y fallo de envío. La recepción real en una casilla del cliente se comprueba por separado en el hosting.
