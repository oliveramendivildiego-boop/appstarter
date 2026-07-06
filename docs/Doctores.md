# Manual del Módulo: Doctores

**Versión:** 1.0
**Fecha:** 2023-10-27

## 1. Objetivo del Módulo

Este módulo permite la administración de la información de los médicos referentes. Un médico referente es aquel que envía pacientes al laboratorio para la realización de análisis. La correcta gestión de estos datos es fundamental para el seguimiento de pacientes, la asignación de órdenes y, especialmente, para el cálculo de comisiones.

## 2. Acceso y Permisos

-   **Ruta de Acceso:** Se accede al módulo a través de la URL `[URL_DEL_SISTEMA]/doctors`.
-   **Permisos Requeridos:** El usuario debe tener el permiso `'doctors'` asignado a su perfil para poder crear, editar y eliminar registros de doctores.

## 3. Funcionalidades

### 3.1. Pantalla Principal (Listado de Doctores)

Muestra una tabla con todos los médicos registrados en el sistema, facilitando su consulta y gestión.

#### Componentes de la Pantalla:

-   **Botón "Agregar Doctor":** Abre el formulario para registrar un nuevo médico.
-   **Campo de Búsqueda:** Permite filtrar la lista de doctores por nombre, apellido o especialidad.
-   **Tabla de Doctores:**
    -   **Columnas:** Nombre Completo, Especialidad, Teléfono, Email, Acciones.
    -   **Acciones por Fila:**
        -   **Editar:** Carga los datos del médico en el formulario para su modificación.
        -   **Eliminar:** Inicia el proceso para dar de baja al médico del sistema (eliminación lógica).

### 3.2. Formulario de Doctor (Crear/Editar)

Formulario para ingresar o actualizar la información de un médico referente.

#### Campos del Formulario:

-   **Información Personal:**
    -   `Nombres` (string, requerido)
    -   `Apellidos` (string, requerido)
    -   `Especialidad` (string)
-   **Información de Contacto:**
    -   `Teléfono` (string)
    -   `Email` (string, formato de email válido)
    -   `Dirección` (string)
-   **Información de Comisión:**
    -   `Porcentaje de Comisión` (numérico): Porcentaje que se aplicará sobre las pruebas de los pacientes referidos por este médico.

## 4. Componentes Técnicos

### 4.1. Rutas (Routes)

El módulo sigue un patrón RESTful, similar a otros módulos de gestión de personas.

-   `GET /doctors`: Muestra el listado de doctores (`index`).
-   `GET /doctors/view/(:num)`: Muestra el formulario de creación/edición (`view`).
-   `POST /doctors/save/(:num)`: Procesa la creación o actualización de un doctor (`save`).
-   `POST /doctors/delete`: Procesa la eliminación de uno o más doctores (`delete`).
-   `GET /doctors/search`: Realiza la búsqueda de doctores vía AJAX (`search`).

### 4.2. Controlador (`App/Controllers/Doctors.php`)

Este controlador, que probablemente hereda de `PersonController`, gestiona la lógica del módulo.
-   `index()`: Muestra la tabla de gestión.
-   `view()`: Muestra el formulario de creación/edición.
-   `save()`: Valida y guarda los datos en las tablas `people` y `doctors`.
-   `delete()`: Realiza la eliminación lógica del doctor.

### 4.3. Modelo (`App/Models/DoctorModel.php`)

Gestiona la interacción con la base de datos, principalmente con las tablas `doctors` y `people`.
-   `saveDoctor()`: Orquesta el guardado en ambas tablas dentro de una transacción.
-   `getInfo()`: Obtiene la información completa de un doctor uniendo las tablas.
-   `search()`: Implementa la lógica de búsqueda en el listado.
-   `getSearchSuggestions()`: Provee los datos para el autocompletado en otros módulos (ej. al crear una orden).

### 4.4. Vistas (`app/Views/doctors/`)

-   `form.php`: Formulario para crear y editar doctores.
-   El listado de doctores reutiliza la vista genérica `app/Views/people/manage.php`, adaptando las columnas y acciones.

## 5. Reportes Relacionados

-   **Módulo de Comisiones de Doctores:** Este módulo es el principal consumidor de la información de los doctores para calcular los pagos.
-   **Reporte de Pacientes por Médico Referente:** Un listado que muestra cuántos y qué pacientes ha referido cada doctor en un período de tiempo.