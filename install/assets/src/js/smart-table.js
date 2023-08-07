BX.ready(() => {
    $(document).on('click', '[data-clone]', function () {
        const $this = $(this);
        const SEL = $this.data('clone');
        const CLOSEST_SEL = $this.data('closest');

        let $row;

        if (CLOSEST_SEL) {
            $row = $this.closest(CLOSEST_SEL).find(SEL);
        } else {
            $row = $(SEL);
        }

        const $table = $row.closest('.up-edit-table');
        const $parent = $row.parent();
        const $clone = $row.clone().appendTo($parent);
        const LAST_INDEX = parseInt($table.attr('data-last-index') || 0);
        const index = Math.max($clone.index(), LAST_INDEX + 1);

        $table.attr('data-last-index', index);

        $clone.find('input:not([data-noclear],[type=checkbox],[type=hidden]), textarea:not([data-noclear])').val('');
        $clone.find('span:not([data-noclear])').text('');

        $clone.find('[data-tpl]').each((i, item) => {
            const $item = $(item);
            const name = item.name;
            const newName = $item.data('tpl').replace('__i__', index);

            if (item.id) {
                const oldId = item.id;
                const newId = [item.type, Date.now(), index].join('_');

                $clone.find(`[id="${oldId}"]`).attr('id', newId);
                $clone.find(`[for="${oldId}"]`).attr('for', newId);
            }

            $clone.find(`[name="${name}"]`).attr('name', newName)
        });
    });


    $(document).on('click', '.up-remove-btn', function () {
        const $this = $(this);
        const SEL = $this.data('remove');
        const $remove = $this.closest(SEL);

        $remove.remove();
    });


    $(document).on('click', '.up-table__btn_element', function () {
        const $this = $(this);
        const $input = $this.prev();
        const $watchInput = $('#' + $this.data('input'));
        const $caption = $this.find('~ span');
        const index = $this.parent().index();
        const changeEvent = 'change';

        $watchInput.off(changeEvent).on(changeEvent, function () {
            const $value = $(this);
            const $text = $value.find('~span');

            setTimeout(function () {
                // console.log($caption.text(), $text.text());

                $input.val($value.val());
                $caption.text($text.text());
            }, 100);

            $watchInput.off(changeEvent);
        });

        window.__item = $this;

        console.log($this);
    });
});