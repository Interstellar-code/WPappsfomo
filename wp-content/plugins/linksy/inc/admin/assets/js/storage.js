
let LOCAL_STORAGE_PREFIX = 'linksy_';

const LinksyStorage  = {
    isAvailable: function() {
        let storage = window[type];
        const x = '__storage_test__';
        storage.setItem(x, x);
        storage.removeItem(x);
        return true;
    },
    get: function(key, defaultValue = null) {
        if (!localStorage) {
            return defaultValue;
        }

        const value = localStorage.getItem(LOCAL_STORAGE_PREFIX+key);
        if (value === null || typeof value == 'undefined') {
            return defaultValue;
        }

        return JSON.parse(value);
    },
    set: function(key, value) {
        if (!localStorage) {
            throw Error('local storage not defined');
        }

        localStorage.setItem(LOCAL_STORAGE_PREFIX+key, JSON.stringify(value));
    },
    remove: function(key, defaultValue = null) {
        if (!localStorage) {
            throw Error('local storage not defined');
        }

        value = this.get(key, defaultValue);

        localStorage.removeItem(LOCAL_STORAGE_PREFIX+key, value);

        return value;
    },
    clear: function(key, value) {
        if (!localStorage) {
            throw Error('local storage not defined');
        }

        localStorage.clear();
    },
}