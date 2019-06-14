<?php declare(strict_types=1);

namespace Limepie;

class Application
{
    public $defaultNamespaceName = '';

    public $defaultControllerName = 'Index';

    public $defaultActionName = 'index';

    public $defaultPath = '';

    public $defaultProperties = [];

    public $className = '';

    public $namespaceName = '';

    public $controllerName = '';

    public $actionName = '';

    public $methodName = '';

    public $path = '';

    public $properties = [];

    public $previous = [];

    public $extension = 'php';

    public function __construct($extension = null)
    {
        if ($extension) {
            $this->extension = $extension;
        }
        Di::register('application', $this);
    }

    public function setExtension($extension) : self
    {
        $this->extension = $extension;

        return $this;
    }

    public function getExtension() : string
    {
        return $this->extension;
    }

    public function addPrevious(object $previous) : self
    {
        $this->previous[] = $previous;

        return $this;
    }

    public function setDefaultNamespaceName(string $defaultNamespaceName) : void
    {
        $this->defaultNamespaceName = $defaultNamespaceName;
    }

    public function getDefaultNamespaceName() : string
    {
        return $this->defaultNamespaceName;
    }

    public function setDefaultControllerName(string $defaultControllerName) : void
    {
        $this->defaultControllerName = $defaultControllerName;
    }

    public function getDefaultControllerName() : string
    {
        return $this->defaultControllerName;
    }

    public function setDefaultAction(string $defaultActionName) : void
    {
        $this->defaultActionName = $defaultActionName;
    }

    public function getDefaultActionName() : string
    {
        return $this->defaultActionName;
    }

    public function setDefaultPath(string $defaultPath) : void
    {
        $this->defaultPath = $defaultPath;
    }

    public function getDefaultPath() : string
    {
        return $this->defaultPath;
    }

    public function setDefaultProperties(array $defaultProperties) : void
    {
        $this->defaultProperties = $defaultProperties;
    }

    public function getDefaultProperties() : array
    {
        return $this->defaultProperties;
    }

    public function getNamespaceName() : string
    {
        return $this->namespaceName;
    }

    public function getControllerName() : string
    {
        return $this->controllerName;
    }

    public function getActionName() : string
    {
        return $this->actionName;
    }

    public function getPath() : string
    {
        return $this->path;
    }

    public function getProperties() : array
    {
        return $this->properties;
    }

    public function getPrevious() : array
    {
        return $this->previous;
    }

    public function getEndPrevious()
    {
        return \end($this->previous);
    }

    public function handle($arguments)// : ?object
    {
        if (false === Di::has('request')) {
            // ERRORCODE: 10005, provider not found
            throw new Exception('"request" service provider not found', 10005);
        }

        if (false === Di::has('response')) {
            // ERRORCODE: 10006, provider not found
            throw new Exception('"response" service provider not found', 10006);
        }

        $isStatic       = false;
        $namespaceName  = $this->getDefaultNamespaceName();
        $controllerName = $this->getDefaultControllerName();
        $actionName     = $this->getDefaultActionName();
        $path           = $this->getDefaultPath();
        $properties     = $this->getDefaultProperties();

        if (true === \is_array($arguments)) {
            if (true === isset($arguments['namespace'])) {
                $namespaceName = $arguments['namespace'];
            }

            if (true === isset($arguments['controller'])) {
                $controllerName = $arguments['controller'];
            }

            if (true === isset($arguments['action'])) {
                $actionName = $arguments['action'];
            }

            if (true === isset($arguments['path'])) {
                $path = $arguments['path'];
            }

            if (true === isset($arguments['properties'])) {
                $properties = $arguments['properties'];
            }
        } else {
            if (!$arguments) {
            } elseif (false !== \strpos($arguments, '->')) {
                $tmp1           = \explode('->', $arguments);
                $tmp2           = \explode('\\', $tmp1[0]);
                $controllerName = \array_pop($tmp2);
                $namespaceName  = \implode('\\', $tmp2);
                $actionName     = $tmp1[1];
            } elseif (false !== \strpos($arguments, '::')) {
                $tmp1           = \explode('::', $arguments);
                $tmp2           = \explode('\\', $tmp1[0]);
                $controllerName = \array_pop($tmp2);
                $namespaceName  = \implode('\\', $tmp2);
                $actionName     = $tmp1[1];
                $isStatic       = true;
            } else {
                $tmp1           = \explode('\\', $arguments);
                $controllerName = \array_pop($tmp1);
                $tmp2           = \implode('\\', $tmp1);

                if ($tmp2) {
                    $namespaceName = $tmp2;
                }
            }
        }

        $this->className      = $namespaceName . '\\' . $controllerName;
        $this->methodName     = $this->getMethodName($this->className, $actionName);
        $this->namespaceName  = $namespaceName;
        $this->actionName     = $actionName;
        $this->controllerName = $controllerName;
        $this->path           = $path;
        $this->properties     = $properties;

        $classFile = \str_replace('\\', '/', \strtr($this->className, ['\\App\\' => __BASE_DIR__ . '/app/'])) . '.' . $this->extension;

        if (false === \file_exists($classFile)) {
            //throw new \Limepie\Exception('"' . $classFile . '" file not found');
        }

        try {
            if (false === $isStatic) {
                return (new $this->className)->{$this->methodName}(
                    Di::get('request'),
                    Di::get('response')
                );
            }

            return $this->className::{$this->methodName}(
                Di::get('request'),
                Di::get('response')
            );
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function getMethodName($className, $actionName) : string
    {
        $method  = \strtolower($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $from    = 'From' . \ucfirst($method);
        $fromAll = 'FromAll';
        $org     = $actionName;
        $likely  = [];

        // 정확한 것보다 재대로 된것이 있는지를 찾고 없을 경우 정확한것

        // FromAll이 붙어 있다면
        if (1 === \preg_match('#' . $fromAll . '$#', $actionName)) {
            // 제거하고
            $actionName = \preg_replace('#' . $fromAll . '$#', '', $actionName);
        }

        $methods = \get_class_methods($className);
//        \pr($className, $methods);

        if (null === $methods) {
            throw new \Limepie\Exception($className . ' class name error');
        }
        $methodNames = \preg_grep('/^' . $actionName . '/', $methods);

        // From{REQUEST_METHOD}를 붙임
        if (false !== \array_search($actionName . $from, $methodNames, true)) {
            return $actionName . $from;
        // FromAll을 붙임
        } elseif (false !== \array_search($actionName . $fromAll, $methodNames, true)) {
            return $actionName . $fromAll;
        // 정확함
        } elseif (false !== \array_search($actionName, $methodNames, true)) {
            return $actionName;
        }
        $likely[] = $org;
        $likely[] = $actionName;
        $likely[] = $actionName . $from;
        $likely[] = $actionName . $fromAll;

        \natsort($likely);
        \rsort($likely);

        // ERRORCODE: 10002, method not found
        throw new Exception('"' . \implode('" or "', \array_unique($likely)) . '" method not found in ' . $className . '" class', 10002);
    }
}
