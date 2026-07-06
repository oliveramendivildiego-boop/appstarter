# Manual del Módulo: Equipos

**Versión:** 1.0
**Fecha:** 2023-10-27

## 1. Objetivo del Módulo

Centralizar la gestión del inventario de equipamiento del laboratorio. Este módulo permite mantener un registro detallado de cada equipo, programar y documentar sus mantenimientos (preventivos y correctivos), y llevar un historial de su vida útil y estado operativo.

## 2. Acceso y Permisos

-   **Ruta de Acceso:** `[URL_DEL_SISTEMA]/equipment`
-   **Permisos Requeridos:** Requiere el permiso `'equipment'`, usualmente asignado a roles de jefatura de laboratorio o personal técnico encargado.

## 3. Funcionalidades

### 3.1. Listado de Equipos

Muestra una tabla con todos los equipos registrados en el sistema.

#### Componentes de la Pantalla:

-   **Botón "Agregar Equipo":** Abre el formulario para registrar un nuevo equipo.
-   **Tabla de Equipos:**
    -   **Columnas:** Nombre del Equipo, Marca/Modelo, Número de Serie, Ubicación, Próximo Mantenimiento, Acciones.
    -   **Acciones por Fila:**
        -   **Editar:** Modifica la información del equipo.
        -   **Ver Historial:** Navega a una vista detallada con todos los mantenimientos realizados.
        -   **Registrar Mantenimiento:** Abre un formulario para documentar un nuevo mantenimiento.

### 3.2. Formulario de Equipo (Crear/Editar)

-   `Nombre del Equipo` (requerido).
-   `Marca`, `Modelo`, `Número de Serie`.
-   `Fecha de Adquisición`, `Proveedor`.
-   `Ubicación` (ej. "Área de Química").
-   `Frecuencia de Mantenimiento Preventivo` (ej. Mensual, Trimestral, Anual).

### 3.3. Registro y Seguimiento de Mantenimientos

-   **Formulario de Mantenimiento:**
    -   `Tipo de Mantenimiento`: Preventivo o Correctivo.
    -   `Fecha de Mantenimiento`.
    -   `Descripción del Trabajo Realizado`.
    -   `Técnico Responsable`.
    -   `Costo` (opcional).
-   **Historial de Mantenimiento:** Una tabla cronológica que lista todos los mantenimientos de un equipo, permitiendo consultar los detalles de cada intervención.

## 4. Componentes Técnicos

### 4.1. Controlador (`App/Controllers/Equipment.php`)

Gestiona el CRUD de equipos y la lógica para registrar y listar los mantenimientos.

### 4.2. Modelos

-   **`EquipmentModel.php`:** Gestiona la tabla `equipment` con la información general de los equipos.
-   **`EquipmentMaintenanceModel.php`:** Gestiona la tabla `equipment_maintenance`, que almacena el registro de cada mantenimiento realizado, vinculado a un equipo.

### 4.3. Vistas (`app/Views/equipment/`)

-   `index.php`: Listado de equipos.
-   `form.php`: Formulario para crear/editar un equipo.
-   `history.php`: Vista del historial de mantenimientos de un equipo.