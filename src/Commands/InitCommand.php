<?php

namespace Nahid\Apily\Commands;

use Nahid\Apily\Api;
use Nahid\Apily\Assertions\TestRunner;
use Nahid\Apily\Client;
use Nahid\Apily\Utilities\Config;
use Nahid\Apily\Utilities\Json;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\ProgressIndicator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class InitCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this->setName('init')
            ->setDescription('Initialize Apily');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $pi = new ProgressIndicator($output);
        $pi->start('Initializing...');
        $io = new SymfonyStyle($input, $output);
        $pi->advance();

        if (is_dir(getcwd() . '/.apily')) {
            $pi->advance();
            $io = new SymfonyStyle($input, $output);
            $io->error('Apily is already initialized.');
            $pi->finish("Finished");
            return Command::FAILURE;
        }

        $pkgDir = __DIR__ . '/../../';

        mkdir(getcwd() . '/.apily/users', 0777, true);
        copy($pkgDir . 'stubs/apily.conf.stub', getcwd() . '/apily.conf.example');
        copy($pkgDir . 'stubs/apily.conf.stub', getcwd() . '/apily.conf');
        copy($pkgDir . 'stubs/all_users.api.stub', getcwd() . '/.apily/users/all.api');
        $pi->advance();

        $pi->setMessage('Finalizing...');
        $advances = 1;
        while ($advances <= 3) {
            $pi->advance();
            $advances++;
            usleep(500000);
        }

        $pi->finish('Initialized successfully!');
        $io->success('Try Command: ./vendor/bin/apily call users.all -i');

        return Command::SUCCESS;
    }

}
