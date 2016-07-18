<?php

namespace zaboy\rest\DataStore;

use Symfony\Component\Filesystem\LockHandler;

class CsvIntId extends CsvBase
{
    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function __construct($filename, $delimiter, LockHandler $lockHandler)
    {
        parent::__construct($filename, $delimiter, $lockHandler);
        if (!$this->checkIntegrityData()) {
            throw new DataStoreException('The source file contains wrong data');
        }
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    protected function flush($item, $delete = false)
    {
        // Create and open temporary file for writing
        $tmpFile = tempnam(sys_get_temp_dir(), uniqid() . '.tmp');
        $tempHandler = fopen($tmpFile, 'w');
        // Write headings
        fputcsv($tempHandler, $this->columns, $this->csvDelimiter);
        $identifier = $this->getIdentifier();
        $inserted = false;
        $prevId = -1;
        foreach ($this as $index => $row) {
            // Check an identifier; if equals and it doesn't need to delete - inserts new item
            if ($item[$identifier] == $row[$identifier]) {
                if (!$delete) {
                    $this->writeRow($tempHandler, $item);
                }
                // anyway marks row as inserted
                $inserted = true;
            } elseif ($item[$identifier] > $prevId && $item[$identifier] < $row[$identifier]) {
                // inserting with autosorting
                if (!$delete) {
                    $this->writeRow($tempHandler, $item);
                }
                $this->writeRow($tempHandler, $row);
                $inserted = true;
            } else {
                $this->writeRow($tempHandler, $row);
            }
            $prevId = min($item[$identifier], $row[$identifier]);
        }
        // If the same item was not found and changed it inserts the new item as the last row in the file
        if (!$inserted) {
            $this->writeRow($tempHandler, $item);
        }
        fclose($tempHandler);
        // Copies the original file to a temporary one.
        if (!copy($tmpFile, $this->filename)) {
            unlink($tmpFile);
            throw new DataStoreException("Failed to write the results to a file.");
        }
        unlink($tmpFile);
    }

    /**
     * Generates an unique identifier
     * @return int
     */
    protected function generatePrimaryKey()
    {
        $this->openFile(1);
        $id = null;
        while (!feof($this->fileHandler)) {
            $row = $this->getTrueRow(
                fgetcsv($this->fileHandler, null, $this->csvDelimiter)
            );
            if ($row) {
                $id = $row[$this->getIdentifier()];
            }
        }
        return ++$id;
    }

    /**
     * Checks integrity data
     * @return bool
     * @throws \zaboy\rest\DataStore\DataStoreException
     */
    public function checkIntegrityData()
    {
        $prevId = 0;
        $identifier = $this->getIdentifier();
        foreach ($this as $item) {
            $this->checkIdentifierType($item[$identifier]);
            if ($item[$identifier] < $prevId) {
                throw new DataStoreException("This storage type supports only a list ordered by id ASC");
            }
            $prevId = $item[$identifier];
        }
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    protected function checkIdentifierType($id)
    {
        $idType = gettype($id);
        if ($idType == 'integer') {
            return true;
        } else {
            throw new DataStoreException("This storage type supports integer primary keys only");
        }
    }
}