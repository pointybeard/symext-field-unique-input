<?php

	Class extension_uniqueinputfield extends Extension{
	
		public function about(){
			return array('name' => 'Field: Unique Text Input',
						 'version' => '1.3',
						 'release-date' => '2008-12-12',
						 'author' => array('name' => 'Symphony Team',
										   'website' => 'http://www.symphony21.com',
										   'email' => 'team@symphony21.com')
				 		);
		}
		
		public function uninstall(){
			$this->_Parent->Database->query("DROP TABLE `tbl_fields_uniqueinput`");
		}


		public function install(){

			return (bool)$this->_Parent->Database->query("CREATE TABLE `tbl_fields_uniqueinput` (
				  `id` int(11) unsigned NOT NULL auto_increment,
				  `field_id` int(11) unsigned NOT NULL,
				  `validator` varchar(100) default NULL,
				  `auto_unique` enum('yes','no') NOT NULL default 'no',
				  PRIMARY KEY  (`id`),
				  KEY `field_id` (`field_id`))"
			);

		}
			
	}