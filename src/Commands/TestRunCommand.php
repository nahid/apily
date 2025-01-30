<?php

namespace Nahid\Apily\Commands;

use Nahid\Apily\Api;
use Nahid\Apily\Assertions\BaseAssertion;
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

class TestRunCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this->setName('test')
            ->setAliases(['t'])
            ->setDescription('Run all tests');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $pi = new ProgressIndicator($output);
        $client = new Client();
        $pi->start('Running tests...');

        $dirs = scandir(getcwd() . '/.apily');
        $dirs = array_slice($dirs, 2);
        $assertions = [];
        $testStatus = true;

        foreach ($dirs as $dir) {
            $files = glob(getcwd() . '/.apily/' . $dir . '/*.api');
            foreach ($files as $file) {
                $requestFilePath = str_replace(getcwd() . '/.apily/', '', $file);
                $apiName = str_replace(['.api', '/'], ['', '.'], $requestFilePath);
                $pi->setMessage('Running: ' . $apiName);
                $testFile = preg_replace('/\.api$/', '.test.php', $file);
                $api = Api::from($apiName);
                $request = $api->request();
                $response = $client->httpClient()->send($request, [
                    'http_errors' => false,
                    'progress' => function ($total, $received) use ($pi, $file) {
                        $pi->advance();
                    }
                ]);
                if (file_exists($testFile)) {
                    $pi->advance();
                    $assertRunnerFunc = require $testFile;
                    $assertionInstance = $assertRunnerFunc($response);
                    if (!$assertionInstance instanceof BaseAssertion)
                        continue;

                    $testRunner = new TestRunner($assertionInstance);
                    $testRunner->run();
                    $assertions[$apiName] = $this->formatAssertions($testRunner->getAssertions(), $testStatus);
                    $pi->advance();
                    $pi->setMessage('Finished: ' . $apiName);
                }
            }
        }

        $pi->finish('Tests run completed.');
        $io->writeln($testStatus ? '<info>Passed!</info>' : '<error>✘ Failed!</error>');

        foreach ($assertions as $file => $assertion) {
            $io->writeln(sprintf("\n<fg=yellow>API: %s</>", $file));
            $io->writeln($assertion);
        }


        return Command::SUCCESS;
    }

    private function formatAssertions(array $assertions, &$testStatus): string
    {
        $formatted = '';
        foreach ($assertions as $key => $value) {
            if ($value === true) {
                $formatted .= sprintf("<fg=green>✔ [Passed]:</> %s\n", $key);
            } else {
                $testStatus = false;
                $formatted .= sprintf("<fg=red>ⅹ [Failed]</>: %s -> %s\n", $key, $value);
            }
        }

        return $formatted;
    }

}
