<?php

namespace Nahid\Apily\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressIndicator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

class ServerStartCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this->setName('server:start')
            ->setAliases(['s'])
            ->addOption('port', 'p', InputOption::VALUE_OPTIONAL, 'Port number', default: 1988)
            ->setDescription('Start web server for API mocking');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->info('Starting server...');
        $io->warning('Press Ctrl+C to stop the server');

        $host = '127.0.0.1';
        $port = $input->getOption('port');
        $command = ['php', '-S', "{$host}:{$port}", '.apily/index.php'];
        $process = new Process($command);
        $process->setTimeout(null);

        $io->writeln("<comment>Server started at http://{$host}:{$port}</comment>");

        $process->run(function ($type, $buffer) use ($output) {
            $output->write($buffer);
        });

        return Command::SUCCESS;
    }

}
