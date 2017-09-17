<?php

namespace ChrisPecoraro\MM\Commands;

use Illuminate\Routing\Console\ControllerMakeCommand;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class MegaMakeCommand extends ControllerMakeCommand
{
    /**
     * The signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:scaffold {model*} {--type=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate scaffold for Laravel 5.5 entities';

    private $pluralVariableName = '';

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
        return str_replace('DummyModelPluralVariable', $this->getPluralVariableName(), $file);
    }

    /**
     * @param string $model
     * @return string
     */
    protected function buildTest(string $model) : string
    {
        $stub = $this->files->get($this->getTestStub());
        return str_replace(['DummyModelPluralVariable', 'DummyModelClass'], [$this->getPluralVariableName(), $model] , $stub);
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
            'DummyModelPluralVariable' => $this->getPluralVariableName(),
            'DummyFullModelClass' => $modelClass,
            'DummyModelClass' => class_basename($modelClass),
            'DummyModelVariable' => lcfirst(class_basename($modelClass)),
        ];
        $replace["use {$controllerNamespace}\Controller;\n"] = '';
        Artisan::call('make:model', ['name'=>class_basename($modelClass)]);
        Artisan::call('make:factory', ['name'=>class_basename($modelClass) . 'Factory']);
        Artisan::call('make:migration', ['name'=>'create_'. lcfirst(class_basename($modelClass)) . '_table']);
        Artisan::call('make:resource', ['name'=>$model. 'Resource' ]);
        Artisan::call('make:resource', ['name'=>$model. 'ResourceCollection' ]);
        Artisan::call('make:request', ['name'=>$model]);
        Artisan::call('make:seeder', ['name'=>$model.'Seeder']);
        // TODO add seeder to DatabaseSeeder.php
        return str_replace(
            array_keys($replace), array_values($replace), $file
        );

    }

    public function handle()
    {
        $models = $this->getNameInput();
        foreach ($models as $model) {
            $this->setPluralVariableName($model);

            $controllerName = str_plural($model) . 'Controller';
            $controllerNameWithPath = $this->qualifyClass($controllerName);

            $path = $this->getPath($controllerNameWithPath);

            if ($this->alreadyExists($controllerName)) {
                $this->error($this->type . ' already exists!');
                return false;
            }

            $this->makeDirectory($path);
            $this->files->put($path, $this->buildClassWithModel($controllerNameWithPath, $model));
            $type = 'api';
            if ($this->option('type') != '')  {
                $type = $this->option('type');
            }
            $this->files->append('routes/' . $type . '.php', "\n" . $this->buildRoute($controllerNameWithPath, $model));
            $this->files->put('tests/Feature/'. $model . ucfirst($type) . 'RoutesTest.php', $this->buildTest($model));

        }
        $this->info('Scaffold successfully built for ' . implode(', ', $models) . '.');
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
     * @return string plural variable name
     */
    private function getPluralVariableName(): string
    {
        return $this->pluralVariableName;
    }

    /**
     * @param string $model
     * @return null
     */
    private function setPluralVariableName(string $model)
    {
        $this->pluralVariableName = str_plural(lcfirst(class_basename($model)));
    }

}