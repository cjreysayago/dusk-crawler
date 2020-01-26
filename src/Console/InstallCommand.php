<?php

namespace Laravie\DuskCrawler\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Orchestra\Canvas\Core\CommandsProvider;
use Orchestra\Canvas\Core\Contracts\GeneratesCodeListener;
use Orchestra\Canvas\Core\Presets\Preset;

class InstallCommand extends Command
{
    use CommandsProvider;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dusk-crawler:install
                {--proxy= : The proxy to download the binary through (example: "tcp://127.0.0.1:9000")}
                {--ssl-no-verify : Bypass SSL certificate verification when installing through a proxy}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Install Dusk Crawler into the application';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(Filesystem $files)
    {
        if (! $files->isDirectory($this->laravel->path('Browser/Components'))) {
            $files->makeDirectory($this->laravel->path('Browser/Components'), 0755, true, true);
        }

        $listener = new class($this->presetForLaravel($this->laravel)) implements GeneratesCodeListener {
            use CodeGenerator;

            protected $preset;

            public function __construct(Preset $preset)
            {
                $this->preset = $preset;
            }

            public function getStubFile(): string
            {
                return __DIR__.'/stubs/page.install.stub';
            }

            public function getDefaultNamespace(string $rootNamespace): string
            {
                return $rootNamespace.'/Browser/Pages';
            }

            public function generatorName(): string
            {
                return 'BasePage';
            }
        }

        if (!! $listener->generateCode()) {
            $this->info('Dusk Crawler scaffolding installed successfully.');
        }

        $driverCommandArgs = ['--all' => true];

        if ($this->option('proxy')) {
            $driverCommandArgs['--proxy'] = $this->option('proxy');
        }

        if ($this->option('ssl-no-verify')) {
            $driverCommandArgs['--ssl-no-verify'] = true;
        }

        $this->call('dusk:chrome-driver', $driverCommandArgs);
    }
}
