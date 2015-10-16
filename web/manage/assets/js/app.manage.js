//初始化编辑器
function initEditor(){
    var option = {
        items: [
            'source', '|', 'undo', 'redo', '|', 'preview', 'print', 'template', 'code', 'cut', 'copy', 'paste',
            'plainpaste', 'wordpaste', '|', 'justifyleft', 'justifycenter', 'justifyright',
            'justifyfull', 'insertorderedlist', 'insertunorderedlist', 'indent', 'outdent', 'subscript',
            'superscript', 'clearhtml', 'quickformat', 'selectall', '|', 'fullscreen', '/',
            'formatblock', 'fontname', 'fontsize', '|', 'forecolor', 'hilitecolor', 'bold',
            'italic', 'underline', 'strikethrough', 'lineheight', 'removeformat', '|', 'image',
            'flash', 'media', 'insertfile', 'table', 'hr', 'emoticons', 'baidumap', 'pagebreak',
            'anchor', 'link', 'unlink'
        ],
        width: '1000px',
        height: '500px',
        minHeight: 500,
        minWidth: 800,
        uploadJson: '/upload/index',
        allowFileManager: false
    };
    window.editor = KindEditor.create('#editor_content', option);
}

//车标select 模板
function formatCarLogo (logo) {
  if (!logo.id) { return logo.text; }
  var _logo_html = $(
    '<span><img src="' + logo.element.value + '" style="width:20%;max-width:24px;" /> ' + logo.text + '</span>'
  );
  return _logo_html;
}


$(function () {
    $('input.file-input-img').fileinput({
        previewFileType: "image",
        browseClass: "btn btn-info btn-flat",
        browseLabel: " 选择",
        browseIcon: '<i class="fa fa-picture-o"></i>',
        showUpload: false,
        removeClass: "btn btn-danger btn-flat",
        removeLabel: " 删除",
        removeIcon: '<i class="fa fa-trash-o"></i>'
    });

    $('input[type="checkbox"].minimal, input[type="radio"].minimal').iCheck({
      checkboxClass: 'icheckbox_minimal-blue',
      radioClass: 'iradio_minimal-blue'
    });

    $('input[name="status"]').on('ifChecked', function(event){
        var _self = $(this);
        if (_self.val() == 'confirmed') {
            $('div.service-addition').each(function(){
                $(this).show();
            });
        }
        else {
            $('div.service-addition').each(function(){
                $(this).hide();
            });
        }
    });

    $('input.datepicker').datepicker({
        format: 'yyyy/mm/dd',
        language: 'zh-CN'
    });

    $('input.datetimepicker').datetimepicker({
        format: 'YYYY/MM/DD HH:mm',
        locale: 'zh-CN'
    });

    $("input.timepicker").timepicker({
        showInputs: false,
        minuteStep: 10,
        showSeconds: false,
        showMeridian: false
    });

    $('select.car_logo').select2({
        language: 'zh-CN',
        templateSelection: formatCarLogo,
        templateResult: formatCarLogo
    });

    $('select#tags').select2({
        language: 'zh-CN'
    });

    $('[data-toggle="confirmation"]').confirmation();

    initEditor();
});
