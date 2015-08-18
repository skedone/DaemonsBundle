<?php
/**
 * Created by PhpStorm.
 * User: skedo
 * Date: 16/08/15
 * Time: 16:43
 */

namespace Edo\DaemonsBundle\Command;


use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class ContainerAwareDaemonCommand extends ContainerAwareCommand {

    public function run(InputInterface $inputInterface, OutputInterface $outputInterface)
    {
        register_shutdown_function(array($this, 'terminate'), $inputInterface, $outputInterface);

        \Amp\run(function () use ($inputInterface, $outputInterface) {

            \Amp\repeat(function () use ($inputInterface, $outputInterface) {

                if($leaking = $this->isLeaking()) {
                    $this->getContainer()->get('logger')->alert('Memory leaked.', $leaking);
                }

                parent::run($inputInterface, $outputInterface);

            }, 1);

            $outputInterface->writeln('The command has <info>successfully</info> started.');
        });
    }

    public function terminate(InputInterface $inputInterface, OutputInterface $outputInterface)
    {

    }

    public function stop()
    {
        \Amp\stop();
    }

    private $previousTickMemory = 0;

    private function isLeaking()
    {
        $currentMemory = \memory_get_usage(true);
        if($this->previousTickMemory === 0) {
            $this->previousTickMemory = $currentMemory;
            return false;
        }

        $leakage = $currentMemory - $this->previousTickMemory;
        $this->previousTickMemory = $currentMemory;

        if($leakage > 0) {
            return [
                'leakage' => $leakage,
                'memory' => $currentMemory,
            ];
        }
        return false;
    }
}