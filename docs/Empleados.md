# Manual del Módulo: Empleados

**Versión:** 1.0
**Fecha:** 2023-10-27

## 1. Objetivo del Módulo

Este módulo es el centro de control para la administración de usuarios del sistema. Permite crear, gestionar y eliminar las cuentas de los empleados que accederán a la plataforma, así como definir de manera granular a qué funcionalidades y módulos tiene acceso cada uno a través de un sistema de permisos.

## 2. Acceso y Permisos

-   **Ruta de Acceso:** Se accede al módulo a través de la URL `[URL_DEL_SISTEMA]/employees`.
-   **Permisos Requeridos:** Para acceder y gestionar otros usuarios, el empleado debe tener el permiso `'employees'` asignado a su propio perfil. Un usuario no puede editar sus propios permisos.

## 3. Funcionalidades

### 3.1. Pantalla Principal (Listado de Empleados)

Al igual que en el módulo de Pacientes, la pantalla principal muestra una tabla con todos los usuarios (empleados) registrados en el sistema.

#### Componentes de la Pantalla:

-   **Botón "Agregar Empleado":** Dirige al formulario para la creación de un nuevo usuario.
-   **Campo de Búsqueda:** Permite encontrar empleados rápidamente por nombre, apellido o nombre de usuario.
-   **Tabla de Empleados:**
    -   **Columnas:** Nombre Completo, Nombre de Usuario, Email, Acciones.
    -   **Acciones por Fila:**
        -   **Editar:** Abre el formulario del empleado para modificar sus datos personales, credenciales de acceso o permisos.
        -   **Eliminar:** Inicia el proceso para dar de baja al empleado (usualmente una eliminación lógica que le impide iniciar sesión).

### 3.2. Formulario de Empleado (Crear/Editar)

Este formulario se divide en secciones para facilitar la gestión de la información del empleado.

#### Campos del Formulario:

-   **Información Personal:**
    -   `Nombres` (string, requerido)
    -   `Apellidos` (string, requerido)
    -   `Email` (string)
    -   `Teléfono` (string)

-   **Información de Acceso:**
    -   `Nombre de Usuario` (string, requerido, único): El identificador que usará el empleado para iniciar sesión.
    -   `Contraseña` (password): Requerido al crear un nuevo empleado. Al editar, solo se debe llenar si se desea cambiar la contraseña actual.
    -   `Confirmar Contraseña` (password): Debe coincidir con el campo de contraseña.

-   **Permisos del Módulo:**
    -   Una lista de todos los módulos del sistema (Pacientes, Reportes, Inventario, etc.) con una casilla de verificación (checkbox) al lado de cada uno.
    -   Marcar una casilla otorga al empleado el acceso al módulo correspondiente.
    -   Desmarcarla revoca el acceso.

## 4. Componentes Técnicos

### 4.1. Rutas (Routes)

El enrutamiento sigue un patrón RESTful similar al de otros módulos de "personas".

-   `GET /employees`: Muestra el listado de empleados (`index`).
-   `GET /employees/view/(:num)`: Muestra el formulario de creación/edición (`view`).
-   `POST /employees/save/(:num)`: Procesa la creación o actualización de un empleado (`save`).
-   `POST /employees/delete`: Procesa la eliminación de uno o más empleados (`delete`).
-   `GET /employees/search`: Realiza la búsqueda de empleados vía AJAX (`search`).

### 4.2. Controlador (`App/Controllers/Employees.php`)

Hereda de `PersonController` para reutilizar la lógica de gestión de personas.
-   `index()`: Prepara y muestra la tabla de gestión.
-   `view()`: Muestra el formulario y carga los permisos actuales del empleado y la lista total de permisos disponibles.
-   `save()`: Valida los datos. Hashea la contraseña si se proporciona una nueva. Guarda los datos personales en la tabla `people`, los datos de empleado en `employees`, y actualiza la tabla pivote `employees_permissions` con los permisos seleccionados.
-   `delete()`: Realiza una eliminación lógica (soft delete) del empleado.

### 4.3. Modelo (`App/Models/EmployeeModel.php`)

Es el modelo más complejo dentro de la gestión de "personas" debido a la lógica de autenticación y permisos.
-   Gestiona la interacción con las tablas `employees`, `people` y `permissions`.
-   `saveEmployee()`: Orquesta el guardado en las múltiples tablas dentro de una transacción.
-   `hashPassword()`: Se asegura de que las contraseñas se almacenen de forma segura.
-   `username_exists()`: Verifica la unicidad del nombre de usuario.
-   `has_permission($module_id, $person_id)`: Método crucial que se utiliza en todo el sistema (a través del controlador `SecureArea`) para verificar si el usuario actual tiene acceso a un módulo específico.

### 4.4. Vistas (`app/Views/employees/`)

-   `form.php`: El formulario principal para crear/editar empleados. Contiene la sección especial para la asignación de permisos.
-   El listado de empleados reutiliza la vista genérica `app/Views/people/manage.php`.

## 5. Reportes Relacionados

-   **Reporte de Permisos de Usuario:** Un listado detallado de qué usuarios tienen acceso a qué módulos, útil para auditorías de seguridad.
-   **Log de Actividad por Usuario:** Aunque es parte del módulo de Auditoría, se nutre de la información de este módulo para registrar quién hizo qué acción.

---