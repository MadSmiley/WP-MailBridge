/**
 * Admin JavaScript for MailBridge
 *
 * @package WP_MailBridge
 */

(function($) {
    'use strict';

    $(document).ready(function() {

        /**
         * Email type reference selector
         */
        $('#email_type_reference').on('change', function() {
            var selectedOption = $(this).find('option:selected');

            if (!selectedOption.val()) {
                $('#mailbridge-variables-info').hide();
                // Réactiver le champ plugin si on désélectionne
                $('#plugin_name').prop('readonly', false).css('background-color', '');
                return;
            }

            var defaultSubject = selectedOption.data('subject');
            var defaultContent = selectedOption.data('content');
            var variables = selectedOption.data('variables');
            var pluginName = selectedOption.data('plugin');

            // Auto-fill template slug
            var typeId = selectedOption.val();
            if (!$('#template_slug').val()) {
                $('#template_slug').val(typeId);
            }

            // Auto-fill and lock plugin name
            if (pluginName) {
                $('#plugin_name').val(pluginName).prop('readonly', true).css('background-color', '#f0f0f1');
            }

            // Auto-fill subject if empty
            if (!$('#subject').val() && defaultSubject) {
                $('#subject').val(defaultSubject);
            }

            // Auto-fill content if empty
            if (defaultContent) {
                var currentContent = '';

                // Check if we're using visual or text editor
                if (typeof tinymce !== 'undefined' && tinymce.get('content')) {
                    currentContent = tinymce.get('content').getContent();
                    if (!currentContent.trim()) {
                        tinymce.get('content').setContent(defaultContent);
                    }
                } else {
                    currentContent = $('#content').val();
                    if (!currentContent.trim()) {
                        $('#content').val(defaultContent);
                    }
                }
            }

            // Display available variables
            if (variables && typeof variables === 'object') {
                var variablesList = '<ul>';
                $.each(variables, function(key, label) {
                    variablesList += '<li><code>{{' + key + '}}</code> - ' + label + '</li>';
                });
                variablesList += '</ul>';

                $('#mailbridge-variables-list').html(variablesList);
                $('#mailbridge-variables-info').slideDown();
            } else {
                $('#mailbridge-variables-info').hide();
            }
        });

        /**
         * Template slug auto-generation from name
         */
        $('#template_name').on('blur', function() {
            var templateName = $(this).val();
            var templateSlug = $('#template_slug').val();

            // Only auto-generate if slug is empty
            if (!templateSlug && templateName) {
                var slug = templateName
                    .toLowerCase()
                    .replace(/[^a-z0-9]+/g, '_')
                    .replace(/^_+|_+$/g, '');
                $('#template_slug').val(slug);
            }
        });

        /**
         * Delete template confirmation
         */
        $('.mailbridge-delete-template').on('click', function(e) {
            e.preventDefault();

            if (!confirm(mailbridgeAdmin.confirmDelete)) {
                return false;
            }

            var templateId = $(this).data('id');
            $('#delete-template-id').val(templateId);
            $('#mailbridge-delete-form').submit();
        });

        /**
         * Show/hide variables in email types page
         */
        $('.mailbridge-show-variables').on('click', function() {
            var typeId = $(this).data('type-id');
            var variablesDiv = $('#variables-' + typeId);

            if (variablesDiv.is(':visible')) {
                variablesDiv.slideUp();
                $(this).text($(this).text().replace('Hide', 'Show'));
            } else {
                variablesDiv.slideDown();
                $(this).text($(this).text().replace('Show', 'Hide'));
            }
        });

        /**
         * Auto-dismiss notices after 5 seconds
         */
        setTimeout(function() {
            $('.notice.is-dismissible').fadeOut('slow', function() {
                $(this).remove();
            });
        }, 5000);

        /**
         * Insert variable into editor
         */
        $(document).on('click', '#mailbridge-variables-list code', function() {
            var variable = $(this).text();

            // Insert into TinyMCE if available
            if (typeof tinymce !== 'undefined' && tinymce.get('content')) {
                tinymce.get('content').execCommand('mceInsertContent', false, variable);
            } else {
                // Insert into textarea
                var textarea = $('#content');
                var cursorPos = textarea.prop('selectionStart');
                var textBefore = textarea.val().substring(0, cursorPos);
                var textAfter = textarea.val().substring(cursorPos);

                textarea.val(textBefore + variable + textAfter);
            }

            // Visual feedback
            $(this).css('background', '#2271b1').css('color', '#fff');
            setTimeout(function() {
                $(this).css('background', '').css('color', '');
            }.bind(this), 300);
        });

        /**
         * Form validation
         */
        $('form[action*="mailbridge"]').on('submit', function(e) {
            var templateSlug = $('#template_slug').val();

            if (templateSlug) {
                // Validate slug format
                var slugPattern = /^[a-z0-9_]+$/;
                if (!slugPattern.test(templateSlug)) {
                    alert('Template slug must contain only lowercase letters, numbers, and underscores.');
                    $('#template_slug').focus();
                    e.preventDefault();
                    return false;
                }
            }
        });

        /**
         * Highlight code blocks on hover
         */
        $('pre code').parent().css('cursor', 'pointer').on('click', function() {
            // Select all text in code block
            var range = document.createRange();
            range.selectNode($(this).find('code')[0]);
            window.getSelection().removeAllRanges();
            window.getSelection().addRange(range);

            // Visual feedback
            $(this).css('background', '#e0e0e0');
            setTimeout(function() {
                $(this).css('background', '');
            }.bind(this), 200);
        });

    });

})(jQuery);
