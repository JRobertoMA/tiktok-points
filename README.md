# TikTok Points

Aplicación web para guardar y visualizar en un mapa los lugares que ves en TikTok y quieres visitar con tu pareja o amigos.

![PHP](https://img.shields.io/badge/PHP-8.0+-777BB4?style=flat-square&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-5.7+-4479A1?style=flat-square&logo=mysql&logoColor=white)
![Leaflet](https://img.shields.io/badge/Leaflet-1.9.4-199900?style=flat-square&logo=leaflet&logoColor=white)
![License](https://img.shields.io/badge/License-MIT-green?style=flat-square)

## Características

### Mapa Interactivo
- Visualización de lugares con marcadores personalizados por categoría
- Colores diferentes para lugares visitados y pendientes
- Zoom y navegación fluida con OpenStreetMap (gratis, sin límites)
- Botón para ir a tu ubicación actual
- Responsive: en móvil usa gestos de pellizco para zoom

### Sistema de Usuarios
- Registro y login con autenticación JWT
- Múltiples usuarios pueden compartir los mismos lugares
- Perfil personalizable (cambiar nombre, email y contraseña)
- Sesión persistente en el navegador

### Gestión de Lugares
- Agregar lugares con:
  - Link de TikTok original
  - Nombre del lugar
  - Categoría personalizable
  - Dirección
  - Coordenadas (seleccionar haciendo clic en el mapa)
  - Calificación con estrellas (1-5)
  - Notas personales
- Editar y eliminar lugares
- Filtrar por: Todos / Pendientes / Visitados

### Marcar como Visitado
- Registrar fecha y hora de la visita
- Actualizar calificación después de visitar
- Agregar notas de la experiencia
- Subir fotos y videos del lugar

### Categorías Personalizables
- Crear categorías con nombre, icono (emoji) y color
- Categorías predefinidas: Restaurantes, Cafés, Bares, Parques, Museos, etc.
- Los marcadores en el mapa reflejan el color e icono de la categoría

### Galería de Media
- Subir múltiples fotos y videos por lugar
- Formatos soportados: JPG, PNG, GIF, WebP, MP4, WebM
- Visualización en lightbox
- Tamaño máximo: 50MB por archivo

## Requisitos

- PHP 8.0 o superior
- MySQL 5.7 o superior
- Apache con mod_rewrite habilitado
- Hosting compartido compatible (probado en IONOS)

## Instalación

### 1. Clonar el repositorio

```bash
git clone https://github.com/tu-usuario/tiktok-routes.git
cd tiktok-routes
```

### 2. Crear la base de datos

Importa el archivo `database.sql` en tu servidor MySQL:

```bash
mysql -u tu_usuario -p tu_base_de_datos < database.sql
```

O desde phpMyAdmin: Importar → Seleccionar `database.sql`

### 3. Configurar credenciales

Edita el archivo `config.php` con tus datos:

```php
// Configuración de Base de Datos
define('DB_HOST', 'localhost');
define('DB_NAME', 'tiktok_points');
define('DB_USER', 'tu_usuario');
define('DB_PASS', 'tu_contraseña');

// Clave secreta para JWT (¡cámbiala!)
define('JWT_SECRET', 'tu_clave_secreta_muy_larga_y_segura');

// URL base de tu aplicación
define('BASE_URL', 'https://tu-dominio.com/tiktok_points');
```

### 4. Configurar permisos

Asegúrate de que la carpeta `uploads/` tenga permisos de escritura:

```bash
chmod 755 uploads/
```

### 5. Subir al servidor

Sube todos los archivos a tu hosting vía FTP o el método que prefieras.

## Estructura del Proyecto

```
tiktok_points/
├── api/
│   ├── auth/
│   │   ├── login.php       # Inicio de sesión
│   │   ├── register.php    # Registro de usuarios
│   │   ├── me.php          # Información del usuario actual
│   │   └── profile.php     # Actualizar perfil y contraseña
│   ├── places/
│   │   ├── index.php       # Listar y crear lugares
│   │   ├── update.php      # Actualizar lugar
│   │   ├── delete.php      # Eliminar lugar
│   │   └── visit.php       # Marcar como visitado
│   ├── categories/
│   │   ├── index.php       # Listar y crear categorías
│   │   ├── update.php      # Actualizar categoría
│   │   └── delete.php      # Eliminar categoría
│   ├── uploads/
│   │   └── index.php       # Subir y eliminar archivos
│   ├── config/
│   │   └── database.php    # Conexión a la base de datos
│   └── middleware/
│       └── auth.php        # Autenticación JWT
├── uploads/                # Fotos y videos subidos
├── index.html              # Página principal con mapa
├── login.html              # Página de login/registro
├── config.php              # Configuración general
├── database.sql            # Script de creación de BD
├── .htaccess               # Configuración Apache
└── README.md
```

## API Endpoints

### Autenticación
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| POST | `/api/auth/register.php` | Registrar usuario |
| POST | `/api/auth/login.php` | Iniciar sesión |
| GET | `/api/auth/me.php` | Obtener usuario actual |
| PUT | `/api/auth/profile.php` | Actualizar perfil |

### Lugares
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/places/index.php` | Listar lugares |
| POST | `/api/places/index.php` | Crear lugar |
| PUT | `/api/places/update.php?id=X` | Actualizar lugar |
| DELETE | `/api/places/delete.php?id=X` | Eliminar lugar |
| POST | `/api/places/visit.php?id=X` | Marcar como visitado |

### Categorías
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/api/categories/index.php` | Listar categorías |
| POST | `/api/categories/index.php` | Crear categoría |
| PUT | `/api/categories/update.php?id=X` | Actualizar categoría |
| DELETE | `/api/categories/delete.php?id=X` | Eliminar categoría |

### Uploads
| Método | Endpoint | Descripción |
|--------|----------|-------------|
| POST | `/api/uploads/index.php?place_id=X` | Subir archivo |
| DELETE | `/api/uploads/index.php?id=X` | Eliminar archivo |

## Tecnologías

- **Backend**: PHP 8+ (sin frameworks, compatible con hosting compartido)
- **Base de datos**: MySQL con PDO
- **Autenticación**: JWT (implementación propia sin dependencias)
- **Frontend**: HTML5, CSS3, JavaScript vanilla
- **Mapas**: Leaflet + OpenStreetMap
- **Estilos**: CSS personalizado con diseño responsive

## Seguridad

- Contraseñas hasheadas con `password_hash()` (bcrypt)
- Tokens JWT con firma HMAC-SHA256
- Prepared statements para prevenir SQL injection
- Validación de tipos de archivo en uploads
- Headers de seguridad en `.htaccess`
- Protección contra ejecución de PHP en carpeta uploads

## Capturas de Pantalla

### Vista Principal (Desktop)
El mapa muestra todos los lugares con marcadores de colores según su categoría. La barra lateral permite filtrar y ver el listado.

### Vista Móvil
Interfaz adaptada para móviles con gestos táctiles para el mapa y menú lateral deslizable.

### Agregar Lugar
Modal para agregar un nuevo lugar seleccionando la ubicación directamente en el mapa.

## Contribuir

1. Fork el repositorio
2. Crea una rama para tu feature (`git checkout -b feature/nueva-funcionalidad`)
3. Commit tus cambios (`git commit -m 'Agregar nueva funcionalidad'`)
4. Push a la rama (`git push origin feature/nueva-funcionalidad`)
5. Abre un Pull Request

## Licencia

Este proyecto está bajo la Licencia MIT. Ver el archivo [LICENSE](LICENSE) para más detalles.

## Autor

Desarrollado con amor para explorar lugares juntos.

---

**Nota**: Este proyecto no está afiliado con TikTok. Es una herramienta personal para organizar lugares vistos en la plataforma.
