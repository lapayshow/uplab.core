function UCTB_OnCustomVisualEditorInit(arParams) {
    console.log('UCTB_OnCustomVisualEditorInit', arParams);

    // noinspection JSUnresolvedVariable
    var $cont = $(arParams.oCont),
        $input = $(arParams.oInput),
        data = JSON.parse(arParams.data);

    // В качестве имени инпута устанавливаем имя уже имеющегося элемента
    // для результата
    var inputCode = $input.attr('name');
    var inputCodeType = inputCode + '_TYPE';

    // Разворачиваем окошко на весь экран
    setTimeout(function () {
        $cont.closest('.bx-core-window').find('.bx-core-adm-icon-expand').click();
    }, 100);

    // noinspection ES6ModulesDependencies
    $.post(data.phpScript, {
        html: $input.val(),
        inputCode: inputCode,
        inputCodeType: inputCodeType,
        sessid: (window.bxSession.sessid || window.bxsessid)
    }, function (res) {
        // Заменяем все содержимое контейнера новым HTML,
        // имеющийся инпут заменяется на визуальный редактор
        $cont.html(res);
    });
}
