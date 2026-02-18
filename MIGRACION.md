# Migración John - CodeIgniter 2.2 → 4.7

## Proyecto migrado

El proyecto **john_ci4** es la versión migrada del sistema de Laboratorio John desde CodeIgniter 2.2 a CodeIgniter 4.7.

## Ubicación

- **Proyecto original**: `c:\wamp64\www\john`
- **Proyecto migrado**: `c:\wamp64\www\john_ci4`

## Configuración para acceder

### 1. Configurar el servidor web

El document root debe apuntar a la carpeta **public**:

- **URL**: `http://localhost/john_ci4/public/`

O configura un Virtual Host que apunte a `c:\wamp64\www\john_ci4\public`

### 2. Base de datos

El archivo `.env` está configurado para usar:
- **Base de datos**: john
- **Prefijo de tablas**: dom_
- **Usuario**: root
- **Contraseña**: (vacía)

Ajusta estos valores si tu configuración es distinta.

### 3. Requisitos PHP

- PHP 8.2 o superior
- Extensiones: intl, mbstring (recomendado: zip para Composer)

## Módulos migrados en esta fase

1. **Login** – Autenticación
2. **Home** – Página principal con módulos permitidos
3. **Customers (Pacientes)** – CRUD completo

## Módulos pendientes de migrar

- Doctors, Labotests, Toquotes, Registers, Reports
- Items, Employees, Config
- Otros según el proyecto original

## Estructura CI4 vs CI2

| CI2              | CI4                      |
|------------------|--------------------------|
| application/     | app/                     |
| system/          | vendor/codeigniter4/     |
| index.php (raíz) | public/index.php         |
| $this->load->view| return view()            |
| $this->db        | model()-> or $db         |
| $this->input->post| $this->request->getPost() |

## Cómo migrar más módulos

1. Crear modelo en `app/Models/` con namespace `App\Models`
2. Crear controlador en `app/Controllers/` extendiendo `PersonController` o `SecureArea`
3. Crear vistas en `app/Views/`
4. Añadir rutas en `app/Config/Routes.php`
5. Añadir archivos de idioma en `app/Language/es/`
