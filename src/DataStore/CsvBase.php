<?php

namespace zaboy\rest\DataStore;

use zaboy\rest\DataStore\DataStoreAbstract;
use zaboy\rest\DataStore\DataStoreException;
use zaboy\rest\DataStore\Iterators\CsvIterator;
use zaboy\rest\DataStore\ConditionBuilder\PhpConditionBuilder;
use Symfony\Component\Filesystem\LockHandler;

class CsvBase extends DataStoreAbstract
{

    const MAX_LOCK_TRIES = 30;

    protected $fileHandler;

    protected $filename;

    protected $lockHandler;

    /**
     * Column headings
     * @var mixed array
     */
    protected $columns;

    protected $csvDelimiter;

    /**
     * Csv constructor. If file with this name doesn't exist attempts find it in document root directory
     * @param string $filename
     * @param string $delimiter - csv field delimiter
     * @throws \zaboy\rest\DataStore\DataStoreException
     */
    public function __construct($filename, $delimiter = ';')
    {
        // At first checks existing file as it is
        // If doesn't exist converts to fullname in the DocumentRoot
        if (is_file($filename)) {
            $this->filename = $filename;
        } else {
            $this->filename = realpath(sys_get_temp_dir() . DIRECTORY_SEPARATOR . trim($filename, DIRECTORY_SEPARATOR));
            if (!is_file($this->filename)) {
                throw new DataStoreException('The specified source file does not exist');
            }
        }
        $this->lockHandler = new LockHandler($this->filename);
        $this->csvDelimiter = $delimiter;
        // Sets the column headings
        $this->getHeaders();

        $this->conditionBuilder = new PhpConditionBuilder();
    }


    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function read($id = null)
    {
        $this->openFile();
        // In the CSV-format first row always containts the column headings
        // That's why first row is passed during the file opening
        // And then it reads the file until end of file won't found or won't found the indentifier
        $row = null;
        while (!feof($this->fileHandler)) {
            $row = $this->getTrueRow(
                fgetcsv($this->fileHandler, null, $this->csvDelimiter)
            );
            if ($row && $row[$this->getIdentifier()] == $id) {
                break;
            }
        }
        $this->closeFile();
        return $row;
    }


    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function getIterator()
    {
        return new CsvIterator($this, $this->filename);
    }


    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function create($itemData, $rewriteIfExist = false)
    {
        $identifier = $this->getIdentifier();
        switch (true) {
            case (!isset($itemData[$identifier])):
                // There isn't item with identifier in the data set; creates a new item
                $item = $this->createNewItem($itemData);
                $item[$identifier] = $this->generatePrimaryKey();
                break;
            case (!$rewriteIfExist && !is_null($this->read($itemData[$identifier]))):
                throw new DataStoreException('Item is already exist with "id" =  ' . $itemData[$identifier]);
                break;
            default:
                // updates an existing item
                $id = $itemData[$identifier];
                $this->checkIdentifierType($id);
                $item = $this->createNewItem($itemData);
            break;
        }
        $this->flush($item);
        return $item;
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function update($itemData, $createIfAbsent = false)
    {
        $identifier = $this->getIdentifier();
        if (!isset($itemData[$identifier])) {
            throw new DataStoreException('Item must have primary key');
        }
        $id = $itemData[$identifier];
        $this->checkIdentifierType($id);
        $item = $this->read($id);

        switch (true) {
            case (is_null($item) && !$createIfAbsent):
                $errorMsg = sprintf('Can\'t update item with "id" = %s: item does not exist.', $id);
                throw new DataStoreException($errorMsg);
            case (is_null($item) && $createIfAbsent):
                // new item
                $item = $this->createNewItem($itemData);
                break;
        }
        foreach ($item as $key => &$value) {
            if (isset($itemData[$key])) {
                $item[$key] = $itemData[$key];
            }
        }
        $this->flush($item);
        return $item;
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function delete($id)
    {
        $this->checkIdentifierType($id);
        // If item with specified id was found flushs file without it
        $item = $this->read($id);
        if (!is_null($item)) {
            $this->flush($item, true);
            return 1;
        }
        // Else do nothing
        return 0;
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function deleteAll()
    {
        // Count rows
        $count = $this->count();
        $tmpFile = tempnam("/tmp", uniqid() . '.tmp');
        $tempHandler = fopen($tmpFile, 'w');
        // Write the headings only and right away closes file
        fputcsv($tempHandler, $this->columns, $this->csvDelimiter);
        fclose($tempHandler);
        // Changes the original file to a temporary one.
        if (!rename($tmpFile, $this->filename)) {
            throw new DataStoreException("Failed to write the results to a file.");
        }
        return $count;
    }

    /**
     * Flushs all changes to temporary file which then will change the original one
     *
     * @param $item
     * @param bool|false $delete
     * @throws \zaboy\rest\DataStore\DataStoreException
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
        foreach ($this as $index => $row) {
            // Check an identifier; if equals and it doesn't need to delete - inserts new item
            if ($item[$identifier] == $row[$identifier]) {
                if (!$delete) {
                    $this->writeRow($tempHandler, $item);
                }
                // anyway marks row as inserted
                $inserted = true;
            } else {
                // Just it inserts row from source-file (copying)
                $this->writeRow($tempHandler, $row);
            }
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
     * @param bool $seekFirstDataRow - the first row in csv-file contains the column headings; this parameter says,
     *     if it is need to pass it (row) after the opening the file.
     * @throws \zaboy\rest\DataStore\DataStoreException
     */
    protected function openFile($seekFirstDataRow = true, $nbTries = 0)
    {
        if (!$this->lockHandler->lock()) {
            if ($nbTries >= static::MAX_LOCK_TRIES) {
                throw new DataStoreException('Reach max retry for locking queue file ' . $this->filename);
            }
            usleep(10);
            return $this->openFile($seekFirstDataRow, ($nbTries + 1));
        }
        try {
            $this->fileHandler = fopen($this->filename, 'r');
            // Sometimes some editors leave a blank line in the end of file
            // That's why it reads the first symbol which points the file handler;
            // if it is EOF moves the pointer to begin of file
            // else moves one byte back (to start position before checking)
            $ch = fgetc($this->fileHandler);
            if (feof($this->fileHandler)) {
                fseek($this->fileHandler, 0);
            } else {
                fseek($this->fileHandler, -1, SEEK_CUR);
            }
            if ($seekFirstDataRow) {
                fgets($this->fileHandler);
            }
        } catch (Exception $e) {
            $this->lockHandler->release();
            throw new DataStoreException('Failed to open file. The specified file does not exist or one is closed for reading.');
        }
    }

    /**
     * Closes file
     */
    public function closeFile()
    {
        fclose($this->fileHandler);
        $this->lockHandler->release();
    }

    /**
     * Sets the column headings
     * @throws \zaboy\rest\DataStore\DataStoreException
     */
    public function getHeaders()
    {
        // Don't pass the first row!!
        $this->openFile(0);
        $this->columns = fgetcsv($this->fileHandler, null, $this->csvDelimiter);
        $this->closeFile();
    }

    /**
     * Creates a new item, combines data with the column headings
     * @param $itemData
     * @return array
     */
    protected function createNewItem($itemData)
    {
        $item = array_flip($this->columns);
        foreach ($item as $key => &$value) {
            if (isset($itemData[$key])) {
                $item[$key] = $itemData[$key];
            } else {
                $item[$key] = null;
            }
        }
        return $item;
    }

    /**
     * {@inheritdoc}
     *
     * {@inheritdoc}
     */
    public function count()
    {
        $count = 0;
        foreach ($this as $item) {
            $count++;
        }
        return $count;
    }

    /**
     * Returns the associative array with the column headings;
     * also checks and sanitize empty string and null value and converts type for the numeric fields
     * @param $row
     * @return array|null
     */
    public function getTrueRow($row)
    {
        if ($row) {
            array_walk($row, function(&$item, $key) {
                if ('' === $item) {
                    $item = null;
                }
                if ($item === '""') {
                    $item = '';
                }
                if (is_numeric($item)) {
                    if (intval($item) == $item) {
                        $item = intval($item);
                    } else {
                        $item = floatval($item);
                    }
                }
            });
            return array_combine($this->columns, $row);
        }
        return null;
    }

    /**
     * Writes the row in the csv-format
     * also converts empty string to string of two quotes
     * It's necessary to distinguish the difference between empty string and null value: both are writen as empty value
     * @param $fHandler
     * @param $row
     */
    public function writeRow($fHandler, $row)
    {
        array_walk($row, function(&$item, $key) {
            switch (true) {
                case ('' === $item):
                    $item = '""';
                    break;
                case (true === $item):
                    $item = 1;
                    break;
                case (false === $item):
                    $item = 0;
                    break;
            }
        });
        fputcsv($fHandler, $row, $this->csvDelimiter);
    }


    /**
     * Generates an unique identifier
     * @return string
     */
    protected function generatePrimaryKey()
    {
        return uniqid();
    }

    /**
     * Returns the deleimiter of csv fields
     * @return string
     */
    public function getCsvDelimiter()
    {
        return $this->csvDelimiter;
    }

}