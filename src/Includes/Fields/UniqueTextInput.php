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

class fieldUniqueTextInput extends fieldInput
{
    public function __construct()
    {
        parent::__construct();
        $this->_name = __('Unique Text Input');
        $this->_required = true;

        $this->set('required', 'yes');
    }

    /*-------------------------------------------------------------------------
        Setup:
    -------------------------------------------------------------------------*/

    public function allowDatasourceOutputGrouping()
    {
        return false;
    }

    public function createTable()
    {
        return SymphonyPDO\Loader::instance()->query(sprintf(
            'CREATE TABLE IF NOT EXISTS `tbl_entries_data_%d` (
              `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `entry_id` int(11) unsigned NOT NULL,
              `handle` varchar(1024) DEFAULT NULL,
              `value` text DEFAULT NULL,
              `value_formatted` text DEFAULT NULL,
              `word_count` int(11) unsigned DEFAULT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `entry_id` (`entry_id`),
              KEY `handle` (`handle`(333)),
              FULLTEXT KEY `value` (`value`),
              FULLTEXT KEY `value_formatted` (`value_formatted`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;',
            (int) $this->get('id')
        ));
    }

    /*-------------------------------------------------------------------------
        Utilities:
    -------------------------------------------------------------------------*/

    public function isHandleUnique(string $handle, int $entryIdSelf = null): bool
    {
        $handle = Symphony::Database()->cleanValue($handle);

        $query = SymphonyPDO\Loader::instance()->prepare(sprintf(
            'SELECT `id` 
                FROM `tbl_entries_data_%d` 
                WHERE `handle` = :handle 
                '
                .(
                    null !== $entryIdSelf
                        ? ' AND `entry_id` != :entryId '
                        : ''
                )
                .'LIMIT 1',
            (int) $this->get('id')
        ));

        $query->bindParam(':handle', $handle, \PDO::PARAM_STR);

        if (null !== $entryIdSelf) {
            $query->bindParam(':entryId', $entryIdSelf, \PDO::PARAM_INT);
        }

        $query->execute();
        $result = $query->fetch();

        return false === $result || null === $result ? true : false;
    }

    /*-------------------------------------------------------------------------
        Settings:
    -------------------------------------------------------------------------*/

    public function displaySettingsPanel(XMLElement &$wrapper, $errors = null)
    {
        parent::displaySettingsPanel($wrapper, $errors);

        $div = new XMLElement('fieldset');

        $label = Widget::Label();
        $input = Widget::Input('fields['.$this->get('sortorder').'][auto_unique]', 'yes', 'checkbox');
        if ('yes' == $this->get('auto_unique')) {
            $input->setAttribute('checked', 'checked');
        }
        $label->setValue($input->generate().' '.__('Create unique handles automatically'));
        $label->appendChild(new XMLElement('p', __('When a handle clash is detected, rather than throw an error, a number is appended to form a unique value.'), ['class' => 'help']));
        $div->appendChild($label);

        $wrapper->appendChild($div);
    }

    public function commit()
    {
        if (false == parent::commit()) {
            return false;
        }

        $id = $this->get('id');

        if (false === $id) {
            return false;
        }

        $fields = [];

        $fields['field_id'] = $id;
        $fields['validator'] = ('custom' == $fields['validator'] ? null : $this->get('validator'));
        $fields['auto_unique'] = ($this->get('auto_unique') ? $this->get('auto_unique') : 'no');

        return FieldManager::saveSettings($id, $fields);
    }

    /*-------------------------------------------------------------------------
        Input:
    -------------------------------------------------------------------------*/

    public function checkPostFieldData($data, &$message = null, $entry_id = null)
    {
        $status = parent::checkPostFieldData($data, $message, $entry_id);

        if (self::__OK__ !== $status) {
            return $status;
        }

        $handle = Lang::createHandle($data);

        if ('yes' != $this->get('auto_unique') && !$this->isHandleUnique($handle, $entry_id)) {
            $message = __('Value must be unique.');

            return self::__INVALID_FIELDS__;
        }

        if (false == General::validateXML(General::sanitize($data), $errors, false, new XsltProcess())) {
            $message = __('‘%s’ contains invalid XML. The following error was returned: %s', [
                $this->get('label'),
                '<code>'.$errors[0]['message'].'</code>',
            ]);

            return self::__INVALID_FIELDS__;
        }

        return self::__OK__;
    }

    public function processRawFieldData($data, &$status, &$message = null, $simulate = false, $entry_id = null)
    {
        if ('' == trim($data)) {
            return [];
        }

        $status = self::__OK__;
        $handle = Lang::createHandle($data);

        if ('yes' == $this->get('auto_unique') && !$this->isHandleUnique($handle, $entry_id)) {
            $existing = null;
            $safeData = Symphony::Database()->cleanValue($data);

            // First, check to see if the handle even needs to change
            if (null !== $entry_id) {
                $existing = Symphony::Database()->fetchRow(0, '
                    SELECT `id`, `value`, `handle`
                    FROM `tbl_entries_data_'.$this->get('id')."`
                    WHERE `value` = '{$safeData}'
                    AND `entry_id` = {$entry_id}
                    LIMIT 1
                ");
            }

            // Either this is a new entry, or the value has changed
            // enough to generate a new handle
            if (null === $existing || null === $existing['handle']) {
                $count = 2;

                while ((bool) Symphony::Database()->fetchVar('id', 0, '
                        SELECT `id`
                        FROM `tbl_entries_data_'.$this->get('id')."`
                        WHERE `handle` = '$handle-$count'
                        ".(null !== $entry_id ? " AND `entry_id` != {$entry_id} " : null).'
                        LIMIT 1 ')) {
                    ++$count;
                }

                $handle = "$handle-$count";
            }

            // Use the existing handle, since nothing has changed
            else {
                $handle = $existing['handle'];
            }
        }

        $result = [
            'handle' => $handle,
            'value' => $data,
        ];

        return $result;
    }
}
