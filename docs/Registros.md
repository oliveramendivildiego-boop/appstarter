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