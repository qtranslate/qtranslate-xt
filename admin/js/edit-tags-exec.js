/* executed for 
 /wp-admin/edit-tags.php (without action=edit)
*/
'use strict';
const $ = jQuery;

$(function () {
    const qtx = qTranslateConfig.js.get_qtx();

    const addDisplayHook = function (i, e) {
        qtx.addDisplayHook(e);
    };

    const updateRow = function (row) {
        const $row = $(row);
        $row.find('.row-title, .description').each(addDisplayHook);
        $row.find('td.name span.inline').css('display', 'none');
    };

    const $theList = $('#the-list');
    let nbRows = $('#the-list > tr').length;

    const onRowAdd = function () {
        const $rows = $theList.children();
        if (nbRows === $rows.length)
            return false;
        const ok = nbRows > $rows.length;
        nbRows = $rows.length;
        if (ok)
            return false;
        for (let i = 0; i < $rows.length; ++i) {
            const row = $rows[i];
            updateRow(row);
        }
        return false;
    };

    $theList.each(function (i, e) {
        $(e).bind("DOMSubtreeModified", onRowAdd);
    });

    // remove "Quick Edit" links for now
    $('#the-list > tr > td.name span.inline').css('display', 'none');
});
