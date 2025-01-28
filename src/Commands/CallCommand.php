<?php

namespace Nahid\Apily\Commands;

use Nahid\Apily\Api;
use Nahid\Apily\Client;
use Nahid\Apily\Utilities\Config;
use Nahid\Apily\Utilities\Helper;
use Nahid\Apily\Utilities\Json;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\VarDumper\VarDumper;

class CallCommand extends BaseCommand
{
    protected function configure()
    {
        $this->setName('call')
            ->setAliases(['x'])
            ->addArgument('name', null, 'API name', default: '')
            ->addOption('args', 'a', InputOption::VALUE_OPTIONAL, 'Arguments')
            ->addOption('info', 'i', InputOption::VALUE_NONE, 'Get detail information about the request')
            ->setDescription('Run a command');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $args = $input->getOption('args');
        $arguments = [];
        if ($args) {
            $arguments = json_decode($args, true);
        }

        $replacements = Config::makeEnvVariables($arguments);

        $apiName = $input->getArgument('name');
        $io = new SymfonyStyle($input, $output);

        $api = Api::from($apiName, $replacements);

        $httpClient = new Client();
        $response = $httpClient->httpClient()->send($api->request());
        if ($input->getOption('info')) {
            $io->title('Request');
            $requestSection = $output->section();
            $requestSection->writeln($this->formatMetaInfo([
                'Method' => $api->getMethod(),
                'URL' => $api->getFullUrl(),
            ]));

            $io->title('Headers');
            $headerSection = $output->section();
            $headerSection->writeln($this->formatHeaders($response->getHeaders()));
        }

        $io->title('Response');
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

}