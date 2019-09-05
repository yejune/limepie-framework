<?php declare(strict_types=1);

namespace Limepie\Form;

use Limepie\Di;

class Generation
{
    public function __construct()
    {
    }

    public static function getValue($data, $key)
    {
        $keys  = \explode('.', $key);
        $value = $data;

        foreach ($keys as $id) {
            if (true === isset($value[$id])) {
                $value = $value[$id];

                continue;
            }

            return null;
        }

        return $value;
    }

    public static function getDefault($spec, $key)
    {
        $keys  = \explode('.', $key);
        $value = $spec;

        foreach ($keys as $id) {
            if (true === isset($value['properties'][$id])) {
                $value = $value['properties'][$id];

                continue;
            }

            return null;
        }

        return $value['default'] ?? null;
    }

    public function write(array $spec, array $data = []) : string
    {
        $method = __NAMESPACE__ . '\\Generation\\Fields\\' . \ucfirst($spec['type']);
        $html   = '';

        Generation\Fields::$allData    = $data;
        Generation\Fields::$conditions = $spec['conditions'] ?? [];
        Generation\Fields::$specs      = $spec               ?? [];

        $reverseConditions = [];
        // foreach(Generation\Fields::$conditions as $key => $value ) {
        //     foreach($value as $k2 => $v2) {
        //         foreach($v2 as $k3 => $v3) {
        //             if(static::getValue($data, $key) == $k2 || static::getDefault($spec, $key) == $k2){
        //                 $reverseConditions[$k3] = $v3 ;
        //             }
        //         }
        //     }
        // }

        if (Generation\Fields::$conditions) {
            foreach (Generation\Fields::$conditions as $key => $value) {
                foreach ($value as $k2 => $v2) {
                    foreach ($v2 as $k3 => $v3) {
                        $reverseConditions[$k3][$key][$k2] = $v3;
                    }
                }
            }
        }
        //pr($reverseConditions);

        Generation\Fields::$reverseConditions = $reverseConditions;

        $title = '';

        if (true === isset($spec['label'])) {
            if (true === \is_array($spec['label'])) {
                if (true === isset($spec['label'][\Limepie\get_language()])) {
                    $title = $spec['label'][\Limepie\get_language()];
                }
            } else {
                $title = $spec['label'];
            }
        }

        if ($title) {
            $html = '<label class="form-label">' . $title . '</label>';
        }

        $description = '';

        if (true === isset($spec['description'])) {
            if (true === \is_array($spec['description'])) {
                if (true === isset($spec['description'][\Limepie\get_language()])) {
                    $description = $spec['description'][\Limepie\get_language()];
                }
            } else {
                $description = $spec['description'];
            }
        }

        if ($description) {
            $html .= '<p>' . $description . '</p>';
        }

        if ($html) {
            $html .= '<hr />';
        }
        $elements = $method::write($spec['key'] ?? '', $spec, $data);

        $innerhtml = <<<EOT
<div>
{$html}
{$elements}
</div>
EOT;

        $innerhtml .= '<hr /> <div class="controlbtn">';

        if (true === isset($spec['buttons'])) {
            // {@button = form.spec.buttons}
            //     {?button.type == 'delete'}
            //         <a href='' data-method='delete' {?button.value??false} data-value="{=\Limepie\genRandomString(6)}"{/} class="btn {=button.class}">{=button.text}</a>
            //     {:}
            //         <button type='{=button.type}'{?button.name??false} name='{=button.name}'{/} class="btn {=button.class}"{?button.value??false} value="{=button.value}"{/}{?button.onclick??false} onclick="/*{=button.onclick}*/"{/}>{=button.text}</button>
            //     {/}
            // {/}
            $i     = 0;
            $count = \count($spec['buttons']);

            foreach ($spec['buttons'] ?? [] as $key => $button) {
                $i++;
                $value       = '';
                $class       = '';
                $text        = '';
                $type        = '';
                $name        = '';
                $onclick     = '';
                $href        = '';
                $description = '';

                if ($button['onclick'] ?? false) {
                    $onclick = 'onclick="' . $button['onclick'] . '"';
                }

                if ($button['name'] ?? false) {
                    $name = 'name="' . $button['name'] . '"';
                }

                if ($button['type'] ?? false) {
                    $type = $button['type'];
                }

                if (true === isset($button['text'][\Limepie\get_language()])) {
                    $text = $button['text'][\Limepie\get_language()];
                } elseif (true === isset($button['text'])) {
                    $text = $button['text'];
                }

                if ($button['class'] ?? false) {
                    $class = $button['class'];
                }

                if ($button['href'] ?? false) {
                    $href = $button['href'];
                    $flag = '';

                    if (false !== \strpos($href, '#')) {
                        [$href, $flag] = \explode('#', $href, 2);
                    }

                    if (false !== \strpos($href, '{=querystring}')) {
                        $href = \str_replace('{=querystring}', Di::get('request')->getQueryString(), $href);
                    }
                    $href = \rtrim($href, '?');

                    if ($flag) {
                        $href .= '#' . $flag;
                    }
                }

                if ($button['value'] ?? false) {
                    $value = 'value="' . $button['value'] . '"';
                }

                if ($button['description'] ?? false) {

                    if (true === isset($button['description'][\Limepie\get_language()])) {
                        $description = $button['description'][\Limepie\get_language()];
                    } elseif (true === isset($button['description'])) {
                        $description = $button['description'];
                    }

                    $description = 'data-description="' . htmlspecialchars($description) . '"';
                }

                if ('delete' === $button['type']) {
                    $string = \Limepie\genRandomString(6);

                    $innerhtml .= '<a href="" data-method="delete" data-value="' . $string . '" ' . str_replace('{=string}',$string,$description) . ' class="btn ' . $class . '">' . $text . '</a>';
                } elseif ('a' === $button['type']) {
                    $innerhtml .= '<a href="' . $href . '" class="btn ' . $class . '">' . $text . '</a>';
                } else {
                    $innerhtml .= '<button type="' . $type . '" ' . $name . ' class="btn ' . $class . '" ' . $value . ' ' . $onclick . ' >' . $text . '</button>';
                }

                if ($i === $count) {
                }
                //$innerhtml .= ' ';
            }
        } else {
            $innerhtml .= '<input type="submit" value="저장" class="btn btn-primary" />';
        }
        $innerhtml .= '</div>';

        return $innerhtml;
    }

    public function read(array $spec, array $data = []) : string
    {
        $method = __NAMESPACE__ . '\\Generation\\Fields\\' . \ucfirst($spec['type']);

        if (true === isset($spec['label'][\Limepie\get_language()])) {
            $title = $spec['label'][\Limepie\get_language()];
        } elseif (true === isset($spec['label'])) {
            $title = $spec['label'];
        } else {
            $title = 'Form';
        }

        $elements = $method::read($spec['key'] ?? '', $spec, $data);

        $innerhtml = <<<EOT
<div>
<label>{$title}</label>
{$elements}
</div>
EOT;

        return $innerhtml;
    }
}
