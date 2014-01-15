<?php

namespace Fias;

use Grace\DBAL\ConnectionAbstract\ConnectionInterface;

class XmlImporter
{
    /** @var ConnectionInterface */
    private $db;
    private $fields = array();
    private $table;

    public function __construct(ConnectionInterface $db, $table, array $fields)
    {
        if (!$table) {
            throw new ImporterException('Не задана таблица для импорта');
        }

        if (!$fields) {
            throw new ImporterException('Не заданы поля для импорта.');
        }

        $this->db     = $db;
        $this->fields = $fields;
        $this->table  = $table . '_xml_importer';

        DbHelper::createTable($this->db, $this->table, $this->fields);
    }

    public function import(XMLReader $reader)
    {
        while ($rows = $reader->getRows()) {
            $this->db->execute($this->getQuery($rows[0]), array($rows));
        }

        return $this->table;
    }

    private $sqlHeader;

    private function getQuery($rowExample)
    {
        if (!$this->sqlHeader) {
            $fields = array();
            foreach ($rowExample as $attribute => $devNull) {
                $fields[] = $this->fields[$attribute]['name'];
            }

            $headerPart = $this->db->replacePlaceholders('INSERT INTO ?f(?i) VALUES ', array($this->table, $fields));

            $this->sqlHeader = $headerPart . ' ?v';
        }

        return $this->sqlHeader;
    }
}
