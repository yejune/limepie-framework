<?php declare(strict_types=1);

namespace Limepie\Form;

class Generation
{
    public function __construct()
    {
    }
    public static function getValue($data, $key) {
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

        Generation\Fields::$allData = $data;
        Generation\Fields::$conditions = $spec['conditions'] ?? [];
        Generation\Fields::$specs = $spec ?? [];

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

        if(Generation\Fields::$conditions) {
            foreach(Generation\Fields::$conditions as $key => $value) {
                foreach($value as $k2 => $v2) {
                    foreach($v2 as $k3 => $v3) {
                        $reverseConditions[$k3][$key][$k2] = $v3;
                    }
                }
            }
        }
        //pr($reverseConditions);

        Generation\Fields::$reverseConditions = $reverseConditions;

        if (true === isset($spec['label'][\Limepie\get_language()])) {
            $title = $spec['label'][\Limepie\get_language()];
        } elseif (true === isset($spec['label'])) {
            $title = $spec['label'];
        } else {
            $title = '';
        }

        if ($title) {
            $html = '<label>' . $title . '</label>';
        }

        if (true === isset($spec['description'][\Limepie\get_language()])) {
            $description = $spec['description'][\Limepie\get_language()];
        } elseif (true === isset($spec['description'])) {
            $description = $spec['description'];
        } else {
            $description = '';
        }

        if ($description) {
            $html .= '<p>' . $description . '</p>';
        }
        $elements = $method::write($spec['key'] ?? '', $spec, $data);

        $innerhtml = <<<EOT
<div>
{$html}
{$elements}
</div>
EOT;

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
