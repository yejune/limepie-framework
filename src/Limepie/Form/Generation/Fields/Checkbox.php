<?php declare(strict_types=1);

namespace Limepie\Form\Generation\Fields;

class Checkbox extends \Limepie\Form\Generation\Fields
{
    public static function write($key, $property, $value)
    {
        $value = (string) $value;

        if (0 === \strlen($value) && true === isset($property['default'])) {
            $value = (string) $property['default'];
        }

        $checked = ((bool) $value) ? 'checked' : '';

        if (true === isset($property['label'])) {
            if (true === isset($property['label'][static::getLanguage()])) {
                $title = $property['label'][static::getLanguage()];
            } else {
                $title = $property['label'];
            }
        } else {
            $title = '';
        }

        $onclick = '';

        if (true === isset($property['onclick'])) {
            $onclick = 'onclick="' . \trim(\addcslashes($property['onclick'], '"')) . '"';
        }

        $html = <<<EOT
        <input type="checkbox" class="form-control" name="{$key}" value="1" {$checked} ${onclick} /> {$title}

EOT;

        return $html;
    }

    public static function read($key, $property, $value)
    {
        $value = (bool) $value;
        $html  = <<<EOT
        {$value}

EOT;

        return $html;
    }
}
