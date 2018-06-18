/* global EVF_AJAX */
(function() {
    'use strict';

    var setting = {};

    setting.deactivatePlugin = function(element) {
        element.onclick = null;
        element.click();
    };

    var notice = {};

    notice.dismiss = function(event) {
        event.preventDefault();
        var attrValue, optionName, dismissableLength, data;

        attrValue = event.target.parentElement.getAttribute('data-dismissible').split('-');

        // remove the dismissible length from the attribute value and rejoin the array.
        dismissableLength = attrValue.pop();
        optionName = attrValue.join('-');

        var params = 'action=dismiss-notice&option_name=' + optionName + '&dismissible_length=' + dismissableLength + '&nonce=' + EVF_AJAX.dismiss_nonce;

        var xhr = new XMLHttpRequest();
        xhr.open('POST', EVF_AJAX.ajaxurl, true);
        xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xhr.send(params);
    };

    notice.deactivate = function(event) {
        var isNoticeActive = document.querySelector('tr.plugin-update-tr[data-plugin="everest-forms/everest-forms.php"]');
        if (isNoticeActive) {
            return true;
        }

        // display notice.
        event.preventDefault();

        var xhr = new XMLHttpRequest();
        xhr.open('GET', EVF_AJAX.ajaxurl + '?action=deactivation-notice&_wpnonce=' + EVF_AJAX.deactivation_nonce, true);

        xhr.onreadystatechange = function() {
            if (xhr.readyState === XMLHttpRequest.DONE) {
                if (xhr.status === 200) {

                    var adminNotice = xhr.responseText;
                    if ( adminNotice === 0 ) {
                        setting.deactivatePlugin(event.target);
                    }

                    // create node from admin notice.
                    var tr = document.createElement('tr');
                    tr.setAttribute('class', 'plugin-update-tr active updated');
                    tr.setAttribute('data-slug', 'everest-forms');
                    tr.setAttribute('data-plugin', 'everest-forms/everest-forms.php');

                    var td = document.createElement('td');
                    td.setAttribute('colspan', '3');
                    td.setAttribute('class', 'plugin-update colspanchange');

                    var div = document.createElement('div');
                    div.innerHTML = adminNotice;
                    var notice = div.firstChild;

                    td.appendChild(notice);
                    tr.appendChild(td);

                    var plugin = document.querySelector('tr[data-plugin="everest-forms/everest-forms.php"]');
                    if (plugin) {
                        plugin.parentNode.insertBefore(tr, plugin.nextSibling);
                        plugin.className += ' updated';
                        return;
                    }

                    // skip admin notice and just deactivate plugin.
                    setting.deactivatePlugin(event.target);
                } else {
                    setting.deactivatePlugin(event.target);
                }
            }
        };

        xhr.send();
    };

    window.addEventListener('load', function () {
        // Add click listener to dismiss notice.
        var notice = document.querySelector('div[data-dismissible] button.notice-dismiss');
        if (notice !== null) {
            notice.onclick = evf.notice.dismiss;
        }

        if ( pagenow === 'plugins' ) {
            // Add click listener to display notice on deactivation of plugin.
            var deactivate = document.querySelector('tr[data-plugin="everest-forms/everest-forms.php"] span.deactivate a');
            if (deactivate !== null) {
                deactivate.onclick = evf.notice.deactivate;
            }
        }

    });

    window.evf = {};
    window.evf.notice = notice;
})();
