<?php declare(strict_types=1);

namespace Limepie\Form\Generation\Fields;

class Tui extends \Limepie\Form\Generation\Fields
{
    public static function write($key, $property, $value)
    {
        $value = \htmlspecialchars((string) $value);

        if (0 === \strlen($value) && true === isset($property['default'])) {
            $value = \htmlspecialchars((string) $property['default']);
        }
        $default = $property['default'] ?? '';
        $rows    = $property['rows']    ?? 5;

        $fileserver = $property['fileserver'] ?? '';
        $class = $property['class'] ?? '';
        $linkcss = $property['linkcss'] ?? '';
        if($linkcss) {
            $linkcss = '<link rel="stylesheet" href="'.$linkcss.'"></link>';
        }
        $id = uniqid();
        $html = <<<EOT
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tui-editor/1.4.10/tui-editor.css"></link>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/tui-editor/1.4.10/tui-editor-contents.css"></link>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.48.4/codemirror.css"></link>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/9.12.0/styles/github.min.css"></link>
        {$linkcss}
        <script src="https://cdnjs.cloudflare.com/ajax/libs/tui-editor/1.4.10/tui-editor-Editor-full.js"></script>
        <textarea class="form-control d-none" id="textarea{$id}" name="{$key}">{$value}</textarea>
        <textarea class="form-control d-none" id="textarea{$id}_html" name="{$key}_html"></textarea>

        <div id="tui{$id}" class="form-control {$class}"  style="display:block; width: 100%"></div>
        <style>#tui{$id} .te-mode-switch-section {
         display: none !important;
         height: 0;
        }</style>
<script>
$(function() {

    var editor = new tui.Editor({
        el: document.querySelector('#tui{$id}'),
        previewStyle: 'vertical',
        width: '100%',
        height: 'auto',
        initialEditType: 'markdown',
        initialValue: $('#textarea{$id}').val(),
        events: {
            change: function() {
                $('#textarea{$id}').val(editor.getMarkdown())
                $('#textarea{$id}_html').val(editor.getHtml())
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
                    processData: false,
                    contentType: false,
                    cache: false,
                    type: 'POST',
                    success: function(response){
                        callback(response.data.url, '');
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
