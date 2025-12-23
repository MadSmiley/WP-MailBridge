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
                // Réactiver toutes les langues
                $('#language option').prop('disabled', false).show();
                return;
            }

            var defaultSubject = selectedOption.data('subject');
            var defaultContent = selectedOption.data('content');
            var variables = selectedOption.data('variables');
            var pluginName = selectedOption.data('plugin');
            var expectedLanguages = selectedOption.data('languages');

            // Auto-fill template slug
            var typeId = selectedOption.val();
            if (!$('#template_slug').val()) {
                $('#template_slug').val(typeId);
            }

            // Auto-fill and lock plugin name
            if (pluginName) {
                $('#plugin_name').val(pluginName).prop('readonly', true).css('background-color', '#f0f0f1');
            }

            // Filter languages based on email type requirements
            if (expectedLanguages && Array.isArray(expectedLanguages) && expectedLanguages.length > 0) {
                var currentLanguage = $('#language').val();
                var languageStillAvailable = false;

                $('#language option').each(function() {
                    var langCode = $(this).val();
                    if (expectedLanguages.indexOf(langCode) !== -1) {
                        $(this).prop('disabled', false).show();
                        if (langCode === currentLanguage) {
                            languageStillAvailable = true;
                        }
                    } else {
                        $(this).prop('disabled', true).hide();
                    }
                });

                // Si la langue actuelle n'est plus disponible, sélectionner la première langue disponible
                if (!languageStillAvailable) {
                    $('#language').val(expectedLanguages[0]);
                }
            } else {
                // Aucune restriction de langue, tout afficher
                $('#language option').prop('disabled', false).show();
            }

            // Auto-fill subject if empty
            if (!$('#subject').val() && defaultSubject) {
                $('#subject').val(defaultSubject);
            }

            // Auto-fill content if empty (géré par le listener CodeMirror plus bas)
            // La logique a été déplacée dans la section d'initialisation de CodeMirror

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

            // Récupérer l'éditeur CodeMirror
            var contentTextarea = document.getElementById('content');
            if (contentTextarea && typeof wp !== 'undefined' && typeof wp.codeEditor !== 'undefined') {
                var editor = wp.codeEditor.getInstance(contentTextarea);
                if (editor && editor.codemirror) {
                    editor.codemirror.replaceSelection(variable);
                    editor.codemirror.focus();
                }
            } else {
                // Fallback pour textarea simple
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

        /**
         * Initialiser CodeMirror pour l'éditeur de contenu
         */
        var contentEditor = null;
        var contentTextarea = document.getElementById('content');

        // Fonction pour mettre à jour l'aperçu (globale)
        function updatePreview() {
            if (!contentEditor || !contentEditor.codemirror) {
                return;
            }

            var content = contentEditor.codemirror.getValue();
            var preview = $('#mailbridge-preview');

            if (!content.trim()) {
                preview.html('<em style="color: #999;">Preview will appear here...</em>');
                return;
            }

            // Remplacer les variables par des placeholders visuels
            var previewContent = content.replace(/\{\{([^}]+)\}\}/g, function(match, varName) {
                return '<span style="background: #fff3cd; padding: 2px 6px; border-radius: 3px; border: 1px solid #ffc107; color: #856404; font-family: monospace; font-size: 0.9em;">{{' + varName.trim() + '}}</span>';
            });

            preview.html(previewContent);
        }

        if (contentTextarea && typeof wp !== 'undefined' && typeof wp.codeEditor !== 'undefined') {
            var editorSettings = wp.codeEditor.defaultSettings ? _.clone(wp.codeEditor.defaultSettings) : {};
            editorSettings.codemirror = _.extend(
                {},
                editorSettings.codemirror,
                {
                    mode: 'text/html',
                    lineNumbers: true,
                    lineWrapping: true,
                    indentUnit: 4,
                    indentWithTabs: false,
                    tabSize: 4,
                    autoCloseTags: true,
                    matchBrackets: true,
                    styleActiveLine: true,
                    extraKeys: {
                        "Tab": function(cm) {
                            var spaces = Array(cm.getOption("indentUnit") + 1).join(" ");
                            cm.replaceSelection(spaces);
                        },
                        "Shift-Tab": function(cm) {
                            cm.execCommand("indentLess");
                        }
                    }
                }
            );

            contentEditor = wp.codeEditor.initialize(contentTextarea, editorSettings);

            // Mettre à jour l'aperçu lors des changements (avec debounce)
            var previewTimeout;
            contentEditor.codemirror.on('change', function() {
                clearTimeout(previewTimeout);
                previewTimeout = setTimeout(updatePreview, 500);
            });

            // Aperçu initial
            setTimeout(updatePreview, 500);

            // Mettre à jour le textarea avant la soumission du formulaire
            $('form').on('submit', function() {
                if (contentEditor && contentEditor.codemirror) {
                    contentEditor.codemirror.save();
                }
            });

            // Synchroniser avec l'auto-fill depuis email type reference
            $('#email_type_reference').on('change', function() {
                var selectedOption = $(this).find('option:selected');
                var defaultContent = selectedOption.data('content');

                if (defaultContent && contentEditor && contentEditor.codemirror) {
                    var currentContent = contentEditor.codemirror.getValue();
                    if (!currentContent.trim()) {
                        contentEditor.codemirror.setValue(defaultContent);
                        updatePreview();
                    }
                }
            });

            /**
             * Gestion des onglets Code/Preview
             */
            $('.mailbridge-tab').on('click', function() {
                var targetTab = $(this).data('tab');

                // Mettre à jour les onglets actifs
                $('.mailbridge-tab').removeClass('mailbridge-tab-active');
                $(this).addClass('mailbridge-tab-active');

                // Mettre à jour les panneaux actifs
                $('.mailbridge-tab-panel').removeClass('mailbridge-tab-panel-active').hide();
                $('.mailbridge-tab-panel[data-panel="' + targetTab + '"]').addClass('mailbridge-tab-panel-active').show();

                // Si on bascule vers le code, rafraîchir CodeMirror
                if (targetTab === 'code' && contentEditor && contentEditor.codemirror) {
                    setTimeout(function() {
                        contentEditor.codemirror.refresh();
                    }, 100);
                }

                // Si on bascule vers le preview, mettre à jour
                if (targetTab === 'preview') {
                    updatePreview();
                }
            });
        }

    });

})(jQuery);
