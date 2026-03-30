let LinksyHtmlEditor = function(config) {
    this.init(config);
};

LinksyHtmlEditor.prototype = {
    ranges: [],
    
    init: function(config) {
        const _this = this;
    },

    getElement() {
        return jQuery('.wp-editor-area#content');
    },

    getContent(raw) {
        return jQuery('.wp-editor-area#content').val();
    },

    updateContent(content) {
        return jQuery('.wp-editor-area#content').val(content)
    },

    onChange(callback) {
        let content = this.getContent();

        this.getElement().on('change keyup paste', function(e) {
            let newContent = e.target.value;

            if (content != newContent) {
                setTimeout(() => callback(), 10);
            }
            content = newContent;
        });
    }
};