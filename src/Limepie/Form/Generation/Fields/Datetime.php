<?php declare(strict_types=1);

namespace Limepie\Form\Generation\Fields;

class Datetime extends \Limepie\Form\Generation\Fields
{
    public static function write($key, $property, $value)
    {
        $value = \htmlspecialchars((string) $value);

        if (0 === \strlen($value) && true === isset($property['default'])) {
            $value = \htmlspecialchars((string) $property['default']);
        }

        // datetime-local format
        if ($value) {
            $value = \date('Y-m-d\TH:i:s', \strtotime($value));
        }

        $default = $property['default'] ?? '';

        $readonly = '';

        if (isset($property['readonly']) && $property['readonly']) {
            $readonly = ' readonly="readonly"';
        }

        $html = <<<EOT
        <input type="datetime-local" class="form-control" name="{$key}" value="{$value}" data-default="{$default}"${readonly} />

EOT;

        return $html;
    }

    public static function read($key, $property, $value)
    {
        $value = (string) $value;
        $html  = <<<EOT
        {$value}

EOT;

        return $html;
    }
}
