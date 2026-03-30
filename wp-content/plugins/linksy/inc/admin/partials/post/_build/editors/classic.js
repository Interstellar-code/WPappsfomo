let LinksyClassicEditor = function(config) {
    this.init(config);
};

LinksyClassicEditor.prototype = {
    ranges: [],
    
    init: function(config) {
        const _this = this;
        // jQuery('#wp-content-wrap #content_ifr').contents().find("head")
        //     .append(`<link rel="stylesheet" href="${LINKSY.plugin_url_dir}/assets/fontawesome/css/all.css" type="text/css" />`)
        //         .append(`<link rel="stylesheet" href="${LINKSY.plugin_url_dir}/partials/post/_build/css/editor.css" type="text/css" />`);
    },

    getElement() {
        const ele = jQuery(tinymce.activeEditor.getDoc()).find('html');
        if (ele.length > 0) {
            return ele[0]
        }
        return tinymce.activeEditor.getDoc();
    },

    getContent(raw) {
        return tinymce.activeEditor?.getContent() || '';
    },

    updateContent(content) {
        tinymce.activeEditor.setContent(content);
        tinymce.activeEditor.undoManager.add();
        tinymce.activeEditor.fire('change');
    },

    onChange(callback) {
        tinymce.activeEditor.on('input', () => {
            debounce(callback, 1000)();
        });
        
        tinymce.activeEditor.on('Undo', () => {
            debounce(callback, 1000)();
        });
    }
};