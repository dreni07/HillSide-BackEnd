<?php

namespace App\Services\Ai;

use App\Enums\ProductExtractionSourceType;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use JsonException;
use RuntimeException;
use Throwable;

/**
 * Thirr API të përputhshëm me OpenAI Chat Completions (p.sh. Groq) për të strukturuar
 * tekstin e nxjerrë nga PDF / fletëllogaritje / OCR në lista produktesh.
 */
class GroqProductStructuringService
{
    public function __construct(
        private readonly ?string $apiKey,
        private readonly string $apiBaseUrl,
        private readonly string $model,
        private readonly int $timeoutSeconds,
        private readonly int $maxInputChars,
    ) {}

    public static function fromConfig(): self
    {
        return new self(
            apiKey: config('ai.api_key') ? (string) config('ai.api_key') : null,
            apiBaseUrl: (string) config('ai.api_url', 'https://api.groq.com/openai/v1'),
            model: (string) config('ai.model', 'llama-3.3-70b-versatile'),
            timeoutSeconds: (int) config('ai.timeout', 120),
            maxInputChars: (int) config('ai.max_input_chars', 48000),
        );
    }

    public function isEnabled(): bool
    {
        return $this->apiKey !== null && $this->apiKey !== '';
    }

    /**
     * Dërgon përmbajtjen e nxjerrë te LLM dhe kthen rreshta të normalizuara për `makeForIngestion`.
     *
     * @return list<array{
     *   title: string|null,
     *   description: string|null,
     *   price: float|null,
     *   sku: string|null,
     *   category: string|null,
     *   extracted_text: string
     * }>
     *
     * @throws RuntimeException kur përgjigjja nuk është e vlefshme
     */
    public function structureExtractedContent(string $rawText, ProductExtractionSourceType $sourceType): array
    {
        if (! $this->isEnabled()) {
            throw new RuntimeException('AI API key is not configured.');
        }

        $trimmed = trim($rawText);
        if ($trimmed === '') {
            return [];
        }

        $payload = Str::limit($trimmed, $this->maxInputChars, "\n...[truncated]");
        $sourceLabel = $sourceType->value;

        $systemPrompt = <<<'PROMPT'
You extract product catalog data from raw text that may come from a PDF, spreadsheet export, or OCR of an image.

Rules:
- Output ONLY valid JSON with a single top-level key "products" whose value is an array.
- Each array element is one product with these keys (use null when unknown): title (string), description (string), price (number, unit price, no currency symbols), sku (string), category (string), extracted_text (string: short verbatim or summary from the source for this product).
- If the source lists multiple products, return multiple objects. If it is one product, return an array of one object.
- If nothing looks like a product, return {"products": []}.
- Do not include markdown fences or commentary outside the JSON.
PROMPT;

        $userMessage = "Source type: {$sourceLabel}\n\nExtracted raw text:\n\n".$payload;

        $url = $this->apiBaseUrl.'/chat/completions';

        try {
            $response = Http::withToken($this->apiKey)
                ->acceptJson()
                ->timeout($this->timeoutSeconds)
                ->post($url, [
                    'model' => $this->model,
                    'temperature' => 0.1,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userMessage],
                    ],
                    'response_format' => ['type' => 'json_object'],
                ]);
        } catch (Throwable $e) {
            Log::warning('Groq product structuring HTTP failed', ['exception' => $e->getMessage()]);

            throw new RuntimeException('LLM request failed: '.$e->getMessage(), 0, $e);
        }

        if (! $response->successful()) {
            Log::warning('Groq product structuring API error', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw new RuntimeException('LLM API error: HTTP '.$response->status());
        }

        $content = data_get($response->json(), 'choices.0.message.content');
        if (! is_string($content) || $content === '') {
            throw new RuntimeException('LLM returned empty content.');
        }

        $decoded = $this->decodeJsonObject($content);
        $products = $decoded['products'] ?? null;
        if (! is_array($products)) {
            throw new RuntimeException('LLM JSON missing "products" array.');
        }

        $out = [];
        foreach ($products as $item) {
            if (! is_array($item)) {
                continue;
            }
            $out[] = $this->normalizeProductRow($item);
        }

        return $out;
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonObject(string $content): array
    {
        $clean = trim($content);
        if (str_starts_with($clean, '```')) {
            $clean = preg_replace('/^```(?:json)?\s*/i', '', $clean) ?? $clean;
            $clean = preg_replace('/\s*```$/', '', $clean) ?? $clean;
            $clean = trim($clean);
        }

        try {
            $data = json_decode($clean, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            throw new RuntimeException('LLM returned invalid JSON: '.$e->getMessage(), 0, $e);
        }

        return is_array($data) ? $data : [];
    }

    /**
     * @param  array<string, mixed>  $p
     * @return array{
     *   title: string|null,
     *   description: string|null,
     *   price: float|null,
     *   sku: string|null,
     *   category: string|null,
     *   extracted_text: string
     * }
     */
    private function normalizeProductRow(array $p): array
    {
        $title = isset($p['title']) ? trim((string) $p['title']) : '';
        $description = isset($p['description']) ? trim((string) $p['description']) : '';
        $sku = isset($p['sku']) ? trim((string) $p['sku']) : '';
        $category = isset($p['category']) ? trim((string) $p['category']) : '';
        $extracted = isset($p['extracted_text']) ? trim((string) $p['extracted_text']) : '';
        if ($extracted === '' && ($title !== '' || $description !== '')) {
            $extracted = trim($title."\n".$description);
        }

        $price = null;
        if (array_key_exists('price', $p) && $p['price'] !== null && $p['price'] !== '') {
            if (is_numeric($p['price'])) {
                $price = (float) $p['price'];
            }
        }

        return [
            'title' => $title !== '' ? Str::limit($title, 255, '') : null,
            'description' => $description !== '' ? Str::limit($description, 5000, '') : null,
            'price' => $price,
            'sku' => $sku !== '' ? Str::limit($sku, 128, '') : null,
            'category' => $category !== '' ? Str::limit($category, 255, '') : null,
            'extracted_text' => Str::limit($extracted !== '' ? $extracted : ($title ?: ''), 65000, '...'),
        ];
    }
}
