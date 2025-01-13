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
import { LinguiseSwitcher } from 'script-js';

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

  var config = linguise_configs.vars.configs;
  config.current_language = config.default_language;

  function updateConfigDemoScript(newConfig) {
    const previewContent = document.getElementById('dashboard-live-preview');
    previewContent.innerHTML = '';

    const existingScript = document.querySelector('script#config-script');
    existingScript.textContent = `var linguise_configs = {vars: {configs: ${JSON.stringify(newConfig)}}}`;

    const instance = new LinguiseSwitcher();
    instance.demo_mode = true;
    instance.initialize();
  }

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
    updateConfigDemoScript(config);
  })
  $('.flag_shadow_color').wpColorPicker({
    // a callback to fire whenever the color changes to a valid color
    change: function(event, ui){
      config.flag_shadow_color = ui.color.toString();
      updateConfigDemoScript(config);
    }
  });

  $('.flag_hover_shadow_color').wpColorPicker({
    // a callback to fire whenever the color changes to a valid color
    change: function(event, ui){
      config.flag_hover_shadow_color = ui.color.toString();
      updateConfigDemoScript(config);
    }
  });

  $('.language_name_color').wpColorPicker({
    // a callback to fire whenever the color changes to a valid color
    change: function(event, ui){
      config.language_name_color = ui.color.toString();
      updateConfigDemoScript(config);
    }
  });

  $('.language_name_hover_color').wpColorPicker({
    // a callback to fire whenever the color changes to a valid color
    change: function(event, ui){
      config.language_name_hover_color = ui.color.toString();
      updateConfigDemoScript(config);
    }
  });

  $('.popup_language_name_color').wpColorPicker({
    // a callback to fire whenever the color changes to a valid color
    change: function(event, ui){
      config.popup_language_name_color = ui.color.toString();
      updateConfigDemoScript(config);
    }
  });

  $('.popup_language_name_hover_color').wpColorPicker({
    // a callback to fire whenever the color changes to a valid color
    change: function(event, ui){
      config.popup_language_name_hover_color = ui.color.toString();
      updateConfigDemoScript(config);
    }
  });

  // copy to clipboard thing for each element
  const clipboardStuff = document.querySelectorAll('[data-clipboard-text]');
  clipboardStuff.forEach((element) => {
    element.addEventListener('click', () => {
      const clipboardText = element.getAttribute('data-clipboard-text');

      if (clipboardText) {
        window.navigator.clipboard.writeText(clipboardText)
          .then(() => {
            const succ = $('<div class="linguise_saved_wrap"><span class="material-icons"> done </span>Copied to clipboard</div>');
            $('body').append(succ);
            linguiseDelayHideNotification(succ);
          })
          .catch(err => {
            console.log(err);
            const succ = $('<div class="linguise_saved_wrap"><span class="material-icons fail">close</span>Failed copying to clipboard</div>');
            $('body').append(succ);
            linguiseDelayHideNotification(succ);
          });
      }
    })
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
    updateConfigDemoScript(config);
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
    updateConfigDemoScript(config);
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
  updateConfigDemoScript(config);
  linguiseDelayHideNotification();

  $('.linguise-main-wrapper').show(); // Toggle left panel on small screen

  $('.linguise-left-panel-toggle').unbind('click').click(function () {
    linguiseResizePanel();
  });

  // render preview when change options
  $('#original_language').on('change', function () {
    reRenderListLanguages();
    updateConfigDemoScript(config);
  });

  $('#translate_into').on('change', function () {
    reRenderListLanguages();
    updateConfigDemoScript(config);
  });

  $('.flag_display_type').on('change', function () {
    config.flag_display_type = $(this).val();
    updateConfigDemoScript(config);
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
    updateConfigDemoScript(config);
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
    updateConfigDemoScript(config);
  });

  $('.enable_flag').on('change', function () {
    if ($(this).is(':checked')) {
      config.enable_flag = 1;
    } else {
      config.enable_flag = 0;
    }
    updateConfigDemoScript(config);
  });

  $('.language_name_display').on('change', function () {
    config.language_name_display = $(this).val();
    reRenderListLanguages();
    updateConfigDemoScript(config);
  });

  $('.flag_en_type').on('change', function () {
    config.flag_en_type = $(this).val();
    updateConfigDemoScript(config);
  });

  $('.flag_es_type').on('change', function () {
    config.flag_es_type = $(this).val();
    updateConfigDemoScript(config);
  });

  $('.flag_tw_type').on('change', function () {
    config.flag_tw_type = $(this).val();
    updateConfigDemoScript(config);
  });

  $('.flag_de_type').on('change', function () {
    config.flag_de_type = $(this).val();
    updateConfigDemoScript(config);
  });

  $('.flag_pt_type').on('change', function () {
    config.flag_pt_type = $(this).val();
    updateConfigDemoScript(config);
  });

  $('.flag_shape').on('change', function () {
    config.flag_shape = $(this).val();
    updateConfigDemoScript(config);
  });

  $('.flag_width').on('change', function () {
    config.flag_width = parseInt($(this).val());
    updateConfigDemoScript(config);
  });

  $('.flag_border_radius').on('change', function () {
    if ($('.flag_shape').val() === 'rectangular') {
      config.flag_border_radius = parseInt($(this).val());
      updateConfigDemoScript(config);
    }
  });

  $('#pre_text').on('change', function () {
    config.pre_text = $(this).val();
    updateConfigDemoScript(config);
  });
  $('#post_text').on('change', function () {
    config.post_text = $(this).val();
    updateConfigDemoScript(config);
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
