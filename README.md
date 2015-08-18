Proof of concept
=================

A worker example
```php    
    <?php
    
    namespace AppBundle\Command;
    
    use Edo\DaemonsBundle\Command\ContainerAwareDaemonCommand;
    use Symfony\Component\Console\Input\Input;
    use Symfony\Component\Console\Input\InputArgument;
    use Symfony\Component\Console\Input\InputInterface;
    use Symfony\Component\Console\Input\InputOption;
    use Symfony\Component\Console\Output\OutputInterface;
    
    class KafkaConsumerCommand extends ContainerAwareDaemonCommand {
    
        protected function configure()
        {
            $this
                ->setName('kafka:consumer')
                ->setDescription('')
            ;
    
        }
    
        /**
         *
         * This function is called before the loop
         *
         * @param InputInterface $inputInterface
         * @param OutputInterface $outputInterface
         */
        protected function initialize(InputInterface $inputInterface, OutputInterface $outputInterface)
        {
    
        }
    
    
        /**
         *
         * This function is the loop, it means you can use this function as while(1)
         *
         * @param InputInterface $input
         * @param OutputInterface $output
         */
    
        private $string = '';
    
        protected function execute(InputInterface $input, OutputInterface $output)
        {
            $this->string .= substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 204800);
        }
    
        /**
         *
         * This function is called inside register_shutdown_function()
         *
         * @param InputInterface $input
         * @param OutputInterface $output
         */
        protected function terminate(InputInterface $input, OutputInterface $output)
        {
    
        }
    }
```