<?php

declare(strict_types=1);

/*
 * This file is part of the "Unique Text Input Field for Symphony CMS" repository.
 *
 * Copyright 2020 Alannah Kearney <hi@alannahkearney.com>
 *
 * For the full copyright and license information, please view the LICENCE
 * file that was distributed with this source code.
 */

if (!file_exists(__DIR__.'/vendor/autoload.php')) {
    throw new Exception(sprintf('Could not find composer autoload file %s. Did you run `composer update` in %s?', __DIR__.'/vendor/autoload.php', __DIR__));
}

require_once __DIR__.'/vendor/autoload.php';

use pointybeard\Symphony\Extended;

// Check if the class already exists before declaring it again.
if (!class_exists('\\extension_Field_UniqueTextInput')) {
    final class extension_Field_UniqueTextInput extends Extended\AbstractExtension
    {
        public function uninstall()
        {
            parent::uninstall();

            return \Symphony::Database()->query('DROP TABLE IF EXISTS `tbl_fields_uniquetextinput`');
        }

        public function install()
        {
            parent::install();

            return \Symphony::Database()->query("
                CREATE TABLE IF NOT EXISTS `tbl_fields_uniquetextinput` (
                  `id` int(11) unsigned NOT NULL auto_increment,
                  `field_id` int(11) unsigned NOT NULL,
                  `validator` varchar(100) default NULL,
                  `auto_unique` enum('yes','no') NOT NULL default 'no',
                  PRIMARY KEY  (`id`),
                  KEY `field_id` (`field_id`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;"
            );
        }
    }
}
