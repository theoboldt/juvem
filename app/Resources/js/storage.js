$(function(){

    /**
     * GLOBAL load user settings
     */
    window.userSettings = {
        storage: $.localStorage,
        init: function () {
            var userSettingsHash = $('body').data('user-settings-hash'),
                settingStorage = this,
                storage = this.storage;
            $.alwaysUseJsonInStorage(true);
            if (userSettingsHash && userSettingsHash != storage.get('user-settings-hash')) {
                this.load();
            }
            window.setInterval(function () {
                settingStorage.store();
            }, 2000);
        },
        load: function () {
            var storage = this.storage;
            $.ajax({
                type: 'GET',
                url: '/user/settings/load',
                success: function (response) {
                    if (response && response.hash && response.settings) {
                        storage.set('user-settings-hash', response.hash);
                        storage.set('user-settings', response.settings);
                    }
                }
            });
        },
        store: function (synchronous) {
            var storage = this.storage,
                async = !synchronous;
            if (storage.get('user-settings-dirty')) {
                storage.set('user-settings-dirty', false);
                $.ajax({
                    type: 'POST',
                    url: '/user/settings/store',
                    data: {
                        _token: $('body').data('user-settings-token'),
                        settings: storage.get('user-settings')
                    },
                    async: async,
                    success: function (response) {
                        if (response && response.hash) {
                            storage.set('user-settings-hash', response.hash);
                        }
                    }
                });
            }
        },
        has: function (key) {
            return this.storage.isSet('user-settings.' + key);
        },
        get: function (key, valueDefault) {
            if (this.storage.isSet('user-settings.' + key)) {
                return this.storage.get('user-settings.' + key);
            } else if (valueDefault) {
                return valueDefault;
            }
        },
        set: function (key, valueNew) {
            var storageOld = this.storage.get('user-settings'),
                result,
                storageNew;

            if (typeof storageOld !== 'object' || storageOld === null || storageOld === []) {
                //prevent error in case settings is not yet declared as object
                storageOld = {};
                this.storage.set('user-settings', storageOld);
            }

                result = this.storage.set('user-settings.' + key, valueNew);
                storageNew = this.storage.get('user-settings');
            if (JSON.stringify(storageOld) !== JSON.stringify(storageNew)) {
                this.storage.set('user-settings-dirty', true);
            }
            return result;
        }
    };
    userSettings.init();
    window.onbeforeunload = function () {
        userSettings.store(true);
    };

    /**
     * GLOBAL Determine if secure cache should be used or not
     *
     * @returns {*|jQuery|{}}
     */
    window.useSecureCache = function() {
        return $('body').data('use-secure-cache');
    };
    /**
     * GLOBAL load user settings
     */
    window.tableCache = {
        storage: $.localStorage,
        init: function () {
            $.alwaysUseJsonInStorage(true);
            if (!useSecureCache()) {
                this.storage.remove('table-cache');
            }
        },
        getKey(table, url) {
            return 'table-cache.' + table + '.' + url;
        },
        has: function (table, url) {
            if (!useSecureCache()) {
                return false;
            }
            return this.storage.isSet(this.getKey(table, url));
        },
        get: function (table, url) {
            if (this.has(table, url)) {
                return this.storage.get(this.getKey(table, url));
            }
        },
        remove: function (table, url) {
            if (this.has(table, url)) {
                return this.storage.remove(this.getKey(table, url));
            }
        },
        set: function (table, url, valueNew) {
            var key = this.getKey(table, url),
                result = this.storage.set(key, valueNew);
            return result;
        }
    };
    tableCache.init();
});
