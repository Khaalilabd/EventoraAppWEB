<?php
namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

#[AsCommand(name: 'app:evisugtime:analyze', description: 'Analyze reclamations with EviSugTime')]
class EviSugTimeAnalyzeCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $pythonScript = __DIR__ . '/../../venv/bin/evisugtime/evisugtime_analyzer.py';
        $process = new Process(['python3', $pythonScript]);
        $process->run();

        if (!$process->isSuccessful()) {
            $output->writeln('<error>Error running EviSugTime analysis: ' . $process->getErrorOutput() . '</error>');
            return Command::FAILURE;
        }

        $output->writeln('<info>EviSugTime analysis completed successfully.</info>');
        return Command::SUCCESS;
    }
}