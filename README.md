# Checador — Sistema de control de entrada/salida con geocerca

Proyecto compuesto por dos partes:

1. **Sistema web** (`/`): Laravel 13 + Filament 5 + MySQL 8.4.
   Recibe peticiones de la app, valida la ubicación contra la geocerca de cada
   centro de trabajo y guarda el registro para su revisión.
2. **App Android** (`/app-movil`): React Native + Expo (TypeScript).
   Pide usuario y contraseña, muestra los datos del empleado y permite "Checar
   entrada" y "Checar salida" enviando coordenadas e info del dispositivo.

---

## 1. Sistema web

### Requisitos

- PHP 8.3+
- Composer 2+
- MySQL 8.x (Laragon incluido)
- Node 20+ (solo para assets si se usan)

### Instalación

```bash
cd C:\laragon\www\checador

# Dependencias
composer install

# Copia de configuración (ajusta credenciales de BD)
copy .env.example .env
#   DB_CONNECTION=mysql
#   DB_DATABASE=checador
#   DB_USERNAME=root
#   DB_PASSWORD=

# Llave de la aplicación
php artisan key:generate

# Base de datos
php artisan migrate --seed
```

El seeder crea:

| Descripción | Correo | Contraseña |
|---|---|---|
| Administrador (todos los permisos) | `admin@checador.test` | `password` |
| Empleado de prueba (para la app) | `empleado@checador.test` | `password` |
| Centro "Planta Matriz" (20.659698, -103.349609, radio 150 m) | — | — |

### Puesta en marcha

Con Laragon: añade `checador.test` como host virtual apuntando a
`C:\laragon\www\checador\public`.

O con el servidor de desarrollo (para que el teléfono lo alcance usa tu IP LAN):

```bash
php artisan serve --host=0.0.0.0 --port=8000
```

- Panel admin: `http://checador.test/admin` (o `http://<IP-LAN>:8000/admin`)
- API: `http://checador.test/api/v1`

### API (para la app)

| Método | Ruta | Descripción |
|---|---|---|
| POST | `/api/v1/auth/login` | Autentica y devuelve token + perfil + centro |
| GET | `/api/v1/estado` | Perfil actual + última checada (requiere token) |
| POST | `/api/v1/dispositivo` | Registra/actualiza el dispositivo |
| POST | `/api/v1/checar` | Registra entrada/salida con coordenadas |
| POST | `/api/v1/auth/logout` | Revoca el token |

`POST /api/v1/checar` recibe:

```json
{
  "tipo": "entrada",
  "lat": 20.659698,
  "lng": -103.349609,
  "precision_metros": 5,
  "fecha_dispositivo": "2026-08-07T18:00:00.000Z",
  "device": { "uuid": "...", "marca": "Xiaomi", "modelo": "Redmi", "plataforma": "android", "version_so": "14", "app_version": "1.0.0" }
}
```

La geocerca usa la **fórmula de Haversine** comparando la distancia contra el
`radio_metros` del centro de trabajo del empleado. La checada **siempre se
registra**; si está fuera de rango queda marcada como `dentro_rango = false` y
el área de revisión lo muestra para que un validador la apruebe o rechace.

### Permisos (roles)

- `admin` — todo
- `revisor` — ver checadas y validar
- `consulta` — solo ver checadas

Los permisos se gestionan en *Roles* dentro del panel.

### Pruebas

```bash
php artisan test
```

---

## 2. App Android

### Requisitos

- Node 20+
- Cuenta en [expo.dev](https://expo.dev) para build en la nube (EAS) — o
  Android Studio + SDK para build local.

### Instalación

```bash
cd app-movil
npm install
```

### Configurar la URL de la API

La app lee la URL desde `app.json → extra.apiUrl`. Para probar en físico, usa
la IP LAN de tu PC (el teléfono **no** puede llegar a `localhost`):

```json
"extra": {
  "apiUrl": "http://192.168.1.100:8000/api/v1"
}
```

> El teléfono y la PC deben estar en la misma red. En Android, además, el
> tráfico HTTP a una IP LAN es permitido en desarrollo.

### Probar en desarrollo

```bash
npx expo start
```

Escanea el QR con Expo Go (instalado desde Play Store). La app se conecta al
servidor web y fluye: login → datos del empleado + GPS en vivo → Checar
entrada/salida → resultado dentro/fuera del área.

### Generar el APK (build en la nube EAS)

```bash
npm install -g eas-cli
eas login
eas build -p android --profile preview
```

El perfil `preview` genera un **APK** instalable directo. Al terminar, EAS
muestra la liga de descarga y el APK queda disponible para distribución
interna.

### Identidad del dispositivo

En el primer arranque la app genera/almacena un UUID seguro
(`androidId` + prefijo, o un UUID aleatorio como respaldo) y lo envía en cada
petición junto con marca, modelo, SO y versión de la app. Así el sistema web
lleva el registro del dispositivo que hizo cada checada.

---

## Estructura relevante

```
├── app/Filament/               Panel admin (recursos Filament 5)
│   ├── Resources/WorkCenterResource.php
│   ├── Resources/Employees/
│   ├── Resources/Users/
│   ├── Resources/Roles/
│   └── Resources/CheckIns/     Área de revisión de checadas
├── app/Http/Controllers/Api/   AuthController, DeviceController, CheckInController
├── app/Models/                 WorkCenter, Employee, User, Device, CheckIn
├── app/Services/GeofenceService.php
├── database/migrations/
├── database/seeders/
├── tests/Feature/              AdminPanelTest, ApiChecadorTest
└── app-movil/                  App React Native (Expo)
    ├── App.tsx
    └── src/screens/            LoginScreen, HomeScreen
```
