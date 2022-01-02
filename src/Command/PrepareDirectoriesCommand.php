<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class PrepareDirectoriesCommand extends Command
{
    protected static $defaultName = 'prepare:directories';
    protected static $defaultDescription = 'Add a short description for your command';
    private $filesystem;

    protected function configure(): void
    {
        /*  $this
              ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
              ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
          ;*/
        $this->filesystem = new Filesystem();

    }

    private function handleDirectory($dirname, OutputInterface $output, InputInterface $input)
    {
        $io = new SymfonyStyle($input, $output);
        if (!$this->filesystem->exists($dirname)) {
            try {
                $this->filesystem->mkdir($dirname);
                $io->info("Dossier $dirname créé");
                return true;
            } catch (FileException $e) {
                $io->error($e->getMessage());
                return false;
            }
        } else {
            $io->info("Dossier $dirname existant");
            return true;
        }
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $result = $this->handleDirectory($_ENV['DIRARCHIVES'],$output,$input) && $this->handleDirectory($_ENV['DIRSCANNER'],$output,$input) && $this->handleDirectory($_ENV['DIRDOUBLON'],$output,$input) && $this->handleDirectory($_ENV['DIRERROR'],$output,$input);
        if ($result) {
            $io->success('Tous les répertoires sont correctement créés');
            return Command::SUCCESS;
        } else {
            $io->error('Erreur(s) lors de la vérification/creation des répertoires');
            return Command::FAILURE;
        }

    }
}
