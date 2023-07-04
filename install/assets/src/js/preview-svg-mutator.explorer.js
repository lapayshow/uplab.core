(() => {
    const loadCss = require('./_include/loadCss');
    loadCss('/bitrix/css/uplab.core/preview-svg-mutator.css');
})();

if (arFDFiles) {
    for (let i in arFDFiles) {
        if (!arFDFiles.hasOwnProperty(i)) continue;

        if (arFDFiles[i] && arFDFiles[i].length) {
            for (let j = 0, k = arFDFiles[i].length; j < k; j++) {
                if (arFDFiles[i][j].path) {
                    if (
                        !arFDFiles[i][j].tmb_src &&
                        (
                            arFDFiles[i][j].path.match(/\.svg$/)
                            // || arFDFiles[i][j].path.match(/\.webp$/)
                        )
                    ) {
                        arFDFiles[i][j].tmb_src =
                            '/bitrix/tools/uplab.core_resize.php?p=' +
                            arFDFiles[i][j].path;
                    }
                }
            }
        }
    }
}
