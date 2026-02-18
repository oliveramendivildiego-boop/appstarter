# Credenciales de acceso

## Usuario por defecto (según john.sql)

- **Usuario:** `admin`
- **Contraseña:** `abc123`

> Nota: La contraseña en la base de datos está almacenada con MD5. Si cambiaste la contraseña en el sistema original, usa esa.

## Si no puedes iniciar sesión

1. **Diagnóstico:** En modo desarrollo, usa el enlace "Restablecer admin/password" en la página de login, o accede a `diagnostico.php` para verificar BD, empleados y restablecer contraseña.

2. **Base de datos:** Asegúrate de que:
   - La base de datos `john` existe
   - Las tablas tienen prefijo `dom_` (dom_employees, dom_people, etc.)

3. **Variables de entorno:** El archivo `.env` debe tener:
   ```
   database.default.database = john
   database.default.DBPrefix = dom_
   ```
