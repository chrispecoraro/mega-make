<?php

namespace ChrisPecoraro\MM\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Routing\Console\ControllerMakeCommand;
use Illuminate\Foundation\Console\ModelMakeCommand;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class MegaMakeCommand extends GeneratorCommand
{
    /**
     * The signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mega:make {model*} {--type=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate scaffold for Laravel 5.5 entities';

    private $pluralVariableName = '';
    private $modelMakeCommand;
    private $controllerMakeCommand;

    /**
     * MegaMakeCommand constructor.
     *
     * @param \Illuminate\Filesystem\Filesystem $filesystem
     * @param \Illuminate\Foundation\Console\ModelMakeCommand $modelMakeCommand
     * @param \Illuminate\Routing\Console\ControllerMakeCommand $controllerMakeCommand
     */
    public function __construct(Filesystem $filesystem, ModelMakeCommand $modelMakeCommand, ControllerMakeCommand $controllerMakeCommand)
    {
        parent::__construct($filesystem);
        $this->files = $filesystem;
        $this->modelMakeCommand = $modelMakeCommand;
        $this->controllerMakeCommand = $controllerMakeCommand;
    }

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
     * Get the default controller namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultControllerNamespace($rootNamespace)
    {
        return $rootNamespace.'\Http\Controllers';
    }

    /**
     * Get the default model namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultModelNamespace($rootNamespace)
    {
        return $rootNamespace.'\\';
    }

    protected function getStub()
    {
        // TODO: Implement getStub() method.
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
     * Get the model stub file for the generator.
     *
     * @return string
     */
    protected function getModelStub(): string
    {
        return __DIR__ . '/../stubs/model/model.stub';
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
     * Get the fully-qualified model class name.
     *
     * @param  string  $model
     * @return string
     */
    protected function parseModel($model)
    {
        if (preg_match('([^A-Za-z0-9_/\\\\])', $model)) {
            throw new InvalidArgumentException('Model name contains invalid characters.');
        }

        $model = trim(str_replace('/', '\\', $model), '\\');

        if (! Str::startsWith($model, $rootNamespace = $this->laravel->getNamespace())) {
            $model = $rootNamespace.$model;
        }

        return $model;
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

        $modelClass = $this->qualifyClass($model);
        $controllerStub = $this->files->get($this->getControllerStub());
        $controllerFile = $this->replaceNamespace($controllerStub, $name)->replaceClass($controllerStub, $name);
        $controllerNamespace = $this->getNamespace($name);

        $replace = [
            'DummyModelPluralVariable' => $this->getPluralVariableName(),
            'DummyFullModelClass' => $modelClass,
            'DummyModelClass' => class_basename($modelClass),
            'DummyModelVariable' => lcfirst(class_basename($modelClass)),
        ];
        $replace["use {$controllerNamespace}\Controller;\n"] = '';
        //Artisan::call('make:model', ['name'=>class_basename($modelClass)]);
        Artisan::call('make:factory', ['name'=>class_basename($modelClass) . 'Factory']);
        Artisan::call('make:migration', ['name'=>'create_'. lcfirst(class_basename($modelClass)) . '_table']);
        Artisan::call('make:resource', ['name'=>$model. 'Resource' ]);
        Artisan::call('make:resource', ['name'=>$model. 'ResourceCollection' ]);
        Artisan::call('make:request', ['name'=>$model]);
        Artisan::call('make:seeder', ['name'=>$model.'Seeder']);
        // TODO add seeder to DatabaseSeeder.php
        return str_replace(
            array_keys($replace), array_values($replace), $controllerFile
        );

    }

    /**
     * Get the destination class path.
     *
     * @param  string  $name
     * @return string
     */
    protected function getControllerPath($name)
    {
        $name = Str::replaceFirst($this->rootNamespace(), '', $name);

        return $this->laravel['path'].'/Http/Controllers/'.str_replace('\\', '/', $name).'.php';
    }

    /**
     * Get the destination class path.
     *
     * @param  string  $name
     * @return string
     */
    protected function getModelPath($name)
    {
        $name = Str::replaceFirst($this->rootNamespace(), '', $name);

        return $this->laravel['path'].'/'.str_replace('\\', '/', $name).'.php';
    }

    public function handle()
    {
        $models = $this->getNameInput();
        foreach ($models as $model) {
            $this->setPluralVariableName($model);

            $modelNameWithPath = $this->qualifyClass($model);
            $modelPath = $this->getModelPath($modelNameWithPath);
            $modelStub = $this->files->get($this->getModelStub());
            $modelClass = $this->qualifyClass($model);
            $modelNameWithPath = $this->qualifyClass($model);
            $modelFile = $this->replaceNamespace($modelStub, $modelNameWithPath)->replaceClass($modelStub, $model);

            $modelNamespace = $this->getDefaultModelNamespace($this->rootNamespace());

            $replace = [
                'DummyModelPluralVariable' => $this->getPluralVariableName(),
                'DummyFullModelClass' => $modelClass,
                'DummyModelClass' => class_basename($modelClass),
                'DummyModelVariable' => lcfirst(class_basename($modelClass)),
                'DummyNamespace'=> $modelNamespace
            ];

            $modelFile = str_replace(
                array_keys($replace), array_values($replace), $modelFile
            );

            $this->makeDirectory($modelPath);
            $this->files->put($modelPath, $modelFile);

            $controllerName = str_plural($model) . 'Controller';
            $controllerNameWithPath = $this->qualifyClass($controllerName);
            $controllerPath = $this->getControllerPath($controllerNameWithPath);

            if ($this->alreadyExists($controllerName)) {
                $this->error($this->type . ' already exists!');
                return false;
            }

            $this->makeDirectory($controllerPath);
            $this->files->put($controllerPath, $this->buildClassWithModel($controllerNameWithPath, $model));

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