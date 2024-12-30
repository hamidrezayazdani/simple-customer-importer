<?php

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'ysci_import_customers_page' ) ) {

	/**
	 * @return void
	 */
	function ysci_import_customers_page() {
		$email_from    = get_option( 'ysci-email-from', '' );
		$email_content = get_option( 'ysci-email-content', '' );
		?>
        <div class="wrap">
            <h1>Import Customers</h1>

            <!-- Step Navigator -->
            <div class="step-navigator">
                <div class="step active" data-step="1">
                    <div class="step-circle">1</div>
                    <div class="step-title">Upload File</div>
                </div>
                <div class="step" data-step="2">
                    <div class="step-circle">2</div>
                    <div class="step-title">Preview & Options</div>
                </div>
                <div class="step" data-step="3">
                    <div class="step-circle">3</div>
                    <div class="step-title">Import Progress</div>
                </div>
                <div class="step" data-step="4">
                    <div class="step-circle">4</div>
                    <div class="step-title">Import Report</div>
                </div>
            </div>

            <!-- Step Contents -->
            <div class="step-content" data-step="1">
                <div class="form-container">
                    <form id="ysci-upload-form" enctype="multipart/form-data">
                        <div class="file-upload">
                            <label class="file-upload-label">
                                <span class="file-upload-icon">📄</span>
                                <span>Drop your Excel file here or click to browse</span>
                                <input type="file" name="ysci-file" id="ysci-file" accept=".xlsx" required>
                            </label>
                        </div>
                        <div id="ysci-upload-progress"></div>
                        <button type="submit" class="button">Upload</button>
                    </form>
                </div>
            </div>

            <div class="step-content" data-step="2" style="display: none;">
                <div class="form-container">
                    <div id="ysci-preview"></div>
                    <div class="switch-control">
                        <input type="checkbox" id="ysci-update-existing">
                        <label class="switch-label" for="ysci-update-existing">Update existing users</label>
                    </div>
                    <div class="switch-control">
                        <input type="checkbox" id="ysci-send-email">
                        <label class="switch-label" for="ysci-send-email">Send email notification</label>
                    </div>
                    <div id="ysci-email-options" style="display:none;">
                        <label for="ysci-email-from">From:</label> <input type="text" id="ysci-email-from" value="<?php echo esc_attr( $email_from ); ?>">
						<?php wp_editor( $email_content, 'ysci-email-content', array( 'textarea_name' => 'ysci-email-content' ) ); ?>
                        <div id="ysci-email-placeholders">
                            <kbd>{first_name}</kbd>
                            <kbd>{last_name}</kbd>
                            <kbd>{company_name}</kbd>
                            <kbd>{user_email}</kbd>
                            <kbd>{password}</kbd>
                            <kbd>{login_url}</kbd>
                        </div>
                    </div>
                    <button id="ysci-start-import" class="button">Start Import</button>
                </div>
            </div>

            <div class="step-content" data-step="3" style="display: none;">
                <div class="form-container">
                    <div id="ysci-import-progress"></div>
                </div>
            </div>

            <div class="step-content" data-step="4" style="display: none;">
                <div class="form-container">
                    <textarea id="ysci-import-report" style="width: 100%" readonly></textarea>
                </div>
            </div>
        </div>
		<?php
	}
}