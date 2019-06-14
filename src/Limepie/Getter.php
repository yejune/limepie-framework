<?php declare(strict_types=1);

namespace Limepie;

class Getter implements \Iterator, \ArrayAccess, \Countable
{
    private $properties;

    public function __construct($properties)
    {
        $this->properties = $properties;
    }
    public function __get($property)
    {
        if (true === isset($this->properties[$property])) {
            return $this->properties[$property];
        }
    }

    public function __set($property, $value)
    {
        $this->properties[$property] = $value;

        return $this;
    }

    public function __call($property, $arguments)
    {
        if (0 === \strpos($property, 'set')) {
            $fieldName                    = \Limepie\decamelize(\substr($property, 3));
            $this->properties[$fieldName] = $arguments[0];

            return $this;
        } elseif (0 === \strpos($property, 'get')) {
            $fieldName = \Limepie\decamelize(\substr($property, 3));

            return $this->properties[$fieldName] ?? null;
        } else {
            throw new \Limepie\Exception('"' . $property . '" function not found', 999);
        }
    }
    public function count()
    {
        return \count($this->properties);
    }

    public function offsetSet($offset, $value)
    {
        if (null === $offset) {
            $this->properties[] = $value;
        } else {
            $this->properties[$offset] = $value;
        }
    }

    public function offsetExists($offset)
    {
        return isset($this->properties[$offset]);
    }

    public function offsetUnset($offset)
    {
        unset($this->properties[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->properties[$offset];

        return isset($this->properties[$offset]) ? $this->properties[$offset] : null;
    }

    //iterator_to_array
    public function toArray()
    {
        if (true === \Limepie\is_assoc($this->properties)) {
            return $this->properties;
        }
        $properties = [];

        foreach ($this->properties as $index => $property) {
            //index에서 seq로 변경
            $properties[] = $property->toArray();
        }

        return $properties;
    }

    public function rewind()
    {
        \reset($this->properties);
    }

    public function current()
    {
        $var = \current($this->properties);

        return $var;
    }

    public function key()
    {
        $var = \key($this->properties);

        return $var;
    }

    public function next()
    {
        $var = \next($this->properties);

        return $var;
    }

    public function valid()
    {
        $key = \key($this->properties);
        $var = (null !== $key && false !== $key);

        return $var;
    }
}
