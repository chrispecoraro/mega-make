<?php

namespace ChrisPecoraro\LCG\Commands;

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
     * Get the controller stub file for the generator.
     *
     * @return string
     */
    protected function getControllerStub(): string
    {
        if ($this->option('type') === 'web') {
            return __DIR__ . '/../stubs/controller/web.controller.stub';
        } else if ($this->option('type') === 'api') {
            return __DIR__ . '/../stubs/controller/api.controller.stub';
        }
        return parent::getStub();
    }

    /**
     * Get the route stub file for the generator.
     *
     * @return string
     */
    protected function getRouteStub(): string
    {
        if ($this->option('type') === 'web') {
            return __DIR__ . '/../stubs/route/api.route.stub';
        } else if ($this->option('type') === 'api') {
            return __DIR__ . '/../stubs/route/web.controller.stub';
        }
        return parent::getStub();
    }

    /**
     * @param string $name
     * @param string $model
     * @return string
     */
    protected function buildRoute(string $name, string $model) : string
    {
        $stub = $this->files->get($this->getRouteStub());
        $file = $this->replaceClass($stub, $name);
        return str_replace('DummyModelPluralVariable', str_plural(lcfirst(class_basename($model))), $file);
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
    protected function buildClassWithModel(string $name, string $model): string
    {

        $stub = $this->files->get($this->getControllerStub());

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

            $path = $this->getPath($controllerNameWithPath);

            if ($this->alreadyExists($controllerName)) {
                $this->error($this->type . ' already exists!');
                return false;
            }

            $this->makeDirectory($path);
            $this->files->put($path, $this->buildClassWithModel($controllerNameWithPath, $model));
            $this->files->append('routes/'.$this->option('type') . '.php', "\n" . $this->buildRoute($controllerNameWithPath, $model));

        }
        $this->info('Controllers successfully built for ' . implode(', ', $models) . '.');
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