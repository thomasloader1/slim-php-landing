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
7. [Ejecutar schema SQL](#ejecutar-schema-sql)
8. [Panel de administración](#panel-de-administración)
   - [Multisitio, servidores y recuperación](#multisitio-servidores-y-recuperación)
9. [SEO y posicionamiento](#seo-y-posicionamiento)
10. [Migraciones SQL](#migraciones-sql)
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
| Lenguaje | PHP 8.3 |
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
│   └── Dockerfile               # Imagen PHP 8.3-Apache
├── docker-compose.yml           # Stack Docker completo
└── landing/                     # Aplicación PHP
    ├── VERSION                  # Versión actual del código (ej: 1.1.0)
    ├── .env.example             # Plantilla de configuración
    ├── .env                     # Config real (NO commitear)
    ├── composer.json
    ├── bootstrap/
    │   └── app.php              # Inicialización de Slim + DI + Eloquent
    ├── public/                  # Document root del servidor
    │   ├── index.php            # Entry point
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
    │   ├── Traits/              # AdminViewTrait, ProcessesImages, ReordersEntities, etc.
    │   ├── Middleware/
    │   │   ├── AuthMiddleware.php  # Proteger rutas admin
    │   │   ├── CsrfMiddleware.php  # CSRF en formularios admin
    │   │   └── RoleMiddleware.php  # Proteger rutas solo-admin
    │   ├── Models/
    │   │   ├── Link.php
    │   │   ├── User.php
    │   │   ├── Location.php
    │   │   ├── MenuSection.php
    │   │   ├── MenuItem.php
    │   │   ├── FaqItem.php
    │   │   └── Site.php
    │   ├── Services/
    │   │   └── AuthService.php  # Login, sesión, CSRF, brute-force
    │   ├── Handlers/
    │   │   └── DetailedErrorHandler.php  # Manejador de errores 500
    │   ├── helpers.php          # url(), request_is()
    │   └── routes.php           # Definición de todas las rutas
    ├── db/                      # Ver "Base de datos"
    │   ├── install.sql          # ÚNICO script de base de datos (crea o actualiza todo)
    │   ├── scripts/             # cron_check_expiry.php, rotate_tenant_key.php
    ├── cleanup.php              # Script de limpieza para producción
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

El compose ya no define un servicio `mysql` propio. El servicio `landing` se conecta al contenedor MySQL compartido `phpmyadmin-mysql` (de otro proyecto compose) a través de la red externa `phpmyadmin_phpmyadminsql-network`. Editá las variables según tu entorno:

```yaml
# docker-compose.yml
services:
  landing:
    environment:
      DB_HOST: phpmyadmin-mysql   # contenedor MySQL compartido (phpmyadmin-mysql)
      DB_PORT: "3306"
      DB_NAME: project_landing
      DB_USER: landing_user
      DB_PASS: landing_pass
      APP_BASE_PATH: ""           # vacío si está en la raíz del dominio
    ports:
      - "8001:80"                 # acceder en localhost:8001
    networks:
      - phpmyadmin_phpmyadminsql-network

networks:
  phpmyadmin_phpmyadminsql-network:
    external: true
```

Las credenciales `DB_NAME`, `DB_USER` y `DB_PASS` se toman de `.env` (valores por defecto `project_landing`, `landing_user`, `landing_pass`). Asegurate de que coincidan con las credenciales del contenedor `phpmyadmin-mysql` y de que el contenedor `landing` se una a la red externa `phpmyadmin_phpmyadminsql-network` donde reside `phpmyadmin-mysql`.

### 3. Levantar el contenedor

```bash
docker compose up -d --build
```

### 4. Crear la base de datos

Conectate a tu instancia MySQL y ejecutá:

```sql
CREATE DATABASE project_landing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 5. Crear las tablas y el usuario inicial

```bash
docker compose exec landing php bin/console migrate
```

Entrá a `/admin` con **`admin@gotogo.com.ar` / `password`** (queda como root) y cambiá la
contraseña en Usuarios. Si preferís otro usuario: `php bin/console admin:create tu@email.com`.

### 6. Configurar `.env`

Copiá `.env.example` como `.env` y editá los datos de conexión:

```bash
cp .env.example .env
# Editá DB_HOST, DB_NAME, DB_USER, DB_PASS
```

---

## Instalación en servidor (hosting/VPS)

### Requisitos del servidor

- PHP 8.3+ con extensiones: `pdo`, `pdo_mysql`, `mbstring`, `json`, `fileinfo`
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

> Si no tenés Composer global, descargá `composer.phar` (https://getcomposer.org/download/) — no se versiona (está en `.gitignore`).

### 3. Limpiar archivos innecesarios

Eliminá archivos que no deben estar en producción (tests, Docker, db/, config de PHPUnit):

```bash
php cleanup.php
```

> Este script verifica que `APP_ENV=production` antes de ejecutarse. Si estás en desarrollo, aborta sin eliminar nada.
>
> Para eliminar `db/` primero valida conexión MySQL + existencia de todas las tablas. Si la BD no está operativa, omite `db/` y avisa.
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

### 6. Crear las tablas y el usuario root

Configurá el `.env` a mano (usá `.env.example` como base) y por SSH:

```bash
php bin/console migrate
```

Entrá a `/admin` con **`admin@gotogo.com.ar` / `password`** (queda como root) y cambiá la
contraseña en Usuarios. Si preferís otro usuario: `php bin/console admin:create tu@email.com`.

Si el hosting no tiene SSH, usá la Terminal de cPanel.

### 7. Verificar que el rewrite funciona

Si al abrir la landing ves un error 404, verificá que `mod_rewrite` está activo y que el `.htaccess` de `public/` tiene efecto. En cPanel suele estar habilitado por defecto.

---

## Configuración `.env`

El archivo `.env` se configura manualmente. Copiá `.env.example` como `.env` y editá los datos:

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

## Base de datos

Toda la base se crea o actualiza con **un solo script: `landing/db/install.sql`**.

- Sirve para una base **vacía** o una **vieja**: antes de cada cambio pregunta si ya existe
  (`CREATE TABLE IF NOT EXISTS`, y para columnas e índices consulta `information_schema`,
  porque MySQL no tiene `ADD COLUMN IF NOT EXISTS`). Nunca falla por "ya existe" ni borra datos.
- Se puede correr **las veces que quieras**: no duplica nada, no pisa configuración ni los
  permisos que el root haya cambiado.
- Si la base no tiene ningún usuario, crea **`admin@gotogo.com.ar` / `password`** (root).
  Cambiá la contraseña al entrar; Sistema lo marca mientras siga siendo `password`. Los sitios
  inicializados desde el gestor no lo reciben: ahí se carga el email y la contraseña de su admin.

Tres formas de correrlo (dan el mismo resultado):

```bash
php bin/console migrate                        # master + todos los sitios
mysql -u USUARIO -p NOMBRE_DB < db/install.sql # una base puntual
# phpMyAdmin → elegir la base → Importar → db/install.sql
```

O desde el panel: **Sistema → Aplicar migraciones**.

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

## Multisitio, servidores y recuperación

Una sola instalación sirve la landing principal (dominios en `MASTER_DOMAINS`) y las landings de clientes, cada una con **su propia base de datos y dominio**. Todo se administra desde el panel master:

| Sección | Para qué |
|---|---|
| **Sitios** | Registrar sitios, inicializar su DB (tablas + admin), verificar dominio, entrar como su admin, suspender/activar |
| **Directorios** | Listar (solo lectura) las carpetas de una ruta del servidor, ej. `public_html/*`, con el sitio asociado y si tiene la app instalada |
| **Sistema** | Diagnóstico de la instalación, aplicar migraciones, re-cifrar credenciales |

Si no podés entrar al panel: `php bin/console help` (reset de contraseña, migraciones, diagnóstico).

Guía completa: [docs/multisitio-y-servidores.md](docs/multisitio-y-servidores.md).

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

## Migraciones SQL

El esquema se aplica con `MigrationService`, que es **idempotente**: re-ejecutarlo es seguro
(tablas/columnas/índices que ya existen se toman como aplicados) y registra cada archivo en la
tabla `schema_migrations`. Nunca ejecuta el seed sobre una base con datos sin que se lo pidas,
y el seed solo completa settings faltantes: no pisa la configuración existente.

### Deploy de una versión nueva

```bash
cd landing
git pull
php composer.phar install --no-dev --optimize-autoloader   # o `composer install ...`
php bin/console migrate      # master + TODOS los sitios cliente inicializados
php cleanup.php              # opcional, solo producción
```

También se puede hacer desde el panel master: **Sistema → Aplicar migraciones** (migra la DB
master y la de cada sitio cliente; si un sitio falla, el resto sigue y se muestra el detalle).

| Comando | Qué hace |
|---|---|
| `php bin/console migrate` | Master + todos los sitios inicializados |
| `php bin/console migrate --only-master` | Solo la DB master |
| `php bin/console site:migrate <dominio>` | Solo la DB de un sitio |

### Agregar una migración

Todo va en `landing/db/install.sql`:

1. Tabla nueva: agregar su `CREATE TABLE IF NOT EXISTS` en la sección 1.
2. Columna o índice nuevo en una tabla existente: agregarlo en su `CREATE TABLE` (sección 1)
   **y** un bloque "si no existe, agregar" en la sección 2 (copiá uno de los que ya hay).
3. Correr los tests: `MigrationDriftTest` arma una base vacía y una vieja, corre el script en
   las dos y falla si no quedan exactamente iguales.

---

## Shop (tienda y pedidos por WhatsApp)

- **Catálogo** en `/shop` (`/menu` redirige). Se publica en *Shop → Configuración → Activar página pública*.
- **Habilitar tienda** (misma pantalla) agrega carrito: el cliente elige productos/variantes, pone
  nombre y teléfono y el pedido se abre en WhatsApp al número de la landing (*UX Builder →
  Botón de WhatsApp*; sin número válido no se puede habilitar). Usá el número **con código de país**.
- **El stock se reserva al pedir**: el pedido queda *Pendiente* en **Pedidos** (badge en el sidebar y
  aviso en el Dashboard). *Aceptar* lo confirma; *Rechazar* devuelve el stock. Después, el botón
  *Avisar por WhatsApp* abre un mensaje para el cliente.
- Precios y stock se recalculan en el servidor; el formulario público tiene límite de 5 pedidos por IP
  cada 15 minutos y máximo de unidades por pedido configurable.
- Permiso **Pedidos** (`orders`) asignable por rol: por ejemplo, un rol que solo atiende pedidos.

---

## Roles y permisos

- **Root**: vive en la DB master. Ve todo (incluidos Sitios, Directorios y Sistema) y es el
  único que edita **Roles y permisos**. El primer usuario de la master es root.
- **Roles** (`admin`, `editor` y los que cree el root): cada uno tiene tildadas las secciones
  del panel que puede ver. Lo que no ve, desaparece del sidebar **y** su URL redirige al
  Dashboard (o responde 403 a pedidos `fetch`).
- **Usuarios de un sitio hijo** están en la DB de ese sitio. Para configurar qué ven, desde
  **Sitios** usá el botón *Roles y permisos* (o *Entrar*): entrás al panel del sitio como root
  por SSO y editás sus roles ahí.
- Un **módulo apagado** (Módulos) se oculta y bloquea para todos, root incluido.
- Las secciones se definen en `landing/src/Support/Permissions.php`. Una ruta nueva bajo
  `/admin` tiene que tener su prefijo ahí; si no, se bloquea (`AccessControlTest` lo verifica).

---

## Checklist de producción

Antes de publicar, verificá cada punto:

### Seguridad

- [ ] Contraseña del admin cambiada (default: `password`)
- [ ] `APP_ENV=production` en `.env`
- [ ] `seo_noindex` desactivado en `/admin/settings` → SEO
- [ ] Archivo `.env` NO expuesto al público (el document root es `public/`, no la raíz)
- [ ] Carpeta `landing/src/` NO accesible desde el navegador
- [ ] `cleanup.php` ejecutado (borra tests/ Docker db/ en producción)

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
- [ ] PHP 8.3+ con extensiones `pdo`, `pdo_mysql`, `mbstring`
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
| `app_installed_version` | Versión instalada (se actualiza manualmente al migrar) |
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