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