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