# AMS Legal Solutions

La integración del sitio público con el panel y las instrucciones para `public_html/admin` están en [INTEGRACION-PANEL.md](INTEGRACION-PANEL.md). La conexión compartida se configura en `config/database.local.php` (privado; ver `config/database.example.php`).

El panel actualizado incluye vista previa sin publicar, aviso de cambios pendientes, contacto/redes/botones editables, imágenes con previsualización, bandeja de consultas e historial con restauración. Las tablas nuevas se declaran en `database-editor.sql`. Consultar la guía de integración para despliegue y pruebas.

La opción **Mi cuenta y contraseña** permite registrar el correo de recuperación y cambiar la clave. El acceso incluye **¿Olvidaste tu contraseña?** con enlace temporal de un solo uso. La configuración del remitente y de la URL del panel se documenta en `config/security.example.php`; requiere correo saliente operativo en el hosting. Las tablas de recuperación están en `database-auth.sql`.

## Uso local con XAMPP

1. Inicia Apache y MySQL desde XAMPP.
2. Importa `database.sql` en phpMyAdmin para crear la base `ams_legal`.
3. Abre `http://localhost/ams-legal-solutions/`.

## Panel administrativo

El panel permite editar datos generales, contacto, portada, firma, áreas legales, abogados, imágenes y SEO.

1. Abre el enlace privado de configuración inicial indicado al entregar el proyecto.
2. Crea un usuario y una contraseña de al menos 12 caracteres.
3. Después ingresa desde `/admin/login.php`.

Las tablas `admin_users` y `site_settings` se crean automáticamente. También están declaradas en `database.sql` para instalaciones manuales.

## Despliegue en Hostinger

- Sube todo el proyecto dentro de `public_html`.
- Verifica las credenciales de producción en `config/database.local.php` y sube ese archivo privado al hosting.
- Confirma que PHP tenga permisos de escritura sobre `uploads/`.
- Crea inmediatamente el primer administrador usando el enlace privado.
- Elimina o cambia el token de configuración después de crear el administrador si el archivo se distribuye a terceros.
