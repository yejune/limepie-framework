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

        $upload = $property['upload'] ?? 'upload';
        $id = uniqid();
        $html = <<<EOT
        <link rel="stylesheet" href="https://uicdn.toast.com/tui-editor/latest/tui-editor.css"></link>
        <link rel="stylesheet" href="https://uicdn.toast.com/tui-editor/latest/tui-editor-contents.css"></link>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.48.4/codemirror.css"></link>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/9.12.0/styles/github.min.css"></link>
        <script src="https://uicdn.toast.com/tui-editor/latest/tui-editor-Editor-full.js"></script>
        <textarea class="form-control d-none" id="textarea{$id}" name="{$key}" data-default="{$default}" rows="{$rows}">{$value}</textarea>

        <div id="tui{$id}" class="form-control"  style="display:block; width: 100%" data-default="{$default}" rows="{$rows}"></div>
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
        height: '300px',
        initialEditType: 'markdown',
        initialValue: $('#textarea{$id}').val(),
        events: {
            change: function() {
                $('#textarea{$id}').val(editor.getMarkdown())
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
