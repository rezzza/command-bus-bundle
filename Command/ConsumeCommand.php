<?php

namespace Rezzza\CommandBusBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Rezzza\VlrModelBundle\Command\RedisCommandBus;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Rezzza\CommandBus\Domain\Consumer\Consumer;

class ConsumeCommand extends Command
{
    private $container;
    private $verbose;
    private $output;

    public function __construct(ContainerInterface $container)
    {
        parent::__construct();

        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('rezzza:command_bus:consume')
            ->setDescription('Consume a command bus')
            ->addArgument('command_class', InputArgument::REQUIRED, 'Command to consume.')
            ->addOption('consumer', null, InputOption::VALUE_REQUIRED, 'Which consumer should we use ?', 'default')
            ->addOption('time-limit', null, InputOption::VALUE_REQUIRED, 'During how many time this command will listen to the queue.', 60)
            ->addOption('usleep', null, InputOption::VALUE_REQUIRED, 'Micro seconds', 100000)
            ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $commandClass  = $input->getArgument('command_class');
        $timeLimit     = (int) $input->getOption('time-limit');
        $usleep        = (int) $input->getOption('usleep');
        $maxTime       = time() + $timeLimit;
        $this->verbose = $input->getOption('verbose');
        $this->output  = $output;

        $consumer     = $this->container->get(sprintf('rezzza_command_bus.command_bus.consumer.%s', $input->getOption('consumer')));
        $live         = true;
        do {
            $response = $consumer->consume($commandClass);

            if ($response) {
                if ($response->isSuccess()) {
                    $this->writeDependVerbosity('<info>S</info>', '<info>Success</info>');
                } else {
                    $this->writeDependVerbosity('<error>F</error>', sprintf('<error>Failed</error>: %s', $response->getError()->getMessage()));
                }
            } else {
                $output->write('.');
            }

            usleep($usleep);

            if (time() >= $maxTime) {
                $live = false;
            }
        } while ($live);
    }

    private function writeDependVerbosity($notVerboseMessage, $verboseMessage)
    {
        if ($this->verbose) {
            $this->output->writeln($verboseMessage);
            return;
        }

        $this->output->write($notVerboseMessage);
    }
}
