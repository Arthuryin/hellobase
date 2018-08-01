<?php

namespace HelloBase;

use Hbase\BatchMutation;
use Hbase\HbaseClient;
use Hbase\Mutation;

class Command
{
    protected $table;
    protected $mutations = [];

    /**
     * Command constructor.
     * @param Table $table
     */
    public function __construct(Table $table)
    {
        $this->table = $table;
    }

    public function put($row, array $data)
    {
        if (!isset($this->mutations[$row])) {
            $this->mutations[$row] = [];
        }

        foreach ($data as $column => $value) {
            $this->mutations[$row][] = new Mutation([
                'column' => $column,
                'value' => $value,
                'isDelete' => false
            ]);
        }
    }

    /**
     * @return int
     * @throws \Exception
     */
    public function execute()
    {
        $commands = [];

        foreach ($this->mutations as $row => $mutation) {
            $commands[] = new BatchMutation(['row' => $row, 'mutations' => $mutation]);
        }

        if (empty($commands)) {
            return 0;
        }

        /**
         * @var $client HbaseClient
         */
        $client = $this->table->getConnection()->getClient();

        try {
            $client->mutateRows($this->table->getTable(), $commands, []);
        } catch (\Exception $exception) {
            throw $exception;
        }

        $this->reset();

        return count($commands);
    }

    public function reset()
    {
        $this->mutations = [];
    }
}
