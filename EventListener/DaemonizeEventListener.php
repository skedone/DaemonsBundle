<?php

namespace Edo\DaemonsBundle\EventListener;

use Edo\DaemonsBundle\Command\ContainerAwareDaemonCommand;
use Edo\DaemonsBundle\Exception\ExtendedConfigurationException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\Exception\ProcessFailedException;

class DaemonizeEventListener {

    const TIMER_LOGGER = 1000;

    const TIMER_KEEP_ALIVE = false;

    /** @var LoggerInterface  */
    private $logger;

    public function __construct(LoggerInterface $loggerInterface)
    {
        $this->logger = $loggerInterface;
    }

    public function onConsoleCommand(ConsoleCommandEvent $event)
    {
        /**
         * Skip if command is not a daemon-command
         */
        if(!($event->getCommand() instanceof ContainerAwareDaemonCommand)) {
            return true;
        }

        if(true !== $this->mergeDefinitions($event)) {
            throw new ExtendedConfigurationException('No definitions');
        }

        $input = new ArgvInput();
        $input->bind($event->getCommand()->getDefinition());

        if(true !== $this->addSignals()) {
            throw new \Exception('No signals!');
        }

        if(true === $input->getOption('log-memory')) {
            if(true !== $this->addWatchers()) {
                throw new \Exception('No watchers!');
            }
        }

        if(true === $input->getOption('daemonize') & function_exists('pcntl_fork')) {

            $processId = pcntl_fork();

            if ($processId === -1) {
                throw new \Exception('The process failed to fork.');
            } else if($processId) {
                $this->logger->info($event->getCommand()->getName() . ' correctly started as a daemon');
                exit(0);
            }

            if ( posix_setsid() == -1 ) {
                throw new \Exception("Unable to detach from the terminal window.");
            }

        } else {
            $event->getOutput()->writeln('<comment>Use --daemonize option to run the process in background.</comment>');
        }

        return true;

    }

    private function addSignals()
    {
        \Amp\onSignal(SIGINT, function(){
            exit();
        });
        \Amp\onSignal(SIGTERM, function(){
            exit();
        });

        register_shutdown_function(function(){
            $this->logger->alert('Clean exit. Thank you.');
            if (\Amp\info()["state"] !== \Amp\Reactor::STOPPED) {
                \Amp\stop();
            }
        });

        return true;
    }

    private function mergeDefinitions(ConsoleCommandEvent $event)
    {
        $inputDefinition = $event->getCommand()->getApplication()->getDefinition();
        $inputDefinition
            ->addOption(
                new InputOption('log-memory', null, InputOption::VALUE_NONE, 'Output information about memory usage', null)
            );
        $inputDefinition
            ->addOption(
                new InputOption('daemonize', null, InputOption::VALUE_NONE, 'Output information about memory usage', null)
            );

        $event->getCommand()->mergeApplicationDefinition();

        return true;
    }

    private $memory = 0;

    private $leakage = 0;

    const MAX_LEAKAGE = 5;


    const TIMER_LEAKAGE = 1000;

    private $previousTickMemory;
    private $leak_counter = 0;

    private function addWatchers()
    {

        \Amp\repeat(function () {
            $previousMemory = $this->memory;
            $this->memory = memory_get_peak_usage(true);
            $leakage = $this->memory - $previousMemory;
            $this->logger->info('Memory consumption', ['memory' => $this->memory / 1024 / 1024, 'increase' => $leakage]);

        }, self::TIMER_LOGGER, $options = ['keep_alive' => self::TIMER_KEEP_ALIVE]);

        return true;
    }
}