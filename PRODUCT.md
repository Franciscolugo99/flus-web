# Product

## Register

product

## Users

FLUS Admin lo usa el responsable de FLUS para dar de alta comercios, administrar licencias, pagos, instalaciones y servicios web. El trabajo ocurre en un contexto operativo y sensible: cada accion puede habilitar o suspender un sistema que un comercio usa para vender.

Los clientes finales no usan este panel administrativo. Acceden a FLUS Web o al portal correspondiente a su negocio, siempre con aislamiento respecto de otros clientes.

## Product Purpose

El panel centraliza el ciclo de vida de cada cliente de FLUS: alta comercial, licencia, instancia web, base asignada, version, respaldos, estado tecnico y futura migracion a FLUS Local.

El resultado esperado es administrar varios clientes sin mantener copias manuales descontroladas, sin exponer credenciales y sin mezclar datos entre comercios.

## Brand Personality

Claro, confiable y operativo. La interfaz debe transmitir control sin parecer una herramienta tecnica improvisada. Los estados y acciones deben explicar que ocurre y cual es el siguiente paso.

## Anti-references

- Paneles genericos llenos de tarjetas decorativas.
- Credenciales visibles o copiables despues del alta.
- Acciones destructivas sin confirmacion ni auditoria.
- Automatizaciones que ocultan pasos incompletos o errores de provisionamiento.
- Interfaces que mezclan datos de distintos clientes en una misma vista sin un alcance explicito.

## Design Principles

1. Aislamiento visible y real: cada instancia, base y licencia pertenece a un cliente comprobado en backend.
2. Secretos de un solo sentido: se pueden crear o rotar, nunca volver a mostrar.
3. Estado antes que sorpresa: provisionamiento, migraciones, backups y salud deben tener estados concretos.
4. Automatizacion auditable: cada accion administrativa registra actor, fecha, resultado y motivo.
5. Compatibilidad como producto: una instancia FLUS Web debe poder exportarse luego hacia FLUS Local.

## Accessibility & Inclusion

El panel apunta a WCAG AA razonable: contraste suficiente, foco visible, formularios con labels, mensajes accionables, estados que no dependan solo del color y funcionamiento completo con teclado. Las tablas y formularios deben adaptarse a notebook y tablet sin ocultar acciones esenciales.
