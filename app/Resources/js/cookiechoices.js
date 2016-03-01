(function (window) {
    var document = window.document;

    var cookieName = 'hideCookieConsent',
        dismissLinkId = 'cookieChoiceDismiss',
        cookieChoiceElement = document.getElementById('cookiechoice');

    function _dismissLinkClick() {
        return function () {
            _saveUserPreference();
            _removeCookieConsent();
            return false;
        }
    }

    function showCookieConsent() {
        if (_shouldDisplayConsent()) {
            if (cookieChoiceElement != null) {
                cookieChoiceElement.style.visibility = 'visible';
                document.getElementById(dismissLinkId).onclick = _dismissLinkClick();
            }
        } else {
            _removeCookieConsent();
        }
    }

    function _removeCookieConsent() {
        if (cookieChoiceElement != null) {
            cookieChoiceElement.style.visibility = 'hidden';
        }
    }

    function _saveUserPreference() {
        var expiryDate = new Date();
        expiryDate.setFullYear(expiryDate.getFullYear() + 1);

        if (window.localStorage !== null && typeof(window.localStorage) !== 'undefined') {
            window.localStorage.setItem(cookieName, 'y');
        } else {
            document.cookie = cookieName + '=y; expires=' + expiryDate.toGMTString() + '; path=/';
        }
    }

    function _shouldDisplayConsent() {
        if (window.localStorage !== null && typeof(window.localStorage) !== 'undefined') {
            if (window.localStorage.getItem(cookieName) == 'y') {
                return false;
            }
        }
        return !document.cookie.match(new RegExp(cookieName + '=([^;]+)'));
    }

    showCookieConsent();
})(this);