document.addEventListener('DOMContentLoaded', () => {

    $(document).on('submit', '[data-uplab-form-wrapper] form', function (event) {
        event.preventDefault();

        if (!BX) return;

        const $form = $(event.currentTarget);
        const $wrapper = $form.closest('[data-uplab-form-wrapper]');

        BX.showWait();

        const request = BX.ajax.runComponentAction('uplab.core:form', 'addFormResult', {
            mode: 'class',
            data: new FormData($form[0])
        });

        request.then((res) => {
            BX.closeWait();

            res = res || {};
            res.data = res.data || {};
            res.data.HTML = res.data.HTML || '';

            $wrapper.html(res.data.HTML);

            const events = BX.message["uplab.form:triggerEvents"];
            events && events.length && events.forEach((event) => {
                window.dispatchEvent(new CustomEvent(event));
            });

            $(window).trigger('init');
        });
    });

});
