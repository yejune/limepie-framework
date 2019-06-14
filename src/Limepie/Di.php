<?php declare(strict_types=1);

namespace Limepie;

class Di
{
    protected static $instance;

    private $properties = [];

    /**
     * reset variables
     */
    public function __destruct()
    {
        //Di::instance()->properties = null;
    }

    /**
     * Singleton instance
     */
    public static function instance() : Di
    {
        if (null === Di::$instance) {
            Di::$instance = new Di();

            // default it automatically
            // Di::$instance->setProperty('request', function() {
            //     return new Request;
            // });
            // Di::$instance->setProperty('response', function() {
            //     return new Response;
            // });
            // Di::$instance->setProperty('dispatcher', function() {
            //     return new Dispatcher;
            // });
        }

        return Di::$instance;
    }

    public function getInstance() : Di
    {
        return Di::$instance;
    }

    public function hasProperty(string $key) : bool
    {
        return true === isset($this->properties[$key]);
    }

    public function setProperty(string $key, $value) : void
    {
        $this->properties[$key] = $value;
    }

    public function getProperty(string $key)
    {
        return $this->properties[$key];
    }

    public function setProperties(array $properties) : void
    {
        $this->properties = $properties;
    }

    public function getProperties() : array
    {
        return $this->properties;
    }

    public static function has($className)
    {
        return Di::instance()->hasProperty($className);
    }

    public static function register(string $className, $classObject, bool $renew = false) : void
    {
        if (false === Di::instance()->hasProperty($className) || true === $renew) {
            Di::instance()->setProperty($className, $classObject);
        }
    }

    public static function set(string $key, $value) : void
    {
        Di::instance()->setProperty($key, $value);
    }

    public static function isCallableClosure($value) : bool
    {
        //return true === \is_object($value) && ($value instanceof Closure);

        return true === \is_object($value) && true === \is_callable($value);
    }

    public static function raw(string $key)
    {
        if (true === Di::instance()->hasProperty($key)) {
            return Di::instance()->getProperty($key);
        }

        // ERRORCODE: 10004, service provider not found
        throw new Exception('"' . $key . '" service provider not found', 10004);
    }

    public static function get(string $key)
    {
        if (true === Di::instance()->hasProperty($key)) {
            $value = Di::instance()->getProperty($key);

            if (true === Di::isCallableClosure($value)) {
                // TODO: invoke method가 있으면 callable가 true임, invoke를 계속 실행하게 됨
                $value = $value();
                Di::instance()->setProperty($key, $value);
            }

            return $value;
        }

        // ERRORCODE: 10004, service provider not found
        throw new Exception('"' . $key . '" service provider not found', 10004);
    }
    public static function getOrNull(string $key)
    {
        if (true === Di::instance()->hasProperty($key)) {
            $value = Di::instance()->getProperty($key);

            if (true === Di::isCallableClosure($value)) {
                // TODO: invoke method가 있으면 callable가 true임, invoke를 계속 실행하게 됨
                $value = $value();
                Di::instance()->setProperty($key, $value);
            }

            return $value;
        }

        return null;
    }

    public static function merge($arg)
    {
        if (true === \is_array($arg)) {
            Di::instance()->setProperties($arg + Di::instance()->getProperties());
        } else {
            if (1 < \count(\func_get_args())) {
                $val = \func_get_arg(1);

                if (true === \is_array($val)) {
                    if (false === Di::instance()->hasProperty($arg)) {
                        Di::instance()->setProperty($arg, []);
                    }
                    Di::instance()->setProperty($arg, $val + Di::instance()->getProperty($arg));
                } else {
                    Di::instance()->setProperty($arg, $val);
                }
            }
        }
    }
}
