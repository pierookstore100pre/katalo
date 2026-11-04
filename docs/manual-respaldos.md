# Manual de respaldos y restauración — Katalo.pe (HU-48)

## Generar un respaldo

Desde la raíz del proyecto ejecutar:

```bash
php spark katalo:backup
```

El comando:

1. Crea `writable/backups/` si no existe.
2. Genera un archivo `katalo_pe_YYYYMMDD_HHMMSS.sql`.
3. Usa `mysqldump` si está disponible en el `PATH`, en la variable de entorno
   `MYSQLDUMP_PATH` o en una ubicación habitual de MySQL/XAMPP.
4. Si `mysqldump` no está disponible o falla, exporta con PHP las tablas base
   mediante `SHOW FULL TABLES`, `SHOW CREATE TABLE` y `SELECT *`.
5. Aplica retención: conserva los últimos 7 archivos `.sql` y elimina los más antiguos.
6. Registra `backup_generado` en `logs_actividad` con el nombre y tamaño del archivo.

Ejemplo de salida:

```text
Respaldo generado: katalo_pe_20261104_153000.sql (1.24 MB). Se conservan 7 archivos.
```

### Notas sobre el respaldo con PHP

- Es más lento que `mysqldump` para bases de datos grandes.
- Incluye estructura y datos de tablas base.
- No incluye triggers, rutinas ni vistas.
- El respaldo con `mysqldump` sí incluye rutinas y triggers porque se ejecuta con
  `--routines --triggers --single-transaction`.

### Ubicación personalizada de mysqldump

Si el binario no está en el `PATH`, definir la ruta antes de ejecutar el comando:

**Windows (PowerShell):**

```powershell
$env:MYSQLDUMP_PATH = "C:\xampp\mysql\bin\mysqldump.exe"
php spark katalo:backup
```

**Linux:**

```bash
export MYSQLDUMP_PATH=/usr/bin/mysqldump
php spark katalo:backup
```

## Programar respaldos automáticos

El comando no programa tareas por sí mismo. Usar el programador de tareas del sistema.

### Windows — Task Scheduler

1. Abrir **Task Scheduler** y crear una tarea básica.
2. Frecuencia: diaria, a la hora deseada (por ejemplo, 02:00).
3. Acción: **Start a program**.
4. Programa: `C:\xampp\php\php.exe`.
5. Argumentos: `spark katalo:backup`.
6. Iniciar en: `C:\xampp\htdocs\katalo`.
7. Guardar y ejecutar la tarea una vez para verificar que genera el archivo.

### Linux — crontab

Editar el crontab del usuario que ejecuta la aplicación:

```bash
crontab -e
```

Agregar una ejecución diaria a las 02:00:

```cron
0 2 * * * cd /var/www/katalo && /usr/bin/php spark katalo:backup >> writable/logs/backup-cron.log 2>&1
```

Ajustar las rutas de `php` y del proyecto según el servidor.

## Restaurar un respaldo

> La restauración sobrescribe datos existentes. Hacerlo preferentemente sobre una
> base de datos de prueba o después de generar un respaldo del estado actual.

1. Crear una base de datos vacía si es necesario:

```bash
mysql -u USER -p -e "CREATE DATABASE katalo_pe_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

2. Restaurar el archivo SQL:

```bash
mysql -u USER -p DBNAME < writable/backups/katalo_pe_YYYYMMDD_HHMMSS.sql
```

Ejemplo:

```bash
mysql -u root -p katalo_pe_test < writable/backups/katalo_pe_20261104_153000.sql
```

En Windows, si `mysql` no está en el `PATH`:

```powershell
& "C:\xampp\mysql\bin\mysql.exe" -u root -p katalo_pe_test < "writable\backups\katalo_pe_20261104_153000.sql"
```

3. Verificar que la restauración terminó sin errores. El archivo generado con
   `mysqldump` incluye `CREATE TABLE` e `INSERT`; el generado con PHP también.

## Verificar integridad después de restaurar

Ejecutar la verificación de aislamiento entre negocios:

```bash
php spark katalo:verificar-aislamiento
```

Todas las comprobaciones deben mostrarse en **OK** y el comando debe devolver
código de salida `0`.

También se recomienda revisar el conteo de registros de las tablas principales
(`negocios`, `productos`, `pedidos`, `usuarios`) antes y después de la restauración.

## Retención y archivo

- El comando conserva automáticamente los **7 respaldos diarios más recientes**.
- Los respaldos mensuales o de cierre de mes deben copiarse manualmente a un
  almacenamiento externo antes de que la retención los elimine.
- No almacenar respaldos únicamente en el mismo servidor de la aplicación.
