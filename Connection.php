<?php

namespace Ijanki\DBAL;

class Connection extends \Doctrine\DBAL\Connection
{
    /**
     * Insert using mysql ON DUPLICATE KEY UPDATE.
     * @link http://dev.mysql.com/doc/refman/5.7/en/insert-on-duplicate.html
     *
     * INSPIRED BY https://github.com/yadakhov/insert-on-duplicate-key
     *
     * Example:  $data = [
     *     ['id' => 1, 'name' => 'John'],
     *     ['id' => 2, 'name' => 'Mike'],
     * ];
     *
     * @param string $tableExpression The expression of the table to insert data into, quoted or unquoted.
     * @param array $data is an array of array.
     * @param array $updateColumns NULL or [] means update all columns
     *
     * @return int 0 if row is not changed, 1 if row is inserted, 2 if row is updated
     */
    public function insertOnDuplicateKey($tableExpression, array $data, array $updateColumns = null)
    {
        if (empty($data)) {
            return $this->executeUpdate('INSERT INTO ' . $tableExpression . ' ()' . ' VALUES ()');
        }

        // Case where $data is not an array of arrays.
        if (!isset($data[0])) {
            $data = [$data];
        }

        $sql = $this->buildInsertOnDuplicateSql($tableExpression, $data, $updateColumns);

        $stmt = $this->prepare($sql);

        $data = $this->inLineArray($data);

        return $stmt->execute($data);
    }

    /**
     * Inline a multiple dimensions array.
     *
     * @param $data
     *
     * @return array
     */
    protected static function inLineArray(array $data)
    {
        return call_user_func_array('array_merge', array_map('array_values', $data));
    }

    private function buildInsertOnDuplicateSql($tableExpression, array $data, array $updateColumns = null)
    {
        $first = $this->getFirstRow($data);

        $columns = '`' . implode('`,`', array_keys($first)) . '`';

        $sql  = 'INSERT INTO `' . $tableExpression . '`(' . $columns . ') VALUES ';
        $sql .=  $this->buildQuestionMarks($data);
        $sql .= ' ON DUPLICATE KEY UPDATE ';

        if (empty($updateColumns)) {
            $sql .= $this->buildValuesList(array_keys($first));
        } else {
            $sql .= $this->buildValuesList($updateColumns);
        }

        return $sql;
    }

    /**
     * Get the first row of the $data array.
     *
     * @param array $data
     *
     * @return mixed
     */
    protected function getFirstRow(array $data)
    {
        if (empty($data)) {
            throw new \InvalidArgumentException('Empty data.');
        }

        list($first) = $data;

        if (!is_array($first)) {
            throw new \InvalidArgumentException('$data is not an array of array.');
        }

        return $first;
    }

    /**
     * Build the question mark placeholder.  Helper function for insertOnDuplicateKeyUpdate().
     * Helper function for insertOnDuplicateKeyUpdate().
     *
     * @param $data
     *
     * @return string
     */
    protected function buildQuestionMarks($data)
    {
        $lines = [];
        foreach ($data as $row) {
            $count = count($row);
            $questions = [];
            for ($i = 0; $i < $count; ++$i) {
                $questions[] = '?';
            }
            $lines[] = '(' . implode(',', $questions) . ')';
        }

        return implode(', ', $lines);
    }

    /**
     * Build a value list.
     *
     * @param array $updatedColumns
     *
     * @return string
     */
    protected static function buildValuesList(array $updatedColumns)
    {
        $out = [];

        foreach ($updatedColumns as $key => $value) {
            if (is_numeric($key)) {
                $out[] = sprintf('`%s` = VALUES(`%s`)', $value, $value);
            } else {
                $out[] = sprintf('%s = %s', $key, $value);
            }
        }

        return implode(', ', $out);
    }
}