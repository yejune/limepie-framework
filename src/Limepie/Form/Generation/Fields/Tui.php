<?php declare(strict_types=1);

namespace Limepie\Form\Generation\Fields;

class Tui extends \Limepie\Form\Generation\Fields
{
    public static function write($key, $property, $value)
    {
        $value = (string) $value;
        $id    = \uniqid();

        if (0 === \strlen($value) && true === isset($property['default'])) {
            $value = (string) $property['default'];
        }
        $default = $property['default'] ?? '';
        $rows    = $property['rows']    ?? 5;

        $fileserver = $property['fileserver']    ?? '';
        $class      = $property['element_class'] ?? '';
        $linkcss    = $property['linkcss']       ?? '';

        if ($linkcss) {
            $linkcss = '<link rel="stylesheet" href="' . $linkcss . '"></link>';
        }

        if (true === isset($property['preview']) && $property['preview']) {
            $previewStyle = $property['preview'];
        } else {
            $previewStyle = 'vertical';
        }

        if (true === isset($property['edit']) && $property['edit']) {
            $editStyle = $property['edit'];
        } else {
            $editStyle = 'wysiwyg';
        }

        $html = <<<EOT
        <link rel="stylesheet" href="https://uicdn.toast.com/tui-editor/latest/tui-editor.css"></link>
        <link rel="stylesheet" href="https://uicdn.toast.com/tui-editor/latest/tui-editor-contents.css"></link>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.48.4/codemirror.css"></link>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/9.12.0/styles/github.min.css"></link>
        <script src="https://uicdn.toast.com/tui-editor/latest/tui-editor-Editor-full.js"></script>

        {$linkcss}
        <textarea class="form-control d-none" id="textarea{$id}" name="{$key}">{$value}</textarea>

        <div id="tui{$id}" class="form-control {$class}"  style="display:block; width: 100%;">{$value}</div>
        <style>
        #tui{$id} .te-mode-switch-section {
        //  display: none !important;
        //  height: 0;
        }
        #tui{$id} .tui-editor-contents h1 {
            margin: 15px 0 15px 0;
        }

        #tui{$id} .tui-editor-defaultUI-toolbar {
            padding: 0 5px;
        }

        #tui{$id} .te-md-container .te-preview {
            padding: 0 5px;
        }
        </style>
<script>
$(function() {

    var editor = new tui.Editor({
        el: document.querySelector('#tui{$id}'),
        previewStyle: '{$previewStyle}',
        width: '100%',
        height: 'auto',
        initialEditType: '{$editStyle}',
        events: {
            change: function() {
                $('#textarea{$id}').val(editor.getHtml());
                $('#textarea{$id}').change();
            }
        },
        hooks: {
            'addImageBlobHook': function(blob, callback) {
                var formData = new FormData();
                formData.append('image', blob);
                $.ajax({
                    url: "{$fileserver}",
                    enctype: 'multipart/form-data',
                    data: formData,
                    dataType : 'json',
                    contentType: false,
                    processData: false,
                    cache: false,
                    type: 'POST',
                    success: function(response){
                        callback(response.url, '');
                        return false;
                    },
                    error: function(e) {
                    }
                });
            }
        },
        exts: ['scrollSync']
      });

});

</script>
EOT;

        return $html;
    }

    public static function read($key, $property, $value)
    {
        $value = \nl2br((string) $value);
        $html  = <<<EOT
        {$value}

EOT;

        return $html;
    }
}
