<?php

namespace GrokPHP\Laravel\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use GrokPHP\Enums\Model;

class InstallCommand extends Command
{
    protected $signature = 'grok:install';
    protected $description = 'Install Grok SDK configuration';

    public function handle(): int
    {
        $this->comment('Publishing Grok configuration...');
        $this->callSilent('vendor:publish', ['--tag' => 'grok-config']);

        $apiKey = $this->ask('Please enter your Grok API key');
        $this->updateEnvFile('GROK_API_KEY', $apiKey);

        $model = $this->choice(
            'Which Grok model do you want to set as default?',
            array_map(fn ($case) => $case->value, Model::cases()),
            Model::GROK_2_1212->value
        );
        $this->updateEnvFile('GROK_DEFAULT_MODEL', $model);

        $baseUrl = $this->ask('Please enter the base URL for the Grok API (or leave blank for default)', 'https://api.x.ai');
        $this->updateEnvFile('GROK_BASE_URL', $baseUrl);

        $this->info('Grok SDK configured successfully!');

        return self::SUCCESS;
    }

    protected function updateEnvFile(string $key, string $value): void
    {
        $path = base_path('.env');

        if (!File::exists($path)) {
            File::put($path, '');
        }

        $oldValue = env($key);
        if (is_null($oldValue)) {
            File::append($path, PHP_EOL . $key . '=' . $value);
            return;
        }

        $escaped = preg_quote('=' . $oldValue, '/');
        File::put(
            $path,
            preg_replace(
                "/^{$key}{$escaped}/m",
                "{$key}={$value}",
                File::get($path)
            )
        );
    }
}
