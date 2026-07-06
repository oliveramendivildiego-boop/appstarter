# Manual del Módulo: Expediente del Paciente

**Versión:** 1.0
**Fecha:** 2023-10-27

## 1. Objetivo del Módulo

El módulo de Expediente proporciona una vista de 360 grados de toda la historia de un paciente dentro del laboratorio. Su propósito es consolidar en una única pantalla todos los registros, órdenes y resultados asociados a un paciente específico, facilitando la consulta rápida de su historial clínico y la trazabilidad de su atención.

## 2. Acceso y Permisos

-   **Ruta de Acceso:** No es un módulo con una URL de entrada directa desde el menú principal. Se accede a él a través de una acción en el listado del módulo de **Pacientes**. La URL sigue el patrón `[URL_DEL_SISTEMA]/customers/record/{id_paciente}`.
-   **Permisos Requeridos:** El acceso está ligado al permiso del módulo de Pacientes (`'customers'`). Si un usuario puede ver la lista de pacientes, puede acceder a sus expedientes.

## 3. Funcionalidades

### 3.1. Pantalla Única de Expediente

Este módulo consiste en una sola pantalla que se divide en dos secciones principales.

#### Sección 1: Datos del Paciente

-   Muestra la información demográfica y de contacto más relevante del paciente, extraída del módulo de **Pacientes**.
-   **Campos mostrados:** Nombre completo, Cédula, Fecha de Nacimiento, Edad actual, Género, Teléfono, Email.

#### Sección 2: Historial de Órdenes

-   Presenta una tabla cronológica con todas las órdenes de laboratorio (`Registros`) que se han creado para ese paciente.
-   **Columnas de la tabla:**
    -   `Folio`: El número de identificación único de la orden.
    -   `Fecha`: La fecha en que se creó la orden.
    -   `Pruebas Solicitadas`: Un resumen de los análisis incluidos en la orden.
    -   `Estado`: El estado actual de la orden (Pendiente, Validado, Entregado, etc.).
    -   `Acciones`:
        -   **Ver Resultados (PDF):** Un botón de acceso directo para descargar el reporte de resultados en PDF. Este botón solo está activo para las órdenes que ya han sido validadas.
        -   **Ir a la Orden:** Un enlace que redirige al detalle de la orden en el módulo de **Registros**, permitiendo ver la captura de resultados, pagos, etc.

## 4. Componentes Técnicos

### 4.1. Rutas (Routes)

-   `GET /customers/record/(:num)`: Es la única ruta asociada a este módulo. El número (`:num`) corresponde al `person_id` del paciente.

### 4.2. Controlador (`App/Controllers/Customers.php`)

-   La lógica para este módulo reside dentro del controlador de Pacientes, en el método `record($id)`.
-   Este método recibe el ID del paciente, consulta su información personal usando `CustomerModel` y luego consulta todas sus órdenes asociadas usando `RegisterModel`. Finalmente, pasa todos estos datos a la vista.

### 4.3. Modelos

-   **`CustomerModel.php`:** Se usa para obtener los datos demográficos del paciente.
-   **`RegisterModel.php`:** Se usa para obtener el listado de todas las órdenes asociadas al `person_id` del paciente.

### 4.4. Vistas (`app/Views/customers/`)

-   `record.php`: Es la única vista para este módulo. Recibe los datos del paciente y el array de sus órdenes para renderizar la pantalla.