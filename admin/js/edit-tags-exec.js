/* executed for 
 /wp-admin/edit-tags.php (without action=edit)
*/
const $ = jQuery;

$(function () {
    const qtx = qTranslateConfig.js.get_qtx();

    const addDisplayHook = function (i, o) {
        qtx.addDisplayHook(o);
    };

    const updateRow = function (r) {
        const j = $(r);
        j.find('.row-title, .description').each(addDisplayHook);
        j.find('td.name span.inline').css('display', 'none');
    };

    const the_list = $('#the-list');
    let rcnt = $('#the-list > tr').length;

    const onRowAdd = function () {
        const trs = the_list.children();
        if (rcnt === trs.length)
            return false;
        const ok = rcnt > trs.length;
        rcnt = trs.length;
        if (ok)
            return false;
        for (let i = 0; i < trs.length; ++i) {
            const r = trs[i];
            updateRow(r);
        }
        return false;
    };

    the_list.each(function (i, o) {
        $(o).bind("DOMSubtreeModified", onRowAdd);
    });

    // remove "Quick Edit" links for now
    $('#the-list > tr > td.name span.inline').css('display', 'none');
});
