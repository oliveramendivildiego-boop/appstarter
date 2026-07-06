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