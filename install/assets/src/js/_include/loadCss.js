const loadCss = (url, onSuccess = false, onError = false) => {
    if (document.querySelector(`link[href='${url}']`)) {
        console.log(`style [${url}] already loaded`);
        return;
    }

    const link = document.createElement('link');

    link.href = url;
    link.rel = 'stylesheet';
    document.body.append(link);

    link.onload = () => {
        console.log(`style [${url}] loaded`);

        if (onSuccess && typeof onSuccess === 'function') {
            onSuccess();
        }
    };
};

module.exports = loadCss;