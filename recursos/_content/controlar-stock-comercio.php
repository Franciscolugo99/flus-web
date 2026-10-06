<?php $articleToc = [
'que-es-stock-real'=>'Qué significa controlar stock',
'metodo'=>'Un método simple',
'errores'=>'Errores frecuentes',
'inventario'=>'Inventarios y mínimos',
'excel'=>'Cuándo Excel queda chico',
]; ?>
<p>Controlar stock no es mirar una planilla y confiar en que el número sigue siendo correcto. En un comercio, el stock cambia cada vez que vendés, recibís mercadería, hacés un ajuste, anulás una operación o contás físicamente un producto. Si alguno de esos movimientos queda afuera del registro, el número deja de representar lo que realmente hay.</p>
<p>La buena noticia es que no hace falta complicar el proceso. Lo importante es trabajar con una regla consistente y registrar cada movimiento en el momento en que ocurre.</p>

<h2 id="que-es-stock-real">Qué significa tener un stock realmente controlado</h2>
<p>Para cada producto necesitás poder responder tres preguntas: <strong>cuánto debería haber</strong>, <strong>por qué llegó a ese número</strong> y <strong>cuánto hay físicamente</strong>. El primer dato es el stock teórico; el segundo es el historial de movimientos; el tercero se confirma con inventarios.</p>
<div class="article-callout"><strong>Regla básica</strong><p style="margin:0">Stock actual = stock inicial + ingresos + ajustes positivos − ventas − egresos − ajustes negativos.</p></div>
<p>El problema aparece cuando una compra se carga como texto libre, una venta no descuenta unidades o alguien corrige el número directamente sin dejar motivo. Por eso conviene pensar el stock como una <strong>historia de movimientos</strong>, no como una celda que se edita.</p>

<h2 id="metodo">Un método simple para ordenar el stock de un comercio</h2>
<h3>1. Identificá cada producto de forma única</h3>
<p>Usá un código interno, SKU o código de barras. Dos productos parecidos no deberían depender solo del nombre para distinguirse. Si vendés distintas presentaciones, tamaños o variantes, cada una necesita una identificación clara.</p>
<h3>2. Definí un punto de partida</h3>
<p>Hacé un conteo inicial. Si los números actuales no son confiables, no intentes corregir meses de historia antes de empezar: tomá un inventario físico como base y documentá la fecha del corte.</p>
<h3>3. Registrá todos los ingresos</h3>
<p>Cuando llega mercadería, el stock debe aumentar por una compra o un ingreso identificable. Eso permite después responder quién cargó el movimiento, qué proveedor intervino y cuándo cambió la existencia.</p>
<h3>4. Hacé que la venta descuente stock</h3>
<p>Este es el punto donde una planilla manual suele empezar a fallar. Si la caja vende por un lado y la planilla se actualiza después, aparecen olvidos y diferencias. En un sistema de gestión, la venta y la baja de stock deben formar parte del mismo circuito.</p>
<h3>5. Separá ajustes de compras y ventas</h3>
<p>Roturas, vencimientos, pérdidas, regalos, errores de carga o correcciones de inventario son ajustes. Registrarlos con motivo evita que una diferencia quede escondida dentro de una compra o una venta ficticia.</p>

<h2 id="errores">Los errores que más diferencias generan</h2>
<ul class="article-checklist">
<li>Vender un producto y actualizar la planilla varias horas después.</li>
<li>Usar el mismo código para presentaciones diferentes.</li>
<li>Modificar directamente la cantidad sin registrar un motivo.</li>
<li>Recibir mercadería sin cargar el ingreso completo.</li>
<li>No controlar anulaciones, devoluciones o roturas.</li>
<li>Hacer un inventario anual en vez de controles pequeños y frecuentes.</li>
</ul>
<p>Cuanto más movimiento tiene un comercio, menos sostenible es depender de la memoria de una persona para mantener esos pasos sincronizados.</p>

<h2 id="inventario">Inventarios parciales y stock mínimo</h2>
<p>No es necesario cerrar el negocio para contar todo cada semana. Una práctica útil es hacer <strong>conteos cíclicos</strong>: hoy bebidas, mañana golosinas, después limpieza. Los productos de mayor rotación deberían revisarse con más frecuencia que los que casi no se mueven.</p>
<p>También conviene definir un <strong>stock mínimo</strong>. No sirve solamente para mostrar una alerta: obliga a pensar qué productos no pueden faltar y con cuánto tiempo se reponen. Una lista de bajo stock debería transformarse en una acción concreta de compra.</p>

<h2 id="excel">Cuándo Excel empieza a quedar chico</h2>
<p>Excel puede funcionar muy bien al principio. El problema no es la herramienta sino la cantidad de procesos manuales que empiezan a depender de ella. Si una venta ocurre en caja y después alguien tiene que abrir otra planilla para descontarla, ya existen dos fuentes de verdad.</p>
<p>Las señales más claras son: varias personas editando, cientos de productos, movimientos durante todo el día, diferencias frecuentes, necesidad de saber quién cambió algo y consultas desde más de un dispositivo.</p>
<div class="article-cta"><h2>En FLUS, la venta y el stock forman parte de la misma operación.</h2><p>Podés consultar productos, registrar ventas y seguir movimientos sin mantener una planilla paralela. Si hoy trabajás con Excel, también podemos evaluar cómo pasar tus datos al sistema.</p><div class="article-cta__actions"><a class="btn btn-primary" href="<?= e(site_url('control-de-stock.php')) ?>">Ver control de stock</a><a class="btn btn-secondary" href="<?= e((string)$site['demo_url']) ?>" target="_blank" rel="noopener" data-track-event="demo_click" data-track-label="Artículo stock CTA">Ver stock en FLUS</a></div></div>
