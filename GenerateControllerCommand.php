<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Routing\Console\ControllerMakeCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;

class GenerateControllerCommand extends ControllerMakeCommand
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate PHP7-based web controllers';
    protected $type = 'Controller';
    protected $signature = 'make:php7controller:for {model*}  {--type=} {--model} {--resource}';

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
    protected function getStub()
    {
            if ($this->option('type') === 'web'){
                return __DIR__ . '/stubs/php7.web.controller.stub';
            }
            else if ($this->option('type') === 'api') {
                return __DIR__ . '/stubs/php7.api.controller.stub';
            }
            return parent::getStub();
    }
    /**
     * Build the class with the given name.
     *
     * Remove the base controller import if we are already in base namespace.
     *
     * @param  string  $name
     * @return string
     */
    protected function buildClassWithModel($name, $model)
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

    public function fire()
    {
        $models = $this->getNameInput();
        foreach ($models as $model){
            $controllerName = str_plural($model) . 'Controller';
            $controllerNameWithPath  = $this->parseName($controllerName);

            $model = $this->parseName($model);
            $path = $this->getPath($controllerNameWithPath);

            if ($this->alreadyExists($controllerName ) ){
                $this->error($this->type.' already exists!');
                return false;
            }

            $this->makeDirectory($path);
            $this->files->put($path, $this->buildClassWithModel($controllerNameWithPath, $model));
        }
        $this->info($this->type.' created successfully.');
    }

    protected function getArguments()
    {
        return [
            ['model', InputArgument::REQUIRED, 'The model.'],
        ];
    }



}