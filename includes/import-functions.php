<?php

use PhpOffice\PhpSpreadsheet\IOFactory;

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'ysci_upload_file' ) ) {

	/**
	 * Handle file upload via AJAX
	 */
	function ysci_upload_file() {
		check_ajax_referer( 'ysci_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		if ( empty( $_FILES['ysci-file'] ) ) {
			wp_send_json_error( 'No file uploaded.' );
		}

		$file = $_FILES['ysci-file'];

		// Validate file type
		$allowed_types = array( 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' );

		if ( ! in_array( $file['type'], $allowed_types ) ) {
			wp_send_json_error( 'Invalid file type. Only .xlsx files are allowed.' );
		}

		// Validate file size (e.g., 5MB limit)
		$max_size = 5 * 1024 * 1024; // 5MB

		if ( $file['size'] > $max_size ) {
			wp_send_json_error( 'File size exceeds the maximum limit of 5MB.' );
		}

		// Handle file upload
		$upload = wp_handle_upload( $file, array( 'test_form' => false ) );

		if ( isset( $upload['error'] ) ) {
			wp_send_json_error( $upload['error'] );
		}

		wp_send_json_success( $upload );
	}

	add_action( 'wp_ajax_ysci_upload_file', 'ysci_upload_file' );
}

if ( ! function_exists( 'ysci_import_customers' ) ) {

	/**
	 * Handle import process via AJAX
	 */
	function ysci_import_customers() {
		check_ajax_referer( 'ysci_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		$file_path       = isset( $_POST['file_path'] ) ? sanitize_text_field( $_POST['file_path'] ) : '';
		$update_existing = isset( $_POST['update_existing'] ) && $_POST['update_existing'];
		$send_email      = isset( $_POST['send_email'] ) && $_POST['send_email'];
		$email_from      = isset( $_POST['email_from'] ) ? sanitize_email( $_POST['email_from'] ) : '';
		$email_content   = isset( $_POST['email_content'] ) ? wp_kses_post( $_POST['email_content'] ) : '';

		if ( ! empty( $email_from ) ) {
			update_option( 'ysci-email-from', $email_from );
		}

		if ( ! empty( $email_content ) ) {
			update_option( 'ysci-email-content', $email_content );
		}

		if ( empty( $file_path ) || ! file_exists( $file_path ) ) {
			wp_send_json_error( 'File not found.' );
		}

		try {
			$spreadsheet = IOFactory::load( $file_path );
			$sheet       = $spreadsheet->getActiveSheet();
			$rows        = $sheet->toArray();

			$report  = array();
			$created = 0;
			$updated = 0;
			$skipped = 0;

			foreach ( $rows as $index => $row ) {
				if ( $index === 0 ) {
					continue; // Skip header row
				}

				$email        = sanitize_email( $row[1] );
				$phone        = sanitize_text_field( $row[0] );
				$first_name   = sanitize_text_field( $row[2] );
				$last_name    = sanitize_text_field( $row[3] );
				$company_name = sanitize_text_field( $row[4] );

				if ( empty( $email ) || ! is_email( $email ) ) {
					$report[] = "Skipped row $index: Invalid email address.";
					$skipped ++;
					continue;
				}

				$user = get_user_by( 'email', $email );

				if ( $user && ! $update_existing ) {
					$report[] = "Skipped row $index: User already exists.";
					$skipped ++;
					continue;
				}

				$user_data = array(
					'user_login' => $email,
					'user_email' => $email,
					'first_name' => $first_name,
					'last_name'  => $last_name,
					'role'       => 'customer',
					'meta_input' => array(
						'phone'        => $phone,
						'company_name' => $company_name,
					),
				);

				if ( $user ) {
					$user_data['ID'] = $user->ID;
					$user_id         = wp_update_user( $user_data );
					$updated ++;
					$report[] = "Updated user: $email";
				} else {
					$user_data['user_pass'] = wp_generate_password();
					$user_id                = wp_insert_user( $user_data );
					$created ++;
					$report[] = "Created user: $email";
				}

				if ( is_wp_error( $user_id ) ) {
					$report[] = "Error processing row $index: " . $user_id->get_error_message();
					$skipped ++;
					continue;
				}

				if ( $send_email ) {
					$email_replacements = array(
						'{first_name}'   => $first_name,
						'{last_name}'    => $last_name,
						'{company_name}' => $company_name,
						'{user_email}'   => $email,
						'{password}'     => $user_data['user_pass'],
						'{login_url}'    => wp_login_url(),
					);

					$email_body = str_replace( array_keys( $email_replacements ), array_values( $email_replacements ), $email_content );

					wp_mail( $email, 'Your Account Information', $email_body, array( 'From: ' . $email_from ) );
				}
			}

			wp_send_json_success( array(
				'report'  => implode( "\n", $report ),
				'created' => $created,
				'updated' => $updated,
				'skipped' => $skipped,
			) );
		} catch ( Exception $e ) {
			wp_send_json_error( 'Error processing file: ' . $e->getMessage() );
		}
	}

	add_action( 'wp_ajax_ysci_import_customers', 'ysci_import_customers' );
}