# Lista de cambios — Reportes analíticos avanzados

Fecha: 2026-06-10

## Archivos nuevos

### Backend
| Archivo | Descripción |
|---------|-------------|
| `app/Controllers/ReportsAnalytics.php` | Controlador independiente (10 reportes × HTML/PDF/Excel) |
| `app/Models/ReportAnalyticsModel.php` | Modelo de consultas SELECT optimizadas |
| `app/Commands/ReportsAnalyticsSmoke.php` | Comando CLI de validación |

### Vistas HTML
| Archivo |
|---------|
| `app/Views/reports/analytics/pruebas_mas_solicitadas.php` |
| `app/Views/reports/analytics/tendencia_paciente.php` |
| `app/Views/reports/analytics/valores_criticos.php` |
| `app/Views/reports/analytics/tiempo_entrega.php` |
| `app/Views/reports/analytics/productividad_usuarios.php` |
| `app/Views/reports/analytics/resultados_corregidos.php` |
| `app/Views/reports/analytics/pendientes_validacion.php` |
| `app/Views/reports/analytics/consumo_insumos.php` |
| `app/Views/reports/analytics/proyeccion_insumos.php` |
| `app/Views/reports/analytics/comparativo_mensual.php` |

### Partials analíticos
| Archivo |
|---------|
| `app/Views/reports/analytics/partials/head_assets.php` |
| `app/Views/reports/analytics/partials/report_header.php` |
| `app/Views/reports/analytics/partials/table_tools.php` |
| `app/Views/reports/analytics/partials/table_pagination.php` |

### Vistas PDF
| Archivo |
|---------|
| `app/Views/reports/analytics/pdf/*.php` (10 archivos) |

### Frontend
| Archivo | Descripción |
|---------|-------------|
| `public/js/analytics_table.js` | Buscador, ordenamiento y paginación en cliente |

### Pruebas y documentación
| Archivo |
|---------|
| `tests/perf/analytics_perf_seed.sql` |
| `docs/reports-analytics/*.md` |

## Archivos modificados (sin cambio de funcionalidad clásica)

| Archivo | Cambio |
|---------|--------|
| `app/Config/Routes.php` | 30 rutas GET nuevas para `ReportsAnalytics` |
| `app/Views/reports/listing.php` | Reorganización del menú en 7 grupos lógicos + badges "Nuevo" |

## Archivos NO modificados

- `app/Controllers/Reports.php` — reportes clásicos intactos
- `app/Models/ReportModel.php`
- Procesos de registro, recepción, resultados, facturación, impresión
- APIs existentes
- Módulo de inventario (solo lectura desde analíticos)
- Esquema de base de datos (sin migraciones nuevas)

## Base de datos

- **Sin ALTER/UPDATE/DELETE** en datos productivos.
- **Sin tablas nuevas** en producción.
- Script opcional `tests/perf/analytics_perf_seed.sql` crea BD `laboratorio_perftest` aislada para benchmarks.

## Limitaciones documentadas

1. **Filtro sucursal** en "Pruebas más solicitadas": no implementado (no existe tabla de sucursales; multi-tenant por BD).
2. **Costo estimado** en consumo de insumos: no disponible (catálogo `dom_reactivo` sin campo de costo).
3. **Detalle analítico** limitado a 2000–3000 filas por consulta; paginación en cliente sobre ese subconjunto.
