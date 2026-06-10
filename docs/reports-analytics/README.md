# Reportes analíticos avanzados — Infolab

Módulo independiente de 10 reportes de solo lectura integrados en `/reports`.

## Documentación

| Documento | Descripción |
|-----------|-------------|
| [MANUAL_USO.md](MANUAL_USO.md) | Guía para usuarios finales |
| [MANUAL_TECNICO.md](MANUAL_TECNICO.md) | Arquitectura, consultas, tablas e índices |
| [LISTA_CAMBIOS.md](LISTA_CAMBIOS.md) | Archivos nuevos y modificados |
| [EVIDENCIA_PRUEBAS.md](EVIDENCIA_PRUEBAS.md) | Resultados de smoke test y rendimiento |
| [CONFIRMACION_IMPACTO.md](CONFIRMACION_IMPACTO.md) | Confirmación de no impacto en módulos existentes |

## Acceso rápido

Menú lateral → **Reportes** → hub en `reports/listing.php`, sección **Analítica avanzada**.

## Smoke test (CLI)

```bash
php spark reports:analytics-smoke [fecha_inicio] [fecha_fin] [bd_opcional]
```

## Prueba de rendimiento (100k órdenes)

```bash
mysql -u root < tests/perf/analytics_perf_seed.sql
php spark reports:analytics-smoke 2024-01-01 2026-06-10 laboratorio_perftest
```
