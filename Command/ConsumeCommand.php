<?php

namespace Rezzza\CommandBusBundle\Command;

use Rezzza\CommandBus\Domain\Consumer\Consumer;
use Rezzza\VlrModelBundle\Command\RedisCommandBus;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\LockHandler;

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
            ->addOption('iteration-limit', 'i', InputOption::VALUE_REQUIRED, 'Limit of iterations, -1 for infinite..', -1)
            ->addOption('time-limit', null, InputOption::VALUE_REQUIRED, 'During how many time this command will listen to the queue.', 60)
            ->addOption('usleep', null, InputOption::VALUE_REQUIRED, 'Micro seconds', 100000)
            ->addOption('lock', null, InputOption::VALUE_REQUIRED, 'Only one command processing ?')
            ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $lockKey        = $input->getOption('lock');
        $commandClass   = $input->getArgument('command_class');
        $timeLimit      = (int) $input->getOption('time-limit');
        $iterationLimit = (int) $input->getOption('iteration-limit');
        $usleep         = (int) $input->getOption('usleep');
        $maxTime        = $timeLimit < 0 ? null : time() + $timeLimit;
        $iteration      = 0;
        $this->verbose  = $input->getOption('verbose');
        $this->output   = $output;

        $isLive = function() use ($maxTime, $iteration, $iterationLimit) {
            // time criteria
            if (null !== $maxTime && time() >= $maxTime) {
                return false;
            }

            // iteration criteria
            if ($iteration >= $iterationLimit && $iterationLimit != -1) {
                return false;
            }

            return true;
        };

        $lock     = $lockKey ? new LockHandler($lockKey) : null;
        $consumer = $this->container->get(sprintf('rezzza_command_bus.command_bus.consumer.%s', $input->getOption('consumer')));

        do {
            if (false === $lock->lock()) {
                $this->writeDependVerbosity('L', 'Locked');
                usleep($usleep);
                continue;
            }

            $response = $consumer->consume($commandClass);

            if ($response) {
                if ($response->isSuccess()) {
                    $this->writeDependVerbosity('<info>S</info>', '<info>Success</info>');
                } else {
                    $this->writeDependVerbosity('<error>F</error>', sprintf('<error>Failed</error>: %s', $response->getError()->getMessage()));
                }
                $iteration++;
            } else {
                $output->write('.');
            }

            usleep($usleep);
        } while ($isLive());
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
