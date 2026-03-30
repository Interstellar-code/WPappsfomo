(function( $ ) {
	'use strict';

    socket.on("connect_error", (err) => {
        console.log(`connect_error due to ${err.message}`);
    });

    socket.on("hey", (data) => {
        console.log("welcome to linksy")
    });

    socket.on("encode_finished", (data) => {
        console.log('encode completed', data);
    });

    socket.on("keywords_generation_finished", (data) => {
        console.log('keywords_generation completed', data);
    });

    socket.on("keywords_encode_finished", (data) => {
        console.log('keywords_encode completed', data);
    });

    if(LINKSY.token) {
        const re = new RegExp('https?://(www\.)?', 'ig');
        linkylyHandShake(LINKSY.token, trim(LINKSY.site_url.replace(re, '').trim(), '/'));
    }
})( jQuery );

function linkylyHandShake(token, site, callback) {
    socket.emit("hello", {token, site}, function (res) {
        if (callback) {
            callback(res)
        }
    });
}