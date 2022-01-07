<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class DocgedDoublonsTxtPdfCommand extends Command
{
    protected static $defaultName = 'docged:doublonsTxtPdf';
    protected static $defaultDescription = 'Add a short description for your command';
    private $filesystem;
    public $io;

    protected function configure(): void
    {
        $this->filesystem = new Filesystem();

    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $this->io = new SymfonyStyle($input, $output);

        $finder = new Finder();
        $finder->files()->in($_ENV['DIRSRC'])->depth('== 0');



        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');

        return Command::SUCCESS;
    }
}
