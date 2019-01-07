<?php

if ( ! class_exists( 'TCPDF' ) ) {

	// Always load config file for TCPDF.
	require_once( FORMFILL_PDF_DIR . '/tcpdf/config/tcpdf_config_alt.php' );
	// Include the main TCPDF library.
	require_once( FORMFILL_PDF_DIR . '/tcpdf/tcpdf.php' );

	class MYPDF extends TCPDF {
		private $urlimage = '';

		public function __construct( $img_url, $orientation = 'P', $unit = 'mm', $format = 'A4', $unicode = true, $encoding = 'UTF-8', $diskcache = false, $pdfa = false ) {
			parent::__construct( $orientation, $unit, $format, $unicode, $encoding, $diskcache, $pdfa );
			$this->setUrlimage( $img_url );
		}

		//Page header
		public function Header() {
			// get the current page break margin
			$bMargin = $this->getBreakMargin();
			// get current auto-page-break mode
			$auto_page_break = $this->AutoPageBreak;
			// disable auto-page-break
			$this->SetAutoPageBreak( false, 0 );
			// set bacground image
			$img_file = FORMFILL_PDF_DIR . '/images/atlantis_watermark.png';
			$this->Image( $img_file, 0, 30, 210, 250, '', '', '', false, 300, '', false, false, 0 );
			// restore auto-page-break status
			$this->SetAutoPageBreak( $auto_page_break, $bMargin );
			// set the starting point for the page content
			$this->setPageMark();

			// Logo
			$image_file = FORMFILL_PDF_DIR . '/images/atlantis_logo.png';
			$this->Image( $image_file, 10, 10, 70, '', '', '', 'T', false, 300, '', false, false, 0, false, false, false );

		}

		/**
		 * @return string
		 */
		public function getUrlimage() {
			return $this->urlimage;
		}

		/**
		 * @param string $urlimage
		 */
		public function setUrlimage( $urlimage ) {
			$this->urlimage = $urlimage;
		}
	}
}

class FORMFILL_PDF_model {

	public function save( $form_id ) {
		global $wpdb;
		$success      = 0;
		$enable_pdf   = WDW_FM_Library::get( 'enable_pdf' ) == 1 ? 1 : 0;
		$send_to      = ( WDW_FM_Library::get( 'pdf_send_to_admin' ) == 'admin' ? 1 : 0 ) . ',' . ( WDW_FM_Library::get( 'pdf_send_to_user' ) == 'user' ? 1 : 0 );
		$pdf_path     = esc_html( stripslashes( WDW_FM_Library::get( 'pdf_path' ) ) );
		$pdf_template = esc_html( stripslashes( WDW_FM_Library::get( 'pdf_template' ) ) );
		$pdf_content  = htmlspecialchars_decode( stripslashes( WDW_FM_Library::get( 'pdf_content', '', false ) ) );
		$pdf_id       = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM " . $wpdb->prefix . "formfill_pdf_options WHERE form_id=%d", $form_id ) );
		if ( $pdf_id ) {
			$wpdb->update( $wpdb->prefix . 'formfill_pdf_options', array(
				'enable_pdf'   => $enable_pdf,
				'send_to'      => $send_to,
				'pdf_path'     => $pdf_path,
				'pdf_template' => $pdf_template,
				'pdf_content'  => $pdf_content,
			), array( 'form_id' => $form_id ) );
		} else {
			$wpdb->insert( $wpdb->prefix . 'formfill_pdf_options', array(
				'form_id'      => $form_id,
				'enable_pdf'   => $enable_pdf,
				'send_to'      => $send_to,
				'pdf_path'     => $pdf_path,
				'pdf_template' => $pdf_template,
				'pdf_content'  => $pdf_content,
			), array(
				'%d',
				'%d',
				'%s',
				'%s',
				'%s',
			) );
		}
		if ( $pdf_path ) {
			$full_path = ABSPATH;
			$pdf_path  = explode( '/', $pdf_path );
			foreach ( $pdf_path as $path ) {
				$full_path .= $path . '/';
				if ( ! is_dir( $full_path ) ) {
					mkdir( $full_path, 0777 );
				}
			}
		}

		return $success;
	}

	/**
	 * Delete.
	 *
	 * @param  int $form_id
	 *
	 * @return bool
	 */
	public function delete( $form_id ) {
		global $wpdb;
		$delete = $wpdb->query( $wpdb->prepare( 'DELETE ' . $wpdb->prefix . 'formfill_pdf_options, ' . $wpdb->prefix . 'formfill_pdf  
            FROM ' . $wpdb->prefix . 'formfill_pdf_options 
            INNER JOIN ' . $wpdb->prefix . 'formfill_pdf 
            ON ' . $wpdb->prefix . 'formfill_pdf_options.form_id = ' . $wpdb->prefix . 'formfill_pdf.form_id 
            WHERE ' . $wpdb->prefix . 'formfill_pdf_options. form_id="%d"', $form_id ) );

		return $delete;
	}

	/**
	 * Get data from formmaker.
	 *
	 * @param  int $form_id
	 *
	 * @return array
	 */
	public static function get_form_data( $form_id ) {
		global $wpdb;
		$label_order_current = $wpdb->get_var( $wpdb->prepare( 'SELECT `label_order_current` FROM ' . $wpdb->prefix . 'formmaker WHERE id="%d"', $form_id ) );
		$label_id            = array();
		$label_label         = array();
		$label_type          = array();
		$label_all           = explode( '#****#', $label_order_current );
		$label_all           = array_slice( $label_all, 0, count( $label_all ) - 1 );
		foreach ( $label_all as $key => $label_each ) {
			$label_id_each = explode( '#**id**#', $label_each );
			array_push( $label_id, $label_id_each[0] );
			$label_order_each = explode( '#**label**#', $label_id_each[1] );
			array_push( $label_label, $label_order_each[0] );
			array_push( $label_type, $label_order_each[1] );
		}

		return array( 'label_label' => $label_label, 'label_type' => $label_type );
	}

	/**
	 * Get data
	 *
	 * @param  int $form_id
	 *
	 * @return array
	 */
	public static function get_frontend_data( $form_id ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT `label_order_current`, `title` FROM ' . $wpdb->prefix . 'formmaker WHERE id="%d"', $form_id ) );

		return $row;
	}

	/**
	 * Get data
	 *
	 * @param  int $form_id
	 *
	 * @return array
	 */
	public function get_data( $form_id ) {
		global $wpdb;
		$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . 'formfill_pdf_options WHERE form_id="%d"', $form_id ) );
		if ( $row ) {
			return $row;
		}
		$row = (object) array(
			"form_id"      => "",
			"enable_pdf"   => 0,
			"send_to"      => "1,1",
			"pdf_path"     => "",
			"pdf_template" => "",
			"pdf_content"  => "",
		);

		return $row;
	}

	public static function get_pdf_data( $form_id ) {
		global $wpdb;
		$pdf_entries = array();
		$rows        = $wpdb->get_results( $wpdb->prepare( "SELECT `group_id`, `file_url` FROM " . $wpdb->prefix . "formfill_pdf WHERE form_id=%d", $form_id ) );
		if ( $rows ) {
			foreach ( $rows as $row ) {
				$pdf_entries[ $row->group_id ] = $row->file_url;
			}
		}

		return $pdf_entries;
	}

	public function frontend( $params ) {
		global $wpdb;
		$form_id             = $params['form_id'];
		$custom_fields_value = $params['custom_fields_value'];
		$form_currency       = WDW_FM_Library::get( 'form_currency' );
		$pdf                 = $this->get_data( $form_id );
		$row                 = FORMFILL_PDF_model::get_frontend_data( $form_id );
		$send_to             = explode( ',', $pdf->send_to );

		if ( $pdf->enable_pdf ) {
			$pdf_file             = '';
			$label_order_original = array();
			$label_type           = array();
			$label_all            = explode( '#****#', $row->label_order_current );
			$label_all            = array_slice( $label_all, 0, count( $label_all ) - 1 );
			foreach ( $label_all as $key => $label_each ) {
				$label_id_each                     = explode( '#**id**#', $label_each );
				$label_id                          = $label_id_each[0];
				$label_order_each                  = explode( '#**label**#', $label_id_each[1] );
				$label_order_original[ $label_id ] = $label_order_each[0];
				$label_type[ $label_id ]           = $label_order_each[1];
			}
			$pdf_content = $pdf->pdf_content;
			$values = array();
			foreach ( $label_order_original as $key => $label_each ) {
				$type = $label_type[ $key ];
				if ( strpos( $pdf_content, "%" . $label_each . "%" ) > - 1 ) {
					$new_value   = FMModelForm_maker::custom_fields_mail( $type, $key, $form_id, '', $form_currency );
					$pdf_content = str_replace( "%" . $label_each . "%", str_replace( array(
						"\r\n",
						"\r",
						"\n",
					), "<br>", $new_value ), $pdf_content );
					$values[$label_each] = $new_value;
				}
			}
			$custom_fields = array( 'ip', 'useremail', 'username', 'subid', 'all' );
			foreach ( $custom_fields as $key => $custom_field ) {
				if ( strpos( $pdf_content, "%" . $custom_field . "%" ) > - 1 ) {
					$pdf_content = str_replace( "%" . $custom_field . "%", $custom_fields_value[ $key ], $pdf_content );
				}
			}
			$ptn        = "/\s+/";
			$rpltxt     = "-";
			$form_title = preg_replace( $ptn, $rpltxt, $row->title );
			$pdf_path   = $pdf->pdf_path . '/' . $form_title . '-' . bin2hex(random_bytes(4)) . '-' . date( 'Y-m-d-H-i-s' ) . '.pdf';

			$values_origin = $values;
			do_action("ff-pdf__fields-value", $values, $form_id, $form_title);
            if (!$GLOBALS['form_justregister']) {
                if ( $pdf->pdf_template ) {
                    $lines  = explode( "\n", $pdf_content );
                    $values = array();
                    foreach ( $lines as $line ) {
                        $pair   = explode( "=", $line );
                        $vkey   = trim( $pair[0], "{}" );
                        $vvalue = strip_tags( trim( $pair[1] ) );
                        if ( $vvalue === '1' || $vvalue === 'true' || $vvalue === 'On' ) {
                            $value = 'On';
                        } elseif ( $vvalue === '0' || $vvalue === 'false' || $vvalue === 'Off' ) {
                            $value = 'Off';
                        } else {
                            $value = $vvalue;
                        }
                        $values[ $vkey ] = $value;
                    }

                    require_once FORMFILL_PDF_DIR . '/PdfForm.php';
                    $ff_pdf = new PdfForm( FORMFILL_PDF_DIR . "/templates/" . $pdf->pdf_template, $values );
                    $ff_pdf->save( ABSPATH . $pdf_path );
                } elseif ( class_exists( 'MYPDF' ) ) {

                    // create new PDF document
                    $pdf = new MYPDF( $pdf->pdf_background, PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false );

                    // set document information
                    // $pdf->SetCreator(PDF_CREATOR);
                    // $pdf->SetAuthor('Form Maker');
                    // $pdf->SetTitle('Form Maker');
                    // $pdf->SetSubject('Form Maker');

                    // set default header and footer data
                    $pdf->SetHeaderData( false, false, false, false, false, array( 255, 255, 255 ) );
                    $pdf->setFooterData( array( 255, 255, 255 ), array( 255, 255, 255 ) );

                    // set header and footer fonts
                    $pdf->setHeaderFont( Array( PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN ) );
                    $pdf->setFooterFont( Array( PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA ) );

                    // set default monospaced font
                    $pdf->SetDefaultMonospacedFont( PDF_FONT_MONOSPACED );

                    // set margins
                    $pdf->SetMargins( PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT );
                    // $pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
                    // $pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

                    // set auto page breaks
                    $pdf->SetAutoPageBreak( true, PDF_MARGIN_BOTTOM );

                    // set image scale factor
                    $pdf->setImageScale( PDF_IMAGE_SCALE_RATIO );

                    // set default font subsetting mode
                    $pdf->setFontSubsetting( true );

                    // Set font
                    // dejavusans is a UTF-8 Unicode font, if you only need to
                    // print standard ASCII chars, you can use core fonts like
                    // helvetica or times to reduce file size.
                    $pdf->SetFont( 'dejavusans', '', 12, '', true );

                    // Add a page
                    // This method has several options, check the source code documentation for more information.
                    $pdf->AddPage();

                    // set text shadow effect
                    $pdf->setTextShadow( array(
                        'enabled'    => true,
                        'depth_w'    => 0.2,
                        'depth_h'    => 0.2,
                        'color'      => array( 196, 196, 196 ),
                        'opacity'    => 1,
                        'blend_mode' => 'Normal'
                    ) );

                    // output the HTML content
                    // $pdf->writeHTML($html, true, false, true, false, '');
                    $pdf->writeHTMLCell( 0, 0, '', '', $pdf_content, 0, 1, 0, true, '', true );

                    // Close and output PDF document
                    // This method has several options, check the source code documentation for more information.
                    $pdf->Output( ABSPATH . $pdf_path, 'F' );
                }
            }

			do_action('ff-pdf__pdf-link', $values_origin, $pdf_path, $form_id, $form_title);

			$group_id   = (int) $custom_fields_value[3];
			$save_or_no = $wpdb->insert( $wpdb->prefix . "formfill_pdf", array(
				'form_id'  => $form_id,
				'group_id' => $group_id,
				'file_url' => stripslashes( $pdf_path ),
			), array(
				'%d',
				'%d',
				'%s',
			) );
			if ( ! $save_or_no ) {
				return false;
			}
			if ( $pdf_path && !$GLOBALS['form_justregister']) {
				if ( $send_to[0] ) {
					$GLOBALS['attachment'] = $pdf_path;
				}
				if ( $send_to[1] ) {
					$GLOBALS['attachment_user'] = $pdf_path;
				}
			}
		}

		return true;
	}
}
