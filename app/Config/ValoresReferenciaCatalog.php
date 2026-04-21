<?php

namespace Config;

/**
 * Valores de referencia por prianacategoria_id (pruebas NO compuestas).
 * Cada entrada: listas de filas con id_poblacion (tabla poblacion), sexo, rangos y opcion_id.
 * Población "Todos" = 15 (respaldo). Bandas etarias 6–12 en {@see ValoresReferenciaBandas}.
 *
 * @return array<int, list<array{p:int,s:string,min:string,max:string,u:string,o?:int,f?:int}>>
 */
class ValoresReferenciaCatalog
{
    public static function priresultadosPorPrueba(): array
    {
        $T = 15; // Todos (cualquier edad según catálogo)
        $o = static fn (int $p, string $s, string $min, string $max, string $u, int $opc = 3, int $form = 1) => [
            'p' => $p, 's' => $s, 'min' => $min, 'max' => $max, 'u' => $u, 'o' => $opc, 'f' => $form,
        ];

        $base = [
            // Hematología sueltas
            1 => [$o($T, 'ambos', '150000', '400000', 'mm³', 3, 24)], // Plaquetas
            3 => [$o($T, 'masculino', '13.5', '17.5', 'g/dL'), $o($T, 'femenino', '12.0', '15.5', 'g/dL')],
            16 => [$o($T, 'ambos', '32', '36', 'pg', 3, 6)], // índice: MCH aprox.
            17 => [$o($T, 'masculino', '41', '52', '%'), $o($T, 'femenino', '36', '46', '%')],
            18 => [$o($T, 'ambos', '0.5', '2.5', '%')],
            19 => [$o($T, 'ambos', '', '', '', 3, 1)], // Coombs indirecto: cualitativo
            20 => [$o($T, 'ambos', '', '', '', 3, 1)],
            13 => [$o($T, 'ambos', '', '', '', 3, 1)], // Gota gruesa
            21 => [$o($T, 'ambos', '', '', '', 3, 1)], // Grupo sanguíneo
            14 => [$o($T, 'ambos', '0', '500', 'ng/mL DDU')],
            202 => [$o($T, 'ambos', '0', '10', '/campo')],
            203 => [$o($T, 'ambos', '0', '14', 'ng/L')], // hs-cTn típico; ajustar método

            // Coagulación
            4 => [$o($T, 'ambos', '2', '9', 'min')],
            22 => [$o($T, 'ambos', '5', '13', 'min')],
            23 => [$o($T, 'ambos', '0.8', '1.2', 'INR')],
            24 => [$o($T, 'ambos', '25', '35', 's')],
            7 => [$o($T, 'ambos', '200', '400', 'mg/dL')],
            6 => [$o($T, 'ambos', '', '', '', 3, 1)],
            67 => [$o($T, 'ambos', '0.8', '1.2', 'INR')],

            // Heces / parásitos
            179 => [$o($T, 'ambos', '', '', '', 3, 1)],
            180 => [$o($T, 'ambos', '', '', '', 3, 1)],
            181 => [$o($T, 'ambos', '', '', '', 3, 1)],
            182 => [$o($T, 'ambos', 'pH 5.5', 'pH 7.0', '')],
            183 => [$o($T, 'ambos', '', '', '', 3, 1)],
            184 => [$o($T, 'ambos', '', '', '', 3, 1)],
            185 => [$o($T, 'ambos', '', '', '', 3, 1)],
            186 => [$o($T, 'ambos', '', '', '', 3, 1)],
            5 => [$o($T, 'ambos', '', '', '', 3, 1)],
            187 => [$o($T, 'ambos', '', '', '', 3, 1)],
            208 => [$o($T, 'ambos', '', '', '', 3, 1)],

            // Hierro
            25 => [$o($T, 'masculino', '65', '175', 'µg/dL'), $o($T, 'femenino', '50', '170', 'µg/dL')],
            26 => [$o($T, 'ambos', '250', '450', 'µg/dL')], // TIBC aprox.
            27 => [$o($T, 'ambos', '20', '50', '%')],
            28 => [$o($T, 'masculino', '30', '400', 'ng/mL'), $o($T, 'femenino', '10', '150', 'ng/mL')],
            210 => [$o($T, 'ambos', '', '', '', 3, 1)], // perfil: ref por componentes

            // Química
            29 => [$o($T, 'ambos', '28', '100', 'U/L')],
            30 => [$o($T, 'masculino', '3.5', '7.2', 'mg/dL'), $o($T, 'femenino', '2.5', '6.0', 'mg/dL')],
            31 => [$o($T, 'ambos', '3.5', '5.2', 'g/dL')],
            32 => [$o($T, 'ambos', '6.0', '8.3', 'g/dL')],
            33 => [$o($T, 'ambos', '2.0', '3.5', 'g/dL')],
            34 => [$o($T, 'ambos', '1.0', '2.2', '')],
            37 => [$o($T, 'ambos', '5320', '11500', 'U/L')],
            40 => [$o($T, 'ambos', '2.5', '12', 'U/L')],
            45 => [$o($T, 'ambos', '70', '140', 'mg/dL')], // 2h PP
            46 => [$o($T, 'ambos', '70', '140', 'mg/dL')], // simplificado
            47 => [$o($T, 'ambos', '4.0', '5.6', '%')],
            48 => [$o($T, 'ambos', '140', '280', 'U/L')],
            51 => [$o($T, 'ambos', '8', '20', 'mg/dL')],
            209 => [$o($T, 'ambos', '13', '60', 'U/L')],
            56 => [$o($T, 'ambos', '2.5', '4.5', 'mg/dL')],

            // Perfil lipídico / cardiaco extra
            68 => [$o($T, 'masculino', '55', '170', 'U/L'), $o($T, 'femenino', '45', '145', 'U/L')],
            69 => [$o($T, 'ambos', '0', '5', 'ng/mL')], // método-dependiente
            70 => [$o($T, 'ambos', '140', '280', 'U/L')],
            72 => [$o($T, 'ambos', '0', '14', 'ng/L')],
            73 => [$o($T, 'ambos', '200', '400', 'mg/dL')],
            74 => [$o($T, 'ambos', '', '', '', 3, 1)],
            71 => [$o($T, 'masculino', '10', '40', 'U/L'), $o($T, 'femenino', '8', '35', 'U/L')],

            // Serología / latex
            75 => [$o($T, 'ambos', '0', '200', 'UI/mL')],
            76 => [$o($T, 'ambos', '', '', '', 3, 1)],
            77 => [$o($T, 'ambos', '', '', '', 3, 1)],
            78 => [$o($T, 'ambos', '', '', '', 3, 1)],
            79 => [$o($T, 'ambos', '', '', '', 3, 1)],
            81 => [$o($T, 'ambos', '0', '20', 'UI/mL')],
            82 => [$o($T, 'ambos', '', '', '', 3, 1)],
            83 => [$o($T, 'ambos', '', '', '', 3, 1)],
            225 => [$o($T, 'femenino', '0', '5', 'mUI/mL'), $o($T, 'masculino', '0', '5', 'mUI/mL')],

            // Marcadores tumorales
            84 => [$o($T, 'ambos', '0', '10', 'ng/mL')],
            85 => [$o($T, 'ambos', '0.8', '2.2', 'mg/L')],
            86 => [$o($T, 'ambos', '140', '280', 'U/L')],
            87 => [$o($T, 'ambos', '0', '30', 'U/mL')],
            88 => [$o($T, 'ambos', '0', '37', 'U/mL')],
            89 => [$o($T, 'ambos', '0', '35', 'U/mL')],
            90 => [$o($T, 'ambos', '0', '5', 'ng/mL')], // CEA; no fumadores suele ser <3
            91 => [$o($T, 'masculino', '0', '4', 'ng/mL')],
            92 => [$o($T, 'masculino', '0', '0.93', 'ratio')],
            93 => [$o($T, 'ambos', '0', '5', 'mUI/mL')],

            // Hormonas
            95 => [$o($T, 'ambos', '7.2', '63', 'pg/mL')], // orden magnitud varía método
            96 => [$o($T, 'ambos', '5', '25', 'µg/dL')], // cortisol AM
            97 => [$o($T, 'femenino', '30', '400', 'pg/mL'), $o($T, 'masculino', '10', '40', 'pg/mL')],
            98 => [$o($T, 'femenino', '3', '20', 'mUI/mL'), $o($T, 'masculino', '1', '12', 'mUI/mL')],
            99 => [$o($T, 'femenino', '2', '15', 'mUI/mL'), $o($T, 'masculino', '1', '10', 'mUI/mL')],
            100 => [$o($T, 'ambos', '0', '5', 'mUI/mL')],
            101 => [$o($T, 'ambos', '2.6', '25', 'µU/mL')],
            102 => [$o($T, 'ambos', '', '', '', 3, 1)],
            103 => [$o($T, 'femenino', '0.2', '25', 'ng/mL')],
            104 => [$o($T, 'ambos', '4', '25', 'ng/mL')],
            105 => [$o($T, 'masculino', '300', '1000', 'ng/dL'), $o($T, 'femenino', '20', '70', 'ng/dL')],
            106 => [$o($T, 'ambos', '0', '10', 'ng/mL')],
            217 => [$o($T, 'ambos', '3', '16', 'µg/dL')],
            221 => [$o($T, 'masculino', '9', '30', 'pg/mL'), $o($T, 'femenino', '0.5', '4.2', 'pg/mL')],
            222 => [$o($T, 'ambos', '0', '10', 'ng/mL')],

            // Tiroides
            107 => [$o($T, 'ambos', '80', '200', 'ng/dL')],
            108 => [$o($T, 'ambos', '5', '12', 'µg/dL')],
            109 => [$o($T, 'ambos', '0.4', '4.5', 'µUI/mL')],
            110 => [$o($T, 'ambos', '0', '20', 'mUI/L')],
            111 => [$o($T, 'ambos', '0.8', '1.8', 'ng/dL')],

            // Infecciosos / Elisa
            112 => [$o($T, 'ambos', '', '', '', 3, 1)],
            113 => [$o($T, 'ambos', '', '', '', 3, 1)],
            114 => [$o($T, 'ambos', '', '', '', 3, 1)],
            115 => [$o($T, 'ambos', '', '', '', 3, 1)],
            116 => [$o($T, 'ambos', '', '', '', 3, 1)],
            117 => [$o($T, 'ambos', '', '', '', 3, 1)],
            118 => [$o($T, 'ambos', '', '', '', 3, 1)],
            119 => [$o($T, 'ambos', '', '', '', 3, 1)],
            121 => [$o($T, 'ambos', '', '', '', 3, 1)],
            123 => [$o($T, 'ambos', '', '', '', 3, 1)],
            207 => [$o($T, 'ambos', '', '', '', 3, 1)],
            213 => [$o($T, 'ambos', '', '', '', 3, 1)],
            214 => [$o($T, 'ambos', '', '', '', 3, 1)],
            215 => [$o($T, 'ambos', '', '', '', 3, 1)],
            216 => [$o($T, 'ambos', '', '', '', 3, 1)],
            125 => [$o($T, 'ambos', '', '', '', 3, 1)],
            126 => [$o($T, 'ambos', '', '', '', 3, 1)],
            127 => [$o($T, 'ambos', '', '', '', 3, 1)],
            218 => [$o($T, 'ambos', '', '', '', 3, 1)],
            219 => [$o($T, 'ambos', '', '', '', 3, 1)],
            220 => [$o($T, 'ambos', '', '', '', 3, 1)],

            // Autoinmunidad
            128 => [$o($T, 'ambos', '', '', '', 3, 1)],
            129 => [$o($T, 'ambos', '', '', '', 3, 1)],
            130 => [$o($T, 'ambos', '', '', '', 3, 1)],
            131 => [$o($T, 'ambos', '', '', '', 3, 1)],
            132 => [$o($T, 'ambos', '', '', '', 3, 1)],
            133 => [$o($T, 'ambos', '', '', '', 3, 1)],
            223 => [$o($T, 'ambos', '', '', '', 3, 1)],
            224 => [$o($T, 'ambos', '', '', '', 3, 1)],

            134 => [$o($T, 'ambos', '30', '100', 'ng/mL')], // 25-OH vit D (suficiencia aprox.)

            // Inmunoglobulinas / complemento
            135 => [$o($T, 'ambos', '90', '180', 'mg/dL')],
            136 => [$o($T, 'ambos', '10', '40', 'mg/dL')],
            137 => [$o($T, 'ambos', '700', '1600', 'mg/dL')],
            138 => [$o($T, 'ambos', '40', '230', 'mg/dL')],
            139 => [$o($T, 'ambos', '70', '400', 'mg/dL')],
            140 => [$o($T, 'ambos', '90', '180', 'mg/dL')],
            141 => [$o($T, 'ambos', '10', '40', 'mg/dL')],
            142 => [$o($T, 'ambos', '700', '1600', 'mg/dL')],
            143 => [$o($T, 'ambos', '40', '230', 'mg/dL')],
            144 => [$o($T, 'ambos', '70', '400', 'mg/dL')],
            145 => [$o($T, 'ambos', '25', '45', 'mg/dL')],

            // Líquidos / otros
            146 => [$o($T, 'ambos', '', '', '', 3, 1)],
            147 => [$o($T, 'ambos', '', '', '', 3, 1)],
            148 => [$o($T, 'ambos', '', '', '', 3, 1)],
            149 => [$o($T, 'ambos', '', '', '', 3, 1)],
            150 => [$o($T, 'ambos', '', '', '', 3, 1)],
            151 => [$o($T, 'ambos', '', '', '', 3, 1)],
            152 => [$o($T, 'ambos', '', '', '', 3, 1)],
            153 => [$o($T, 'ambos', '', '', '', 3, 1)],
            154 => [$o($T, 'ambos', '', '', '', 3, 1)],
            155 => [$o($T, 'ambos', '', '', '', 3, 1)],
            156 => [$o($T, 'ambos', '', '', '', 3, 1)],
            157 => [$o($T, 'ambos', '', '', '', 3, 1)],
            158 => [$o($T, 'ambos', '', '', '', 3, 1)],
            159 => [$o($T, 'ambos', '', '', '', 3, 1)],
            160 => [$o($T, 'ambos', '', '', '', 3, 1)],
            161 => [$o($T, 'ambos', '', '', '', 3, 1)],
            162 => [$o($T, 'ambos', '', '', '', 3, 1)],
            164 => [$o($T, 'ambos', '', '', '', 3, 1)],
            165 => [$o($T, 'ambos', '', '', '', 3, 1)],
            166 => [$o($T, 'ambos', '', '', '', 3, 1)],
            167 => [$o($T, 'ambos', '', '', '', 3, 1)],
            168 => [$o($T, 'ambos', '', '', '', 3, 1)],
            169 => [$o($T, 'ambos', '0', '40', 'U/L')],
            170 => [$o($T, 'ambos', '', '', '', 3, 1)],
            204 => [$o($T, 'ambos', '', '', '', 3, 1)],
            205 => [$o($T, 'ambos', '', '', '', 3, 1)],
            206 => [$o($T, 'ambos', '', '', '', 3, 1)],

            // Orina
            171 => [$o($T, 'ambos', '', '', '', 3, 1)],
            172 => [$o($T, 'ambos', '30', '150', 'mg/24h')],
            173 => [$o($T, 'ambos', '500', '2000', 'mg/24h')],
            174 => [$o($T, 'ambos', '0', '5', 'mUI/mL')],
            175 => [$o($T, 'ambos', '0', '500', 'U/L')],
            176 => [$o($T, 'ambos', '85', '125', 'mL/min/1.73m²')], // eGFR proxy — orientativo

            // Alergias panel
            188 => [$o($T, 'ambos', '', '', '', 3, 1)],
            189 => [$o($T, 'ambos', '', '', '', 3, 1)],
            190 => [$o($T, 'ambos', '', '', '', 3, 1)],
            191 => [$o($T, 'ambos', '', '', '', 3, 1)],
            192 => [$o($T, 'ambos', '', '', '', 3, 1)],
            193 => [$o($T, 'ambos', '', '', '', 3, 1)],

            211 => [$o($T, 'ambos', '7.35', '7.45', 'pH')],
            212 => [$o($T, 'ambos', '15', '45', 'µg/dL')],
            227 => [$o($T, 'ambos', '', '', '', 3, 1)],

            // Química / electrolitos (rangos adulto; población Todos)
            15 => [$o($T, 'masculino', '0', '15', 'mm/h'), $o($T, 'femenino', '0', '20', 'mm/h')],
            35 => [$o($T, 'ambos', '0.2', '1.2', 'mg/dL')], // bilirrubina total aprox.
            36 => [$o($T, 'masculino', '0.7', '1.3', 'mg/dL'), $o($T, 'femenino', '0.6', '1.1', 'mg/dL')],
            38 => [$o($T, 'ambos', '150', '260', 'mg/dL')], // colesterol total
            39 => [$o($T, 'ambos', '0', '40', 'U/L')],      // AST
            41 => [$o($T, 'ambos', '0', '40', 'U/L')],      // GPT duplicado en catálogo
            42 => [$o($T, 'ambos', '0', '40', 'U/L')],
            43 => [$o($T, 'ambos', '5', '35', 'U/L')],
            44 => [$o($T, 'ambos', '70', '110', 'mg/dL')],   // glicemia ayunas
            49 => [$o($T, 'ambos', '30', '200', 'mg/dL')],
            50 => [$o($T, 'ambos', '20', '40', 'mg/dL')],
            52 => [$o($T, 'ambos', '3.5', '5.3', 'mEq/L')],
            53 => [$o($T, 'ambos', '98', '108', 'mEq/L')],
            54 => [$o($T, 'ambos', '135', '145', 'mEq/L')],
            55 => [$o($T, 'ambos', '1.6', '2.6', 'mg/dL')],
            57 => [$o($T, 'ambos', '8.5', '10.5', 'mg/dL')],
            58 => [$o($T, 'ambos', '30', '200', 'mg/dL')],
            59 => [$o($T, 'ambos', '150', '260', 'mg/dL')],
            60 => [$o($T, 'masculino', '40', '60', 'mg/dL'), $o($T, 'femenino', '50', '70', 'mg/dL')], // HDL
            61 => [$o($T, 'ambos', '60', '180', 'mg/dL')],
            62 => [$o($T, 'ambos', '25', '50', '%')],
            63 => [$o($T, 'ambos', '0', '40', 'U/L')],
            64 => [$o($T, 'ambos', '0', '40', 'U/L')],
            65 => [$o($T, 'ambos', '0', '120', 'U/L')],
            66 => [$o($T, 'ambos', '5', '40', 'U/L')],
            80 => [$o($T, 'ambos', '0', '5', 'mg/L')],       // PCR
            94 => [$o($T, 'ambos', '5', '40', 'U/L')],
            120 => [$o($T, 'ambos', '', '', '', 3, 1)],
            122 => [$o($T, 'ambos', '', '', '', 3, 1)],      // H. Pilory IgA si existe
            124 => [$o($T, 'ambos', '0', '100', 'UI/mL')],
            226 => [$o($T, 'ambos', '8', '20', 'mg/dL')],    // BUN
        ];

        return array_replace($base, ValoresReferenciaBandas::reemplazosPorPoblacion());
    }

    /**
     * IDs de prianacategoria con compleja = 0 (prueba simple) — para asegurar cobertura.
     *
     * @return int[]
     */
    public static function idsPruebasSimples(): array
    {
        return [
            1, 3, 4, 5, 6, 7, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38, 39, 40, 41, 42, 43, 44, 45, 46, 47, 48, 49, 50, 51, 52, 53, 54, 55, 56, 57, 58, 59, 60, 61, 62, 63, 64, 65, 66, 67, 68, 69, 70, 71, 72, 73, 74, 75, 76, 77, 78, 79, 80, 81, 82, 83, 84, 85, 86, 87, 88, 89, 90, 91, 92, 93, 94, 95, 96, 97, 98, 99, 100, 101, 102, 103, 104, 105, 106, 107, 108, 109, 110, 111, 112, 113, 114, 115, 116, 117, 118, 119, 120, 121, 122, 123, 124, 125, 126, 127, 128, 129, 130, 131, 132, 133, 134, 135, 136, 137, 138, 139, 140, 141, 142, 143, 144, 145, 146, 147, 148, 149, 150, 151, 152, 153, 154, 155, 156, 157, 158, 159, 160, 161, 162, 164, 165, 166, 167, 168, 169, 170, 171, 172, 173, 174, 175, 176, 179, 180, 181, 182, 183, 184, 185, 186, 187, 188, 189, 190, 191, 192, 193, 202, 203, 204, 205, 206, 207, 208, 209, 210, 211, 212, 213, 214, 215, 216, 217, 218, 219, 220, 221, 222, 223, 224, 225, 226, 227,
        ];
    }

    /**
     * Catálogo completo: rellena huecos con fila cualitativa genérica.
     *
     * @return array<int, list<array{p:int,s:string,min:string,max:string,u:string,o?:int,f?:int}>>
     */
    public static function catalogoCompleto(): array
    {
        $T   = 15;
        $def = [['p' => $T, 's' => 'ambos', 'min' => '', 'max' => '', 'u' => '', 'o' => 3, 'f' => 1]];
        $m   = self::priresultadosPorPrueba();
        $out = [];
        foreach (self::idsPruebasSimples() as $id) {
            $out[$id] = $m[$id] ?? $def;
        }

        return $out;
    }
}
