<?php

	if(!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');

	require_once(TOOLKIT . '/class.xsltprocess.php');
	require_once(TOOLKIT . '/fields/field.input.php');

	Class fieldUniqueInput extends fieldInput {

		public function __construct(){
			parent::__construct();
			$this->_name = __('Unique Text Input');
			$this->_required = true;

			$this->set('required', 'yes');
		}

	/*-------------------------------------------------------------------------
		Setup:
	-------------------------------------------------------------------------*/

		public function allowDatasourceOutputGrouping(){
			return false;
		}

	/*-------------------------------------------------------------------------
		Utilities:
	-------------------------------------------------------------------------*/

		public function isHandleUnique($handle, $self_entry_id=NULL){
			return !(bool)Symphony::Database()->fetchVar('id', 0, "SELECT `id` FROM `tbl_entries_data_" . $this->get('id') . "` WHERE `handle` = '$handle' ".(is_numeric($self_entry_id) ? " AND `entry_id` != $self_entry_id " : NULL) . "LIMIT 1");
		}

	/*-------------------------------------------------------------------------
		Settings:
	-------------------------------------------------------------------------*/

		public function displaySettingsPanel(XMLElement &$wrapper, $errors = null) {
			parent::displaySettingsPanel($wrapper, $errors);

			$div = new XMLElement('div');
			$div->setAttribute('class', 'frame');

			$label = Widget::Label();
			$input = Widget::Input('fields['.$this->get('sortorder').'][auto_unique]', 'yes', 'checkbox');
			if($this->get('auto_unique') == 'yes') $input->setAttribute('checked', 'checked');
			$label->setValue($input->generate() . ' ' . __('Create unique handles automatically'));
			$label->appendChild(new XMLElement('p', __('When a handle clash is detected, rather than throw an error, a number is appended to form a unique value.'), array('class' => 'help')));
			$div->appendChild($label);

			$wrapper->appendChild($div);
		}

		public function commit(){
			if(!parent::commit()) return false;

			$id = $this->get('id');

			if($id === false) return false;

			$fields = array();

			$fields['field_id'] = $id;
			$fields['validator'] = ($fields['validator'] == 'custom' ? NULL : $this->get('validator'));
			$fields['auto_unique'] = ($this->get('auto_unique') ? $this->get('auto_unique') : 'no');

			return FieldManager::saveSettings($id, $fields);
		}

	/*-------------------------------------------------------------------------
		Input:
	-------------------------------------------------------------------------*/

		public function checkPostFieldData($data, &$message = null, $entry_id = null){
			$status = parent::checkPostFieldData($data, $message, $entry_id);

			if($status !== self::__OK__) {
				return $status;
			}

			$handle = Lang::createHandle($data);

			if($this->get('auto_unique') != 'yes' && !$this->isHandleUnique($handle, $entry_id)){
				$message = __('Value must be unique.');
				return self::__INVALID_FIELDS__;
			}

			if(!General::validateXML(General::sanitize($data), $errors, false, new XsltProcess)){
				$message = __('‘%s’ contains invalid XML. The following error was returned: %s', array(
					$this->get('label'),
					'<code>' . $errors[0]['message'] . '</code>'
				));
				return self::__INVALID_FIELDS__;
			}

			return self::__OK__;
		}

		public function processRawFieldData($data, &$status, &$message=null, $simulate=false, $entry_id=NULL){
			if(trim($data) == '') return array();

			$status = self::__OK__;
			$handle = Lang::createHandle($data);

			if($this->get('auto_unique') == 'yes' && !$this->isHandleUnique($handle, $entry_id)){
				$existing = NULL;

				// First, check to see if the handle even needs to change
				if(!is_null($entry_id)){
					$existing = Symphony::Database()->fetchRow(0, "
						SELECT `id`, `value`, `handle`
						FROM `tbl_entries_data_" . $this->get('id') . "`
						WHERE `value` = '".General::sanitize($data)."'
						AND `entry_id` = {$entry_id}
						LIMIT 1
					");
				}

				// Either this is a new entry, or the value has changed
				// enough to generate a new handle
				if(is_null($existing) || is_null($existing['handle'])){
					$count = 2;

					while((bool)Symphony::Database()->fetchVar('id', 0, "
							SELECT `id`
							FROM `tbl_entries_data_" . $this->get('id') . "`
							WHERE `handle` = '$handle-$count'
							".(!is_null($entry_id) ? " AND `entry_id` != {$entry_id} " : NULL)."
							LIMIT 1 ")){

						$count++;
					}

					$handle = "$handle-$count";
				}

				// Use the existing handle, since nothing has changed
				else $handle = $existing['handle'];
			}

			$result = array(
				'handle' => $handle,
				'value' => $data,
			);

			return $result;
		}
	}

