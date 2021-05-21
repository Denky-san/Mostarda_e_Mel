// add delete buttons
jQuery.fn.add_delete = function () {
    this.append('<div class="tnpc-row-delete" title="Delete"><img src="' + TNP_PLUGIN_URL + '/emails/tnp-composer/_assets/delete.png" width="32"></div>');
    this.find('.tnpc-row-delete').perform_delete();
};

// delete row
jQuery.fn.perform_delete = function () {
    this.click(function () {
        tnpc_hide_block_options();
        // remove block
        jQuery(this).parent().remove();
        tnpc_mobile_preview();
    });
}

// add edit button
jQuery.fn.add_block_edit = function () {
    this.append('<div class="tnpc-row-edit-block" title="Edit"><img src="' + TNP_PLUGIN_URL + '/emails/tnp-composer/_assets/edit.png" width="32"></div>');
    this.find('.tnpc-row-edit-block').perform_block_edit();
}

// edit block
jQuery.fn.perform_block_edit = function () {

    jQuery(".tnpc-row-edit-block").click(function (e) {
        e.preventDefault()
    });

    this.click(function (e) {

        e.preventDefault();

        target = jQuery(this).parent().find('.edit-block');

        // The row container which is a global variable and used later after the options save
        container = jQuery(this).closest("table");

        if (container.hasClass('tnpc-row-block')) {

            tnpc_show_block_options();

            var options = container.find(".tnpc-block-content").attr("data-json");

            // Compatibility
            if (!options) {
                options = target.attr("data-options");
            }

            var data = {
                action: "tnpc_options",
                id: container.data("id"),
                context_type: tnp_context_type,
                options: options
            };

            tnpc_add_global_options(data);

            builderAreaHelper.lock();
            jQuery("#tnpc-block-options-form").load(ajaxurl, data , function () {
                console.log('Block form options loaded');
                start_options = jQuery("#tnpc-block-options-form").serializeArray();
                tnpc_add_global_options(start_options);
                builderAreaHelper.unlock();
            });

        } else {
            alert("This is deprecated block version and cannot be edited. Please replace it with a new one.");
        }

    });

};

// add clone button
jQuery.fn.add_block_clone = function () {
    this.append('<div class="tnpc-row-clone" title="Clone"><img src="' + TNP_PLUGIN_URL + '/emails/tnp-composer/_assets/copy.png" width="32"></div>');
    this.find('.tnpc-row-clone').perform_clone();
}

// clone block
jQuery.fn.perform_clone = function () {

    jQuery(".tnpc-row-clone").click(function (e) {
        e.preventDefault()
    });

    this.click(function (e) {

        e.preventDefault();

        // hide block edit form
        tnpc_hide_block_options();

        // find the row
        let row = jQuery(this).closest('.tnpc-row');

        // clone the block
        let new_row = row.clone();
        new_row.find(".tnpc-row-delete").remove();
        new_row.find(".tnpc-row-edit-block").remove();
        new_row.find(".tnpc-row-clone").remove();

        new_row.add_delete();
        new_row.add_block_edit();
        new_row.add_block_clone();
        // if (new_row.hasClass('tnpc-row-block')) {
        //     new_row.find(".tnpc-row-edit-block i").click();
        // }
        new_row.insertAfter(row);
        tnpc_mobile_preview();
    });
};

let start_options = null;
let container = null;

jQuery(function () {

    // open blocks tab
    document.getElementById("defaultOpen").click();

    // preload content from a body named input
    var preloadedContent = jQuery('input[name="message"]').val();
    if (!preloadedContent) {
        preloadedContent = jQuery('input[name="options[message]"]').val();
    }

    if (!preloadedContent) {
        tnpc_show_presets();
    } else {
        jQuery('#newsletter-builder-area-center-frame-content').html(preloadedContent);
        start_composer();
    }

    // subject management
    jQuery('#options-title').val(jQuery('#tnpc-form input[name="options[subject]"]').val());

    // ======================== //
    // ==  BACKGROUND COLOR  == //
    // ======================== //
    _setBuilderAreaBackgroundColor(document.getElementById('options-options_composer_background').value);

    function _setBuilderAreaBackgroundColor(color) {
        jQuery('#newsletter-builder-area-center-frame-content').css('background-color', color);
    }

    window._setBuilderAreaBackgroundColor = _setBuilderAreaBackgroundColor; //BAD STUFF!!!

    // ======================== //
    // ==  BACKGROUND COLOR  == //
    // ======================== //

});

function BuilderAreaHelper() {

    var _builderAreaEl = document.querySelector('#newsletter-builder-area');
    var _overlayEl = document.createElement('div');
    _overlayEl.style.zIndex = 99999;
    _overlayEl.style.position = 'absolute';
    _overlayEl.style.top = 0;
    _overlayEl.style.left = 0;
    _overlayEl.style.width = '100%';
    _overlayEl.style.height = '100%';

    this.lock = function () {
        console.log('Lock builder area');
        _builderAreaEl.appendChild(_overlayEl);
    }

    this.unlock = function () {
        console.log('Unlock builder area');
        _builderAreaEl.removeChild(_overlayEl);
    }

}

let builderAreaHelper = new BuilderAreaHelper();

function init_builder_area() {

    //Drag & Drop
    jQuery("#newsletter-builder-area-center-frame-content").sortable({
        revert: false,
        placeholder: "placeholder",
        forcePlaceholderSize: true,
        opacity: 0.6,
        tolerance: "pointer",
        helper: function (e) {
            var helper = jQuery(document.getElementById("sortable-helper")).clone();
            return helper;
        },
        update: function (event, ui) {
            if (ui.item.attr("id") == "draggable-helper") {
                loading_row = jQuery('<div style="text-align: center; padding: 20px; background-color: #d4d5d6; color: #52BE7F;"><i class="fa fa-cog fa-2x fa-spin" /></div>');
                ui.item.before(loading_row);
                ui.item.remove();
                var data = new Array(
                    {"name": 'action', "value": 'tnpc_render'},
                    {"name": 'b', "value": ui.item.data("id")},
                    {"name": 'full', "value": 1},
                    {"name": '_wpnonce', "value": tnp_nonce}
                );

                tnpc_add_global_options(data);

                jQuery.post(ajaxurl, data, function (response) {

                    var new_row = jQuery(response);
//                    ui.item.before(new_row);
//                    ui.item.remove();
                    loading_row.before(new_row);
                    loading_row.remove();
                    new_row.add_delete();
                    new_row.add_block_edit();
                    new_row.add_block_clone();
                    // new_row.find(".tnpc-row-edit").hover_edit();
                    if (new_row.hasClass('tnpc-row-block')) {
                        new_row.find(".tnpc-row-edit-block").click();
                    }
                    tnpc_mobile_preview();
                }).fail(function () {
                    alert("Block rendering failed.");
                    loading_row.remove();
                });
            } else {
                tnpc_mobile_preview();
            }
        }
    });

    jQuery(".newsletter-sidebar-buttons-content-tab").draggable({
        connectToSortable: "#newsletter-builder-area-center-frame-content",

        // Build the helper for dragging
        helper: function (e) {
            var helper = jQuery(document.getElementById("draggable-helper")).clone();
            // Do not uset .data() with jQuery
            helper.attr("data-id", e.currentTarget.dataset.id);
            helper.html(e.currentTarget.dataset.name);
            return helper;
        },
        revert: false,
        start: function () {
            if (jQuery('.tnpc-row').length) {
            } else {
                jQuery('#newsletter-builder-area-center-frame-content').append('<div class="tnpc-drop-here">Drag&Drop blocks here!</div>');
            }
        },
        stop: function (event, ui) {
            jQuery('.tnpc-drop-here').remove();
        }
    });

    jQuery(".tnpc-row").add_delete();
    jQuery(".tnpc-row").add_block_edit();
    jQuery(".tnpc-row").add_block_clone();

}

function start_composer() {

    init_builder_area();

    // Closes the block options layer (without saving)
    jQuery("#tnpc-block-options-cancel").click(function () {

        tnpc_hide_block_options();

        var _target = target;

        jQuery.post(ajaxurl, start_options, function (response) {
            _target.html(response);
            jQuery("#tnpc-block-options-form").html("");
        });
    });

    // Fires the save event for block options
    jQuery("#tnpc-block-options-save").click(function (e) {
        e.preventDefault();

        var _target = target;

        // fix for Codemirror
        if (typeof templateEditor !== 'undefined') {
            templateEditor.save();
        }

        if (window.tinymce)
            window.tinymce.triggerSave();

        var data = jQuery("#tnpc-block-options-form").serializeArray();

        tnpc_add_global_options(data);

        tnpc_hide_block_options();

        jQuery.post(ajaxurl, data, function (response) {
            _target.html(response);
            tnpc_mobile_preview();
            jQuery("#tnpc-block-options-form").html("");
        });
    });

    // live preview from block options *** EXPERIMENTAL ***
    jQuery('#tnpc-block-options-form').change(function (event) {
        var data = jQuery("#tnpc-block-options-form").serializeArray();

        var _container = container;
        var _target = target;

        tnpc_add_global_options(data);

        jQuery.post(ajaxurl, data, function (response) {
            _target.html(response);
            if (event.target.dataset.afterRendering === 'reload') {
                _container.find(".tnpc-row-edit-block").click();
            }
        }).fail(function () {
            alert("Block rendering failed");
        });

    });

    tnpc_mobile_preview();

}

function tnpc_show_block_options() {

    const animationDuration = 500;

    jQuery("#tnpc-blocks").fadeOut(animationDuration);
    jQuery("#tnpc-global-styles").fadeOut(animationDuration);
    jQuery("#tnpc-mobile-tab").fadeOut(animationDuration);
    jQuery("#tnpc-test-tab").fadeOut(animationDuration);

    jQuery("#tnpc-block-options").fadeIn(animationDuration);

}

function tnpc_hide_block_options() {

    const animationDuration = 500;

    jQuery("#tnpc-block-options").fadeOut(animationDuration);

    var $activeTab = jQuery(".tnpc-tabs .tablinks.active");
    jQuery('#' + $activeTab.data('tabId')).fadeIn(animationDuration);

    jQuery("#tnpc-block-options-form").html('');

}

function tnpc_mobile_preview() {

    var d = document.getElementById("tnpc-mobile-preview").contentWindow.document;
    d.open();

    d.write("<!DOCTYPE html>\n<html>\n<head>\n");
    d.write("<link rel='stylesheet' href='" + TNP_HOME_URL + "?na=emails-composer-css&ver=" + Math.random() + "' type='text/css'>");
    d.write("<style>.tnpc-row-delete, .tnpc-row-edit-block, .tnpc-row-clone { display: none; }</style>");
    d.write("<style>body::-webkit-scrollbar {width: 0px;background: transparent;}</style>");
    d.write("<style>body{scrollbar-width: none; -ms-overflow-style: none;}</style>");
    d.write("</head>\n<body style='margin: 0; padding: 0;'><div style='width: 320px!important'>");
    d.write(jQuery("#newsletter-builder-area-center-frame-content").html());
    d.write("</div>\n</body>\n</html>");
    d.close();
}

function tnpc_save(form) {

    form.elements["options[message]"].value = tnpc_get_email_content_from_builder_area();
    if (document.getElementById("options-title")) {
        form.elements["options[subject]"].value = jQuery('#options-title').val();
    } else {
        form.elements["options[subject]"].value = "";
    }

    var global_form = document.getElementById("tnpc-global-styles-form");
    //Copy "Global styles" form inputs into main form
    tnpc_copy_form(global_form, form);

}

function tnpc_get_email_content_from_builder_area() {

    var $elMessage = jQuery("#newsletter-builder-area-center-frame-content").clone();

    $elMessage.find('.tnpc-row-delete').remove();
    $elMessage.find('.tnpc-row-edit-block').remove();
    $elMessage.find('.tnpc-row-clone').remove();
    $elMessage.find('.tnpc-row').removeClass('ui-draggable');
    $elMessage.find('#sortable-helper').remove();

    return $elMessage.html();

}

function tnpc_copy_form(source, dest) {
    for (var i = 0; i < source.elements.length; i++) {
        var field = document.createElement("input");
        field.type = "hidden";
        field.name = source.elements[i].name;
        field.value = source.elements[i].value;

        // Non clona le select!
        //var clonedEl = source.elements[i].cloneNode();
        //clonedEl.style.display = 'none';
        dest.appendChild(field);
    }
}

function tnpc_test() {
    let form = document.getElementById("tnpc-form");
    tnpc_save(form);
    form.act.value = "test";
    form.submit();
}

function openTab(evt, tabName) {
    evt.preventDefault();
    // Declare all variables
    var i, tabcontent, tablinks;

    // Get all elements with class="tabcontent" and hide them
    tabcontent = document.getElementsByClassName("tabcontent");
    for (i = 0; i < tabcontent.length; i++) {
        tabcontent[i].style.display = "none";
    }

    // Get all elements with class="tablinks" and remove the class "active"
    tablinks = document.getElementsByClassName("tablinks");
    for (i = 0; i < tablinks.length; i++) {
        tablinks[i].className = tablinks[i].className.replace(" active", "");
    }

    // Show the current tab, and add an "active" class to the button that opened the tab
    document.getElementById(tabName).style.display = "block";
    evt.currentTarget.className += " active";
}

function tnpc_scratch() {

    jQuery('#newsletter-builder-area-center-frame-content').html(" ");
    init_builder_area();

}

function tnpc_reload_options(e) {
    e.preventDefault();
    let options = jQuery("#tnpc-block-options-form").serializeArray();
    for (let i = 0; i < options.length; i++) {
        if (options[i].name === 'action') {
            options[i].value = 'tnpc_options';
        }
    }

    jQuery("#tnpc-block-options-form").load(ajaxurl, options);
}

function tnpc_add_global_options(data) {
    let globalOptions = jQuery("#tnpc-global-styles-form").serializeArray();
    for (let i = 0; i < globalOptions.length; i++) {
        globalOptions[i].name = globalOptions[i].name.replace("[options_", "[").replace("options[", "composer[").replace("composer_", "");
        if (Array.isArray(data)) {
            data.push(globalOptions[i]);
        } else {
            //Inline edit data format is object not array
            data[globalOptions[i].name] = globalOptions[i].value;
        }
    }
}

// ==================================================== //
// =================    PRESET    ===================== //
// ==================================================== //

//TODO non va bene tenere nel global space variabili che altri potrebbero accidentalmente modificare/usare
// ma questo è un test
const toastBottom = new TnpToast({duration: 5000, position: 'bottom right', wrapperPadding: '70px 20px'});

//TODO - spostare gestione dei preset in contesto privato ma aggiungendo comunque a window le funzioni triggerate da html (load_preset, delete_preset,...) per mantenere compatibilità?
const presetListModal = new TNPModal({
    closeWhenClickOutside: true,
    showClose: true,
    style: {
        backgroundColor: '#ECF0F1',
        height: '400px',
        width: '740px',
    },
    onClose: function () {
        start_composer();
        //Enable buttons
        jQuery('.tnpc-controls input[type=button]').attr('disabled', false);
    }
});

function tnpc_show_presets() {

    jQuery('.tnpc-controls input[type=button]').attr('disabled', true);

    const elModalContent = presetListModal.open();

    jQuery(elModalContent).load(ajaxurl, {
        action: "tnpc_presets",
        context_type: tnp_context_type,
    });

}

function tnpc_load_preset(id, subject, isEditMode) {

    presetListModal.close();

    jQuery('#newsletter-builder-area-center-frame-content').load(ajaxurl, {
        action: "tnpc_presets",
        id: id
    }, function () {
        start_composer();

        if (!isEditMode) {
            //Enable buttons
            jQuery('.tnpc-controls input[type=button]').attr('disabled', false);
        }

        if (subject && subject.length > 0) {
            jQuery('#options-title').val(tnpc_remove_double_quotes_escape_from(subject));
        }
    });

}

function tnpc_save_preset(form) {

    const presetName = tnpc_remove_double_quotes_from(document.querySelector('#options-title').value);

    const presetNameModal = new TNPModal({
        title: 'Choose a preset name',
        content: '<input type="text" id="preset_name" style="width: 100%" placeholder="Preset name" value="' + presetName + '"/>',
        showConfirm: true,
        clickConfirmOnPressEnter: true,
        onConfirm: function () {
            const inputEl = document.querySelector('#preset_name');
            document.querySelector('#options-title').value = inputEl.value;
            tnpc_save(form);
            form.submit();
        }
    });

    presetNameModal.open();

}

function tnpc_delete_preset(presetId, name, event) {
    event.stopPropagation();

    const presetDeleteModal = new TNPModal({
        title: `Are you sure to delete "${name}" preset?`,
        confirmText: 'DELETE PRESET',
        confirmClassName: 'button-secondary button-danger',
        showConfirm: true,
        onConfirm: function () {

            const wrapperPresetEl = event.target.closest(".tnpc-preset");

            jQuery.ajax({
                type: 'POST',
                dataType: 'json',
                url: ajaxurl,
                data: {
                    action: 'tnpc_delete_preset',
                    _wpnonce: tnp_preset_nonce,
                    presetId: presetId
                },
                success: function (response) {
                    if (response.success) {
                        wrapperPresetEl.parentNode.removeChild(wrapperPresetEl);
                        toastBottom.success('Preset successfully deleted!');
                    }
                }
            });

        }
    });

    presetDeleteModal.open();

}

function tnpc_edit_preset(presetId, name, event) {
    event.stopPropagation();
    tnpc_load_preset(presetId, name, true);

    //DISABLE BUTTON AND SHOW UPDATE BUTTON
    const composerForm = document.querySelector('#tnpc-form');
    const buttons = composerForm.querySelectorAll('input[type=button]');
    const updatePresetButton = composerForm.querySelector('#update-preset-button');

    for (btn of buttons) {
        if (btn.id && btn.id === 'save-preset-button') {
            btn.style.display = 'none';
            updatePresetButton.style.display = 'inline';
            updatePresetButton.disabled = false;
        } else {
            btn.disabled = true;
        }
    }

    //Add preset id hidden field
    const presetIdfield = document.createElement("input");
    presetIdfield.type = "hidden";
    presetIdfield.name = "preset_id";
    presetIdfield.value = presetId;
    composerForm.appendChild(presetIdfield);

}

function tnpc_remove_double_quotes_escape_from(str) {
    return str.replace(/\\"/g, '"');
}

function tnpc_remove_double_quotes_from(str) {
    return str.replace(/['"]+/g, '');
}

function tnpc_update_preset(form) {

    const presetName = tnpc_remove_double_quotes_from(document.querySelector('#options-title').value);

    const presetNameModal = new TNPModal({
        title: 'Choose a preset name',
        content: '<input type="text" id="preset_name" style="width: 100%" placeholder="Preset name" value="' + presetName + '"/>',
        showConfirm: true,
        clickConfirmOnPressEnter: true,
        onConfirm: function () {
            const inputEl = document.querySelector('#preset_name');
            document.querySelector('#options-title').value = inputEl.value;
            tnpc_save(form);
            form.submit();
        }
    });

    presetNameModal.open();

}

// ========================================================= //
// =================    PRESET FINE    ===================== //
// ========================================================= //

jQuery(document).ready(function () {
    'use strict'

    var TNPInlineEditor = (function () {

        var className = 'tnpc-inline-editable';
        var newInputName = 'new_name';
        var activeInlineElements = [];

        function init() {
            // find all inline editable elements
            jQuery('#newsletter-builder-area-center-frame-content').on('click', '.' + className, function (e) {
                e.preventDefault();
                removeAllActiveElements();

                var originalEl = jQuery(this).hide();
                var newEl = jQuery(getEditableComponent(this.innerText.trim(), this.dataset.id, this.dataset.type, originalEl)).insertAfter(this);

                activeInlineElements.push({'originalEl': originalEl, 'newEl': newEl});

                //Add submit event listener for newly created block
                jQuery('.tnpc-inline-editable-form-' + this.dataset.type + this.dataset.id).on('submit', function (e) {
                    submit(e, newEl, jQuery(originalEl));
                });

                //Add close event listener for newly created block
                jQuery('.tnpc-inline-editable-form-actions .tnpc-dismiss-' + this.dataset.type + this.dataset.id).on('click', function (e) {
                    removeAllActiveElements();
                });

            });

            // Close all created elements if clicked outside
            jQuery('#newsletter-builder-area-center-frame-content').on('click', function (e) {
                if (activeInlineElements.length > 0
                    && !jQuery(e.target).hasClass(className)
                    && jQuery(e.target).closest('.tnpc-inline-editable-container').length === 0) {
                    removeAllActiveElements();
                }
            });

        }

        function removeAllActiveElements() {
            activeInlineElements.forEach(function (obj) {
                obj.originalEl.show();

                obj.newEl.off();
                obj.newEl.remove();
            });

            activeInlineElements = []
        }

        function getEditableComponent(value, id, type, originalEl) {

            var element = '';

            //COPY FONT STYLE FROM ORIGINAL ELEMENT
            var fontFamily = originalEl.css('font-family');
            var fontSize = originalEl.css('font-size');
            var styleAttr = "style='font-family:" + fontFamily + ";font-size:" + fontSize + ";'";

            switch (type) {
                case 'text': {
                    element = "<textarea name='" + newInputName + "' class='" + className + "-textarea' rows='5' " + styleAttr + ">" + value + "</textarea>";
                    break;
                }
                case 'title': {
                    element = "<textarea name='" + newInputName + "' class='" + className + "-textarea' rows='2'" + styleAttr + ">" + value + "</textarea>";
                    break;
                }
            }

            var component = "<td>";
            component += "<form class='tnpc-inline-editable-form tnpc-inline-editable-form-" + type + id + "'>";
            component += "<input type='hidden' name='id' value='" + id + "'>";
            component += "<input type='hidden' name='type' value='" + type + "'>";
            component += "<input type='hidden' name='old_value' value='" + value + "'>";
            component += "<div class='tnpc-inline-editable-container'>";
            component += element;
            component += "<div class='tnpc-inline-editable-form-actions'>";
            component += "<button type='submit'><span class='dashicons dashicons-yes-alt' title='save'></span></button>";
            component += "<span class='dashicons dashicons-dismiss tnpc-dismiss-" + type + id + "' title='close'></span>";
            component += "</div>";
            component += "</div>";
            component += "</form>";
            component += "</td>";
            return component;
        }

        function submit(e, elementToDeleteAfterSubmit, elementToShow) {
            e.preventDefault();

            var id = elementToDeleteAfterSubmit.find('form input[name=id]').val();
            var type = elementToDeleteAfterSubmit.find('form input[name=type]').val();
            var newValue = elementToDeleteAfterSubmit.find('form [name="' + newInputName + '"]').val();

            ajax_render_block(elementToShow, type, id, newValue);

            elementToDeleteAfterSubmit.remove();
            elementToShow.show();

        }

        function ajax_render_block(inlineElement, type, postId, newContent) {

            var target = inlineElement.closest('.edit-block');
            var container = target.closest('table');
            var blockContent = target.children('.tnpc-block-content');

            if (container.hasClass('tnpc-row-block')) {
                var data = {
                    'action': 'tnpc_render',
                    'b': container.data('id'),
                    'full': 1,
                    '_wpnonce': tnp_nonce,
                    'options': {
                        'inline_edits': [{
                            'type': type,
                            'post_id': postId,
                            'content': newContent
                        }]
                    },
                    'encoded_options': blockContent.data('json')
                };

                tnpc_add_global_options(data);

                jQuery.post(ajaxurl, data, function (response) {
                    var new_row = jQuery(response);

                    container.before(new_row);
                    container.remove();

                    new_row.add_delete();
                    new_row.add_block_edit();
                    new_row.add_block_clone();

                    //Force reload options
                    if (new_row.hasClass('tnpc-row-block')) {
                        new_row.find(".tnpc-row-edit-block").click();
                    }

                    tnpc_mobile_preview();

                }).fail(function () {
                    alert("Block rendering failed.");
                });

            }

        }

        return {init};
    })();

    TNPInlineEditor.init();

});

// =================================================== //
// ===============   GLOBAL STYLE   ================== //
// =================================================== //

(function globalStyleIIFE() {

    var _elTrigger = document.querySelector('#tnpc-global-styles-form [name="apply"]');

    _elTrigger.addEventListener('click', function (e) {
        e.preventDefault();

        var data = {
            'action': 'tnpc_regenerate_email',
            'content': tnpc_get_email_content_from_builder_area(),
            '_wpnonce': tnp_nonce,
        };

        tnpc_add_global_options(data);

        jQuery.post(ajaxurl, data, function (response) {
            if (response && response.success) {
                jQuery('#newsletter-builder-area-center-frame-content').html(response.data.content);
                //Change background color of builder area
                _setBuilderAreaBackgroundColor(document.getElementById('options-options_composer_background').value);
                init_builder_area();
                tnpc_mobile_preview();

                toastBottom.success(response.data.message);
            } else {
                toastBottom.error(response.data.message);
            }
        });

    });

})();
