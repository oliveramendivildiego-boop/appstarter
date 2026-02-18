# Migración: Módulos de laboratorio

## 1. Migración principal

```bash
mysql -u root -p laboratorio < database/migrations_modulos_2025.sql
```

## 2. Módulos nuevos (permisos)

Para que aparezcan en el menú: Control de calidad, Reactivos, Equipos, Auditoría:

```bash
mysql -u root -p laboratorio < database/add_modulos_nuevos.sql
```

O desde phpMyAdmin: importar ambos archivos.

**Nota:** Si alguna columna o tabla ya existe, omita esa línea o ignore el error.

## Contenido

- **Pacientes:** `seguro`, `institucion` en dom_customers
- **Órdenes:** `prioridad`, `perfil_id`; tabla dom_perfil_examen
- **Muestras:** dom_tipo_muestra, dom_muestra
- **Valores críticos:** critico_min, critico_max
- **Validación:** validado_tecnico, validado_medico, fecha_validacion, observaciones_clinicas
- **Control de calidad:** dom_control_calidad, dom_control_valor
- **Inventario:** dom_reactivo, dom_reactivo_lote
- **Equipos:** dom_equipo, dom_equipo_mantenimiento
- **Roles:** dom_rol, dom_employees.rol_id
- **Auditoría:** dom_auditoria
