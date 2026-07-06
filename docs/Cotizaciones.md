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