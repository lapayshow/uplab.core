$(function () {
    'use strict';

    $(document)
        .on('submit', '.subscribe__form', function (ev) {
            ev.preventDefault();

            var $form = $(this);
            var $email = $form.find('input[name=email]');

            BX.ajax.runComponentAction(
                'fesco:subscribe.add',
                'subscribe',
                {
                    mode: 'class',
                    data: {
                        email: $form.find('input[name=email]').val()
                    }
                }
            )
                .then(function (response) {
                    if (response.status === 'success') {
                        $form.next('.js-other-text').show();
                        $form.find('input[name=email]').val('');
                    }
                })
                .catch(function (response) {
                    if (response.status === 'error' && response.errors.length > 0) {
                        if (response.status === 'error' && response.errors.length > 0) {
                            if (typeof $email[0].pristine !== 'undefined') {
                                $email[0].pristine.self.addError($email[0], response.errors[0].message);
                            }
                        }
                    }
                });
        })
        .on('focus', '.subscribe__form input[name=email]', function () {
            $(this)
                .parents('form')
                .next('.js-other-text')
                .hide(500);
        });

});
