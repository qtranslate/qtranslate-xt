/**
 * middleware handler for Gutenberg editor
 *
 * $author herrvigg
 */

(function () {
    console.log('QT-XT API: setup apiFetch');

    wp.apiFetch.use((options, next) => {
        if (options.path) {
            const post = wp.data.select('core/editor').getCurrentPost();
            if ((options.path.startsWith('/wp/v2/posts/' + post.id) && options.method == 'PUT') ||
                (options.path.startsWith('/wp/v2/posts/' + post.id + '/autosaves') && options.method == 'POST')) {
                console.log('QT-XT API: handling method=' + options.method, 'path=' + options.path, 'post=', post);
                if (! post.hasOwnProperty('qtx_editor_lang')) {
                    console.log('QT-XT API: missing field [qtx_editor_lang] in post id=' + post.id);
                    return next(options);
                }
                const newOptions = {
                    ...options,
                    data: {
                        ...options.data,
                        'qtx_editor_lang': post.qtx_editor_lang
                    }
                };
                console.log('QT-XT API: using options=', options);
                const result = next(newOptions);
                return result;
            }
        }
        return next(options);
    });
})();
