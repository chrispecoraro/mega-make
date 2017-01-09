<?php

namespace App\Console\Commands;

use Illuminate\Routing\Console\ControllerMakeCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class GenerateControllerCommand extends ControllerMakeCommand
{
    /**
     * The signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:php7controller:for {model*} {--type=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate PHP7-based controllers';

    /**
     * Get the desired class name from the input.
     *
     * @return string
     */
    protected function getNameInput()
    {
        return $this->argument('model');
    }

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub(): string
    {
        if ($this->option('type') === 'web') {
            return __DIR__ . '/stubs/php7.web.controller.stub';
        } else if ($this->option('type') === 'api') {
            return __DIR__ . '/stubs/php7.api.controller.stub';
        }
        return parent::getStub();
    }

    /**
     * Build the class with the given name.
     *
     * Remove the base controller import if we are already in base namespace.
     *
     * @param  string $name
     * @param  string $model
     * @return string
     */
    protected function buildClassWithModel($name, $model): string
    {

        $stub = $this->files->get($this->getStub());

        $file = $this->replaceNamespace($stub, $name)->replaceClass($stub, $name);

        $controllerNamespace = $this->getNamespace($name);

        $replace = [];

        $modelClass = $this->parseModel($model);

        $replace = [
            'DummyFullModelClass' => $modelClass,
            'DummyModelClass' => class_basename($modelClass),
            'DummyModelVariable' => lcfirst(class_basename($modelClass)),
        ];

        $replace["use {$controllerNamespace}\Controller;\n"] = '';

        return str_replace(
            array_keys($replace), array_values($replace), $file
        );

    }

    public function handle()
    {
        $models = $this->getNameInput();
        foreach ($models as $model) {
            $controllerName = str_plural($model) . 'Controller';
            $controllerNameWithPath = $this->parseName($controllerName);

            $model = $this->parseName($model);
            $path = $this->getPath($controllerNameWithPath);

            if ($this->alreadyExists($controllerName)) {
                $this->error($this->type . ' already exists!');
                return false;
            }

            $this->makeDirectory($path);
            $this->files->put($path, $this->buildClassWithModel($controllerNameWithPath, $model));
        }
        $this->info( 'Controllers successfully built for '. implode(', ',$models).'.');
    }

    /**
     * @return array
     */
    protected function getArguments(): array
    {
        return [
            ['model', InputArgument::REQUIRED, 'The model.'],
        ];
    }

    /**
     * @return array
     */
    protected function getOptions(): array
    {
        return [
            ['type', null, InputOption::VALUE_REQUIRED, 'Controller Type'],
        ];
    }

}