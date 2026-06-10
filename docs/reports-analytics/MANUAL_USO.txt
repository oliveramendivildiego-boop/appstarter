# Manual de uso — Reportes analíticos avanzados

## Acceso

1. Inicie sesión con un usuario que tenga permiso al módulo **Reportes**.
2. En el menú lateral, haga clic en **Reportes**.
3. En el hub de reportes, localice la sección correspondiente:
   - **Análisis clínico avanzado** — tendencia y valores críticos.
   - **Indicadores estratégicos** — pruebas más solicitadas y comparativo mensual.
   - **Productividad y calidad** — TAT, productividad, correcciones y pendientes.
   - **Insumos e inventario** — consumo y proyección de agotamiento.

Los reportes marcados con badge **Nuevo** son los 10 reportes analíticos implementados en esta entrega.

## Funciones comunes (todos los reportes nuevos)

| Función | Cómo usarla |
|---------|-------------|
| **Filtro por fecha** | Campos *Desde* y *Hasta* con calendario Flatpickr; pulse **Filtrar**. |
| **Imprimir** | Botón **Imprimir** en la barra superior (oculta filtros y herramientas). |
| **PDF** | Botón rojo **PDF** — descarga documento con el mismo contenido filtrado. |
| **Excel** | Botón verde **Excel** — descarga CSV UTF-8 compatible con Excel. |
| **Buscador** | Campo de búsqueda sobre la tabla activa (filtra en el navegador). |
| **Ordenamiento** | Clic en encabezado de columna para ordenar ascendente/descendente. |
| **Paginación** | Selector de filas por página (10, 25, 50, 100 o todas). |
| **Totalizadores** | Panel inferior o tarjetas de resumen según el reporte. |

---

## 1. Pruebas más solicitadas

**Ruta:** `reports/pruebasMasSolicitadas`

**Filtros:** fecha inicio/fin, grupo de análisis, médico.

**Muestra:** ranking, código, nombre, grupo, cantidad, porcentaje, ingreso estimado.

**Gráficos:** barras Top 10/Top 20 y torta de distribución porcentual.

**Totales:** total de pruebas solicitadas y total de ingresos estimados.

> **Nota:** El filtro por sucursal no está disponible porque el sistema no almacena sucursales en base de datos (multi-tenant por base de datos independiente).

---

## 2. Tendencia histórica por paciente

**Ruta:** `reports/tendenciaPaciente`

**Filtros:** búsqueda de paciente (nombre o CI), tipo de prueba, rango de fechas.

**Flujo:**
1. Busque al paciente por nombre o cédula.
2. Selecciónelo de la lista de candidatos.
3. Opcionalmente filtre por tipo de prueba (glucosa, HbA1c, perfil lipídico, PSA, TSH, hemograma, etc.).
4. Revise la tabla cronológica y el gráfico de líneas por parámetro.

**Columnas:** fecha, orden, prueba, parámetro, resultado, unidad, referencias, estado (normal/bajo/alto/crítico), médico.

---

## 3. Valores críticos o fuera de rango

**Ruta:** `reports/valoresCriticos`

**Filtros:** fecha, grupo de análisis, prueba específica.

**Clasificación:** bajo, alto, crítico bajo, crítico alto.

**Indicadores:** cantidad y porcentaje por prueba y por grupo.

---

## 4. Tiempo de entrega (TAT)

**Ruta:** `reports/tiempoEntrega`

**Filtros:** fecha, grupo, prueba, SLA en horas (por defecto 24 h).

**Muestra:** fecha recepción, primer resultado, validación, horas transcurridas.

**Resumen:** promedio, máximo, mínimo, órdenes dentro/fuera de SLA.

---

## 5. Productividad por usuario

**Ruta:** `reports/productividadUsuarios`

**Filtros:** fecha, usuario (opcional).

**Métricas por usuario:** recepciones, resultados cargados, validaciones, modificaciones, impresiones.

**Ranking:** usuarios ordenados por actividad total.

---

## 6. Resultados corregidos (auditoría)

**Ruta:** `reports/resultadosCorregidos`

**Filtros:** fecha, usuario que corrigió.

**Muestra:** resultado original, resultado nuevo, paciente, orden, fecha, usuario.

**Origen de datos:** bitácora `dom_auditoria` (acción `guardar_resultados` con cambios en JSON). No requiere tabla nueva.

---

## 7. Resultados pendientes de validar

**Ruta:** `reports/pendientesValidacion`

**Sin filtro de fecha obligatorio** — muestra órdenes con resultados cargados pero sin validación técnica ni médica.

**Columnas:** orden, paciente, fecha recepción, prueba, usuario que cargó, tiempo pendiente.

**Indicadores:** pendientes por usuario y por área (grupo de análisis).

---

## 8. Consumo de insumos por prueba

**Ruta:** `reports/consumoInsumos`

**Filtros:** rango de fechas.

**Secciones:**
- Consumo por prueba y reactivo (consumo automático aplicado).
- Consumo por reactivo (todas las salidas de inventario).
- Consumo diario y mensual.
- Gráfico de consumo mensual.

> **Costo estimado:** no se muestra porque el catálogo de reactivos no incluye campo de costo unitario. Si se agrega en el futuro, el reporte puede extenderse sin afectar datos existentes.

---

## 9. Proyección de agotamiento de insumos

**Ruta:** `reports/proyeccionInsumos`

**Filtro:** ventana de días para calcular consumo promedio (por defecto 30).

**Muestra:** stock actual, consumo promedio diario, días restantes, fecha estimada de agotamiento.

**Clasificación:** normal (verde), advertencia (amarillo), crítico (rojo).

---

## 10. Comparativo mensual de ingresos y pacientes

**Ruta:** `reports/comparativoMensual`

**Filtros:** rango de meses (por defecto desde enero del año anterior hasta hoy).

**Columnas:** mes, pacientes únicos, órdenes, pruebas, facturado, cobrado, variación vs mes anterior, variación vs mismo mes año anterior, acumulado.

**Gráficos:** tendencia mensual y crecimiento acumulado de facturación.

---

## Capturas de pantalla

Las capturas de cada reporte deben generarse desde el entorno de producción o staging:

1. Abra cada URL listada arriba con datos de prueba.
2. Aplique un filtro representativo.
3. Guarde captura de pantalla (tabla + gráficos si aplica).
4. Almacene en `docs/reports-analytics/capturas/` con nombre `{reporte}.png`.

Este paso requiere acceso visual al navegador; no se automatiza en el repositorio.
