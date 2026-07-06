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