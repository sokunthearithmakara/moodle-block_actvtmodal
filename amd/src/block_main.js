

// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * block_actvtmodal/block_main
 *
 * @module     block_actvtmodal/block_main
 * @copyright  2025 Sokunthearith Makara <sokunthearithmakara@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import $ from 'jquery';
import ModalFactory from 'core/modal_factory';
import ModalEvents from 'core/modal_events';
import Ajax from 'core/ajax';

export const init = async(blockid, contextid, courseid, userid, canedit) => {
    let str = await import('core/str');
    const bindEvents = (event, selector, func) => {
        $(document).off(event, selector).on(event, selector, func);
    };

    bindEvents('change', '[name=config_modcm]', function() {
        let configcm = [];
        if ($(this).is(':checked')) {
            configcm.push($(this).val());
        } else {
            configcm = configcm.filter(x => x != $(this).val());
        }
        $('[name=config_disablecm]').val(configcm.join(','));
    });

    const updateConfigType = async() => {
        let $table = $('#configtypewrapper').find('table');
        let $tr = $table.find('tbody tr');
        // Unset the configtype value.
        let configtype = {};
        $tr.each(function(index, elem) {
            let $this = $(elem);
            configtype[$this.find('[name=disabletype]').val()] = {
                'd': $this.find('[name=disabletype]').is(':checked'),
                'o': $this.find('[name=overridetype]').is(':checked'),
                'p': $this.find('[name=showpageheader]').is(':checked'),
                't': $this.find('[name=showtitle]').is(':checked'),
                'a': $this.find('[name=showactivityheader]').is(':checked'),
                's': $this.find('[name=showsecondarynav]').is(':checked'),
                'b': $this.find('[name=showblock]').is(':checked'),
                'u': $this.find('[name=urlparams]').val(),
            };
        });
        $('[name=config_disabletype]').val(JSON.stringify(configtype));
    };

    bindEvents('change', '#configtypewrapper [type=checkbox]', function() {
        updateConfigType();
    });

    bindEvents('input', '#configtypewrapper [type=text]', function() {
        updateConfigType();
    });

    if ($('body').hasClass('editing')) {
        return;
    }

    let sitecss = $('#sitecss').val();
    if (!sitecss || sitecss == 'null') {
        sitecss = '';
    }

    let configjson = $('#configjson').val();
    if (!configjson || configjson == 'null') {
        configjson = '{}';
    }
    configjson = JSON.parse(configjson || '{}');
    let showactivityheader = Object.keys(configjson).length === 0 ? true :
        !configjson.showactivityheader || configjson.showactivityheader != 0;
    let showtitle = configjson.showtitle == 1;
    let showsecondarynav = configjson.showsecondarynav == 1;
    let showblock = configjson.showblock == 1;
    let showpageheader = configjson.showpageheader == 1;
    let css = configjson.css || '';
    let types = JSON.parse(configjson.disabletype || '{}');
    let disabletypekeys = Object.keys(types);
    let disabledtypes = [];
    disabletypekeys.forEach(key => {
        if (types[key] && types[key].d) {
            disabledtypes.push(key);
        }
    });

    let disablecm = configjson.disablecm ? configjson.disablecm.split(',') : [];

    let active = false; // Modal active.
    bindEvents('click.AM', '[id="courseindex"] a.courseindex-link, .course-content a.aalink, nav#courseindex a[data-for=cm_name]',
        async function(e) {
            if (active) {
                return;
            }
            active = true;
            e.preventDefault();
            let href = $(this).attr('href');
            // Regex match mod/xxx/view.php?id=xxx
            let matches = href.match(/mod\/([^\/]+)\/view.php\?id=(\d+)/);
            if (!matches) {
                // Open the link.
                window.location.href = href;
                return;
            }
            let url = href;
            let mod = matches[1];
            let id = matches[2];
            if (disablecm.includes(id)) {
                return;
            }
            if (disabledtypes.includes(mod)) {
                return;
            }
            let config = types[mod];

            if (!config) {
                config = {};
            }
            if (!config.o) {
                config.showtitle = showtitle;
                config.showactivityheader = showactivityheader;
                config.showsecondarynav = showsecondarynav;
                config.showblock = showblock;
                config.showpageheader = showpageheader;
            } else {
                config.showtitle = config.t;
                config.showactivityheader = config.a;
                config.showsecondarynav = config.s;
                config.showblock = config.b;
                config.showpageheader = config.p;
            }

            if (config.u && config.u != '') {
                if (config.u.startsWith('?')) {
                    url += config.u;
                } else {
                    url += '&' + config.u;
                }
            }

            let activityModal = await ModalFactory.create({
                body: `<div class="position-absolute w-100 h-100 no-pointer bg-transparent"
                         id="background-loading">
                        <div class="d-flex h-100 align-items-center justify-content-center">
                            <div class="spinner-border text-danger"
                                 style="width: 3rem;
                                        height: 3rem"
                                 role="status">
                                <span class="sr-only">Loading...</span>
                            </div>
                        </div>
                    </div>`,
                large: true,
                show: false,
                removeOnClose: true,
                isVerticallyCentered: true,
            });


            let root = activityModal.getRoot();
            root.addClass('activitymodal');
            root.find('.modal-dialog').addClass('modal-xl').removeClass('modal-lg');
            let $body = root.find('.modal-body');
            $body.addClass('overflow-hidden');
            let data; // The current cm data.

            let modalhidden = false;
            root.find('[data-action="hide"]').on('click', function() {
                root.attr('data-region', 'modal-container');
            });

            root.off('click.modal').on('click.modal', function(e) {
                if ($(e.target).closest('.modal-content').length === 0) {
                    root.addClass('am-jelly-anim');
                    setTimeout(() => {
                        root.removeClass('am-jelly-anim');
                    }, 500);
                }
            });
            root.off(ModalEvents.hidden).on(ModalEvents.hidden, async function() {
                activityModal.destroy();
                $('body').removeClass('modal-open');
                active = false;
                modalhidden = true;
                if (data && data.overallcompletion == -1) { // No completion, no care ;).
                    return;
                }

                if (data && data.withavailability == 1) { // Referenced in availability, refresh the page.
                    window.location.reload();
                }

                // Now get the new completion status.
                let newcmdata = await Ajax.call([{
                    methodname: 'block_actvtmodal_get_info',
                    args: {
                        courseid: courseid,
                        cmid: id,
                        userid: userid,
                        completiononly: true,
                    },
                }])[0];

                if (newcmdata.data) {
                    let newdata = JSON.parse(newcmdata.data);
                    if (newdata.overallcompletion != data.overallcompletion) {
                        // We're going to update the course activity card.
                        let card = await Ajax.call([{
                            methodname: 'block_actvtmodal_get_cm_html',
                            args: {
                                data: JSON.stringify({
                                    courseid: courseid,
                                    cmid: id,
                                    userid: userid,
                                }),
                            },
                        }])[0];

                        if (!card.data) {
                            window.location.reload();
                        }

                        let html = JSON.parse(card.data).html;
                        $(`li.activity[data-id="${id}"]`).replaceWith(html).trigger('click');
                    }
                }
            });

            root.off(ModalEvents.shown);
            root.on(ModalEvents.shown, async function() {
                root.addClass('am-jelly-anim');
                setTimeout(() => {
                    root.removeClass('am-jelly-anim');
                }, 500);
                $('body').addClass('modal-open');
                root.attr('data-region', 'popup'); // Must set to avoid dismissing the modal when clicking outside.
                let cmdata = await Ajax.call([{
                    methodname: 'block_actvtmodal_get_info',
                    args: {
                        courseid: courseid,
                        cmid: id,
                        userid: userid,
                        completiononly: false,
                    },
                }])[0];

                if (cmdata.data) {
                    data = JSON.parse(cmdata.data);
                } else {
                    return;
                }
                root.find('.modal-title').empty()
                    .addClass('w-100 d-flex align-items-center')
                    .append(`
                        <div id="am-ca-header-wrapper"
                         class="d-flex align-items-center w-100">
                            <div class="${data.activity.purpose} activityiconcontainer activity-icon am-mr-2 smaller small">
                                ${data.activity.icon}
                                </div>
                            <h5 class="h5 mb-0 clamp-1">${data.activity.name}</h5>
                        </div>
                        ${canedit ? `<a href="${M.cfg.wwwroot}/course/mod.php?update=${id}" class="btn border-0" target="_blank"
                         title="${await str.get_string('edit', 'block_actvtmodal')}">
                            <i class="fa fa-pen"></i>
                        </a>` : ''}
                        <a href="${url}" class="btn border-0" title="${await str.get_string('openpage', 'block_actvtmodal')}">
                            <i class="fa fa-external-link-alt"></i>
                        </a>
                        <button type="button" class="btn border-0 enterfullscreen"
                         title="${await str.get_string('expand', 'block_actvtmodal')}">
                            <i class="fa fa-expand"></i>
                        </button>
                        <button type="button" class="btn border-0 exitfullscreen"
                         title="${await str.get_string('narrow', 'block_actvtmodal')}">
                            <i class="fa fa-compress"></i>
                        </button>
                        `);
                $body.addClass('p-0');
                $body.append(`<iframe src="${url}" width="100%" frameborder="0" allowfullscreen class="d-none"></iframe>`);
                $body.removeClass('overflow-hidden');

                // Run animation frame to get the iframe document.
                let iframe = document.querySelector(`.activitymodal iframe`);
                let divloaded = false;
                let timeout = 0;
                let timeoutInterval;
                let clicklink;
                let checkIframe = async() => {
                    if (timeout > 15) {
                        clearInterval(timeoutInterval);
                        if (iframe) {
                            iframe.classList.remove('d-none');
                        }
                        return;
                    }
                    if (iframe) {
                        iframe.style.background = 'none';
                        let contentWindow = iframe.contentWindow;
                        if (!contentWindow) {
                            requestAnimationFrame(checkIframe);
                            return;
                        }
                        let contentDocument = iframe.contentDocument || iframe.contentWindow.document;
                        let page = contentDocument.querySelector('div'); // Any div element.
                        if (page) {
                            divloaded = true;
                            clearInterval(timeoutInterval);
                            // If body does not have 'path-mod' class, it isn't a course page.
                            if (!contentDocument.body.classList.contains('cmid-' + id)) {
                                // Go to the link.
                                activityModal.hide();
                                window.location.href = clicklink;
                                return;
                            }
                            iframe.classList.remove('d-none');
                            // Add the am-embedactivity class to the body.
                            contentDocument.body.classList.add('am-embedactivity');
                            if (!config.showtitle) {
                                contentDocument.body.classList.add('am-hidetitle');
                            }
                            if (!config.showactivityheader) {
                                contentDocument.body.classList.add('am-hideactivityheader');
                            }
                            if (!config.showsecondarynav) {
                                contentDocument.body.classList.add('am-hidesecondarynav');
                            }
                            if (!config.showblock) {
                                contentDocument.body.classList.add('am-hideblock');
                            }
                            if (!config.showpageheader) {
                                contentDocument.body.classList.add('am-hidepageheader');
                            }

                            if (sitecss != '') {
                                contentDocument.head.innerHTML += '<style>' + sitecss + '</style>';
                            }

                            if (css != '') {
                                contentDocument.head.innerHTML += '<style>' + css + '</style>';
                            }

                            // Append a div to the iframe.
                            $(contentDocument.body).append(`<div id="am-embed-activity-container">
                                <a class="btn" href="${url}"
                                 title="${await str.get_string('startpage', 'block_actvtmodal')}">
                                 <i class="fa fa-home"></i></a>
                                </div>`);

                            contentWindow.addEventListener('unload', function() { // We have to reapply styles.
                                iframe.classList.add('d-none');
                                timeout = 0;
                                requestAnimationFrame(checkIframe);
                                timeoutInterval = setInterval(function() {
                                    timeout += 1;
                                    if (timeout > 15) {
                                        clearInterval(timeoutInterval);
                                        if (iframe) {
                                            iframe.classList.remove('d-none');
                                        }
                                        return;
                                    }
                                    if (modalhidden) {
                                        clearInterval(timeoutInterval);
                                        timeout = 16;
                                        return;
                                    }
                                }, 1000);
                            });

                            $(contentDocument).off('click.AM', 'a[href^="http"]')
                                .on('click.AM', 'a[href^="http"]', function() {
                                    clicklink = $(this).attr('href');
                                });

                        } else {
                            requestAnimationFrame(checkIframe);
                        }
                    } else {
                        requestAnimationFrame(checkIframe);
                    }
                };

                requestAnimationFrame(checkIframe);
                timeoutInterval = setInterval(function() {
                    timeout += 1;
                    if (timeout > 15) {
                        clearInterval(timeoutInterval);
                        if (iframe) {
                            iframe.classList.remove('d-none');
                        }
                        return;
                    }
                }, 1000);

                iframe.addEventListener('load', async function() {
                    $body.find('#am-ca-back-wrapper').remove();
                    // Load event waits until everything is loaded including the videos, images, etc., which isn't ideal.
                    timeout = 0;
                    if (!divloaded) { // Should not expect this to happen, but just in case.
                        iframe.classList.add('d-none');
                        requestAnimationFrame(checkIframe);
                        return;
                    }
                    let iframedoc = iframe.contentDocument || iframe.contentWindow.document;
                    if (!iframedoc || !iframedoc.body.classList.contains('course-' + courseid)) {
                        iframe.classList.remove('d-none');
                        iframe.classList.add('bg-white');
                        $body.append(`<div id="am-ca-back-wrapper" class="w-100 position-absolute top-0">
                                <a id="gobacktoactivity" class="btn am-float-right" href="javascript:void(0)"
                                 title="${await str.get_string('startpage', 'block_actvtmodal')}">
                                 <i class="fa fa-home fa-1x"></i></a>
                                </div>`);
                        return;
                    }

                    iframe.classList.remove('bg-white');
                });

                $body.off('click', '#gobacktoactivity').on('click', '#gobacktoactivity', function(e) {
                    e.preventDefault();
                    $body
                        .find('iframe')
                        .replaceWith(`<iframe src="${url}" width="100%" frameborder="0" allowfullscreen class="d-none"></iframe>`);
                    iframe = document.querySelector(`.activitymodal iframe`);
                    divloaded = false;
                    timeout = 0;
                    requestAnimationFrame(checkIframe);
                    clearInterval(timeoutInterval);
                    timeoutInterval = setInterval(function() {
                        timeout += 1;
                        if (timeout > 15) {
                            clearInterval(timeoutInterval);
                            if (iframe) {
                                iframe.classList.remove('d-none');
                            }
                            return;
                        }
                    }, 1000);
                });
            });

            activityModal.show();

        });

    bindEvents('click.AM', '.activitymodal .exitfullscreen', function(e) {
        e.preventDefault();
        $(this).closest('.activitymodal').removeClass('fullscreen');
    });

    bindEvents('click.AM', '.activitymodal .enterfullscreen', function(e) {
        e.preventDefault();
        $(this).closest('.activitymodal').addClass('fullscreen');
    });
};
