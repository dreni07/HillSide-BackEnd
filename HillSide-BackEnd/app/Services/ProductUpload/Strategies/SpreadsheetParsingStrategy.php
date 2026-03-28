<?php

namespace App\Services\ProductUpload\Strategies;

use App\Contracts\ProductUpload\DocumentParsingStrategyInterface;
use App\Services\ProductUpload\Mapping\ExtractedProductRowMapper;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\ODS\Reader as OdsReader;
use OpenSpout\Reader\ReaderInterface;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;

/**
 * Strategy: lexon rreshtat nga CSV / XLSX / ODS (OpenSpout).
 */
class SpreadsheetParsingStrategy implements DocumentParsingStrategyInterface
{
    public function parse(string $absolutePath): array
    {
        $reader = $this->createReaderForPath($absolutePath);
        $reader->open($absolutePath);

        $rows = [];
        $headers = null;
        $columnMap = null;

        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                $values = $this->rowToStringValues($row);
                if ($this->isRowEmpty($values)) {
                    continue;
                }

                if ($headers === null) {
                    $headers = $values;
                    $columnMap = $this->buildColumnMap($headers);

                    continue;
                }

                $rows[] = ExtractedProductRowMapper::fromSpreadsheetRow($values, $columnMap);
            }

            break;
        }

        $reader->close();

        if ($rows === []) {
            return [array_merge(ExtractedProductRowMapper::emptyRow(), ['extracted_text' => ''])];
        }

        return $rows;
    }

    private function createReaderForPath(string $absolutePath): ReaderInterface
    {
        $ext = strtolower(pathinfo($absolutePath, PATHINFO_EXTENSION));

        return match ($ext) {
            'csv', 'txt' => new CsvReader,
            'xlsx' => new XlsxReader,
            'ods' => new OdsReader,
            default => throw new \InvalidArgumentException(
                "Spreadsheet extension not supported: {$ext}. Use csv, xlsx, or ods."
            ),
        };
    }

    /**
     * @return list<string>
     */
    private function rowToStringValues(Row $row): array
    {
        $out = [];
        foreach ($row->cells as $cell) {
            $out[] = $this->cellToString($cell);
        }

        return $out;
    }

    private function cellToString(Cell $cell): string
    {
        $v = $cell->getValue();
        if ($v === null) {
            return '';
        }
        if (is_string($v)) {
            return $v;
        }
        if (is_int($v) || is_float($v)) {
            return (string) $v;
        }

        return '';
    }

    /**
     * @param  list<string>  $values
     */
    private function isRowEmpty(array $values): bool
    {
        foreach ($values as $v) {
            if (trim($v) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  list<string>  $headers
     * @return array<string, int>
     */
    private function buildColumnMap(array $headers): array
    {
        $map = [];
        foreach ($headers as $i => $label) {
            $key = $this->normalizeHeaderKey($label);
            if ($key !== '' && ! isset($map[$key])) {
                $map[$key] = $i;
            }
        }

        return $map;
    }

    private function normalizeHeaderKey(string $label): string
    {
        $s = strtolower(trim($label));
        $s = preg_replace('/[^a-z0-9]+/i', '_', $s) ?? '';
        $s = trim($s, '_');

        return match ($s) {
            'product_name', 'emri', 'emertimi' => 'name',
            'item', 'artikulli' => 'name',
            default => $s,
        };
    }
}
