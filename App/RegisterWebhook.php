<?php
namespace App;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class RegisterWebhook
 * @package App
 * Registers a webhook with an event and a callback URL
 */
class RegisterWebhook extends CommonTasks
{

    /**
     * Configure the command.
     */
    public function configure()
    {
        $this->setName('create')
             ->setDescription('Creates a Webhook')
             ->addArgument('event-name', InputArgument::REQUIRED, 'Subscribes for the given event name')
             ->addArgument('callback-url', InputArgument::REQUIRED, 'Callback URL');
    }

    /**
     * Execute the command.
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {

        $event_name = $input->getArgument('event-name');
        $callback_url = $input->getArgument('callback-url');

        // save the new Event Name
        $status = $this->database->query(
            'insert into webhook_event(name) values(:event_name)',
            compact('event_name')
        );

        // create an entry in webhook table
        $event_id = $this->database->lastInsertId();

        if ($status) {

            // create an entry in webhook_callback table

            $this->database->query(
                'insert into webhook_callback(callback_url, event) values(:callback_url, :event_name)',
                compact('callback_url', 'event_name')
            );

            $this->database->query(
                'insert into webhook(fk_webhook_event, callback_url) values(:event_id, :callback_url)',
                compact('event_id', 'callback_url')
            );

            $this->output($output, 'Webhook registered to event: "'.$event_name.'" with a callback URL: "'.$callback_url.'"', 'info');
            return $this->showWebhooks($output);

        }

        return $this->output($output, 'Failure While Registration!', 'error');

    }
}
