-- Manuales por proveedor: 2-3 manuales por cada proveedor (Resumen insert + Uso con Statfax)
-- Proveedores: Human, Wiener Lab, Biomerieux, Randox
-- Ejecutar en la base de datos laboratorio (prefijo dom_)
-- Categorías: Química y Serología (5-21)

SET @resumen = '<div class="manual-procedimiento">
<h4>Resumen del insert - kit {PROVEEDOR}</h4>
<p><strong>Proveedor:</strong> {PROVEEDOR} ({PAIS})</p>

<h5>Reactivos y referencia</h5>
<ul>
<li>Catálogo: [completar del insert]</li>
<li>Lote y caducidad: [registrar]</li>
</ul>

<h5>Muestra</h5>
<p>[Tipo, tubo, volumen - según insert]</p>

<h5>Procedimiento resumido</h5>
<ol>
<li>Preparar muestra (centrifugar 10 min, obtener suero)</li>
<li>Pipetear según insert: [volúmenes]</li>
<li>Incubar: [tiempo y temperatura]</li>
<li>Leer en equipo: [Statfax/ espectrofotómetro]</li>
</ol>
</div>';

SET @statfax = '<div class="manual-procedimiento">
<h4>Uso con Statfax - kit {PROVEEDOR}</h4>
<p><strong>Proveedor:</strong> {PROVEEDOR} ({PAIS})</p>
<p><strong>Equipo:</strong> Statfax (o analizador semiautomático)</p>

<h5>Paso 1.</h5>
<p>Dejar coagular 30 min y centrifugar para obtener suero.</p>

<h5>Paso 2.</h5>
<p>Guardar suero en eppendorf, rotular con código del sistema y fecha.</p>

<h5>Paso 3.</h5>
<p>Según insert {PROVEEDOR}: [volumen reactivo + volumen muestra]. Incubar [temperatura] por [minutos].</p>

<h5>Paso 4.</h5>
<p>Registrar blancos y valores en Statfax. [Completar parámetros del programa según insert].</p>
</div>';

-- Human: Resumen + Statfax
INSERT INTO `dom_manuals` (`prianacategoria_id`, `tittle`, `manual`, `deleted`)
SELECT p.prianacategoria_id, CONCAT(p.name, ' - Human: Resumen del insert'),
REPLACE(REPLACE(@resumen, '{PROVEEDOR}', 'Human'), '{PAIS}', 'Alemania'), 0
FROM dom_prianacategoria p
WHERE p.anacategoria_id IN (5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21) AND (p.deleted=0 OR p.deleted IS NULL)
AND NOT EXISTS (SELECT 1 FROM dom_manuals m WHERE m.prianacategoria_id=p.prianacategoria_id AND m.tittle=CONCAT(p.name,' - Human: Resumen del insert') AND (m.deleted=0 OR m.deleted IS NULL));

INSERT INTO `dom_manuals` (`prianacategoria_id`, `tittle`, `manual`, `deleted`)
SELECT p.prianacategoria_id, CONCAT(p.name, ' - Human: Uso con Statfax'),
REPLACE(REPLACE(@statfax, '{PROVEEDOR}', 'Human'), '{PAIS}', 'Alemania'), 0
FROM dom_prianacategoria p
WHERE p.anacategoria_id IN (5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21) AND (p.deleted=0 OR p.deleted IS NULL)
AND NOT EXISTS (SELECT 1 FROM dom_manuals m WHERE m.prianacategoria_id=p.prianacategoria_id AND m.tittle=CONCAT(p.name,' - Human: Uso con Statfax') AND (m.deleted=0 OR m.deleted IS NULL));

-- Wiener Lab: Resumen + Statfax
INSERT INTO `dom_manuals` (`prianacategoria_id`, `tittle`, `manual`, `deleted`)
SELECT p.prianacategoria_id, CONCAT(p.name, ' - Wiener Lab: Resumen del insert'),
REPLACE(REPLACE(@resumen, '{PROVEEDOR}', 'Wiener Lab'), '{PAIS}', 'Argentina'), 0
FROM dom_prianacategoria p
WHERE p.anacategoria_id IN (5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21) AND (p.deleted=0 OR p.deleted IS NULL)
AND NOT EXISTS (SELECT 1 FROM dom_manuals m WHERE m.prianacategoria_id=p.prianacategoria_id AND m.tittle=CONCAT(p.name,' - Wiener Lab: Resumen del insert') AND (m.deleted=0 OR m.deleted IS NULL));

INSERT INTO `dom_manuals` (`prianacategoria_id`, `tittle`, `manual`, `deleted`)
SELECT p.prianacategoria_id, CONCAT(p.name, ' - Wiener Lab: Uso con Statfax'),
REPLACE(REPLACE(@statfax, '{PROVEEDOR}', 'Wiener Lab'), '{PAIS}', 'Argentina'), 0
FROM dom_prianacategoria p
WHERE p.anacategoria_id IN (5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21) AND (p.deleted=0 OR p.deleted IS NULL)
AND NOT EXISTS (SELECT 1 FROM dom_manuals m WHERE m.prianacategoria_id=p.prianacategoria_id AND m.tittle=CONCAT(p.name,' - Wiener Lab: Uso con Statfax') AND (m.deleted=0 OR m.deleted IS NULL));

-- Biomerieux: Resumen + Statfax
INSERT INTO `dom_manuals` (`prianacategoria_id`, `tittle`, `manual`, `deleted`)
SELECT p.prianacategoria_id, CONCAT(p.name, ' - Biomerieux: Resumen del insert'),
REPLACE(REPLACE(@resumen, '{PROVEEDOR}', 'Biomerieux'), '{PAIS}', 'Francia'), 0
FROM dom_prianacategoria p
WHERE p.anacategoria_id IN (5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21) AND (p.deleted=0 OR p.deleted IS NULL)
AND NOT EXISTS (SELECT 1 FROM dom_manuals m WHERE m.prianacategoria_id=p.prianacategoria_id AND m.tittle=CONCAT(p.name,' - Biomerieux: Resumen del insert') AND (m.deleted=0 OR m.deleted IS NULL));

INSERT INTO `dom_manuals` (`prianacategoria_id`, `tittle`, `manual`, `deleted`)
SELECT p.prianacategoria_id, CONCAT(p.name, ' - Biomerieux: Uso con Statfax'),
REPLACE(REPLACE(@statfax, '{PROVEEDOR}', 'Biomerieux'), '{PAIS}', 'Francia'), 0
FROM dom_prianacategoria p
WHERE p.anacategoria_id IN (5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21) AND (p.deleted=0 OR p.deleted IS NULL)
AND NOT EXISTS (SELECT 1 FROM dom_manuals m WHERE m.prianacategoria_id=p.prianacategoria_id AND m.tittle=CONCAT(p.name,' - Biomerieux: Uso con Statfax') AND (m.deleted=0 OR m.deleted IS NULL));

-- Randox: Resumen + Statfax
INSERT INTO `dom_manuals` (`prianacategoria_id`, `tittle`, `manual`, `deleted`)
SELECT p.prianacategoria_id, CONCAT(p.name, ' - Randox: Resumen del insert'),
REPLACE(REPLACE(@resumen, '{PROVEEDOR}', 'Randox'), '{PAIS}', 'Reino Unido'), 0
FROM dom_prianacategoria p
WHERE p.anacategoria_id IN (5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21) AND (p.deleted=0 OR p.deleted IS NULL)
AND NOT EXISTS (SELECT 1 FROM dom_manuals m WHERE m.prianacategoria_id=p.prianacategoria_id AND m.tittle=CONCAT(p.name,' - Randox: Resumen del insert') AND (m.deleted=0 OR m.deleted IS NULL));

INSERT INTO `dom_manuals` (`prianacategoria_id`, `tittle`, `manual`, `deleted`)
SELECT p.prianacategoria_id, CONCAT(p.name, ' - Randox: Uso con Statfax'),
REPLACE(REPLACE(@statfax, '{PROVEEDOR}', 'Randox'), '{PAIS}', 'Reino Unido'), 0
FROM dom_prianacategoria p
WHERE p.anacategoria_id IN (5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21) AND (p.deleted=0 OR p.deleted IS NULL)
AND NOT EXISTS (SELECT 1 FROM dom_manuals m WHERE m.prianacategoria_id=p.prianacategoria_id AND m.tittle=CONCAT(p.name,' - Randox: Uso con Statfax') AND (m.deleted=0 OR m.deleted IS NULL));
