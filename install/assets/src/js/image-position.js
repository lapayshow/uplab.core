BX.ready(() => {
    $(function () {
        const $item = $('.uplab-image-position');

        if ($item.height()) {
            setTimeout(draggable, 2000);
        } else {
            if ($item.find('img').length > 0) {
                draggable();
            } else {
                $item.find('img').load(function () {
                    draggable();
                });
            }
        }
    });

    function draggable() {
        const $items = $('.uplab-image-position');

        $items.each((i, item) => {
            const $item = $(item);
            const $input = $item.closest('td').find('input');
            const $image = $item.find('img');
            const defaultDotStyle = 'position:absolute;';

            // $item.css({
            //     width: $image.width(),
            //     height: $image.height()
            // });

            $item.append(
                `<div class="js-dot" style="${defaultDotStyle + $input.val()}">` +
                `  <div class="dot"></div>` +
                `</div>`
            );

            const $dot = $item.find('.js-dot');

            $dot.draggable({
                stop: () => {
                    const left = ((parseInt($dot.css('left'))) / $image.width() * 100).toFixed(4);
                    const top = ((parseInt($dot.css('top'))) / $image.height() * 100).toFixed(4);

                    $input.val(`left:${left}%;top:${top}%`);
                }
            });

            $input.on('change', (event) => {
                const $this = $(event.currentTarget);
                $dot.attr('style', defaultDotStyle + $this.val());
            });
        });
    }
});
