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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

class ApiCreateCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this->setName('api:create')
            ->setAliases(['ac'])
            ->addArgument('name', null, 'API name', default: '')
            ->addOption('with-test', 't', InputOption::VALUE_NONE, 'Create test file')
            ->setDescription('Create a new API request');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $apiName = $input->getArgument('name');
        $fileName = str_replace('.', '/', $apiName) . '.api';
        $apiDir = dirname($fileName);

        if (file_exists(getcwd() . '/.apily/' . $fileName)) {
            $io->error('Api is already exists.');
            return Command::FAILURE;
        }

        $helper = $this->getHelper('question');
        $questionMethod = new ChoiceQuestion(
            'Select Method (defaults to GET)',
            // choices can also be PHP objects that implement __toString() method
            ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD', 'TRACE', 'CONNECT'],
            'GET'
        );

        $questionMethod->setErrorMessage('Method %s is invalid.');
        $method = $helper->ask($input, $output, $questionMethod);
        $bodyType = null;

        if (!in_array($method, ['GET', 'HEAD', 'OPTIONS', 'TRACE', 'CONNECT'])) {
            $questionBodyType = new ChoiceQuestion(
                'Select Method (defaults to GET)',
                // choices can also be PHP objects that implement __toString() method
                ['json', 'form-data', 'multipart', 'binary', 'x-www-form-urlencoded', 'text', 'empty'],
                'json'
            );

            $questionBodyType->setErrorMessage('Body type %s is invalid.');
            $bodyType = $helper->ask($input, $output, $questionBodyType);
        }

        $uri = $io->ask('Enter URI: ', validator: function ($value) {
            if (empty($value)) {
                throw new \Exception('URI cannot be empty');
            }

            return $value;
        });

        $body = '{}';

        $api = [
            'method' => $method,
            'uri' => $uri,
            'headers' => [],
            'description' => 'API request for ' . str_replace('.', ' ', $apiName),
        ];

        if ($bodyType) {
            $body = <<<BodyType
{
        "type": "{$bodyType}",
        "data": {}
    }
BodyType;
        }

        $api['body'] = $body;

        $sampleApiContents = file_get_contents(__DIR__ . '/../../stubs/sample.api.stub');
        $sampleApiContents = Helper::replacePlaceholders($sampleApiContents, $api);
        if (!is_dir(getcwd() . '/.apily/' . $apiDir)) {
            mkdir(getcwd() . '/.apily/' . $apiDir, 0777, true);
        }

        file_put_contents(getcwd() . '/.apily/' . $fileName, $sampleApiContents);

        if ($input->getOption('with-test')) {
            $testContents = file_get_contents(__DIR__ . '/../../stubs/sample.test.php.stub');
            $testContents = Helper::replacePlaceholders($testContents, ['name' => ucfirst(str_replace('.', ' ', $apiName))]);
            file_put_contents(getcwd() . '/.apily/' . str_replace('.api', '.test.php', $fileName), $testContents);
        }

        $io->success($fileName . ' created successfully.');

        return Command::SUCCESS;
    }

}
