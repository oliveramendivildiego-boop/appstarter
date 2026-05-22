# Migraciones de Base de Datos

## Situación actual

El proyecto utiliza scripts SQL manuales en `database/*.sql` para cambios de esquema.
No existe aún un sistema unificado de migraciones CI4.

## Scripts existentes (orden sugerido de ejecución)

| Orden | Archivo | Descripción |
|-------|---------|-------------|
| 1 | `migration_inventario_insumos.sql` | Tablas reactivo, reactivo_lote, movimientos |
| 2 | `add_registro_id_reactivo_movimiento.sql` | Columna registro_id en movimientos |
| 3 | `migration_doctors_login.sql` | Acceso de doctores |
| 4 | `migration_poblacion_edad.sql` | Grupos de población |
| 5 | `insert_poblaciones_edad.sql` | Datos iniciales de población |
| 6 | `migration_formulas_expresion.sql` / `migration_formula_expresion.sql` | Fórmulas con expresión |
| 7 | `migration_secanacategoria_sexo.sql` | Sexo en sub-clases |
| 8 | `migration_secanacategoria_orden.sql` | Orden en sub-clases |
| 9 | `migration_priresultados_sexo.sql` | Sexo en resultados primarios |
| 10 | `migration_pago_abono.sql` | Abonos de pago |
| 11 | `migration_tipopago_pendiente.sql` | Tipo de pago |
| 12 | `migrations_modulos_2025.sql` | Módulos y permisos |
| 13 | `add_modulos_nuevos.sql` | Módulos adicionales |
| 14 | `migration_address_1_people.sql` | Dirección del paciente (`dom_people.address_1`) |

**Equivalente CI4:** `php spark migrate` → `2026-05-21-120000_AddAddress1ToPeople.php`

### Otros scripts (datos/fixes)

- `fix_poblacion_encoding.sql` - Corrección de encoding
- `seed_manuals_proveedores.sql` - Datos de manuales y proveedores
- `manuales_completos.sql`, `insertos_reales.sql` - Datos de prueba/manuales

## Migrar a CI4 Migrations (futuro)

Para usar migraciones nativas de CodeIgniter 4:

```bash
# Crear migración
php spark make:migration NombreMigracion

# Ejecutar migraciones
php spark migrate

# Rollback
php spark migrate:rollback
```

Las migraciones se guardarían en `app/Database/Migrations/`.
