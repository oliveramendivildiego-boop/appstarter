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