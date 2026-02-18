# Refactorización - Guía de Mejores Prácticas

Estado del proyecto respecto a las recomendaciones de arquitectura.

## ✅ Implementado

### Arquitectura
- **Controllers livianos**: Config y Registers delegando a Services
- **Services**: `ConfigService`, `RegisterService` - lógica de negocio fuera de controllers
- **Un controller por recurso**: Estructura respetada
- **BaseController**: SecureArea extiende BaseController
- **Rutas explícitas**: `autoRoute = false` en Routing.php
- **Helpers reutilizables**: `config_helper`, `table_helper`

### Seguridad
- **esc()** en vistas para escapado
- **Filtros creados**: AuthFilter, PermissionFilter (disponibles para usar)
- **Validación backend**: Config usa Validation service
- **Respuestas HTTP**: 200, 400, 500 según corresponda

### Código
- **Manejo de errores**: try/catch en Registers::save
- **Métodos cortos**: Controllers coordinadores
- **Validaciones centralizadas**: Config\Validation.php con reglas para config y registro

## ⏳ Pendiente / Próximos pasos

### CSRF
- **Habilitar CSRF** en Filters.php cuando los formularios incluyan el token
- Añadir `<meta name="csrf-token" content="<?= csrf_hash() ?>">` en header
- Incluir header `X-CSRF-TOKEN` en peticiones fetch

### Migrations
- Crear migrations para tablas: registro, pago, regvalues, people, doctors, etc.
- Seeders para datos iniciales (módulos, permisos)

### Models
- Revisar `$allowedFields` en todos los modelos
- Evitar acceso directo a `$_POST` - usar request

### JavaScript
- Extraer JS inline a archivos separados por módulo
- Usar `data-*` en lugar de IDs mágicos
- Cargar con `defer`

### Vistas
- Reducir lógica PHP compleja en vistas
- Partials ya usados (header, footer)
- Validación frontend como apoyo

### Performance
- Paginación ya en registros/lista
- Revisar cache para reportes
- Optimizar queries con índices

## Estructura de carpetas

```
app/
├── Config/          # Configuración
├── Controllers/     # Solo coordinan
├── Filters/         # Auth, Permission (auth aplicado en SecureArea)
├── Helpers/         # config_helper, table_helper
├── Language/        # Traducciones
├── Libraries/       # PdfService
├── Models/          # Un modelo por tabla
├── Services/        # Lógica de negocio
│   ├── ConfigService.php
│   └── RegisterService.php
└── Views/           # Vistas con parciales
```

## Cómo agregar un nuevo módulo

1. Crear Service para lógica de negocio
2. Crear/actualizar Model con allowedFields
3. Controller que solo coordine (request → service → response)
4. Validación en Config\Validation
5. Vista con form_open, esc(), Bootstrap
6. Ruta explícita en Routes.php
