# Sistema de Comisiones para Doctores

## Descripción
Este sistema permite configurar un porcentaje de comisión para cada doctor, el cual se aplicará automáticamente sobre todas las pruebas realizadas con su referencia.

## Instalación

### 1. Ejecutar la migración de base de datos
```sql
-- Ejecutar el archivo: database/migration_doctors_commission.sql
```

Esto agregará:
- Campo `commission_percent` a la tabla `doctors`
- Tabla `doctor_commissions` para registrar las comisiones generadas

### 2. Funcionalidades implementadas

#### Configuración de comisión por doctor
- En el formulario de doctor (`/doctors/view/{id}`) se agregó un campo para ingresar el porcentaje de comisión
- El valor acepta decimales (ej: 10.5) y va de 0 a 100%
- La comisión es opcional (por defecto 0%)

#### Cálculo automático de comisiones
- Cuando se registra una nueva prueba (`Registers::save()`), el sistema verifica si el doctor tiene comisión configurada
- Si tiene comisión > 0, se crea automáticamente un registro en `doctor_commissions`
- El cálculo: `monto_comisión = (total_prueba * porcentaje_comisión) / 100`

#### Panel de comisiones para doctores
- En el dashboard del doctor (`/doctor/home`) se muestra:
  - **Comisiones Pendientes**: Total y cantidad de comisiones no pagadas
  - **Comisiones Pagadas**: Total y cantidad de comisiones ya pagadas  
  - **Total Comisiones**: Acumulado total de todas las comisiones
  - **Comisiones Recientes**: Tabla con las últimas 5 comisiones generadas

## Uso

### Para configurar comisión de un doctor:
1. Ir a `Doctores` → seleccionar doctor → `Editar`
2. En el campo "Comisión (%)" ingresar el porcentaje deseado
3. Guardar cambios

### Para ver comisiones (vista del doctor):
1. Iniciar sesión como doctor
2. En el dashboard principal ver las tarjetas de resumen de comisiones
3. En la tabla "Comisiones Recientes" ver el detalle de las últimas generadas

## Estructura de tablas

### doctors (nuevo campo)
```sql
commission_percent DECIMAL(5,2) DEFAULT 0.00
```

### doctor_commissions (nueva tabla)
- `commission_id`: ID único
- `doctor_id`: ID del doctor
- `registro_id`: ID del registro/prueba
- `total_amount`: Monto total de la prueba
- `commission_percent`: Porcentaje aplicado
- `commission_amount`: Monto calculado de comisión
- `created_date`: Fecha de creación
- `paid_date`: Fecha de pago (cuando se marca como pagada)
- `status`: 0=pendiente, 1=pagado
- `notes`: Notas adicionales

## Modelos y Controladores modificificados

### Modelos:
- `DoctorModel`: Agregados métodos para manejar `commission_percent`
- `DoctorCommissionModel`: Nuevo modelo para gestionar comisiones

### Controladores:
- `Doctors`: Agregado guardado de `commission_percent`
- `Registers`: Agregada creación automática de comisiones
- `DoctorHome`: Agregada visualización de comisiones en dashboard

### Vistas:
- `doctors/form_basic_info.php`: Agregado campo de comisión
- `doctor/dashboard.php`: Agregado panel de comisiones

## Notas importantes
- El sistema es compatible con instalaciones existentes
- Si la tabla `doctor_commissions` no existe, el sistema funciona normalmente sin generar comisiones
- Las comisiones se calculan solo cuando el doctor tiene un porcentaje > 0 configurado
- El panel de comisiones solo se muestra si existen datos disponibles
