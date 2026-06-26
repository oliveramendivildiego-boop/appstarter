<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\LabotestModel;
use App\Models\OpcionModel;
use Config\CategoricalComparisonMap;

/**
 * Construye la tabla heatmap de comparación seriada (M1, M2, M3) para resultados categóricos.
 */
class CategoricalSerialComparisonService
{
    private LabotestModel $labotestModel;

    public function __construct(?LabotestModel $labotestModel = null)
    {
        $this->labotestModel = $labotestModel ?? model(LabotestModel::class);
    }

    /**
     * @param list<object|array<string, mixed>> $items Filas del reporte (subItems de prueba compuesta)
     *
     * @return array<string, mixed>|null null si no aplica o no hay filas comparables
     */
    public function buildFromReportItems(array $items, int $prianacategoriaId): ?array
    {
        if ($prianacategoriaId < 1 || $items === []) {
            return null;
        }

        if (! $this->prianacategoriaTieneGraficar($prianacategoriaId)) {
            return null;
        }

        return $this->buildHeatmapFromParsedItems($items, $prianacategoriaId);
    }

    /**
     * Igual que buildFromReportItems pero sin consultar graficar en BD (precalculado en prepareReportData).
     *
     * @param list<object|array<string, mixed>> $items
     */
    public function buildFromReportItemsWithModo(array $items, int $prianacategoriaId, int $graficarModo): ?array
    {
        if ($prianacategoriaId < 1 || $items === [] || $graficarModo <= LabotestModel::GRAFICAR_NO) {
            return null;
        }

        return $this->buildHeatmapFromParsedItems($items, $prianacategoriaId);
    }

    /**
     * @param list<object|array<string, mixed>> $items
     */
    private function buildHeatmapFromParsedItems(array $items, int $prianacategoriaId): ?array
    {
        unset($prianacategoriaId);

        $parsed = $this->parseItems($items);
        if ($parsed['rows'] === []) {
            return null;
        }

        $sections = [];
        foreach (CategoricalComparisonMap::SECTION_LABELS as $sectionKey => $sectionTitle) {
            $sectionRows = [];
            foreach ($parsed['rows'] as $rowKey => $row) {
                if (($row['section'] ?? '') !== $sectionKey) {
                    continue;
                }
                if (! $this->filaTieneAlgunValor($row)) {
                    continue;
                }
                $samples = $this->buildSampleCells($row['samples'] ?? []);
                $trend = $this->computeTrend(array_column($samples, 'value', 'label'));
                $sectionRows[] = [
                    'parametro' => (string) ($row['parametro'] ?? ''),
                    'samples'   => $samples,
                    'trend'     => $trend,
                ];
            }
            if ($sectionRows !== []) {
                $sections[] = [
                    'key'   => $sectionKey,
                    'title' => $sectionTitle,
                    'rows'  => $sectionRows,
                ];
            }
        }

        if ($sections === []) {
            return null;
        }

        return [
            'sample_labels' => CategoricalComparisonMap::SAMPLE_LABELS,
            'sections'      => $sections,
            'legend'        => CategoricalComparisonMap::LEGEND_ITEMS,
        ];
    }

    /**
     * @param list<object|array<string, mixed>> $items
     *
     * @return array{rows: array<string, array<string, mixed>>}
     */
    private function parseItems(array $items): array
    {
        $rows = [];
        $currentSample = null;
        $currentSection = null;

        foreach ($items as $raw) {
            $item = is_array($raw) ? (object) $raw : $raw;
            if ((int) ($item->es_separador ?? 0) === 1) {
                $ctx = $this->parseSeparatorContext((string) ($item->nombre ?? ''));
                $currentSample = $ctx['sample'];
                $currentSection = $ctx['section'];
                continue;
            }

            if ($currentSample === null || $currentSection === null) {
                continue;
            }

            $opcionId = (int) ($item->opcion_id ?? 0);
            if (! OpcionModel::isSelect($opcionId)) {
                continue;
            }

            $parametro = $this->normalizeParamName((string) ($item->nombre ?? ''));
            if ($parametro === '') {
                continue;
            }

            $rowKey = $currentSection . '|' . $parametro;
            if (! isset($rows[$rowKey])) {
                $rows[$rowKey] = [
                    'section'   => $currentSection,
                    'parametro' => $parametro,
                    'samples'   => [1 => '', 2 => '', 3 => ''],
                    'orden'     => count($rows),
                ];
            }

            $valor = trim((string) ($item->regvalues ?? ''));
            if ($valor === '-' || $valor === '') {
                $valor = '';
            }
            $rows[$rowKey]['samples'][$currentSample] = $valor;
        }

        return ['rows' => $rows];
    }

    /**
     * @return array{sample: int|null, section: string|null}
     */
    private function parseSeparatorContext(string $title): array
    {
        $titleNorm = $this->normalizeText($title);
        if ($titleNorm === '') {
            return ['sample' => null, 'section' => null];
        }

        if (! preg_match('/\b([123])\s*(ra|da|er|ro|rv|th|do)\b/u', $titleNorm, $sampleMatch)) {
            return ['sample' => null, 'section' => null];
        }

        $sample = (int) $sampleMatch[1];
        if ($sample < 1 || $sample > 3) {
            return ['sample' => null, 'section' => null];
        }

        $section = null;
        if (str_contains($titleNorm, 'macroscop')) {
            $section = 'macroscopico';
        } elseif (str_contains($titleNorm, 'microscop')) {
            $section = 'microscopico';
        } elseif (str_contains($titleNorm, 'otros')) {
            $section = 'otros';
        }

        return ['sample' => $section !== null ? $sample : null, 'section' => $section];
    }

    /**
     * @param array<int, string> $samplesByIndex
     *
     * @return list<array{label: string, value: string, badge_class: string, display: string}>
     */
    private function buildSampleCells(array $samplesByIndex): array
    {
        $out = [];
        foreach (CategoricalComparisonMap::SAMPLE_LABELS as $idx => $label) {
            $sampleNum = $idx + 1;
            $value = trim((string) ($samplesByIndex[$sampleNum] ?? ''));
            $badge = $this->resolveBadge($value);
            $out[] = [
                'label'       => $label,
                'value'       => $value,
                'badge_class' => $badge['class'],
                'display'     => $badge['display'],
            ];
        }

        return $out;
    }

    /**
     * @param array<string, string> $valuesByLabel M1/M2/M3 => valor
     *
     * @return array{symbol: string, label: string}
     */
    public function computeTrend(array $valuesByLabel): array
    {
        $labels = CategoricalComparisonMap::SAMPLE_LABELS;
        $values = [];
        foreach ($labels as $label) {
            $values[] = trim((string) ($valuesByLabel[$label] ?? ''));
        }

        $norm = array_map(fn (string $v): string => $this->normalizeText($v), $values);

        if ($norm[0] !== '' && $norm[0] === $norm[1] && $norm[1] === $norm[2]) {
            return ['symbol' => '=', 'label' => ''];
        }

        $ranks = array_map(fn (string $v): ?int => $this->ordinalRank($v), $norm);
        if ($this->ranksStrictlyIncreasing($ranks)) {
            return ['symbol' => '↑', 'label' => ''];
        }
        if ($this->ranksStrictlyDecreasing($ranks)) {
            return ['symbol' => '↓', 'label' => ''];
        }

        $startFinding = $this->isFinding($norm[0]);
        $endFinding = $this->isFinding($norm[2]);
        $startNeutral = $this->isNeutral($norm[0]);
        $endNeutral = $this->isNeutral($norm[2]);

        if ($startNeutral && $endFinding && ! ($norm[0] === $norm[1] && $norm[1] === $norm[2])) {
            return ['symbol' => '↑', 'label' => ''];
        }

        if ($startFinding && $endNeutral && ! ($norm[0] === $norm[1] && $norm[1] === $norm[2])) {
            return ['symbol' => '↓', 'label' => ''];
        }

        return ['symbol' => '↔', 'label' => 'Cambio de característica'];
    }

    /**
     * @return array{class: string, display: string}
     */
    public function resolveBadge(string $value): array
    {
        $display = trim($value);
        if ($display === '' || $display === '-') {
            return [
                'class'   => CategoricalComparisonMap::BADGE_CLASSES['neutral'],
                'display' => '—',
            ];
        }

        $slug = $this->normalizeText($display);

        if ($this->isNeutral($slug)) {
            return ['class' => CategoricalComparisonMap::BADGE_CLASSES['neutral'], 'display' => $display];
        }

        if ($this->ordinalRank($slug) === 0) {
            return ['class' => CategoricalComparisonMap::BADGE_CLASSES['escaso'], 'display' => $display];
        }
        if ($this->ordinalRank($slug) === 1) {
            return ['class' => CategoricalComparisonMap::BADGE_CLASSES['moderado'], 'display' => $display];
        }
        if ($this->ordinalRank($slug) === 2) {
            return ['class' => CategoricalComparisonMap::BADGE_CLASSES['abundante'], 'display' => $display];
        }

        if ($this->isPresence($slug)) {
            return ['class' => CategoricalComparisonMap::BADGE_CLASSES['presente'], 'display' => $display];
        }

        if ($this->isDescriptiveFinding($slug)) {
            return ['class' => CategoricalComparisonMap::BADGE_CLASSES['descriptivo'], 'display' => $display];
        }

        return ['class' => CategoricalComparisonMap::BADGE_CLASSES['default'], 'display' => $display];
    }

    private function prianacategoriaTieneGraficar(int $prianacategoriaId): bool
    {
        return $this->getGraficarModo($prianacategoriaId) > LabotestModel::GRAFICAR_NO;
    }

    public function getGraficarModo(int $prianacategoriaId): int
    {
        if ($prianacategoriaId < 1) {
            return LabotestModel::GRAFICAR_NO;
        }

        $info = $this->labotestModel->getSubInfo($prianacategoriaId);

        return LabotestModel::normalizeGraficar((int) ($info->graficar ?? 0));
    }

    /**
     * @param array<string, mixed> $row
     */
    private function filaTieneAlgunValor(array $row): bool
    {
        foreach ((array) ($row['samples'] ?? []) as $val) {
            $v = trim((string) $val);
            if ($v !== '' && $v !== '-') {
                return true;
            }
        }

        return false;
    }

    private function normalizeParamName(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return '';
        }

        return mb_strtoupper($name, 'UTF-8');
    }

    private function normalizeText(string $value): string
    {
        $value = trim($value);
        if ($value === '' || $value === '-') {
            return '';
        }

        $value = mb_strtolower($value, 'UTF-8');
        $value = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ü'], ['a', 'e', 'i', 'o', 'u', 'u'], $value);

        return preg_replace('/\s+/u', ' ', $value) ?? $value;
    }

    private function isNeutral(string $slug): bool
    {
        if ($slug === '') {
            return true;
        }

        foreach (CategoricalComparisonMap::NEUTRAL_VALUES as $neutral) {
            if ($slug === $neutral || str_contains($slug, $neutral)) {
                return true;
            }
        }

        return false;
    }

    private function isPresence(string $slug): bool
    {
        foreach (CategoricalComparisonMap::PRESENCE_VALUES as $presence) {
            if ($slug === $presence || str_contains($slug, $presence)) {
                return true;
            }
        }

        return false;
    }

    private function isDescriptiveFinding(string $slug): bool
    {
        if ($slug === '' || $this->isNeutral($slug) || $this->isPresence($slug)) {
            return false;
        }

        if ($this->ordinalRank($slug) !== null) {
            return false;
        }

        return true;
    }

    private function isFinding(string $slug): bool
    {
        if ($slug === '' || $this->isNeutral($slug)) {
            return false;
        }

        return $this->isPresence($slug) || $this->isDescriptiveFinding($slug) || $this->ordinalRank($slug) !== null;
    }

    private function ordinalRank(string $slug): ?int
    {
        if ($slug === '') {
            return null;
        }

        $groups = [
            ['escaso', 'escasos', 'escasa', 'escasas'],
            ['moderado', 'moderados', 'moderada', 'moderadas'],
            ['abundante', 'abundantes'],
        ];

        foreach ($groups as $rank => $terms) {
            foreach ($terms as $term) {
                if ($slug === $term || str_contains($slug, $term)) {
                    return $rank;
                }
            }
        }

        return null;
    }

    /**
     * @param list<int|null> $ranks
     */
    private function ranksStrictlyIncreasing(array $ranks): bool
    {
        if (in_array(null, $ranks, true)) {
            return false;
        }

        return $ranks[0] < $ranks[1] && $ranks[1] < $ranks[2];
    }

    /**
     * @param list<int|null> $ranks
     */
    private function ranksStrictlyDecreasing(array $ranks): bool
    {
        if (in_array(null, $ranks, true)) {
            return false;
        }

        return $ranks[0] > $ranks[1] && $ranks[1] > $ranks[2];
    }
}
