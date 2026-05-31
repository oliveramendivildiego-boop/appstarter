<?php
/**
 * Convierte "valores biocenter.csv" al formato de importación del sistema.
 * Uso: php database/convert_valores_biocenter_csv.php [entrada] [salida] [base_datos]
 */
declare(strict_types=1);

error_reporting(E_ALL & ~E_DEPRECATED);

$sourcePath = $argv[1] ?? 'C:/Users/Diego/Desktop/valores biocenter.csv';
$outputPath = $argv[2] ?? 'C:/Users/Diego/Desktop/valores_biocenter_import.csv';
$database   = $argv[3] ?? 'laboratorio';
$host       = 'localhost';
$user       = 'root';
$pass       = '';
$prefix     = 'dom_';

if (! is_file($sourcePath)) {
    fwrite(STDERR, "No existe el archivo de entrada: {$sourcePath}\n");
    exit(1);
}

$mysqli = new mysqli($host, $user, $pass, $database);
if ($mysqli->connect_error) {
    fwrite(STDERR, 'Error conexión: ' . $mysqli->connect_error . "\n");
    exit(1);
}
$mysqli->set_charset('utf8mb4');

function stripAccents(string $s): string
{
    if (class_exists('Normalizer')) {
        $n = Normalizer::normalize($s, Normalizer::FORM_D);
        if (is_string($n)) {
            $s = preg_replace('/\p{M}+/u', '', $n) ?? $s;
        }
    } else {
        $map = [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U', 'Ñ' => 'N',
        ];
        $s = strtr($s, $map);
    }

    return $s;
}

function norm(string $s): string
{
    $s = trim(stripAccents($s));
    $s = preg_replace('/^\*+|\*+$/', '', $s) ?? $s;
    $s = preg_replace('/\s+/u', ' ', $s) ?? $s;

    return mb_strtoupper($s, 'UTF-8');
}

function normAnalisisKey(string $s): string
{
    $s = norm($s);
    $s = preg_replace('/\s*\([^)]*\)\s*/', ' ', $s) ?? $s;
    $s = trim($s);

    return str_replace(['.', ' ', '-', '/', '°', 'º'], '', $s);
}

function normCategoria(string $cat, string $prueba = ''): string
{
    $c = norm($cat);
    $p = norm($prueba);

    if ($c === 'INMUNOLOGIA') {
        return 'HORMONAS';
    }
    if ($c === 'CUAGULOGRAMA') {
        return 'COAGULOGRAMA';
    }
    if (in_array($p, ['GRUPO SANGUINEO', 'COOMBS DIRECTO', 'COOMBS INDIRECTO', 'GOTA GRUESA', 'CELULAS LE'], true)) {
        return 'HEMATOLOGIA';
    }
    if (in_array($c, ['GRUPO SANGUINEO', 'COOMBS DIRECTO', 'COOMBS INDIRECTO', 'GOTA GRUESA'], true)) {
        return 'HEMATOLOGIA';
    }

    return $c;
}

/** @return array<string, list<string>> clave CSV (sin puntos) => posibles claves DB */
function biocenterAnalisisAliasKeys(): array
{
    return [
        'GLUCOSA'                    => ['GLICEMIA'],
        'GLUCOSAPOSPRANDIAL'         => ['GLUCPOSTPRANDIAL'],
        'GLUCOSAPOSTPRANDIAL'        => ['GLUCPOSTPRANDIAL'],
        'GLUCOSAPOSESTIMULO1H'       => ['GLUCPOSTPRANDIAL', 'GLICEMIA'],
        'GLUCOSAPOSESTIMULO2HRS'     => ['TOLERANCIADEGLUCOSA', 'GLICEMIA'],
        'GLUCOSABASEAL'              => ['TOLERANCIADEGLUCOSA', 'GLICEMIA'],
        'GLUCOSAPRIMERAHORA'         => ['TOLERANCIADEGLUCOSA'],
        'GLUCOSASEGUNDAHORA'         => ['TOLERANCIADEGLUCOSA'],
        'GLUCOSATERCERAHORA'         => ['TOLERANCIADEGLUCOSA'],
        'HEMOGLOBINAGLICOSILADA'     => ['HBGLICOSILADA'],
        'HEMOGLOBINAGLICOSILADAHBA1C'=> ['HBGLICOSILADA'],
        'HBA1C'                      => ['HBGLICOSILADA'],
        'UREA'                       => ['UREA'],
        'NITROGENOUREICO'            => ['NITROGENOUREICO', 'BUN'],
        'CREATININA'                 => ['CREATININA'],
        'ACIDOURICO'                 => ['ACIDOURICO'],
        'COLESTEROL'                 => ['COLESTEROL'],
        'HDLCOLESTEROL'              => ['CHDL'],
        'LDLCOLESTEROL'              => ['CLDL'],
        'VLDLCOLESTEROL'             => ['VLDL'],
        'TRIGLICERIDOS'              => ['TRIGLICERIDOS'],
        'PROTEINASTOTALES'           => ['PROTEINAS'],
        'RELACIONAG'                 => ['RAG'],
        'ALBUMINA'                   => ['ALBUMINA'],
        'GLOBULINA'                  => ['GLOBULINA'],
        'BILIRRUBINATOTAL'           => ['BILIRRUBINADIT'],
        'BILIRRUBINADIRECTA'         => ['BILIRRUBINADIT'],
        'BILIRRUBINAINDIRECTA'       => ['BILIRRUBINADIT'],
        'NILIRRUBINASINDIRECTAS'     => ['BILIRRUBINADIT'],
        'NILIRRUBINAS'               => ['BILIRRUBINADIT'],
        'REIESGOCARDIACO'            => ['RIESGOCARDIACO'],
        'RIESGOCARDIACO'             => ['RIESGOCARDIACO'],
        'PROPONINAT'                 => ['TROPONINAT'],
        'PROPONINAI'                 => ['TROPONINAI'],
        'MAGNECIO'                   => ['MAGNESIO'],
        'CALCIOTOTAL'                => ['CALCIO'],
        'CALCIOIONICO'               => ['CALCIO'],
        'CALCIOIONICOCAI'            => ['CALCIO'],
        'LITIO'                      => ['LITIO'],
        'LDH'                        => ['LDH'],
        'CPK'                        => ['CPK'],
        'HOMOCISTEINA'               => ['HOMOCISTEINA'],
        'LIPIDOSTOTALES'             => ['LIPIDOSTOTALES'],
        'MIOGLOBINA'                 => ['MIOGLOBINA'],
        'NTPROBNP'                   => ['NTPROBNP'],
        'AMONIOSERICO'               => ['AMONIO'],
        'FOSFATASAALCALINA'          => ['FOSFATASAALCALINA'],
        'FALCALINA'                  => ['FOSFATASAALCALINA'],
        'GOTAST'                     => ['GOTAST'],
        'GPTALT'                     => ['GPT', 'GPTALT'],
        'GPT'                        => ['GPT'],
        'FOSFATASAALCALINA'          => ['FOSFATASAALCALINA'],
        'FOSFATASAACIDA'             => ['FOSFATASAACIDA'],
        'GGT'                        => ['GGT'],
        'AMILASA'                    => ['AMILASA'],
        'LIPASA'                     => ['LIPASA'],
        'TIEMPODEPROTROMBINA'        => ['TIEMPODEPROTROMBINAINR', 'TIEMPODEPROTROMBINATP'],
        'INR'                        => ['IRN', 'TIEMPODEPROTROMBINAINR'],
        'PORCENTAJEDEACTIVIDAD'      => ['PORCENTAJEDEACTIVIDADDEPROTROMBINA', 'ACTIVIDADDEPROTROMBINA'],
        'ACTIVIDAD'                  => ['PORCENTAJEDEACTIVIDADDEPROTROMBINA'],
        'APTT'                       => ['APTT', 'TIEMPODETROMBOPLASTINA'],
        'FIBRINOGENO'                => ['FIBRINOGENO'],
        'DIMEROD'                    => ['DIMEROD'],
        'DIMERD'                     => ['DIMEROD'],
        'TIEMPODESANGRIA'            => ['TIEMPODESANGRIA'],
        'TIEMPODECOAGULACION'        => ['TIEMPODECOAGULACION'],
        'TIEMPODETROMBINA'           => ['TIEMPODETROMBOPLASTINA'],
        'GOTAGRUESA'                 => ['GOTAGRUESA'],
        'GRUPOYFACTORRH'             => ['GRUPOSANGUINEO'],
        'MUESTRA'                    => ['GRUPOSANGUINEO'],
        'COOMBSDIRECTO'              => ['COOMBSDIRECTO'],
        'COOMBSINDIRECTO'            => ['COOMBSINDIRECTO'],
        'CELULASLE'                  => ['CELULASLE'],
        'FERRITINA'                  => ['FERRITINA'],
        'HIERROSERICO'               => ['HIERROSERICO'],
        'TRANSFERRINA'               => ['TRANSFERRINATIBC'],
        'TSH'                        => ['TSH'],
        'T3'                         => ['T3'],
        'T4'                         => ['T4'],
        'T4LIBRE'                    => ['T4LIBRE'],
        'T4LIBRET4L'                 => ['T4LIBRE'],
        'CALCIO'                     => ['CALCIO'],
        'SODIO'                      => ['SODIO'],
        'POTASIO'                    => ['POTASIO'],
        'CLORO'                      => ['CLORO'],
        'CLORUROS'                   => ['CLORO'],
        'MAGNESIO'                   => ['MAGNESIO'],
        'FOSFORO'                    => ['FOSFORO'],
        'FOSFOROINORGANICO'          => ['FOSFORO'],
        'VITAMINAD'                  => ['VITAMINAD'],
        'VITAMINAB12'                => ['VITAMINAB12'],
        'VITAMINAB9'                 => ['ACIDOFOLICO'],
        'ACIDOFOLICO'                => ['ACIDOFOLICO'],
        'INSULINA'                   => ['INSULINABASALAM', 'INSULINA'],
        'PROGESTERONA'               => ['PROGESTERONA'],
        'ESTRADIOL'                  => ['ESTRADIOL'],
        'TESTOSTERONA'               => ['TESTOSTERONALIBRE', 'TESTOSTERONA'],
        'CORTISOL'                   => ['CORTISOL'],
        'PROLACTINA'                 => ['PROLACTINA'],
        'PSA'                        => ['PSA'],
        'CEA'                        => ['CEA'],
        'CA125'                      => ['CA125'],
        'CA199'                      => ['CA199'],
        'ALFAFETOPROTEINA'           => ['ALFAFETOPROTEINA'],
        'AFP'                        => ['ALFAFETOPROTEINA'],
        'PCR'                        => ['PCR'],
        'VSG1HORA'                   => ['VSG1HORA'],
        'VSG2HORA'                   => ['VSG2HORA'],
        '1RAHORA'                    => ['VSG1HORA'],
        '2DAHORA'                    => ['VSG2HORA'],
        'VELOCIDADDEERITROSEDIMENTACION'=> ['VSG1HORA', 'ERITROSEDIMENTACION'],
    ];
}

/** @return list<string> */
function expandDbAnalisisKeys(string $dbName): array
{
    $keys   = [normAnalisisKey($dbName), norm($dbName)];
    $dbKey  = normAnalisisKey($dbName);
    $aliases = biocenterAnalisisAliasKeys();
    foreach ($aliases as $csvKey => $dbKeys) {
        foreach ($dbKeys as $dk) {
            if ($dbKey === $dk || str_contains($dbKey, $dk) || str_contains($dk, $dbKey)) {
                $keys[] = $csvKey;
            }
        }
    }

    return array_values(array_unique($keys));
}

/** @return list<string> */
function categoriaCandidates(string $cat, string $prueba): array
{
    $c   = normCategoria($cat, $prueba);
    $p   = norm($prueba);
    $out = [$c];

    if (str_contains($p, 'PERFIL LIPIDICO') || str_contains(norm($cat), 'QUIMICA')) {
        $out[] = 'PERFIL LIPIDICO';
    }
    if (str_contains($p, 'PERFIL HEPATICO') || str_contains($p, 'BILIRRUBINA') || str_contains($p, 'GOT') || str_contains($p, 'GPT')) {
        $out[] = 'PERFIL HEPATICO';
    }
    if (str_contains($p, 'PERFIL RENAL') || in_array(normAnalisisKey($prueba), ['UREA', 'CREATININA', 'ACIDOURICO', 'NITROGENOUREICO'], true)) {
        $out[] = 'QUIMICA SANGUINEA';
    }
    if (str_contains($p, 'PERFIL METABOLICO') || str_contains($p, 'GLUCOSA') || str_contains($p, 'GLICEMIA')) {
        $out[] = 'QUIMICA SANGUINEA';
    }
    if (str_contains($p, 'PERFIL PROTEICO')) {
        $out[] = 'QUIMICA SANGUINEA';
    }
    if (str_contains($p, 'PERFIL PANCREATICO')) {
        $out[] = 'QUIMICA SANGUINEA';
    }
    if (str_contains($p, 'COAGULACION') || $c === 'COAGULOGRAMA') {
        $out[] = 'COAGULOGRAMA';
    }
    if (str_contains($p, 'ELECTROLITO') || in_array($c, ['ELECTROLITOS'], true)) {
        $out[] = 'ELECTROLITOS';
    }
    if (str_contains($p, 'TIROIDEO') || in_array($c, ['PERFIL TIROIDEO'], true)) {
        $out[] = 'PERFIL TIROIDEO';
    }
    if (str_contains($p, 'HIERRO') || $c === 'PERFIL DE HIERRO') {
        $out[] = 'PERFIL DE HIERRO';
    }

    return array_values(array_unique($out));
}

function normSexo(string $s): string
{
    $k = norm($s);
    if (in_array($k, ['M', 'MASCULINO', 'HOMBRE', 'VARON'], true)) {
        return 'masculino';
    }
    if (in_array($k, ['F', 'FEMENINO', 'MUJER', 'FEEMENINO', 'FEMENINA'], true)) {
        return 'femenino';
    }

    return 'ambos';
}

function normValor(string $s): string
{
    $s = trim($s);
    if ($s === '') {
        return '';
    }
    if (str_contains($s, ',')) {
        $s = str_replace('.', '', $s);
        $s = str_replace(',', '.', $s);
    } elseif (preg_match('/^\d{1,3}\.\d{3}$/', $s)) {
        $s = str_replace('.', '', $s);
    }

    if (is_numeric($s)) {
        $f = (float) $s;
        if (abs($f - (int) $f) < 0.00001) {
            return (string) (int) $f;
        }

        return rtrim(rtrim(number_format($f, 4, '.', ''), '0'), '.');
    }

    return $s;
}

function mapTipoResultado(string $raw, array $opcionesByName): int
{
    $k = norm($raw);
    if ($k === 'NUMERICO') {
        return 3;
    }
    if (isset($opcionesByName[$k])) {
        return (int) $opcionesByName[$k];
    }
    foreach ($opcionesByName as $name => $id) {
        if (str_contains($k, $name) || str_contains($name, $k)) {
            return (int) $id;
        }
    }

    return 3;
}

/** @return list<string> */
function pruebaCandidates(string $prueba, string $analisis = ''): array
{
    $p   = norm($prueba);
    $a   = norm($analisis);
    $out = [$p];
    $map = [
        'LEUCOGRAMA'                       => ['HEMOGRAMA'],
        'PLAQUETARIO'                      => ['HEMOGRAMA'],
        'RETICULOCITOS'                    => ['RECUENTO DE RETICULOCITOS', 'HEMOGRAMA'],
        'INDICES HEMATIMETRICOS'           => ['HEMOGRAMA', 'INDICES HEMATIMETRICOS'],
        'INDICES HEMATOMETRICOS'           => ['HEMOGRAMA', 'INDICES HEMATIMETRICOS'],
        'VELOCIDAD DE ERITROSEDIMENTACION' => ['ERITROSEDIMENTACION', 'HEMOGRAMA'],
        'VSG'                              => ['ERITROSEDIMENTACION', 'HEMOGRAMA'],
        'PLAQUETAS'                        => ['HEMOGRAMA', 'PLAQUETAS'],
        'PERFIL DE COAGULACION'            => ['COAGULOGRAMA'],
        'GRUPO Y FACTOR (RH)'              => ['GRUPO SANGUINEO'],
        'GRUPO Y FACTOR  (RH)'             => ['GRUPO SANGUINEO'],
        'GRUPO Y FACTOR RH'                => ['GRUPO SANGUINEO'],
        'PERFIL RENAL'                     => [],
        'PERFIL LIPIDICO'                  => [],
        'PERFIL METABOLICO'                => [],
        'PERFIL PANCREATICO'               => [],
        'PERFIL PROTEICO'                  => [],
        'PERFIL HEPATICO'                  => [],
        'PRUEBA DE TOLERANCIA ORAL A LA GLUCOSA (PTOG)' => ['TOLERANCIA DE GLUCOSA'],
        'PRUEBA DE TOLERANCIA ORAL A LA GLUCOSA' => ['TOLERANCIA DE GLUCOSA'],
    ];
    if (isset($map[$p])) {
        $aliases = $map[$p];
        if ($a !== '' && $a !== $p) {
            $out = array_merge($aliases, $out);
        } else {
            $out = array_merge($out, $aliases);
        }
    }

    return array_values(array_unique($out));
}

/** @return list<string> */
function analisisCandidates(string $analisis): array
{
    $a   = norm($analisis);
    $out = [$a];
    $map = [
        'SEGMENTADOS'           => ['NEUTROFILOS', 'NEUTROFILO'],
        'NEUTROFILOS'           => ['NEUTROFILOS'],
        'CAYADOS'               => ['CAYADOS'],
        'LINFOCITO'             => ['LINFOCITOS', 'LINFOCITO'],
        'MONOCITO'              => ['MONOCITOS', 'MONOCITO'],
        'BASOFILO'              => ['BASOFILOS', 'BASOFILO'],
        'EOSINOFILOS'           => ['EOSINOFILOS', 'EOSINOFILO'],
        'ERITROCITOS'           => ['ERITROCITOS'],
        'HEMATOCRITO'           => ['HEMATOCRITO'],
        'HEMOGLOBINA'           => ['HEMOGLOBINA'],
        'LEUCOCITOS'            => ['LEUCOCITOS'],
        'PLAQUETAS'             => ['PLAQUETAS'],
        'VCM'                   => ['VCM'],
        'VCMFL'                 => ['VCM'],
        'HCM'                   => ['HCM'],
        'HBCM'                  => ['HCM', 'CHCM'],
        'HBCMFL'                => ['CHCM'],
        'CHCM'                  => ['CHCM'],
        'VCMFL'                 => ['VCM'],
        'CHBCM'                 => ['CHCM'],
        '1RAHORA'               => ['VSG1HORA', 'VELOCIDADDEERITROSEDIMENTACION', 'VSG 1° HORA'],
        '2DAHORA'               => ['VSG2HORA'],
        'INDICEDEKATZ'          => ['INDICEDEKATZ', 'KATZ'],
        'RECUENTODEPLAQUETAS'   => ['PLAQUETAS', 'RECUENTO DE PLAQUETAS'],
        'RECUENTODERETICULOCITOS'=> ['RECUENTO DE RETICULOCITOS'],
    ];
    $aDotless = normAnalisisKey($analisis);
    if (! in_array($aDotless, $out, true)) {
        $out[] = $aDotless;
    }
    if (isset($map[$a])) {
        $out = array_merge($out, $map[$a]);
    }
    if (isset($map[$aDotless])) {
        $out = array_merge($out, $map[$aDotless]);
    }
    foreach (biocenterAnalisisAliasKeys() as $csvKey => $dbKeys) {
        if ($aDotless === $csvKey || $a === $csvKey) {
            $out[] = $csvKey;
            foreach ($dbKeys as $dk) {
                $out[] = $dk;
            }
        }
    }

    return array_values(array_unique($out));
}

// Poblaciones
$poblacionByName = [];
$res = $mysqli->query("SELECT id_poblacion, name FROM {$prefix}poblacion ORDER BY id_poblacion");
while ($row = $res->fetch_assoc()) {
    $poblacionByName[norm((string) $row['name'])] = (int) $row['id_poblacion'];
}
$res->free();

function resolvePoblacionId(string $name, array $byName): int
{
    $n = trim($name);
    if ($n === '') {
        return $byName['TODOS'] ?? 15;
    }
    $n = norm($n);
    if (isset($byName[$n])) {
        return $byName[$n];
    }

    foreach (['PERRO', 'PERROS', 'GATO', 'GATOS'] as $vet) {
        if ($n === $vet || str_contains($n, $vet)) {
            return $byName['TODOS'] ?? 15;
        }
    }

    $tokenMap = [
        'RECIEN NACIDO'  => 'RECIEN NACIDO',
        'RECIEN NACIDOS' => 'RECIEN NACIDO',
        'LACTANTE'       => 'LACTANTE',
        'NINOS'          => 'NIÑO PEQUEÑO',
        'NIÑOS'          => 'NIÑO PEQUEÑO',
        'NINO'           => 'NIÑO PEQUEÑO',
        'NINO PEQUEÑO'   => 'NIÑO PEQUEÑO',
        'NIÑO PEQUEÑO'   => 'NIÑO PEQUEÑO',
        'NINO ADOLESCENTE' => 'ADOLESCENTE',
        'NINO ADOLOCENTE' => 'ADOLESCENTE',
        'NIÑO ADOLESCENTE' => 'ADOLESCENTE',
        'NIÑO ADULTO MAYOR' => 'ADULTO MAYOR',
        'NIÑOS ADULTO MAYOR' => 'ADULTO MAYOR',
        'NIÑO ADULTO'    => 'ADULTO',
        'ADULTO ADULTO MAYOR' => 'ADULTO',
        'TODOS VARON'    => 'TODOS',
        'TODOS MUJER'    => 'TODOS',
        'NINO ESCOLAR'   => 'NIÑO ESCOLAR',
        'NIÑO ESCOLAR'   => 'NIÑO ESCOLAR',
        'ADOLESCENTE'    => 'ADOLESCENTE',
        'ADULTO MAYOR'   => 'ADULTO MAYOR',
        'ADULTO'         => 'ADULTO',
        'TODOS'          => 'TODOS',
        'TODO'           => 'TODOS',
        'VARON'          => 'TODOS',
        'MUJER'          => 'TODOS',
    ];
    foreach ($tokenMap as $needle => $canonical) {
        if (str_contains($n, $needle) && isset($byName[$canonical])) {
            return $byName[$canonical];
        }
    }

    foreach ($byName as $key => $id) {
        if ($key !== '' && strlen($key) > 3 && (str_contains($n, $key) || str_contains($key, $n))) {
            return $id;
        }
    }

    return $byName['TODOS'] ?? 15;
}

$opcionesByName = [];
$res = $mysqli->query("SELECT opciones_id, opciones FROM {$prefix}opciones ORDER BY opciones_id");
while ($row = $res->fetch_assoc()) {
    $opcionesByName[norm((string) $row['opciones'])] = (int) $row['opciones_id'];
}
$res->free();

/** @var array<string, array<string, mixed>> */
$index = [];
/** @var array<string, list<array<string, mixed>>> */
$indexByCatAna = [];
/** @var array<string, list<array<string, mixed>>> */
$globalSimple = [];

function registerGlobalSimple(array &$globalSimple, array $meta, string $dbName, int $pob, string $sexo): void
{
    foreach (expandDbAnalisisKeys($dbName) as $key) {
        $gs = "GS|{$key}|{$pob}|{$sexo}";
        $globalSimple[$gs] ??= [];
        $globalSimple[$gs][] = $meta;
    }
    if ($sexo !== 'ambos') {
        foreach (expandDbAnalisisKeys($dbName) as $key) {
            $gs = "GS|{$key}|{$pob}|ambos";
            $globalSimple[$gs] ??= [];
            $globalSimple[$gs][] = $meta;
        }
    }
}

/** @param list<array<string, mixed>> $list */
function pickSingleMeta(array $list): ?array
{
    if ($list === []) {
        return null;
    }
    $unique = [];
    foreach ($list as $m) {
        $unique[(int) ($m['id_referencia'] ?? 0)] = $m;
    }
    if (count($unique) === 1) {
        return array_values($unique)[0];
    }

    return null;
}

$sql = "
SELECT
  ana.name AS categoria,
  pri.name AS prueba,
  pri.prianacategoria_id,
  pri.compleja,
  sec.secanacategoria_id,
  sec.nombre AS analisis_sec,
  sec.paciente_id AS poblacion_sec,
  sec.sexo AS sexo_sec,
  pr.priresultados_id,
  pr.id_poblacion AS poblacion_pri,
  pr.sexo AS sexo_pri
FROM {$prefix}anacategoria ana
LEFT JOIN {$prefix}prianacategoria pri ON ana.anacategoria_id = pri.anacategoria_id
  AND (pri.deleted = 0 OR pri.deleted IS NULL)
LEFT JOIN {$prefix}secanacategoria sec ON pri.prianacategoria_id = sec.prianacategoria_id
  AND (sec.deleted = 0 OR sec.deleted IS NULL)
LEFT JOIN {$prefix}priresultados pr ON pri.prianacategoria_id = pr.prianacategoria_id
  AND (pr.deleted = 0 OR pr.deleted IS NULL)
WHERE (ana.deleted = 0 OR ana.deleted IS NULL)
  AND pri.prianacategoria_id IS NOT NULL
";

$res = $mysqli->query($sql);
while ($row = $res->fetch_assoc()) {
    $cat  = norm((string) ($row['categoria'] ?? ''));
    $pru  = norm((string) ($row['prueba'] ?? ''));
    $comp = (int) ($row['compleja'] ?? 0) === 1;

    if ($comp && ! empty($row['secanacategoria_id']) && trim((string) ($row['analisis_sec'] ?? '')) !== '') {
        $ana  = norm((string) $row['analisis_sec']);
        $anaK = normAnalisisKey((string) $row['analisis_sec']);
        $pob  = (int) ($row['poblacion_sec'] ?? 0);
        $sexo = normSexo((string) ($row['sexo_sec'] ?? 'ambos'));
        $meta = [
            'tipo'          => 'compuesto',
            'id_prueba'     => (int) $row['prianacategoria_id'],
            'id_referencia' => (int) $row['secanacategoria_id'],
            'poblacion_id'  => $pob,
            'grupo'         => trim((string) $row['categoria']),
            'prueba'        => trim((string) $row['prueba']),
            'analisis'      => trim((string) $row['analisis_sec']),
        ];
        $index["C|{$cat}|{$pru}|{$ana}|{$pob}|{$sexo}"] = $meta;
        $index["C|{$cat}|{$pru}|{$anaK}|{$pob}|{$sexo}"] = $meta;
        foreach (expandDbAnalisisKeys((string) $row['analisis_sec']) as $ak) {
            $kCa = "CA|{$cat}|{$ak}|{$pob}|{$sexo}";
            $indexByCatAna[$kCa] ??= [];
            $indexByCatAna[$kCa][] = $meta;
        }
    }

    if (! $comp && ! empty($row['priresultados_id'])) {
        $pob  = (int) ($row['poblacion_pri'] ?? 0);
        $sexo = normSexo((string) ($row['sexo_pri'] ?? 'ambos'));
        $meta = [
            'tipo'          => 'simple',
            'id_prueba'     => (int) $row['prianacategoria_id'],
            'id_referencia' => (int) $row['priresultados_id'],
            'poblacion_id'  => $pob,
            'grupo'         => trim((string) $row['categoria']),
            'prueba'        => trim((string) $row['prueba']),
            'analisis'      => trim((string) $row['prueba']),
        ];
        $index["S|{$cat}|{$pru}|{$pob}|{$sexo}"] = $meta;
        $index["S|{$cat}|{$pru}|{$pob}|ambos"]  = $index["S|{$cat}|{$pru}|{$pob}|ambos"] ?? $meta;
        $anaKey = normAnalisisKey((string) $row['prueba']);
        if ($anaKey !== $pru) {
            $index["S|{$cat}|{$anaKey}|{$pob}|{$sexo}"] = $meta;
            $index["S|{$cat}|{$anaKey}|{$pob}|ambos"]  = $index["S|{$cat}|{$anaKey}|{$pob}|ambos"] ?? $meta;
        }
        registerGlobalSimple($globalSimple, $meta, (string) $row['prueba'], $pob, $sexo);
    }
}
$res->free();

$poblacionTodosId = $poblacionByName['TODOS'] ?? 15;
$GLOBALS['poblacionTodosId'] = $poblacionTodosId;

function findMeta(
    array $index,
    array $indexByCatAna,
    array $globalSimple,
    string $cat,
    string $prueba,
    string $analisis,
    int $pobId,
    string $sexo
): ?array {
    foreach (categoriaCandidates($cat, $prueba) as $catTry) {
        foreach (pruebaCandidates($prueba, $analisis) as $pru) {
            foreach (analisisCandidates($analisis) as $ana) {
                foreach ([$ana, normAnalisisKey($ana)] as $anaKey) {
                    $keyC = "C|{$catTry}|{$pru}|{$anaKey}|{$pobId}|{$sexo}";
                    if (isset($index[$keyC])) {
                        return $index[$keyC];
                    }
                    if ($sexo !== 'ambos') {
                        $keyCA = "C|{$catTry}|{$pru}|{$anaKey}|{$pobId}|ambos";
                        if (isset($index[$keyCA])) {
                            return $index[$keyCA];
                        }
                    }
                }
            }
        }

        foreach (pruebaCandidates($prueba, $analisis) as $pru) {
            $keyS = "S|{$catTry}|{$pru}|{$pobId}|{$sexo}";
            if (isset($index[$keyS])) {
                return $index[$keyS];
            }
            if ($sexo !== 'ambos') {
                $keySA = "S|{$catTry}|{$pru}|{$pobId}|ambos";
                if (isset($index[$keySA])) {
                    return $index[$keySA];
                }
            }
        }

        foreach (analisisCandidates($analisis) as $ana) {
            foreach ([$ana, normAnalisisKey($ana)] as $anaKey) {
                $keyS = "S|{$catTry}|{$anaKey}|{$pobId}|{$sexo}";
                if (isset($index[$keyS])) {
                    return $index[$keyS];
                }
                if ($sexo !== 'ambos') {
                    $keySA = "S|{$catTry}|{$anaKey}|{$pobId}|ambos";
                    if (isset($index[$keySA])) {
                        return $index[$keySA];
                    }
                }
            }
        }

        foreach (analisisCandidates($analisis) as $ana) {
            $anaKey = normAnalisisKey($ana);
            foreach ([$sexo, 'ambos'] as $sx) {
                $kCa = "CA|{$catTry}|{$anaKey}|{$pobId}|{$sx}";
                if (! isset($indexByCatAna[$kCa])) {
                    continue;
                }
                $picked = pickSingleMeta($indexByCatAna[$kCa]);
                if ($picked !== null) {
                    return $picked;
                }
            }
        }
    }

    foreach (analisisCandidates($analisis) as $ana) {
        foreach ([normAnalisisKey($ana), $ana] as $anaKey) {
            $gs = "GS|{$anaKey}|{$pobId}|{$sexo}";
            $picked = pickSingleMeta($globalSimple[$gs] ?? []);
            if ($picked !== null) {
                return $picked;
            }
            if ($sexo !== 'ambos') {
                $gsA = "GS|{$anaKey}|{$pobId}|ambos";
                $picked = pickSingleMeta($globalSimple[$gsA] ?? []);
                if ($picked !== null) {
                    return $picked;
                }
            }
        }
    }

    if ($pobId === ($GLOBALS['poblacionTodosId'] ?? 15) && $sexo === 'ambos') {
        foreach (collectCompoundMetas($index, $cat, $prueba, $analisis, $sexo) as $meta) {
            if ((int) ($meta['poblacion_id'] ?? 0) === $pobId) {
                return $meta;
            }
        }
    }

    return null;
}

/**
 * @return list<array<string, mixed>>
 */
function collectCompoundMetas(
    array $index,
    string $cat,
    string $prueba,
    string $analisis,
    string $sexo
): array {
    $collected = [];
    foreach (categoriaCandidates($cat, $prueba) as $catTry) {
        foreach (pruebaCandidates($prueba, $analisis) as $pru) {
            $anaKeys = [];
            foreach (analisisCandidates($analisis) as $ana) {
                $anaKeys[] = $ana;
                $anaKeys[] = normAnalisisKey($ana);
            }
            $anaKeys = array_values(array_unique($anaKeys));
            foreach ($index as $key => $meta) {
                if (! is_string($key) || ! str_starts_with($key, 'C|')) {
                    continue;
                }
                $parts = explode('|', $key);
                if (count($parts) !== 6) {
                    continue;
                }
                [, $kCat, $kPru, $kAna, , $kSex] = $parts;
                if ($kCat !== $catTry || $kPru !== $pru) {
                    continue;
                }
                $anaMatch = false;
                foreach ($anaKeys as $ak) {
                    if ($kAna === $ak || normAnalisisKey($kAna) === normAnalisisKey($ak)) {
                        $anaMatch = true;
                        break;
                    }
                }
                if (! $anaMatch) {
                    continue;
                }
                if ($sexo !== 'ambos' && $kSex !== 'ambos' && $kSex !== $sexo) {
                    continue;
                }
                $collected[(int) ($meta['id_referencia'] ?? 0)] = $meta;
            }
        }
    }

    return array_values($collected);
}

/**
 * @return list<array<string, mixed>>
 */
function findAllMeta(
    array $index,
    array $indexByCatAna,
    array $globalSimple,
    string $cat,
    string $prueba,
    string $analisis,
    int $pobId,
    string $sexo,
    string $pobName
): array {
    $single = findMeta($index, $indexByCatAna, $globalSimple, $cat, $prueba, $analisis, $pobId, $sexo);
    if ($single !== null) {
        return [$single];
    }

    $pobNorm = norm($pobName);
    $isTodos = in_array($pobNorm, ['TODOS', 'TODO', 'VARON', 'MUJER', ''], true)
        || str_contains($pobNorm, 'PERRO')
        || str_contains($pobNorm, 'GATO');

    if (! $isTodos && $pobId !== ($GLOBALS['poblacionTodosId'] ?? 15)) {
        return [];
    }

    $collected = [];
    foreach (collectCompoundMetas($index, $cat, $prueba, $analisis, $sexo) as $meta) {
        $collected[(int) ($meta['id_referencia'] ?? 0)] = $meta;
    }
    foreach (analisisCandidates($analisis) as $ana) {
        foreach ([normAnalisisKey($ana), $ana] as $anaKey) {
            foreach ($globalSimple as $key => $list) {
                if (! preg_match('/^GS\\|' . preg_quote($anaKey, '/') . '\\|(\\d+)\\|(ambos|masculino|femenino)$/', $key, $m)) {
                    continue;
                }
                $sx = $m[2];
                if ($sexo !== 'ambos' && $sx !== 'ambos' && $sx !== $sexo) {
                    continue;
                }
                foreach ($list as $meta) {
                    $collected[(int) ($meta['id_referencia'] ?? 0)] = $meta;
                }
            }
        }
    }

    return array_values($collected);
}

$rawContent = file_get_contents($sourcePath);
if (! is_string($rawContent) || $rawContent === '') {
    fwrite(STDERR, "Archivo vacío o ilegible.\n");
    exit(1);
}
$rawContent = preg_replace('/^\xEF\xBB\xBF/', '', $rawContent) ?? $rawContent;
if (! mb_check_encoding($rawContent, 'UTF-8')) {
    $converted = @mb_convert_encoding($rawContent, 'UTF-8', 'Windows-1252');
    if (is_string($converted) && $converted !== '') {
        $rawContent = $converted;
    }
}
$lines = preg_split('/\r\n|\r|\n/', $rawContent);
$lines = array_values(array_filter($lines, static fn ($l) => $l !== null && $l !== ''));
if ($lines === []) {
    fwrite(STDERR, "Archivo vacío.\n");
    exit(1);
}

$out = fopen($outputPath, 'w');
if ($out === false) {
    fwrite(STDERR, "No se pudo crear: {$outputPath}\n");
    exit(1);
}
fwrite($out, "\xEF\xBB\xBF");
fputcsv($out, [
    'TIPO', 'ID_PRUEBA', 'GRUPO', 'PRUEBA', 'ANALISIS', 'ID_REFERENCIA',
    'POBLACION_ID', 'SEXO', 'VALOR_MIN', 'VALOR_MAX', 'UNIDAD', 'ID_TIPO_RESULTADO',
], ',', '"', '\\');

$matched   = 0;
$unmatched = 0;
$reportUnmatched = [];

foreach ($lines as $i => $line) {
    if ($i === 0 || trim($line) === '') {
        continue;
    }
    $cols = str_getcsv($line, ';', '"', '\\');
    if (count($cols) < 8) {
        continue;
    }

    $grupo    = trim((string) ($cols[0] ?? ''));
    $prueba   = trim((string) ($cols[1] ?? ''));
    $analisis = trim((string) ($cols[2] ?? ''));
    $tipoDato = trim((string) ($cols[3] ?? 'NUMERICO'));
    $pobName  = trim((string) ($cols[5] ?? ''));
    $sexoRaw  = trim((string) ($cols[7] ?? 'Ambos'));
    $vmin     = normValor((string) ($cols[8] ?? ''));
    $vmax     = normValor((string) ($cols[9] ?? ''));
    $unidad   = trim((string) ($cols[10] ?? ''));

    if ($grupo === '' || $prueba === '') {
        continue;
    }

    $cat      = normCategoria($grupo, $prueba);
    $pobId    = resolvePoblacionId($pobName, $poblacionByName);
    $sexo     = normSexo($sexoRaw);
    $opcionId = mapTipoResultado($tipoDato, $opcionesByName);
    $anaSrc   = $analisis !== '' ? $analisis : $prueba;

    $metas = findAllMeta($index, $indexByCatAna, $globalSimple, $cat, $prueba, $anaSrc, $pobId, $sexo, $pobName);

    if ($metas === []) {
        $unmatched++;
        $reportUnmatched[] = "{$grupo} | {$prueba} | {$analisis} | {$pobName} | {$sexoRaw}";
        continue;
    }

    foreach ($metas as $meta) {
        $matched++;
        fputcsv($out, [
            $meta['tipo'],
            $meta['id_prueba'],
            $meta['grupo'] !== '' ? $meta['grupo'] : $grupo,
            $meta['prueba'] !== '' ? $meta['prueba'] : $prueba,
            $meta['analisis'] !== '' ? $meta['analisis'] : $anaSrc,
            $meta['id_referencia'],
            (int) ($meta['poblacion_id'] ?? $pobId),
            $sexo,
            $vmin,
            $vmax,
            $unidad,
            $opcionId,
        ], ',', '"', '\\');
    }
}

fclose($out);

$reportPath = preg_replace('/\.csv$/i', '_sin_coincidencia.txt', $outputPath);
if (is_string($reportPath)) {
    file_put_contents(
        $reportPath,
        "Base de datos: {$database}\nFilas convertidas: {$matched}\nFilas sin coincidencia: {$unmatched}\n\n"
        . implode("\n", array_slice($reportUnmatched, 0, 800))
    );
}

$projectCopy = dirname(__DIR__) . '/writable/valores_biocenter_import.csv';
$databaseCopy = __DIR__ . '/valores_biocenter_import.csv';
foreach ([$projectCopy, $databaseCopy] as $copyPath) {
    if (@copy($outputPath, $copyPath)) {
        echo "Copia: {$copyPath}\n";
    }
}

echo "Entrada:  {$sourcePath}\n";
echo "Salida:   {$outputPath}\n";
echo "BD:       {$database}\n";
echo "Matched:  {$matched}\n";
echo "Sin ID:   {$unmatched}\n";
if ($unmatched > 0 && is_string($reportPath)) {
    echo "Reporte:  {$reportPath}\n";
}
