<?php declare(strict_types=1);

namespace Limepie\Form\Generation\Fields;

class Hidden extends \Limepie\Form\Generation\Fields
{
    public static function write($key, $property, $value)
    {
        $value = \htmlspecialchars((string) $value);

        if (0 === \strlen($value) && true === isset($property['default'])) {
            $value = \htmlspecialchars((string) $property['default']);
        }
        $default = $property['default'] ?? '';

        $html = <<<EOT
    <input type="hidden" class="form-control" readonly="readonly" name="{$key}" value="{$value}" data-default="{$default}" />
EOT;

        return $html;
    }

    public static function read($key, $property, $value)
    {
        return $html = '';
    }
}
