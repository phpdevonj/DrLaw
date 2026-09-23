<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

/**
 * Shared Google-translate engine for the lang files. Used by both the full
 * translation command (lang:translate) and the incremental missing-keys cron
 * (lang:translate-missing). Needs no API key — it uses the same free endpoint
 * the admin panel's translate widget uses.
 */
class LangTranslationService
{
    /** @var string */
    protected $endpoint = 'https://translate.googleapis.com/translate_a/single';

    /** @var array In-process cache so identical source strings hit the network once. */
    protected $cache = [];

    /** @var int Delay between calls to stay gentle with the unofficial endpoint. */
    protected $sleepMicroseconds = 300000;

    /**
     * Translate one leaf string into $code, preserving :name / {token} placeholders.
     * Returns the original English text on network failure or if a placeholder is
     * dropped, so we never write a broken value.
     */
    public function translate(string $text, string $code): string
    {
        if (trim($text) === '') {
            return $text;
        }

        $cacheKey = $code . '|' . $text;
        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        // Placeholder tokens we must preserve: :name style and {..} / {$..} style.
        preg_match_all('/:\w+|\{\$?\w+\}/', $text, $matches);
        $tokens = $matches[0];

        $protected = $text;
        foreach ($tokens as $token) {
            $protected = str_replace($token, '<span class="notranslate">' . $token . '</span>', $protected);
        }

        $translated = $this->requestTranslation($protected, $code);

        if ($translated === null) {
            return $this->cache[$cacheKey] = $text;
        }

        $translated = preg_replace('/<span class="notranslate">\s*(.*?)\s*<\/span>/u', '$1', $translated);
        $translated = html_entity_decode($translated, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Safety net: every original placeholder must survive, else keep English.
        foreach ($tokens as $token) {
            if (! str_contains($translated, $token)) {
                return $this->cache[$cacheKey] = $text;
            }
        }

        usleep($this->sleepMicroseconds);

        return $this->cache[$cacheKey] = $translated;
    }

    /**
     * Recursively translate every leaf string, preserving keys and nesting.
     */
    public function translateArray(array $data, string $code): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $result[$key] = $this->translateArray($value, $code);
            } elseif (is_string($value)) {
                $result[$key] = $this->translate($value, $code);
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    /**
     * Write the array in the same on-disk format the admin language editor
     * produces (see LanguageController::saveFileContent), so both stay compatible.
     */
    public function writeLangFile(string $path, array $data): void
    {
        $fp = fopen($path, 'w');
        fwrite($fp, var_export($data, true));
        fclose($fp);
        File::prepend($path, '<?php return  ');
        File::append($path, ';');
    }

    /**
     * Call the free translate endpoint with basic retry/backoff. Returns null on failure.
     */
    protected function requestTranslation(string $text, string $code): ?string
    {
        for ($attempt = 1; $attempt <= 3; $attempt++) {
            try {
                $response = Http::timeout(20)->get($this->endpoint, [
                    'client' => 'gtx',
                    'sl'     => 'en',
                    'tl'     => $code,
                    'dt'     => 't',
                    'format' => 'html',
                    'q'      => $text,
                ]);

                if ($response->successful()) {
                    $json = $response->json();
                    // Response shape: [ [ [ "translated", "source", ... ], ... ], ... ]
                    if (is_array($json) && isset($json[0]) && is_array($json[0])) {
                        $out = '';
                        foreach ($json[0] as $segment) {
                            $out .= $segment[0] ?? '';
                        }
                        return $out !== '' ? $out : null;
                    }
                    return null;
                }

                if (in_array($response->status(), [429, 500, 502, 503])) {
                    sleep($attempt * 2);
                    continue;
                }

                return null;
            } catch (\Throwable $e) {
                sleep($attempt);
            }
        }

        return null;
    }
}
