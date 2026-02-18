# Fabricantes Principales y Sus Insertos Oficiales

Guía de acceso a IFU (Instrucciones de Uso) y documentación técnica para LIS.
Con 6-8 fabricantes se cubre ~95% de las pruebas de laboratorio.

**Electrolitos (Na, K, Cl, Ca, Mg, P):** ver [ELECTROLITOS_INSERTOS.md](ELECTROLITOS_INSERTOS.md)

---

## Wiener Lab (Argentina)

**Muy usado en Bolivia y LATAM**  
Cubre: Hematología, Química clínica, Serología, ELISA, Coagulación, Orina

| Recurso | URL |
|---------|-----|
| Productos / Reactivos | https://www.wiener-lab.com.ar/Productos/Reactivos |
| Manuales / IFU (PDF) | https://www.wiener-lab.com.ar/Productos/Manuales |
| Vademecum acceso directo | https://access.wiener-lab.com/ |
| Catálogo | https://www.wiener-lab.com.ar/ |

**Cubre:** Hemograma, Plaquetas, Reticulocitos, Urea, Creatinina, Glucosa, Perfil lipídico, GPT, GOT, FA, GGT, PCR, ASTO, VDRL, Widal, Ferritina, PSA, AFP, ELISA Hepatitis, HIV, Toxo, CMV

**PDFs descargados:** bilirrubina_total_en.pdf, crp_hs_turbitest_es.pdf, ldh_en.pdf, ige_calibrator_en.pdf

---

## SpinReact (España)

**Insertos muy claros – recomendado para sistemas**  
Cubre: Química sanguínea completa, Perfil hepático, Perfil lipídico, Hierro, TIBC, Ferritina, Coagulación, Orina

| Recurso | URL |
|---------|-----|
| Productos | https://www.spinreact.com/es/productos/ |
| Documentación | https://www.spinreact.com/es/documentacion/ |
| Bioquímica Clínica | https://www.spinreact.com/es/lista-productos/bioquimica-clinica.html |
| Enzimas | https://www.spinreact.com/es/lista-productos/bioquimica-clinica/enzimas.html |
| Lípidos | https://www.spinreact.com/es/lista-productos/bioquimica-clinica/lipidos.html |
| Coagulación | https://www.spinreact.com/es/lista-productos/hematologia-hemostasia-/coagulacion.html |

**PDFs descargados:** albumina, bilirrubina, creatinina, glucosa, urea, acido urico, hemoglobina, GOT, GPT, GGT, LDH, lipasa, amilasa, FA, CK, CK-MB, colinesterasa, colesterol, trigliceridos, HDL, LDL, ferritina, APTT, PT, fibrinogeno, D-dimeros

---

## BioSystems (España)

**Muy usado en química y automatización**

| Recurso | URL |
|---------|-----|
| Reactivos | https://www.biosystems.es/es/reactivos |
| Documentación | https://www.biosystems.es/es/documentacion |

**Cubre:** Amilasa, Lipasa, LDH, CK, CK-MB, Urea, BUN, Electrolitos, HbA1c

*Nota: La URL de documentación puede requerir navegación desde el sitio principal.*

---

## Human Diagnostics (Alemania)

**Muy completo en serología y química**

| Recurso | URL |
|---------|-----|
| Productos | https://www.human.de/products/ |
| Descargas / IFU | https://www.human.de/service/downloads/ |

**Cubre:** Serología (latex, PCR, RA), Química clínica, ELISA básicos

*Nota: Los insertos suelen entregarse con el kit. Solicitar al distribuidor.*

---

## Biolabo (Francia)

**Excelente para química húmeda**

| Recurso | URL |
|---------|-----|
| Productos + IFU | https://www.biolabo.fr/en/products/ |
| Descargas | https://www.biolabo.fr/en/downloads/ |

**Cubre:** Urea, Creatinina, Glucosa, Perfil hepático, Perfil lipídico, Orina 24 h

---

## DiaSys (Alemania)

**Alta calidad – laboratorios de referencia**

| Recurso | URL |
|---------|-----|
| Productos | https://www.diasys-diagnostics.com/products/ |
| Descargas | https://www.diasys-diagnostics.com/downloads/ |
| Búsqueda producto | https://www.diasys-diagnostics.com/product-search/ |
| IFU Reagentes | https://www.diasys-diagnostics.com/service-area/instructions-for-use-reagents/ |

**Cubre:** Enzimas, Electrolitos, Marcadores cardíacos, Glucosa GOD

**PDFs descargados:** glucosa_god_fs.pdf

---

## Abbott Diagnostics

**Para ELISA, Hormonas, Marcadores tumorales**  
**Requiere registro**

| Recurso | URL |
|---------|-----|
| IFU (Core Lab) | https://www.corelaboratory.abbott/ |

**Cubre:** Hormonas, Troponina, Marcadores tumorales, Perfil tiroideo

---

## Roche Diagnostics

**Referencia mundial – automatización**

| Recurso | URL |
|---------|-----|
| IFU / Productos | https://diagnostics.roche.com/global/en/products.html |

**Cubre:** ELISA, Hormonas, Marcadores tumorales, Inmunología

---

## Linear Chemicals (España)

| Recurso | URL |
|---------|-----|
| Química clínica | https://www.linear.es/en/portfolio-category/clinical-chemistry/ |

**PDFs descargados:** urea_bun_kr10382.pdf, ggt_ct10192.pdf, got_ast_kr10160.pdf

---

## Randox (Reino Unido)

| Recurso | URL |
|---------|-----|
| Portal IFU | https://ifu.randox.com/ |

*Requiere registro / solicitud de acceso.*

---

## Resumen de uso en LIS

**Tip:** No enlazar por examen. Enlazar por:
- Fabricante
- Familia de pruebas
- Código del reactivo

**Ejemplo:**
- Examen: Urea en orina  
- Fabricante: Wiener  
- Inserto: Urea UV AA  
- URL: PDF en access.wiener-lab.com  

---

## Descarga local (respaldo)

Para guardar copias locales de los insertos:

```powershell
cd c:\wamp64\www\john_ci4\database
powershell -ExecutionPolicy Bypass -File descargar_insertos.ps1
```

Los PDF se guardan en `insertos_pdf/` organizados por proveedor (wiener_lab, spinreact, linear, diasys).

---

*Última actualización: Feb 2026*
