<?php

namespace Tests\Feature;

use App\Console\Commands\InstallFeaturesCommand;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\TestCase;

class InstallFeaturesCommandTest extends TestCase
{
    private string $originalBasePath;

    private string $fixturePath;

    private array $originalEnvironment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalBasePath = $this->app->basePath();
        $this->fixturePath = sys_get_temp_dir().'/marketplace-installer-'.bin2hex(random_bytes(8));
        File::makeDirectory($this->fixturePath);
        $this->originalEnvironment = $_ENV;
        $_ENV['LARAVEL_INSTALLER_DEFER_HOOKS'] = 'false';
        $_ENV['LARAVEL_INSTALLER_NO_NODE'] = 'true';
        $this->app->setBasePath($this->fixturePath);
    }

    protected function tearDown(): void
    {
        $this->app->setBasePath($this->originalBasePath);
        $_ENV = $this->originalEnvironment;
        File::deleteDirectory($this->fixturePath);
        parent::tearDown();
    }

    public function test_missing_script_is_a_safe_no_op(): void
    {
        $tester = $this->command();
        $this->assertSame(0, $tester->execute([], ['interactive' => false]));
        $this->assertFileDoesNotExist($this->fixturePath.'/answers.json');
    }

    public function test_deferred_hooks_do_not_modify_files(): void
    {
        $this->writeScript();
        $_ENV['LARAVEL_INSTALLER_DEFER_HOOKS'] = 'true';
        $this->assertSame(0, $this->command()->execute([], ['interactive' => false]));
        $this->assertFileDoesNotExist($this->fixturePath.'/answers.json');
    }

    public function test_explicit_answers_run_even_when_hooks_are_deferred(): void
    {
        $this->writeScript();
        $_ENV['LARAVEL_INSTALLER_DEFER_HOOKS'] = 'true';
        $this->assertSame(0, $this->command()->execute(['--answers' => '{"features":["auth"]}'], ['interactive' => false]));
        $this->assertSame(['features' => ['auth']], json_decode(File::get($this->fixturePath.'/answers.json'), true));
    }

    public function test_noninteractive_installer_uses_defaults_without_running_node_when_disabled(): void
    {
        $this->writeScript();
        $command = $this->installer();
        $this->assertSame(0, (new CommandTester($command))->execute([], ['interactive' => false]));
        $this->assertSame(['features' => ['auth']], json_decode(File::get($this->fixturePath.'/answers.json'), true));
        $this->assertSame([], $command->nodeSteps);
    }

    public function test_node_install_and_build_surround_the_script_mutations(): void
    {
        $this->writeScript();
        $_ENV['LARAVEL_INSTALLER_NO_NODE'] = 'false';
        $command = $this->installer();
        $this->assertSame(0, (new CommandTester($command))->execute(['--answers' => '{}'], ['interactive' => false]));
        $this->assertSame(['install', 'build'], $command->nodeSteps);
        $this->assertFileExists($this->fixturePath.'/answers.json');
    }

    #[DataProvider('invalidAnswers')]
    public function test_invalid_answers_fail_before_any_script_or_node_operation(string $answers): void
    {
        $this->writeScript();
        $_ENV['LARAVEL_INSTALLER_NO_NODE'] = 'false';
        $command = $this->installer();
        $tester = new CommandTester($command);
        $this->assertSame(1, $tester->execute(['--answers' => $answers], ['interactive' => false]));
        $this->assertStringContainsString('objeto JSON', $tester->getDisplay());
        $this->assertSame([], $command->nodeSteps);
        $this->assertFileDoesNotExist($this->fixturePath.'/answers.json');
    }

    public static function invalidAnswers(): array
    {
        return [['null'], ['true'], ['42'], ['"text"'], ['[]'], ['[1]'], ['{invalid}']];
    }

    private function command(): CommandTester
    {
        return new CommandTester($this->installer());
    }

    private function installer(): InstallFeaturesCommand
    {
        $command = new class extends InstallFeaturesCommand
        {
            public array $nodeSteps = [];

            protected function installNodeDependencies(): void
            {
                $this->nodeSteps[] = 'install';
            }

            protected function buildAssets(): void
            {
                if (! file_exists(base_path('answers.json'))) {
                    throw new \RuntimeException('O build deve executar depois das alterações do script.');
                }
                $this->nodeSteps[] = 'build';
            }
        };
        $command->setLaravel($this->app);

        return $command;
    }

    private function writeScript(): void
    {
        File::put($this->fixturePath.'/chisel.php', <<<'SCRIPT'
<?php
return (new \Laravel\Chisel\Script(__DIR__))
    ->questions([\Laravel\Chisel\Question::multiselect('features', 'Features', ['auth' => 'Auth'], ['auth'])])
    ->apply(function (\Laravel\Chisel\Chisel $chisel, array $answers): void {
        file_put_contents(__DIR__.'/answers.json', json_encode($answers, JSON_THROW_ON_ERROR));
    });
SCRIPT);
    }
}
