# Configurar Login con Google

Para habilitar el inicio de sesión con Google, sigue estos pasos:

## 1. Crear proyecto en Google Cloud Console

1. Ve a [Google Cloud Console](https://console.cloud.google.com/)
2. Crea un proyecto nuevo o selecciona uno existente
3. Ve a **APIs y servicios** > **Credenciales**

## 2. Configurar pantalla de consentimiento OAuth

1. Ve a **APIs y servicios** > **Pantalla de consentimiento de OAuth**
2. Tipo de usuario: **Externo** (o Interno si usas Google Workspace)
3. Completa: nombre de la aplicación, email de soporte
4. En **Ámbitos**, añade:
   - `email`
   - `profile`
   - `openid`

## 3. Crear credenciales OAuth

1. Ve a **APIs y servicios** > **Credenciales**
2. Clic en **+ CREAR CREDENCIALES** > **ID de cliente de OAuth**
3. Tipo de aplicación: **Aplicación web**
4. Nombre: "Laboratorio John" (o el que prefieras)
5. En **URIs de redirección autorizados**, añade:
   - `http://localhost/john_ci4/public/login/google/callback`
   - Para producción: `https://tudominio.com/login/google/callback`
6. Guarda y copia el **ID de cliente** y el **Secreto del cliente**

## 4. Configurar en el proyecto

Edita el archivo `.env` y descomenta/actualiza:

```
googleClientId = TU_ID_DE_CLIENTE.apps.googleusercontent.com
googleClientSecret = TU_SECRETO_DE_CLIENTE
```

## 5. Registrar empleados con email

Para que un empleado pueda iniciar sesión con Google, su **email** debe estar guardado en la tabla `people` (vinculada al empleado en `employees`). El email de la cuenta de Google debe coincidir exactamente con el registrado.
