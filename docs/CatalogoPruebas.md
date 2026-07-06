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