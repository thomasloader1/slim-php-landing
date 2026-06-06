# template-2mez-landing

Landing page tipo Linktree con panel de administración. Una sola URL que centraliza todos tus links, redes sociales y datos de contacto. Panel admin para gestionar todo sin tocar código.

---

## Índice

1. [Qué incluye](#qué-incluye)
2. [Stack técnico](#stack-técnico)
3. [Estructura del proyecto](#estructura-del-proyecto)
4. [Inicio rápido con Docker](#inicio-rápido-con-docker)
5. [Instalación en servidor (hosting/VPS)](#instalación-en-servidor-hostingvps)
6. [Configuración `.env`](#configuración-env)
7. [Wizard de instalación (`install.php`)](#wizard-de-instalación-installphp)
8. [Panel de administración](#panel-de-administración)
9. [SEO y posicionamiento](#seo-y-posicionamiento)
10. [Sistema de actualizaciones (`update.php`)](#sistema-de-actualizaciones-updatephp)
11. [Agregar migraciones a un nuevo commit](#agregar-migraciones-a-un-nuevo-commit)
12. [Checklist de producción](#checklist-de-producción)
13. [Arquitectura técnica](#arquitectura-técnica)

---

## Qué incluye

**Landing pública**
- Perfil con avatar o logo
- Links ilimitados (URL, redes sociales, WhatsApp, email, teléfono, custom)
- Biografía con formato enriquecido (negrita, listas, links)
- Integración con Google Maps (botón o mapa embebido)
- Diseño glassmorphism responsive con colores completamente configurables
- SEO completo: Open Graph, Twitter Cards, JSON-LD Schema.org, canonical, sitemap, robots.txt

**Panel de administración** (`/admin`)
- Login con protección brute-force
- CRUD de links con drag & drop para reordenar
- Editor de ajustes visuales (colores, logo, avatar, fondo)
- Sección SEO con preview en tiempo real del snippet de Google
- Estadísticas de clicks (Chart.js)
- Gestión de usuarios con roles (admin / editor)
- Sistema de actualizaciones automáticas con log

---

## Stack técnico

| Capa | Tecnología |
|---|---|
| Lenguaje | PHP 8.1 |
| Framework HTTP | Slim 4 |
| Templating | Blade (jenssegers/blade) |
| ORM / DB | Illuminate Database (Eloquent) |
| DI Container | PHP-DI 7 |
| Base de datos | MySQL 5.7+ / 8.0 |
| Frontend admin | Tailwind CSS (CDN), Font Awesome 6 |
| Rich text | Quill.js 1.3 |
| Reordenamiento | SortableJS 1.15 |
| Gráficos | Chart.js 4.4 |
| Servidor | Apache 2.4+ (con `mod_rewrite`) |
| Contenedor | Docker + Docker Compose (opcional) |

---

## Estructura del proyecto

```
template-2mez-landing/
├── docker/
│   └── Dockerfile               # Imagen PHP 8.1-Apache
├── docker-compose.yml           # Stack Docker completo
├── db/
│   ├── init/
│   │   ├── 01_schema.sql        # Tablas: users, links, settings
│   │   └── 02_seed.sql          # Usuario admin + settings por defecto
│   ├── modules/
│   │   └── landing_base/
│   │       ├── module.json      # Metadatos del módulo base
│   │       └── install.sql      # SQL del módulo (click stats, etc.)
│   └── updates/                 # Migraciones por commit/versión
│       └── YYYYMMDD_HHMM_vX_X_X_descripcion.sql
└── landing/                     # Aplicación PHP
    ├── VERSION                  # Versión actual del código (ej: 1.1.0)
    ├── .env.example             # Plantilla de configuración
    ├── .env                     # Config real (NO commitear)
    ├── composer.json
    ├── bootstrap/
    │   └── app.php              # Inicialización de Slim + DI + Eloquent
    ├── public/                  # Document root del servidor
    │   ├── index.php            # Entry point
    │   ├── install.php          # Wizard de instalación inicial
    │   ├── update.php           # Wizard de actualizaciones
    │   ├── .htaccess            # Rewrite rules para Slim
    │   └── uploads/             # Imágenes subidas (avatar, logo, fondo)
    ├── resources/
    │   └── views/
    │       ├── index.blade.php  # Landing pública
    │       └── admin/           # Vistas del panel admin
    ├── src/
    │   ├── Controllers/
    │   │   ├── Admin/           # Auth, Dashboard, Links, Settings, Users
    │   │   ├── Api/             # LinkApiController (JSON)
    │   │   └── Front/           # LandingController, SitemapController
    │   ├── Entities/
    │   │   └── LinkEntity.php   # Lógica de tipos de link (iconos, URLs)
    │   ├── Install/
    │   │   ├── DbInstaller.php  # Conexión PDO, ejecutar SQL
    │   │   ├── InstallLock.php  # Archivo .installed
    │   │   ├── ModuleRegistry.php # Descubrimiento de módulos
    │   │   └── UpdateManager.php  # Migraciones + detección de commit
    │   ├── Middleware/
    │   │   ├── AuthMiddleware.php  # Proteger rutas admin
    │   │   └── RoleMiddleware.php  # Proteger rutas solo-admin
    │   ├── Models/
    │   │   ├── Link.php
    │   │   └── User.php
    │   ├── Services/
    │   │   └── AuthService.php  # Login, sesión, CSRF, brute-force
    │   ├── helpers.php          # url(), request_is()
    │   └── routes.php           # Definición de todas las rutas
    └── storage/
        ├── cache/               # Caché de Blade compilado
        └── logs/
```

---

## Inicio rápido con Docker

Opción recomendada para desarrollo local o VPS con Docker instalado.

### Requisitos

- Docker 24+
- Docker Compose v2+
- Una instancia MySQL accesible (puede ser otro contenedor)

### 1. Clonar el repositorio

```bash
git clone <repo-url> template-2mez-landing
cd template-2mez-landing
```

### 2. Ajustar `docker-compose.yml`

El compose está pensado para conectarse a una red Docker externa donde ya corre MySQL. Editá las variables según tu entorno:

```yaml
# docker-compose.yml
services:
  landing:
    environment:
      DB_HOST: mysql-server      # nombre del contenedor MySQL en la red
      DB_PORT: "3306"
      DB_NAME: project_landing
      DB_USER: root
      DB_PASS: tu_password
      APP_BASE_PATH: ""          # vacío si está en la raíz del dominio
    ports:
      - "8001:80"                # acceder en localhost:8001
    networks:
      - phpmyadmin_phpmyadminsql-network  # red donde está tu MySQL

networks:
  phpmyadmin_phpmyadminsql-network:
    external: true
```

> Si no tenés una red Docker existente con MySQL, creá un servicio `mysql:` en el mismo compose o cambiá la red.

### 3. Levantar el contenedor

```bash
docker compose up -d --build
```

### 4. Crear la base de datos

Conectate a tu instancia MySQL y ejecutá:

```sql
CREATE DATABASE project_landing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 5. Instalar la app

Abrí el wizard de instalación en el navegador:

```
http://localhost:8001/install.php
```

Seguí los pasos del wizard. Al finalizar, el archivo `.env` se genera automáticamente y la base de datos queda lista.

---

## Instalación en servidor (hosting/VPS)

### Requisitos del servidor

- PHP 8.1+ con extensiones: `pdo`, `pdo_mysql`, `mbstring`, `json`, `fileinfo`
- Apache 2.4+ con `mod_rewrite` habilitado y `AllowOverride All`
- MySQL 5.7+ o MariaDB 10.3+
- Acceso a la carpeta donde se aloja la app (para escribir `.env` y `uploads/`)

### 1. Subir los archivos

Subí el contenido de la carpeta `landing/` al servidor. El **document root** del dominio/subdominio debe apuntar a `landing/public/`.

```
Dominio: misitio.com
Document root: /home/usuario/public_html/landing/public/
```

> Si subís todo en la raíz de `public_html/`, asegurate de que el document root apunte a `public/` y no a la carpeta raíz.

### 2. Instalar dependencias PHP

En el servidor, dentro de la carpeta `landing/`:

```bash
php composer.phar install --no-dev --optimize-autoloader
```

> Si no tenés Composer global, usá el `composer.phar` incluido en el repo.

### 3. Limpiar archivos innecesarios

Eliminá archivos que no deben estar en producción (tests, Docker, config de PHPUnit):

```bash
php cleanup.php
```

> Este script verifica que `APP_ENV=production` antes de ejecutarse. Si estás en desarrollo, aborta sin eliminar nada.
>
> Para agregar más rutas a limpiar en el futuro, editá el array `$paths` en `cleanup.php`.

### 4. Permisos de escritura

```bash
chmod 755 landing/public/uploads/
chmod 755 landing/storage/
chmod 755 landing/storage/cache/
```

### 5. Crear la base de datos

Desde phpMyAdmin o la consola MySQL:

```sql
CREATE DATABASE mi_landing_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 6. Abrir el wizard de instalación

Navegá a:

```
https://misitio.com/install.php
```

El wizard te pedirá los datos de conexión a MySQL, generará el `.env` y ejecutará el schema + seed automáticamente.

### 7. Verificar que el rewrite funciona

Si al abrir la landing ves un error 404, verificá que `mod_rewrite` está activo y que el `.htaccess` de `public/` tiene efecto. En cPanel suele estar habilitado por defecto.

---

## Configuración `.env`

El archivo `.env` se genera automáticamente por el wizard. Podés editarlo manualmente si es necesario:

```env
# Conexión a MySQL
DB_HOST=localhost
DB_PORT=3306
DB_NAME=mi_landing_db
DB_USER=mi_usuario
DB_PASS=mi_password

# Entorno: production | development
APP_ENV=production

# Ruta base si la app NO está en la raíz del dominio
# Ej: si la app está en misitio.com/landing → APP_BASE_PATH=/landing
# Dejar vacío si está en la raíz del dominio
APP_BASE_PATH=
```

> **Nunca commitees `.env` al repositorio.** Está incluido en `.gitignore`.

---

## Wizard de instalación (`install.php`)

Accedé a `/install.php` **solo la primera vez**. El wizard tiene 5 pasos:

| Paso | Descripción |
|---|---|
| 1 – Bienvenida | Pantalla inicial |
| 2 – Base de datos | Ingresás host, puerto, nombre de DB, usuario, contraseña y entorno |
| 3 – Confirmar | Muestra un resumen de la conexión verificada |
| 4 – Módulos | Seleccionás los módulos a instalar (el base es requerido) |
| 5 – Completado | Log de ejecución SQL + acceso al admin |

Al completar el paso 5:
- Se genera `landing/.env` con tus credenciales
- Se crean las tablas (`users`, `links`, `settings`)
- Se inserta el usuario admin y los settings por defecto
- Se crea el archivo `landing/.installed` que **bloquea el wizard** para futuras visitas

### Credenciales por defecto

| Campo | Valor |
|---|---|
| Email | `admin@mi-landing.com` |
| Contraseña | `password` |

> **Cambiá la contraseña inmediatamente** desde el panel admin → Usuarios.

### Reinstalar (solo en emergencia)

Para forzar el wizard nuevamente, eliminá el archivo `landing/.installed` y accedé a `/install.php?force=1`. Esto **no borra** la base de datos existente (los SQL usan `INSERT IGNORE` / `ON DUPLICATE KEY`).

---

## Panel de administración

Accedé en `/admin/login` con tus credenciales.

### Navegación

| Sección | URL | Descripción |
|---|---|---|
| Dashboard | `/admin` | Estadísticas de clicks por link |
| Links | `/admin/links` | CRUD de links con drag & drop |
| Ajustes | `/admin/settings` | Apariencia, SEO, ubicación |
| Usuarios | `/admin/users` | Solo rol admin |

### Tipos de link soportados

| Tipo | Comportamiento |
|---|---|
| `url` | Link genérico con ícono Font Awesome |
| `social` | Red social (se usa para JSON-LD `sameAs`) |
| `whatsapp` | Abre WhatsApp con número precargado |
| `email` | Abre cliente de correo |
| `phone` | Llamada telefónica (se usa para Schema `telephone`) |
| `custom` | HTML de ícono personalizado |

### Roles de usuario

| Rol | Permisos |
|---|---|
| `admin` | Todo: settings, links, usuarios, actualizaciones |
| `editor` | Solo links y dashboard |

---

## SEO y posicionamiento

La sección **SEO & Buscadores** en `/admin/settings` controla todo el posicionamiento. Configurala antes de publicar.

### Campos disponibles

**Básico**

| Campo | Descripción | Consejo |
|---|---|---|
| Meta Descripción | Texto que aparece en Google debajo del título | 120–160 caracteres. Es lo más importante para el CTR |
| Keywords | Palabras clave separadas por coma | Máximo 10 |

**Social & Open Graph** *(cómo se ve al compartir en WhatsApp/Facebook/Twitter)*

| Campo | Descripción |
|---|---|
| URL del sitio | `https://tusitio.com` — necesario para canonical y sitemap |
| Imagen para redes | 1200×630 px recomendado. Si está vacío, usa el avatar |
| Idioma / Locale | `es_AR`, `es_MX`, `es_ES`, etc. |
| Twitter handle | Tu `@usuario` sin el @ |

**Schema.org** *(datos estructurados para Google)*

| Tipo | Cuándo usarlo |
|---|---|
| `Person` | Profesional, freelancer, influencer |
| `LocalBusiness` | Negocio con dirección física: cerrajería, restaurant, etc. |
| `Organization` | Marca, empresa, ONG |

Para `LocalBusiness` también podés completar:
- **Tipo de negocio**: subtipo según [schema.org/LocalBusiness](https://schema.org/LocalBusiness) en inglés (ej: `Locksmith`, `Restaurant`, `HairSalon`)
- **Dirección**: dirección física del negocio

> Los teléfonos y emails configurados como links de tipo `phone` y `email` se incluyen automáticamente en el JSON-LD.

**Avanzado**
- **Ocultar de buscadores (noindex)**: activalo en entornos de prueba, desactivalo en producción.

### Rutas SEO generadas automáticamente

| URL | Descripción |
|---|---|
| `/sitemap.xml` | Sitemap XML con la URL canónica del sitio |
| `/robots.txt` | Reglas para crawlers (bloquea `/admin/` y `/api/`) |

### Guía de keywords por tipo de negocio

**Negocio local**
```
[servicio] [ciudad], [servicio] [barrio], [servicio] urgente, [servicio] 24hs
```
*Ejemplo cerrajería:* `cerrajero Buenos Aires, cerrajería urgente, abrir puertas Palermo`

**Profesional / Freelancer**
```
[profesión] [ciudad], [profesión] freelance, [especialidad]
```
*Ejemplo fotógrafo:* `fotógrafo de bodas Buenos Aires, fotografía corporativa CABA`

**Marca / Emprendimiento**
```
nombre de marca, [categoría] [diferenciador], comprar [producto]
```
*Ejemplo tienda:* `Tienda Sol, ropa sustentable Argentina, indumentaria ecológica`

**Reglas de oro:**
1. Incluí siempre la ciudad si es negocio local
2. Usá las palabras que tus clientes tipean en Google, no la jerga técnica
3. La meta description impacta más en el CTR que las keywords
4. Completá la URL del sitio para habilitar canonical tag y sitemap

---

## Sistema de actualizaciones (`update.php`)

Cuando desplegás una nueva versión del código que incluye cambios en la base de datos, el wizard `/update.php` aplica las migraciones SQL pendientes de forma segura.

### Cómo funciona

1. **Versión del código** → se detecta en este orden:
   - `git describe --tags --always` (requiere git en el servidor)
   - `git rev-parse --short HEAD`
   - Archivo `landing/VERSION`

2. **Versión instalada** → guardada en la tabla `settings` bajo la clave `app_installed_version`

3. **Migraciones pendientes** → archivos `.sql` en `db/updates/` que aún no se aplicaron (rastreados por nombre de archivo en `app_applied_migrations`)

### Proceso de actualización

```
1. Hacer git pull en el servidor (o subir los archivos)
2. Abrir https://tusitio.com/update.php
3. Loguearse con cuenta admin
4. Ver la lista de migraciones pendientes
5. Confirmar y aplicar
6. El wizard muestra el log SQL por migración
```

### Convención de nombres para migraciones

```
YYYYMMDD_HHMM_{version}_{descripcion}.sql

Ejemplos:
  20260316_1100_1_1_0_seo_settings.sql
  20260401_0900_1_2_0_click_stats.sql
  20260515_1430_2_0_0_new_link_types.sql
```

El orden de aplicación es **alfabético**, por eso el prefijo de fecha garantiza el orden correcto.

### Seguridad de `update.php`

- Solo accesible si la app está instalada (existe `landing/.installed`)
- Requiere autenticación con usuario de rol `admin`
- Token CSRF en cada formulario
- Marcado como `noindex, nofollow` para que Google no lo indexe

---

## Agregar migraciones a un nuevo commit

Cuando hacés cambios que requieren modificaciones en la base de datos:

### Paso 1 — Crear el archivo SQL

```bash
# Nombre: YYYYMMDD_HHMM_{version}_{descripcion}.sql
touch db/updates/20260401_0900_1_2_0_nueva_feature.sql
```

Contenido del archivo:

```sql
-- =============================================================
--  Migración: 1.2.0 – Descripción de lo que hace
--  Fecha: 2026-04-01
--  Descripción: Agrega tal o cual columna/tabla/settings
--  Commit: abc1234
-- =============================================================

-- Tu SQL aquí. Usá siempre IF NOT EXISTS / ON DUPLICATE KEY
-- para que la migración sea idempotente.
ALTER TABLE links ADD COLUMN IF NOT EXISTS `clicks` INT UNSIGNED NOT NULL DEFAULT 0;
```

> Escribí siempre SQL idempotente (`IF NOT EXISTS`, `ON DUPLICATE KEY UPDATE`) para que sea seguro re-ejecutar si algo falla.

### Paso 2 — Actualizar `landing/VERSION`

```
1.2.0
```

### Paso 3 — Commitear y deployar

```bash
git add db/updates/20260401_0900_1_2_0_nueva_feature.sql landing/VERSION
git commit -m "feat: nueva feature + migración DB v1.2.0"
git push
```

### Paso 4 — Aplicar en producción

```
https://tusitio.com/update.php
```

---

## Checklist de producción

Antes de publicar, verificá cada punto:

### Seguridad

- [ ] Contraseña del admin cambiada (default: `password`)
- [ ] `APP_ENV=production` en `.env`
- [ ] `seo_noindex` desactivado en `/admin/settings` → SEO
- [ ] Archivo `.env` NO expuesto al público (el document root es `public/`, no la raíz)
- [ ] Carpeta `landing/src/` NO accesible desde el navegador
- [ ] `install.php` bloqueado (el archivo `landing/.installed` debe existir)

### SEO

- [ ] URL del sitio configurada en `/admin/settings` → SEO → Social & Open Graph
- [ ] Meta descripción entre 120 y 160 caracteres
- [ ] Al menos 5 keywords relevantes cargadas
- [ ] Imagen Open Graph subida (1200×630 px)
- [ ] Tipo de Schema seleccionado (Person / LocalBusiness / Organization)
- [ ] Verificar `/sitemap.xml` devuelve XML válido
- [ ] Verificar `/robots.txt` devuelve el texto correcto
- [ ] Probar preview en [Facebook Sharing Debugger](https://developers.facebook.com/tools/debug/)
- [ ] Validar JSON-LD en [Google Rich Results Test](https://search.google.com/test/rich-results)

### Contenido

- [ ] Avatar o logo subido
- [ ] Título y subtítulo del perfil completados
- [ ] Al menos 3–5 links activos configurados
- [ ] Colores de acento y fondo personalizados
- [ ] Biografía / descripción redactada

### Técnico

- [ ] `mod_rewrite` habilitado en Apache
- [ ] PHP 8.1+ con extensiones `pdo`, `pdo_mysql`, `mbstring`
- [ ] Permisos de escritura en `uploads/` y `storage/`
- [ ] HTTPS configurado (certificado SSL activo)
- [ ] Dependencias instaladas: `php composer.phar install --no-dev`

---

## Arquitectura técnica

### Flujo de una request

```
Navegador
    │
    ▼
public/.htaccess       → rewrite a index.php
    │
    ▼
public/index.php       → carga bootstrap/app.php
    │
    ▼
bootstrap/app.php      → inicializa Slim + PHP-DI + Eloquent + Blade
    │
    ▼
src/routes.php         → match de ruta → Controller
    │
    ├── Middleware/AuthMiddleware.php     (rutas /admin)
    ├── Middleware/RoleMiddleware.php     (rutas /admin/users)
    │
    ▼
Controller             → consulta DB vía Eloquent/Capsule
    │
    ▼
Blade template         → renderiza HTML
    │
    ▼
Response               → devuelve al navegador
```

### Tablas de la base de datos

**`users`**

| Columna | Tipo | Descripción |
|---|---|---|
| id | INT UNSIGNED | PK autoincrement |
| name | VARCHAR(100) | Nombre del usuario |
| email | VARCHAR(150) | Email único |
| password_hash | VARCHAR(255) | bcrypt cost 12 |
| role | ENUM | `admin` o `editor` |
| active | TINYINT | 1 = activo |
| email_verified | TINYINT | 1 = verificado |

**`links`**

| Columna | Tipo | Descripción |
|---|---|---|
| id | INT UNSIGNED | PK autoincrement |
| title | VARCHAR(150) | Texto visible del link |
| url | VARCHAR(500) | URL destino |
| type | VARCHAR(30) | url, social, whatsapp, email, phone, custom |
| icon | VARCHAR(100) | Clase Font Awesome (ej: `fa-solid fa-link`) |
| color | VARCHAR(20) | Color hex del ícono |
| sort_order | INT UNSIGNED | Orden de aparición |
| active | TINYINT | 1 = visible en la landing |

**`settings`**

| Clave | Descripción |
|---|---|
| `site_name` | Nombre del sitio |
| `landing_title` | Título principal del perfil |
| `landing_subtitle` | Subtítulo / profesión |
| `landing_bio` | Biografía (HTML enriquecido) |
| `landing_accent_color` | Color de acento (hex) |
| `landing_bg_color` | Color de fondo (hex) |
| `landing_text_color` | Color de texto (hex) |
| `landing_avatar_url` | URL del avatar |
| `landing_logo_url` | URL del logo (reemplaza avatar) |
| `landing_bg_image_url` | URL de imagen de fondo |
| `landing_bg_overlay` | Color del overlay sobre la imagen |
| `landing_bg_overlay_opacity` | Opacidad del overlay (0–100) |
| `landing_accent_force` | Forzar color de acento en todos los links |
| `landing_maps_url` | URL de Google Maps |
| `landing_maps_mode` | `none`, `button`, `embed` |
| `seo_description` | Meta description |
| `seo_keywords` | Keywords (separadas por coma) |
| `seo_author` | Meta author |
| `seo_site_url` | URL canónica del sitio |
| `seo_og_image` | Imagen Open Graph (1200×630 px) |
| `seo_locale` | Locale para OG (ej: `es_AR`) |
| `seo_twitter_handle` | Usuario de Twitter sin @ |
| `seo_schema_type` | `Person`, `LocalBusiness`, `Organization` |
| `seo_business_type` | Subtipo schema.org (ej: `Locksmith`) |
| `seo_address` | Dirección física |
| `seo_noindex` | `1` = noindex (staging), `0` = indexar |
| `app_installed_version` | Versión instalada (gestionada por update.php) |
| `app_applied_migrations` | JSON con migraciones aplicadas |

### Rutas disponibles

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/` | Landing pública |
| GET | `/sitemap.xml` | Sitemap XML dinámico |
| GET | `/robots.txt` | Robots.txt dinámico |
| GET | `/admin/login` | Formulario de login |
| POST | `/admin/login` | Procesar login |
| POST | `/admin/logout` | Cerrar sesión |
| GET | `/admin` | Dashboard con estadísticas |
| GET/POST | `/admin/links` | Listado de links |
| GET/POST | `/admin/links/create` | Crear link |
| GET/POST | `/admin/links/edit/{id}` | Editar link |
| POST | `/admin/links/delete/{id}` | Eliminar link |
| GET/POST | `/admin/settings` | Ajustes del sitio |
| GET/POST | `/admin/users` | Gestión de usuarios (solo admin) |
| GET | `/api/links` | JSON con todos los links activos |
| GET | `/api/links/{id}` | JSON de un link específico |

---

*Documentación generada el 2026-03-16 — template-2mez-landing v1.1.0*
