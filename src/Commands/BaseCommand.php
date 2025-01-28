<?php

namespace Nahid\Apily\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Process\Process;

class BaseCommand extends Command
{
    protected InputInterface $input;
    protected OutputInterface $output;

    protected function initialize(InputInterface $input,OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;
    }

    protected function ask(string $question, string $default = 'wpdrill'): string
    {
        $helper = $this->getHelper('question');
        $question = new Question($question, $default);

        return $helper->ask($this->input, $this->output, $question);
    }

    protected function info(string $message)
    {
        $this->output->writeln('<info>'.$message.'</info>');
    }

    protected function error(string $message)
    {
        $this->output->writeln('<error>'.$message.'</error>');
    }

    protected function comment(string $message)
    {
        $this->output->writeln('<comment>'.$message.'</comment>');
    }


    protected function process(array $command = []): Process
    {
        if (empty($command)) {
            return new Process([]);
        }

        $helper = $this->getHelper('process');
        $process = new Process($command);
        $process->setTimeout(360);

        return $helper->run($this->output, $process);
    }


}