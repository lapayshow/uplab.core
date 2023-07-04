window.canvasAreaDraw = require('./_vendor/jquery.canvasAreaDraw.min');

window.initCanvasResize = (targetNodeSelector) => {

    let TIMEOUT;
    let ITERATION_COUNTER = 0;

    const TIMEOUT_TIME = 50;
    const MAX_RECURSION_DEPTH = 15;

    const targetNode = document.querySelector(targetNodeSelector);
    if (!targetNode) return;

    const timeoutFunction = () => {
        const $canvas = $(targetNode).find('canvas');

        // Если функция была вызвана слишком рано, то либо канваса, либо его размеров еще нет,
        // поэтому мы заводим новый таймаут и будем это делать до тех пор,
        // пока интересующие нас условия не возникнут.
        const resetTimeout = () => {
            console.log('resize canvas ... early');

            if (ITERATION_COUNTER < MAX_RECURSION_DEPTH) {
                ITERATION_COUNTER++;
                TIMEOUT = setTimeout(timeoutFunction, TIMEOUT_TIME);
            }
        };

        if (!$canvas.length) {
            return resetTimeout();
        }

        const w = $canvas.attr('width');
        const h = $canvas.attr('height');

        // Если функция была вызвана сли
        if (!w || !h) {
            return resetTimeout();
        }

        let newW = Math.max(900, window.innerWidth - 420);
        let k1 = newW / w;

        let newH = Math.max(600, window.innerHeight - 150);
        let k2 = newH / h;

        let k;

        if (k1 < k2) {
            k = k1;
            newH = h * k;
        } else {
            k = k2;
            newW = w * k;
        }

        $canvas.css({
            transform: `scale(${k})`,
            transformOrigin: '0 0',
            position: 'absolute',
            marginTop: '50px',
        });

        console.log(`resize canvas ... to ${newW}x${newH}`);

        $(targetNode).find('.canvas-placeholder').css({
            width: newW,
            height: newH,
        });

        observer.disconnect();
    };

    // Создаем слушатель изменений, при любом изменении в DOM-дереве
    // внутри нужного контейнера "заводим" таймаут на изменение размеров канваса.
    // При этом при каждом следующем изменении предыдущий таймаут сбрасывается.
    // Таким образом, должен отработать только последний таймаут
    const observer = new MutationObserver(() => {
        clearTimeout(TIMEOUT);
        TIMEOUT = setTimeout(timeoutFunction, TIMEOUT_TIME);
    });

    // Start observing the target node for configured mutations
    observer.observe(
        targetNode,
        {
            attributes: true,
            childList: true,
            subtree: true
        }
    );

};
