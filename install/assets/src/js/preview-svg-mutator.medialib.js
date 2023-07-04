(() => {
    const loadCss = require('./_include/loadCss');
    loadCss('/bitrix/css/uplab.core/preview-svg-mutator.css');
})();

for (let i in window.MLItems) {
    if (!window.MLItems.hasOwnProperty(i)) continue;
    window.MLItems[i].forEach((item, j) => {
        if (
            window.MLItems[i][j] &&
            window.MLItems[i][j].path &&
            !window.MLItems[i][j].thumb_path &&
            (
                window.MLItems[i][j].path.match(/\.svg$/)
                // || window.MLItems[i][j].path.match(/\.webp$/)
            )
        ) {
            window.MLItems[i][j].thumb_path =
                '/bitrix/tools/uplab.core_resize.php?p=' +
                window.MLItems[i][j].path;
        }
    });
}
