<?php
/**
 *
 *          ..::..
 *     ..::::::::::::..
 *   ::'''''':''::'''''::
 *   ::..  ..:  :  ....::
 *   ::::  :::  :  :   ::
 *   ::::  :::  :  ''' ::
 *   ::::..:::..::.....::
 *     ''::::::::::::''
 *          ''::''
 *
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Creative Commons License.
 * It is available through the world-wide-web at this URL:
 * http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to servicedesk@tig.nl so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this module to newer
 * versions in the future. If you wish to customize this module for your
 * needs please contact servicedesk@tig.nl for more information.
 *
 * @copyright   Copyright (c) Total Internet Group B.V. https://tig.nl/copyright
 * @license     http://creativecommons.org/licenses/by-nc-nd/3.0/nl/deed.en_US
 */

namespace TIG\GLS\Setup;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\Declaration\Schema\Dto\Factories\MediumBlob;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use TIG\GLS\Model\Shipment\Label;

// @codingStandardsIgnoreFile
class InstallSchema implements InstallSchemaInterface
{
    const DEFAULT_BLOB_LENGTH                = 16777216;
    const GLS_DELIVERY_OPTION                = 'gls_delivery_option';
    const GLS_DELIVERY_OPTION_LABEL          = 'GLS Delivery Option';
    const GLS_DELIVERY_OPTION_COLUMN         = [
        // @codingStandardsIgnoreLine
        'type'     => Table::TYPE_TEXT,
        'nullable' => true,
        'default'  => null,
        'comment'  => self::GLS_DELIVERY_OPTION_LABEL,
        'after'    => 'shipping_method'
    ];
    const GLS_DELIVERY_OPTION_INSTALL_TABLES = [
        'quote_address',
        'sales_order'
    ];
    const GLS_TABLE_SHIPMENT_LABEL           = 'gls_shipment_label';

    /**
     * @param SchemaSetupInterface   $setup
     * @param ModuleContextInterface $context
     *
     * @throws \Zend_Db_Exception
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        $connection = $installer->getConnection();

        foreach (self::GLS_DELIVERY_OPTION_INSTALL_TABLES as $table) {
            $this->addColumn($connection, $installer, $table);
        }

        $labelTable = $setup->getTable(self::GLS_TABLE_SHIPMENT_LABEL);
        if ($connection->isTableExists($labelTable) != true) {
            $this->createTable($connection, $installer);
        }

        $installer->endSetup();
    }

    /**
     * @param AdapterInterface     $connection
     * @param SchemaSetupInterface $installer
     * @param                      $table
     */
    private function addColumn(AdapterInterface $connection, SchemaSetupInterface $installer, $table)
    {
        $connection->addColumn(
            $installer->getTable($table),
            self::GLS_DELIVERY_OPTION,
            self::GLS_DELIVERY_OPTION_COLUMN
        );
    }

    /**
     * @param AdapterInterface     $connection
     * @param SchemaSetupInterface $installer
     *
     * @throws \Zend_Db_Exception
     */
    private function createTable(AdapterInterface $connection, SchemaSetupInterface $installer)
    {
        $table = $connection->newTable(self::GLS_TABLE_SHIPMENT_LABEL);

        $this->addInteger($table, 'entity_id', 10, true, true, 'GLS Entity ID');
        $this->addInteger($table, Label::GLS_SHIPMENT_LABEL_SHIPMENT_ID, 10, false, false, 'Magento Shipment ID');
        $this->addForeignKey($installer, $table, self::GLS_TABLE_SHIPMENT_LABEL, Label::GLS_SHIPMENT_LABEL_SHIPMENT_ID, 'sales_shipment', 'entity_id', Table::ACTION_CASCADE);
        $this->addText($table, Label::GLS_SHIPMENT_LABEL_UNIT_ID, 50, 'Unit ID');
        $this->addText($table, Label::GLS_SHIPMENT_LABEL_UNIT_NO, 50, 'Unit Number');
        $this->addText($table, Label::GLS_SHIPMENT_LABEL_UNIT_NO_SHOP_RETURN, 50, 'Shop Return Unit Number');
        $this->addText($table, Label::GLS_SHIPMENT_LABEL_UNIQUE_NO, 50, 'Unique Number');
        $this->addBool($table, Label::GLS_SHIPMENT_LABEL_CONFIRMED, 'Is Confirmed?');
        $this->addBlob($table, Label::GLS_SHIPMENT_LABEL_LABEL, self::DEFAULT_BLOB_LENGTH, 'GLS Label (Base64 encoded)');
        $this->addText($table, Label::GLS_SHIPMENT_LABEL_UNIT_TRACKING_LINK, 256, 'GLS Tracking Link');

        $connection->createTable($table);
    }

    /**
     * @param SchemaSetupInterface $installer
     * @param Table                $table
     * @param                      $primaryTable
     * @param                      $primaryColumn
     * @param                      $referenceTable
     * @param                      $referenceColumn
     * @param                      $onDeleteAction
     *
     * @throws \Zend_Db_Exception
     */
    private function addForeignKey(SchemaSetupInterface $installer, Table $table, $primaryTable, $primaryColumn, $referenceTable, $referenceColumn, $onDeleteAction)
    {
        $foreignKey = $installer->getFkName(
            $primaryTable,
            $primaryColumn,
            $referenceTable,
            $referenceColumn
        );

        $table->addForeignKey(
            $foreignKey,
            $primaryColumn,
            $referenceTable,
            $referenceColumn,
            $onDeleteAction
        );
    }

    /**
     * @param Table $table
     * @param       $name
     * @param       $size
     * @param bool  $primary
     * @param bool  $autoIncrement
     * @param bool  $comment
     *
     * @throws \Zend_Db_Exception
     */
    private function addInteger(Table $table, $name, $size, $primary = false, $autoIncrement = false, $comment = false)
    {
        $table->addColumn(
            $name,
            Table::TYPE_INTEGER,
            $size,
            [
                'primary' => $primary,
                'auto_increment' => $autoIncrement,
                'nullable' => false,
                'unsigned' => true
            ],
            $comment
        );
    }

    /**
     * @param Table $table
     * @param       $name
     * @param       $size
     * @param bool  $comment
     *
     * @throws \Zend_Db_Exception
     */
    private function addText(Table $table, $name, $size, $comment = false)
    {
        $table->addColumn(
            $name,
            Table::TYPE_TEXT,
            $size,
            [
                'nullable' => true,
                'default'  => null
            ],
            $comment
        );
    }

    /**
     * @param Table $table
     * @param       $name
     * @param bool  $comment
     *
     * @throws \Zend_Db_Exception
     */
    private function addBool(Table $table, $name, $comment = false)
    {
        $table->addColumn(
            $name,
            Table::TYPE_BOOLEAN,
            null,
            [
                'nullable' => false,
                'default'  => false,
                'unsigned' => true
            ],
            $comment
        );
    }

    /**
     * @param Table $table
     * @param       $name
     * @param null  $size    Increase this if you want to make larger blobs.
     * @param bool  $comment
     *
     * @throws \Zend_Db_Exception
     */
    private function addBlob(Table $table, $name,  $size = null, $comment = false)
    {
        $table->addColumn(
            $name,
            Table::TYPE_BLOB,
            $size ?: null,
            [
                'identity' => false,
                'unsigned' => false,
                'nullable' => true,
                'primary'  => false,
                'default'  => null
            ],
            $comment
        );
    }
}
