<?php

	if(!defined('__IN_SYMPHONY__')) die('<h2>Symphony Error</h2><p>You cannot directly access this file</p>');

	require_once(TOOLKIT . '/class.xsltprocess.php');

	Class fieldUniqueInput extends Field{

		function __construct(){
			parent::__construct();
			$this->_name = 'Unique Text Input';
			$this->_required = true;

			$this->set('required', 'yes');
		}

		function allowDatasourceOutputGrouping(){
			return false;
		}

		function allowDatasourceParamOutput(){
			return true;
		}

		function isSortable(){
			return true;
		}

		function canFilter(){
			return true;
		}

		function displayPublishPanel(&$wrapper, $data=NULL, $flagWithError=NULL, $fieldnamePrefix=NULL, $fieldnamePostfix=NULL){
			$value = General::sanitize($data['value']);
			$label = Widget::Label($this->get('label'));
			if($this->get('required') != 'yes') $label->appendChild(new XMLElement('i', 'Optional'));
			$label->appendChild(Widget::Input('fields'.$fieldnamePrefix.'['.$this->get('element_name').']'.$fieldnamePostfix, (strlen($value) != 0 ? $value : NULL)));

			if($flagWithError != NULL) $wrapper->appendChild(Widget::wrapFormElementWithError($label, $flagWithError));
			else $wrapper->appendChild($label);
		}

		public function buildSortingSQL(&$joins, &$where, &$sort, $order='ASC', $useIDFieldForSorting=false){

			$sort_field = (!$useIDFieldForSorting ? 'ed' : 't' . $this->get('id'));

			$joins .= "INNER JOIN `tbl_entries_data_".$this->get('id')."` AS `$sort_field` ON (`e`.`id` = `$sort_field`.`entry_id`) ";
			$sort .= ' ORDER BY' . (strtolower($order) == 'random' ? 'RAND()' : "`$sort_field`.`value` $order");
		}

		function buildDSRetrivalSQL($data, &$joins, &$where, $andOperation=false){

			$field_id = $this->get('id');

			if(self::isFilterRegex($data[0])):

				$pattern = str_replace('regexp:', '', $data[0]);
				$joins .= " LEFT JOIN `tbl_entries_data_$field_id` AS `t$field_id` ON (`e`.`id` = `t$field_id`.entry_id) ";
				$where .= " AND (`t$field_id`.value REGEXP '$pattern' OR `t$field_id`.handle REGEXP '$pattern') ";


			elseif($andOperation):

				foreach($data as $key => $bit){
					$joins .= " LEFT JOIN `tbl_entries_data_$field_id` AS `t$field_id$key` ON (`e`.`id` = `t$field_id$key`.entry_id) ";
					$where .= " AND (`t$field_id$key`.value = '$bit' OR `t$field_id$key`.handle = '$bit') ";
				}

			else:

				$joins .= " LEFT JOIN `tbl_entries_data_$field_id` AS `t$field_id` ON (`e`.`id` = `t$field_id`.entry_id) ";
				$where .= " AND (`t$field_id`.value IN ('".@implode("', '", $data)."') OR `t$field_id`.handle IN ('".@implode("', '", $data)."')) ";

			endif;

			return true;

		}

		function __applyValidationRules($data){
			$rule = $this->get('validator');
			return ($rule ? General::validateString($data, $rule) : true);
		}

		function __isHandleUnique($handle, $self_entry_id=NULL){
			return !(bool)Symphony::Database()->fetchVar('id', 0, "SELECT `id` FROM `tbl_entries_data_" . $this->get('id') . "` WHERE `handle` = '$handle' ".(is_numeric($self_entry_id) ? " AND `entry_id` != $self_entry_id " : NULL) . "LIMIT 1");
		}

		function checkPostFieldData($data, &$message, $entry_id=NULL){

			$message = NULL;

			$handle = Lang::createHandle($data);

			if($this->get('required') == 'yes' && strlen($data) == 0){
				$message = "'". $this->get('label')."' is a required field.";
				return self::__MISSING_FIELDS__;
			}

			if(!$this->__applyValidationRules($data)){
				$message = "'". $this->get('label')."' contains invalid data. Please check the contents.";
				return self::__INVALID_FIELDS__;
			}

			if($this->get('auto_unique') != 'yes' && !$this->__isHandleUnique($handle, $entry_id)){
				$message = 'Value must be unique.';
				return self::__INVALID_FIELDS__;
			}

			if(!General::validateXML(General::sanitize($data), $errors, false, new XsltProcess)){
				$message = "'". $this->get('label')."' contains invalid XML. The following error was returned: <code>" . $errors[0]['message'] . '</code>';
				return self::__INVALID_FIELDS__;
			}

			return self::__OK__;

		}

		function processRawFieldData($data, &$status, &$message=null, $simulate=false, $entry_id=NULL){

			if(trim($data) == '') return array();

			$status = self::__OK__;

			$handle = Lang::createHandle($data);

			if($this->get('auto_unique') == 'yes' && !$this->__isHandleUnique($handle, $entry_id)){

				$existing = NULL;

				## First, check to see if the handle even needs to change
				if(!is_null($entry_id)){

					$existing = Symphony::Database()->fetchRow(0, "
						SELECT `id`, `value`, `handle`
						FROM `tbl_entries_data_" . $this->get('id') . "`
						WHERE `value` = '".General::sanitize($data)."'
						AND `entry_id` = {$entry_id}
						LIMIT 1
					");

				}

				## Either this is a new entry, or the value has changed
				## enough to generate a new handle
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

				## Use the existing handle, since nothing has changed
				else $handle = $existing['handle'];
			}

			$result = array(
				'handle' => $handle,
				'value' => $data,
			);

			return $result;
		}

		function canPrePopulate(){
			return true;
		}

		function appendFormattedElement(&$wrapper, $data, $encode=false){

			if($this->get('apply_formatting') == 'yes' && isset($data['value_formatted'])) $value = General::sanitize($data['value_formatted']);
			else $value = General::sanitize($data['value']);

			$wrapper->appendChild(new XMLElement($this->get('element_name'), ($encode ? General::sanitize($value) : $value), array('handle' => $data['handle'])));
		}

		function getEntryFormatter($entry_id){
			return Symphony::Database()->fetchVar('formatter', 0, "SELECT `formatter` FROM `tbl_entries` WHERE `id` = '$entry_id' LIMIT 1");
		}

		function commit(){

			if(!parent::commit()) return false;

			$id = $this->get('id');

			if($id === false) return false;

			$fields = array();

			$fields['field_id'] = $id;
			$fields['validator'] = ($fields['validator'] == 'custom' ? NULL : $this->get('validator'));
			$fields['auto_unique'] = ($this->get('auto_unique') ? $this->get('auto_unique') : 'no');

			Symphony::Database()->query("DELETE FROM `tbl_fields_".$this->handle()."` WHERE `field_id` = '$id' LIMIT 1");

			return Symphony::Database()->insert($fields, 'tbl_fields_' . $this->handle());

		}

		function setFromPOST($postdata){
			parent::setFromPOST($postdata);
			if($this->get('validator') == '') $this->remove('validator');
		}

		function displaySettingsPanel(&$wrapper, $errors=NULL){

			parent::displaySettingsPanel($wrapper, $errors);

			$this->buildValidationSelect($wrapper, $this->get('validator'), 'fields['.$this->get('sortorder').'][validator]');

			$div = new XMLElement('div', NULL, array('class' => 'group'));

			$label = Widget::Label();
			$input = Widget::Input('fields['.$this->get('sortorder').'][auto_unique]', 'yes', 'checkbox');
			if($this->get('auto_unique') == 'yes') $input->setAttribute('checked', 'checked');
			$label->setValue($input->generate() . ' Create unique handles automatically');
			$div->appendChild($label);

			$this->appendRequiredCheckbox($div);
			$wrapper->appendChild($div);

			$wrapper->appendChild(new XMLElement('p', 'When a handle clash is detected, rather than throw an error, a number is appended to form a unique value.', array('class' => 'help')));

			$this->appendShowColumnCheckbox($wrapper);

		}

		function createTable(){
			return Symphony::Database()->query(
				"CREATE TABLE IF NOT EXISTS `tbl_entries_data_" . $this->get('id') . "` (
				  `id` int(11) unsigned NOT NULL auto_increment,
				  `entry_id` int(11) unsigned NOT NULL,
				  `handle` varchar(255) default NULL,
				  `value` varchar(255) default NULL,
				  PRIMARY KEY  (`id`),
				  UNIQUE KEY `entry_id` (`entry_id`),
				  KEY `handle` (`handle`),
				  KEY `value` (`value`)
				) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci"
			);
		}

	}

