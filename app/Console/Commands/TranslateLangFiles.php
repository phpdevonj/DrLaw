<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use App\Models\LanguageList;
use App\Services\LangTranslationService;

class TranslateLangFiles extends Command
{
    /**
     * Full translation of a language (heavy — translates every key). Use this for
     * a one-off complete translation. Day-to-day gap-filling is done incrementally
     * by lang:translate-missing.
     *
     * php artisan lang:translate                → translate all active languages, skip existing files
     * php artisan lang:translate --lang=ar      → only Arabic
     * php artisan lang:translate --force        → re-translate & overwrite existing files
     * php artisan lang:translate --files=message,validation
     */
    protected $signature = 'lang:translate
        {--lang=* : Specific language codes to translate (defaults to all active languages)}
        {--files=* : Specific lang files without extension (defaults to every file in resources/lang/en)}
        {--force : Re-translate and overwrite files that already exist}';

    protected $description = 'Google-translate the full English lang files into every active app language';

    public function handle(LangTranslationService $translator)
    {
        $enDir = resource_path('lang/en');
        if (! File::isDirectory($enDir)) {
            $this->error('Source locale resources/lang/en not found.');
            return self::FAILURE;
        }

        $onlyFiles = $this->option('files');
        $files = collect(File::files($enDir))
            ->filter(fn ($f) => $f->getExtension() === 'php')
            ->map(fn ($f) => $f->getFilenameWithoutExtension())
            ->when(! empty($onlyFiles), fn ($c) => $c->filter(fn ($n) => in_array($n, $onlyFiles)))
            ->values();

        if ($files->isEmpty()) {
            $this->error('No lang files matched.');
            return self::FAILURE;
        }

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

        foreach ($languages as $code) {
            if (File::isDirectory(resource_path("lang/{$code}")) && ! $this->option('force')) {
                $this->warn("Skipping {$code} (already exists — use --force to re-translate).");
                continue;
            }

            $this->info("Translating → {$code}");
            createLangFile($code);

            foreach ($files as $name) {
                $source = include $enDir . "/{$name}.php";
                if (! is_array($source)) {
                    continue;
                }
                $this->line("  {$name}.php");
                $translated = $translator->translateArray($source, $code);
                $translator->writeLangFile(resource_path("lang/{$code}/{$name}.php"), $translated);
            }
        }

        $this->info('Done.');
        return self::SUCCESS;
    }
}
