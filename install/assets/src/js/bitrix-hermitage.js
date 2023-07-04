BX.ready(() => {
    $(window).on('load', function () {
        if (!$('#bx-panel').length) return;
        // console.log($.fn);


        const $body = $('body');


        $body.append(
            '<span id="uplab-edit-btn" ' +
            '      style="border-radius:2px;overflow:hidden;display:inline-block;z-index:9999;position:absolute">' +
            '   <span class="bx-context-toolbar">' +
            '       <span class="bx-context-toolbar-inner" style="padding: 0 10px !important;">' +
            '           <span class="bx-content-toolbar-default">' +
            '               <span class="bx-context-toolbar-button-underlay"></span>' +
            '               <span class="bx-context-toolbar-button-wrapper">' +
            '                   <span class="bx-context-toolbar-button" title="">' +
            '                       <span class="bx-context-toolbar-button-inner">' +
            '                           <a href="javascript: void(0)" style="white-space: nowrap!important;">' +
            '                               <span class="bx-context-toolbar-button-icon bx-context-toolbar-edit-icon"></span>' +
            '                               <span class="bx-context-toolbar-button-text">Изменить</span>' +
            '                           </a>' +
            '                       </span>' +
            '                   </span>' +
            '               </span>' +
            '           </span>' +
            '       </span>' +
            '   </span>' +
            '</span>');


        $body.append('<style>' +
            '[data-hermitage-link] { ' +
            '   z-index: 999 !important;' +
            '   pointer-events: all !important;' +
            '   min-height: 50px;' +
            '}' +
            '[data-hermitage--hovered=true] { ' +
            '   box-shadow: inset 0 0 0 1px #777F8B, inset 0 0 0 2px rgba(255, 255, 255, 0.5) !important;  ' +
            '}' +
            '</style>');


        const $editBtn = $('#uplab-edit-btn').hide();
        let editBtnTimeout = 0;
        let isEditHovered = false;
        let editElement = $();


        const setHideTimeout = function () {
            editBtnTimeout = setTimeout(function () {
                console.log(isEditHovered);
                if (isEditHovered === true) {
                    setHideTimeout();
                } else {
                    $editBtn.fadeOut();
                    clearTimeout(editBtnTimeout);
                    editElement = $();
                }
            }, 500);
        };


        $(document).on('mouseenter, mousemove', '[data-hermitage-link][data-hermitage-link!=""]', function (event) {
            const $this = $(event.target).closest('[data-hermitage-link]');
            // const $this = $(this);
            const offset = $this.offset();

            // console.log($this.offset());

            clearTimeout(editBtnTimeout);

            editElement = $this;
            isEditHovered = false;

            $('[data-hermitage--hovered]').attr('data-hermitage--hovered', false);
            editElement.attr('data-hermitage--hovered', true);

            $editBtn.show().css({
                top: offset.top,
                left: offset.left,
                transform: offset.top < 85 ? 'none' : 'translateY(-100%)'
            });
        });


        $(document).on('mouseleave', '[data-hermitage-link][data-hermitage-link!=""]', function () {
            setHideTimeout();
            isEditHovered = false;
            editElement && editElement.length && editElement.attr('data-hermitage--hovered', false);
        });


        $(document).on('mouseenter', '#uplab-edit-btn', function () {
            isEditHovered = true;
            editElement && editElement.length && editElement.attr('data-hermitage--hovered', true);
        });


        $(document).on('mouseleave', '#uplab-edit-btn', function () {
            isEditHovered = false;
            editElement && editElement.length && editElement.attr('data-hermitage--hovered', false);
        });


        $(document).on('click', '#uplab-edit-btn', function (event) {
            event.preventDefault();

            if (!editElement || !editElement.length) {
                $(this).hide();
                return false;
            }

            // noinspection JSUnresolvedFunction
            (new BX.CAdminDialog({
                content_url: editElement.data('hermitage-link'),
                height: Math.max(500, window.innerHeight - 400),
                width: Math.max(800, window.innerWidth - 400),
            })).Show()
        });
    });
});