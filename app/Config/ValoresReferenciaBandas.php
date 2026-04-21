<?php

namespace Config;

/**
 * Valores de referencia estratificados por id_poblacion (dom_poblacion), alineados con la configuración de edades:
 *
 * - 6 Recién nacido (p. ej. 0–28 días en catálogo)
 * - 7 Lactante
 * - 8 Niño pequeño
 * - 9 Niño escolar
 * - 10 Adolescente
 * - 11 Adulto
 * - 12 Adulto mayor
 *
 * Ejemplo calcio total (mg/dL): RN 7.6–10.4; lactante 9.0–11.0; niños 8.8–10.8; adolescente/adulto 8.2–10.2;
 * adulto mayor 8.8–10.2 (como en tablas clásicas; el intervalo de RN del catálogo abarca la primera semana–mes).
 *
 * id 15 «Todos» se reserva en el catálogo base para pruebas cualitativas o respaldo; aquí priorizamos 6–12.
 *
 * Rangos orientativos; validar con método, reactivo y población local.
 */
class ValoresReferenciaBandas
{
    /** @return array{p:int,s:string,min:string,max:string,u:string,o?:int,f?:int} */
    private static function o(int $p, string $s, string $min, string $max, string $u, int $opc = 3, int $form = 1): array
    {
        return ['p' => $p, 's' => $s, 'min' => $min, 'max' => $max, 'u' => $u, 'o' => $opc, 'f' => $form];
    }

    /**
     * Misma referencia en todos los grupos etarios (sexo ambos).
     *
     * @param array<int, array{0:string,1:string}> $map id_poblacion => [min, max]
     * @return list<array{p:int,s:string,min:string,max:string,u:string,o?:int,f?:int}>
     */
    private static function mapaAmbos(array $map, string $u, int $form = 1): array
    {
        $rows = [];
        foreach ($map as $pid => $mm) {
            $rows[] = self::o((int) $pid, 'ambos', $mm[0], $mm[1], $u, 3, $form);
        }

        return $rows;
    }

    /**
     * Hemoglobina g/dL: por grupo y sexo (11–12); pediatría sin separación M/F en bandas cortas.
     *
     * @return list<array{p:int,s:string,min:string,max:string,u:string,o?:int,f?:int}>
     */
    private static function hemoglobina(): array
    {
        return [
            self::o(6, 'ambos', '13.4', '19.8', 'g/dL'),
            self::o(7, 'ambos', '9.4', '13.0', 'g/dL'),
            self::o(8, 'ambos', '11.0', '14.0', 'g/dL'),
            self::o(9, 'ambos', '11.5', '15.5', 'g/dL'),
            self::o(10, 'masculino', '13.0', '16.0', 'g/dL'),
            self::o(10, 'femenino', '12.0', '15.0', 'g/dL'),
            self::o(11, 'masculino', '13.5', '17.5', 'g/dL'),
            self::o(11, 'femenino', '12.0', '15.5', 'g/dL'),
            self::o(12, 'masculino', '12.0', '17.0', 'g/dL'),
            self::o(12, 'femenino', '11.5', '15.5', 'g/dL'),
        ];
    }

    private static function hematocrito(): array
    {
        return [
            self::o(6, 'ambos', '41', '65', '%'),
            self::o(7, 'ambos', '28', '42', '%'),
            self::o(8, 'ambos', '33', '42', '%'),
            self::o(9, 'ambos', '34', '43', '%'),
            self::o(10, 'masculino', '37', '48', '%'),
            self::o(10, 'femenino', '36', '46', '%'),
            self::o(11, 'masculino', '41', '52', '%'),
            self::o(11, 'femenino', '36', '46', '%'),
            self::o(12, 'masculino', '39', '50', '%'),
            self::o(12, 'femenino', '36', '47', '%'),
        ];
    }

    private static function plaquetas(): array
    {
        return self::mapaAmbos([
            6 => ['150000', '450000'],
            7 => ['200000', '500000'],
            8 => ['180000', '450000'],
            9 => ['180000', '450000'],
            10 => ['170000', '450000'],
            11 => ['150000', '400000'],
            12 => ['150000', '380000'],
        ], 'mm³', 24);
    }

    private static function eritrosedimentacion(): array
    {
        return [
            self::o(6, 'ambos', '0', '2', 'mm/h'),
            self::o(7, 'ambos', '0', '5', 'mm/h'),
            self::o(8, 'ambos', '0', '10', 'mm/h'),
            self::o(9, 'ambos', '0', '15', 'mm/h'),
            self::o(10, 'masculino', '0', '15', 'mm/h'),
            self::o(10, 'femenino', '0', '20', 'mm/h'),
            self::o(11, 'masculino', '0', '15', 'mm/h'),
            self::o(11, 'femenino', '0', '20', 'mm/h'),
            self::o(12, 'masculino', '0', '20', 'mm/h'),
            self::o(12, 'femenino', '0', '30', 'mm/h'),
        ];
    }

    /** Calcio total mg/dL (tabla clásica por etapa) */
    private static function calcio(): array
    {
        return self::mapaAmbos([
            6 => ['7.6', '10.4'],
            7 => ['9.0', '11.0'],
            8 => ['8.8', '10.8'],
            9 => ['8.8', '10.8'],
            10 => ['8.2', '10.2'],
            11 => ['8.2', '10.2'],
            12 => ['8.8', '10.2'],
        ], 'mg/dL');
    }

    private static function sodio(): array
    {
        return self::mapaAmbos([
            6 => ['133', '145'],
            7 => ['134', '145'],
            8 => ['135', '145'],
            9 => ['135', '145'],
            10 => ['135', '145'],
            11 => ['135', '145'],
            12 => ['132', '146'],
        ], 'mEq/L');
    }

    private static function potasio(): array
    {
        return self::mapaAmbos([
            6 => ['3.7', '5.9'],
            7 => ['3.9', '5.3'],
            8 => ['3.4', '4.7'],
            9 => ['3.4', '4.7'],
            10 => ['3.5', '5.1'],
            11 => ['3.5', '5.1'],
            12 => ['3.5', '5.3'],
        ], 'mEq/L');
    }

    private static function cloro(): array
    {
        return self::mapaAmbos([
            6 => ['96', '110'],
            7 => ['98', '110'],
            8 => ['98', '107'],
            9 => ['98', '107'],
            10 => ['98', '107'],
            11 => ['98', '107'],
            12 => ['96', '108'],
        ], 'mEq/L');
    }

    private static function magnesio(): array
    {
        return self::mapaAmbos([
            6 => ['1.5', '2.6'],
            7 => ['1.6', '2.4'],
            8 => ['1.6', '2.3'],
            9 => ['1.6', '2.3'],
            10 => ['1.7', '2.2'],
            11 => ['1.7', '2.2'],
            12 => ['1.6', '2.2'],
        ], 'mg/dL');
    }

    /** Fósforo inorgánico mg/dL (desciende con la edad) */
    private static function fosforo(): array
    {
        return self::mapaAmbos([
            6 => ['4.3', '7.4'],
            7 => ['4.5', '6.7'],
            8 => ['4.0', '5.8'],
            9 => ['3.7', '5.6'],
            10 => ['3.3', '5.4'],
            11 => ['2.5', '4.5'],
            12 => ['2.7', '4.5'],
        ], 'mg/dL');
    }

    private static function creatinina(): array
    {
        return self::mapaAmbos([
            6 => ['0.6', '1.5'],
            7 => ['0.2', '0.5'],
            8 => ['0.3', '0.6'],
            9 => ['0.4', '0.8'],
            10 => ['0.5', '1.0'],
            11 => ['0.7', '1.3'],
            12 => ['0.8', '1.5'],
        ], 'mg/dL');
    }

    /** Creatinina: sexo en adultos y mayores */
    private static function creatininaConSexo(): array
    {
        $rows = self::creatinina();
        $out  = [];
        foreach ($rows as $r) {
            if ((int) $r['p'] >= 11) {
                continue;
            }
            $out[] = $r;
        }
        $out[] = self::o(11, 'masculino', '0.7', '1.3', 'mg/dL');
        $out[] = self::o(11, 'femenino', '0.6', '1.1', 'mg/dL');
        $out[] = self::o(12, 'masculino', '0.8', '1.5', 'mg/dL');
        $out[] = self::o(12, 'femenino', '0.7', '1.3', 'mg/dL');

        return $out;
    }

    private static function ureaMgdl(): array
    {
        return self::mapaAmbos([
            6 => ['5', '25'],
            7 => ['5', '20'],
            8 => ['10', '30'],
            9 => ['12', '35'],
            10 => ['12', '40'],
            11 => ['15', '45'],
            12 => ['17', '50'],
        ], 'mg/dL');
    }

    private static function bunMgdl(): array
    {
        return self::mapaAmbos([
            6 => ['3', '12'],
            7 => ['3', '10'],
            8 => ['5', '15'],
            9 => ['6', '18'],
            10 => ['6', '20'],
            11 => ['8', '20'],
            12 => ['9', '23'],
        ], 'mg/dL');
    }

    /** Glicemia ayunas mg/dL */
    private static function glicemia(): array
    {
        return self::mapaAmbos([
            6 => ['45', '90'],
            7 => ['60', '100'],
            8 => ['70', '100'],
            9 => ['70', '100'],
            10 => ['70', '100'],
            11 => ['70', '100'],
            12 => ['70', '110'],
        ], 'mg/dL');
    }

    /** Bilirrubina total mg/dL (muy elevada en RN fisiológica) */
    private static function bilirrubinaTotal(): array
    {
        return self::mapaAmbos([
            6 => ['0.0', '12.0'],
            7 => ['0.2', '8.0'],
            8 => ['0.2', '1.2'],
            9 => ['0.2', '1.2'],
            10 => ['0.2', '1.2'],
            11 => ['0.2', '1.2'],
            12 => ['0.2', '1.3'],
        ], 'mg/dL');
    }

    private static function colesterolTotal(): array
    {
        return self::mapaAmbos([
            6 => ['75', '180'],
            7 => ['80', '200'],
            8 => ['120', '200'],
            9 => ['125', '210'],
            10 => ['130', '220'],
            11 => ['150', '260'],
            12 => ['140', '260'],
        ], 'mg/dL');
    }

    private static function trigliceridos(): array
    {
        return self::mapaAmbos([
            6 => ['30', '150'],
            7 => ['35', '160'],
            8 => ['40', '180'],
            9 => ['45', '190'],
            10 => ['50', '200'],
            11 => ['30', '200'],
            12 => ['35', '200'],
        ], 'mg/dL');
    }

    private static function hdlColesterol(): array
    {
        return [
            self::o(6, 'ambos', '25', '70', 'mg/dL'),
            self::o(7, 'ambos', '30', '75', 'mg/dL'),
            self::o(8, 'ambos', '35', '70', 'mg/dL'),
            self::o(9, 'ambos', '38', '72', 'mg/dL'),
            self::o(10, 'masculino', '38', '65', 'mg/dL'),
            self::o(10, 'femenino', '40', '70', 'mg/dL'),
            self::o(11, 'masculino', '40', '60', 'mg/dL'),
            self::o(11, 'femenino', '50', '70', 'mg/dL'),
            self::o(12, 'masculino', '38', '58', 'mg/dL'),
            self::o(12, 'femenino', '48', '68', 'mg/dL'),
        ];
    }

    private static function ldlColesterol(): array
    {
        return self::mapaAmbos([
            6 => ['30', '110'],
            7 => ['35', '120'],
            8 => ['60', '130'],
            9 => ['65', '140'],
            10 => ['70', '150'],
            11 => ['60', '180'],
            12 => ['55', '170'],
        ], 'mg/dL');
    }

    private static function transaminasaAlt(): array
    {
        return self::mapaAmbos([
            6 => ['5', '48'],
            7 => ['10', '55'],
            8 => ['10', '40'],
            9 => ['10', '40'],
            10 => ['10', '40'],
            11 => ['0', '40'],
            12 => ['0', '45'],
        ], 'U/L');
    }

    private static function transaminasaAst(): array
    {
        return self::mapaAmbos([
            6 => ['20', '75'],
            7 => ['20', '60'],
            8 => ['15', '45'],
            9 => ['15', '45'],
            10 => ['15', '40'],
            11 => ['0', '40'],
            12 => ['0', '45'],
        ], 'U/L');
    }

    private static function fosfatasaAlcalina(): array
    {
        return self::mapaAmbos([
            6 => ['90', '310'],
            7 => ['120', '450'],
            8 => ['120', '390'],
            9 => ['110', '320'],
            10 => ['80', '270'],
            11 => ['40', '120'],
            12 => ['40', '140'],
        ], 'U/L');
    }

    private static function ggt(): array
    {
        return self::mapaAmbos([
            6 => ['10', '200'],
            7 => ['5', '60'],
            8 => ['3', '25'],
            9 => ['3', '25'],
            10 => ['4', '30'],
            11 => ['5', '40'],
            12 => ['6', '50'],
        ], 'U/L');
    }

    private static function amilasa(): array
    {
        return self::mapaAmbos([
            6 => ['5', '65'],
            7 => ['10', '90'],
            8 => ['25', '125'],
            9 => ['30', '130'],
            10 => ['30', '110'],
            11 => ['28', '100'],
            12 => ['30', '110'],
        ], 'U/L');
    }

    private static function lipasa(): array
    {
        return self::mapaAmbos([
            6 => ['5', '60'],
            7 => ['10', '80'],
            8 => ['15', '90'],
            9 => ['15', '95'],
            10 => ['15', '85'],
            11 => ['13', '60'],
            12 => ['14', '65'],
        ], 'U/L');
    }

    private static function acidoUrico(): array
    {
        return [
            self::o(6, 'ambos', '1.5', '6.0', 'mg/dL'),
            self::o(7, 'ambos', '1.5', '5.5', 'mg/dL'),
            self::o(8, 'ambos', '2.0', '5.5', 'mg/dL'),
            self::o(9, 'ambos', '2.2', '5.7', 'mg/dL'),
            self::o(10, 'masculino', '3.0', '7.0', 'mg/dL'),
            self::o(10, 'femenino', '2.5', '6.5', 'mg/dL'),
            self::o(11, 'masculino', '3.5', '7.2', 'mg/dL'),
            self::o(11, 'femenino', '2.5', '6.0', 'mg/dL'),
            self::o(12, 'masculino', '3.5', '7.5', 'mg/dL'),
            self::o(12, 'femenino', '2.6', '6.2', 'mg/dL'),
        ];
    }

    private static function ldh(): array
    {
        return self::mapaAmbos([
            6 => ['200', '580'],
            7 => ['180', '430'],
            8 => ['160', '370'],
            9 => ['160', '345'],
            10 => ['150', '320'],
            11 => ['140', '280'],
            12 => ['145', '290'],
        ], 'U/L');
    }

    private static function ckTotal(): array
    {
        return [
            self::o(6, 'ambos', '80', '500', 'U/L'),
            self::o(7, 'ambos', '60', '300', 'U/L'),
            self::o(8, 'ambos', '50', '200', 'U/L'),
            self::o(9, 'ambos', '45', '180', 'U/L'),
            self::o(10, 'masculino', '55', '200', 'U/L'),
            self::o(10, 'femenino', '45', '170', 'U/L'),
            self::o(11, 'masculino', '55', '170', 'U/L'),
            self::o(11, 'femenino', '45', '145', 'U/L'),
            self::o(12, 'masculino', '50', '160', 'U/L'),
            self::o(12, 'femenino', '40', '135', 'U/L'),
        ];
    }

    private static function pcrMgL(): array
    {
        return self::mapaAmbos([
            6 => ['0', '4'],
            7 => ['0', '3'],
            8 => ['0', '3'],
            9 => ['0', '3'],
            10 => ['0', '3'],
            11 => ['0', '5'],
            12 => ['0', '6'],
        ], 'mg/L');
    }

    private static function hba1c(): array
    {
        return self::mapaAmbos([
            6 => ['4.0', '5.8'],
            7 => ['4.2', '5.8'],
            8 => ['4.3', '5.8'],
            9 => ['4.3', '5.8'],
            10 => ['4.3', '5.8'],
            11 => ['4.0', '5.6'],
            12 => ['4.2', '5.8'],
        ], '%');
    }

    private static function hierroSerico(): array
    {
        return [
            self::o(6, 'ambos', '100', '250', 'µg/dL'),
            self::o(7, 'ambos', '40', '120', 'µg/dL'),
            self::o(8, 'ambos', '50', '120', 'µg/dL'),
            self::o(9, 'ambos', '55', '130', 'µg/dL'),
            self::o(10, 'masculino', '55', '150', 'µg/dL'),
            self::o(10, 'femenino', '50', '140', 'µg/dL'),
            self::o(11, 'masculino', '65', '175', 'µg/dL'),
            self::o(11, 'femenino', '50', '170', 'µg/dL'),
            self::o(12, 'masculino', '50', '160', 'µg/dL'),
            self::o(12, 'femenino', '45', '145', 'µg/dL'),
        ];
    }

    private static function ferritina(): array
    {
        return [
            self::o(6, 'ambos', '25', '200', 'ng/mL'),
            self::o(7, 'ambos', '10', '120', 'ng/mL'),
            self::o(8, 'ambos', '10', '80', 'ng/mL'),
            self::o(9, 'ambos', '10', '70', 'ng/mL'),
            self::o(10, 'masculino', '20', '200', 'ng/mL'),
            self::o(10, 'femenino', '10', '150', 'ng/mL'),
            self::o(11, 'masculino', '30', '400', 'ng/mL'),
            self::o(11, 'femenino', '10', '150', 'ng/mL'),
            self::o(12, 'masculino', '25', '350', 'ng/mL'),
            self::o(12, 'femenino', '10', '130', 'ng/mL'),
        ];
    }

    private static function albumina(): array
    {
        return self::mapaAmbos([
            6 => ['2.8', '4.8'],
            7 => ['3.2', '5.0'],
            8 => ['3.6', '5.2'],
            9 => ['3.6', '5.2'],
            10 => ['3.6', '5.2'],
            11 => ['3.5', '5.2'],
            12 => ['3.4', '5.0'],
        ], 'g/dL');
    }

    private static function proteinasTotales(): array
    {
        return self::mapaAmbos([
            6 => ['4.3', '7.6'],
            7 => ['5.0', '7.5'],
            8 => ['6.0', '8.0'],
            9 => ['6.2', '8.2'],
            10 => ['6.2', '8.3'],
            11 => ['6.0', '8.3'],
            12 => ['6.0', '8.0'],
        ], 'g/dL');
    }

    private static function tsh(): array
    {
        return self::mapaAmbos([
            6 => ['1.0', '18.0'],
            7 => ['0.5', '6.5'],
            8 => ['0.6', '4.5'],
            9 => ['0.5', '4.2'],
            10 => ['0.5', '4.0'],
            11 => ['0.4', '4.5'],
            12 => ['0.4', '5.5'],
        ], 'µUI/mL');
    }

    private static function t4Libre(): array
    {
        return self::mapaAmbos([
            6 => ['0.8', '2.8'],
            7 => ['0.9', '2.2'],
            8 => ['0.9', '1.8'],
            9 => ['0.9', '1.8'],
            10 => ['0.9', '1.8'],
            11 => ['0.8', '1.8'],
            12 => ['0.8', '1.8'],
        ], 'ng/dL');
    }

    private static function vitaminaD(): array
    {
        return self::mapaAmbos([
            6 => ['10', '60'],
            7 => ['15', '70'],
            8 => ['20', '80'],
            9 => ['20', '80'],
            10 => ['20', '80'],
            11 => ['30', '100'],
            12 => ['25', '90'],
        ], 'ng/mL');
    }

    private static function tiempoSangriaMin(): array
    {
        return self::mapaAmbos([
            6 => ['1', '4'],
            7 => ['2', '7'],
            8 => ['2', '8'],
            9 => ['2', '8'],
            10 => ['2', '9'],
            11 => ['2', '9'],
            12 => ['2', '10'],
        ], 'min');
    }

    private static function tiempoCoagulacionMin(): array
    {
        return self::mapaAmbos([
            6 => ['5', '13'],
            7 => ['6', '13'],
            8 => ['7', '13'],
            9 => ['7', '13'],
            10 => ['7', '14'],
            11 => ['8', '15'],
            12 => ['8', '16'],
        ], 'min');
    }

    private static function fibrinogeno(): array
    {
        return self::mapaAmbos([
            6 => ['150', '400'],
            7 => ['150', '400'],
            8 => ['180', '400'],
            9 => ['180', '400'],
            10 => ['200', '400'],
            11 => ['200', '400'],
            12 => ['200', '450'],
        ], 'mg/dL');
    }

    private static function apttSeg(): array
    {
        return self::mapaAmbos([
            6 => ['25', '55'],
            7 => ['25', '45'],
            8 => ['25', '38'],
            9 => ['25', '36'],
            10 => ['25', '36'],
            11 => ['25', '35'],
            12 => ['25', '38'],
        ], 's');
    }

    private static function inr(): array
    {
        return self::mapaAmbos([
            6 => ['0.5', '1.6'],
            7 => ['0.7', '1.4'],
            8 => ['0.8', '1.3'],
            9 => ['0.8', '1.25'],
            10 => ['0.8', '1.2'],
            11 => ['0.8', '1.2'],
            12 => ['0.8', '1.3'],
        ], 'INR');
    }

    private static function ckMb(): array
    {
        return self::mapaAmbos([
            6 => ['0', '8'],
            7 => ['0', '6'],
            8 => ['0', '5'],
            9 => ['0', '5'],
            10 => ['0', '5'],
            11 => ['0', '5'],
            12 => ['0', '6'],
        ], 'ng/mL');
    }

    private static function troponina(): array
    {
        return self::mapaAmbos([
            6 => ['0', '50'],
            7 => ['0', '30'],
            8 => ['0', '20'],
            9 => ['0', '18'],
            10 => ['0', '16'],
            11 => ['0', '14'],
            12 => ['0', '16'],
        ], 'ng/L');
    }

    /** Mismo rango en 6–12 (marcadores u hormonas con variación mínima por edad en el informe). */
    private static function mismoRangoTodasEdades(string $min, string $max, string $u): array
    {
        $m = [];
        foreach (range(6, 12) as $pid) {
            $m[$pid] = [$min, $max];
        }

        return self::mapaAmbos($m, $u);
    }

    private static function psaTotal(): array
    {
        $rows = [];
        foreach (range(6, 12) as $p) {
            $rows[] = self::o((int) $p, 'masculino', '0', '4', 'ng/mL');
            $rows[] = self::o((int) $p, 'femenino', '0', '0.2', 'ng/mL');
        }

        return $rows;
    }

    private static function psaLibreRatio(): array
    {
        return self::mismoRangoTodasEdades('0', '0.93', 'ratio');
    }

    /** HCM (pg) aproximado por edad */
    private static function indicesHematimetricos(): array
    {
        return self::mapaAmbos([
            6 => ['30', '38'],
            7 => ['28', '36'],
            8 => ['28', '34'],
            9 => ['28', '34'],
            10 => ['28', '34'],
            11 => ['27', '33'],
            12 => ['27', '33'],
        ], 'pg', 6);
    }

    /**
     * Sustituye entradas del catálogo base por versiones estratificadas por edad.
     *
     * @return array<int, list<array{p:int,s:string,min:string,max:string,u:string,o?:int,f?:int}>>
     */
    public static function reemplazosPorPoblacion(): array
    {
        return [
            1 => self::plaquetas(),
            3 => self::hemoglobina(),
            15 => self::eritrosedimentacion(),
            16 => self::indicesHematimetricos(),
            17 => self::hematocrito(),
            18 => self::mapaAmbos([
                6 => ['0.5', '4.5'],
                7 => ['0.5', '2.5'],
                8 => ['0.5', '2.2'],
                9 => ['0.5', '2.0'],
                10 => ['0.5', '2.0'],
                11 => ['0.5', '2.5'],
                12 => ['0.5', '2.5'],
            ], '%'),
            4 => self::tiempoSangriaMin(),
            22 => self::tiempoCoagulacionMin(),
            7 => self::fibrinogeno(),
            73 => self::fibrinogeno(),
            24 => self::apttSeg(),
            25 => self::hierroSerico(),
            26 => self::mapaAmbos([
                6 => ['200', '400'],
                7 => ['220', '450'],
                8 => ['240', '460'],
                9 => ['250', '470'],
                10 => ['250', '480'],
                11 => ['250', '450'],
                12 => ['240', '440'],
            ], 'µg/dL'),
            27 => self::mapaAmbos([
                6 => ['35', '75'],
                7 => ['25', '60'],
                8 => ['22', '50'],
                9 => ['22', '48'],
                10 => ['20', '45'],
                11 => ['20', '50'],
                12 => ['18', '48'],
            ], '%'),
            28 => self::ferritina(),
            29 => self::amilasa(),
            30 => self::acidoUrico(),
            31 => self::albumina(),
            32 => self::proteinasTotales(),
            33 => self::mapaAmbos([
                6 => ['1.5', '3.2'],
                7 => ['1.8', '3.4'],
                8 => ['2.0', '3.5'],
                9 => ['2.0', '3.5'],
                10 => ['2.0', '3.5'],
                11 => ['2.0', '3.5'],
                12 => ['2.0', '3.5'],
            ], 'g/dL'),
            34 => self::mapaAmbos([
                6 => ['0.8', '2.5'],
                7 => ['0.9', '2.3'],
                8 => ['1.0', '2.2'],
                9 => ['1.0', '2.2'],
                10 => ['1.0', '2.2'],
                11 => ['1.0', '2.2'],
                12 => ['1.0', '2.2'],
            ], ''),
            35 => self::bilirrubinaTotal(),
            36 => self::creatininaConSexo(),
            37 => self::mapaAmbos([
                6 => ['3000', '8000'],
                7 => ['3500', '8500'],
                8 => ['4000', '9500'],
                9 => ['4500', '10000'],
                10 => ['4800', '11000'],
                11 => ['5320', '11500'],
                12 => ['4800', '11000'],
            ], 'U/L'),
            38 => self::colesterolTotal(),
            39 => self::transaminasaAst(),
            40 => self::mapaAmbos([
                6 => ['2.5', '12'],
                7 => ['2.5', '12'],
                8 => ['2.5', '12'],
                9 => ['2.5', '12'],
                10 => ['2.5', '12'],
                11 => ['2.5', '12'],
                12 => ['2.5', '12'],
            ], 'U/L'),
            41 => self::transaminasaAlt(),
            42 => self::transaminasaAst(),
            43 => self::fosfatasaAlcalina(),
            44 => self::glicemia(),
            45 => self::mapaAmbos([
                6 => ['60', '120'],
                7 => ['70', '140'],
                8 => ['70', '140'],
                9 => ['70', '140'],
                10 => ['70', '140'],
                11 => ['70', '140'],
                12 => ['70', '150'],
            ], 'mg/dL'),
            46 => self::mapaAmbos([
                6 => ['60', '120'],
                7 => ['70', '140'],
                8 => ['70', '140'],
                9 => ['70', '140'],
                10 => ['70', '140'],
                11 => ['70', '140'],
                12 => ['70', '150'],
            ], 'mg/dL'),
            47 => self::hba1c(),
            48 => self::ldh(),
            49 => self::trigliceridos(),
            209 => self::lipasa(),
            56 => self::fosforo(),
            52 => self::potasio(),
            53 => self::cloro(),
            54 => self::sodio(),
            55 => self::magnesio(),
            57 => self::calcio(),
            58 => self::trigliceridos(),
            59 => self::colesterolTotal(),
            60 => self::hdlColesterol(),
            61 => self::ldlColesterol(),
            62 => self::mapaAmbos([
                6 => ['15', '45'],
                7 => ['18', '48'],
                8 => ['20', '50'],
                9 => ['20', '50'],
                10 => ['22', '50'],
                11 => ['25', '50'],
                12 => ['25', '52'],
            ], '%'),
            63 => self::transaminasaAlt(),
            64 => self::transaminasaAst(),
            65 => self::fosfatasaAlcalina(),
            66 => self::ggt(),
            68 => self::ckTotal(),
            70 => self::ldh(),
            71 => self::transaminasaAst(),
            80 => self::pcrMgL(),
            94 => self::ggt(),
            109 => self::tsh(),
            111 => self::t4Libre(),
            134 => self::vitaminaD(),
            226 => self::bunMgdl(),
            50 => self::ureaMgdl(),
            51 => self::bunMgdl(),
            23 => self::inr(),
            67 => self::inr(),
            69 => self::ckMb(),
            72 => self::troponina(),
            84 => self::mismoRangoTodasEdades('0', '10', 'ng/mL'),
            85 => self::mismoRangoTodasEdades('0.8', '2.2', 'mg/L'),
            87 => self::mismoRangoTodasEdades('0', '30', 'U/mL'),
            88 => self::mismoRangoTodasEdades('0', '37', 'U/mL'),
            89 => self::mismoRangoTodasEdades('0', '35', 'U/mL'),
            90 => self::mismoRangoTodasEdades('0', '5', 'ng/mL'),
            91 => self::psaTotal(),
            92 => self::psaLibreRatio(),
            93 => self::mismoRangoTodasEdades('0', '5', 'mUI/mL'),
            95 => self::mismoRangoTodasEdades('7', '63', 'pg/mL'),
            96 => self::mismoRangoTodasEdades('5', '25', 'µg/dL'),
            97 => [
                self::o(6, 'femenino', '20', '250', 'pg/mL'),
                self::o(6, 'masculino', '10', '50', 'pg/mL'),
                self::o(7, 'femenino', '25', '300', 'pg/mL'),
                self::o(7, 'masculino', '10', '45', 'pg/mL'),
                self::o(8, 'femenino', '28', '350', 'pg/mL'),
                self::o(8, 'masculino', '10', '42', 'pg/mL'),
                self::o(9, 'femenino', '30', '400', 'pg/mL'),
                self::o(9, 'masculino', '10', '40', 'pg/mL'),
                self::o(10, 'femenino', '30', '400', 'pg/mL'),
                self::o(10, 'masculino', '10', '40', 'pg/mL'),
                self::o(11, 'femenino', '30', '400', 'pg/mL'),
                self::o(11, 'masculino', '10', '40', 'pg/mL'),
                self::o(12, 'femenino', '25', '350', 'pg/mL'),
                self::o(12, 'masculino', '10', '38', 'pg/mL'),
            ],
            98 => self::mismoRangoTodasEdades('1', '20', 'mUI/mL'),
            99 => self::mismoRangoTodasEdades('1', '15', 'mUI/mL'),
            100 => self::mismoRangoTodasEdades('0', '5', 'mUI/mL'),
            101 => self::mismoRangoTodasEdades('2.6', '25', 'µU/mL'),
            103 => self::mismoRangoTodasEdades('0.2', '25', 'ng/mL'),
            104 => self::mismoRangoTodasEdades('4', '25', 'ng/mL'),
            105 => [
                self::o(6, 'masculino', '5', '200', 'ng/dL'),
                self::o(6, 'femenino', '5', '150', 'ng/dL'),
                self::o(7, 'masculino', '20', '350', 'ng/dL'),
                self::o(7, 'femenino', '15', '200', 'ng/dL'),
                self::o(8, 'masculino', '50', '500', 'ng/dL'),
                self::o(8, 'femenino', '20', '120', 'ng/dL'),
                self::o(9, 'masculino', '100', '700', 'ng/dL'),
                self::o(9, 'femenino', '20', '90', 'ng/dL'),
                self::o(10, 'masculino', '200', '900', 'ng/dL'),
                self::o(10, 'femenino', '20', '80', 'ng/dL'),
                self::o(11, 'masculino', '300', '1000', 'ng/dL'),
                self::o(11, 'femenino', '20', '70', 'ng/dL'),
                self::o(12, 'masculino', '250', '900', 'ng/dL'),
                self::o(12, 'femenino', '18', '65', 'ng/dL'),
            ],
            106 => self::mismoRangoTodasEdades('0', '10', 'ng/mL'),
            217 => self::mismoRangoTodasEdades('3', '16', 'µg/dL'),
            221 => [
                self::o(6, 'masculino', '2', '15', 'pg/mL'),
                self::o(6, 'femenino', '2', '12', 'pg/mL'),
                self::o(7, 'masculino', '3', '18', 'pg/mL'),
                self::o(7, 'femenino', '2', '14', 'pg/mL'),
                self::o(8, 'masculino', '4', '22', 'pg/mL'),
                self::o(8, 'femenino', '1', '10', 'pg/mL'),
                self::o(9, 'masculino', '5', '26', 'pg/mL'),
                self::o(9, 'femenino', '1', '8', 'pg/mL'),
                self::o(10, 'masculino', '6', '28', 'pg/mL'),
                self::o(10, 'femenino', '0.8', '6.5', 'pg/mL'),
                self::o(11, 'masculino', '9', '30', 'pg/mL'),
                self::o(11, 'femenino', '0.5', '4.2', 'pg/mL'),
                self::o(12, 'masculino', '8', '28', 'pg/mL'),
                self::o(12, 'femenino', '0.5', '4.0', 'pg/mL'),
            ],
            222 => self::mismoRangoTodasEdades('0', '10', 'ng/mL'),
            107 => self::mismoRangoTodasEdades('80', '200', 'ng/dL'),
            108 => self::mismoRangoTodasEdades('5', '12', 'µg/dL'),
            110 => self::mismoRangoTodasEdades('0', '20', 'mUI/L'),
            135 => self::mismoRangoTodasEdades('90', '180', 'mg/dL'),
            136 => self::mismoRangoTodasEdades('10', '40', 'mg/dL'),
            137 => self::mismoRangoTodasEdades('700', '1600', 'mg/dL'),
            138 => self::mismoRangoTodasEdades('40', '230', 'mg/dL'),
            139 => self::mismoRangoTodasEdades('70', '400', 'mg/dL'),
            140 => self::mismoRangoTodasEdades('90', '180', 'mg/dL'),
            141 => self::mismoRangoTodasEdades('10', '40', 'mg/dL'),
            142 => self::mismoRangoTodasEdades('700', '1600', 'mg/dL'),
            143 => self::mismoRangoTodasEdades('40', '230', 'mg/dL'),
            144 => self::mismoRangoTodasEdades('70', '400', 'mg/dL'),
            145 => self::mismoRangoTodasEdades('25', '45', 'mg/dL'),
            124 => self::mismoRangoTodasEdades('0', '100', 'UI/mL'),
            75 => self::mismoRangoTodasEdades('0', '200', 'UI/mL'),
            81 => self::mismoRangoTodasEdades('0', '20', 'UI/mL'),
            225 => [
                self::o(6, 'femenino', '0', '15000', 'mUI/mL'),
                self::o(6, 'masculino', '0', '5', 'mUI/mL'),
                self::o(7, 'femenino', '0', '10000', 'mUI/mL'),
                self::o(7, 'masculino', '0', '5', 'mUI/mL'),
                self::o(8, 'femenino', '0', '5', 'mUI/mL'),
                self::o(8, 'masculino', '0', '5', 'mUI/mL'),
                self::o(9, 'femenino', '0', '5', 'mUI/mL'),
                self::o(9, 'masculino', '0', '5', 'mUI/mL'),
                self::o(10, 'femenino', '0', '5', 'mUI/mL'),
                self::o(10, 'masculino', '0', '5', 'mUI/mL'),
                self::o(11, 'femenino', '0', '5', 'mUI/mL'),
                self::o(11, 'masculino', '0', '5', 'mUI/mL'),
                self::o(12, 'femenino', '0', '5', 'mUI/mL'),
                self::o(12, 'masculino', '0', '5', 'mUI/mL'),
            ],
            174 => self::mismoRangoTodasEdades('0', '5', 'mUI/mL'),
            172 => self::mismoRangoTodasEdades('30', '150', 'mg/24h'),
            173 => self::mismoRangoTodasEdades('500', '2000', 'mg/24h'),
            175 => self::mismoRangoTodasEdades('0', '500', 'U/L'),
            176 => self::mismoRangoTodasEdades('85', '125', 'mL/min/1.73m²'),
            211 => self::mismoRangoTodasEdades('7.35', '7.45', 'pH'),
            212 => self::mismoRangoTodasEdades('15', '45', 'µg/dL'),
            169 => self::mismoRangoTodasEdades('0', '40', 'U/L'),
            86 => self::ldh(),
            203 => self::troponina(),
        ];
    }
}
