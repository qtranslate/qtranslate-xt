/**
 * middleware handler for Gutenberg editor
 *
 * $author herrvigg
 */

(function () {
    console.log('QT-XT API: setup apiFetch');
    wp.apiFetch.use((options, next) => {
        if (!options.path || (options.method !== 'PUT' && options.method !== 'POST')) {
            return next(options);
        }

        // A better event handler is needed to understand when the post is saved.
        // For now "wait" by ignoring all API calls until the post is loaded in the editor.
        const post = wp.data.select('core/editor').getCurrentPost();
        // console.log('QT-XT API: PRE handling method=' + options.method, 'path=' + options.path, 'post=', post);
        if (!post.hasOwnProperty('type')) {
            return next(options);
        }
        const typeData = wp.data.select('core').getPostType(post.type);
        if (!typeData.hasOwnProperty('rest_base')) {
            return next(options);
        }
        //console.log('QT-XT API: PRE handling method=' + options.method, 'path=' + options.path, 'post=', post, 'type=', typeData);
        const prefixPath = '/wp/v2/' + typeData.rest_base + '/' + post.id;

        if ((options.path.startsWith(prefixPath) && options.method === 'PUT') ||
            (options.path.startsWith(prefixPath + '/autosaves') && options.method === 'POST')) {
            // console.log('QT-XT API: handling method=' + options.method, 'path=' + options.path, 'post=', post);
            if (!post.hasOwnProperty('qtx_editor_lang')) {
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
        return next(options);
    });
})();
