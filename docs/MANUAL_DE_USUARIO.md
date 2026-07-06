# Manual de Usuario del Sistema de Laboratorio

**Versión del Documento:** 1.0
**Fecha de Creación:** 2023-10-27

---

## Introducción

Este documento unifica los manuales de todos los módulos que componen el Sistema de Gestión de Laboratorio (LIS). Su objetivo es servir como una guía de referencia completa para los usuarios, administradores y personal técnico, detallando las funcionalidades, flujos de trabajo y componentes técnicos de cada parte del sistema.

---

# Índice de Módulos del Sistema de Laboratorio

Este documento proporciona un resumen de alto nivel de todos los módulos que componen el sistema. Para cada módulo, se detalla su propósito, ruta de acceso principal, archivos técnicos clave, y una estimación de su complejidad y tamaño.

| Nombre | Ruta (URL) | Descripción | Archivos Relacionados (Estimado) | Complejidad | Pantallas (Aprox.) |
| :--- | :--- | :--- | :--- | :--- | :--- |
| **Pacientes (Clientes)** | `/customers` | Gestión de la información demográfica y de contacto de los pacientes. | `Customers.php`, `CustomerModel.php`, `app/Views/customers/` | Media | 3 |
| **Empleados** | `/employees` | Administración de usuarios del sistema, roles y permisos de acceso. | `Employees.php`, `EmployeeModel.php`, `app/Views/employees/` | Media | 3 |
| **Doctores** | `/doctors` | Gestión de la información de los médicos referentes. | `Doctors.php`, `DoctorModel.php`, `app/Views/doctors/` | Baja | 2 |
| **Comisiones de Doctores** | `/doctor_commissions` | Cálculo, reporte y gestión del pago de comisiones a doctores. | `DoctorCommissions.php`, `DoctorCommissionModel.php`, `app/Views/doctor_commissions/` | Alta | 3 |
| **Catálogo de Pruebas (Labotests)** | `/labotests` | Administración de análisis, perfiles, precios y parámetros de las pruebas. | `Labotests.php`, `LabotestModel.php`, `app/Views/labotests/` | Alta | 4 |
| **Cotizaciones (Toquotes)** | `/toquotes` | Creación y envío de cotizaciones de pruebas a pacientes. | `Toquotes.php`, `ToquoteModel.php`, `PdfRendererInterface.php`, `app/Views/toquotes/` | Media | 3 |
| **Registros (Órdenes)** | `/registers` | Módulo central para crear, procesar y gestionar las órdenes de laboratorio. | `Registers.php`, `RegisterModel.php`, `RegistroFolioService.php`, `app/Views/registers/` | Alta | 5+ |
| **Expediente** | `/customers/record/{id}` | Visualización del historial clínico y de resultados de un paciente. | `Customers.php` (método `record`), `app/Views/customers/record.php` | Media | 1 |
| **Reportes** | `/reports` | Generación de reportes operativos, financieros y estadísticos. | `Reports.php`, Varios Modelos, `app/Views/reports/` | Alta | 5+ |
| **Configuración** | `/config` | Panel para la configuración general del sistema (ej. datos de la empresa, plantillas). | `Config.php`, `AppConfigModel.php`, `app/Views/config/` | Media | 2 |
| **Control de Calidad** | `/quality_control` | Gestión de la calidad de los resultados, lotes de control y reglas. | `QualityControl.php`, `QualityControlModel.php`, `app/Views/quality_control/` | Alta | 4 |
| **Inventario (Reactivos)** | `/inventory` | Administración de reactivos, insumos, lotes, stock y fechas de vencimiento. | `Inventory.php`, `InventoryModel.php`, `app/Views/inventory/` | Media | 4 |
| **Equipos** | `/equipment` | Gestión del equipamiento del laboratorio y sus mantenimientos. | `Equipment.php`, `EquipmentModel.php`, `app/Views/equipment/` | Baja | 3 |
| **Auditoría** | `/audit` | Registro y consulta de todas las acciones importantes realizadas en el sistema. | `Audit.php`, `AuditModel.php`, `app/Views/audit/` | Media | 1 |

---

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

---

# Manual del Módulo: Comisiones de Doctores

**Versión:** 1.0
**Fecha:** 2023-10-27

## 1. Objetivo del Módulo

Este módulo está diseñado para automatizar el cálculo, la generación de reportes y la gestión de los pagos de comisiones a los médicos referentes. Se basa en las órdenes de laboratorio generadas para los pacientes que han sido referidos por un doctor específico, aplicando el porcentaje de comisión configurado.

## 2. Acceso y Permisos

-   **Ruta de Acceso:** Se accede al módulo a través de la URL `[URL_DEL_SISTEMA]/doctor_commissions`.
-   **Permisos Requeridos:** Requiere el permiso `'doctor_commissions'`, usualmente asignado a roles administrativos o de contabilidad.

## 3. Funcionalidades

### 3.1. Pantalla Principal (Generador de Reporte)

La pantalla inicial permite al usuario filtrar y generar el reporte de comisiones para un período determinado.

#### Componentes de la Pantalla:

-   **Filtros de Reporte:**
    -   `Rango de Fechas`: Campos "Desde" y "Hasta" para definir el período del cálculo.
    -   `Doctor`: Un campo de selección (con autocompletado) para generar el reporte de un médico específico o de todos.
-   **Botón "Generar Reporte":** Ejecuta el cálculo y muestra los resultados en una tabla resumen.
-   **Tabla Resumen de Comisiones (por Doctor):**
    -   **Columnas:** Doctor, Total Facturado (de sus pacientes), % Comisión Promedio, Monto Comisión Calculada, Monto Pagado, Saldo Pendiente.
    -   **Acciones por Fila:**
        -   **Ver Detalle:** Navega a una vista desglosada de todas las órdenes que componen la comisión de ese doctor.
        -   **Registrar Pago:** Abre un modal o formulario para registrar un pago del saldo pendiente.

### 3.2. Vista de Detalle de Comisiones

Esta pantalla muestra el desglose completo de las órdenes y pruebas que contribuyen a la comisión de un doctor para el período seleccionado.

-   **Información mostrada:** Nombre del Doctor y período del reporte.
-   **Tabla de Órdenes:**
    -   **Columnas:** Folio de Orden, Fecha, Paciente, Pruebas Realizadas, Monto Facturado, % Comisión, Monto Comisión, Estado de Pago (Pagado/Pendiente).

### 3.3. Registro de Pagos

Permite marcar las comisiones como pagadas, actualizando el saldo del doctor.

-   **Funcionalidad:** Se puede acceder desde la tabla resumen o la vista de detalle.
-   **Campos:** Monto a Pagar, Fecha de Pago, Método de Pago (Efectivo, Transferencia), Referencia/Notas.
-   **Lógica:** Al registrar un pago, el sistema actualiza el estado de las comisiones correspondientes y ajusta el saldo pendiente del doctor.

## 4. Componentes Técnicos

### 4.1. Rutas (Routes)

-   `GET /doctor_commissions`: Muestra el formulario inicial del reporte (`index`).
-   `POST /doctor_commissions/report`: Procesa la generación del reporte y lo muestra (`generate_report`).
-   `GET /doctor_commissions/detail/(:num)`: Muestra el detalle de comisiones para un doctor (`detail`).
-   `POST /doctor_commissions/pay`: Procesa el registro de un pago de comisión (`register_payment`).

### 4.2. Controlador (`App/Controllers/DoctorCommissions.php`)

Es un controlador complejo que orquesta la lógica de negocio.
-   `index()`: Carga la vista principal con los filtros.
-   `generate_report()`: Recibe los filtros, invoca al modelo para realizar los cálculos complejos y carga la vista de resultados.
-   `detail()`: Obtiene el desglose de órdenes para un doctor y período.
-   `register_payment()`: Valida y guarda el registro del pago, actualizando los registros correspondientes.

### 4.3. Modelo (`App/Models/DoctorCommissionModel.php`)

Contiene la lógica de base de datos, que es la parte más pesada del módulo.
-   `calculateCommissions()`: Método principal que ejecuta una consulta SQL compleja uniendo las tablas `registers`, `register_items`, `labotests`, `customers`, y `doctors` para calcular el total facturado y la comisión por doctor en un rango de fechas.
-   `getCommissionDetail()`: Obtiene el listado de órdenes para la vista de detalle.
-   `savePayment()`: Inserta un registro en una tabla `doctor_commission_payments` y actualiza el estado de las comisiones pagadas.

### 4.4. Vistas (`app/Views/doctor_commissions/`)

-   `index.php`: Vista principal con el formulario de filtros.
-   `_report.php` (parcial): Renderiza la tabla de resultados del reporte.
-   `detail.php`: Muestra la tabla de detalle de órdenes.
-   `_payment_form.php` (parcial): Modal o formulario para registrar el pago.

## 5. Reportes Relacionados

-   **Reporte de Egresos:** Los pagos de comisiones se reflejan como un egreso para el laboratorio.
-   **Estado de Cuenta por Doctor:** El módulo en sí genera un estado de cuenta detallado para cada médico.

---

# Manual del Módulo: Catálogo de Pruebas (Labotests)

**Versión:** 1.0
**Fecha:** 2023-10-27

## 1. Objetivo del Módulo

Este es un módulo fundamental que actúa como el cerebro del laboratorio. Permite definir, configurar y administrar todos los análisis, pruebas y perfiles que el laboratorio ofrece. Su correcta configuración es crucial para la creación de órdenes, el procesamiento de muestras y la generación de reportes de resultados.

## 2. Acceso y Permisos

-   **Ruta de Acceso:** Se accede al módulo a través de la URL `[URL_DEL_SISTEMA]/labotests`.
-   **Permisos Requeridos:** El usuario debe tener un rol de **Empleado** con el permiso `'labotests'` asignado.

## 3. Funcionalidades

### 3.1. Pantalla Principal (Listado de Pruebas y Perfiles)

Muestra una tabla con todas las pruebas y perfiles disponibles, permitiendo una gestión centralizada.

#### Componentes de la Pantalla:

-   **Botón "Agregar Prueba":** Inicia el proceso para crear una nueva prueba individual o un perfil.
-   **Filtros y Búsqueda:** Permite buscar pruebas por nombre o filtrar por categoría (ej. Hematología, Química, Microbiología).
-   **Tabla de Pruebas:**
    -   **Columnas:** Nombre, Categoría, Tipo (Prueba/Perfil), Precio de Venta, Acciones.
    -   **Acciones por Fila:**
        -   **Editar:** Abre el formulario para modificar la configuración de la prueba o perfil.
        -   **Eliminar:** Elimina la prueba del catálogo (usualmente con una confirmación y validación para no eliminar pruebas en uso).

### 3.2. Formulario de Prueba (Crear/Editar)

Este es un formulario complejo con varias secciones para definir todos los aspectos de un análisis.

#### Pestaña 1: Información General

-   `Nombre de la Prueba`: Nombre completo que verá el paciente (ej. "Hemograma Completo").
-   `Nombre Corto`: Abreviatura para uso interno o en etiquetas.
-   `Categoría`: Agrupación funcional (ej. "Química Sanguínea").
-   `Tipo`:
    -   **Prueba Simple:** Un único análisis.
    -   **Perfil:** Un conjunto de pruebas simples.
-   `Precio de Venta`: Costo para el paciente.
-   `Indicaciones para el Paciente`: Instrucciones pre-analíticas (ej. "Ayuno de 8 horas").

#### Pestaña 2: Parámetros y Valores de Referencia (para Pruebas Simples)

-   Permite definir los analitos que se miden en la prueba.
-   **Por cada parámetro:**
    -   `Nombre del Parámetro` (ej. "Glucosa").
    -   `Unidad de Medida` (ej. "mg/dL").
    -   `Tabla de Valores de Referencia`: Se pueden definir múltiples rangos según **género** y **edad** (ej. Adulto Masculino, 0-1 años, etc.).
    -   `Tipo de Resultado`: Numérico, Texto, Lista desplegable.

#### Pestaña 3: Pruebas Incluidas (para Perfiles)

-   Un buscador permite seleccionar y agregar múltiples pruebas simples para conformar el perfil.
-   Muestra un listado de las pruebas ya incluidas en el perfil.

#### Pestaña 4: Plantilla de Reporte

-   Permite asociar una plantilla de diseño específica para la impresión del resultado de esta prueba.

## 4. Componentes Técnicos

### 4.1. Rutas (Routes)

-   `GET /labotests`: Muestra el listado de pruebas (`index`).
-   `POST /labotests`: Procesa la creación de una nueva prueba (`create`).
-   `GET /labotests/(:num)/edit`: Muestra el formulario de edición (`edit`).
-   `POST /labotests/(:num)`: Procesa la actualización de una prueba (`update`).
-   `DELETE /labotests/(:num)`: Elimina una prueba (`delete`).

### 4.2. Controlador (`App/Controllers/Labotests.php`)

Orquesta toda la lógica del módulo. Contiene métodos para mostrar las vistas, procesar los formularios, y manejar las complejas relaciones entre pruebas, perfiles y parámetros.

### 4.3. Modelos

-   **`LabotestModel.php`:** Modelo principal que gestiona la tabla `labotests` (información general de la prueba/perfil).
-   **`LabotestParameterModel.php`:** Gestiona los parámetros (analitos) asociados a una prueba simple.
-   **`ReferenceValueModel.php`:** Gestiona los valores de referencia (rangos por edad/género) asociados a cada parámetro.
-   **`ProfileTestsModel.php`:** Tabla pivote que relaciona un perfil con las múltiples pruebas simples que lo componen.

### 4.4. Vistas (`app/Views/labotests/`)

-   `index.php`: Vista principal con la tabla de pruebas.
-   `form.php`: Vista que contiene el formulario con pestañas para crear/editar.
-   `_parameters.php` (parcial): Fragmento de vista para gestionar dinámicamente los parámetros de una prueba.
-   `_profile_tests.php` (parcial): Fragmento de vista para gestionar las pruebas de un perfil.

## 5. Reportes Relacionados

-   **Catálogo de Precios:** Un listado completo de todas las pruebas y perfiles con sus precios de venta para el público o para convenios.
-   **Catálogo Técnico de Pruebas:** Un reporte detallado con todas las pruebas, sus parámetros, unidades y valores de referencia.

---

# Manual del Módulo: Cotizaciones (Toquotes)

**Versión:** 1.0
**Fecha:** 2023-10-27

## 1. Objetivo del Módulo

Permitir al personal del laboratorio crear, gestionar y enviar cotizaciones formales de análisis clínicos a pacientes o clientes potenciales. Estas cotizaciones pueden ser consultadas, impresas en formato PDF y, si el cliente acepta, convertidas directamente en una orden de laboratorio (`Registro`), agilizando el flujo de trabajo.

## 2. Acceso y Permisos

-   **Ruta de Acceso:** Se accede al módulo a través de la URL `[URL_DEL_SISTEMA]/toquotes`.
-   **Permisos Requeridos:** El usuario debe tener el permiso `'toquotes'` asignado para operar en este módulo.

## 3. Funcionalidades

### 3.1. Pantalla Principal (Listado de Cotizaciones)

Muestra una tabla con todas las cotizaciones generadas, permitiendo un seguimiento rápido de su estado.

#### Componentes de la Pantalla:

-   **Botón "Nueva Cotización":** Dirige al formulario para crear una nueva cotización.
-   **Tabla de Cotizaciones:**
    -   **Columnas:** Folio, Fecha, Paciente, Monto Total, Estado (Pendiente, Aceptada, Expirada), Acciones.
    -   **Acciones por Fila:**
        -   **Ver/Imprimir PDF:** Genera y muestra la cotización en formato PDF.
        -   **Editar:** Abre la cotización para modificarla.
        -   **Eliminar:** Borra la cotización.
        -   **Convertir a Registro:** Inicia el proceso para crear una orden de laboratorio a partir de la cotización.

### 3.2. Formulario de Cotización (Crear/Editar)

Interfaz para construir la cotización.

#### Secciones del Formulario:

-   **Datos del Paciente:** Permite buscar un paciente ya registrado o ingresar los datos básicos (Nombre, Email) de un cliente nuevo para la cotización.
-   **Detalle de Pruebas:**
    -   Un campo de búsqueda permite añadir pruebas y perfiles del **Catálogo de Pruebas**.
    -   Cada prueba añadida se lista en una tabla con su precio unitario. Se pueden aplicar descuentos por ítem o al total.
-   **Totales y Validez:**
    -   Muestra el subtotal, descuentos aplicados y el monto total.
    -   Campo `Válida hasta` para definir la fecha de expiración de la oferta.
-   **Acciones del Formulario:**
    -   **Guardar:** Almacena la cotización en el sistema.
    -   **Guardar y Enviar:** Guarda la cotización y la envía por correo electrónico al paciente (si se proporcionó un email).

### 3.3. Conversión a Registro

Al seleccionar "Convertir a Registro", el sistema toma los datos del paciente y las pruebas de la cotización y pre-carga el formulario de una nueva orden de laboratorio, ahorrando tiempo y evitando errores de transcripción.

## 4. Componentes Técnicos

### 4.1. Rutas (Routes)

-   `GET /toquotes`: Muestra el listado de cotizaciones (`index`).
-   `GET /toquotes/new`: Muestra el formulario de creación (`new`).
-   `POST /toquotes`: Procesa la creación de una nueva cotización (`create`).
-   `GET /toquotes/(:num)/pdf`: Genera y descarga el PDF de la cotización (`generatePdf`).
-   `POST /toquotes/(:num)/to_register`: Convierte la cotización en una orden (`convertToRegister`).

### 4.2. Controlador (`App/Controllers/Toquotes.php`)

Orquesta la lógica del módulo, incluyendo la búsqueda de pruebas, el cálculo de totales, la interacción con el modelo y la generación del PDF.

### 4.3. Modelo (`App/Models/ToquoteModel.php`)

Gestiona la persistencia de los datos en la base de datos.
-   Interactúa con dos tablas principales: `toquotes` (para la cabecera de la cotización) y `toquote_items` (para las pruebas incluidas).

### 4.4. Vistas (`app/Views/toquotes/`)

-   `index.php`: Vista principal con el listado.
-   `form.php`: Formulario de creación/edición con la lógica de búsqueda de pruebas.
-   `pdf_template.php`: Plantilla HTML que sirve de base para generar el documento PDF.

## 5. Reportes Relacionados

-   **Reporte de Tasa de Conversión:** Mide la efectividad del proceso de cotización (Cotizaciones vs. Órdenes generadas).
-   **Reporte de Ventas Potenciales:** Sumariza los montos de las cotizaciones pendientes en un período.

---

# Manual del Módulo: Registros (Órdenes)

**Versión:** 1.0
**Fecha:** 2023-10-27

## 1. Objetivo del Módulo

El módulo de Registros (u Órdenes) es el corazón operativo del sistema. Centraliza todo el flujo de trabajo de un paciente en el laboratorio, desde la creación de la solicitud de análisis hasta la entrega del reporte de resultados. Gestiona la selección de pruebas, la toma de muestras, la captura de resultados, la validación, la facturación y la generación de reportes.

## 2. Acceso y Permisos

-   **Ruta de Acceso:** `[URL_DEL_SISTEMA]/registers`
-   **Permisos Requeridos:** El permiso `'registers'` es necesario para acceder. Se pueden requerir permisos adicionales para acciones específicas como validar resultados o anular órdenes.

## 3. Flujo de Trabajo y Estados de la Orden

Una orden de laboratorio pasa por varios estados a lo largo de su ciclo de vida:

1.  **Pendiente:** La orden ha sido creada, pero aún no se han capturado los resultados.
2.  **En Proceso:** Se ha comenzado a ingresar al menos un resultado.
3.  **Validado:** Todos los resultados han sido ingresados y un profesional autorizado los ha validado.
4.  **Entregado:** El reporte de resultados ha sido impreso y/o entregado al paciente.
5.  **Anulado:** La orden ha sido cancelada y no se procesará.

## 4. Funcionalidades

### 4.1. Pantalla Principal (Listado de Registros)

Muestra una tabla con todas las órdenes de laboratorio, permitiendo un seguimiento en tiempo real del trabajo del laboratorio.

#### Componentes de la Pantalla:

-   **Botón "Nuevo Registro":** Inicia el proceso de creación de una nueva orden.
-   **Filtros:** Permiten buscar órdenes por Folio, Nombre del Paciente, Cédula, Rango de Fechas o Estado.
-   **Tabla de Registros:**
    -   **Columnas:** Folio, Fecha, Paciente, Pruebas, Monto, Estado, Acciones.
    -   **Acciones por Fila:**
        -   **Capturar Resultados:** Abre la interfaz para ingresar los resultados de las pruebas.
        -   **Ver/Imprimir Reporte:** Genera el PDF del reporte de resultados (disponible para órdenes validadas).
        -   **Editar:** Permite modificar datos de la orden (paciente, médico, pruebas) si el estado lo permite.
        -   **Pagos:** Abre la gestión de pagos de la orden.
        -   **Imprimir Etiquetas:** Genera etiquetas con código de barras para los tubos de muestra.

### 4.2. Formulario de Creación/Edición de Registro

Interfaz para crear una nueva orden de laboratorio.

#### Secciones del Formulario:

-   **Datos del Paciente:**
    -   Permite buscar un paciente existente por nombre o cédula.
    -   Si el paciente no existe, un botón permite crearlo "al vuelo" sin salir del formulario.
-   **Datos de la Orden:**
    -   `Médico Referente`: Campo opcional para asociar un doctor del módulo de Doctores.
    -   `Fecha de Toma de Muestra`.
-   **Selección de Pruebas:**
    -   Un campo de búsqueda permite añadir pruebas y perfiles del **Catálogo de Pruebas**.
    -   Las pruebas seleccionadas se listan en una tabla con su precio.
-   **Resumen y Pago:**
    -   Muestra el subtotal, permite aplicar descuentos (porcentuales o fijos) y calcula el total.
    -   Permite registrar el pago inicial (total o parcial).

Al guardar, el sistema genera un **Folio** único para la orden.

### 4.3. Pantalla de Captura de Resultados

Esta es la interfaz utilizada por el personal técnico para ingresar los resultados de los análisis.

-   **Estructura:** La pantalla se organiza por prueba. Para cada prueba, se listan los parámetros a medir.
-   **Ingreso de Datos:**
    -   Para resultados **numéricos**, se muestra el valor de referencia (rango) según la edad y género del paciente. El sistema resalta automáticamente los resultados fuera de rango.
    -   Para resultados de **texto** o **lista**, se presenta un campo de texto o un selector.
    -   Para pruebas de **cultivo**, se muestra una matriz para ingresar antibióticos y sensibilidad (S, I, R).
-   **Acciones:**
    -   **Guardar Parcialmente:** Permite guardar el progreso.
    -   **Validar Resultados:** Un usuario con permisos especiales (ej. un bacteriólogo) puede revisar y validar los resultados. Una vez validada, la orden queda bloqueada para edición y lista para imprimir el reporte.

### 4.4. Generación de Reporte (PDF)

Una vez la orden está validada, se puede generar el reporte final en formato PDF.

-   **Contenido:** El PDF incluye los datos del laboratorio, del paciente, del médico, y una tabla con los resultados de las pruebas, sus valores de referencia, unidades y cualquier observación.
-   **Diseño:** El diseño del reporte (logo, encabezado, pie de página) se gestiona desde el módulo de **Configuración**.

## 5. Componentes Técnicos

### 5.1. Rutas (Routes)

-   `GET /registers/lista`: Muestra el listado de órdenes.
-   `GET /registers/new`: Muestra el formulario de creación.
-   `POST /registers`: Procesa la creación de una nueva orden.
-   `GET /registers/capture/(:num)`: Muestra la pantalla de captura de resultados.
-   `POST /registers/save_results`: Guarda los resultados ingresados.
-   `POST /registers/validate/(:num)`: Valida los resultados de una orden.
-   `GET /registers/report/(:num)`: Genera el PDF del reporte.

### 5.2. Controlador (`App/Controllers/Registers.php`)

Es el controlador más grande y complejo del sistema. Orquesta toda la lógica de negocio: creación de órdenes, carga de pruebas y parámetros, cálculo de precios, guardado y validación de resultados, y la invocación al servicio de generación de PDF.

### 5.3. Modelos

-   **`RegisterModel.php`:** Gestiona la tabla principal `registers` (la cabecera de la orden).
-   **`RegisterItemsModel.php`:** Gestiona las pruebas asociadas a cada orden.
-   **`RegisterValuesModel.php`:** Almacena el resultado de cada parámetro para cada prueba de una orden. Es una de las tablas más grandes de la base de datos.
-   **`PaymentModel.php`:** Gestiona los pagos asociados a una orden.

### 5.4. Servicios

-   **`RegistroFolioService.php`:** Lógica especializada para generar números de folio únicos y consecutivos, posiblemente con un formato específico (ej. `AÑO-MES-CONTADOR`).
-   **`PdfService.php` (o similar):** Servicio que utiliza una librería (como DomPDF) para convertir una vista HTML en un documento PDF.

### 5.5. Vistas (`app/Views/registers/`)

-   `list.php`: Vista principal con el listado y filtros.
-   `form.php`: Formulario de creación/edición de la orden.
-   `capture.php`: Interfaz para la captura de resultados.
-   `report_template.php`: Plantilla HTML que se usa para generar el PDF del reporte.

## 6. Reportes Relacionados

-   **Reporte de Producción:** Cantidad de pruebas procesadas por día/semana/área.
-   **Reporte de Tiempos de Entrega (TAT):** Mide el tiempo desde la creación de la orden hasta la validación.
-   **Reporte Financiero de Ingresos:** Utiliza los datos de pagos de este módulo.
-   **Reportes Epidemiológicos:** Analizan la frecuencia de resultados patológicos.

---

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

---

# Manual del Módulo: Reportes

**Versión:** 1.0
**Fecha:** 2023-10-27

## 1. Objetivo del Módulo

El módulo de Reportes es una herramienta de inteligencia de negocio que permite a los administradores y personal autorizado extraer, visualizar y exportar información consolidada de todas las áreas del sistema. Su objetivo es proporcionar una visión clara del rendimiento operativo, financiero y estadístico del laboratorio para facilitar la toma de decisiones.

## 2. Acceso y Permisos

-   **Ruta de Acceso:** `[URL_DEL_SISTEMA]/reports`
-   **Permisos Requeridos:** Requiere el permiso `'reports'`. Es un módulo sensible, por lo que usualmente se asigna solo a roles de gerencia, administración o contabilidad.

## 3. Funcionalidades

### 3.1. Pantalla Principal (Menú de Reportes)

Al ingresar al módulo, se presenta un menú o un listado de todos los reportes disponibles, agrupados por categorías para facilitar su localización.

#### Categorías de Reportes (Ejemplos):

-   **Reportes Financieros y de Ventas**
-   **Reportes Operativos y de Producción**
-   **Reportes Estadísticos y Epidemiológicos**
-   **Reportes de Gestión y Auditoría**

### 3.2. Generador de Reportes

Cada reporte tiene su propia interfaz de generación, pero la mayoría sigue un patrón común:

1.  **Filtros:** El usuario selecciona los parámetros para acotar la información. El filtro más común es el **Rango de Fechas**. Otros filtros pueden ser: por paciente, por médico, por tipo de prueba, por empleado, etc.
2.  **Generación:** Un botón "Generar" o "Consultar" ejecuta la consulta a la base de datos.
3.  **Visualización:** Los resultados se muestran en una tabla en pantalla.
4.  **Exportación:** Se ofrecen botones para exportar los datos mostrados a formatos como **Excel (CSV)** o **PDF**.

### 3.3. Listado de Reportes Disponibles (Estimado)

A continuación se listan los reportes más importantes que se pueden generar:

#### Financieros:

-   **Corte de Caja / Ingresos Diarios:** Muestra todos los pagos recibidos en un período, desglosados por método de pago.
-   **Ventas por Prueba/Perfil:** Ranking de las pruebas que más ingresos generan.
-   **Cuentas por Cobrar:** Listado de órdenes con saldo pendiente de pago.
-   **Reporte de Comisiones a Médicos:** (Relacionado con el módulo de Comisiones).

#### Operativos:

-   **Producción por Área:** Volumen de pruebas realizadas, agrupadas por categoría (Química, Hematología, etc.).
-   **Tiempos de Entrega (TAT - Turnaround Time):** Mide la eficiencia del laboratorio, calculando el tiempo promedio desde la creación de la orden hasta la validación del resultado.
-   **Resultados Pendientes:** Lista de órdenes que aún no han sido validadas.
-   **Consumo de Inventario:** Reporte que muestra qué reactivos se han consumido en un período (relacionado con el módulo de Inventario).

#### Estadísticos:

-   **Reporte Epidemiológico:** Permite consultar la frecuencia de resultados (normales vs. patológicos) para un analito específico en un período, pudiendo filtrar por edad o género.
-   **Demografía de Pacientes:** Gráficos y tablas sobre la distribución de pacientes por edad y género.

## 4. Componentes Técnicos

### 4.1. Controlador (`App/Controllers/Reports.php`)

Contiene un método para cada reporte. Por ejemplo: `daily_cash_flow()`, `sales_by_test()`, `turnaround_time()`. Cada método se encarga de:
1.  Mostrar la vista con los filtros.
2.  Si se envía el formulario, recibir los filtros, llamar al modelo correspondiente para obtener los datos y pasar los resultados a la vista.

### 4.2. Modelos

-   Este módulo no suele tener un único `ReportModel`. En su lugar, el `Reports.php` (controlador) invoca métodos especializados que pueden estar en los modelos de otros módulos (`RegisterModel`, `PaymentModel`, `CustomerModel`, etc.) o en un modelo de reportes (`ReportModel.php`) que contiene consultas SQL complejas que unen múltiples tablas.

### 4.3. Vistas (`app/Views/reports/`)

-   `index.php`: El menú principal de reportes.
-   Una vista por cada reporte, ej: `daily_cash_flow_view.php`. Estas vistas contienen el formulario de filtros y la tabla para mostrar los resultados.
-   Vistas de exportación (ej. `_daily_cash_flow_excel.php`) que generan un formato más simple, apto para ser convertido a CSV.

---

# Manual del Módulo: Configuración

**Versión:** 1.0
**Fecha:** 2023-10-27

## 1. Objetivo del Módulo

El módulo de Configuración es el panel de control central para personalizar y ajustar el comportamiento general del sistema. Permite a los administradores modificar parámetros clave que afectan a toda la aplicación, desde la información de la empresa hasta la integración con servicios externos y el diseño de los reportes.

## 2. Acceso y Permisos

-   **Ruta de Acceso:** `[URL_DEL_SISTEMA]/config`
-   **Permisos Requeridos:** Este es un módulo de alta sensibilidad. Requiere el permiso `'config'`, que debe ser asignado exclusivamente a roles de **Administrador del Sistema**.

## 3. Funcionalidades

La interfaz de este módulo se presenta como un único formulario extenso, dividido en pestañas o secciones para organizar las diferentes áreas de configuración.

### 3.1. Pestaña: Información de la Empresa

-   `Nombre de la Empresa/Laboratorio`: El nombre que aparecerá en los encabezados de los reportes y en toda la interfaz.
-   `Dirección`: Dirección física del laboratorio.
-   `Teléfono(s)`: Números de contacto.
-   `Email`: Correo electrónico principal.
-   `Sitio Web`: URL del sitio web del laboratorio.
-   `Logo de la Empresa`: Permite subir el archivo de imagen del logo que se usará en los reportes PDF y en la interfaz del sistema.

### 3.2. Pestaña: Reportes y Plantillas

-   `Encabezado de Reporte`: Un editor de texto enriquecido para diseñar el encabezado que aparecerá en todos los reportes de resultados.
-   `Pie de Página de Reporte`: Editor para el pie de página, usualmente para información de contacto, acreditaciones o firmas digitales.
-   `Formato de Fecha/Hora`: Permite seleccionar cómo se mostrarán las fechas y horas en todo el sistema.
-   `Mostrar Precios en Orden de Trabajo`: Un checkbox para decidir si los costos de las pruebas se imprimen en la orden de trabajo interna.

### 3.3. Pestaña: Parámetros del Sistema

-   `Moneda`: Símbolo de la moneda a utilizar (ej. $, Bs., €).
-   `Zona Horaria`: Configuración de la zona horaria del servidor para asegurar que todas las fechas se registren correctamente.
-   `Días de Alerta de Vencimiento (Inventario)`: Número de días de antelación para que el sistema alerte sobre reactivos próximos a vencer.

### 3.4. Pestaña: Integraciones (Opcional)

-   `API Key de WhatsApp`: Si el sistema se integra con un servicio de envío de WhatsApp, aquí se configura la clave de la API.
-   `Credenciales de Email (SMTP)`: Configuración para el envío de correos electrónicos (cotizaciones, resultados).

## 4. Componentes Técnicos

### 4.1. Controlador (`App/Controllers/Config.php`)

-   `index()`: Muestra el formulario de configuración, cargando todos los valores actuales desde la base de datos.
-   `save()`: Recibe todos los datos del formulario, los valida y los guarda en la tabla de configuración. También maneja la subida y guardado del archivo del logo.

### 4.2. Modelo (`App/Models/AppConfigModel.php`)

-   Gestiona la tabla `app_config`, que es una tabla de tipo clave-valor.
-   `getValue($key)`: Obtiene el valor de una clave de configuración específica.
-   `saveValue($key, $value)`: Guarda o actualiza el valor de una clave.
-   `getAllAsArray()`: Devuelve todas las configuraciones como un array asociativo, útil para cargar el formulario.

### 4.3. Vistas (`app/Views/config/`)

-   `index.php`: La vista principal que contiene el formulario con todas las pestañas y campos de configuración.

---

# Manual del Módulo: Control de Calidad

**Versión:** 1.0
**Fecha:** 2023-10-27

## 1. Objetivo del Módulo

Asegurar la precisión y fiabilidad de los resultados del laboratorio mediante la monitorización continua del rendimiento de los análisis. Este módulo permite registrar y analizar los resultados de los materiales de control (controles de calidad), aplicar reglas de Westgard para detectar desviaciones y generar gráficos de Levey-Jennings para la visualización de tendencias.

## 2. Acceso y Permisos

-   **Ruta de Acceso:** `[URL_DEL_SISTEMA]/quality_control`
-   **Permisos Requeridos:** Requiere el permiso `'quality_control'`, asignado a personal técnico especializado, jefes de laboratorio o responsables de calidad.

## 3. Funcionalidades

### 3.1. Gestión de Lotes de Control

Permite definir los materiales de control que se utilizarán en el laboratorio.

-   **Funcionalidad:** CRUD para lotes de control.
-   **Campos por Lote:**
    -   `Nombre del Control` (ej. "Control de Química Nivel I").
    -   `Número de Lote`.
    -   `Fecha de Vencimiento`.
    -   `Analitos Incluidos`: Se asocian los parámetros (analitos) que este control mide. Para cada analito, se define la **Media (Target)** y la **Desviación Estándar (DE)** proporcionadas por el fabricante o establecidas internamente.

### 3.2. Ingreso de Resultados de Control

Interfaz para que el personal técnico registre los resultados obtenidos al procesar los materiales de control.

-   **Flujo de Trabajo:**
    1.  El usuario selecciona el **Lote de Control** y el **Analito** a registrar.
    2.  Ingresa el **valor obtenido** en el análisis.
    3.  El sistema guarda el resultado con la fecha y hora.

### 3.3. Gráficos de Levey-Jennings y Reglas de Westgard

Esta es la herramienta principal de análisis del módulo.

-   **Generación:** El usuario selecciona un **Lote de Control**, un **Analito** y un **Rango de Fechas**.
-   **Visualización:**
    -   Se genera un gráfico de Levey-Jennings que muestra los resultados de control a lo largo del tiempo.
    -   En el gráfico se trazan las líneas correspondientes a la Media, y a ±1DE, ±2DE y ±3DE.
    -   El sistema analiza automáticamente los puntos del gráfico y resalta las violaciones a las **Reglas de Westgard** (ej. 1-3s, 2-2s, R-4s, etc.).
-   **Acciones Correctivas:** Permite al usuario registrar una nota o acción correctiva asociada a una violación de las reglas, asegurando la trazabilidad de la gestión de calidad.

## 4. Componentes Técnicos

### 4.1. Rutas (Routes)

-   `GET /quality_control`: Dashboard y acceso a las funcionalidades.
-   `POST /quality_control/lots`: Creación de un nuevo lote de control.
-   `POST /quality_control/results`: Registro de un nuevo resultado de control.
-   `GET /quality_control/levey_jennings`: Genera y muestra el gráfico de Levey-Jennings.

### 4.2. Controlador (`App/Controllers/QualityControl.php`)

-   Gestiona la lógica del módulo, incluyendo la creación de lotes, el registro de resultados y la preparación de los datos para los gráficos.
-   Contiene la implementación de las **Reglas de Westgard** para analizar la serie de datos de control.

### 4.3. Modelos

-   **`QualityControlLotModel.php`:** Gestiona la tabla de lotes de control y sus analitos asociados con sus medias y DE.
-   **`QualityControlResultModel.php`:** Almacena cada resultado de control ingresado, con su fecha, valor, lote y analito.

### 4.4. Vistas (`app/Views/quality_control/`)

-   `index.php`: Vista principal para gestionar lotes.
-   `results_form.php`: Formulario para el ingreso de resultados.
-   `levey_jennings.php`: Vista que contiene el gráfico (usualmente generado con una librería como Chart.js) y la tabla de violaciones de reglas.

---

# Manual del Módulo: Inventario (Reactivos)

**Versión:** 1.0
**Fecha:** 2023-10-27

## 1. Objetivo del Módulo

Gestionar de forma integral el ciclo de vida de los reactivos, insumos y materiales del laboratorio. El módulo permite controlar el stock, registrar entradas y salidas, monitorear fechas de vencimiento y gestionar la información de proveedores, asegurando la trazabilidad y evitando quiebres de stock.

## 2. Acceso y Permisos

-   **Ruta de Acceso:** Se accede al módulo a través de la URL `[URL_DEL_SISTEMA]/inventory`.
-   **Permisos Requeridos:** El usuario debe tener un rol de **Empleado** con el permiso `'inventory'` asignado.

## 3. Funcionalidades

### 3.1. Dashboard de Inventario

Es la pantalla de inicio del módulo y ofrece una vista rápida del estado actual del inventario.

-   **Alertas:** Tarjetas o notificaciones destacadas para:
    -   Reactivos con stock por debajo del punto de reorden.
    -   Lotes de reactivos próximos a vencer.
-   **Gráficos:** Resúmenes visuales del valor del inventario o consumo por categoría.

### 3.2. Listado de Ítems de Inventario

Presenta una tabla con todos los productos (reactivos e insumos) definidos en el sistema.

#### Componentes de la Pantalla:

-   **Botón "Agregar Ítem":** Lleva al formulario para definir un nuevo producto.
-   **Filtros:** Permiten acotar la lista por categoría (ej. Química, Hematología) o proveedor.
-   **Tabla de Ítems:**
    -   **Columnas:** Nombre del Ítem, Categoría, Proveedor, Stock Total, Unidad.
    -   **Acciones por Fila:**
        -   **Editar:** Modifica la información general del ítem (no el stock).
        -   **Ver Lotes/Movimientos:** Navega a una vista detallada de todos los lotes y movimientos de stock para ese ítem.

### 3.3. Gestión de Lotes y Movimientos de Stock

Esta es la funcionalidad central para el control de existencias. No es una única pantalla, sino un conjunto de acciones.

-   **Registrar Entrada (Compra):**
    -   Se selecciona un ítem de inventario existente.
    -   Se ingresan los datos del nuevo lote: **Número de Lote**, **Cantidad recibida**, **Fecha de Vencimiento**, y opcionalmente, costo y fecha de compra.
    -   Esta acción incrementa el stock total del ítem.

-   **Registrar Salida (Uso/Ajuste):**
    -   Se selecciona un lote específico de un ítem.
    -   Se ingresa la **Cantidad a descontar** y un motivo (ej. "Uso diario", "Ajuste por pérdida").
    -   Esta acción decrementa el stock del lote y, por ende, el stock total del ítem.

### 3.4. Gestión de Proveedores

Es un sub-módulo que permite mantener un directorio de los proveedores de insumos.

-   **Funcionalidad:** CRUD (Crear, Leer, Actualizar, Eliminar) para proveedores.
-   **Campos:** Nombre del Proveedor, Contacto, Teléfono, Email, Dirección.

## 4. Componentes Técnicos

### 4.1. Rutas (Routes)

-   `GET /inventory`: Muestra el dashboard y listado de ítems (`index`).
-   `POST /inventory`: Crea un nuevo ítem de inventario (`create`).
-   `GET /inventory/suppliers`: Gestiona los proveedores (`suppliersIndex`).
-   `POST /inventory/stock_in`: Registra una entrada de stock para un ítem (`stockIn`).
-   `POST /inventory/stock_out`: Registra una salida de stock de un lote (`stockOut`).
-   `GET /inventory/(:num)/movements`: Muestra el historial de movimientos de un ítem (`viewMovements`).

### 4.2. Controlador (`App/Controllers/Inventory.php`)

Contiene la lógica para todas las operaciones de inventario. Métodos como `index()`, `createItem()`, `stockIn()`, `stockOut()`, `suppliersIndex()`, etc.

### 4.3. Modelos

-   **`InventoryItemModel.php`:** Gestiona la tabla de productos/ítems (la definición general).
-   **`InventoryLotModel.php`:** Gestiona la tabla de lotes, donde cada registro tiene un `item_id`, número de lote, cantidad y fecha de vencimiento.
-   **`InventoryMovementModel.php`:** Registra cada entrada y salida en una tabla de auditoría de movimientos, referenciando al lote afectado.
-   **`SupplierModel.php`:** Gestiona el CRUD de la tabla de proveedores.

### 4.4. Vistas (`app/Views/inventory/`)

-   `index.php`: Dashboard y listado principal.
-   `item_form.php`: Formulario para crear/editar un ítem.
-   `movements.php`: Vista detallada de lotes y movimientos de un ítem.
-   `stock_in_modal.php`: Modal para registrar entradas.
-   `stock_out_modal.php`: Modal para registrar salidas.
-   `suppliers.php`: Vista para la gestión de proveedores.

### 4.5. Reportes Relacionados

-   Reporte de Stock Bajo (ítems por debajo del punto de reorden).
-   Reporte de Próximos a Vencer.
-   Reporte de Consumo de Reactivos por período.
-   Reporte de Valorización de Inventario.

---

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

---

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