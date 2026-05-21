# Bitácora de Dificultades y Soluciones

Este documento recopila los desafíos arquitectónicos, problemas de diseño lógico y decisiones técnicas de infraestructura encontrados durante el desarrollo del proyecto de Gestión de Incidencias, junto con las soluciones aplicadas para resolverlos. 

Sirve como evidencia para el apartado **9. Dificultades y soluciones** del documento final de sustentación.

---

## 🏗️ 1. Infraestructura, Despliegue y CORS

### Desacoplamiento de Servicios (Frontend / Backend) sin conflictos de CORS
* **Dificultad:** Al construir una SPA (Single Page Application) en el frontend y una API REST en Laravel para el backend, surge el problema clásico de seguridad del navegador conocido como **CORS (Cross-Origin Resource Sharing)** si se ejecutan en puertos o dominios diferentes. Además, la escalabilidad futura requiere que el backend pueda balancear su carga de forma transparente.
* **Solución:** En lugar de habilitar CORS de forma abierta en Laravel (lo cual reduce la seguridad), se configuró un **Proxy Inverso con Nginx** dentro de la arquitectura de contenedores Docker. Nginx recibe todas las peticiones en el puerto estándar `80`. Las peticiones que inician con `/api/` son redirigidas internamente al backend Laravel, y las demás se sirven directo desde el directorio estático del frontend. Esto unifica el origen de las peticiones a ojos del navegador, eliminando problemas de CORS y preparando el sistema para un balanceador de carga.

---

## 💾 2. Diseño de Base de Datos e Integración de Frameworks

### Integración de Roles en el Sistema de Autenticación por Defecto
* **Dificultad:** El sistema de autenticación nativo de Laravel viene con una tabla `users` predefinida que carece del manejo de roles y permisos necesarios para diferenciar entre Ciudadanos, Técnicos y Administradores. Modificar la migración original del framework puede romper dependencias o herramientas del ecosistema de Laravel.
* **Solución:** Se implementó una estrategia de migración incremental por pasos (`add_rol_to_users_table`). Primero se crea la tabla independiente `roles`. Luego, mediante una migración de tipo `ALTER TABLE`, se modifica la estructura original de `users` añadiendo la columna nullable `id_rol` referenciada a la tabla `roles`. Esto preserva la estructura nativa del framework para autenticación y añade robustez de integridad referencial a la asignación de perfiles.

### Consistencia y Validación Física en Campos Semánticos (Estados y Prioridades)
* **Dificultad:** Las incidencias cambian a través de estados específicos (`PENDIENTE`, `EN_PROCESO`, `RESUELTO`) y manejan niveles de prioridad. Permitir que el backend ingrese cualquier cadena de texto puede provocar inconsistencias graves en el software por diferencias tipográficas (como escribir `"En Proceso"`, `"en_proceso"`, etc.).
* **Solución:** Se evitó sobrecargar la base de datos con tablas adicionales de estados/prioridades (que requerirían múltiples operaciones `JOIN` lentas en cada consulta). En su lugar, se implementaron **restricciones `CHECK` físicas** directamente en el motor PostgreSQL sobre las columnas `estado_incidencia` y `prioridad_incidencia`. Esto asegura que el motor de base de datos rechace automáticamente cualquier valor que no pertenezca estrictamente al dominio de datos definido, garantizando la integridad referencial al instante con costo computacional nulo.

### Lógica de Estados en Auditorías de Historiales
* **Dificultad:** La tabla `historial_estados` se diseñó para almacenar las transiciones de estado de una incidencia (estado anterior vs. estado nuevo). Sin embargo, al registrar una incidencia por primera vez, no existe un estado previo. Obligar a que la columna `estado_anterior` fuera requerida (`NOT NULL`) causaba un fallo lógico que impedía crear registros nuevos.
* **Solución:** Se estructuró la base de datos para que la columna `estado_anterior` sea de tipo nullable (`NULL`). De esta forma, el primer registro se guarda indicando que partió de un estado nulo, y las transiciones posteriores se registrarán con datos completos mediante el uso de disparadores (Triggers) automáticos en la base de datos.


### Límite de Evidencias por Incidencia
* **Dificultad:** Permitir que los usuarios suban archivos sin restricción puede causar abuso de almacenamiento y degradar el rendimiento. Imponer el límite desde el backend con validaciones en Laravel es posible, pero no garantiza integridad si la tabla se manipula directamente desde otro origen.
* **Solución:** Se implementó un trigger `BEFORE INSERT` en la tabla `evidencias` que, antes de cada inserción, cuenta cuántas evidencias ya existen para esa incidencia. Si el conteo llega a 5, el motor lanza una excepción y cancela el INSERT automáticamente. Al implementarse en la base de datos, la restricción aplica independientemente de qué capa intente insertar el registro.

### Separación de Responsabilidades entre Triggers y Procedimientos Almacenados
* **Dificultad:** Al diseñar la capa de automatización de la BD, surgió la duda de qué lógica colocar en un trigger y qué lógica colocar en un procedimiento almacenado. Inicialmente se creó un trigger para notificar al técnico al asignar una incidencia, pero esto duplicaba la responsabilidad que le corresponde al procedimiento `asignar_tecnico`.
* **Solución:** Se estableció el criterio de que los triggers reaccionan a eventos simples y transversales (cambio de estado, fecha de resolución, nuevo comentario), mientras que los procedimientos almacenados encapsulan operaciones de negocio complejas que involucran múltiples pasos y validaciones (como asignar un técnico, que valida rol, unicidad y luego notifica). El trigger de notificación por asignación se eliminó y esa responsabilidad quedó dentro del procedimiento `asignar_tecnico`.
