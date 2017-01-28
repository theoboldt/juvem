(function (window) {
    var document = window.document;

    var cookieName = 'c',
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

        document.cookie = cookieName + '=y; expires=' + expiryDate.toGMTString() + '; path=/';
    }

    function _shouldDisplayConsent() {
        return !document.cookie.match(new RegExp(cookieName + '=([^;]+)'));
    }

    showCookieConsent();
})(this);