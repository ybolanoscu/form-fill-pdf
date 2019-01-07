<?php

class FORMFILL_PDF_view {
	/**
	 * Display page.
	 *
	 * @param array $params
	 */
	public function display( $params ) {
		$pdf_data    = $params['data'];
		$form_id     = $params['form_id'];
		$pdf_entries = FORMFILL_PDF_model::get_pdf_data( $form_id );
		$form_data   = FORMFILL_PDF_model::get_form_data( $form_id );
		?>
        <div id="WD_FM_PDF_INT_fieldset" class="adminform fm_fieldset_deactive">
            <div class="wd-table">
                <div class="wd-table-col-70">
                    <div class="wd-box-section">
                        <div class="wd-box-content">
                            <div class="wd-group">
                                <label class="wd-label"><?php _e( 'Enable', WDFM()->prefix ); ?></label>
                                <input type="radio" name="enable_pdf" id="enable_pdf1"
                                       value="1" <?php echo ( $pdf_data->enable_pdf ) ? "checked" : ""; ?> /><label
                                        for="enable_pdf1"><?php _e( 'Yes', WDFM()->prefix ); ?></label>
                                <input type="radio" name="enable_pdf" id="enable_pdf2"
                                       value="0" <?php echo ( ! $pdf_data->enable_pdf ) ? "checked" : ""; ?> /><label
                                        for="enable_pdf2"><?php _e( 'No', WDFM()->prefix ); ?></label>
                                <p class="description"><?php _e( 'Send submitted forms in PDF format.', WDFM()->prefix ); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div id="pdf_int_fieldset_options"
                 class="adminform show_tr  <?php echo $pdf_data->enable_pdf == 0 ? 'hide' : '' ?>">
                <div class="wd-table">
                    <div class="wd-table-col-70">
                        <div class="wd-box-section">
                            <div class="wd-box-content">
                                <div class="wd-group">
                                    <label class="wd-label"><?php _e( 'Send email to', WDFM()->prefix ); ?></label>
									<?php $send_to = explode( ',', $pdf_data->send_to ); ?>
                                    <input type="checkbox" name="pdf_send_to_admin" id="pdf_send_to_admin"
                                           value="admin" <?php echo( $send_to[0] == 1 ? 'checked="checked"' : '' ); ?> /><label
                                            for="pdf_send_to_admin"><?php _e( 'Administrator', WDFM()->prefix ); ?></label>
                                    </br>
                                    <input type="checkbox" name="pdf_send_to_user" id="pdf_send_to_user"
                                           value="user" <?php echo( $send_to[1] == 1 ? 'checked="checked"' : '' ); ?> /><label
                                            for="pdf_send_to_user"><?php _e( 'User', WDFM()->prefix ); ?></label>

                                </div>
                                <div class="wd-group">
                                    <label class="wd-label"><?php _e( 'Folder path', WDFM()->prefix ); ?></label>
                                    <input type="text" name="pdf_path" id="pdf_path" size="50"
                                           value="<?php echo( $pdf_data->pdf_path ? $pdf_data->pdf_path : 'wp-content/uploads/formfill/pdf' ); ?>"/>
                                </div>
                                <div class="wd-group">
                                    <label class="wd-label"><?php _e( 'Template PDF', WDFM()->prefix ); ?></label>
									<?php $choise = "'pdf_content'"; ?>
                                    <select name="pdf_template" id="pdf_template">
                                        <option value="">Choose a template</option>
										<?php
										$templates = apply_filters( 'ff_get_templates', array() );
									    $i = 1;
										foreach ( $templates as $template ) : ?>
                                            <?php if ($pdf_data->pdf_template== $template['value']) $select = 'selected'; else $select = ''; ?>
                                            <option data-index="<?php echo $i; ?>" value="<?php echo $template['value']; ?>" <?php echo $select; ?>><?php echo $template['label']; ?></option>
											<?php $i ++; ?>
										<?php endforeach; ?>
                                    </select>
									<?php $i = 1; ?>
									<?php foreach ( $templates as $template ) : ?>
                                        <div class="wd-group fields-templates" id="template_option_<?php echo $i; ?>"
                                             style="display: none;">
											<?php foreach ( $template['fields'] as $field ) : ?>
                                                <input class="button" type="button" value="<?php echo $field; ?>"
                                                       onClick="insertAtCursorFill( <?php echo $choise; ?>,'<?php echo $field; ?>' )"/>
											<?php endforeach; ?>
                                        </div>
										<?php $i ++; ?>
									<?php endforeach; ?>
                                </div>
                                <div class="wd-group">
                                    <label class="wd-label"><?php _e( 'PDF content', WDFM()->prefix ); ?></label>
									<?php $choise = "'pdf_content'"; ?>
                                    <input class="button" type="button" value="All fields list"
                                           onClick="insertAtCursor( <?php echo $choise; ?>, 'all' )"/>
                                    <input class="button" type="button" value="Submission ID"
                                           onClick="insertAtCursor( <?php echo $choise; ?>,'subid' )"/>
                                    <input class="button" type="button" value="Ip"
                                           onClick="insertAtCursor( <?php echo $choise; ?>,'ip' )"/>
                                    <input class="button" type="button" value="Username"
                                           onClick="insertAtCursor( <?php echo $choise; ?>,'username' )"/>
                                    <input class="button" type="button" value="User Email"
                                           onClick="insertAtCursor( <?php echo $choise; ?>,'useremail' )"/>
									<?php
									$label_label = $form_data['label_label'];
									$label_type  = $form_data['label_type'];
									for ( $i = 0; $i < count( $label_label ); $i ++ ) {
										if ( $label_type[ $i ] == "type_submit_reset" || $label_type[ $i ] == "type_editor" || $label_type[ $i ] == "type_map" || $label_type[ $i ] == "type_mark_map" || $label_type[ $i ] == "type_captcha" || $label_type[ $i ] == "type_recaptcha" || $label_type[ $i ] == "type_button" || $label_type[ $i ] == "type_file_upload" || $label_type[ $i ] == "type_send_copy" ) {
											continue;
										}

										$param     = htmlspecialchars( addslashes( $label_label[ $i ] ) );
										$fld_label = $param;
										if ( strlen( $fld_label ) > 30 ) {
											$fld_label = wordwrap( htmlspecialchars( addslashes( $label_label[ $i ] ) ), 30 );
											$fld_label = explode( "\n", $fld_label );
											$fld_label = $fld_label[0] . ' ...';
										}
										if ( $label_type[ $i ] == "type_file_upload" ) {
											$fld_label .= '(as image)';
										}
										?>
                                        <input class="button" type="button" value="<?php echo $fld_label; ?>"
                                               onClick="insertAtCursor( <?php echo $choise; ?>, '<?php echo $param; ?>' )"/>
										<?php
									}
									$pdf_data->pdf_content = $pdf_data->pdf_content ? $pdf_data->pdf_content : '%all%';
									if ( user_can_richedit() ) {
										wp_editor( $pdf_data->pdf_content, 'pdf_content', array(
											'teeny'         => true,
											'textarea_name' => 'pdf_content',
											'media_buttons' => false,
											'textarea_rows' => 5
										) );
									} else {
										?>
                                        <textarea name="pdf_content" id="pdf_content" cols="20"
                                                  rows="10"><?php echo $pdf_data->pdf_content; ?></textarea>
										<?php
									}
									?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script>
            function insertAtCursorFill(myField, myValue) {
                if (tinyMCE.get(myField)) {
                    tinyMCE.get(myField).focus();
                }
                var myField = document.getElementById(myField);
                if (myField.style.display == "none") {
                    tinyMCE.execCommand('mceInsertContent', false, "{{" + myValue + "}}=");
                    return;
                }
                if (document.selection) {
                    myField.focus();
                    sel = document.selection.createRange();
                    sel.text = myValue;
                }
                else if (myField.selectionStart || myField.selectionStart == '0') {
                    var startPos = myField.selectionStart;
                    var endPos = myField.selectionEnd;
                    myField.value = myField.value.substring(0, startPos)
                        + "{{" + myValue + "}}="
                        + myField.value.substring(endPos, myField.value.length);
                }
                else {
                    myField.value += "{{" + myValue + "}}=";
                }
            }

            jQuery(document).ready(function () {
                var templ = jQuery('#pdf_template');
                templ.on('change', function (e) {
                    e.preventDefault();
                    jQuery('.fields-templates').hide();
                    var option = templ.find(':selected');
                    jQuery('#template_option_' + option.get(0).dataset.index).show();
                });
                templ.trigger('change');
            })
        </script>
		<?php
	}
}
