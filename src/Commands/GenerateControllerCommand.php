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
        }
        return __DIR__ . '/../stubs/controller/api.controller.stub';
    }

    /**
     * Get the route stub file for the generator.
     *
     * @return string
     */
    protected function getRouteStub(): string
    {
        if ($this->option('type') === 'web') {
            return __DIR__ . '/../stubs/route/web.route.stub';
        }
        return __DIR__ . '/../stubs/route/api.route.stub';
    }


    /**
     * Get the route stub file for the generator.
     *
     * @return string
     */
    protected function getTestStub(): string
    {
        if ($this->option('type') === 'web') {
            return __DIR__ . '/../stubs/test/web.route.test.stub';
        }
        return __DIR__ . '/../stubs/test/api.route.test.stub';
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
        return str_replace('DummyModelPluralVariable', $this->getPluralVariableNameFromClass($model), $file);
    }

    /**
     * @param string $model
     * @return string
     */
    protected function buildTest(string $model) : string
    {
        $stub = $this->files->get($this->getTestStub());
        return str_replace(['DummyModelPluralVariable', 'DummyModelClass'], [$this->getPluralVariableNameFromClass($model), $model] , $stub);
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
        $pluralVariableName = $this->getPluralVariableNameFromClass($model);
        $modelClass = $this->parseModel($model);

        $replace = [
            'DummyModelPluralVariable' => $pluralVariableName,
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
            $this->files->put('tests/'. $model . ucfirst($this->option('type')) . 'RoutesTest.php', $this->buildTest($model));

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

    /**
     * @param string $model
     * @return string
     */
    private function getPluralVariableNameFromClass(string $model): string
    {
        return str_plural(lcfirst(class_basename($model)));
    }

}