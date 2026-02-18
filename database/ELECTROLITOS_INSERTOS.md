# Electrolitos – Insertos Oficiales

Guía de acceso a IFU para Na⁺, K⁺, Cl⁻, Ca²⁺, Mg²⁺, P (fósforo).

---

## Exámenes

| Electrolito | Símbolo | Método colorimétrico | Método ISE |
|-------------|---------|----------------------|------------|
| Sodio | Na⁺ | Inusual (más ISE) | Na/K/Cl (1 inserto) |
| Potasio | K⁺ | Pyruvate Kinase | Na/K/Cl (1 inserto) |
| Cloro | Cl⁻ | Tiocianato-Hg | Na/K/Cl (1 inserto) |
| Calcio | Ca²⁺ | Arsenazo III | — |
| Magnesio | Mg²⁺ | Xylidyl Blue | — |
| Fósforo | P | Fosfomolibdato UV | — |

---

## MÉTODO 1: Química húmeda / colorimétrica

*Para laboratorios manuales o semiautomatizados*

### Wiener Lab (Argentina)

**Muy usado en Bolivia**

| Recurso | URL |
|---------|-----|
| Reactivos | https://www.wiener-lab.com.ar/Productos/Reactivos |
| Manuales | https://www.wiener-lab.com.ar/Productos/Manuales |
| Vademecum | https://access.wiener-lab.com/ |

**Insertos por analito:**
- Sodio colorimétrico
- Potasio colorimétrico
- Cloruros colorimétrico
- Calcio Arsenazo III
- Magnesio Xylidyl Blue
- Fósforo UV

*Buscar en catálogo/vademecum por nombre del reactivo.*

---

### SpinReact (España)

**Insertos claros → ideales para sistemas**

| Electrolito | Método | Archivo | URL |
|-------------|--------|---------|-----|
| Sodio | ONPG colorimétrico | sodio_lq.pdf | [BSIS94_Na-LQ_2019.pdf](https://www.spinreact.com/assets/files/Inserts/Bioquimica/BSIS94_Na-LQ_2019.pdf) |
| Potasio | Pyruvate Kinase | potasio_lq.pdf | [bsis93_k-lq-2019.pdf](https://www.spinreact.com/assets/files/Inserts/Bioquimica/bsis93_k-lq-2019.pdf) |
| Cloruro | Thiocianato-Hg | cloruro.pdf | [bsis10_cloruro](https://www.spinreact.com/assets/files/Inserts/Bioquimica/bsis10_cloruro-2022-noves-precaucions.pdf) |
| Calcio | Arsenazo III | calcio_arsenazo.pdf | [bsis09_ca-a-iii](https://www.spinreact.com/assets/files/Inserts/Bioquimica/bsis09_ca-a-iii-2022-noves-precaucions.pdf) |
| Magnesio | Xylidyl Blue | magnesio_xylidyl.pdf | [bsis79_mg-xilidil](https://www.spinreact.com/assets/files/Inserts/Bioquimica/bsis79_mg-xilidil-2022-noves-precaucions.pdf) |
| Fósforo | Fosfomolibdato UV | fosforo_uv.pdf | [bsis15_p-uv](https://www.spinreact.com/assets/files/Inserts/Bioquimica/bsis15_p-uv-2022-nuevas-precauciones-en-espera.pdf) |

**Sección electrolitos:** https://www.spinreact.com/es/lista-productos/bioquimica-clinica/electrolitos.html

---

### BioSystems (España)

**Muy bien documentados**

| Recurso | URL |
|---------|-----|
| Reactivos | https://www.biosystems.es/es/reactivos |

**Insertos:** Calcium Arsenazo, Magnesium, Phosphorus, Chloride

*Navegar por categoría electrolitos en el sitio.*

---

## MÉTODO 2: ISE (electrodos selectivos de ion)

*Para autoanalizadores y gasómetros. Un inserto cubre Na⁺, K⁺ y Cl⁻.*

### Roche Diagnostics

| Recurso | URL |
|---------|-----|
| Productos | https://diagnostics.roche.com/global/en/products.html |

**Inserto:** ISE Sodium / Potassium / Chloride
- Método indirecto / directo
- 1 inserto → 3 electrolitos

---

### Abbott Diagnostics

| Recurso | URL | Nota |
|---------|-----|------|
| Core Lab | https://www.corelaboratory.abbott/ | Requiere registro |

**Inserto:** ISE Na / K / Cl (Architect / Alinity)

---

### Siemens Healthineers

| Recurso | URL |
|---------|-----|
| Sitio | https://www.siemens-healthineers.com/ |

**Inserto:** ISE Sodium, Potassium, Chloride

---

### SpinReact ISE

**Pack reactivos ISE** (solución estándar calibración)

| Recurso | URL |
|---------|-----|
| ISE Electrolitos | https://www.spinreact.com/es/lista-productos/bioquimica-clinica/ise-electrolitos.html |

Ref. 1802001, 1802002 (850 mL / 1280 mL)

---

## Modelado en LIS (importante)

**No registrar uno por uno:**
```
Sodio ❌
Potasio ❌
Cloro ❌
```

**Registrar así:**

**Opción A – Química húmeda (colorimétrico)**  
| Inserto | Aplica a |
|---------|----------|
| Calcio Arsenazo III – SpinReact | Calcio |
| Magnesio Xylidyl Blue – SpinReact | Magnesio |
| Fósforo UV – SpinReact | Fósforo |
| Sodio LQ – SpinReact | Sodio |
| Potasio LQ – SpinReact | Potasio |
| Cloruro Thiocianato – SpinReact | Cloro |

**Opción B – ISE**  
| Inserto | Aplica a |
|---------|----------|
| ISE Na/K/Cl – Roche | Sodio, Potasio, Cloro |

👉 Un inserto ISE puede cubrir varios exámenes.

---

## Resumen rápido

| Examen | Método | Inserto típico |
|--------|--------|----------------|
| Sodio | ISE o colorimétrico | Na/K/Cl (ISE) o Sodio LQ (SpinReact) |
| Potasio | ISE o colorimétrico | Na/K/Cl (ISE) o Potasio Pyruvate Kinase (SpinReact) |
| Cloro | ISE o colorimétrico | Na/K/Cl (ISE) o Cloruro Thiocianato (SpinReact) |
| Calcio | Colorimétrico | Arsenazo III |
| Magnesio | Colorimétrico | Xylidyl Blue |
| Fósforo | UV | Phosphorus UV / Fosfomolibdato |

---

## Descarga local

Los insertos SpinReact de electrolitos se descargan con:

```powershell
cd c:\wamp64\www\john_ci4\database
powershell -ExecutionPolicy Bypass -File descargar_insertos.ps1
```

Se guardan en `insertos_pdf/spinreact/`:
- electrolitos_sodio_lq.pdf
- electrolitos_potasio_lq.pdf
- electrolitos_cloruro.pdf
- electrolitos_calcio_arsenazo.pdf
- electrolitos_magnesio_xylidyl.pdf
- electrolitos_fosforo_uv.pdf
