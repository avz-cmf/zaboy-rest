<?php

namespace zaboy\rest\DataStore;

use zaboy\rest\DataStore\CsvBase;
use zaboy\rest\DataStore\Iterators\CsvIterator;
use zaboy\rest\DataStore\DataStoreException;

class CsvIntId extends CsvBase
{
    /**
     * index offset from begining of file
     * @var int
     */
    protected $offset = 0;

    /**
     * offset from begining of file in bytes
     * @var int
     */
    protected $bOffset = 0;

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function __construct($filename, $delimiter)
    {
        parent::__construct($filename, $delimiter);
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
        $prevId = $this->offset;
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
        // If the same item was not found and changed inserts the new item as the last row in the file
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
     * Opens file for reading.
     * If sepicified id is greater than offset after the last reading sets file pointer to this row
     * @param $id
     * @throws \zaboy\rest\DataStore\DataStoreException
     */
    protected function openFile($id = null, $nbTries = 0)
    {
        parent::openFile($id, $nbTries);
        if ($id >= $this->offset && $this->offset) {
            fseek($this->fileHandler, $this->bOffset);
            // Sometimes some editors leave a blank line in the end of file
            // That's why it reads the first symbol which points the file handler;
            // if it is EOF moves the pointer to begin of file
            // else moves one byte back (to start position before checking)
            $ch = fgetc($this->fileHandler);
            if (feof($this->fileHandler)) {
                $this->fixOffset();
                fseek($this->fileHandler, $this->bOffset);
            } else {
                fseek($this->fileHandler, -1, SEEK_CUR);
            }
        } else {
            $this->fixOffset();
        }
    }

    /**
     * Fixes index and byte offset after the last reading
     * @param int $id
     */
    public function fixOffset($id = 0)
    {
        $this->offset = $id;
        if ($id) {
            $this->bOffset = ftell($this->fileHandler);
        } else {
            $this->bOffset = 0;
        }
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