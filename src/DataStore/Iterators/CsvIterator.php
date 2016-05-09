<?php

namespace zaboy\rest\DataStore\Iterators;

use zaboy\rest\DataStore\DataStoreException;
use zaboy\rest\DataStore\Iterators\DataStoreIterator;
use zaboy\rest\DataStore\Interfaces\ReadInterface;

class CsvIterator extends DataStoreIterator
{

    /**
     * File handler.
     * @var resource
     */
    protected $fileHandler;

    /**
     * CsvIterator constructor.
     * After the creation an object it opens the file (by filename) and locks one.
     */
    public function __construct(ReadInterface $dataStore, $filename)
    {
        parent::__construct($dataStore);
        if (!is_file($filename)) {
            throw new DataStoreException(sprintf('The specified file path "%s" does not exist', $filename));
        }
        $this->fileHandler = fopen($filename, 'r');
        flock($this->fileHandler, LOCK_SH);
        // We always pass the first row because it contains the column headings.
        fgets($this->fileHandler);
    }

    /**
     * During destruction an object it unlocks the file and then closes one.
     */
    function __destruct()
    {
        flock($this->fileHandler, LOCK_UN);
        fclose($this->fileHandler);
    }


    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->index = 1;
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        ++$this->index;
    }

    /**
     * It reads current row from the self file handler but uses a method from dataStore object for data conversion.
     * @return mixed
     */
    public function current()
    {
        return $this->dataStore->getTrueRow(
            fgetcsv($this->fileHandler, null, $this->dataStore->getCsvDelimiter())
        );
    }


    /**
     * It checks if index is valid.
     * If index doesn't set it returns false.
     * Else reads first symbol after the file pointer. If this symbol is EOF it returns false.
     * Finally it sets the file pointer one byte back and returns true.
     * {@inheritdoc}
     */
    public function valid()
    {
        if (!isset($this->index)) {
            return false;
        }
        $ch = fgetc($this->fileHandler);
        if (feof($this->fileHandler)) {
            return false;
        }
        fseek($this->fileHandler, -1, SEEK_CUR);
        return true;
    }

}