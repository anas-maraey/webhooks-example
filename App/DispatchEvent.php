<?php

namespace App;

use Carbon\Carbon;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use GuzzleHttp\Client;


class DispatchEvent extends CommonTasks
{
    //webhooks dispatch event_name message

    /**
     * Configure the command.
     */
    public function configure()
    {
        $this->setName('dispatch')
            ->setDescription('Dispatches event name with given message')
            ->addArgument('event-name', InputArgument::REQUIRED, 'Subscribes for the given event name')
            ->addArgument('message', InputArgument::REQUIRED, 'message');
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
        $message = $input->getArgument('message');

        // Check existense if event

        $event = $this->database->checkField('webhook_event', 'name', $event_name);

        $event = ( count($event) > 0 ) ? $event[0] : null;

        if(isset($event)) {

            $callback_record = $this->database->checkField('webhook_callback', 'event', $event_name);
            $callback_record = ( count($callback_record) > 0 ) ? $callback_record[0] : null;

            $client = new Client();
            try{
                // https://webhook.site/5cd16f60-eaa4-4ce5-8fc9-a83523796297
                $response = $client->request('POST', $callback_record['callback_url']);
                $response_status_code = $response->getStatusCode();
            } catch (RequestException $r_ex) {
                $response_status_code = $r_ex->getResponse()->getStatusCode();
            }

            $last_call_at_obj = Carbon::now();

            $last_call_at = $last_call_at_obj->toDateTimeString();


            if ($response_status_code == 200) {

                $status = 'success';
                $this->database->query(
                    'UPDATE `webhook_callback` SET `message`=:message, `status`=:status, `last_call_at`=:last_call_at WHERE `id_webhook_callback` = '. $callback_record["id_webhook_callback"] .'',
                    compact('message', 'status', 'last_call_at')
                );

                return $this->output($output, 'Success !', 'comment');

            } else {

                $status = 'fail';

                $retry_at = $this->calcRetryAt($callback_record['retries'], $last_call_at_obj);

                $this->database->query(
                    'UPDATE `webhook_callback` SET `message`=:message, `status`=:status, `last_call_at`=:last_call_at, `retry_at`=:retry_at, `retries`= `retries`+1  WHERE `id_webhook_callback` = '. $callback_record["id_webhook_callback"] .'',
                    compact('message', 'status', 'last_call_at', 'retry_at')
                );

                return $this->output($output, 'Failed!, Event Rescheduled at: '.$retry_at, 'error');
            }

        }

        return $this->output($output, 'Event was not found!', 'error');
    }


    /**
     * Calculate the retry time based on number of failures
     * @return DataTime
     */

    public function calcRetryAt($retries_count, Carbon $last_call_date)
    {
        $schedule_criteria = [
            '1' =>  1,
            '2' =>  5,
            '3' =>  10,
            '4' =>  30,
            '5' =>  60,
            '6' =>  120,
            '7' =>  300,
            '8' =>  600,
            '9' =>  1440
        ];

        return array_key_exists($retries_count, $schedule_criteria) ? $last_call_date->addMinutes($schedule_criteria[$retries_count])->toDateTimeString() : null;
    }
}