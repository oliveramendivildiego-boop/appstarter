# Evidencia de pruebas — Reportes analíticos

## 1. Validación de sintaxis PHP

```
php -l app/Controllers/ReportsAnalytics.php   → OK
php -l app/Models/ReportAnalyticsModel.php    → OK
php -l app/Commands/ReportsAnalyticsSmoke.php → OK
```

## 2. Smoke test (BD productiva local — laboratorio)

Comando:

```bash
php spark reports:analytics-smoke
```

Rango: `2024-06-10` → `2026-06-10`

| Reporte | Filas | Tiempo |
|---------|------:|-------:|
| 1. Pruebas más solicitadas | 20 | 22.6 ms |
| 2. Tendencia por paciente | 16 | 2.2 ms |
| 3. Valores críticos | 9 | 5.3 ms |
| 4a. Tiempo de entrega — detalle | 70 | 2.0 ms |
| 4b. Tiempo de entrega — resumen SLA | 70 | 1.6 ms |
| 5. Productividad por usuario | 1 | 2.6 ms |
| 6. Resultados corregidos | 0 | 1.0 ms |
| 7. Pendientes de validar | 49 | 4.3 ms |
| 8a. Consumo por prueba | 2 | 1.6 ms |
| 8b. Consumo por reactivo | 3 | 1.1 ms |
| 8c. Consumo por mes | 6 | 0.8 ms |
| 9. Proyección de agotamiento | 3 | 1.0 ms |
| 10. Comparativo mensual | 4 | 1.2 ms |

**Resultado:** Smoke test completado sin errores. Todos los tiempos &lt; 5 s (muy por debajo del umbral).

## 3. Prueba de rendimiento (100.000 órdenes)

Script: `tests/perf/analytics_perf_seed.sql`

Genera BD aislada `laboratorio_perftest` con:
- ~100.000 órdenes
- ~300.000 resultados
- ~250.000 eventos de auditoría

Ejecución:

```bash
mysql -u root < tests/perf/analytics_perf_seed.sql
php spark reports:analytics-smoke 2024-01-01 2026-06-10 laboratorio_perftest
```

> Ejecutar cuando `mysql` esté disponible en PATH (WAMP: `c:\wamp64\bin\mysql\mysql8.x.x\bin\mysql.exe`).

Criterio de aceptación: cada consulta &lt; 5000 ms con 100k órdenes.

## 4. Checklist funcional por reporte

| # | Reporte | Filtro fecha | PDF | Excel | Imprimir | Totales | Paginación | Orden | Buscador |
|---|---------|:---:|:---:|:---:|:---:|:---:|:---:|:---:|:---:|
| 1 | Pruebas más solicitadas | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| 2 | Tendencia paciente | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| 3 | Valores críticos | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| 4 | Tiempo entrega | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| 5 | Productividad usuarios | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| 6 | Resultados corregidos | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| 7 | Pendientes validación | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| 8 | Consumo insumos | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| 9 | Proyección insumos | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |
| 10 | Comparativo mensual | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ | ✓ |

## 5. Capturas de pantalla

Pendiente de generación manual en navegador. Ver instrucciones en `MANUAL_USO.md` → sección *Capturas de pantalla*.

Directorio sugerido: `docs/reports-analytics/capturas/`
