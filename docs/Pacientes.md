# Manual del Módulo: Pacientes (Clientes)

**Versión:** 1.0
**Fecha:** 2023-10-27

## 1. Objetivo del Módulo

Este módulo permite crear, consultar, modificar y eliminar los registros de los pacientes del laboratorio. Funciona como el repositorio central para toda la información demográfica, de contacto y facturación de los pacientes, siendo un pilar fundamental para la creación de órdenes y la consulta de historiales.

## 2. Acceso y Permisos

-   **Ruta de Acceso:** Se accede al módulo a través de la URL `[URL_DEL_SISTEMA]/customers`.
-   **Permisos Requeridos:** El usuario debe tener un rol de **Empleado** con el permiso `'customers'` asignado para poder acceder y operar en este módulo.

## 3. Funcionalidades

### 3.1. Pantalla Principal (Listado de Pacientes)

Al ingresar al módulo, se despliega una tabla que lista todos los pacientes registrados en el sistema, con paginación para facilitar la navegación.

#### Componentes de la Pantalla:

-   **Botón "Agregar Paciente":** Ubicado en la parte superior, permite acceder al formulario para registrar un nuevo paciente.
-   **Campo de Búsqueda:** Permite filtrar la lista de pacientes en tiempo real por Nombre, Apellido o Número de Cédula (CI).
-   **Botón "Formato de nombres":** Una herramienta de acción masiva que permite estandarizar los nombres y apellidos de **todos** los pacientes a un formato consistente (ej. tipo título: "Juan Perez").
-   **Tabla de Pacientes:** Muestra la información clave de cada paciente.
    -   **Columnas:** Cédula, Nombre Completo, Edad, Género, Teléfono, Acciones.
    -   **Acciones por Fila:**
        -   **Editar:** Abre el formulario de paciente con los datos cargados para su modificación.
        -   **Eliminar:** Inicia el proceso para eliminar el registro del paciente (usualmente una eliminación lógica o *soft delete*).
        -   **Expediente:** Redirige al historial completo de órdenes y resultados del paciente.

### 3.2. Formulario de Paciente (Crear/Editar)

Este formulario se utiliza tanto para crear nuevos pacientes como para modificar los existentes.

#### Campos del Formulario:

-   **Información Personal:**
    -   `Nombres` (string, requerido)
    -   `Apellidos` (string, requerido)
    -   `Cédula de Identidad / Pasaporte` (string, requerido, único)
    -   `Fecha de Nacimiento` (date, requerido)
    -   `Género` (select: Masculino, Femenino, Otro)
-   **Información de Contacto:**
    -   `Teléfono Celular` (string)
    -   `Teléfono Fijo` (string)
    -   `Email` (string, formato de email válido)
    -   `Dirección` (textarea)
-   **Información Adicional/Facturación:**
    -   `NIT / RUC` (string, para facturación)
    -   `Razón Social` (string, para facturación)

#### Validaciones:

-   Los campos de nombres, apellidos, cédula y fecha de nacimiento son obligatorios.
-   La cédula de identidad debe ser única en todo el sistema para evitar duplicados.
-   El email debe tener un formato válido.
-   La fecha de nacimiento no puede ser una fecha futura.

### 3.3. Expediente del Paciente

Accesible desde el listado, esta pantalla muestra una vista consolidada de toda la actividad del paciente en el laboratorio.

-   **Información mostrada:** Datos demográficos del paciente.
-   **Historial de Órdenes:** Un listado de todas las órdenes de laboratorio asociadas al paciente, con su fecha, folio, y estado. Permite acceder al detalle de cada orden.
-   **Historial de Resultados:** Acceso directo a los PDFs de resultados de órdenes finalizadas.

## 4. Componentes Técnicos

### 4.1. Rutas (Routes)

El módulo responde a las siguientes rutas principales, siguiendo un patrón RESTful:

-   `GET /customers`: Muestra el listado de pacientes (`index`).
-   `GET /customers/new`: Muestra el formulario de creación (`new`).
-   `POST /customers`: Procesa la creación de un nuevo paciente (`create`).
-   `GET /customers/(:num)/edit`: Muestra el formulario de edición para un paciente (`edit`).
-   `POST /customers/(:num)`: Procesa la actualización de un paciente (`update`).
-   `DELETE /customers/(:num)`: Elimina un paciente (`delete`).
-   `GET /customers/record/(:num)`: Muestra el expediente del paciente (`record`).
-   `POST /customers/format_names`: Ejecuta la acción masiva de formateo de nombres (`formatNames`).

### 4.2. Controlador (`App/Controllers/Customers.php`)

Orquesta la lógica del módulo. Contiene métodos que se corresponden con las rutas definidas: `index()`, `new()`, `create()`, `edit()`, `update()`, `delete()`, `record()`, y `formatNames()`. Se encarga de recibir las peticiones, interactuar con el modelo y cargar las vistas adecuadas.

### 4.3. Modelo (`App/Models/CustomerModel.php`)

Gestiona todas las interacciones con la tabla `customers` de la base de datos.
-   Define los campos permitidos (`$allowedFields`).
-   Contiene las reglas de validación (`$validationRules`).
-   Implementa la lógica de eliminación (probablemente *soft deletes* a través de la propiedad `$useSoftDeletes`).
-   Puede contener métodos personalizados como `search($term)` para la funcionalidad de búsqueda o `getCustomerWithAge()` para calcular la edad.

### 4.4. Vistas (`app/Views/customers/`)

-   `index.php`: Vista principal que contiene la estructura del listado y los botones de acción.
-   `form.php`: Formulario para crear y editar pacientes.
-   `record.php`: Plantilla para mostrar el expediente del paciente.
-   `_list.php` (parcial): Probablemente contiene solo el bucle que renderiza la tabla de pacientes, para ser recargada vía AJAX durante la búsqueda.

### 4.5. Reportes Relacionados

La información de este módulo es fundamental para reportes como:
-   Reporte demográfico de pacientes (por edad, por género).
-   Listado de pacientes para campañas de marketing (ej. cumpleaños del mes).
-   Reportes de actividad de pacientes nuevos.

---