# Instalación del panel AMS Legal Solutions

Este panel utiliza el contrato de `../INTEGRACION-PANEL.md`. El sitio público ya
está integrado: no hay que reconstruirlo ni modificar sus plantillas para instalar
el panel. La conexión y la lectura del contenido pertenecen al sitio público.

## Archivos exactos que se deben copiar

Copiar únicamente estos siete archivos desde la carpeta `admin/` del proyecto del
panel a `public_html/admin/`, conservando sus nombres:

| Origen en este proyecto | Destino en Hostinger |
| --- | --- |
| `admin/index.php` | `public_html/admin/index.php` |
| `admin/bootstrap.php` | `public_html/admin/bootstrap.php` |
| `admin/login.php` | `public_html/admin/login.php` |
| `admin/logout.php` | `public_html/admin/logout.php` |
| `admin/save.php` | `public_html/admin/save.php` |
| `admin/setup.php` | `public_html/admin/setup.php` |
| `admin/admin.css` | `public_html/admin/admin.css` |

No subir la carpeta de trabajo completa dentro de `admin`: eso produciría
`public_html/admin/admin/`. No subir `tests/`, `INSTALACION.md`, `.gitignore` ni
`database.sql` al directorio público. Se retiraron del paquete del panel las copias
antiguas de los includes compartidos y el ejemplo de configuración independiente.

## Archivos públicos existentes que usa el panel

La estructura instalada debe ser:

```text
public_html/
  index.php, servicios.php, equipo.php, sobre_la_firma.php, contacto.php
  css/, img/
  config/
    database.local.php           existente, privado; no reemplazar
    .htaccess                    existente; conservar
  includes/
    database.php                 conector central existente; no reemplazar
    content.php                  funciones públicas existentes; no reemplazar
    header.php, footer.php, ...   existentes; no modificar
  uploads/
    .htaccess                    existente; conservar
    branding/
    team/
  admin/
    index.php
    bootstrap.php
    login.php
    logout.php
    save.php
    setup.php
    admin.css
```

Desde `public_html/admin/bootstrap.php` se cargan exactamente:

```php
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/content.php';
```

El panel usa el `$pdo` creado por ese conector; no abre una segunda conexión y no
utiliza `ams-config.php`. Las credenciales continúan administrándose únicamente
en el `config/database.local.php` del sitio y/o mediante las variables de entorno
que acepta el conector público: `AMS_ENV`, `AMS_DB_HOST`, `AMS_DB_PORT`,
`AMS_DB_NAME`, `AMS_DB_USER`, `AMS_DB_PASS`. No es necesario modificar esa
configuración para conectar el panel si el sitio ya funciona con MySQL.

Requisitos: PHP 8.0 o superior, PDO MySQL, Fileinfo, sesiones PHP y HTTPS.

## Base de datos y acceso

Conservar la base MySQL existente y todos sus registros. `bootstrap.php` mantiene
la creación idempotente de `admin_users` y `site_settings` con
`CREATE TABLE IF NOT EXISTS`; nunca elimina tablas ni usuarios existentes.
Si las tablas ya están creadas, no hace falta importar datos ni crear otra base.
El archivo SQL del panel queda como referencia para instalaciones sin tablas;
si se usa, seleccionar primero la base asignada en phpMyAdmin y ejecutar solo
los bloques `CREATE TABLE IF NOT EXISTS`.

Los usuarios existentes siguen entrando por `/admin/login.php` con sus accesos
actuales. Se conservan `password_hash`/`password_verify`, regeneración de sesión,
protección CSRF, límite de intentos y cookies HttpOnly/SameSite.

Solo si todavía no existe administrador, definir temporalmente la variable de
entorno `AMS_SETUP_TOKEN` con un valor privado aleatorio de al menos 32 caracteres,
abrir `/admin/setup.php?token=VALOR_PRIVADO` y completar el formulario. Después,
retirar la variable de entorno. Este token protege únicamente la creación inicial;
no configura la base de datos ni necesita otro archivo de configuración. Sin token,
la ruta de configuración devuelve 404. No crear otro administrador si ya existe.

## Contrato de contenido

El formulario de Datos generales incluye `site.url`, `site.footer_description` y
la carga de archivo para `site.favicon`, además de los controles existentes.
`site.url` acepta un dominio completo HTTP/HTTPS sin rutas, consultas ni fragmentos.
El favicon admite PNG, JPG o WebP; se recomienda PNG cuadrado.

Se conservan las claves literales con puntos de `INTEGRACION-PANEL.md`:

- Identidad: `site.name`, `site.location`, `site.logo`, `site.favicon`,
  `site.office_image`, `site.footer_description`, `site.url`.
- Contacto: `contact.address`, `contact.phone`, `contact.email`, `contact.hours`,
  `contact.heading`, `contact.intro`.
- Inicio y estadísticas: `home.hero_title`, `home.hero_description`,
  `home.services_title`, `home.services_text`, `home.about_title`, `home.about_p1`,
  `home.about_p2`, `home.team_title`, `stats.years`, `stats.cases`, `stats.lawyers`,
  `stats.satisfaction`.
- Firma: `firm.hero_title`, `firm.hero_text`, `firm.history_title`, `firm.history_p1`,
  `firm.history_p2`, `firm.quote`, `firm.quote_author`, `firm.values_title`,
  `firm.value_1_name`/`firm.value_1_description` y equivalentes para 2, 3 y 4.
- Introducciones: `services.intro_title`, `services.intro_text`, `team.intro_title`,
  `team.intro_text`.
- SEO: `seo.home_title`/`seo.home_description` y equivalentes para `services`,
  `firm`, `team` y `contact`.
- Listas: `content.services` y `content.team`, siempre JSON de arrays ordenados.

Los servicios contienen `slug`, `name`, `summary`, `description`, `services` y
`steps`; los dos últimos son arrays de texto. Los abogados contienen `slug`, `name`,
`role`, `area`, `bio` e `image`. Los slugs son únicos dentro de su lista y usan
letras minúsculas a-z, números y guiones entre palabras. El panel utiliza
`site_services()` y `site_team()` del sitio para leer y validar esas colecciones.
Se respeta `[]` como lista vacía al leer y guardar; los marcadores de formulario
permiten distinguir una lista vacía de un envío incompleto. La interfaz actual
edita los registros existentes; no añade ni elimina registros desde botones.

Los textos se guardan en UTF-8 como texto plano, sin escapar previamente como HTML.
Los campos de texto pueden guardarse vacíos; los números se validan como enteros
no negativos y el porcentaje no puede superar 100. El panel solo hace INSERT/UPDATE
de las claves de la sección guardada: no borra claves desconocidas ni limpia toda
la tabla `site_settings`.

## Imágenes y guardado

Las subidas se escriben físicamente en `dirname(__DIR__) . '/uploads'`: una vez
instalado el panel, esa ruta es `public_html/uploads`, nunca `public_html/admin/uploads`.
MySQL recibe únicamente rutas relativas como:

```text
uploads/branding/identificador-aleatorio.png
uploads/branding/identificador-aleatorio.webp
uploads/team/identificador-aleatorio.jpg
```

Se mantienen nombres aleatorios para evitar colisiones y caché de imágenes antiguas.
No se almacenan binarios, rutas de Windows, `public_html/...` ni rutas temporales en
MySQL. Las fotografías existentes con URL absoluta HTTP/HTTPS también se conservan;
las vistas previas no les agregan `../`. Las subidas nuevas siempre usan `uploads/`.

Si existen imágenes reales dentro de `uploads/` de esta copia de trabajo, copiar
solo esos archivos a `public_html/uploads/`, conservando nombres y subcarpetas sin
borrar imágenes anteriores ni reemplazar el `.htaccess` público. No subir `uploads/`
dentro de `/admin`. PHP necesita permiso de escritura en las carpetas públicas de
uploads; mantener propietario correcto y permisos habituales 755, sin usar 777.

Se conservan comprobación de carga HTTP, MIME, contenido real de imagen y límite
máximo de 5 MB por archivo. El guardado usa transacciones: un error revierte los
cambios de esa operación y retira los archivos nuevos de una subida parcial.

Los cambios se ven en la siguiente petición pública, al recargar, sin volver a
publicar archivos PHP. Si existe caché de HTML de Hostinger/LiteSpeed/CDN, configurar
su invalidación o excluir estas páginas dinámicas. Mantener `/admin/*` fuera de caché.
No se requiere modificar el encabezado público ni instalar `site-head.php`: el sitio
ya consume favicon, canonical y descripción del pie.

## Pruebas

Desde la carpeta de trabajo del panel:

```powershell
C:\xampp\php\php.exe tests/integration.php
C:\xampp\php\php.exe ..\tests\content-integration.php
```

La primera prueba ejecuta peticiones HTTP al panel en una instalación temporal
separada con un adaptador SQLite y carga el `content.php` público original por
referencia, sin copiarlo ni modificarlo. Comprueba acceso, CSRF, las seis secciones,
claves nuevas, conservación de claves desconocidas, JSON, listas vacías, imágenes,
URL absolutas y reversión de cambios. La segunda prueba pertenece al sitio público
y verifica sus cinco páginas en cuatro escenarios (20 renders). Las pruebas no
usan ni modifican los datos MySQL reales. `tests/.runtime/` no se despliega.

Después de desplegar en Hostinger, comprobar con MySQL activo un texto, teléfono,
servicio, abogado, fotografía, logo y favicon. Revisar título, descripción SEO y
canonical en el HTML público. No se ha desplegado desde esta tarea ni se ha
verificado conectividad real con MySQL de Hostinger.