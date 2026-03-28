<?php

namespace App\Services\ProductUpload\Mapping;

/**
 * Hekuristikë për të nxjerrë fusha strukturore nga tekst i lirë (PDF/OCR).
 */
final class ExtractedProductRowMapper
{
    /**
     * @return array{
     *   title: string|null,
     *   description: string|null,
     *   price: float|null,
     *   sku: string|null,
     *   category: string|null,
     *   extracted_text: string
     * }
     */
    public static function fromFreeText(string $text): array
    {
        $trimmed = trim($text);
        if ($trimmed === '') {
            return self::emptyRow();
        }

        $lines = preg_split('/\R+/u', $trimmed) ?: [];
        $lines = array_values(array_filter(array_map('trim', $lines), fn (string $l) => $l !== ''));

        $title = $lines[0] ?? null;
        $rest = array_slice($lines, 1);
        $description = $rest !== [] ? implode("\n", $rest) : null;

        return [
            'title' => $title !== null ? self::limit($title, 255) : null,
            'description' => $description !== null ? self::limit($description, 5000) : null,
            'price' => self::guessPrice($trimmed),
            'sku' => self::guessSku($trimmed),
            'category' => null,
            'extracted_text' => $trimmed,
        ];
    }

    /**
     * @param  array<int, string>  $values
     * @param  array<string, int>  $columnIndexByKey
     * @return array{
     *   title: string|null,
     *   description: string|null,
     *   price: float|null,
     *   sku: string|null,
     *   category: string|null,
     *   extracted_text: string
     * }
     */
    public static function fromSpreadsheetRow(array $values, array $columnIndexByKey): array
    {
        $get = function (string $key) use ($values, $columnIndexByKey): ?string {
            if (! isset($columnIndexByKey[$key])) {
                return null;
            }
            $i = $columnIndexByKey[$key];

            return isset($values[$i]) ? trim((string) $values[$i]) : null;
        };

        $title = $get('name') ?? $get('title') ?? $get('product');
        $description = $get('description') ?? $get('desc') ?? $get('details');
        $sku = $get('sku') ?? $get('code');
        $category = $get('category') ?? $get('type');
        $priceRaw = $get('price') ?? $get('cost');

        $price = null;
        if ($priceRaw !== null && $priceRaw !== '') {
            $normalized = str_replace(',', '.', preg_replace('/[^\d.,-]/', '', $priceRaw) ?? '');
            if (is_numeric($normalized)) {
                $price = (float) $normalized;
            }
        }

        $extracted = implode(' | ', array_filter($values, fn ($v) => trim((string) $v) !== ''));

        return [
            'title' => $title !== null && $title !== '' ? self::limit($title, 255) : null,
            'description' => $description !== null && $description !== '' ? self::limit($description, 5000) : null,
            'price' => $price,
            'sku' => $sku !== null && $sku !== '' ? self::limit($sku, 128) : null,
            'category' => $category !== null && $category !== '' ? self::limit($category, 255) : null,
            'extracted_text' => $extracted,
        ];
    }

    /**
     * @return array{
     *   title: null,
     *   description: null,
     *   price: null,
     *   sku: null,
     *   category: null,
     *   extracted_text: string
     * }
     */
    public static function emptyRow(): array
    {
        return [
            'title' => null,
            'description' => null,
            'price' => null,
            'sku' => null,
            'category' => null,
            'extracted_text' => '',
        ];
    }

    private static function guessPrice(string $text): ?float
    {
        if (preg_match('/(?:€|\$|EUR|USD|lek|ALL)\s*([\d]{1,6}(?:[.,]\d{1,2})?)/iu', $text, $m)) {
            $n = str_replace(',', '.', $m[1]);

            return is_numeric($n) ? (float) $n : null;
        }
        if (preg_match('/\b(\d{1,6}[.,]\d{2})\b/', $text, $m)) {
            $n = str_replace(',', '.', $m[1]);

            return (float) $n;
        }

        return null;
    }

    private static function guessSku(string $text): ?string
    {
        if (preg_match('/\b(?:SKU|Art\.?|Kodi)\s*[:#]?\s*([A-Z0-9][A-Z0-9-]{2,32})\b/iu', $text, $m)) {
            return $m[1];
        }

        return null;
    }

    private static function limit(string $value, int $max): string
    {
        if (strlen($value) <= $max) {
            return $value;
        }

        return substr($value, 0, $max);
    }
}
