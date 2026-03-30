(function( $ ) {
	'use strict';
    $( window ).on('load', async function() {
        
        JQUERY_LOADED = true;

        // phrase container bridge
        const phraseContainer = $('#linksy-phrase-container');
        
        // anchors loaded
        phraseContainer.on('loaded', async function(e) {
            return LinksyPhraseContainer.validatePhrases();
        });
    });
})( jQuery );

function getCurrentEditor() {
    if (typeof wp !== 'undefined' && typeof wp.blocks !== 'undefined' && window._wpLoadBlockEditor){
        return new LinksyBlockEditor(wp);
    }

    if (typeof tinymce != 'undefined' && tinymce.activeEditor) {
        return new LinksyClassicEditor();
    }

    if (jQuery('.wp-editor-area#content').get(0)) {
        return new LinksyHtmlEditor();
    }
}

function dispatchEvent(name, detail) {
    const phraseContainer = document.getElementById('linksy-phrase-container');

    if (phraseContainer) {
        console.log(name, 'event dispatched')
        document.getElementById('linksy-phrase-container').dispatchEvent(new CustomEvent(name, {
            detail
        }));
    }
}