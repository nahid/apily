<?php

namespace Nahid\Apily\Commands;

use Nahid\Apily\Api;
use Nahid\Apily\Assertions\TestRunner;
use Nahid\Apily\Client;
use Nahid\Apily\Utilities\Config;
use Nahid\Apily\Utilities\Helper;
use Nahid\Apily\Utilities\Json;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\ProgressIndicator;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

class ApiListCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this->setName('api:list')
            ->setAliases(['al'])
            ->setDescription('List of all API requests');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $files = glob(getcwd() . '/.apily/**/*.api');
        $apis = [];
        foreach ($files as $file) {
            $hasTest = file_exists(preg_replace('/\.api$/', '.test.php', $file));
            $requestFilePath = str_replace(getcwd() . '/.apily/', '', $file);
            $apiName = str_replace(['.api', '/'], ['', '.'], $requestFilePath);
            $apiData = json_decode(file_get_contents($file), true);
            $apis[] = [
                'name' => $apiName,
                'method' => $apiData['http']['method'] ?? 'N/A',
                'path' => $apiData['http']['path'] ?? 'N/A',
                'hasTest' => $hasTest ? 'Yes' : 'No',
            ];
        }

       $table = new Table($output);
         $table->setHeaders(['Name', 'Method', 'Path', 'Has Test']);
            $table->setRows($apis);

            $table->render();

        return Command::SUCCESS;
    }

}
