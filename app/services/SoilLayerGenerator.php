<?php

namespace App\Services;

use DateTimeImmutable;
use DateTimeZone;
use DomainException;
use RuntimeException;
use ZipArchive;

/**
 * Generates a multi-layer soil compaction workbook from a ONE-sheet master template by
 * patching the .xlsx package directly (no PhpSpreadsheet, no cloning).
 *
 * Behaviour (mirrors soil_compaction_gui1.py):
 *  - Layer 1 is the master sheet, byte-for-byte, renamed only in workbook.xml.
 *    (Its date cell changes only if the user supplies custom dates.)
 *  - Layers 2..N are copies of the master with: date, layer ref, A, B, H, P replaced.
 *  - Each copy gets its own drawing (logo) and printer-settings parts, and the master's
 *    sheet-scoped defined names (print area etc.) are replicated for every layer.
 *
 * Safety rules (fail loudly instead of producing a damaged file):
 *  - the template must contain exactly one sheet;
 *  - the sheet may only relate to: a drawing (image-only), printer settings, external hyperlinks;
 *  - cached formula values are written ONLY for plain arithmetic on numeric cells.
 *    Any other formula has its cached value removed so Excel recalculates it on open.
 */
class SoilLayerGenerator
{
    private const RANGES = ['A' => [9200, 10000], 'B' => [2800, 3700], 'H' => [5800, 7000]];
    private const CELL_RE = '<c r="([A-Z]+)(\d+)"([^>]*?)(?:/>|>(.*?)</c>)';
    private const REL_NS = 'http://schemas.openxmlformats.org/package/2006/relationships';

    /** @var array<string,string> every part of the template package */
    private array $files = [];
    private string $sheetTag = '';
    private string $origName = '';
    private string $sheetRid = '';
    private string $sheetPath = '';
    private string $sheetXml = '';
    private ?string $drawingPath = null;
    private ?string $printerPath = null;
    private ?string $drawingRelsPath = null;
    /** @var array<int,array{a:array<string,string>,tag:string}> */
    private array $sheetRels = [];
    /** @var string[] */
    private array $strings = [];
    /** @var array<int,array{0:int,1:int,2:int,3:int}> */
    private array $merges = [];
    /** @var array<int,array{0:int,1:int,2:?string}> [row,col,text] row-major */
    private array $cells = [];

    /**
     * @param array{
     *   total_layers:int, start_comp:float, start_moist:float, step_comp:float, step_moist:float,
     *   custom_dates?:string[], skip_fridays?:bool, data_offset?:int, meta_offset?:int
     * } $o
     * @return string[] names of the sheets that were created
     */
    public function generate(string $templatePath, string $outPath, array $o): array
    {
        $total       = (int) $o['total_layers'];
        $dataOff     = (int) ($o['data_offset'] ?? 2);
        $metaOff     = (int) ($o['meta_offset'] ?? 3);
        $skipFri     = (bool) ($o['skip_fridays'] ?? true);
        $customDates = $o['custom_dates'] ?? [];

        // validate dates BEFORE doing any work
        $parsedCustom = [];
        if (!empty($customDates)) {
            if (count($customDates) !== $total) {
                throw new RuntimeException('You provided ' . count($customDates) . " dates but need exactly {$total}.");
            }
            foreach ($customDates as $d) {
                $parsedCustom[] = $this->parseIso((string) $d);
            }
        }

        $this->load($templatePath);

        // ---- locate cells by label (same rules as the Python tool) ----
        $const = [];
        foreach (['M', 'F', 'D', 'J'] as $k) {
            $found = $this->findExact($k);
            $raw = $found ? $this->readCell($this->target($found, $dataOff)) : null;
            if ($raw === null || !is_numeric($raw)) {
                throw new RuntimeException("Missing or invalid value for constant {$k}. Check the master sheet labels.");
            }
            $const[$k] = (float) $raw;
        }

        $dateFound  = $this->findKeywords(['date', 'test']);
        $layerFound = $this->findKeywords(['layer', 'ref']);
        if (!$dateFound || !$layerFound) {
            throw new RuntimeException('Could not find the "Date of Test" or "Layer Ref." labels in the master sheet.');
        }
        $dateRef  = $this->target($dateFound, $metaOff);
        $layerRef = $this->target($layerFound, $metaOff);

        $refs = [];
        foreach (['A', 'B', 'H', 'P'] as $k) {
            $found = $this->findExact($k);
            if (!$found) {
                throw new RuntimeException("Label \"{$k}\" not found in the master sheet.");
            }
            $refs[$k] = $this->target($found, $dataOff);
        }

        $base = $this->parseBaseDate($this->readCell($dateRef));

        // ---- sheet XML variants ----
        $master = $this->sheetXml;
        if (!empty($parsedCustom)) {
            $master = $this->patchCell($master, $dateRef, $parsedCustom[0]->format('d M Y'));
            $base = $parsedCustom[0];
        }
        // copies: not "selected" (else Excel groups sheets) and no duplicate sheet uid
        $copyBase = str_replace(' tabSelected="1"', '', $this->sheetXml);
        $copyBase = preg_replace('~\sxr:uid="[^"]*"~', '', $copyBase, 1);

        $sheets = [];
        $last = $base;
        for ($i = 1; $i <= $total; $i++) {
            $name = "Layer_{$i}_" . $this->ordinal($i);

            if ($i === 1) {
                $sheets[] = [$name, $master];
                continue;
            }

            $targetComp  = min($o['start_comp'] + $o['step_comp'] * ($i - 1), 99.0);
            $targetMoist = max(7.5, min($o['start_moist'] + $o['step_moist'] * ($i - 1), 9.5));
            $vals = $this->solve($targetComp, $targetMoist, $const);

            if (!empty($parsedCustom)) {
                $d = $parsedCustom[$i - 1];
            } else {
                $d = $this->nextDay($last, $skipFri);
                $last = $d;
            }

            $xml = $copyBase;
            $xml = $this->patchCell($xml, $dateRef, $d->format('d M Y'));
            $xml = $this->patchCell($xml, $layerRef, $this->ordinal($i) . ' layer');
            foreach (['A', 'B', 'H', 'P'] as $k) {
                $xml = $this->patchCell($xml, $refs[$k], $vals[$k]);
            }
            $sheets[] = [$name, $this->recalc($xml)];
        }

        $this->assemble($sheets, $outPath);
        return array_column($sheets, 0);
    }

    // ==================================================================
    // Loading the template package
    // ==================================================================
    private function load(string $path): void
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) {
            throw new RuntimeException('The uploaded file is not a valid .xlsx workbook.');
        }
        $this->files = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if ($name !== false && !str_ends_with($name, '/')) {
                $this->files[$name] = (string) $zip->getFromIndex($i);
            }
        }
        $zip->close();

        foreach (['xl/workbook.xml', 'xl/_rels/workbook.xml.rels', '[Content_Types].xml'] as $req) {
            if (!isset($this->files[$req])) {
                throw new RuntimeException("Template is missing {$req}. Please re-save it from Excel.");
            }
        }

        // exactly one sheet
        $wb = $this->files['xl/workbook.xml'];
        preg_match_all('~<sheet\b[^>]*>~', $wb, $sm);
        if (count($sm[0]) !== 1) {
            throw new RuntimeException(
                'The master workbook must contain exactly one sheet (found ' . count($sm[0]) . '). '
                . 'Delete the other sheets and upload it again.'
            );
        }
        $this->sheetTag = $sm[0][0];
        $sa = $this->attrs($this->sheetTag);
        $this->origName = $sa['name'] ?? '';
        $this->sheetRid = $sa['r:id'] ?? '';

        $wbRels = [];
        foreach ($this->parseRels($this->files['xl/_rels/workbook.xml.rels']) as $r) {
            $wbRels[$r['a']['Id']] = $r['a'];
        }
        if (!isset($wbRels[$this->sheetRid])) {
            throw new RuntimeException('Could not resolve the master sheet inside the template.');
        }
        $this->sheetPath = $this->resolve('xl', $wbRels[$this->sheetRid]['Target']);
        if (!isset($this->files[$this->sheetPath])) {
            throw new RuntimeException('Master sheet XML not found in the template.');
        }
        $this->sheetXml = $this->files[$this->sheetPath];

        if (!str_contains($this->sheetXml, '<sheetData') || preg_match('~<[a-zA-Z0-9]+:c[ >]~', $this->sheetXml)) {
            throw new RuntimeException('Unsupported sheet XML structure. Please re-save the template from Excel.');
        }

        // relationships of the master sheet: only drawing / printerSettings / external hyperlinks
        $sdir = dirname($this->sheetPath);
        $relsPath = $sdir . '/_rels/' . basename($this->sheetPath) . '.rels';
        $this->sheetRels = [];
        $this->drawingPath = null;
        $this->printerPath = null;
        if (isset($this->files[$relsPath])) {
            foreach ($this->parseRels($this->files[$relsPath]) as $rel) {
                $a = $rel['a'];
                $type = substr(strrchr($a['Type'] ?? '', '/') ?: '', 1);
                if ($type === 'drawing') {
                    $this->drawingPath = $this->resolve($sdir, $a['Target']);
                } elseif ($type === 'printerSettings') {
                    $this->printerPath = $this->resolve($sdir, $a['Target']);
                } elseif (!($type === 'hyperlink' && ($a['TargetMode'] ?? '') === 'External')) {
                    throw new RuntimeException(
                        "The master sheet uses \"{$type}\", which this generator cannot duplicate safely. "
                        . 'Remove it from the template.'
                    );
                }
                $this->sheetRels[] = $rel;
            }
        }

        // the logo drawing may only reference images
        $this->drawingRelsPath = null;
        if ($this->drawingPath !== null) {
            if (!isset($this->files[$this->drawingPath])) {
                throw new RuntimeException('The logo drawing part is missing from the template.');
            }
            $ddir = dirname($this->drawingPath);
            $this->drawingRelsPath = $ddir . '/_rels/' . basename($this->drawingPath) . '.rels';
            foreach ($this->parseRels($this->files[$this->drawingRelsPath] ?? '') as $rel) {
                if (substr(strrchr($rel['a']['Type'] ?? '', '/') ?: '', 1) !== 'image') {
                    throw new RuntimeException('The logo drawing contains non-image parts (charts/shapes), which are not supported.');
                }
            }
        }

        // shared strings
        $this->strings = [];
        if (isset($this->files['xl/sharedStrings.xml'])) {
            preg_match_all('~<si>(.*?)</si>~s', $this->files['xl/sharedStrings.xml'], $si);
            foreach ($si[1] as $item) {
                preg_match_all('~<t[^>]*>(.*?)</t>~s', $item, $tt);
                $this->strings[] = $this->unescape(implode('', $tt[1]));
            }
        }

        // merged ranges
        $this->merges = [];
        preg_match_all('~<mergeCell ref="([A-Z]+\d+):([A-Z]+\d+)"~', $this->sheetXml, $mm, PREG_SET_ORDER);
        foreach ($mm as $m) {
            [$c1, $r1] = $this->splitRef($m[1]);
            [$c2, $r2] = $this->splitRef($m[2]);
            $this->merges[] = [$c1, $r1, $c2, $r2];
        }

        // all cells (text from shared OR inline strings)
        $this->cells = [];
        preg_match_all('~' . self::CELL_RE . '~s', $this->sheetXml, $cm, PREG_SET_ORDER);
        foreach ($cm as $m) {
            $this->cells[] = [(int) $m[2], $this->colToNum($m[1]), $this->cellText($m[3], $m[4] ?? '')];
        }
        usort($this->cells, fn ($a, $b) => [$a[0], $a[1]] <=> [$b[0], $b[1]]);
    }

    private function cellText(string $attrStr, string $inner): ?string
    {
        if ($inner === '') {
            return null;
        }
        if (str_contains($attrStr, 't="s"')) {
            if (preg_match('~<v>(\d+)</v>~', $inner, $v)) {
                return $this->strings[(int) $v[1]] ?? null;
            }
            return null;
        }
        if (str_contains($attrStr, 't="inlineStr"')) {
            preg_match_all('~<t[^>]*>(.*?)</t>~s', $inner, $tt);
            return $this->unescape(implode('', $tt[1]));
        }
        return null;
    }

    // ==================================================================
    // Label lookup (same rules as the Python helpers)
    // ==================================================================
    /** @return array{0:int,1:int}|null [col,row] */
    private function findExact(string $label): ?array
    {
        $want = mb_strtolower(trim($label));
        foreach ($this->cells as [$row, $col, $text]) {
            if ($text !== null && mb_strtolower(trim($text)) === $want) {
                return [$col, $row];
            }
        }
        return null;
    }

    /** @return array{0:int,1:int}|null */
    private function findKeywords(array $keywords): ?array
    {
        foreach ($this->cells as [$row, $col, $text]) {
            if ($text === null || trim($text) === '') {
                continue;
            }
            $low = mb_strtolower($text);
            $all = true;
            foreach ($keywords as $k) {
                if (!str_contains($low, mb_strtolower($k))) {
                    $all = false;
                    break;
                }
            }
            if ($all) {
                return [$col, $row];
            }
        }
        return null;
    }

    /** cell at (label column + offset), resolved to its merged-range anchor */
    private function target(array $found, int $offset): string
    {
        [$col, $row] = $found;
        $col += $offset;
        foreach ($this->merges as [$c1, $r1, $c2, $r2]) {
            if ($row >= $r1 && $row <= $r2 && $col >= $c1 && $col <= $c2) {
                return $this->numToCol($c1) . $r1;
            }
        }
        return $this->numToCol($col) . $row;
    }

    private function readCell(string $ref): ?string
    {
        if (!preg_match('~<c r="' . $ref . '"([^>]*?)(?:/>|>(.*?)</c>)~s', $this->sheetXml, $m)) {
            return null;
        }
        $inner = $m[2] ?? '';
        if ($inner === '') {
            return null;
        }
        $text = $this->cellText($m[1], $inner);
        if ($text !== null) {
            return $text;
        }
        return preg_match('~<v>(.*?)</v>~s', $inner, $v) ? $v[1] : null;
    }

    // ==================================================================
    // Cell patching
    // ==================================================================
    /** @param string|int|float $value */
    private function patchCell(string $xml, string $ref, $value): string
    {
        $pattern = '~<c r="' . $ref . '"((?:\s[^>]*?)?)(?:/>|>.*?</c>)~s';
        if (!preg_match($pattern, $xml, $m, PREG_OFFSET_CAPTURE)) {
            throw new RuntimeException("Cell {$ref} not found in the master sheet.");
        }
        $style = preg_match('~\ss="(\d+)"~', $m[1][0], $s) ? ' s="' . $s[1] . '"' : '';

        if (is_string($value)) {
            $new = '<c r="' . $ref . '"' . $style . ' t="inlineStr"><is><t xml:space="preserve">'
                . htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8') . '</t></is></c>';
        } else {
            $new = '<c r="' . $ref . '"' . $style . '><v>' . $this->num((float) $value) . '</v></c>';
        }

        return substr($xml, 0, $m[0][1]) . $new . substr($xml, $m[0][1] + strlen($m[0][0]));
    }

    private function num(float $v): string
    {
        $s = rtrim(rtrim(number_format($v, 12, '.', ''), '0'), '.');
        return ($s === '' || $s === '-0') ? '0' : $s;
    }

    // ==================================================================
    // Cached formula values: ONLY for provably-supported formulas
    // ==================================================================
    private function recalc(string $xml): string
    {
        $nums = [];
        $textCells = [];
        $formulas = [];
        $hasF = [];

        preg_match_all('~' . self::CELL_RE . '~s', $xml, $mm, PREG_SET_ORDER);
        foreach ($mm as $m) {
            $ref = $m[1] . $m[2];
            $inner = $m[4] ?? '';
            if ($inner === '') {
                continue;
            }
            if (str_contains($inner, '<f')) {
                $hasF[$ref] = true;
                if (preg_match('~^<f>(.*?)</f>~s', $inner, $f)) {
                    $expr = str_replace('$', '', $f[1]);
                    $toks = $this->tokenize($expr);
                    // supported only if the tokens account for the WHOLE expression
                    if (implode('', $toks) === preg_replace('~\s+~', '', $expr)) {
                        $formulas[$ref] = $toks;
                    }
                }
            } elseif (str_contains($m[3], 't=')) {
                $textCells[$ref] = true;
            } elseif (preg_match('~<v>(.*?)</v>~s', $inner, $v)) {
                if (is_numeric($v[1])) {
                    $nums[$ref] = (float) $v[1];
                } else {
                    $textCells[$ref] = true;
                }
            }
        }

        $memo = [];
        $get = null;
        $get = function (string $ref) use (&$get, &$memo, $nums, $textCells, $formulas, $hasF): float {
            if (array_key_exists($ref, $memo)) {
                if ($memo[$ref] === null) {
                    throw new DomainException('circular reference');
                }
                return $memo[$ref];
            }
            if (isset($textCells[$ref]) || (isset($hasF[$ref]) && !isset($formulas[$ref]))) {
                throw new DomainException('unsupported dependency');
            }
            if (isset($formulas[$ref])) {
                $memo[$ref] = null;
                $memo[$ref] = $this->evalTokens($formulas[$ref], $get);
            } else {
                $memo[$ref] = $nums[$ref] ?? 0.0; // empty cell counts as 0 in arithmetic
            }
            return $memo[$ref];
        };

        return preg_replace_callback(
            '~' . self::CELL_RE . '~s',
            function (array $m) use ($get, $formulas): string {
                $inner = $m[4] ?? '';
                if (!str_contains($inner, '<f')) {
                    return $m[0];
                }
                $ref = $m[1] . $m[2];
                try {
                    if (!isset($formulas[$ref])) {
                        throw new DomainException('unsupported formula');
                    }
                    $val = $this->num($get($ref));
                } catch (DomainException $e) {
                    // can't prove the result: drop the cached value, Excel recalculates on open
                    return '<c r="' . $ref . '"' . $m[3] . '>' . preg_replace('~<v>.*?</v>~s', '', $inner) . '</c>';
                }
                preg_match('~<f>.*?</f>~s', $inner, $f);
                $attrs = preg_replace('~\st="[^"]*"~', '', $m[3]);
                return '<c r="' . $ref . '"' . $attrs . '>' . $f[0] . '<v>' . $val . '</v></c>';
            },
            $xml
        );
    }

    /** @return string[] */
    private function tokenize(string $expr): array
    {
        preg_match_all('~[A-Z]+\d+|\d+\.?\d*|[()+\-*/]~', $expr, $m);
        return $m[0];
    }

    private function evalTokens(array $t, callable $get): float
    {
        $p = 0;
        $v = $this->parseExpr($t, $p, $get);
        if ($p !== count($t)) {
            throw new DomainException('unparsed tokens');
        }
        return $v;
    }

    private function parseExpr(array $t, int &$p, callable $get): float
    {
        $v = $this->parseTerm($t, $p, $get);
        while ($p < count($t) && ($t[$p] === '+' || $t[$p] === '-')) {
            $op = $t[$p++];
            $r = $this->parseTerm($t, $p, $get);
            $v = $op === '+' ? $v + $r : $v - $r;
        }
        return $v;
    }

    private function parseTerm(array $t, int &$p, callable $get): float
    {
        $v = $this->parseFactor($t, $p, $get);
        while ($p < count($t) && ($t[$p] === '*' || $t[$p] === '/')) {
            $op = $t[$p++];
            $r = $this->parseFactor($t, $p, $get);
            if ($op === '/') {
                if ($r == 0.0) {
                    throw new DomainException('division by zero'); // Excel shows #DIV/0!
                }
                $v /= $r;
            } else {
                $v *= $r;
            }
        }
        return $v;
    }

    private function parseFactor(array $t, int &$p, callable $get): float
    {
        if ($p >= count($t)) {
            throw new DomainException('unexpected end');
        }
        $tok = $t[$p++];
        if ($tok === '(') {
            $v = $this->parseExpr($t, $p, $get);
            if (($t[$p++] ?? '') !== ')') {
                throw new DomainException('missing )');
            }
            return $v;
        }
        if ($tok === '-') {
            return -$this->parseFactor($t, $p, $get);
        }
        if (preg_match('~^[A-Z]+\d+$~', $tok)) {
            return $get($tok);
        }
        return (float) $tok;
    }

    // ==================================================================
    // The maths (identical to generate_A_B_H_P_for_target in the Python tool)
    // ==================================================================
    /** @return array{A:float,B:float,H:float,P:float} */
    private function solve(float $targetCompPct, float $targetMoistPct, array $c): array
    {
        $r = self::RANGES;
        $L = ($targetCompPct / 100.0) * $c['M'] * (1 + $targetMoistPct / 100.0);

        for ($n = 0; $n < 500; $n++) {
            $H = $this->uniform($r['H'][0], $r['H'][1]);
            $C = (($H - $c['J']) / $L) * $c['F'] + $c['D'];
            $lo = max($r['A'][0], $C + $r['B'][0]);
            $hi = min($r['A'][1], $C + $r['B'][1]);
            if ($lo <= $hi) {
                $A = $this->uniform($lo, $hi);
                $B = $A - $C;
                if ($B >= $r['B'][0] && $B <= $r['B'][1]) {
                    return ['A' => round($A, 1), 'B' => round($B, 1), 'H' => round($H, 1), 'P' => round($targetMoistPct, 1)];
                }
            }
        }
        throw new RuntimeException("Cannot generate values for target compaction {$targetCompPct}%. Check the master constants.");
    }

    private function uniform(float $a, float $b): float
    {
        return $a + ($b - $a) * (mt_rand() / mt_getrandmax());
    }

    // ==================================================================
    // Dates / names
    // ==================================================================
    private function parseIso(string $s): DateTimeImmutable
    {
        $s = trim($s);
        if (!preg_match('~^(\d{4})-(\d{2})-(\d{2})$~', $s, $m)) {
            throw new RuntimeException("Invalid date \"{$s}\". Use YYYY-MM-DD.");
        }
        if (!checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
            throw new RuntimeException("Invalid date \"{$s}\" (that day does not exist).");
        }
        return new DateTimeImmutable($s, new DateTimeZone('UTC'));
    }

    private function parseBaseDate(?string $raw): DateTimeImmutable
    {
        $utc = new DateTimeZone('UTC');
        $fallback = new DateTimeImmutable('2026-03-26', $utc);
        if ($raw === null || trim($raw) === '') {
            return $fallback;
        }
        $raw = trim($raw);
        if (is_numeric($raw)) { // Excel serial date
            return (new DateTimeImmutable('1899-12-30', $utc))->modify('+' . (int) floor((float) $raw) . ' days');
        }
        foreach (['d M Y', 'Y-m-d', 'd-M-Y', 'd/m/Y'] as $fmt) {
            $d = DateTimeImmutable::createFromFormat('!' . $fmt, $raw, $utc);
            if ($d) {
                return $d;
            }
        }
        return $fallback;
    }

    private function nextDay(DateTimeImmutable $d, bool $skipFridays): DateTimeImmutable
    {
        $d = $d->modify('+1 day');
        while ($skipFridays && (int) $d->format('N') === 5) { // 5 = Friday
            $d = $d->modify('+1 day');
        }
        return $d;
    }

    private function ordinal(int $n): string
    {
        return match ($n) { 1 => '1ST', 2 => '2ND', 3 => '3RD', default => $n . 'TH' };
    }

    // ==================================================================
    // Building the output package
    // ==================================================================
    /** @param array<int,array{0:string,1:string}> $sheets */
    private function assemble(array $sheets, string $outPath): void
    {
        $f  = $this->files;
        $ct = $f['[Content_Types].xml'];
        $sdir = dirname($this->sheetPath);

        // calcChain lists cells of the old single sheet: drop it (Excel rebuilds it)
        unset($f['xl/calcChain.xml']);
        $ct = preg_replace('~<Override\b[^>]*PartName="/xl/calcChain\.xml"[^>]*/>~', '', $ct);

        $wb     = $f['xl/workbook.xml'];
        $wbRels = preg_replace('~<Relationship\b[^>]*calcChain[^>]*/>~', '', $f['xl/_rels/workbook.xml.rels']);

        preg_match_all('~sheetId="(\d+)"~', $wb, $ids);
        $maxSheetId = max(array_map('intval', $ids[1]));
        $newSheetTags = [];
        $newRels = '';

        foreach ($sheets as $idx => [$name, $xml]) {
            if ($idx === 0) {
                // layer 1 = the master part itself
                $f[$this->sheetPath] = $xml;
                $newSheetTags[] = preg_replace('~name="[^"]*"~', 'name="' . $name . '"', $this->sheetTag, 1);
                continue;
            }

            $sp = $this->uniquePath($f, $sdir, 'sheet', 'xml');
            $f[$sp] = $xml;
            $ct = str_replace('</Types>', '<Override PartName="/' . $sp . '" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>', $ct);

            if (!empty($this->sheetRels)) {
                $relsXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="' . self::REL_NS . '">';
                foreach ($this->sheetRels as $rel) {
                    $tag = $rel['tag'];
                    $type = substr(strrchr($rel['a']['Type'] ?? '', '/') ?: '', 1);

                    if ($type === 'drawing' && $this->drawingPath !== null) {
                        $ddir = dirname($this->drawingPath);
                        $dp = $this->uniquePath($f, $ddir, 'drawing', 'xml');
                        $this->copyPart($f, $ct, $this->drawingPath, $dp);
                        if ($this->drawingRelsPath !== null && isset($f[$this->drawingRelsPath])) {
                            $f[$ddir . '/_rels/' . basename($dp) . '.rels'] = $f[$this->drawingRelsPath];
                        }
                        $tag = preg_replace('~Target="[^"]*"~', 'Target="' . $this->relativeTarget($sdir, $dp) . '"', $tag, 1);
                    } elseif ($type === 'printerSettings' && $this->printerPath !== null) {
                        $pdir = dirname($this->printerPath);
                        $ext = pathinfo($this->printerPath, PATHINFO_EXTENSION);
                        $pp = $this->uniquePath($f, $pdir, 'printerSettings', $ext);
                        $this->copyPart($f, $ct, $this->printerPath, $pp);
                        $tag = preg_replace('~Target="[^"]*"~', 'Target="' . $this->relativeTarget($sdir, $pp) . '"', $tag, 1);
                    }
                    $relsXml .= $tag;
                }
                $relsXml .= '</Relationships>';
                $f[$sdir . '/_rels/' . basename($sp) . '.rels'] = $relsXml;
            }

            $rid = 'rIdLayer' . ($idx + 1);
            $maxSheetId++;
            $newSheetTags[] = '<sheet name="' . $name . '" sheetId="' . $maxSheetId . '" r:id="' . $rid . '"/>';
            $newRels .= '<Relationship Id="' . $rid . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="'
                . $this->relativeTarget('xl', $sp) . '"/>';
        }

        $wb = str_replace($this->sheetTag, implode('', $newSheetTags), $wb);

        // defined names: replicate the master's sheet-scoped names (Print_Area, Print_Titles...) per layer
        if (preg_match('~<definedNames>(.*?)</definedNames>~s', $wb, $dn)) {
            preg_match_all('~<definedName\b([^>]*)>(.*?)</definedName>~s', $dn[1], $names, PREG_SET_ORDER);
            $outNames = '';
            foreach ($sheets as $idx => [$name, $_]) {
                foreach ($names as $n) {
                    if (!str_contains($n[1], 'localSheetId=')) {
                        continue;
                    }
                    $a = preg_replace('~localSheetId="\d+"~', 'localSheetId="' . $idx . '"', $n[1]);
                    $quoted = "'" . $this->origName . "'!";
                    $text = str_contains($n[2], $quoted)
                        ? str_replace($quoted, "'" . $name . "'!", $n[2])
                        : str_replace($this->origName . '!', "'" . $name . "'!", $n[2]);
                    $outNames .= '<definedName' . $a . '>' . $text . '</definedName>';
                }
            }
            foreach ($names as $n) { // workbook-wide names stay as they are
                if (!str_contains($n[1], 'localSheetId=')) {
                    $outNames .= '<definedName' . $n[1] . '>' . $n[2] . '</definedName>';
                }
            }
            $wb = preg_replace_callback('~<definedNames>.*?</definedNames>~s', fn () => '<definedNames>' . $outNames . '</definedNames>', $wb, 1);
        }

        // calcPr: keep the template's own settings, just force a full recalculation on open
        if (preg_match('~<calcPr\b([^>]*?)/>~', $wb, $cp)) {
            $a = preg_replace('~\sfullCalcOnLoad="[^"]*"~', '', $cp[1]);
            $wb = str_replace($cp[0], '<calcPr' . $a . ' fullCalcOnLoad="1"/>', $wb);
        } else {
            $wb = str_replace('</workbook>', '<calcPr fullCalcOnLoad="1"/></workbook>', $wb);
        }

        $f['xl/workbook.xml'] = $wb;
        $f['xl/_rels/workbook.xml.rels'] = str_replace('</Relationships>', $newRels . '</Relationships>', $wbRels);

        // docProps/app.xml: keep the metadata, drop only the sheet-title lists that would now be wrong
        if (isset($f['docProps/app.xml'])) {
            $app = preg_replace('~<HeadingPairs>.*?</HeadingPairs>~s', '', $f['docProps/app.xml']);
            $f['docProps/app.xml'] = preg_replace('~<TitlesOfParts>.*?</TitlesOfParts>~s', '', $app);
        }

        // write the zip ([Content_Types].xml first, as Office expects)
        $zip = new ZipArchive();
        if ($zip->open($outPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Could not create the output file.');
        }
        $zip->addFromString('[Content_Types].xml', $ct);
        unset($f['[Content_Types].xml']);
        foreach ($f as $partName => $data) {
            $zip->addFromString($partName, $data);
        }
        if (!$zip->close()) {
            throw new RuntimeException('Could not write the output file.');
        }
    }

    private function uniquePath(array $files, string $dir, string $prefix, string $ext): string
    {
        $n = 2;
        while (isset($files["{$dir}/{$prefix}{$n}.{$ext}"])) {
            $n++;
        }
        return "{$dir}/{$prefix}{$n}.{$ext}";
    }

    /** copy a package part and, if the source has a content-type Override, give the copy the same one */
    private function copyPart(array &$f, string &$ct, string $src, string $dst): void
    {
        $f[$dst] = $f[$src];
        if (preg_match('~<Override\b[^>]*PartName="' . preg_quote('/' . $src, '~') . '"[^>]*/>~', $ct, $m)) {
            $copy = str_replace('/' . $src, '/' . $dst, $m[0]);
            $ct = str_replace('</Types>', $copy . '</Types>', $ct);
        }
    }

    // ==================================================================
    // Small helpers
    // ==================================================================
    /** @return array<string,string> */
    private function attrs(string $tag): array
    {
        preg_match_all('~([\w:.-]+)="([^"]*)"~', $tag, $m, PREG_SET_ORDER);
        $out = [];
        foreach ($m as $a) {
            $out[$a[1]] = $a[2];
        }
        return $out;
    }

    /** @return array<int,array{a:array<string,string>,tag:string}> */
    private function parseRels(string $xml): array
    {
        preg_match_all('~<Relationship\b[^>]*/?>~', $xml, $m);
        $out = [];
        foreach ($m[0] as $tag) {
            $a = $this->attrs($tag);
            if (isset($a['Id'], $a['Target'])) {
                $out[] = ['a' => $a, 'tag' => $tag];
            }
        }
        return $out;
    }

    private function unescape(string $s): string
    {
        return html_entity_decode($s, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    private function resolve(string $baseDir, string $target): string
    {
        if (str_starts_with($target, '/')) {
            return ltrim($target, '/');
        }
        $parts = [];
        foreach (explode('/', $baseDir . '/' . $target) as $seg) {
            if ($seg === '' || $seg === '.') {
                continue;
            }
            if ($seg === '..') {
                array_pop($parts);
                continue;
            }
            $parts[] = $seg;
        }
        return implode('/', $parts);
    }

    /** relative path from directory $fromDir to the part $toFile (both inside the package) */
    private function relativeTarget(string $fromDir, string $toFile): string
    {
        $from = array_values(array_filter(explode('/', $fromDir), 'strlen'));
        $to   = array_values(array_filter(explode('/', $toFile), 'strlen'));
        while ($from && $to && $from[0] === $to[0] && count($to) > 1) {
            array_shift($from);
            array_shift($to);
        }
        return str_repeat('../', count($from)) . implode('/', $to);
    }

    private function colToNum(string $s): int
    {
        $n = 0;
        foreach (str_split($s) as $ch) {
            $n = $n * 26 + (ord($ch) - 64);
        }
        return $n;
    }

    private function numToCol(int $n): string
    {
        $s = '';
        while ($n > 0) {
            $r = ($n - 1) % 26;
            $s = chr(65 + $r) . $s;
            $n = intdiv($n - 1, 26);
        }
        return $s;
    }

    /** @return array{0:int,1:int} [col,row] */
    private function splitRef(string $ref): array
    {
        preg_match('~^([A-Z]+)(\d+)$~', $ref, $m);
        return [$this->colToNum($m[1]), (int) $m[2]];
    }
}
