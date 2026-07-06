# Manual del Módulo: Auditoría

**Versión:** 1.0
**Fecha:** 2023-10-27

## 1. Objetivo del Módulo

Proporcionar un registro inmutable y detallado de todas las acciones significativas realizadas por los usuarios dentro del sistema. Su propósito es garantizar la trazabilidad, la seguridad y la rendición de cuentas, permitiendo a los administradores investigar quién hizo qué, cuándo y desde dónde.

## 2. Acceso y Permisos

-   **Ruta de Acceso:** `[URL_DEL_SISTEMA]/audit`
-   **Permisos Requeridos:** Requiere el permiso `'audit'`. Este es un permiso de muy alto nivel, reservado exclusivamente para **Administradores del Sistema** o roles de auditoría.

## 3. Funcionalidades

### 3.1. Pantalla Principal (Visor de Logs)

El módulo consiste en una única pantalla que muestra un log cronológico de eventos.

#### Componentes de la Pantalla:

-   **Filtros de Búsqueda:**
    -   `Rango de Fechas`: Para acotar los eventos a un período específico.
    -   `Usuario`: Para ver todas las acciones de un empleado en particular.
    -   `Módulo`: Para filtrar eventos relacionados con un módulo específico (ej. "Pacientes", "Registros").
    -   `Acción`: Para buscar acciones específicas (ej. "crear", "eliminar", "validar_resultados").
-   **Tabla de Auditoría:**
    -   **Columnas:**
        -   `Fecha y Hora`: Momento exacto en que ocurrió el evento.
        -   `Usuario`: Nombre del empleado que realizó la acción.
        -   `Módulo`: Módulo afectado.
        -   `Acción`: Tipo de operación realizada.
        -   `ID del Objeto`: Identificador del registro afectado (ej. ID del paciente, Folio de la orden).
        -   `Detalles`: Un resumen o datos en formato JSON con la información relevante del evento (ej. valores antes y después de un cambio).

## 4. Componentes Técnicos

### 4.1. Controlador (`App/Controllers/Audit.php`)

-   `index()`: Recibe los filtros, consulta al modelo y muestra la tabla de logs con paginación.

### 4.2. Modelo (`App/Models/AuditModel.php`)

-   Gestiona la tabla `audit_log`.
-   `log($module, $action, $objectId, $details)`: Método estático o de servicio que es invocado desde **todos los demás controladores** del sistema cada vez que se realiza una acción importante. Este método se encarga de recopilar la información (usuario actual, fecha, etc.) y guardarla en la tabla de auditoría.
-   `search($filters)`: Método que construye la consulta a la base de datos aplicando los filtros de la interfaz.