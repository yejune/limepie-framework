<?php declare(strict_types=1);

namespace Limepie\Form\Generation\Fields;

class Image extends \Limepie\Form\Generation\Fields
{
    public static function write($key, $property, $data)
    {
        $maxwidth = $property['maxwidth'] ?? 0;
        $minwidth = $property['minwidth'] ?? 0;
        $maxheight = $property['maxheight'] ?? 0;
        $minheight = $property['minheight'] ?? 0;

        if (true === \Limepie\is_file_array($data, false)) {
            $value  = \htmlspecialchars((string) $data['name']);
            $accept = $property['rules']['accept'] ?? '';
            $button = '';
            $html   = <<<EOT
            <input type="text" class='form-control form-control-file' value="{$value}" readonly="readonly" />
EOT;

            foreach ($data as $key1 => $value1) {
                if ('name' === $key1) {
                    $html .= <<<EOT
                    <input type="text" class='form-control-file form-control-filetext form-control-image' data-maxwidth="{$maxwidth}" data-minwidth="{$minwidth}" data-maxheight="{$maxheight}" data-minheight="{$minheight}"  name="{$key}[{$key1}]" value="{$value1}" accept="{$accept}" />
                    EOT;
                } else {
                    if ('tmp_name' === $key1) {
                    } else {
                        $html .= <<<EOT
                            <input type="hidden" class="clone-element" name="{$key}[{$key1}]" value="{$value1}" />
                        EOT;
                    }
                }
            }

            $html .= <<<EOT
            <div class='form-preview clone-element'><img src='{$data['url']}' class='form-preview-image'></div>
EOT;
            $button = <<<EOT
            <button class="btn btn-filesearch-text" type="button"><span class="fas fa-search"></span></button>
EOT;
        } else {
            $value  = '';
            $accept = $property['rules']['accept'] ?? '';
            $html   = <<<EOT
            <input type="text" class='form-control form-control-file' value="" readonly="readonly" />
            <input type="file" class='form-control-file form-control-image' data-maxwidth="{$maxwidth}" data-minwidth="{$minwidth}" data-maxheight="{$maxheight}" data-minheight="{$minheight}" name="{$key}" value="{$value}" accept="{$accept}" />
EOT;
            $button = <<<EOT
            <button class="btn btn-filesearch" type="button"><span class="fas fa-search"></span></button>
EOT;
        }

        return [$html, $button];
    }

    public static function read($key, $property, $data)
    {
        $html = '';

        if (true === \Limepie\is_file_array($data, false)) {
            $value = \str_replace(__PUBLIC__, '', (string) $data['path']);
            $html  = <<<EOT
            <img src="{$value}" />

EOT;
        }

        return $html;
    }
}
