import '../css/style.scss';
require('script-js');

const linguiseFrontendScript = () => {
    const config = linguise_configs.vars.configs;
    const languages_keys = Object.keys(config.languages);
    config.scheme = window.location.protocol;
    config.host = window.location.host;
    config.query = window.location.search;
    config.current_language = config.default_language;

    if ((config.base ? config.base : '') + config.original_path !== window.location.pathname) {
        // Retrieve current language from url
        const regex = new RegExp('^' + (config.base !== '' ? config.base : '') + '/(' + languages_keys.join('|') + ')(?:/(.*)|$)');
        const result = regex.exec(window.location.pathname);

        if (result !== null && result.length >= 2 && result[1]) {
            config.current_language = result[1];
        }
    }

    /**
     * Generate the url of the current page for another language
     */
    const getLanguageUrl = (language, config) => {
        // Try to find the alternate
        const alternate = document.querySelector('link[rel="alternate"][hreflang="' + language + '"]');
        if (alternate !== null && alternate.href !== undefined) {
            return alternate.href;
        }

        return config.scheme + '//' + config.host + (config.base ? config.base : '') + (language === config.default_language ? '' : '/' + language) + config.original_path + config.trailing_slashes + (config.query ? config.query : '');
    }

    if (document.querySelectorAll('.linguise_parent_menu_item').length) {
        document.querySelectorAll('.linguise_parent_menu_item a').forEach(e => {
            let lang_label = '';

            if (parseInt(config.enable_flag) === 1) {
                let current_language_class = `linguise_flag_${config.current_language}`;
                if (config.current_language === 'en' && config.flag_en_type === 'en-gb') {
                    current_language_class = 'linguise_flag_en_gb';
                } else if (config.current_language === 'de' && config.flag_de_type === 'de-at') {
                    current_language_class = 'linguise_flag_de_at';
                } else if (config.current_language === 'es' && config.flag_es_type === 'es-mx') {
                    current_language_class = 'linguise_flag_es_mx';
                } else if (config.current_language === 'es' && config.flag_es_type === 'es-pu') {
                    current_language_class = 'linguise_flag_es_pu';
                } else if (config.current_language === 'pt' && config.flag_pt_type === 'pt-br') {
                    current_language_class = 'linguise_flag_pt_br';
                } else if (config.current_language === 'zh-tw' && config.flag_tw_type === 'zh-cn') {
                    current_language_class = 'linguise_flag_zh-cn';
                }

                lang_label += `<span class="linguise_flags ${current_language_class} linguise_language_icon"></span>`;
            }

            if (parseInt(config.enable_language_short_name) === 1) {
                lang_label += `<span class="linguise_lang_name">${config.current_language.toUpperCase()}</span>`;
            } else if (parseInt(config.enable_language_name) === 1) {
                lang_label += `<span class="linguise_lang_name">${config.languages[config.current_language]}</span>`;
            }

            e.innerHTML = lang_label;
        });
    }

    const linguise_switcher = document.querySelectorAll('.linguise_switcher');
    if (linguise_switcher.length) {
        linguise_switcher.forEach(e => {
            let current_language_class = `linguise_flag_${config.current_language}`;
            if (config.current_language === 'en' && config.flag_en_type === 'en-gb') {
                current_language_class = 'linguise_flag_en_gb';
            } else if (config.current_language === 'de' && config.flag_de_type === 'de-at') {
                current_language_class = 'linguise_flag_de_at';
            } else if (config.current_language === 'es' && config.flag_es_type === 'es-mx') {
                current_language_class = 'linguise_flag_es_mx';
            } else if (config.current_language === 'es' && config.flag_es_type === 'es-pu') {
                current_language_class = 'linguise_flag_es_pu';
            } else if (config.current_language === 'pt' && config.flag_pt_type === 'pt-br') {
                current_language_class = 'linguise_flag_pt_br';
            } else if (config.current_language === 'zh-tw' && config.flag_tw_type === 'zh-cn') {
                current_language_class = 'linguise_flag_zh-cn';
            }

            if (!e.classList.contains('linguise_parent_menu_item')) {
                let switcher = '';
                switch (config.flag_display_type) {
                    case 'popup':
                        if (parseInt(config.enable_flag) === 1) {
                            switcher += `<span class="linguise_flags ${current_language_class} linguise_language_icon"></span>`;
                        }

                        if (parseInt(config.enable_language_short_name) === 1) {
                            switcher += `<span class="linguise_lang_name">${config.current_language.toUpperCase()}</span>`;
                        } else if (parseInt(config.enable_language_name) === 1) {
                            switcher += `<span class="linguise_lang_name">${config.languages[config.current_language]}</span>`;
                        }

                        document.querySelectorAll('.linguise_switcher_popup').forEach(e => {
                            e.innerHTML = switcher;
                        });
                        break;

                    case 'side_by_side':
                        for (let ij = 0; ij < languages_keys.length; ij++) {
                            switcher += `<li ${languages_keys[ij] === config.current_language ? 'class="linguise_current"' : ''}>`;

                            if (languages_keys[ij] !== config.current_language) {
                                switcher += '<a href="' + getLanguageUrl(languages_keys[ij], config) + '">';
                            } else {
                                switcher += '<a href="javascript:void(0);">';
                            }

                            if (parseInt(config.enable_flag) === 1) {
                                let flag = `linguise_flag_${languages_keys[ij]}`;
                                if (languages_keys[ij] === 'en' && config.flag_en_type === 'en-gb') {
                                    flag = 'linguise_flag_en_gb';
                                } else if (languages_keys[ij] === 'de' && config.flag_de_type === 'de-at') {
                                    flag = 'linguise_flag_de_at';
                                } else if (languages_keys[ij] === 'es' && config.flag_es_type === 'es-mx') {
                                    flag = 'linguise_flag_es_mx';
                                } else if (languages_keys[ij] === 'es' && config.flag_es_type === 'es-pu') {
                                    flag = 'linguise_flag_es_pu';
                                } else if (languages_keys[ij] === 'pt' && config.flag_pt_type === 'pt-br') {
                                    flag = 'linguise_flag_pt_br';
                                } else if (languages_keys[ij] === 'zh-tw' && config.flag_tw_type === 'zh-cn') {
                                    flag = 'linguise_flag_zh-cn';
                                }
                                switcher += `<span class="linguise_flags ${flag} linguise_language_icon"></span>`;
                            }

                            if (parseInt(config.enable_language_short_name) === 1) {
                                switcher += `<span class="linguise_lang_name">${languages_keys[ij].toUpperCase()}</span>`;
                            } else if (parseInt(config.enable_language_name) === 1) {
                                switcher += `<span class="linguise_lang_name">${config.languages[languages_keys[ij]]}</span>`;
                            }

                            switcher += `</a>`;
                            switcher += `</li>`;
                        }
                        document.querySelectorAll('.linguise_switcher_side_by_side').forEach(e => {
                            e.innerHTML = switcher;
                        });
                        break;

                    case 'dropdown':
                        switcher += `<li class="linguise_current ${parseInt(config.enable_language_name) === 1 ? 'enabled_language_name' : ''}">`;
                        switcher += `<div class="linguise_current_lang">`
                        if (parseInt(config.enable_flag) === 1) {
                            switcher += `<span class="linguise_flags ${current_language_class} linguise_language_icon"></span>`;
                        }

                        if (parseInt(config.enable_language_short_name) === 1) {
                            switcher += `<span class="linguise_lang_name">${config.current_language.toUpperCase()}</span>`;
                        } else if (parseInt(config.enable_language_name) === 1) {
                            switcher += `<span class="linguise_lang_name">${config.languages[config.current_language]}</span>`;
                        }
                        switcher += `<span class="lccaret ${(config.display_position.includes('top') || config.display_position === 'no') ? 'top' : ''}">
                                        <svg height="48" viewBox="0 96 960 960" width="48">
                                            <path d="M480 699q-6 0-11-2t-10-7L261 492q-8-8-7.5-21.5T262 449q10-10 21.5-8.5T304 450l176 176 176-176q8-8 21.5-9t21.5 9q10 8 8.5 21t-9.5 22L501 690q-5 5-10 7t-11 2Z"/>
                                        </svg>
                                    </span>`;
                        switcher += `</div>`
                        switcher += `<ul class="linguise_switcher_sub ${languages_keys.length > 9 ? 'many_languages' : ''}">`;
                        for (let ij = 0; ij < languages_keys.length; ij++) {
                            if (languages_keys[ij] !== config.current_language) {
                                switcher += `<li class="linguise-lang-item">`;
                                if (languages_keys[ij] !== config.current_language) {
                                    switcher += '<a href="' + getLanguageUrl(languages_keys[ij], config) + '">';
                                }
                                if (parseInt(config.enable_flag) === 1) {
                                    let flag = `linguise_flag_${languages_keys[ij]}`;
                                    if (languages_keys[ij] === 'en' && config.flag_en_type === 'en-gb') {
                                        flag = 'linguise_flag_en_gb';
                                    } else if (languages_keys[ij] === 'de' && config.flag_de_type === 'de-at') {
                                        flag = 'linguise_flag_de_at';
                                    } else if (languages_keys[ij] === 'es' && config.flag_es_type === 'es-mx') {
                                        flag = 'linguise_flag_es_mx';
                                    } else if (languages_keys[ij] === 'es' && config.flag_es_type === 'es-pu') {
                                        flag = 'linguise_flag_es_pu';
                                    } else if (languages_keys[ij] === 'pt' && config.flag_pt_type === 'pt-br') {
                                        flag = 'linguise_flag_pt_br';
                                    } else if (languages_keys[ij] === 'zh-tw' && config.flag_tw_type === 'zh-cn') {
                                        flag = 'linguise_flag_zh-cn';
                                    }
                                    switcher += `<span class="linguise_flags ${flag} linguise_language_icon"></span>`;
                                }

                                if (parseInt(config.enable_language_short_name) === 1) {
                                    switcher += `<span class="linguise_lang_name popup_linguise_lang_name">${languages_keys[ij].toUpperCase()}</span>`;
                                } else if (parseInt(config.enable_language_name) === 1) {
                                    switcher += `<span class="linguise_lang_name popup_linguise_lang_name">${config.languages[languages_keys[ij]]}</span>`;
                                }
                                if (languages_keys[ij] !== config.current_language) {
                                    switcher += `</a>`;
                                }
                                switcher += `</li>`;
                            }
                        }

                        switcher += '</ul>';
                        switcher += `</li>`;
                        document.querySelectorAll('.linguise_switcher_dropdown').forEach(e => {
                            e.innerHTML = switcher;
                        });
                        break;

                    default:
                        if (parseInt(config.enable_flag) === 1) {
                            switcher += `<span class="linguise_flags ${current_language_class} linguise_language_icon"></span>`;
                        }

                        if (parseInt(config.enable_language_short_name) === 1) {
                            switcher += `<span class="linguise_lang_name">${config.current_language.toUpperCase()}</span>`;
                        } else if (parseInt(config.enable_language_name) === 1) {
                            switcher += `<span class="linguise_lang_name">${config.languages[config.current_language]}</span>`;
                        }

                        switcher += `<span class="lccaret">
                                          <svg height="48" viewBox="0 96 960 960" width="48">
                                            <path d="M480 699q-6 0-11-2t-10-7L261 492q-8-8-7.5-21.5T262 449q10-10 21.5-8.5T304 450l176 176 176-176q8-8 21.5-9t21.5 9q10 8 8.5 21t-9.5 22L501 690q-5 5-10 7t-11 2Z"/>
                                          </svg>
                                     </span>`;
                        document.querySelectorAll('.linguise_switcher_popup').forEach(e => {
                            e.innerHTML = switcher;
                        });
                }

                if (config.display_position === 'top_left_no_scroll' || config.display_position === 'top_right_no_scroll' || config.display_position === 'bottom_left_no_scroll' || config.display_position === 'bottom_right_no_scroll') {
                    document.querySelector('body').appendChild(e);
                }
            } else {
                let switcher = '';
                switch (config.flag_display_type) {
                    case 'dropdown':
                        switcher = `<ul class="linguise_switcher_sub ${languages_keys.length > 9 ? 'many_languages' : ''}">`;
                        for (let ij = 0; ij < languages_keys.length; ij++) {
                            if (languages_keys[ij] !== config.current_language) {
                                switcher += `<li>`;
                                if (languages_keys[ij] !== config.current_language) {
                                    switcher += '<a href="' + getLanguageUrl(languages_keys[ij], config) + '">';
                                }
                                if (parseInt(config.enable_flag) === 1) {
                                    let flag = `linguise_flag_${languages_keys[ij]}`;
                                    if (languages_keys[ij] === 'en' && config.flag_en_type === 'en-gb') {
                                        flag = 'linguise_flag_en_gb';
                                    } else if (languages_keys[ij] === 'de' && config.flag_de_type === 'de-at') {
                                        flag = 'linguise_flag_de_at';
                                    } else if (languages_keys[ij] === 'es' && config.flag_es_type === 'es-mx') {
                                        flag = 'linguise_flag_es_mx';
                                    } else if (languages_keys[ij] === 'es' && config.flag_es_type === 'es-pu') {
                                        flag = 'linguise_flag_es_pu';
                                    } else if (languages_keys[ij] === 'pt' && config.flag_pt_type === 'pt-br') {
                                        flag = 'linguise_flag_pt_br';
                                    } else if (languages_keys[ij] === 'zh-tw' && config.flag_tw_type === 'zh-cn') {
                                        flag = 'linguise_flag_zh-cn';
                                    }
                                    switcher += `<span class="linguise_flags ${flag} linguise_language_icon"></span>`;
                                }

                                if (parseInt(config.enable_language_short_name) === 1) {
                                    switcher += `<span class="linguise_lang_name popup_linguise_lang_name">${languages_keys[ij].toUpperCase()}</span>`;
                                } else if (parseInt(config.enable_language_name) === 1) {
                                    switcher += `<span class="linguise_lang_name popup_linguise_lang_name">${config.languages[languages_keys[ij]]}</span>`;
                                }
                                if (languages_keys[ij] !== config.current_language) {
                                    switcher += `</a>`;
                                }
                                switcher += `</li>`;
                            }
                        }

                        switcher += '</ul>';

                        const div_dropdown = document.createElement('div');
                        div_dropdown.innerHTML = switcher.trim();
                        e.appendChild(div_dropdown.firstChild);
                        break;

                    case 'side_by_side':
                        switcher = '<div class="side_by_side_lang_list">';

                        switcher += `<a  href="${config.scheme}//${config.host + (config.base ? config.base : '') + (config.current_language === config.default_language ? '' : '/' + config.current_language) + config.original_path + config.trailing_slashes + (config.query ? config.query : '')}">`;
                        if (parseInt(config.enable_flag) === 1) {
                            switcher += `<span class="linguise_flags ${current_language_class} linguise_language_icon"></span>`;
                        }

                        if (parseInt(config.enable_language_short_name) === 1) {
                            switcher += `<span class="linguise_lang_name">${config.current_language.toUpperCase()}</span>`;
                        } else if (parseInt(config.enable_language_name) === 1) {
                            switcher += `<span class="linguise_lang_name">${config.languages[config.current_language]}</span>`;
                        }
                        switcher += `</a>`;

                        for (let ij = 0; ij < languages_keys.length; ij++) {
                            if (languages_keys[ij] !== config.current_language) {
                                switcher += '<a href="' + getLanguageUrl(languages_keys[ij], config) + '">';
                                if (parseInt(config.enable_flag) === 1) {
                                    let flag = `linguise_flag_${languages_keys[ij]}`;
                                    if (languages_keys[ij] === 'en' && config.flag_en_type === 'en-gb') {
                                        flag = 'linguise_flag_en_gb';
                                    } else if (languages_keys[ij] === 'de' && config.flag_de_type === 'de-at') {
                                        flag = 'linguise_flag_de_at';
                                    } else if (languages_keys[ij] === 'es' && config.flag_es_type === 'es-mx') {
                                        flag = 'linguise_flag_es_mx';
                                    } else if (languages_keys[ij] === 'es' && config.flag_es_type === 'es-pu') {
                                        flag = 'linguise_flag_es_pu';
                                    } else if (languages_keys[ij] === 'pt' && config.flag_pt_type === 'pt-br') {
                                        flag = 'linguise_flag_pt_br';
                                    } else if (languages_keys[ij] === 'zh-tw' && config.flag_tw_type === 'zh-cn') {
                                        flag = 'linguise_flag_zh-cn';
                                    }
                                    switcher += `<span class="linguise_flags ${flag} linguise_language_icon"></span>`;
                                }

                                if (parseInt(config.enable_language_short_name) === 1) {
                                    switcher += `<span class="linguise_lang_name">${languages_keys[ij].toUpperCase()}</span>`;
                                } else if (parseInt(config.enable_language_name) === 1) {
                                    switcher += `<span class="linguise_lang_name">${config.languages[languages_keys[ij]]}</span>`;
                                }
                                switcher += `</a>`;
                            }
                        }
                        switcher += '</div>';
                        const div_side_by_side = document.createElement('div');
                        div_side_by_side.innerHTML = switcher;
                        e.appendChild(div_side_by_side.firstChild);
                        break;

                    case 'popup':
                        if (document.querySelectorAll('.linguise_parent_menu_item').length) {
                            e.addEventListener("click", function(e){
                                window.openLanguagePopUp(e);
                            });
                        }
                        break;
                }
            }
        });

        if (config.flag_display_type === 'dropdown') {
            // Fix mobile menu not working on elementor (click closes the menu)
            document.querySelectorAll('.linguise_switcher > a').forEach(e => {
                e.addEventListener('click', event => {
                    event.stopImmediatePropagation();
                });
            });
        }
    }

    window.openLanguagePopUp = function (e) {
        if (e !== undefined && e.preventDefault) {
            e.preventDefault();
        }

        if (window.language_popup_initialised === true) {
            document.querySelector('#linguise_background').style.display = 'block';
            document.querySelector('#linguise_popup_container').classList.add('show_linguise_popup_container');
            return;
        }

        window.language_popup_initialised = true;
        const elementBackground = document.createElement('div');
        elementBackground.id = 'linguise_background';
        const elementPopUp = document.createElement('div');
        const elementPopUpParent = document.createElement('div');
        elementPopUpParent.id = 'linguise_popup_container';
        elementPopUpParent.appendChild(elementPopUp);

        elementPopUp.classList.add(config['flag_shape'] === 'rounded' ? 'linguise_flag_rounded' : 'linguise_flag_rectangular');
        elementPopUp.id = 'linguise_popup';

        const elementClose = document.createElement('a');
        elementClose.classList.add('close');
        elementClose.href = '#';
        elementClose.appendChild(document.createElement('span'));
        elementPopUp.appendChild(elementClose);

        if (config.pre_text) {
            const elementPre = document.createElement('p');
            elementPre.innerHTML = config.pre_text;
            elementPopUp.appendChild(elementPre);
        }

        const elementUl = document.createElement('ul');
        elementUl.translate = false;

        for (let ij = 0; ij < languages_keys.length; ij++) {
            const elementLi = document.createElement('li');
            if (languages_keys[ij] === config.current_language) {
                elementLi.classList.add('linguise_current');
            }

            let topElement = elementLi;
            if (languages_keys[ij] !== config.current_language) {
                const elementA = document.createElement('a');
                elementA.href = getLanguageUrl(languages_keys[ij], config);
                elementLi.appendChild(elementA);
                topElement = elementA;
            }


            // Append language flags
            if (parseInt(config.enable_flag) === 1) {
                const elementFlag = document.createElement('span');
                elementFlag.classList.add('linguise_flags');
                let flag = `linguise_flag_${languages_keys[ij]}`;
                if (languages_keys[ij] === 'en' && config.flag_en_type === 'en-gb') {
                    flag = 'linguise_flag_en_gb';
                } else if (languages_keys[ij] === 'de' && config.flag_de_type === 'de-at') {
                    flag = 'linguise_flag_de_at';
                } else if (languages_keys[ij] === 'es' && config.flag_es_type === 'es-mx') {
                    flag = 'linguise_flag_es_mx';
                } else if (languages_keys[ij] === 'es' && config.flag_es_type === 'es-pu') {
                    flag = 'linguise_flag_es_pu';
                } else if (languages_keys[ij] === 'pt' && config.flag_pt_type === 'pt-br') {
                    flag = 'linguise_flag_pt_br';
                } else if (languages_keys[ij] === 'zh-tw' && config.flag_tw_type === 'zh-cn') {
                    flag = 'linguise_flag_zh-cn';
                }
                elementFlag.classList.add(flag);
                topElement.appendChild(elementFlag);
            }


            const elementName = document.createElement('span');
            elementName.classList.add('linguise_lang_name', 'popup_linguise_lang_name');
            elementName.innerText = parseInt(config.enable_language_short_name) === 1 ? languages_keys[ij].toUpperCase() : config.languages[languages_keys[ij]];
            topElement.appendChild(elementName);

            elementUl.appendChild(elementLi);
        }

        elementPopUp.appendChild(elementUl);

        if (config.post_text) {
            const elementPost = document.createElement('p');
            elementPost.innerHTML = config.post_text;
            elementPopUp.appendChild(elementPost);
        }

        const closeModal = function (e) {
            e.preventDefault();
            elementBackground.style.display = 'none';
            document.querySelector('#linguise_popup_container').classList.remove('show_linguise_popup_container');
        };

        elementBackground.onclick = closeModal;

        elementClose.onclick = closeModal;

        document.querySelector('body').appendChild(elementBackground);
        document.querySelector('body').appendChild(elementPopUpParent);

        setTimeout(function () {
            elementPopUpParent.classList.add('show_linguise_popup_container');
        }, 100);

        if (document.querySelector('.linguise_parent_menu_item') !== null) {
            document.querySelectorAll('.linguise_parent_menu_item').forEach(el => {
                el.addEventListener("click", function(e) {
                    window.openLanguagePopUp(e);
                });
            });
        }
    };
};

if (document.readyState !== 'loading') {
    linguiseFrontendScript();
} else {
    document.addEventListener('DOMContentLoaded', linguiseFrontendScript);
}
