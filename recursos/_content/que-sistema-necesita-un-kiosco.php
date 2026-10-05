<?php $articleToc = [
'velocidad'=>'Venta rápida',
'codigos'=>'Códigos de barras',
'stock'=>'Stock y compras',
'caja'=>'Caja y medios de pago',
'movil'=>'Acceso móvil',
'eleccion'=>'Checklist para elegir',
]; ?>
<p>Un kiosco necesita velocidad. Cuando hay clientes esperando, el sistema no puede obligar al cajero a navegar por cinco pantallas para vender una bebida. Pero velocidad sola tampoco alcanza: cada venta debería alimentar caja y stock para que el dueño tenga control después del horario pico.</p>
<p>Por eso conviene evaluar un sistema de kiosco por el circuito completo, no por una lista enorme de funciones que tal vez nunca uses.</p>

<h2 id="velocidad">1. Un punto de venta realmente rápido</h2>
<p>La búsqueda por nombre y código tiene que estar a mano, la cantidad debe poder cambiarse sin fricción y el total tiene que quedar siempre visible. Los productos más frecuentes deberían venderse con la menor cantidad posible de pasos.</p>
<p>En un kiosco, algunos segundos extra por operación se multiplican por cientos de ventas al mes.</p>

<h2 id="codigos">2. Códigos de barras</h2>
<p>El lector USB sigue siendo una excelente opción en caja. En dispositivos móviles también puede ser útil escanear con cámara para tareas puntuales. Lo importante es que el código identifique un producto único y que el sistema informe claramente cuando no existe o ya está en uso.</p>
<p>Además, conviene tener una política para productos sin código de fabricante: asignar un código interno de forma consistente evita depender del nombre.</p>

<h2 id="stock">3. Stock conectado con ventas y compras</h2>
<p>Las bebidas, golosinas, cigarrillos, artículos de limpieza y otros productos de alta rotación cambian todo el día. Si el stock se actualiza en una planilla aparte, rápidamente se desincroniza.</p>
<p>El circuito ideal es: <strong>compra ingresa mercadería → venta descuenta unidades → ajuste documenta excepciones → inventario confirma realidad</strong>.</p>

<h2 id="caja">4. Caja y medios de pago separados</h2>
<p>Un kiosco puede cobrar efectivo, QR, débito, crédito o transferencia. El sistema debería registrar el medio utilizado y permitir cerrar el turno comparando el efectivo esperado con el declarado.</p>
<p>No todos los cobros terminan en el cajón, por lo que separar medios de pago es esencial para que el arqueo tenga sentido.</p>

<h2 id="movil">5. Poder mirar y operar desde otros dispositivos</h2>
<p>El dueño no siempre está sentado frente a la caja. Poder consultar stock, movimientos o información comercial desde una notebook o un teléfono es una ventaja importante, especialmente en negocios pequeños donde una misma persona cumple varios roles.</p>
<p>FLUS Web está orientado justamente a una operación por navegador/PWA, mientras que FLUS Server apunta a escenarios que requieren infraestructura propia y un proyecto más personalizado.</p>

<h2 id="eleccion">Checklist para elegir un sistema para kiosco</h2>
<ul class="article-checklist">
<li>¿La venta por código de barras es rápida?</li>
<li>¿La venta descuenta stock automáticamente?</li>
<li>¿Se pueden registrar compras e ingresos de mercadería?</li>
<li>¿Distingue efectivo, QR, débito, crédito y transferencia?</li>
<li>¿Permite abrir y cerrar caja con un responsable identificado?</li>
<li>¿Se puede usar desde los dispositivos del negocio?</li>
<li>¿Hay soporte cuando aparece un problema operativo?</li>
<li>¿La facturación fiscal requerida está contemplada en el alcance contratado?</li>
</ul>
<div class="article-cta"><h2>FLUS tiene una propuesta específica para kioscos y comercios de mostrador.</h2><p>Podés empezar con FLUS Web y probar la experiencia de caja antes de decidir. Para proyectos con infraestructura propia también existe FLUS Server.</p><div class="article-cta__actions"><a class="btn btn-primary" href="<?= e(site_url('sistema-para-kioscos/')) ?>">Ver FLUS para kioscos</a><a class="btn btn-secondary" href="<?= e((string)$site['demo_url']) ?>" target="_blank" rel="noopener" data-track-event="demo_click" data-track-label="Artículo kiosco CTA">Abrir demo de caja</a></div></div>
