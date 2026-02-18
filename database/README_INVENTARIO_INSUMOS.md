# Migración: Inventario de insumos (presentación vs unidad base)

## Ejecutar

```bash
mysql -u root -p laboratorio < database/migration_inventario_insumos.sql
```

O importar `migration_inventario_insumos.sql` desde phpMyAdmin.

**Nota:** Si alguna columna ya existe (por migración previa), ignore el error "Duplicate column name" para esa línea.

## Contenido

- **dom_reactivo:** nuevas columnas  
  - `grupo`, `subgrupo`  
  - `unidad_base` (ml, prueba, unidad)  
  - `contenido_por_presentacion` (unidades por caja/frasco)  
  - `tipo` (1=reactivo, 2=kit, 3=insumo)  
  - `ubicacion`

- **dom_reactivo_movimiento:** tabla nueva para historial  
  - `reactivo_id`, `tipo` (entrada/salida), `cantidad`  
  - `fecha`, `person_id` (responsable), `lote_id`

## Buenas prácticas aplicadas

- Stock mínimo siempre en **unidad base**
- Alertas por **vencimiento** (30 días)
- Manejo por **lotes** separados
- Registro de **quién consume** en movimientos
- Diferenciación reactivo / kit / insumo
- Descuento por consumo real con FIFO (primer vencimiento primero)
