# Optimizaciones para PHP 8.4

## Cambios aplicados

### 1. Requisito de versión
- **composer.json**: `"php": "^8.4"` (actualizar manualmente si se mantiene ^8.2)
- Permite usar las nuevas características de PHP 8.4

### 2. Propiedades tipadas
Todas las propiedades de los controllers principales ahora tienen tipo explícito:
- `CustomerModel $customerModel`, `DoctorModel $doctorModel`, etc.
- `object|null $user_info` en SecureArea
- Elimina propiedades dinámicas (deprecado en PHP 8.2+)

### 3. Parámetros con union types
- `Customers::view(int|string $customer_id = -1)`
- `Customers::save(int|string $customer_id = -1)`

### 4. safe_mb_trim (PHP 8.4 mb_trim)
- Nueva función `safe_mb_trim()` en `config_helper.php`
- Usa `mb_trim()` en PHP 8.4+ (mejor manejo de acentos y ñ en español)
- Fallback a `trim()` en versiones anteriores
- Aplicado en formularios de clientes (apellidos)

### 5. Operador ??= (null coalescing assignment)
- `$data['theme_color'] ??= '#FF7218'` en ConfigService

### 6. array_filter + array_intersect_key
- ConfigService::saveFromRequest simplificado con funciones de array
- Menos iteraciones explícitas

### 7. Arrow functions
- Callbacks con `fn (mixed $v): bool => ...` donde aplica

## Recomendaciones adicionales (PHP 8.4)

- **array_find** / **array_find_key**: Para búsquedas "primera coincidencia"
- **array_any** / **array_all**: Para validaciones sobre arrays
- **Property Hooks**: En clases con lógica de get/set repetitiva
- **readonly**: En DTOs o clases de valor inmutable
- **#[Deprecated]**: Marcar código legacy antes de eliminar
