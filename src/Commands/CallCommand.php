<?php

namespace Nahid\Apily\Commands;

use GuzzleHttp\Psr7\Response;
use Nahid\Apily\Api;
use Nahid\Apily\Assertions\TestRunner;
use Nahid\Apily\Client;
use Nahid\Apily\Utilities\Config;
use Nahid\Apily\Utilities\Json;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Helper\ProgressIndicator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CallCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this->setName('call')
            ->setAliases(['x'])
            ->addArgument('name', null, 'API name', default: '')
            ->addOption('args', 'a', InputOption::VALUE_OPTIONAL, 'Arguments')
            ->addOption('info', 'i', InputOption::VALUE_NONE, 'Get detail information about the request')
            ->addOption('test', 't', InputOption::VALUE_NONE, 'Run assertions')
            ->setDescription('Run a command');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getOption('test') && $input->getOption('info')) {
            throw new \Exception('Cannot use --test and --info together');
        }

        $args = $input->getOption('args');
        $arguments = [];
        if ($args) {
            $arguments = json_decode($args, true);
        }

        $replacements = Config::makeEnvVariables($arguments);

        $apiName = $input->getArgument('name');
        $io = new SymfonyStyle($input, $output);
        $progressIndicator = new ProgressIndicator($output);

        $api = Api::from($apiName, $replacements);

        $metaInfo = [
            'URL' => $api->getFullUrl(),
            'Method' => $api->getMethod(),
        ];

        $httpClient = new Client();
        $progressIndicator->start('Sending request...');
        $totalReceivedBytes = 0;
        $response = null;

        if (Config::get('mock_enabled', false)) {
            $progressIndicator->advance();
            $progressIndicator->setMessage('Receiving...');

            $response = $api->getMockResponse();
            $metaInfo['Status'] = $response->getStatusCode() . ' ' . $httpClient->getHttpStatus($response->getStatusCode());
            $metaInfo['Size'] = round(strlen($response->getBody())/1000, 2) . ' KB';
            $metaInfo['Duration'] = rand(20, 1000 ) . 'ms';
            $metaInfo['Connect Time'] = rand(20, 1000 ) . 'ms';

            $progressIndicator->advance();

            $totalReceivedBytes = strlen($response->getBody());

        }

        if (!Config::get('mock_enabled', false)) {
            $step = 1;
            $response = $httpClient->httpClient()->send($api->request(), [
                'http_errors' => false,
                'on_stats' => function ($stats) use (&$metaInfo) {
                    $metaInfo['Duration'] = round(1000 * $stats->getTransferTime(), 2) . 'ms';
                    $metaInfo['Connect Time'] = round(1000 * $stats->getHandlerStat('connect_time'), 2) . 'ms';
                },
                'progress' => function ($total, $received) use ($progressIndicator, &$step) {
                    $step++;
                    if ($step > 10) {
                        $progressIndicator->setMessage('Receiving...');
                    }
                    $progressIndicator->advance();
                }
            ]);

            $totalReceivedBytes = $response->getBody()->getSize();
            $metaInfo['Size'] = round($totalReceivedBytes/1000, 2) . ' KB';
            $metaInfo['Status'] = $response->getStatusCode() . ' ' . $httpClient->getHttpStatus($response->getStatusCode());
        }



        if ($input->getOption('test')) {
            $progressIndicator->advance();
            $progressIndicator->setMessage('Running assertions...');

            $assertionSection = $output->section();
            $io->title('Assertions');
            $testPath = str_replace('.', '/', $apiName);
            $testFilePath = getcwd().'/.apily/'.$testPath.'.test.php';

            $progressIndicator->advance();

            if (!file_exists($testFilePath)) {
                throw new \Exception("File not found: $testFilePath");
            }

            $test = require $testFilePath;
            $testInstance = $test($response);

            $testRunner = new TestRunner($testInstance);
            $testRunner->run();
            $progressIndicator->advance();
            $assertions = $testRunner->getAssertions();

            $output->writeln($this->formatAssertions($assertions));

            return Command::SUCCESS;
        }

        $progressIndicator->finish('Received (' . $totalReceivedBytes . ' bytes)');

        $output->writeln("↓");

        if ($input->getOption('info')) {
            $requestSection = $output->section();
            $requestSection->writeln($this->formatMetaInfo($metaInfo));

            $io->title('Headers');
            $headerSection = $output->section();
            $headerSection->writeln($this->formatHeaders($response->getHeaders()));
            $io->title('Response');
        }

        $responseSection = $output->section();
        if (str_contains($response->getHeaderLine('Content-Type'), 'application/json')) {
            $body = Json::highlight($response->getBody());
        } else {
            $body = $response->getBody();
        }

        $responseSection->writeln($body);

        return Command::SUCCESS;
    }


    private function formatHeaders(array $headers): string
    {
        $formatted = '';
        foreach ($headers as $header => $value) {
            $formatted .= sprintf("<fg=green>%s</>: %s\n", $header, implode(', ', $value));
        }

        return $formatted;
    }

    private function formatMetaInfo(array $info)
    {
        $formatted = '';
        foreach ($info as $key => $value) {
            $formatted .= sprintf("<fg=green>%s</>: %s\n", $key, $value);
        }

        return $formatted;
    }

    private function formatAssertions(array $assertions): string
    {
        $formatted = '';
        foreach ($assertions as $key => $value) {
            if ($value === true) {
                $formatted .= sprintf("<fg=green>✔ [Passed]:</> %s\n", $key);
            } else {
                $formatted .= sprintf("<fg=red>ⅹ [Failed]</>: %s -> %s\n", $key, $value);
            }
        }

        return $formatted;
    }

    private function makeMockResponse(Api $api): ResponseInterface
    {
        $mockData = $api->getMockResponse();
        $data = $mockData['body'] ?? [];
        $body = '';

        if (is_array($data)) {
            $body = json_encode($data, JSON_PRETTY_PRINT);
        } else {
            $body = $data;
        }

        return new Response(
            $mockData['status'] ?? 200,
            $mockData['headers'] ?? [],
            $body
        );
    }

}
