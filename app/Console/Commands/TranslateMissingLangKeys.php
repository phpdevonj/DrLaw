<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use App\Models\LanguageList;
use App\Services\LangTranslationService;

class TranslateMissingLangKeys extends Command
{
    /**
     * Incrementally translate only the keys that are still missing or untranslated
     * in each active language file (compared to English). Light by design — meant to
     * run on a schedule and/or manually. Keys already translated (value differs from
     * English) and any values edited manually are left untouched.
     *
     * php artisan lang:translate-missing                 → all active languages, all files
     * php artisan lang:translate-missing --lang=fr       → only French
     * php artisan lang:translate-missing --files=message → only message.php
     * php artisan lang:translate-missing --limit=200     → at most 200 keys this run (spread load)
     */
    protected $signature = 'lang:translate-missing
        {--lang=* : Specific language codes (defaults to all active languages)}
        {--files=* : Specific lang files without extension (defaults to all)}
        {--limit=0 : Max keys to translate this run across all languages (0 = no limit)}';

    protected $description = 'Google-translate only the missing/untranslated keys in each active language file';

    /** @var int Remaining translation budget for this run (0 = unlimited). */
    protected $budget = 0;

    public function handle(LangTranslationService $translator)
    {
        $enDir = resource_path('lang/en');
        if (! File::isDirectory($enDir)) {
            $this->error('Source locale resources/lang/en not found.');
            return self::FAILURE;
        }

        $this->budget = (int) $this->option('limit');

        $onlyFiles = $this->option('files');
        $files = collect(File::files($enDir))
            ->filter(fn ($f) => $f->getExtension() === 'php')
            ->map(fn ($f) => $f->getFilenameWithoutExtension())
            ->when(! empty($onlyFiles), fn ($c) => $c->filter(fn ($n) => in_array($n, $onlyFiles)))
            ->values();

        $requested = $this->option('lang');
        $languages = LanguageList::where('status', 1)
            ->where('language_code', '!=', 'en')
            ->when(! empty($requested), fn ($q) => $q->whereIn('language_code', $requested))
            ->pluck('language_code')
            ->filter()
            ->unique()
            ->values();

        if ($languages->isEmpty()) {
            $this->warn('No active target languages found in LanguageList.');
            return self::SUCCESS;
        }

        $grandTotal = 0;

        foreach ($languages as $code) {
            // Ensure the folder/files exist (as an English copy) so there is
            // something to fill in for a brand-new language.
            createLangFile($code);

            $langTotal = 0;
            foreach ($files as $name) {
                if ($this->budgetExhausted()) {
                    break 2;
                }

                $en = include $enDir . "/{$name}.php";
                if (! is_array($en)) {
                    continue;
                }

                $targetPath = resource_path("lang/{$code}/{$name}.php");
                $existing = File::exists($targetPath) ? (include $targetPath) : [];
                if (! is_array($existing)) {
                    $existing = [];
                }

                $count = 0;
                $merged = $this->fillMissing($en, $existing, $code, $translator, $count);

                if ($count > 0) {
                    $translator->writeLangFile($targetPath, $merged);
                    $langTotal += $count;
                    $this->line("  {$code}/{$name}.php: {$count} key(s) translated");
                }
            }

            if ($langTotal > 0) {
                $this->info("{$code}: {$langTotal} key(s) translated");
            }
            $grandTotal += $langTotal;
        }

        $this->info("Done. {$grandTotal} key(s) translated" . ($this->budget > 0 ? " (limit {$this->budget})." : '.'));
        return self::SUCCESS;
    }

    /**
     * Walk the English tree; translate only leaves that are absent or still equal to
     * English (untranslated) in the existing file. Preserves nesting, previously
     * translated values, and any manual edits.
     */
    protected function fillMissing(array $en, array $existing, string $code, LangTranslationService $translator, int &$count): array
    {
        $result = $existing; // keep anything already present (translations, extra keys)

        foreach ($en as $key => $enVal) {
            if (is_array($enVal)) {
                $childExisting = (isset($existing[$key]) && is_array($existing[$key])) ? $existing[$key] : [];
                $result[$key] = $this->fillMissing($enVal, $childExisting, $code, $translator, $count);
                continue;
            }

            if (! is_string($enVal)) {
                $result[$key] = $existing[$key] ?? $enVal;
                continue;
            }

            $alreadyTranslated = array_key_exists($key, $existing)
                && is_string($existing[$key])
                && trim($existing[$key]) !== ''
                && $existing[$key] !== $enVal;

            if ($alreadyTranslated) {
                $result[$key] = $existing[$key];
                continue;
            }

            // Needs translation (missing, empty, or still English).
            if ($this->budgetExhausted()) {
                $result[$key] = $existing[$key] ?? $enVal; // leave English until a later run
                continue;
            }

            $translated = $translator->translate($enVal, $code);
            $result[$key] = $translated;

            if ($translated !== $enVal) {
                $count++;
                if ($this->budget > 0) {
                    $this->budget--;
                }
            }
        }

        return $result;
    }

    protected function budgetExhausted(): bool
    {
        return $this->option('limit') > 0 && $this->budget <= 0;
    }
}
