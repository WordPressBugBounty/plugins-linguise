import $ from 'jquery';
import 'material-design-icons/iconfont/material-icons.css';
import { MDCTabBar } from '@material/tab-bar';
import tippy from 'tippy.js';
import 'tippy.js/dist/tippy.css';
import 'chosen-js/chosen.jquery.min.js';
import 'chosen-js/chosen.min.css';
import CodeMirror from 'codemirror/lib/codemirror.js';
import 'codemirror/addon/hint/show-hint.js';
import 'codemirror/addon/hint/show-hint.css';
import 'codemirror/addon/hint/css-hint.js';
import 'codemirror/lib/codemirror.css';
import 'codemirror/theme/3024-day.css';
import '../css/admin-main.scss';
var contentEls = document.querySelectorAll('.linguise-tab-content');
tippy('.linguise-tippy', {
  theme: 'reviews',
  animation: 'scale',
  animateFill: false,
  maxWidth: 300,
  duration: 0,
  arrow: true,
  onShow(instance) {
    instance.popper.hidden = instance.reference.dataset.tippy ? false : true;
    instance.setContent(instance.reference.dataset.tippy);
  }
});

var editor = CodeMirror.fromTextArea(document.querySelector('.custom_css'), {
  theme: '3024-day',
  lineNumbers: true,
  lineWrapping : true,
  autoRefresh:true,
  styleActiveLine: true,
  fixedGutter:true,
  coverGutterNextToScrollbar:false,
  gutters: ['CodeMirror-lint-markers'],
  extraKeys: {"Ctrl-Space": "autocomplete"},
  mode: 'css'
});

jQuery(document).ready(function ($) {
  const mdcTabBar = document.querySelector('.mdc-tab-bar');
  const tabBar = mdcTabBar ? new MDCTabBar(mdcTabBar) : null;

  // keep tab on first load
  var hash = window.location.hash;
  if (typeof hash !== "undefined" && hash !== '' && hash !== 'main_settings') {
    var index = $(hash).data('index');
    tabBar && tabBar.activateTab(index);
    $('.linguise-content-active').removeClass('linguise-content-active'); // Show content for newly-activated tab
    if (index == 1) {
      setTimeout(function() {
        editor.refresh();
      },1);
    }
    $(contentEls[index]).addClass('linguise-content-active');
  }

  var config = $('.linguise_preview').data('config');
  config.current_language = config.default_language;
  tabBar && tabBar.listen('MDCTabBar:activated', function (event) {
    var tab_id = $(contentEls[event.detail.index]).attr('id');
    window.location.hash = tab_id;
    // Hide currently-active content
    $('.linguise-content-active').removeClass('linguise-content-active'); // Show content for newly-activated tab
    if (event.detail.index == 1) {
      setTimeout(function() {
        editor.refresh();
      },1);
    }
    $(contentEls[event.detail.index]).addClass('linguise-content-active');
  });
  $(".chosen-select").chosen().change(function () {
    $('.note_lang_choose').fadeIn(1000).delay(3000).fadeOut(1000);
  }).chosenSortable();

  $('#translate_into').on('chosen_sortabled', function () {
    var langs = [];
    var sort_lists = [];
    langs[config.current_language] = config.all_languages[config.current_language].name;
    $('#translate_into_chosen .search-choice').each(function () {
      var names = $(this).find('span').text().trim();
      var pos1 = names.indexOf("(");
      var pos2 = names.indexOf(")");
      var lang = names.substring(pos1 + 1, pos2);
      langs[lang]= config.all_languages[lang].name;
      sort_lists.push(lang);
    });

    $('.enabled_languages_sortable').val(sort_lists.join()).change();
    config.languages = langs;
    renderSwitcherPreview(config);
  })
  $('.flag_shadow_color').wpColorPicker({
    // a callback to fire whenever the color changes to a valid color
    change: function(event, ui){
      config.flag_shadow_color = ui.color.toString();
      renderSwitcherPreview(config);
    }
  });

  $('.flag_hover_shadow_color').wpColorPicker({
    // a callback to fire whenever the color changes to a valid color
    change: function(event, ui){
      config.flag_hover_shadow_color = ui.color.toString();
      renderSwitcherPreview(config);
    }
  });

  $('.language_name_color').wpColorPicker({
    // a callback to fire whenever the color changes to a valid color
    change: function(event, ui){
      config.language_name_color = ui.color.toString();
      renderSwitcherPreview(config);
    }
  });

  $('.language_name_hover_color').wpColorPicker({
    // a callback to fire whenever the color changes to a valid color
    change: function(event, ui){
      config.language_name_hover_color = ui.color.toString();
      renderSwitcherPreview(config);
    }
  });

  $('.popup_language_name_color').wpColorPicker({
    // a callback to fire whenever the color changes to a valid color
    change: function(event, ui){
      config.popup_language_name_color = ui.color.toString();
      renderSwitcherPreview(config);
    }
  });

  $('.popup_language_name_hover_color').wpColorPicker({
    // a callback to fire whenever the color changes to a valid color
    change: function(event, ui){
      config.popup_language_name_hover_color = ui.color.toString();
      renderSwitcherPreview(config);
    }
  });

  window.linguiseUpdateTextInput = function(val, id) {
    document.getElementById(id).value=val;
    switch (id) {
      case 'flag_shadow_h':
        config.flag_shadow_h = val;
        break;
      case 'flag_shadow_v':
        config.flag_shadow_v = val;
        break;
      case 'flag_shadow_blur':
        config.flag_shadow_blur = val;
        break;
      case 'flag_shadow_spread':
        config.flag_shadow_spread = val;
        break;
      case 'flag_hover_shadow_h':
        config.flag_hover_shadow_h = val;
        break;
      case 'flag_hover_shadow_v':
        config.flag_hover_shadow_v = val;
        break;
      case 'flag_hover_shadow_blur':
        config.flag_hover_shadow_blur = val;
        break;
      case 'flag_hover_shadow_spread':
        config.flag_hover_shadow_spread = val;
        break;
    }
    renderSwitcherPreview(config);
  }

  window.linguiseUpdateSliderInput = function(val, classs) {
    document.querySelector('.' + classs).value = val;
    switch (classs) {
      case 'flag_shadow_h':
        config.flag_shadow_h = val;
        break;
      case 'flag_shadow_v':
        config.flag_shadow_v = val;
        break;
      case 'flag_shadow_blur':
        config.flag_shadow_blur = val;
        break;
      case 'flag_shadow_spread':
        config.flag_shadow_spread = val;
        break;
      case 'flag_hover_shadow_h':
        config.flag_hover_shadow_h = val;
        break;
      case 'flag_hover_shadow_v':
        config.flag_hover_shadow_v = val;
        break;
      case 'flag_hover_shadow_blur':
        config.flag_hover_shadow_blur = val;
        break;
      case 'flag_hover_shadow_spread':
        config.flag_hover_shadow_spread = val;
        break;
    }
    renderSwitcherPreview(config);
  }

  function openLanguagePopUp(options) {
    var languages_keys = Object.keys(options.languages);
    var flag_width = options.flag_width + 'px';
    var flag_height = options.flag_width + 'px';
    if (options['flag_shape'] !== 'rounded') {
      flag_height = (parseInt(flag_width)*2/3) + 'px';
    }
    var body = $('body');
    var script = `<div id="linguise_background"></div><div id="linguise_popup_container"><div id="linguise_popup" class="${((options['flag_shape'] === 'rounded') ? 'linguise_flag_rounded' : 'linguise_flag_rectangular')}"><a class="close" href="#"><span></span></a>`;

    if (options.pre_text) {
      script += `<p>${options.pre_text}</p>`;
    }

    script += `<ul translate="no">`;
    for (var ij = 0; ij < languages_keys.length; ij++) {
      script += `<li ${languages_keys[ij] === options.current_language ? 'class="linguise-current linguise-lang-item"' : 'class="linguise-lang-item"'}  data-lang="${languages_keys[ij]}">`;
      let flag = `linguise_flag_${languages_keys[ij]}`;
      if (languages_keys[ij] === 'en' && options.flag_en_type === 'en-gb') {
        flag = 'linguise_flag_en_gb';
      } else if (languages_keys[ij] === 'de' && options.flag_de_type === 'de-at') {
        flag = 'linguise_flag_de_at';
      } else if (languages_keys[ij] === 'es' && options.flag_es_type === 'es-mx') {
        flag = 'linguise_flag_es_mx';
      } else if (languages_keys[ij] === 'es' && options.flag_es_type === 'es-pu') {
        flag = 'linguise_flag_es_pu';
      } else if (languages_keys[ij] === 'pt' && options.flag_pt_type === 'pt-br') {
        flag = 'linguise_flag_pt_br';
      } else if (languages_keys[ij] === 'zh-tw' && options.flag_tw_type === 'zh-cn') {
        flag = 'linguise_flag_zh-cn';
      }
      script += `<span class="linguise_flags ${flag}" style="width: ${flag_width}; height: ${flag_height}"></span>`;
      script += `<span class="linguise_lang_name popup_linguise_lang_name">${parseInt(options.enable_language_short_name) === 1 ? languages_keys[ij].toUpperCase() : options.languages[languages_keys[ij]]}</span>`;
      script += `</li>`;
    }

    script += `</ul>`;

    if (options.post_text) {
      script += `<p>${options.post_text}</p>`;
    }

    script += `</div>`;
    script += `</div>`;
    body.append(script);

    // Add css transition for popup
    setTimeout(function () {
      document.querySelector('#linguise_popup_container').classList.add('show_linguise_popup_container');
    }, 100);

    $('#linguise_background, #linguise_popup_container .close').click(function (e) {
      e.preventDefault();
      $('#linguise_background, #linguise_popup_container').remove();
    });
  }

  function renderSwitcherPreview(options) {
    var languages_keys = Object.keys(options.languages);
    var switcher = '';
    $('.linguise_preview').html('');
    var flag_width = options.flag_width + 'px';
    var flag_height = options.flag_width + 'px';
    if (options['flag_shape'] !== 'rounded') {
      flag_height = (parseInt(flag_width)*2/3) + 'px';
    }

    // render style
    switcher += '<style>';
    switcher += '.linguise_lang_name, .lccaret {color: '+ options.language_name_color +' !important}';
    switcher += '.popup_linguise_lang_name {color: '+ options.popup_language_name_color +' !important}';
    switcher += '.lccaret svg {fill: '+ options.language_name_color +' !important}';
    switcher += '.linguise_current_lang:hover .lccaret svg {fill: '+ options.language_name_hover_color +' !important}';
    switcher += '.linguise_lang_name:hover, .linguise_current_lang:hover .linguise_lang_name, .linguise-lang-item:hover .linguise_lang_name {color: '+ options.language_name_hover_color +' !important}';
    switcher += '.popup_linguise_lang_name:hover, .linguise-lang-item:hover .popup_linguise_lang_name {color: '+ options.popup_language_name_hover_color +' !important}';
    switcher += '.linguise_switcher span.linguise_language_icon, #linguise_popup li .linguise_flags {box-shadow: '+ options.flag_shadow_h +'px '+ options.flag_shadow_v +'px '+ options.flag_shadow_blur +'px '+ options.flag_shadow_spread +'px '+ options.flag_shadow_color +' !important}';
    switcher += '.linguise_switcher span.linguise_language_icon:hover, #linguise_popup li .linguise_flags:hover {box-shadow: '+ options.flag_hover_shadow_h +'px '+ options.flag_hover_shadow_v +'px '+ options.flag_hover_shadow_blur +'px '+ options.flag_hover_shadow_spread +'px '+ options.flag_hover_shadow_color +' !important}';
    if (options.flag_shape === "rectangular") {
      switcher += '#linguise_popup.linguise_flag_rectangular ul li .linguise_flags, .linguise_switcher.linguise_flag_rectangular span.linguise_language_icon {border-radius: '+ options.flag_border_radius +'px}';
    }
    switcher += '</style>';

    let current_language_flag = `linguise_flag_${options.current_language}`;
    if (options.current_language === 'en' && options.flag_en_type === 'en-gb') {
      current_language_flag = 'linguise_flag_en_gb';
    } else if (options.current_language === 'de' && options.flag_de_type === 'de-at') {
      current_language_flag = 'linguise_flag_de_at';
    } else if (options.current_language === 'es' && options.flag_es_type === 'es-mx') {
      current_language_flag = 'linguise_flag_es_mx';
    } else if (options.current_language === 'es' && options.flag_es_type === 'es-pu') {
      current_language_flag = 'linguise_flag_es_pu';
    } else if (options.current_language === 'pt' && options.flag_pt_type === 'pt-br') {
      current_language_flag = 'linguise_flag_pt_br';
    } else if (options.current_language === 'zh-tw' && options.flag_tw_type === 'zh-cn') {
      current_language_flag = 'linguise_flag_zh-cn';
    }

    switch (options.flag_display_type) {
      case 'popup':
        switcher += '<a class="linguise_switcher linguise_switcher_popup '+ ((options['flag_shape'] === 'rounded') ? 'linguise_flag_rounded' : 'linguise_flag_rectangular') +'">';
        if (parseInt(options.enable_flag) === 1) {
          switcher += `<span class="linguise_flags ${current_language_flag} linguise_language_icon" style="width: ${flag_width}; height: ${flag_height}"></span>`;
        }

        if (parseInt(options.enable_language_short_name) === 1) {
          switcher += `<span class="linguise_lang_name">${options.current_language.toUpperCase()}</span>`;
        } else if (parseInt(options.enable_language_name) === 1) {
          switcher += `<span class="linguise_lang_name">${options.languages[options.current_language]}</span>`;
        }

        switcher += '</a>';
        $('.linguise_preview').html(switcher);
        $('a.linguise_switcher_popup').on('click', function (e) {
          e.preventDefault();
          openLanguagePopUp(options);
        });
        break;

      case 'side_by_side':
        switcher += '<ul class="linguise_switcher linguise_switcher_side_by_side '+ ((options['flag_shape'] === 'rounded') ? 'linguise_flag_rounded' : 'linguise_flag_rectangular') +'">';
        for (var ij = 0; ij < languages_keys.length; ij++) {
          switcher += `<li ${languages_keys[ij] === options.current_language ? 'class="linguise-current linguise-lang-item"' : 'class="linguise-lang-item"'} data-lang="${languages_keys[ij]}">`;
          if (parseInt(options.enable_flag) === 1) {
            let flag = `linguise_flag_${languages_keys[ij]}`;
            if (languages_keys[ij] === 'en' && options.flag_en_type === 'en-gb') {
              flag = 'linguise_flag_en_gb';
            } else if (languages_keys[ij] === 'de' && options.flag_de_type === 'de-at') {
              flag = 'linguise_flag_de_at';
            } else if (languages_keys[ij] === 'es' && options.flag_es_type === 'es-mx') {
              flag = 'linguise_flag_es_mx';
            } else if (languages_keys[ij] === 'es' && options.flag_es_type === 'es-pu') {
              flag = 'linguise_flag_es_pu';
            } else if (languages_keys[ij] === 'pt' && options.flag_pt_type === 'pt-br') {
              flag = 'linguise_flag_pt_br';
            } else if (languages_keys[ij] === 'zh-tw' && config.flag_tw_type === 'zh-cn') {
              flag = 'linguise_flag_zh-cn';
            }
            switcher += `<span class="linguise_flags ${flag} linguise_language_icon" style="width: ${flag_width}; height: ${flag_height}"></span>`;
          }

          if (parseInt(options.enable_language_short_name) === 1) {
            switcher += `<span class="linguise_lang_name">${languages_keys[ij].toUpperCase()}</span>`;
          } else if (parseInt(options.enable_language_name) === 1) {
            switcher += `<span class="linguise_lang_name">${options.languages[languages_keys[ij]]}</span>`;
          }
          switcher += `</li>`;
        }
        switcher += '</ul>';
        $('.linguise_preview').html(switcher);
        break;

      case 'dropdown':
        switcher += `<ul class="linguise_switcher linguise_switcher_dropdown ${((options['flag_shape'] === 'rounded') ? 'linguise_flag_rounded' : 'linguise_flag_rectangular')}">`;
        switcher += `<li class="linguise_current ${parseInt(options.enable_language_name) === 1 ? 'enabled_language_name' : ''}">`;
        switcher += `<div class="linguise_current_lang">`
        if (parseInt(options.enable_flag) === 1) {
          switcher += `<span class="linguise_flags ${current_language_flag} linguise_language_icon" style="width: ${flag_width}; height: ${flag_height}"></span>`;
        }

        if (parseInt(options.enable_language_short_name) === 1) {
          switcher += `<span class="linguise_lang_name">${options.current_language.toUpperCase()}</span>`;
        } else if (parseInt(options.enable_language_name) === 1) {
          switcher += `<span class="linguise_lang_name">${options.languages[options.current_language]}</span>`;
        }
        switcher += `<span class="lccaret top">
                        <svg height="48" viewBox="0 96 960 960" width="48">
                            <path d="M480 699q-6 0-11-2t-10-7L261 492q-8-8-7.5-21.5T262 449q10-10 21.5-8.5T304 450l176 176 176-176q8-8 21.5-9t21.5 9q10 8 8.5 21t-9.5 22L501 690q-5 5-10 7t-11 2Z"/>
                        </svg>
                    </span>`;
        switcher += `</div>`
        switcher += `<ul class="linguise_switcher_sub ${languages_keys.length > 9 ? 'many_languages' : ''}">`;
        for (var ij = 0; ij < languages_keys.length; ij++) {
          if (languages_keys[ij] !== options.current_language) {
            switcher += `<li class="linguise-lang-item" data-lang="${languages_keys[ij]}">`;
            if (parseInt(options.enable_flag) === 1) {
              let flag = `linguise_flag_${languages_keys[ij]}`;
              if (languages_keys[ij] === 'en' && options.flag_en_type === 'en-gb') {
                flag = 'linguise_flag_en_gb';
              } else if (languages_keys[ij] === 'de' && options.flag_de_type === 'de-at') {
                flag = 'linguise_flag_de_at';
              } else if (languages_keys[ij] === 'es' && options.flag_es_type === 'es-mx') {
                flag = 'linguise_flag_es_mx';
              } else if (languages_keys[ij] === 'es' && options.flag_es_type === 'es-pu') {
                flag = 'linguise_flag_es_pu';
              } else if (languages_keys[ij] === 'pt' && options.flag_pt_type === 'pt-br') {
                flag = 'linguise_flag_pt_br';
              } else if (languages_keys[ij] === 'zh-tw' && config.flag_tw_type === 'zh-cn') {
                flag = 'linguise_flag_zh-cn';
              }

              switcher += `<span class="linguise_flags ${flag} linguise_language_icon" style="width: ${flag_width}; height: ${flag_height}"></span>`;
            }

            if (parseInt(options.enable_language_short_name) === 1) {
              switcher += `<span class="linguise_lang_name popup_linguise_lang_name">${languages_keys[ij].toUpperCase()}</span>`;
            } else if (parseInt(options.enable_language_name) === 1) {
              switcher += `<span class="linguise_lang_name popup_linguise_lang_name">${options.languages[languages_keys[ij]]}</span>`;
            }
            switcher += `</li>`;
          }
        }

        switcher += '</ul>';
        switcher += `</li>`;
        switcher += `</ul>`;
        $('.linguise_preview').append(switcher);
        break;
    }
  }

  function linguiseResizePanel() {
    var rightPanel = $('.linguise-right-panel');
    var rtl = $('body').hasClass('rtl');

    if (rightPanel.is(':visible')) {
      if (rightPanel.is(':visible')) {
        if (!rtl) {
          $(this).css('right', 0);
        } else {
          $(this).css('left', 0);
        }
      } else {
        if (!rtl) {
          $(this).css('right', 0);
        } else {
          $(this).css('left', 0);
        }
      }
    } else {
      if (rightPanel.is(':visible')) {
        if (!rtl) {
          $(this).css('right', 335);
        } else {
          $(this).css('left', 335);
        }
      } else {
        if (!rtl) {
          $(this).css('right', 300);
        } else {
          $(this).css('left', 300);
        }
      }
    }

    rightPanel.toggle();
  }

  function reRenderListLanguages() {
    var lang = $('#original_language').val();
    config.default_language = lang;
    config.current_language = lang;
    config.languages[config.current_language] = (config.language_name_display === 'en') ? config.all_languages[config.current_language].name : config.all_languages[config.current_language].original_name;

    var languages = {};
    var selected_languages = $('#translate_into').val();
    languages[config.default_language] = (config.language_name_display === 'en') ? config.all_languages[config.default_language].name : config.all_languages[config.default_language].original_name;
    if (selected_languages.length) {
      $.each(selected_languages, function () {
        languages[this] = (config.language_name_display === 'en') ? config.all_languages[this].name : config.all_languages[this].original_name;
      });
    }
    config.languages = languages;
  }

  function linguiseDelayHideNotification(elm) {
    if (elm) {
      setTimeout(function () {
        elm.fadeOut(2000);
      }, 3000);
    } else {
      if ($('.linguise_saved_wrap').length) {
        setTimeout(function () {
          $('.linguise_saved_wrap').fadeOut(2000);
        }, 3000);
      }
    }

  }

  // render switcher preview
  renderSwitcherPreview(config);
  linguiseDelayHideNotification();

  $('.linguise-main-wrapper').show(); // Toggle left panel on small screen

  $('.linguise-left-panel-toggle').unbind('click').click(function () {
    linguiseResizePanel();
  });

  // render preview when change options
  $('#original_language').on('change', function () {
    reRenderListLanguages();
    renderSwitcherPreview(config);
  });

  $('#translate_into').on('change', function () {
    reRenderListLanguages();
    renderSwitcherPreview(config);
  });

  $('.flag_display_type').on('change', function () {
    config.flag_display_type = $(this).val();
    renderSwitcherPreview(config);
  });

  $('.enable_language_name').on('change', function () {
    const element_enable_language_short_name = $('#id-enable_language_short_name');
    if ($(this).is(':checked')) {
      config.enable_language_name = 1;
      config.enable_language_short_name = 0;
      element_enable_language_short_name.prop('checked', false);
    } else {
      config.enable_language_name = 0;
    }
    renderSwitcherPreview(config);
  });

  $('.enable_language_short_name').on('change', function () {
    const element_enable_language_name = $('#id-enable_language_name');
    if ($(this).is(':checked')) {
      config.enable_language_short_name = 1;
      config.enable_language_name = 0;
      element_enable_language_name.prop('checked', false);
    } else {
      config.enable_language_short_name = 0;
    }
    renderSwitcherPreview(config);
  });

  $('.enable_flag').on('change', function () {
    if ($(this).is(':checked')) {
      config.enable_flag = 1;
    } else {
      config.enable_flag = 0;
    }
    renderSwitcherPreview(config);
  });

  $('.language_name_display').on('change', function () {
    config.language_name_display = $(this).val();
    reRenderListLanguages();
    renderSwitcherPreview(config);
  });

  $('.flag_shape').on('change', function () {
    config.flag_shape = $(this).val();
    renderSwitcherPreview(config);
  });

  $('.flag_width').on('change', function () {
    config.flag_width = parseInt($(this).val());
    renderSwitcherPreview(config);
  });

  $('.flag_border_radius').on('change', function () {
    if ($('.flag_shape').val() === 'rectangular') {
      config.flag_border_radius = parseInt($(this).val());
      renderSwitcherPreview(config);
    }
  });

  $('#pre_text').on('change', function () {
    config.pre_text = $(this).val();
    renderSwitcherPreview(config);
  });
  $('#post_text').on('change', function () {
    config.post_text = $(this).val();
    renderSwitcherPreview(config);
  });

  $(document).on('click', '#linguise_truncate_debug', function (e) {
    e.preventDefault();

    let href = $(this).attr('href');
    $.ajax({
      url: href,
      method: 'POST',
      success: function(data) {
        if (data.success) {
          // Add simple notification
          let succ = $('<div class="linguise_saved_wrap"><span class="material-icons"> done </span> ' + data.data + '</div>');
          $('body').append(succ);
          linguiseDelayHideNotification(succ);
        } else {
          // On error
          console.log(data);
        }
      }
    })

    return false;
  })
    .on('click', '#linguise_clear_cache', function(e) {
    e.preventDefault();
    let href = $(this).data('href');
    $.ajax({
      url: href,
      method: 'POST',
      success: function(data) {
        if (data.success === undefined) {
          if (data === '0' || data === '') {
            data = 'Cache empty!';
          }
          let succ = $('<div class="linguise_saved_wrap"><span class="material-icons"> done </span> ' + data + '</div>');
          $('body').append(succ);
          linguiseDelayHideNotification(succ);
        } else {
          // On error
          console.log(data);
        }
      }
    })
    return false;
  });
});
