<?php
namespace App;

use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;

class CommonTasks extends SymfonyCommand
{

    /**
     * The wrapper for the database.
     *
     * @var DatabaseAdapter
     */
    protected $database;

    /**
     * Create a new Command instance.
     *
     * @param DatabaseAdapter $database
     */
    public function __construct(DatabaseAdapter $database)
    {
        $this->database = $database;

        parent::__construct();
    }

    /**
     * Show a table of all webhooks.
     *
     * @param OutputInterface $output
     * @return mixed
     */
    protected function showWebhooks(OutputInterface $output)
    {
        $webhooks = $this->database->fetchAll('webhook');

        if ($webhooks) {
            $table = new Table($output);

            $table->setHeaders(['Id', 'Event Id', 'Callback URL'])
                  ->setRows($webhooks)
                  ->render();
        } else {
            $this->output($output, 'There are no webhooks', 'comment');

            exit(1);
        }
    }

    //TODO: to be removed
    /**
     * Finds out whether an event exists or no,
     * if it was found return it, else return false
     * @param
     * @return mixed
     */
    public function checkForEvent(){}

    /**
     * Output to console.
     *
     * @param OutputInterface $output
     * @param $message
     * @param $type (comment/error/info/success)
     * @return mixed
     */
    protected function output(OutputInterface $output, $message, $type)
    {

            $output->writeln('<'.$type.'>'.$message.'</'.$type.'>');
    }
}
