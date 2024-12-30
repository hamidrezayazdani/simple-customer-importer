jQuery(document).ready(function($) {
    // WordPress AJAX configuration
    const wpConfig = {
        ajaxUrl: typeof ysciParams !== 'undefined' ? ysciParams.ajax_url : '/wp-admin/admin-ajax.php',
        nonce: typeof ysciParams !== 'undefined' ? ysciParams.nonce : '',
        actions: {
            upload: 'ysci_upload_file',
            import: 'ysci_import_customers'
        }
    };

    // AJAX request handlers
    const AjaxHandlers = {
        uploadFile: function(formData) {
            formData.append('action', wpConfig.actions.upload);
            formData.append('nonce', wpConfig.nonce);

            return $.ajax({
                url: wpConfig.ajaxUrl,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false
            });
        },

        importCustomers: function(data) {
            return $.ajax({
                url: wpConfig.ajaxUrl,
                type: 'POST',
                data: {
                    action: wpConfig.actions.import,
                    nonce: wpConfig.nonce,
                    ...data
                }
            });
        }
    };

    // Notification system
    const Notifications = {
        show: function(message, type = 'success') {
            const notification = $(`
                <div class="ysci-snackbar ${type}">
                    ${message}
                </div>
            `);

            $('body').append(notification);

            setTimeout(() => {
                notification.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 30000);
        }
    };

    // Step navigation functionality
    const StepManager = {
        currentStep: 1,
        totalSteps: 4,

        init() {
            this.updateSteps();
            this.bindEvents();
        },

        bindEvents() {
            $('.step').on('click', (e) => {
                const stepNumber = parseInt($(e.currentTarget).data('step'));
                if (stepNumber < this.currentStep) {
                    this.goToStep(stepNumber);
                }
            });
        },

        updateSteps() {
            $('.step').each((_, step) => {
                const $step = $(step);
                const stepNumber = parseInt($step.data('step'));
                $step.removeClass('active completed');

                if (stepNumber === this.currentStep) {
                    $step.addClass('active');
                } else if (stepNumber < this.currentStep) {
                    $step.addClass('completed');
                }
            });

            // Show/hide content
            $('.step-content').each((_, content) => {
                $(content).toggle($(content).data('step') == this.currentStep);
            });
        },

        nextStep() {
            if (this.currentStep < this.totalSteps) {
                this.currentStep++;
                this.updateSteps();
            }
        },

        previousStep() {
            if (this.currentStep > 1) {
                this.currentStep--;
                this.updateSteps();
            }
        },

        goToStep(step) {
            if (step <= this.totalSteps) {
                this.currentStep = step;
                this.updateSteps();
            }
        }
    };

    // Initialize step manager
    StepManager.init();

    // File upload handling
    $('#ysci-file').on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            $('#ysci-upload-progress')
              .html(`Selected file: ${file.name}`)
              .hide()
              .fadeIn(300);
        }
    });

    // File upload form submission
    $('#ysci-upload-form').on('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const $progress = $('<div class="progress-bar"><div class="progress-bar-fill"></div></div>');

        $('#ysci-upload-progress').html('').append($progress);

        AjaxHandlers.uploadFile(formData)
          .done(function(response) {
              if (response.success) {
                  $('#ysci-upload-form').data('file_path', response.data.file); // Set file_path
                  Notifications.show('Upload successful!', 'success');
                  StepManager.nextStep(); // Move to step 2
              } else {
                  Notifications.show('Upload failed: ' + response.data, 'error');
              }
          })
          .fail(function() {
              Notifications.show('Upload failed.', 'error');
          });
    });

    // Import process
    $('#ysci-start-import').on('click', function() {
        const $progress = $('<div class="progress-bar"><div class="progress-bar-fill"></div></div>');
        $('#ysci-import-progress').html('').append($progress);

        // Move to step 3 immediately after clicking the "Import" button
        StepManager.goToStep(3);

        let progress = 0;
        const progressInterval = setInterval(() => {
            progress += 1;
            $progress.find('.progress-bar-fill').css('width', progress + '%');
            if (progress >= 100) clearInterval(progressInterval);
        }, 100);

        // Get email content from TinyMCE editor
        const emailContent = tinymce.get('ysci-email-content') ? tinymce.get('ysci-email-content').getContent() : $('#ysci-email-content').val();

        const importData = {
            file_path: $('#ysci-upload-form').data('file_path'),
            update_existing: $('#ysci-update-existing').is(':checked'),
            send_email: $('#ysci-send-email').is(':checked'),
            email_from: $('#ysci-email-from').val(),
            email_content: emailContent // Use the content from the editor
        };

        AjaxHandlers.importCustomers(importData)
          .done(function(response) {
              clearInterval(progressInterval);
              if (response.success) {
                  $progress.find('.progress-bar-fill').css('width', '100%');
                  Notifications.show('Import successful!', 'success');
                  StepManager.goToStep(4); // Move to step 4 after import is completed
                  $('#ysci-import-report').val(response.data.report);
              } else {
                  Notifications.show('Import failed: ' + response.data, 'error');
              }
          })
          .fail(function() {
              clearInterval(progressInterval);
              Notifications.show('Import failed.', 'error');
          });
    });

    // Toggle email options
    $('#ysci-send-email').on('change', function() {
        $('#ysci-email-options').slideToggle(300);
    });

    // Clipboard functionality
    $('#ysci-email-placeholders kbd').on('click', function() {
        const placeholder = $(this).text();
        const $this = $(this);

        navigator.clipboard.writeText(placeholder).then(function() {
            $this.addClass('copied');
            setTimeout(() => $this.removeClass('copied'), 1000);
            Notifications.show('Copied to clipboard');
        });
    });
});