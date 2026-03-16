# Implementación de Facturación con SIN

## 📋 Descripción General

Se ha agregado una nueva pestaña de configuración "Facturación SIN" en `http://laboratorio.local/config` para integrar la facturación electrónica con el **Servicio de Impuestos Nacionales (SIN)** de Bolivia.

## ✅ Lo que se implementó

### 1. Nueva Pestaña de Configuración
- **Ubicación**: `http://laboratorio.local/config` → Pestaña "Facturación SIN"
- **Posición**: Lado de la pestaña de WhatsApp
- **Icono**: Receipt (🧾)

### 2. Control Principal: Checkbox de Habilitación
Un checkbox gigante " Habilitar facturación con SIN" que permite:
- **Activado (checked)**: Sistema emite facturas electrónicas a SIN
- **Desactivado (unchecked)**: Sistema emite solo recibos (comportamiento actual)

### 3. Campos de Configuración (cuando está habilitado)

#### API y Certificado
- **Endpoint de API**: URL de conexión a SIN
- **Ruta del Certificado Digital (PEM)**: Archivo del certificado para firmar documentos
- **Contraseña del Certificado**: Clave de acceso al certificado

#### Datos de Empresa
- **NIT de la Empresa**: Número de Identificación Tributario
- **Razón Social**: Nombre legal de la empresa
- **Código de Sucursal**: Sucursal emisora (default: 1)

#### Configuración de SIN
- **Tipo de Sistema**: 
  - Nativo (Conectado en línea)
  - Sistema Descargable
- **Modalidad de Emisión**:
  - En Línea
  - Fuera de Línea
- **Código de Actividad (CAEN)**: Código de la actividad económica (ej: 6230 para laboratorios)

### 4. Botón de Prueba
- **Probar Conexión**: Valida que la configuración sea correcta
- Verifica acceso a certificado, conectividad con API
- Muestra mensaje de éxito/error

## 🗄️ Base de Datos

### Tabla: `app_config`
Se guardan los siguientes pares clave-valor:

| Clave | Descripción | Tipo |
|-------|-------------|------|
| `sin_billing_enabled` | Habilitar facturación | 0/1 |
| `sin_api_endpoint` | URL de la API de SIN | string |
| `sin_certificate_path` | Ruta del certificado PEM | string |
| `sin_certificate_password` | Contraseña del certificado | string |
| `sin_nit` | NIT de la empresa | string |
| `sin_business_name` | Razón social | string |
| `sin_branch_code` | Código de sucursal | string |
| `sin_system_type` | Tipo de sistema | string |
| `sin_emission_mode` | Modalidad de emisión | string |
| `sin_activity_code` | Código CAEN | string |

## 🔧 Archivos Modificados

### 1. `/app/Views/config/manage.php`
- Agregado botón de navegación de la pestaña SIN
- Agregada sección de contenido con formulario SIN
- Agregada lógica JavaScript para toggle y prueba de conexión

### 2. `/app/Controllers/Config.php`
- `saveSin()`: Maneja la llegada del formulario SIN
- `testSin()`: Endpoint AJAX para probar conexión
- Logging en auditoría

### 3. `/app/Services/ConfigService.php`
- `saveSinConfig(array $postData)`: Guarda configuración en base de datos
- `testSinConnection()`: Valida la configuración y prueba conectividad

### 4. Nuevo Archivo: `.github/agents/sin-integration.agent.md`
- Agente especializado para trabajo futuro en integración SIN
- Documenta patrones y principios de implementación
- Facilita delegación a subagentes para tareas adicionales

## 🔐 Seguridad

- ✅ Campos de contraseña ocultos (type="password")
- ✅ Valores escapados con `esc()` en HTML
- ✅ Guardados como cadenas en BD (no en logs)
- ⚠️ **TODO**: Implementar encriptación para credenciales sensibles
- ⚠️ **TODO**: Usar .env para certificados en producción

## 🚀 Flujo de Uso

1. Admin va a **Configuración** → **Facturación SIN**
2. Habilita el checkbox "Habilitar facturación con SIN"
3. Completa todos los campos:
   - Datos de SIN (endpoint, certificado)
   - Datos de empresa (NIT, razón social)
   - Configuración técnica (tipo de sistema, modalidad)
4. Hace clic en **"Probar Conexión"** para validar
5. Hace clic en **"Guardar Configuración SIN"**
6. Sistema guarda en base de datos
7. Se registra en auditoría

## 📝 Próximos Pasos (Implementación Futura)

### Corto Plazo
1. **Encriptación de Credenciales**: Encriptar certificado_password
2. **Carga de Archivo**: Permitir upload del certificado PEM
3. **Validación Avanzada**: Verificar validez del certificado
4. **Documentación SIN**: Links directos a docs de SIN

### Mediano Plazo
1. **SinService**: Servicio para generar/enviar facturas a SIN
2. **Lógica de Facturación**: En checkout/registros, decidir factura vs recibo
3. **Historial de Facturas**: Tabla para registrar facturas emitidas
4. **Status de Sincronización**: Ver si facturas fueron aceptadas por SIN

### Largo Plazo
1. **Descarga de Catálogos SIN**: Importar códigos de actividad, productos
2. **Soporte Offline**: Manejo de datos sin conexión a SIN
3. **Portal de Consulta**: Pacientes ven sus facturas/recibos
4. **Integración Bancaria**: Sincronización de pagos

## 🧪 Testing

Para probar la configuración:

```bash
# 1. Navegar a
http://laboratorio.local/config?tab=sin

# 2. Completar formulario con datos de prueba

# 3. Hacer clic en "Probar Conexión"

# 4. Ver resultado en alert
```

## 📞 Contacto / Preguntas

Para consultas sobre la implementación SIN, revisar:
- [Documentación Oficial SIN](https://www.impuestos.gob.bo/)
- Archivo de configuración: `ARQUITECTURA_AUDIT.md`
- Agente especializado: `.github/agents/sin-integration.agent.md`

---

**Fecha de implementación**: 11 de marzo de 2026  
**Estado**: ✅ Fase 1 - Interfaz y configuración completada
