<?php declare(strict_types=1);

namespace Limepie\Form\Generation\Fields;

class Group extends \Limepie\Form\Generation\Fields
{
    public static function write(string $key, array $specs, $data)
    {
        $innerhtml = '';
        $script    = '';
        $html      = '';

        foreach ($specs['properties'] ?? [] as $propertyKey => $propertyValue) {
            if (false === isset($propertyValue['type'])) {
                throw new \Exception('group ' . $key . ' ' . $propertyKey . ' error');
            }
            $method   = __NAMESPACE__ . '\\' . \ucfirst($propertyValue['type']);
            $elements = '';
            $index    = 0;

            $fixPropertyKey = $propertyKey;
            $isArray        = false;
            $strip          = false;

            if (false !== \strpos((string) $fixPropertyKey, '[]')) {
                //\pr($fixPropertyKey, $propertyValue['multiple'] ?? false);
                $fixPropertyKey = \str_replace('[]', '', $fixPropertyKey);
                $isArray        = true;
                $strip          = true;
            }
            $isArray = $propertyValue['multiple'] ?? false;

            $propertyName = $fixPropertyKey;

            if ($key) {
                $propertyName = $key . '[' . $fixPropertyKey . ']';
            }

            if (!$isArray && $strip) {
                $propertyName = $propertyName . '[]';
            }
            $dotKey = \str_replace(['[', ']'], ['.', ''], $propertyName);
            // pr(static::$reverseConditions, $dotKey);
            // pr( ?? '');

            $aData = '';

            if (true === \is_array($data) && $fixPropertyKey) {
                $aData = $data[$fixPropertyKey] ?? '';
            }

            $isMultiple = true === isset($propertyValue['multiple']) ? true : false;
            $isCollapse = true === isset($propertyValue['collapse']) ? true : false;

            if (true === static::isValue($aData)) {
                if (false === $isArray) { // 배열이 아닐때
                    $parentId = static::getUniqueId();
                    $elements .= static::addElement(
                        $method::write($propertyName, $propertyValue, $aData),
                        $index,
                        $isMultiple,
                        $isCollapse,
                        static::isValue($aData),
                        $parentId
                    );
                } else {
                    foreach ($aData as $aKey => $aValue) {
                        $index++;
                        // 배열 키는 바꾸면 안됨. 파일업로드 변경 여부 판별때문에
                        if (17 === \strlen((string) $aKey)) {
                            $parentId = $aKey;
                        } else {
                            $parentId = static::getUniqueId();
                        }
                        $elements .= static::addElement(
                            $method::write($propertyName . '[' . $parentId . ']', $propertyValue, $aData[$aKey]),
                            $index,
                            $isMultiple,
                            $isCollapse,
                            static::isValue($aData[$aKey]),
                            $parentId
                        );
                    }
                }
            } else {
                //if (false === isset($parentId)) {
                $parentId = static::getUniqueId();
                //}

                if (false === $isArray) {
                    // TODO: default가 array면 error
                    $aData = $propertyValue['default'] ?? '';

                    $elements .= static::addElement(
                        $method::write($propertyName, $propertyValue, $aData),
                        $index,
                        $isMultiple,
                        $isCollapse,
                        static::isValue($aData),
                        $parentId
                    );
                } else {
                    if (true === isset($propertyValue['default'])) {
                        if (true === \is_array($propertyValue['default'])) {
                            $aData = $propertyValue['default'];
                        } else {
                            $aData = [$propertyValue['default']];
                        }
                    } else {
                        $aData = ['' => ''];
                    }

                    foreach ($aData as $aKey => $aValue) {
                        $index++;
                        // 배열 키는 바꾸면 안됨. 파일업로드 변경 여부 판별때문에
                        if (17 === \strlen((string) $aKey)) {
                            $parentId = $aKey;
                        } else {
                            if ('multichoice' === $propertyValue['type']) {
                                $parentId = '';
                            } else {
                                $parentId = static::getUniqueId();
                            }
                        }
                        $elements .= static::addElement(
                            $method::write($propertyName . '[' . $parentId . ']', $propertyValue, $aData[$aKey]),
                            $index,
                            $isMultiple,
                            $isCollapse,
                            static::isValue($aData[$aKey]),
                            $parentId
                        );
                    }
                    /*
                                        $index++;

                                        $elements .= static::addElement(
                                            $method::write($propertyName . '[' . $parentId . ']', $propertyValue, $aData),
                                            $index,
                                            $isMultiple,
                                            $isCollapse,
                                            static::isValue($aData),
                                            $parentId
                                        );
                    */
                }
            }

            $title = '';

            if (true === isset($propertyValue['label'])) {
                if (true === \is_array($propertyValue['label'])) {
                    if (true === isset($propertyValue['label'][static::getLanguage()])) {
                        $title = $propertyValue['label'][static::getLanguage()];
                    }
                } else {
                    $title = $propertyValue['label'];
                }
            }

            $description = '';

            if (true === isset($propertyValue['description'])) {
                if (true === \is_array($propertyValue['description'])) {
                    if (true === isset($propertyValue['description'][static::getLanguage()])) {
                        $description = $propertyValue['description'][static::getLanguage()];
                    }
                } else {
                    $description = $propertyValue['description'];
                }
            }

            $collapse = '';

            if (true === isset($propertyValue['collapse'])) {
                if (true === \Limepie\is_boolean_type($propertyValue['collapse'])) {
                    $collapse = 'hide';
                } else {
                    if (false === (bool) static::getValueByDot($aData, $propertyValue['collapse'])) {
                        //\var_dump((bool) static::getValue($data, $propertyValue['collapse']));
                        $collapse = 'hide';
                    }
                }
            }

            $collapse1 = '';

            // if (true === isset($propertyValue['collapse'])) {
            //     $target = $propertyValue['collapse'];

            //     if ($key) {
            //         $target = $key . '[' . $target . ']';
            //     }
            //     $collapse1 = '<i class="button-collapse glyphicon glyphicon-triangle-right" data-target="' . $target . '"></i> ';
            // }

            if (true === isset($propertyValue['collapse'])) {
                $collapse1 = static::arrow(static::isValue2($aData));
            }

            $titleHtml = '';

            $collapse2 = '';

            if (true === isset($propertyValue['collapse'])) {
                $collapse2 = 'label-collapse';
            }

            $addClass2 = '';

            // if (true === isset($propertyValue['collapse'])) {
            //     if ($data[$propertyValue['collapse']] ?? '') {
            //     } else {
            //         $addClass2 = ' collapse-element collapse-hide';
            //     }
            // }
            if (true === isset($propertyValue['collapse'])) {
                if (\is_string($propertyValue['collapse'])) {
                    //\pr($aData, $propertyValue['collapse'], $aData, $propertyValue['collapse']);
                }
                //\pr($aData, $propertyValue['collapse'], $aData, $propertyValue['collapse'], \Limepie\is_boolean_type($propertyValue['collapse']), \is_int($propertyValue['collapse']), \is_bool($propertyValue['collapse']));
                //\var_dump(static::isValue($aData));
                if (
                    false === \Limepie\is_boolean_type($propertyValue['collapse'])
                    && false === (bool) static::getValueByDot($aData, $propertyValue['collapse'])
                ) {
                    $collapse1 = static::arrow(false);
                    $addClass2 = ' collapse-element collapse-hide';
                } elseif (false === static::isValue2($aData)) {
                    $collapse1 = static::arrow(false);
                    $addClass2 = ' collapse-element collapse-hide';
                }
            }

            $addClass = '';

            if (true === isset($propertyValue['class'])) {
                $addClass = ' ' . $propertyValue['class'];
            } else {
                $addClass = ' ';
            }

            $addStyle = '';

            if (true === isset($propertyValue['style'])) {
                $addStyle = ' ' . $propertyValue['style'];
            } else {
                $addStyle = ' ';
            }

            if (true === isset($propertyValue['class_condition'])) {
                $conditions = $propertyValue['class_condition'];

                $conditionResult = false;

                foreach ($conditions['if'] as $conditionKey => $condition) {
                    if ('equeal' === $conditionKey) {
                    } elseif ('in' === $conditionKey) {
                        foreach ($condition as $ckey => $cvalue) {
                            $conditionValue = $data[$ckey] ?? '';
                            $tmp            = false;
                            \var_dump($conditionValue);

                            if (\in_array($conditionValue, \array_values($cvalue), $tmp)) {
                                $conditionResult = true;
                            } else {
                                $conditionResult = false;
                            }
                        }
                    }
                }

                if (false === $conditionResult) {
                    $addClass .= ' ' . $conditions['else'];
                } else {
                    $addClass .= ' ' . $conditions['then'];
                }
            }

//            $dotKey = str_replace(['[',']'],['.',''],$propertyName);

            $parts      = \explode('.', $dotKey);
            $dotParts   = [];
            $keyAsArray = [];

            foreach ($parts as $part) {
                if (1 === \preg_match('#__([^_]{13})__#', $part)) {
                    $keyAsArray[] = $part;
                    $dotParts[]   = '*';
                } else {
                    $dotParts[] = $part;
                }
            }
            $dotName = \implode('.', $dotParts);

            if (true === isset(static::$reverseConditions[$dotName])) {
                //console.log('aaaaatttt',$dotName, static::$reverseConditions[$dotName]);
                $condition = static::$reverseConditions[$dotName];

                foreach ($condition as $keyAs => $va1) {
                    $parts2      = \explode('.', $keyAs);
                    $dot2        = [];
                    $keyAsArray2 = $keyAsArray;

                    foreach ($parts2 as $part2) {
                        if ('*' === $part2) {
                            $keyAs3 = \array_shift($keyAsArray2);
                            $dot2[] = $keyAs3;
                        } else {
                            $dot2[] = $part2;
                        }
                    }
                    $keyAs2       = \implode('.', $dot2);
                    $valueResult1 = static::getValueByDot(static::$allData, $keyAs2);

                    if (null === $valueResult1) {
                        $valueResult1 = static::getDefaultByDot(static::$specs, $keyAs2);
                    }
                    // var_dump($valueResult1);
                    // pr($dotName, $keyAs2, $va1, $valueResult1);
                    if (true === isset($va1[$valueResult1])) {
                        if (false === ($va1[$valueResult1])) {
                            //return true;
                            $addClass .= ' d-none';
                        } else {
                            $addClass .= ' d-block';
                        }
                    } else {
                        //\pr($va1);

                        throw new \Exception(static::getNameByDot($keyAs) . ' value not found.');
                    }
                }
            }

            // if(true === isset(static::$reverseConditions[$dotKey])) {
            //     if(static::$reverseConditions[$dotKey]) {
            //         $addClass .= ' d-block';
            //     } else {
            //         $addClass .= ' d-none';
            //     }
            // }
            if ($title) {
                $titleHtml .= '<label class="' . $collapse2 . '">' . $collapse1 . $title . '</label>';
            }

            if ($description) {
                if (true === \is_array($description)) {
                    $title = '<div class="wrap-description">';
                    $title .= '<table class="table table-bordered description">';

                    foreach ($description as $dkey => $dvalue) {
                        $title .= '<tr><td>' . $dkey . '</td><td>' . $dvalue . '</td></tr>';
                    }
                    $title     .= '</table>';
                    $title     .= '</div>';
                    $titleHtml .= $title;
                } else {
                    $description = \preg_replace("#\*(.*)\n#", '<span class="bold">*$1</span>' . \PHP_EOL, $description);
                    $titleHtml .= '<p class="description">' . \nl2br($description) . '</p>';
                }
            }

            if ('hidden' === $propertyValue['type']) {
//                {$titleHtml}

                $innerhtml .= <<<EOT
                <div class="x-hidden">
                    {$elements}
                </div>
EOT;
            } elseif ('checkbox' === $propertyValue['type']) {
                $d = '';

                if ($description) {
                    $d = '<p class="description">' . \nl2br($description) . '</p>';
                }

                $innerhtml .= <<<EOT
                <div class="wrap-form-group{$addClass}" name="{$dotKey}.layer">
                    <div class="checkbox{$addClass2}">
                        <label>{$elements}</label>
                        {$d}
                    </div>
                </div>
EOT;
            } else {
                $sortableClass = '';

                if (true === isset($propertyValue['sortable'])) {
                    $sortableClass = 's' . \uniqid();
                }

                $innerhtml .= <<<EOT
                <div class="wrap-form-group{$addClass}" style="{$addStyle}" name="{$dotKey}.layer">
                    {$titleHtml}
                    <div class="form-group{$addClass2} {$sortableClass}">
                        {$elements}
                    </div>
                </div>
EOT;

                if ($sortableClass) {
                    $script .= <<<EOD
<script>
$(function() {
$(".{$sortableClass}").sortable({
opacity: 0.5,
axis: 'y'
});
});
</script>
EOD;
                }
                unset($parentId);
            }
            $fieldsetClass = ' ';

            if (true === isset($specs['fieldset_class'])) {
                $fieldsetClass = ' ' . $specs['fieldset_class'];
            }
            $style = '';

            if (true === isset($specs['style'])) {
                $style = "style='" . $specs['style'] . "'";
            }

            $html = <<<EOT
<div class='fieldset{$fieldsetClass} ' {$style}>
    {$innerhtml}
</div>
{$script}
EOT;
        }

        return $html;
    }

    public static function read(string $key, array $specs, $data)
    {
        //pr($key, $data);

        $innerhtml = '';

        foreach ($specs['properties'] as $propertyKey => $propertyValue) {
            $method   = __NAMESPACE__ . '\\' . \ucfirst($propertyValue['type']);
            $elements = '';
            $index    = 0;

            $fixPropertyKey = $propertyKey;
            $isArray        = false;

            if (false !== \strpos((string) $fixPropertyKey, '[]')) {
                $fixPropertyKey = \str_replace('[]', '', $fixPropertyKey);
                $isArray        = true;
            }
            $propertyName = $fixPropertyKey;

            if ($key) {
                $propertyName = $key . '[' . $fixPropertyKey . ']';
            }
            $aData = $data[$fixPropertyKey] ?? '';

            if ($aData) {
                if (false === $isArray) { // 배열일때
                    if (false === isset($parentId)) {
                        $parentId = static::getUniqueId();
                    }
                    $elements .= static::readElement(
                        $method::read($propertyName, $propertyValue, $aData),
                        $index
                    );
                } else {
                    foreach ($aData as $aKey => $aValue) {
                        $index++;

                        //if (false === isset($parentId)) {
                        $parentId = $aKey;
                        //}
                        $elements .= static::readElement(
                            $method::read($propertyName . '[' . $aKey . ']', $propertyValue, $aData[$aKey]),
                            $index
                        );
                    }
                }
            } else {
                if (false === $isArray) {
                    $elements .= static::readElement(
                        $method::read($propertyName, $propertyValue, $aData),
                        $index
                    );
                } else {
                    $index++;

                    if (false === isset($parentId)) {
                        $parentId = static::getUniqueId();
                    }

                    $elements .= static::readElement(
                        $method::read($propertyName . '[' . $parentId . ']', $propertyValue, $aData),
                        $index
                    );
                }
            }

            $language     = $propertyValue['label'][static::getLanguage()] ?? $key;
            $multipleHtml = true === isset($propertyValue['multiple']) ? static::getMultipleHtml($parentId) : '';
            $titleHtml    = '<label>' . $language . '</label>';

            if ('hidden' === $propertyValue['type']) {
                $innerhtml .= <<<EOT
                    {$elements}
EOT;
            } else {
                $innerhtml .= <<<EOT
                {$titleHtml}
                <div class="form-group">
                    {$elements}
                </div>
EOT;
            }
            unset($parentId);
        }
        $style = '';

        if (true === isset($propertyValue['style'])) {
            $style = "style='" . $propertyValue['style'] . "'";
        }

        $html = <<<EOT
<div class='fieldset'  {$style}>
    {$innerhtml}
</div>

EOT;

        return $html;
    }
}
