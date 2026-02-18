# Script para descargar insertos PDF de laboratorios como respaldo
# Fuentes: Wiener Lab, SpinReact, Linear, DiaSys, BioSystems
# Ejecutar: powershell -ExecutionPolicy Bypass -File descargar_insertos.ps1

$baseDir = Split-Path -Parent $MyInvocation.MyCommand.Path
$insertosDir = Join-Path $baseDir "insertos_pdf"

# Lista de insertos: URL -> archivo local
$insertos = @(
    # ========== WIENER LAB (Argentina) - access.wiener-lab.com ==========
    @{ Url = "https://access.wiener-lab.com/VademecumDocumentos/Vademecum%20ingles/bilirrubina_total_aa_en.pdf"; File = "wiener_lab\bilirrubina_total_en.pdf" },
    @{ Url = "https://access.wiener-lab.com/VademecumDocumentos/Vademecum%20espanol/crp_hs_turbitest_aa_sp.pdf"; File = "wiener_lab\crp_hs_turbitest_es.pdf" },
    @{ Url = "https://access.wiener-lab.com/VademecumDocumentos/Vademecum%20ingles/ldh_p_uv_aa_en.pdf"; File = "wiener_lab\ldh_en.pdf" },
    @{ Url = "https://access.wiener-lab.com/VademecumDocumentos/Vademecum%20ingles/ige_calibrator_turbitest_aa_en.pdf"; File = "wiener_lab\ige_calibrator_en.pdf" },

    # ========== SPINREACT (Espana) - Sustratos ==========
    @{ Url = "https://www.spinreact.com/assets/files/Inserts/albumina.pdf"; File = "spinreact\albumina.pdf" },
    @{ Url = "https://www.spinreact.com/assets/files/Inserts/Bioquimica/bsis92_bili-t-dpd-2025.pdf"; File = "spinreact\bilirrubina_total_dpd_2025.pdf" },
    @{ Url = "https://www.spinreact.com/assets/files/Inserts/Bioquimica/bsis95_bili-d-dpd-2022-noves-precaucions-en-espera.pdf"; File = "spinreact\bilirrubina_directa_dpd.pdf" },
    @{ Url = "https://www.spinreact.com/assets/files/Inserts/Bioquimica/bsis13_crea-j-2022-nuevas-precauciones.pdf"; File = "spinreact\creatinina_jaffe.pdf" },
    @{ Url = "https://www.spinreact.com/assets/files/Inserts/Bioquimica/bsis77_creatinina-tr-enzimatica-2024-nova-normativa.pdf"; File = "spinreact\creatinina_enzimatica_2024.pdf" },
    @{ Url = "https://www.spinreact.com/assets/files/Inserts/glucose-lq.pdf"; File = "spinreact\glucosa_godpod_lq.pdf" },
    @{ Url = "https://www.spinreact.com/assets/files/Inserts/Bioquimica/BSIS17_GLU_TR_02_2016.pdf"; File = "spinreact\glucosa_godpod_trinder.pdf" },
    @{ Url = "https://www.spinreact.com/assets/files/Inserts/Bioquimica/BSIS19_GLU-UV_2017.pdf"; File = "spinreact\glucosa_hk_uv.pdf" },
    @{ Url = "https://www.spinreact.com/assets/files/Inserts/Bioquimica/bsis30_prot-tot-2022-noves-precaucions.pdf"; File = "spinreact\proteinas_totales.pdf" },
    @{ Url = "https://www.spinreact.com/assets/files/Inserts/Bioquimica/BSIS32_UREA-UV_2016.pdf"; File = "spinreact\urea_uv.pdf" },
    @{ Url = "https://www.spinreact.com/assets/files/Inserts/Bioquimica/bsis47_urea-lq-2022-noves-precaucions.pdf"; File = "spinreact\urea_lq.pdf" },
    @{ Url = "https://www.spinreact.com/assets/files/Inserts/uric-acid-acido-urico.pdf"; File = "spinreact\acido_urico.pdf" },
    @{ Url = "https://www.spinreact.com/assets/files/Inserts/Bioquimica/BSIS45_URIC-LQ_2016.pdf"; File = "spinreact\acido_urico_lq.pdf" },
    @{ Url = "https://www.spinreact.com/assets/files/Inserts/Bioquimica/bsis20_hemoglobin-drabkin-02-2025.pdf"; File = "spinreact\hemoglobina_drabkin.pdf" },

    # ========== SPINREACT - Enzimas ==========
    @{ Url = "https://www.spinreact.com/assets/files/Inserts/Bioquimica/BEIS09_GOT_2015.pdf"; File = "spinreact\got_ast_ifcc.pdf" },
    @{ Url = "https://www.spinreact.com/assets/files/Inserts/Bioquimica/BEIS11_GPT_ALT-2016.pdf"; File = "spinreact\gpt_alt_ifcc.pdf" },
    @{ Url = "https://www.spinreact.com/assets/files/Inserts/Bioquimica/BEIS08_GGT_2016.pdf"; File = "spinreact\ggt.pdf" },
    @{ Url = "https://www.spinreact.com/assets/files/Inserts/Bioquimica/BEIS16_LDH_02-2015.pdf"; File = "spinreact\ldh_dgkc.pdf" },
    @{ Url = "https://www.spinreact.com/assets/files/Inserts/Bioquimica/beis54_lipase-lqdggr-2024.pdf"; File = "spinreact\lipase_dggr_2024.pdf" },
    @{ Url = "https://www.spinreact.com/assets/files/Inserts/Bioquimica/BEIS27_AMILASA-LQ_02-2016.pdf"; File = "spinreact\amilasa_lq.pdf" },
    @{ Url = "https://www.spinreact.com/assets/files/Inserts/Bioquimica/beis07_alp-2025.pdf"; File = "spinreact\fosfatasa_alcalina_dgkc.pdf" },
    @{ Url = "https://www.spinreact.com/assets/files/Inserts/Bioquimica/BEIS02_CK_NAC_2015.pdf"; File = "spinreact\ck_nac.pdf" },
    @{ Url = "https://www.spinreact.com/assets/files/Inserts/Bioquimica/BEIS04_CK-MB_02-2015.pdf"; File = "spinreact\ck_mb.pdf" },
    @{ Url = "https://www.spinreact.com/assets/files/Inserts/Bioquimica/beis51_che-lq-2018.pdf"; File = "spinreact\colinesterasa_lq.pdf" },

    # ========== SPINREACT - Lipidos ==========
    @{ Url = "https://www.spinreact.com/assets/files/Inserts/Bioquimica/bsis11_colesterol-2025.pdf"; File = "spinreact\colesterol_chodpod.pdf" },
    @{ Url = "https://www.spinreact.com/assets/files/Inserts/triglycerides-trigliceridos.pdf"; File = "spinreact\trigliceridos_gpopod.pdf" },
    @{ Url = "https://www.spinreact.com/assets/files/Inserts/Bioquimica/BSIS37_HDLc_02-2018.pdf"; File = "spinreact\hdl_colesterol_directo.pdf" },
    @{ Url = "https://www.spinreact.com/assets/files/Inserts/ldl-cholesterol-d.pdf"; File = "spinreact\ldl_colesterol_directo.pdf" },

    # ========== SPINREACT - Inmunoquimica / Turbilatex ==========
    @{ Url = "https://www.spinreact.com/assets/files/Inserts/SERIE_SPINTECH_(TKB)/Turbilatex/tktlis44_ferr-4-1-cal-liof-2023.pdf"; File = "spinreact\ferritina_turbilatex_2023.pdf" },
    @{ Url = "https://www.spinreact.com/assets/files/Inserts/SERIE_SPINTECH_(TKB)/Bioquimica/TKBSIS49_TG_LIQ_2019.pdf"; File = "spinreact\trigliceridos_tg_liq_2019.pdf" },

    # ========== SPINREACT - Electrolitos (quimica humeda/colorimetrica) ==========
    @{ Url = "https://www.spinreact.com/assets/files/Inserts/Bioquimica/BSIS94_Na-LQ_2019.pdf"; File = "spinreact\electrolitos_sodio_lq.pdf" },
    @{ Url = "https://www.spinreact.com/assets/files/Inserts/Bioquimica/bsis93_k-lq-2019.pdf"; File = "spinreact\electrolitos_potasio_lq.pdf" },
    @{ Url = "https://www.spinreact.com/assets/files/Inserts/Bioquimica/bsis10_cloruro-2022-noves-precaucions.pdf"; File = "spinreact\electrolitos_cloruro.pdf" },
    @{ Url = "https://www.spinreact.com/assets/files/Inserts/Bioquimica/bsis09_ca-a-iii-2022-noves-precaucions.pdf"; File = "spinreact\electrolitos_calcio_arsenazo.pdf" },
    @{ Url = "https://www.spinreact.com/assets/files/Inserts/Bioquimica/bsis79_mg-xilidil-2022-noves-precaucions.pdf"; File = "spinreact\electrolitos_magnesio_xylidyl.pdf" },
    @{ Url = "https://www.spinreact.com/assets/files/Inserts/Bioquimica/bsis15_p-uv-2022-nuevas-precauciones-en-espera.pdf"; File = "spinreact\electrolitos_fosforo_uv.pdf" },

    # ========== SPINREACT - Coagulacion ==========
    @{ Url = "https://www.spinreact.com/assets/files/Inserts/Coagulacio/cois01_aptt-2024.pdf"; File = "spinreact\aptt_2024.pdf" },
    @{ Url = "https://www.spinreact.com/assets/files/Inserts/Coagulacio/cois11_pt-02_2022-noves-precaucions.pdf"; File = "spinreact\pt_protrombina.pdf" },
    @{ Url = "https://www.spinreact.com/assets/files/Inserts/Coagulacio/COIS03_FIB_2015_(marges).pdf"; File = "spinreact\fibrinogeno_clauss.pdf" },
    @{ Url = "https://www.spinreact.com/assets/files/Inserts/Coagulacio/cois08_d-dimer-2023-noves-precaucions-en-espera.pdf"; File = "spinreact\d_dimero.pdf" },

    # ========== LINEAR CHEMICALS (Espana) ==========
    @{ Url = "https://www.linear.es/wp-content/uploads/2018/03/KR10382.pdf"; File = "linear\urea_bun_kr10382.pdf" },
    @{ Url = "https://www.linear.es/wp-content/uploads/2018/03/CT10192.pdf"; File = "linear\ggt_ct10192.pdf" },
    @{ Url = "https://www.linear.es/ficheros/archivos/KR10160.pdf"; File = "linear\got_ast_kr10160.pdf" },

    # ========== DIASYS (Alemania) ==========
    @{ Url = "https://www.diasys-diagnostics.com/misc/download/?cHash=732672cafcf8d0be329c4884776b1395&tx_vierwddiasysproducts_download%5Bfile%5D=downloads%2FPackage+inserts+reagents+general%2FGlucose%2FGlucose+GOD%2F10+min.+Version%2FPI-e-GLUC_GOD_10-1.pdf&tx_vierwddiasysproducts_download%5Bmsds%5D=&_=1713944282"; File = "diasys\glucosa_god_fs.pdf" }
)

$total = $insertos.Count
$ok = 0
$err = 0

foreach ($item in $insertos) {
    $destPath = Join-Path $insertosDir $item.File
    $destDir = Split-Path -Parent $destPath

    if (-not (Test-Path $destDir)) {
        New-Item -ItemType Directory -Force -Path $destDir | Out-Null
    }

    try {
        Write-Host "Descargando: $($item.File)..."
        [Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12
        Invoke-WebRequest -Uri $item.Url -OutFile $destPath -UseBasicParsing -TimeoutSec 45
        $ok++
        Write-Host "  OK" -ForegroundColor Green
    }
    catch {
        Write-Host "  Error: $($_.Exception.Message)" -ForegroundColor Red
        $err++
    }
}

Write-Host "`nResumen: $ok descargados, $err fallidos de $total" -ForegroundColor Cyan
