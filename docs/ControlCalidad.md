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