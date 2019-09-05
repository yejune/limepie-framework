<?php declare(strict_types=1);

namespace Limepie;

function _($string)
{
    return \dgettext('system', $string);
}
function __($domain, $string)
{
    return \dgettext($domain, $string);
}
function ___($domain, $string, $a, $b)
{
    return \dngettext($domain, $string, $a, $b);
}

/**
 * debug용 print_r
 *
 * @return void
 */
function pr()
{
    $trace = \debug_backtrace()[0];
    echo '<pre xstyle="font-size:9px;font: small monospace;">';
    echo \PHP_EOL . \str_repeat('=', 100) . \PHP_EOL;
    echo 'file ' . $trace['file'] . ' line ' . $trace['line'];
    echo \PHP_EOL . \str_repeat('-', 100) . \PHP_EOL;

    if (1 === \func_num_args()) {
        $args = \func_get_arg(0);
    } else {
        $args = \func_get_args();
    }
    echo \Limepie\print_x($args);
    echo \PHP_EOL . \str_repeat('=', 100) . \PHP_EOL;
    echo '</pre>';
}

/**
 * beautify print_r
 *
 * @param mixed $args
 *
 * @return string
 */
function print_x($args)
{
    $a = [
        'Object' . \PHP_EOL . ' \*RECURSION\*' => '#RECURSION',
        '    '                                 => '  ',
        \PHP_EOL . \PHP_EOL                    => \PHP_EOL,
        ' \('                                  => '(',
        ' \)'                                  => ')',
        '\(' . \PHP_EOL . '\s+\)'              => '()',
        'Array\s+\(\)'                         => 'Array()',
        '\s+(Array|Object)\s+\('               => ' $1(',
    ];
    $args = \htmlentities(\print_r($args, true));

    foreach ($a as $key => $val) {
        $args = \preg_replace('#' . $key . '#X', $val, $args);
    }

    return $args;
}

/**
 * 배열을 html table로 반환
 *
 * @param mixed $in
 *
 * @return string
 */
function html_encode(array $in) : string
{
    if (0 < \count($in)) {
        $t = '<table border=1 cellspacing="0" cellpadding="0">';

        foreach ($in as $key => $value) {
            if (true === \is_assoc($in)) {
                if (true === \is_array($value)) {
                    $t .= '<tr><td>' . $key . '</td><td>' . \Limepie\html_encode($value) . '</td></tr>';
                } else {
                    $t .= '<tr><td>' . $key . '</td><td>' . $value . '</td></tr>';
                }
            } else {
                if (true === \is_array($value)) {
                    $t .= '<tr><td>' . \Limepie\html_encode($value) . '</td></tr>';
                } else {
                    $t .= '<tr><td>' . $value . '</td></tr>';
                }
            }
        }

        return $t . '</table>';
    }

    return '';
}

/**
 * 배열의 키가 숫자가 아닌 경우를 판별
 *
 * @param array $array
 *
 * @return bool
 */
function is_assoc($array)
{
    if (true === \is_array($array)) {
        $keys = \array_keys($array);

        return \array_keys($keys) !== $keys;
    }

    return false;
}

/**
 * file을 읽어 확장자에 따라 decode하여 리턴
 *
 * @param string $filename
 *
 * @return string
 */
function decode_file(string $filename) : array
{
    if (false === \file_exists($filename)) {
        throw new Exception($filename . ' file not exists');
    }
    $contents = \file_get_contents($filename);
    $ext      = \pathinfo($filename, \PATHINFO_EXTENSION);

    switch ($ext) {
        case 'yaml':
        case 'yml':
            $result = \yaml_parse($contents);

            break;
        case 'json':
            $result = \json_decode($contents, true);

            if ($type = \json_last_error()) {
                switch ($type) {
                    case \JSON_ERROR_DEPTH:
                        $message = 'Maximum stack depth exceeded';

                        break;
                    case \JSON_ERROR_CTRL_CHAR:
                        $message = 'Unexpected control character found';

                        break;
                    case \JSON_ERROR_SYNTAX:
                        $message = 'Syntax error, malformed JSON';

                        break;
                    case \JSON_ERROR_NONE:
                        $message = 'No errors';

                        break;
                    case \JSON_ERROR_UTF8:
                        $message = 'Malformed UTF-8 characters';

                        break;
                    default:
                        $message = 'Invalid JSON syntax';
                }

                throw new \Exception($filename . ' ' . $message);
            }

            break;
        default:
            throw new \Exception($ext . ' not support');

            break;
    }

    return $result;
}

function array_insert_before(array $array, $key, array $new)
{
    $keys = \array_keys($array);
    $pos  = (int) \array_search($key, $keys, true);

    return \array_merge(\array_slice($array, 0, $pos), $new, \array_slice($array, $pos));
}

function array_insert_after(array $array, $key, array $new)
{
    $keys  = \array_keys($array);
    $index = (int) \array_search($key, $keys, true);
    $pos   = false === $index ? \count($array) : $index + 1;

    return \array_merge(\array_slice($array, 0, $pos), $new, \array_slice($array, $pos));
}

// function array_insert_before($key,&$array,$new_key,$new_value='NA'){
//     if(array_key_exists($key,$array)){
//         $new = array();
//         foreach($array as $k=>$value){
//             if($k === $key){
//                 $new[$new_key] = $new_value;
//             }
//             $new[$k] = $value;
//         }
//         return $new;
//     }
//     return false;
// }

// function array_insert_after($key,&$array,$new_key,$new_value='NA'){
//     if(array_key_exists($key,$array)){
//         $new = array();
//         foreach($array as $k=>$value){
//             $new[$k] = $value;
//             if($k === $key){
//                 $new[$new_key] = $new_value;
//             }
//         }
//         return $new;
//     }
//     return false;
// }

function refparse($arr = [], $basepath = '') : array
{
    $return = [];

    foreach ($arr as $key => $value) {
        if (true === \in_array($key, ['$after', '$before', '$change'], true)) {
        } elseif ('$ref' === $key) {
            if (false === \is_array($value)) {
                $value = [$value];
            }
            $data = [];

            foreach ($value as $path) {
                $m       = [];
                $orgPath = $path;
                $keys    = [
                    'properties',
                ];

                if (0 === \strpos($path, '(')) {
                    if (\preg_match('#\((?P<path>.*)?\)\.(?P<key>.*)#', $path, $m)) {
                        $path = $m['path'];
                        $keys = \array_merge(\explode('.', $m['key']), $keys, );
                    }
                    //\pr($m);
                }
                //pr($keys);

                //\pr($basepath, $path);
                if ($path) {
                    if (0 !== \strpos($path, '/')) {
                        if ($basepath) {
                            $path = $basepath . '/' . $path . '';
                        }
                    }
                    //\pr($path);
                    $yml = \Limepie\yml_parse_file($path);

                    $yml2 = $yml;

                    foreach ($keys as $key2) {
                        if (true === isset($yml2[$key2])) {
                            $yml2 = $yml2[$key2];
                        //pr($keys, $path, $key2, $yml2);
                        } else {
                            throw new \Exception($key2 . ' not found');
                        }
                    }
                } else {
                    throw new \Exception($orgPath . ' ref error');
                }

                if ($yml2) {
                    $data = \array_merge($data, $yml2);
                }
            }
            $yml = \Limepie\refparse($data, $basepath);

            $return = \array_merge($return, $yml);
        } elseif (true === \is_array($value)) {
            $return[$key] = \Limepie\refparse($value, $basepath);

            if (true === isset($value['lang'])) {
                $default = $value;
                unset($default['lang']);
                $default2                      = $default;
                $default2['rules']['required'] = false;

                $value = [
                    'label'      => $value['label'],
                    'type'       => 'group',
                    'properties' => [
                        'ko' => ['label' => \__('core', '한국어'), 'prepend' => '<i class="flag-icon flag-icon-kr"></i>'] + $default2,
                        'en' => ['label' => \__('core', '영어'), 'prepend' => '<i class="flag-icon flag-icon-us"></i>'] + $default2,
                        'zh' => ['label' => \__('core', '중국어'), 'prepend' => '<i class="flag-icon flag-icon-cn"></i>'] + $default2,
                        'ja' => ['label' => \__('core', '일본어'), 'prepend' => '<i class="flag-icon flag-icon-jp"></i>'] + $default2,
                    ],
                ];
                $return[$key . '_langs'] = \Limepie\refparse($value, $basepath);
            }
        } else {
            $return[$key] = $value;
        }

        if ('$after' === $key) {
            foreach ($value as $k => $v) {
                foreach ($v as $v1) {
                    $return = \Limepie\array_insert_after($return, $k, $v1);
                }
            }
        } elseif ('$before' === $key) {
            foreach ($value as $k => $v) {
                foreach ($v as $v1) {
                    $return = \Limepie\array_insert_before($return, $k, $v1);
                }
            }
        } elseif ('$change' === $key) {
            foreach ($value as $k => $v) {
                $return[$k] = $return[$k] + $v;
            }
        }
    }

    return $return;
}

function yml_parse_file($file, \Closure $callback = null)
{
    $filepath = \Limepie\stream_resolve_include_path($file);

    if ($filepath) {
        $basepath = \dirname($filepath);
        $spec     = \yaml_parse_file($filepath);

        $data = \Limepie\refparse($spec, $basepath);

        if(true === isset($callback) && $callback) {
            return $callback($data);
        }
        return $data;
    }

    throw new \Limepie\Exception('"' . $file . '" file not found');
}
/**
 * recursive array를 unique key로 merge
 *
 * @param array $array1 초기 배열
 * @param array $array2 병합할 배열
 *
 * @return array
 */
function array_merge_recursive_distinct(array $array1, array $array2) : array
{
    $merged = $array1;

    foreach ($array2 as $key => $value) {
        if (
            true === \is_array($value)
            && true === isset($merged[$key])
            && true === \is_array($merged[$key])
        ) {
            $merged[$key] = \Limepie\array_merge_recursive_distinct($merged [$key], $value);
        } else {
            $merged[$key] = $value;
        }
    }

    return $merged;
}

function array_key_flatten($array) {
    if(!isset($keys) || !is_array($keys)) {
        $keys = array();
    }
    foreach($array as $key => $value) {
        $keys[] = $key;
        if(is_array($value)) {
            $keys = array_merge($keys,\Limepie\array_key_flatten($value));
        }
    }
    return $keys;
}
function array_value_flatten($array) {
    if(!isset($values) || !is_array($values)) {
        $values = array();
    }
    foreach($array as $key => $value) {
        if(is_array($value)) {
            $values = array_merge($values,\Limepie\array_values_flatten($value));
        } else {
            $values[] = $value;
        }
    }
    return $values;
}

function array_flattenx($items)
{
    if (! is_array($items)) {
        return [$items];
    }

    return array_reduce($items, function ($carry, $item) {
        return array_merge($carry, array_flatten($item));
    }, []);
}

function array_mix(array $a, array $b) : array
{
    if (2 < \func_num_args()) {
        $args = \func_get_args();
        $base = \array_shift($args);

        foreach ($args as $arg) {
            $base = \Limepie\array_merge_recursive_distinct($base, $arg);
        }

        return $base;
    }

    return \Limepie\array_merge_recursive_distinct($a, $b);
}
/**
 * time으로부터 지난 시간을 문자열로 반환
 *
 * @param string|int $time  시간으로 표현가능한 문자열이나 숫자
 * @param int        $depth 표현 깊이
 *
 * @return string
 */
function time_ago($time, int $depth = 1) : string
{
    if (true === \is_string($time)) {
        $time = \strtotime($time);
    }
    $time   = \time() - $time;
    $time   = (1 > $time) ? 1 : $time;
    $tokens = [
        31536000 => 'year',
        2592000  => 'month',
        604800   => 'week',
        86400    => 'day',
        3600     => 'hour',
        60       => 'min', //ute
        1        => 'sec', //ond
    ];
    $parts = [];

    foreach ($tokens as $unit => $text) {
        if ($time < $unit) {
            continue;
        }
        $numberOfUnits = \floor($time / $unit);
        $parts[]       = $numberOfUnits . ' ' . $text . ((1 < $numberOfUnits) ? 's' : '');

        if (\count($parts) === $depth) {
            return \implode(' ', $parts);
        }
        $time -= ($unit * $numberOfUnits);
    }

    return \implode(' ', $parts);
}

/**
 * 숫자를 읽기쉬운 문자열로 변환
 *
 * @param $bytes
 * @param $decimals
 *
 * @return string
 */
function readable_size($bytes, $decimals = 2) : string
{
    $size   = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
    $factor = \floor((\strlen((string) $bytes) - 1) / 3);

    return \sprintf("%.{$decimals}f", $bytes / \pow(1024, $factor)) . @$size[$factor];
}

/**
 * formatting ISO8601MICROSENDS date
 *
 * @param float $float microtime
 *
 * @return string
 */
function iso8601micro(float $float) : string
{
    $date = \DateTime::createFromFormat('U.u', $float);
    $date->setTimezone(new \DateTimeZone('Asia/Seoul'));

    return $date->format('Y-m-d\TH:i:s.uP');
}

/**
 * env to array
 *
 * @param string $envPath
 */
function env_to_array(string $envPath) : array
{
    $variables = [];
    $lines     = \explode("\n", \trim(\file_get_contents($envPath)));

    if ($lines) {
        foreach ($lines as $line) {
            if ($line) {
                [$key, $value]   = \explode('=', $line, 2);
                $variables[$key] = \trim($value, '"\'');
            }
        }
    }

    return $variables;
}

/**
 * file 인지
 *
 * @param array $array
 * @param bool  $isMulti
 *
 * @return bool
 */
function is_file_array($array = [], $isMulti = false) : bool
{
    if (true === \is_array($array)) {
        if (
            true === isset($array['name'])
            && true === isset($array['type'])
            && true === isset($array['tmp_name'])
            && true === isset($array['error'])
            && true === isset($array['size'])
        ) {
            return true;
        }

        if (true === $isMulti) {
            foreach ($array as $file) {
                if (
                    true === isset($file['name'])
                    && true === isset($file['type'])
                    && true === isset($file['tmp_name'])
                    && true === isset($file['error'])
                    && true === isset($file['size'])
                ) {
                    return true;
                }
            }
        }
    }

    return false;
}

function get_language() : string
{
    $locale = \Limepie\Cookie::get(\Limepie\Cookie::getKeyStore('locale'));

    return \explode('_', $locale)[0];

    return $_COOKIE['client-language'] ?? 'ko';
}

function mkdir($dir)
{
    if (false === \is_file($dir)) {
        $dirs       = \explode('/', $dir);
        $createPath = '';

        for ($dirIndex = 0, $dirCount = \count($dirs); $dirIndex < $dirCount; $dirIndex++) {
            $createPath .= $dirs[$dirIndex] . '/';

            if (false === \is_dir($createPath)) {
                if (false === \mkdir($createPath)) {
                    throw new \Exception('cannot create asserts directory <b>' . $createPath . '</b>');
                }
                \chmod($createPath, 0777);
            }
        }
    }
}

function is_boolean_typex($var)
{
    $result = \Peanut\is_boolean_type2($var);

    \ob_start(); // 출력 버퍼링을 켭니다
    echo \var_dump($var);
    $tmp = \ob_get_contents(); // 출력 버퍼의 내용을 반환
    \ob_end_clean();

    \pr($var, $result, $tmp);

    return $result;
}

function is_boolean_type($var)
{
    if (true === \is_int($var)) {
        return true;
    } elseif (true === \is_numeric($var)) {
        return true;
    } elseif (true === \is_bool($var)) {
        return true;
    } elseif (true === \is_array($var)) {
        return false;
    }

    switch (\strtolower($var)) {
        case '1':
        case 'true':
        case 'on':
        case 'yes':
        case 'y':
        case '0':
        case 'false':
        case 'off':
        case 'no':
        case 'n':
        case '':
            return true;
        default:
            return false;
    }
}

// https://stackoverflow.com/questions/6311779/finding-cartesian-product-with-php-associative-arrays
function cartesian(array $input) : array
{
    $result = [[]];

    foreach ($input as $key => $values) {
        $append = [];

        foreach ($values as $value) {
            foreach ($result as $data) {
                $append[] = $data + [$key => $value];
            }
        }
        $result = $append;
    }

    return $result;
}

function array_cross($arrays)
{
    $result = [[]];

    foreach ($arrays as $property => $propertyValues) {
        $tmp = [];

        foreach ($result as $resultItem) {
            foreach ($propertyValues as $propertyKey => $propertyValue) {
                $tmp[] = $resultItem + [$property => $propertyValue];
                //$tmp[] = \array_merge($resultItem, [$propertyValue]);
                //$tmp[] = $resultItem + array($propertyKey => $propertyValue);
            }
        }
        $result = $tmp;
    }

    return $result;
}

/**
 * Generate a unique ID
 *
 * @param int $length
 *
 * @return string
 */
function uniqid(int $length = 13) : string
{
    if (true === \function_exists('random_bytes')) {
        $bytes = \random_bytes((int) \ceil($length / 2));
    } elseif (true === \function_exists('openssl_random_pseudo_bytes')) {
        $bytes = \openssl_random_pseudo_bytes((int) \ceil($length / 2));
    } else {
        $bytes = \md5(\mt_rand());
    }

    return \substr(\bin2hex($bytes), 0, $length);
}

function genRandomString($length = 5)
{
    // $char = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    // $char .= 'abcdefghijklmnopqrstuvwxyz';
    // $char .= '0123456789';
    $char = 'abcdefghjkmnpqrstuvwxyz';
    $char .= '23456789';
    $result = '';
    for ($i = 0; $i < $length; $i++) {
        $result .= $char[\mt_rand(0, \strlen($char) - 1)];
    }

    return $result;
}

function decamelize($word)
{
    return \preg_replace_callback(
      '/(^|[a-z])([A-Z])/',
      function($m) {
          return \strtolower(\strlen($m[1]) ? "{$m[1]}_{$m[2]}" : "{$m[2]}");
      },
      $word
  );
}

function camelize($word)
{
    return \preg_replace_callback(
      '/(^|_|-)([a-zA-Z]+)/',
      function($m) {
          return \ucfirst(\strtolower("{$m[2]}"));
      },
      $word
  );
}

function array_extract($arrays, $key, $index = null)
{
    $return = [];

    foreach ($arrays as $i => $value) {
        if(true === isset($index)) {
            if(true === is_array($index)) {
                $tmp = $value;
                foreach($index as $k1) {
                    $tmp = $tmp[$k1];
                }
                $i1 = $tmp;
            } else {
                $i1 = $index;
            }
        } else {
            $i1 = $i;
        }
        if(true === is_array($key)) {
            $tmp = $value;
            foreach($key as $k1) {
                $tmp = $tmp[$k1];
            }
            $return[$i1] = $tmp;

        } else {
            $return[$i1] = $value[$key];
        }
    }

    return $return;
}

function file_array_flatten($list, $prefix = '')
{
    $result = [];

    foreach ($list as $name => $value) {
        if (true === \is_array($value)) {
            $newPrefix = ($prefix) ? $prefix . '[' . $name . ']' : $name;

            if (true === \Limepie\is_file_array($value, false)) {
                $result[$newPrefix] = $value;
            } else {
                $result = $result + \Limepie\file_array_flatten($value, $newPrefix);
            }
        }
    }

    return $result;
}

function array_flatten_get($data, $flattenKey)
{
    $keys = \explode('[', \str_replace(']', '', $flattenKey));

    foreach ($keys as $key) {
        if (true === isset($data[$key])) {
            $data = $data[$key];
        } else {
            return false;
        }
    }

    return $data;
}

function array_flatten_put($data, $flattenKey, $value)
{
    $keys = \explode('[', \str_replace(']', '', $flattenKey));
    $d    = &$data;

    foreach ($keys as $key) {
        if (true === isset($d[$key])) {
            $d = &$d[$key];
        } else {
            throw new \Exception('not found key');
        }
    }
    $d = $value;

    return $data;
}

function array_flatten_remove($data, $flattenKey)
{
    $keys = \explode('[', \str_replace(']', '', $flattenKey));
    $d    = &$data;

    foreach ($keys as $key) {
        if (true === isset($d[$key])) {
            $d = &$d[$key];
        } else {
            throw new \Exception('not found key');
        }
    }
    unset($d);

    return $data;
}

function guid($l = 10)
{
    $str = '';
    for ($x = 0; $x < $l; $x++) {
        $str .= \substr(\str_shuffle('0123456789abcdefghijklmnopqrstuvwxyz'), 0, 1);
    }

    return $str;
}

function is_ajax()
{
    return true === isset($_SERVER['HTTP_X_REQUESTED_WITH'])
           && ('xmlhttprequest' === \strtolower(\getenv('HTTP_X_REQUESTED_WITH')));
}

function is_cli() : bool
{
    return 'cli' === \php_sapi_name();
}

function uuid(int $type = \UUID_TYPE_TIME) : string
{
    return \uuid_create($type);
}

function stream_resolve_include_path($filename)
{
    $includePaths = \explode(\PATH_SEPARATOR, \get_include_path());

    \array_unshift($includePaths, '');

    foreach ($includePaths as $path) {
        if ('.' === $path) {
            continue;
        }
        $includeFilename = ($path ? $path . '/' : '') . $filename;

        if (true === \file_exists($includeFilename)) {
            return \realpath($includeFilename);
        }
    }

    return false;
}

// https://stackoverflow.com/questions/594112/matching-an-ip-to-a-cidr-mask-in-php-5
function cidr_match($ip, $cidr)
{
    $outcome = false;
    $pattern = '/^(([01]?\d?\d|2[0-4]\d|25[0-5])\.){3}([01]?\d?\d|2[0-4]\d|25[0-5])\/(\d{1}|[0-2]{1}\d{1}|3[0-2])$/';

    if (\preg_match($pattern, $cidr)) {
        [$subnet, $mask] = \explode('/', $cidr);

        if (\ip2long($ip) >> (32 - $mask) === \ip2long($subnet) >> (32 - $mask)) {
            $outcome = true;
        }
    }

    return $outcome;
}

function url()
{
    $request = Di::get('request');

    return $request->getUrl();
}

function decimal($number) : float
{
    // $tmp = new \Decimal\Decimal((string) $number);

    // return $tmp->trim();
    $parts  = \explode('.', $number);
    $result = $parts[0];

    if (true === isset($parts[1])) {
        if ($r = \rtrim($parts[1], '0')) {
            $result .= '.' . $r;
        }
    }

    return (float) $result;
}

function number_format($number)
{
    //$stripzero = sprintf('%g',$number);
    if (0 < \strlen((string) $number)) {
        $parts  = \explode('.', $number);
        $result = \number_format((int) $parts[0]);

        if (true === isset($parts[1])) {
            if ($r = \rtrim($parts[1], '0')) {
                $result .= '.' . $r;
            }
        }

        return $result;
    }

    return 0;
}
function array_insert(&$array, $position, $insert)
{
    if (\is_int($position)) {
        \array_splice($array, $position, 0, $insert);
    } else {
        $pos   = \array_search($position, \array_keys($array), true);
        $array = \array_merge(
            \array_slice($array, 0, $pos),
            $insert,
            \array_slice($array, $pos)
        );
    }
}

function count($target)
{
    if ($target instanceof \RecursiveIteratorIterator) {
        return \iterator_count($target);
    }

    return \count($target);
}
