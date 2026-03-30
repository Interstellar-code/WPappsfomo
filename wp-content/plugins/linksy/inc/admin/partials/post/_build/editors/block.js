let LinksyBlockEditor = function(config) {
    this.init(config);
};

LinksyBlockEditor.prototype = {
    ranges: [],

    init: function(config) {
        // jQuery("head")
        //     .append(`<link rel="stylesheet" href="${LINKSY.plugin_url_dir}/partials/post/_build/css/editor.css" type="text/css" />`);
    },

    getElement() {
        return document.getElementsByClassName('is-root-container')[0];
    },

    getContent(raw) {
        const content  = wp.data.select("core/editor").getEditedPostContent();
        const parsed_content = wp.blocks.parse(content).reduce((accumulator, currentValue) => accumulator + currentValue.originalContent, '');
        
        return raw? content : parsed_content;
    },

    updateContent(content) {
        wp.data.dispatch( 'core/block-editor' ).resetBlocks( wp.blocks.parse( content ) );
        // todo: trigger undo
    },

    onChange(callback) {
        let content = this.getContent();

        wp.data.subscribe( () => {
            let newContent = this.getContent();

            if (content != newContent) {
                setTimeout(() => callback(), 10);
            }
            content = newContent;
        });
    }
};