BX.ready(() => {
    window.sendBxClearCacheQuery = () => {
        // noinspection JSUnresolvedFunction
        BX.showWait();

        clearTimeout(window.__sendBxClearCacheQueryTimer);
        if (window.__sendBxClearCacheQueryPopup) {
            window.__sendBxClearCacheQueryPopup.Close();
        }

        BX.ajax.post(
            'php_command_line.php?lang=' + phpVars.LANGUAGE_ID + '&sessid=' + phpVars.bitrix_sessid,
            {
                query: 'var_export(BXClearCache(true));',
                result_as_text: 'y',
                ajax: 'y'
            },
            (res) => {
                BX.closeWait();

                const content =
                    (res || '') +
                    '<br><br><br><a href="javascript:sendBxClearCacheQuery();">Еще раз</a>';

                const parser = new DOMParser();
                const parsedDoc = parser.parseFromString(content, 'text/html');
                let h2 = '';
                let titleText = '';

                if (!parsedDoc) return;

                h2 = parsedDoc.querySelector('h2');

                if (h2) {
                    // noinspection JSUnresolvedVariable
                    titleText = h2.textContent;
                    // noinspection JSPrimitiveTypeWrapperUsage
                    h2.textContent = '';
                }

                // noinspection JSUnresolvedFunction
                window.__sendBxClearCacheQueryPopup = new BX.CDialog({
                    title: titleText,
                    content: parsedDoc.documentElement.innerHTML,
                    width: 400,
                    height: 200,
                });

                // noinspection JSUnresolvedFunction
                window.__sendBxClearCacheQueryPopup.Show();

                window.__sendBxClearCacheQueryTimer = setTimeout(function () {
                    // noinspection JSUnresolvedFunction
                    window.__sendBxClearCacheQueryPopup.Close();
                }, 3000);
            }
        );
    };
});
