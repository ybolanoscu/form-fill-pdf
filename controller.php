<?php

class FORMFILL_PDF_controller {
	private $model;
	private $view;

	function __construct() {
		require_once 'model.php';
		require_once 'view.php';
		$this->model = new FORMFILL_PDF_model();
		$this->view  = new FORMFILL_PDF_view();
	}

	/**
	 * Display.
	 *
	 * @param array $params
	 */
	public function display( $params ) {
		wp_enqueue_script( 'ff-pdf_int' );
		wp_enqueue_style( 'ff-pdf_int' );
		$form_id = $params['form_id'];
		$data    = $this->model->get_data( $form_id );
		// Set params for view.
		$params['data'] = $data;
		$this->view->display( $params );
	}

	/**
	 * Save.
	 *
	 * @param int $id
	 *
	 * @return void
	 */
	function save( $id ) {
		$this->model->save( $id );
	}

	/**
	 * Delete.
	 *
	 * @param int $id
	 *
	 * @return void
	 */
	function delete( $id ) {
		$this->model->delete( $id );
	}

	/**
	 * Frontend.
	 *
	 * @param array $params
	 */
	function frontend( $params ) {
		$this->model->frontend( $params );
	}
}
