<?php

namespace App\Libraries;

/**
 * Gráfica Levey-Jennings rasterizada (PNG) compatible con Dompdf.
 */
class QcLeveyJenningsChartImage
{
    /**
     * @param list<array<string, mixed>> $valores
     * @param array{n:int,mean:float,sd:float,cv_percent:?float}|null $qc
     */
    public static function toPngDataUri(array $valores, ?array $qc): ?string
    {
        if ($qc === null || (int) ($qc['n'] ?? 0) < 1 || ! extension_loaded('gd')) {
            return null;
        }

        $points = [];
        foreach ($valores as $v) {
            $points[] = [
                'fecha'    => (string) ($v['fecha'] ?? ''),
                'valor'    => (float) ($v['valor'] ?? 0),
                'esperado' => isset($v['esperado']) && $v['esperado'] !== null && $v['esperado'] !== ''
                    ? (float) $v['esperado']
                    : null,
            ];
        }
        if ($points === []) {
            return null;
        }

        $chartW = 680;
        $chartH = 280;
        $padL   = 52;
        $padR   = 18;
        $padT   = 22;
        $padB   = 48;

        $mean = (float) $qc['mean'];
        $sd   = (float) ($qc['sd'] ?? 0);

        $yVals = array_map(static fn (array $p): float => $p['valor'], $points);
        $yVals[] = $mean;
        if ($sd > 0) {
            for ($k = -3; $k <= 3; $k++) {
                $yVals[] = $mean + ($k * $sd);
            }
        }
        foreach ($points as $p) {
            if ($p['esperado'] !== null) {
                $yVals[] = $p['esperado'];
            }
        }

        $yMin = min($yVals);
        $yMax = max($yVals);
        $span = $yMax - $yMin;
        $margin = $span > 0 ? $span * 0.08 : 1.0;
        $yMin -= $margin;
        $yMax += $margin;
        if ($yMax <= $yMin) {
            $yMax = $yMin + 1.0;
        }

        $plotW = $chartW - $padL - $padR;
        $plotH = $chartH - $padT - $padB;
        $count = count($points);

        $yToPx = static function (float $y) use ($yMin, $yMax, $plotH, $padT): int {
            return (int) round($padT + $plotH * (1.0 - (($y - $yMin) / ($yMax - $yMin))));
        };
        $xToPx = static function (int $i) use ($count, $padL, $plotW): int {
            if ($count <= 1) {
                return (int) round($padL + ($plotW / 2));
            }

            return (int) round($padL + ($i / ($count - 1)) * $plotW);
        };

        $img = imagecreatetruecolor($chartW, $chartH);
        if ($img === false) {
            return null;
        }

        $white      = imagecolorallocate($img, 255, 255, 255);
        $plotBg     = imagecolorallocate($img, 250, 250, 250);
        $plotBorder = imagecolorallocate($img, 226, 232, 240);
        $grid       = imagecolorallocate($img, 226, 232, 240);
        $green      = imagecolorallocate($img, 25, 135, 84);
        $blue       = imagecolorallocate($img, 13, 110, 253);
        $gray       = imagecolorallocate($img, 108, 117, 125);
        $yellow     = imagecolorallocate($img, 211, 158, 0);
        $red        = imagecolorallocate($img, 220, 53, 69);
        $pink       = imagecolorallocate($img, 214, 51, 132);
        $labelCol   = imagecolorallocate($img, 100, 116, 139);

        imagefill($img, 0, 0, $white);
        imagefilledrectangle($img, $padL, $padT, $padL + $plotW, $padT + $plotH, $plotBg);
        imagerectangle($img, $padL, $padT, $padL + $plotW, $padT + $plotH, $plotBorder);

        for ($t = 0; $t <= 4; $t++) {
            $val = $yMin + (($yMax - $yMin) * $t / 4);
            $yy  = $yToPx($val);
            imageline($img, $padL, $yy, $padL + $plotW, $yy, $grid);
            imagestring($img, 2, 4, $yy - 6, self::fmtAxis($val), $labelCol);
        }

        $hline = static function ($img, int $y1, int $x1, int $x2, int $color, bool $dashed = false): void {
            if (! $dashed) {
                imageline($img, $x1, $y1, $x2, $y1, $color);
                return;
            }
            for ($x = $x1; $x < $x2; $x += 6) {
                imageline($img, $x, $y1, min($x + 3, $x2), $y1, $color);
            }
        };

        if ($sd > 0) {
            $specs = [
                ['y' => $mean, 'color' => $blue, 'dash' => false, 'w' => 2],
                ['y' => $mean + $sd, 'color' => $gray, 'dash' => true],
                ['y' => $mean - $sd, 'color' => $gray, 'dash' => true],
                ['y' => $mean + 2 * $sd, 'color' => $yellow, 'dash' => true],
                ['y' => $mean - 2 * $sd, 'color' => $yellow, 'dash' => true],
                ['y' => $mean + 3 * $sd, 'color' => $red, 'dash' => true],
                ['y' => $mean - 3 * $sd, 'color' => $red, 'dash' => true],
            ];
            foreach ($specs as $spec) {
                $yy = $yToPx((float) $spec['y']);
                $hline($img, $yy, $padL, $padL + $plotW, $spec['color'], $spec['dash']);
            }
        } else {
            $hline($img, $yToPx($mean), $padL, $padL + $plotW, $blue, false);
        }

        $seg = [];
        foreach ($points as $i => $p) {
            if ($p['esperado'] === null) {
                if (count($seg) >= 2) {
                    self::drawPolyline($img, $seg, $pink, true);
                }
                $seg = [];
                continue;
            }
            $seg[] = ['x' => $xToPx($i), 'y' => $yToPx($p['esperado'])];
        }
        if (count($seg) >= 2) {
            self::drawPolyline($img, $seg, $pink, true);
        }

        $linePts = [];
        foreach ($points as $i => $p) {
            $linePts[] = ['x' => $xToPx($i), 'y' => $yToPx($p['valor'])];
        }
        self::drawPolyline($img, $linePts, $green, false);

        foreach ($linePts as $pt) {
            imagefilledellipse($img, $pt['x'], $pt['y'], 7, 7, $green);
            imageellipse($img, $pt['x'], $pt['y'], 7, 7, $white);
        }

        $step = max(1, (int) ceil($count / 8));
        foreach ($points as $i => $p) {
            if ($i % $step !== 0 && $i !== $count - 1) {
                continue;
            }
            $x = $xToPx($i);
            imagestringup($img, 1, $x - 3, $chartH - 10, substr($p['fecha'], 5), $labelCol);
        }

        ob_start();
        imagepng($img);
        $png = (string) ob_get_clean();
        imagedestroy($img);

        if ($png === '') {
            return null;
        }

        return 'data:image/png;base64,' . base64_encode($png);
    }

    /**
     * @param list<array{x:int,y:int}> $pts
     */
    private static function drawPolyline($img, array $pts, int $color, bool $dashed): void
    {
        $n = count($pts);
        if ($n < 2) {
            return;
        }
        for ($i = 1; $i < $n; $i++) {
            if ($dashed) {
                self::drawDashedLine($img, $pts[$i - 1]['x'], $pts[$i - 1]['y'], $pts[$i]['x'], $pts[$i]['y'], $color);
            } else {
                imageline($img, $pts[$i - 1]['x'], $pts[$i - 1]['y'], $pts[$i]['x'], $pts[$i]['y'], $color);
            }
        }
    }

    private static function drawDashedLine($img, int $x1, int $y1, int $x2, int $y2, int $color): void
    {
        $dx = $x2 - $x1;
        $dy = $y2 - $y1;
        $len = sqrt($dx * $dx + $dy * $dy);
        if ($len < 1) {
            return;
        }
        $dash = 5;
        $gap  = 4;
        $steps = (int) floor($len / ($dash + $gap));
        for ($s = 0; $s <= $steps; $s++) {
            $t0 = ($s * ($dash + $gap)) / $len;
            $t1 = min(1.0, ($s * ($dash + $gap) + $dash) / $len);
            imageline(
                $img,
                (int) round($x1 + $dx * $t0),
                (int) round($y1 + $dy * $t0),
                (int) round($x1 + $dx * $t1),
                (int) round($y1 + $dy * $t1),
                $color
            );
        }
    }

    private static function fmtAxis(float $val): string
    {
        return number_format($val, 4, '.', '');
    }
}
